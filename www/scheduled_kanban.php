<?php
date_default_timezone_set('Europe/Moscow');

$db_path = __DIR__ . '/db/db.sqlite';

if (!file_exists($db_path)) {
	error_log('Database file not found: ' . $db_path);
	exit;
}

$db = new SQLite3($db_path);

// Получаем настройки Telegram
$tg_settings = $db->querySingle("SELECT bot_token, chat_id, daily_report_time, timer_notification_minutes FROM telegram_settings WHERE id=1", true);
$bot_token = $tg_settings['bot_token'] ?? '';
$chat_id = $tg_settings['chat_id'] ?? '';
$daily_report_time = $tg_settings['daily_report_time'] ?? '10:00';
$timer_minutes = $tg_settings['timer_notification_minutes'] ?? 1440;

if (empty($bot_token) || empty($chat_id)) {
	error_log('Telegram settings not configured');
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

// === Проверка таймера для задач (настраиваемое время) ===
function checkTimerNotifications($db, $bot_token, $chat_id, $timer_minutes) {
	// Преобразуем минуты в часы для удобства отображения
	$hours = floor($timer_minutes / 60);
	$minutes_remainder = $timer_minutes % 60;
	$time_text = $hours > 0 ? "{$hours}ч {$minutes_remainder}м" : "{$minutes_remainder}м";
	
	// Получаем текущую минуту часа (0-59)
	$current_minute = (int)date('i');
	
	// Запускаем проверку только в определенные минуты (например, каждую 5-ю минуту)
	// Это предотвратит многократную проверку одной и той же задачи
	if ($current_minute % 5 !== 0) {
		return;
	}
	
	// Получаем задачи с включенным таймером, которые находятся в колонках с таймером
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
		$minutes_in_column = ($current_time - $moved_at) / 60;
		
		// Проверяем, прошло ли заданное количество минут
		// Используем точное сравнение с небольшим допуском для задач, которые только что достигли времени
		if ($minutes_in_column >= $timer_minutes && $minutes_in_column < $timer_minutes + 1) {
			// Проверяем, не было ли уже уведомления для этой задачи сегодня
			$task_id = $task['id'];
			$today = date('Y-m-d');
			$last_notification = $db->querySingle("SELECT value FROM task_notifications WHERE task_id = {$task_id} AND notification_type = 'timer' AND date = '{$today}'");
			
			if (!$last_notification) {
				// Отправляем уведомление
				$title = htmlspecialchars($task['title']);
				$column_name = htmlspecialchars($task['column_name']);
				$responsible = htmlspecialchars($task['responsible_name']);
				
				$message = "⏰ <b>Задача находится в колонке {$time_text}</b>\n"
						 . "<blockquote>"
						 . "📋 <b>Задача:</b> <i>{$title}</i>\n"
						 . "📂 <b>Колонка:</b> <i>{$column_name}</i>\n"
						 . "🧑‍💻 <b>Исполнитель:</b> <i>{$responsible}</i>\n"
						 . "⏱️ <b>В колонке:</b> {$time_text}\n"
						 . "</blockquote>";
				
				if (sendTelegram($bot_token, $chat_id, $message)) {
					// Сохраняем факт отправки уведомления
					$db->exec("INSERT INTO task_notifications (task_id, notification_type, date, sent_at) VALUES ({$task_id}, 'timer', '{$today}', datetime('now'))");
					error_log("Timer notification sent for task ID: {$task_id} after {$timer_minutes} minutes");
				}
			}
		}
	}
}

// === Ежедневный отчет в настраиваемое время ===
function sendDailyReport($db, $bot_token, $chat_id, $report_time) {
	// Текущее время в Москве
	$current_time = date('H:i');
	
	// Проверяем точное совпадение времени
	if ($current_time === $report_time) {
		// Проверяем, не отправляли ли отчет в эту минуту уже
		$today = date('Y-m-d');
		$last_report = $db->querySingle("SELECT value FROM system_logs WHERE key = 'last_daily_report' AND value = '{$today}_{$report_time}'");
		
		if (!$last_report) {
			// Получаем все не завершенные задачи, сгруппированные по колонкам
			$query = "SELECT c.name as column_name, t.title as task_title, 
							 COALESCE(u.name, t.responsible) as responsible_name,
							 t.importance
					  FROM tasks t 
					  JOIN columns c ON t.column_id = c.id 
					  LEFT JOIN users u ON t.responsible = u.username
					  WHERE t.completed = 0 
					  ORDER BY c.id, t.importance DESC, t.created_at";
			
			$result = $db->query($query);
			
			$tasks_by_column = [];
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$column_name = $row['column_name'];
				if (!isset($tasks_by_column[$column_name])) {
					$tasks_by_column[$column_name] = [];
				}
				$tasks_by_column[$column_name][] = $row;
			}
			
			// Формируем сообщение
			$message = "📊 <b>Ежедневный отчет по открытым задачам</b>\n"
					 . "<i>" . date('d.m.Y') . " {$report_time}</i>\n\n";
			
			if (empty($tasks_by_column)) {
				$message .= "🎉 <b>Все задачи завершены!</b>\nОтличная работа!";
			} else {
				foreach ($tasks_by_column as $column_name => $tasks) {
					$message .= "\n<b>📂 Колонка: {$column_name}</b>\n";
					
					foreach ($tasks as $task) {
						$importance_icon = match($task['importance']) {
							'срочно' => '🔴',
							'средне' => '🟡',
							default => '🟢'
						};
						
						$message .= "{$importance_icon} <i>{$task['task_title']}</i> (👤 {$task['responsible_name']})\n";
					}
				}
				
				$total_tasks = array_sum(array_map('count', $tasks_by_column));
				$message .= "\n<b>Всего открытых задач:</b> {$total_tasks}";
			}
			
			if (sendTelegram($bot_token, $chat_id, $message)) {
				// Сохраняем факт отправки отчета
				$report_key = "{$today}_{$report_time}";
				$db->exec("INSERT OR REPLACE INTO system_logs (key, value) VALUES ('last_daily_report', '{$report_key}')");
				error_log("Daily report sent at " . date('Y-m-d H:i:s') . " (scheduled time: {$report_time})");
			}
		}
	}
}

// Выполняем проверки
try {
	// Проверяем таймер с настраиваемым временем
	checkTimerNotifications($db, $bot_token, $chat_id, $timer_minutes);
	
	// Проверяем ежедневный отчет с настраиваемым временем
	sendDailyReport($db, $bot_token, $chat_id, $daily_report_time);
	
	$db->close();
	
} catch (Exception $e) {
	error_log('Error in scheduled task: ' . $e->getMessage());
	exit(1);
}
?>