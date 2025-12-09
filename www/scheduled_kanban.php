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

error_log("=== CRON STARTED at " . date('Y-m-d H:i:s') . " ===");
error_log("Telegram configured: " . (!empty($bot_token) ? 'YES' : 'NO'));
error_log("Daily report time: {$daily_report_time}");
error_log("Timer minutes: {$timer_minutes}");

// Функция отправки Telegram
function sendTelegram($bot_token, $chat_id, $text) {
	if (empty($bot_token) || empty($chat_id)) {
		error_log("Cannot send Telegram: bot_token or chat_id empty");
		return false;
	}
	
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
	
	if ($result === false) {
		error_log("Telegram send failed");
		return false;
	}
	
	$response = json_decode($result, true);
	if (!$response['ok']) {
		error_log("Telegram API error: " . ($response['description'] ?? 'Unknown'));
		return false;
	}
	
	return true;
}

// === Проверка таймера для задач (настраиваемое время) ===
function checkTimerNotifications($db, $bot_token, $chat_id, $timer_minutes) {
	error_log("=== Checking timer notifications ===");
	
	// Создаем таблицу для отслеживания отправленных уведомлений, если её нет
	$db->exec("CREATE TABLE IF NOT EXISTS sent_notifications (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		task_id INTEGER NOT NULL,
		notification_type TEXT NOT NULL,
		timer_minutes INTEGER NOT NULL,
		sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		UNIQUE(task_id, notification_type, timer_minutes)
	)");
	
	// Преобразуем минуты в часы для удобства отображения
	$hours = floor($timer_minutes / 60);
	$minutes_remainder = $timer_minutes % 60;
	$time_text = $hours > 0 ? "{$hours}ч {$minutes_remainder}м" : "{$minutes_remainder}м";
	
	// Получаем все задачи с включенным таймером
	$query = "SELECT 
				t.id as task_id,
				t.title,
				t.moved_at,
				t.responsible,
				c.name as column_name,
				c.timer as column_timer,
				COALESCE(u.name, t.responsible) as responsible_name
			  FROM tasks t 
			  JOIN columns c ON t.column_id = c.id 
			  LEFT JOIN users u ON t.responsible = u.username
			  WHERE c.timer = 1 
				AND t.moved_at IS NOT NULL 
				AND t.completed = 0";
	
	$result = $db->query($query);
	
	$found_tasks = 0;
	$notified_tasks = 0;
	
	while ($task = $result->fetchArray(SQLITE3_ASSOC)) {
		$found_tasks++;
		
		$moved_at = strtotime($task['moved_at']);
		$current_time = time();
		$minutes_in_column = ($current_time - $moved_at) / 60;
		
		// Проверяем, достигла ли задача заданного времени
		if ($minutes_in_column >= $timer_minutes) {
			// Проверяем, не отправляли ли уже уведомление для этой задачи с этими настройками
			$task_id = $task['task_id'];
			$already_sent = $db->querySingle("
				SELECT COUNT(*) FROM sent_notifications 
				WHERE task_id = {$task_id} 
				AND notification_type = 'timer' 
				AND timer_minutes = {$timer_minutes}
			");
			
			if (!$already_sent) {
				error_log("✅ Task ID {$task_id} reached {$timer_minutes} minutes, sending notification...");
				
				// Отправляем уведомление
				$title = htmlspecialchars($task['title']);
				$column_name = htmlspecialchars($task['column_name']);
				$responsible = htmlspecialchars($task['responsible_name']);
				
				$message = "⏰ <b>Задача в работе {$time_text}</b>\n"
						 . "<blockquote>"
						 . "📋 <b>Задача:</b> <i>{$title}</i>\n"
						 . "📂 <b>Колонка:</b> <i>{$column_name}</i>\n"
						 . "🧑‍💻 <b>Исполнитель:</b> <i>{$responsible}</i>\n"
						 . "</blockquote>";
				
				if (sendTelegram($bot_token, $chat_id, $message)) {
					// Сохраняем факт отправки уведомления
					$stmt = $db->prepare("
						INSERT INTO sent_notifications (task_id, notification_type, timer_minutes) 
						VALUES (:task_id, 'timer', :timer_minutes)
					");
					$stmt->bindValue(':task_id', $task_id, SQLITE3_INTEGER);
					$stmt->bindValue(':timer_minutes', $timer_minutes, SQLITE3_INTEGER);
					$stmt->execute();
					
					$notified_tasks++;
					error_log("✅ Notification sent and saved for task ID: {$task_id}");
				} else {
					error_log("❌ Failed to send notification for task ID: {$task_id}");
				}
			} else {
				error_log("ℹ️ Notification already sent for task ID: {$task_id} with timer = {$timer_minutes}");
			}
		} else {
			if ($minutes_in_column >= $timer_minutes - 5) {
				error_log("ℹ️ Task ID {$task_id} almost reached time: " . round($minutes_in_column, 1) . " / {$timer_minutes} minutes");
			}
		}
	}
	
	error_log("=== Timer check completed ===");
	error_log("Total tasks found: {$found_tasks}");
	error_log("Tasks notified: {$notified_tasks}");
}

// === Ежедневный отчет в настраиваемое время ===
function sendDailyReport($db, $bot_token, $chat_id, $report_time) {
	error_log("=== Checking daily report ===");
	
	// Создаем таблицу для отслеживания отправленных отчетов, если её нет
	$db->exec("CREATE TABLE IF NOT EXISTS sent_reports (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		report_date DATE NOT NULL,
		report_time TEXT NOT NULL,
		sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		UNIQUE(report_date, report_time)
	)");
	
	// Текущее время в Москве
	$current_time = date('H:i');
	$current_hour = (int)date('H');
	$current_minute = (int)date('i');
	
	list($report_hour, $report_minute) = explode(':', $report_time);
	$report_hour = (int)$report_hour;
	$report_minute = (int)$report_minute;
	
	error_log("Current time: {$current_time}");
	error_log("Report time: {$report_time}");
	
	// Проверяем точное совпадение времени (с допуском ±1 минута для cron)
	if ($current_hour == $report_hour && abs($current_minute - $report_minute) <= 1) {
		// Проверяем, не отправляли ли уже отчет сегодня с этим временем
		$today = date('Y-m-d');
		$already_sent = $db->querySingle("
			SELECT COUNT(*) FROM sent_reports 
			WHERE report_date = '{$today}' 
			AND report_time = '{$report_time}'
		");
		
		if (!$already_sent) {
			error_log("✅ Time condition met! Sending daily report...");
			
			// Получаем все не завершенные задачи
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
			$total_tasks = 0;
			
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$column_name = $row['column_name'];
				if (!isset($tasks_by_column[$column_name])) {
					$tasks_by_column[$column_name] = [];
				}
				$tasks_by_column[$column_name][] = $row;
				$total_tasks++;
			}
			
			error_log("Found {$total_tasks} open tasks");
			
			// Формируем сообщение
			$message = "📊 <b>Ежедневный отчет</b>\n\n";
			
			if (empty($tasks_by_column)) {
				$message .= "🎉 <b>Все задачи завершены!</b>\nОтличная работа!";
			} else {
				foreach ($tasks_by_column as $column_name => $tasks) {
					$message .= "\n<b>📂 {$column_name}</b>\n";
					$message .= "<blockquote>";
					foreach ($tasks as $task) {
						$message .= "📋 <b>Задача:</b> <i>{$task['task_title']}</i>\n👤 <b>Автор:</b> <i>{$task['responsible_name']}</i>\n";
					}
					$message .= "</blockquote>\n\n";
				}
				
				$message .= "\n<b>Всего открытых задач:</b> {$total_tasks}";
			}
			
			if (sendTelegram($bot_token, $chat_id, $message)) {
				// Сохраняем факт отправки отчета
				$stmt = $db->prepare("
					INSERT INTO sent_reports (report_date, report_time) 
					VALUES (:report_date, :report_time)
				");
				$stmt->bindValue(':report_date', $today, SQLITE3_TEXT);
				$stmt->bindValue(':report_time', $report_time, SQLITE3_TEXT);
				$stmt->execute();
				
				error_log("✅ Daily report sent and saved at " . date('Y-m-d H:i:s'));
			} else {
				error_log("❌ Failed to send daily report");
			}
		} else {
			error_log("ℹ️ Daily report already sent today at {$report_time}");
		}
	} else {
		error_log("❌ Time condition NOT met for daily report");
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

error_log("=== CRON FINISHED at " . date('Y-m-d H:i:s') . " ===\n");
?>