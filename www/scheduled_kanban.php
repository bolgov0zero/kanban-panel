<?php
date_default_timezone_set('Europe/Moscow');

$db_path = __DIR__ . '/db.sqlite';

if (!file_exists($db_path)) {
	error_log('Database file not found: ' . $db_path);
	exit;
}

$db = new SQLite3($db_path);

// Получаем настройки Telegram
$tg_settings = $db->querySingle("SELECT bot_token, chat_id FROM telegram_settings WHERE id=1", true);
$bot_token = $tg_settings['bot_token'] ?? '';
$chat_id = $tg_settings['chat_id'] ?? '';

if (empty($bot_token) || empty($chat_id)) {
	error_log('Telegram settings not configured');
	exit;
}

// Получаем настройки таймеров
$timer_settings = $db->querySingle("SELECT * FROM timer_settings WHERE id=1", true);
if (!$timer_settings) {
	$timer_settings = [
		'timer_hours' => 24,
		'report_time' => '10:00',
		'notify_before_hours' => 2,
		'enabled' => 1
	];
}

// Проверяем, включены ли уведомления
if ($timer_settings['enabled'] == 0) {
	error_log('Timer notifications are disabled');
	exit;
}

// Функция отправки Telegram
function sendTelegram($bot_token, $chat_id, $text) {
	if (empty($bot_token) || empty($chat_id)) return false;
	
	$url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
	$data = [
		'chat_id' => $chat_id,
		'text' => $text,
		'parse_mode' => 'HTML'
	];
	
	$options = [
		'http' => [
			'header' => "Content-type: application/x-www-form-urlencoded\r\n",
			'method' => 'POST',
			'content' => http_build_query($data)
		]
	];
	
	$context = stream_context_create($options);
	$result = @file_get_contents($url, false, $context);
	
	return $result !== false;
}

// === Проверка таймера для задач ===
function checkTaskTimers($db, $bot_token, $chat_id, $timer_settings) {
	$timer_hours = $timer_settings['timer_hours'] ?? 24;
	$notify_before_hours = $timer_settings['notify_before_hours'] ?? 2;
	
	// Получаем задачи с включенным таймером
	$query = "SELECT t.*, c.name as column_name, c.timer as column_timer, 
					 COALESCE(u.name, t.responsible) as responsible_name
			  FROM tasks t 
			  JOIN columns c ON t.column_id = c.id 
			  LEFT JOIN users u ON t.responsible = u.username
			  WHERE c.timer = 1 
				AND t.moved_at IS NOT NULL 
				AND t.completed = 0";
	
	$result = $db->query($query);
	
	while ($task = $result->fetchArray(SQLITE3_ASSOC)) {
		$moved_at = strtotime($task['moved_at']);
		$current_time = time();
		$hours_in_column = ($current_time - $moved_at) / 3600;
		
		// Основное уведомление при достижении лимита
		if ($hours_in_column >= $timer_hours && $hours_in_column < $timer_hours + 0.0167) { // +1 минута
			// Отправляем уведомление
			$title = htmlspecialchars($task['title']);
			$column_name = htmlspecialchars($task['column_name']);
			$responsible = htmlspecialchars($task['responsible_name']);
			
			$message = "⏰ <b>Задача находится в колонке {$timer_hours} часа(ов)</b>\n"
					 . "<blockquote>"
					 . "📋 <b>Задача:</b> <i>{$title}</i>\n"
					 . "📂 <b>Колонка:</b> <i>{$column_name}</i>\n"
					 . "🧑‍💻 <b>Исполнитель:</b> <i>{$responsible}</i>\n"
					 . "⏱️ <b>В колонке:</b> {$timer_hours} часа(ов)\n"
					 . "</blockquote>";
			
			sendTelegram($bot_token, $chat_id, $message);
			error_log("{$timer_hours}-hour notification sent for task ID: {$task['id']}");
		}
		
		// Предварительное уведомление (за N часов до лимита)
		if ($notify_before_hours > 0) {
			$remaining_hours = $timer_hours - $hours_in_column;
			if ($remaining_hours > 0 && $remaining_hours <= $notify_before_hours && $remaining_hours > $notify_before_hours - 0.0167) {
				$title = htmlspecialchars($task['title']);
				$column_name = htmlspecialchars($task['column_name']);
				$responsible = htmlspecialchars($task['responsible_name']);
				
				$message = "⚠️ <b>Скоро истечет время задачи</b>\n"
						 . "<blockquote>"
						 . "📋 <b>Задача:</b> <i>{$title}</i>\n"
						 . "📂 <b>Колонка:</b> <i>{$column_name}</i>\n"
						 . "🧑‍💻 <b>Исполнитель:</b> <i>{$responsible}</i>\n"
						 . "⏱️ <b>Осталось до уведомления:</b> " . round($remaining_hours, 1) . " часа(ов)\n"
						 . "</blockquote>";
				
				sendTelegram($bot_token, $chat_id, $message);
				error_log("Pre-notification sent for task ID: {$task['id']}, remaining: " . round($remaining_hours, 1) . " hours");
			}
		}
	}
}

// === Ежедневный отчет в указанное время ===
function sendDailyReport($db, $bot_token, $chat_id, $timer_settings) {
	$report_time = $timer_settings['report_time'] ?? '10:00';
	
	// Разбираем время отчета
	list($report_hour, $report_minute) = explode(':', $report_time);
	$report_hour = (int)$report_hour;
	$report_minute = (int)$report_minute;
	
	// Текущее время в Москве
	$current_hour = (int)date('H');
	$current_minute = (int)date('i');
	
	// Проверяем, что сейчас указанное время (с допуском в 1 минуту)
	if ($current_hour == $report_hour && $current_minute <= $report_minute + 1) {
		// Получаем все не завершенные задачи, сгруппированные по колонкам
		$query = "SELECT c.name as column_name, t.title as task_title, 
						 COALESCE(u.name, t.responsible) as responsible_name,
						 t.importance,
						 t.deadline,
						 t.created_at
				  FROM tasks t 
				  JOIN columns c ON t.column_id = c.id 
				  LEFT JOIN users u ON t.responsible = u.username
				  WHERE t.completed = 0 
				  ORDER BY c.id, t.importance DESC, t.created_at";
		
		$result = $db->query($query);
		
		$tasks_by_column = [];
		$overdue_tasks = [];
		$today = date('Y-m-d');
		
		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$column_name = $row['column_name'];
			if (!isset($tasks_by_column[$column_name])) {
				$tasks_by_column[$column_name] = [];
			}
			$tasks_by_column[$column_name][] = $row;
			
			// Проверяем просроченные задачи
			if (!empty($row['deadline']) && $row['deadline'] < $today) {
				$overdue_tasks[] = $row;
			}
		}
		
		// Формируем сообщение
		$message = "📊 <b>Ежедневный отчет по открытым задачам</b>\n"
				 . "<i>" . date('d.m.Y') . " {$report_time}</i>\n\n";
		
		if (empty($tasks_by_column)) {
			$message .= "🎉 <b>Все задачи завершены!</b>\nОтличная работа!";
		} else {
			$total_tasks = 0;
			
			foreach ($tasks_by_column as $column_name => $tasks) {
				$message .= "\n<b>📂 Колонка: {$column_name}</b> (" . count($tasks) . ")\n";
				
				foreach ($tasks as $task) {
					$importance_icon = match($task['importance']) {
						'срочно' => '🔴',
						'средне' => '🟡',
						default => '🟢'
					};
					
					$deadline_text = '';
					if (!empty($task['deadline'])) {
						$deadline_date = date('d.m.Y', strtotime($task['deadline']));
						$deadline_text = " 📅 {$deadline_date}";
					}
					
					$message .= "{$importance_icon} <i>{$task['task_title']}</i> (👤 {$task['responsible_name']}){$deadline_text}\n";
				}
				
				$total_tasks += count($tasks);
			}
			
			// Добавляем просроченные задачи
			if (!empty($overdue_tasks)) {
				$message .= "\n<b>🚨 Просроченные задачи:</b> (" . count($overdue_tasks) . ")\n";
				foreach ($overdue_tasks as $task) {
					$deadline_date = date('d.m.Y', strtotime($task['deadline']));
					$message .= "🔴 <i>{$task['task_title']}</i> (👤 {$task['responsible_name']}) - просрочено с {$deadline_date}\n";
				}
			}
			
			$message .= "\n<b>Всего открытых задач:</b> {$total_tasks}";
			if (!empty($overdue_tasks)) {
				$message .= "\n<b>Просрочено:</b> " . count($overdue_tasks);
			}
		}
		
		sendTelegram($bot_token, $chat_id, $message);
		error_log("Daily report sent at " . date('Y-m-d H:i:s'));
	}
}

// Выполняем проверки
try {
	// Проверяем таймеры задач
	checkTaskTimers($db, $bot_token, $chat_id, $timer_settings);
	
	// Проверяем ежедневный отчет
	sendDailyReport($db, $bot_token, $chat_id, $timer_settings);
	
	$db->close();
	
} catch (Exception $e) {
	error_log('Error in scheduled task: ' . $e->getMessage());
	exit(1);
}
?>