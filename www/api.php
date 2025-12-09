<?php
date_default_timezone_set('Europe/Moscow');  // <-- Добавлено: UTC+3 (Москва)
session_start();
if (!isset($_SESSION['user'])) exit('auth required');
$db = new SQLite3(__DIR__ . '/db/db.sqlite');
$user = $_SESSION['user'];
$isAdmin = $_SESSION['is_admin'] ?? 0;
$action = $_POST['action'] ?? '';

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
	$result = file_get_contents($url, false, $context);
	return json_decode($result, true)['ok'] ?? false;
}

// Получаем Telegram настройки
$tg_settings = $db->querySingle("SELECT bot_token, chat_id, daily_report_time, timer_notification_minutes FROM telegram_settings WHERE id=1", true);
$bot_token = $tg_settings['bot_token'] ?? '';
$chat_id = $tg_settings['chat_id'] ?? '';

// Получаем имя текущего пользователя
$user_name_stmt = $db->prepare("SELECT name FROM users WHERE username = :u");
$user_name_stmt->bindValue(':u', $user, SQLITE3_TEXT);
$user_name = $user_name_stmt->execute()->fetchArray(SQLITE3_ASSOC)['name'] ?? $user;

switch ($action) {
	case 'get_telegram_settings':
		if(!$isAdmin) exit('forbidden');
		$stmt = $db->prepare("SELECT bot_token, chat_id, daily_report_time, timer_notification_minutes FROM telegram_settings WHERE id=1");
		$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
		echo json_encode($res ?: ['bot_token' => '', 'chat_id' => '', 'daily_report_time' => '10:00', 'timer_notification_minutes' => 1440], JSON_UNESCAPED_UNICODE);
		break;

	case 'save_telegram_settings':
		if(!$isAdmin) exit('forbidden');
		$token = trim($_POST['bot_token'] ?? '');
		$chat = trim($_POST['chat_id'] ?? '');
		$daily_report_time = trim($_POST['daily_report_time'] ?? '10:00');
		$timer_minutes = (int)($_POST['timer_notification_minutes'] ?? 1440);
		
		// Валидация времени
		if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $daily_report_time)) {
			$daily_report_time = '10:00';
		}
		
		// Валидация минут (от 1 минуты до 30 дней)
		if ($timer_minutes < 1) $timer_minutes = 1;
		if ($timer_minutes > 43200) $timer_minutes = 43200; // 30 дней
		
		$stmt = $db->prepare("INSERT OR REPLACE INTO telegram_settings (id, bot_token, chat_id, daily_report_time, timer_notification_minutes) VALUES (1, :t, :c, :drt, :tnm)");
		$stmt->bindValue(':t', $token, SQLITE3_TEXT);
		$stmt->bindValue(':c', $chat, SQLITE3_TEXT);
		$stmt->bindValue(':drt', $daily_report_time, SQLITE3_TEXT);
		$stmt->bindValue(':tnm', $timer_minutes, SQLITE3_INTEGER);
		$stmt->execute();
		echo json_encode(['success' => true]);
		break;

	case 'test_telegram':
		if(!$isAdmin) exit('forbidden');
		$text = "🔔 <b>Тестовое уведомление</b> от Kanban-доски\nДата: " . date('Y-m-d H:i:s');
		$result = sendTelegram($bot_token, $chat_id, $text);
		echo json_encode(['success' => $result]);
		break;

	// НОВЫЙ CASE: Тестирование таймера
	case 'test_timer_notification':
		if(!$isAdmin) exit('forbidden');
		
		if (empty($bot_token) || empty($chat_id)) {
			echo json_encode(['success' => false, 'error' => 'Telegram не настроен']);
			break;
		}
		
		// Получаем настройки таймера
		$timer_minutes = $tg_settings['timer_notification_minutes'] ?? 1440;
		$hours = floor($timer_minutes / 60);
		$minutes = $timer_minutes % 60;
		$time_text = $hours > 0 ? "{$hours}ч {$minutes}м" : "{$minutes}м";
		
		// Ищем задачу с включенным таймером для демонстрации
		$task_query = "SELECT t.*, c.name as column_name, 
							  COALESCE(u.name, t.responsible) as responsible_name
					   FROM tasks t 
					   JOIN columns c ON t.column_id = c.id 
					   LEFT JOIN users u ON t.responsible = u.username
					   WHERE c.timer = 1 
					   LIMIT 1";
		
		$task = $db->query($task_query)->fetchArray(SQLITE3_ASSOC);
		
		if ($task) {
			// Используем реальную задачу
			$title = htmlspecialchars($task['title']);
			$column_name = htmlspecialchars($task['column_name']);
			$responsible = htmlspecialchars($task['responsible_name']);
			
			$message = "⏰ <b>ТЕСТ: Уведомление о таймере ({$time_text})</b>\n"
					 . "<blockquote>"
					 . "📋 <b>Задача:</b> <i>{$title}</i>\n"
					 . "📂 <b>Колонка:</b> <i>{$column_name}</i>\n"
					 . "🧑‍💻 <b>Исполнитель:</b> <i>{$responsible}</i>\n"
					 . "⏱️ <b>В колонке:</b> {$time_text} (тестовое уведомление)\n"
					 . "</blockquote>\n\n"
					 . "<i>Это тестовое уведомление для проверки работы таймера.</i>";
		} else {
			// Демо-уведомление, если нет задач с таймером
			$message = "⏰ <b>ТЕСТ: Уведомление о таймере ({$time_text})</b>\n"
					 . "<blockquote>"
					 . "📋 <b>Задача:</b> <i>Пример задачи</i>\n"
					 . "📂 <b>Колонка:</b> <i>В работе</i>\n"
					 . "🧑‍💻 <b>Исполнитель:</b> <i>Иван Иванов</i>\n"
					 . "⏱️ <b>В колонке:</b> {$time_text} (тестовое уведомление)\n"
					 . "</blockquote>\n\n"
					 . "<i>Это тестовое уведомление для проверки работы таймера.</i>";
		}
		
		$result = sendTelegram($bot_token, $chat_id, $message);
		echo json_encode(['success' => $result]);
		break;

	// НОВЫЙ CASE: Тестирование ежедневного отчета
	case 'test_daily_report':
		if(!$isAdmin) exit('forbidden');
		
		if (empty($bot_token) || empty($chat_id)) {
			echo json_encode(['success' => false, 'error' => 'Telegram не настроен']);
			break;
		}
		
		// Получаем время отчета из настроек
		$report_time = $tg_settings['daily_report_time'] ?? '10:00';
		
		// Получаем все не завершенные задачи для отчета
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
		
		// Формируем тестовое сообщение
		$message = "📊 <b>ТЕСТ: Ежедневный отчет</b>\n\n";
		
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
		
		$result = sendTelegram($bot_token, $chat_id, $message);
		echo json_encode(['success' => $result]);
		break;

	case 'add_column':
		$stmt = $db->prepare("INSERT INTO columns (name, bg_color, auto_complete, timer) VALUES (:n, :b, :a, :tm)");
		foreach([':n'=>'name', ':b'=>'bg_color'] as $k => $v) $stmt->bindValue($k, $_POST[$v]);
		$stmt->bindValue(':a', (int)($_POST['auto_complete'] ?? 0));
		$stmt->bindValue(':tm', (int)($_POST['timer'] ?? 0));
		$stmt->execute();
		break;

	case 'update_column':
		$stmt = $db->prepare("UPDATE columns SET name=:n, bg_color=:b, auto_complete=:a, timer=:tm WHERE id=:id");
		foreach([':n'=>'name', ':b'=>'bg_color'] as $k => $v) $stmt->bindValue($k, $_POST[$v]);
		$stmt->bindValue(':a', (int)$_POST['auto_complete']);
		$stmt->bindValue(':tm', (int)($_POST['timer'] ?? 0));
		$stmt->bindValue(':id', (int)$_POST['id']);
		$stmt->execute();
		break;

	case 'delete_column':
		if(!$isAdmin) exit('forbidden');
		$id=(int)$_POST['id'];
		$db->exec("DELETE FROM tasks WHERE column_id=$id");
		$db->exec("DELETE FROM columns WHERE id=$id");
		break;

	case 'get_column':
		$id = (int)$_POST['id'];
		echo json_encode($db->query("SELECT * FROM columns WHERE id=$id")->fetchArray(SQLITE3_ASSOC), JSON_UNESCAPED_UNICODE);  // Уже включает timer
		break;

	case 'get_columns':
		$res = $db->query("SELECT id, name FROM columns ORDER BY id");
		$list = []; while ($r = $res->fetchArray(SQLITE3_ASSOC)) $list[] = $r;
		echo json_encode($list, JSON_UNESCAPED_UNICODE);
		break;

	case 'add_task':
		$stmt=$db->prepare("INSERT INTO tasks (title,description,responsible,deadline,importance,column_id,created_at,author) VALUES (:t,:d,:r,:dl,:i,:c,:cr,:a)");
		foreach([':t'=>'title',':d'=>'description',':r'=>'responsible',':dl'=>'deadline',':i'=>'importance',':c'=>'column_id'] as $k=>$v)
			$stmt->bindValue($k,$_POST[$v]);
		$stmt->bindValue(':cr',date('Y-m-d H:i:s'));
		$stmt->bindValue(':a',$user); // Добавляем автора
		$stmt->execute();
		// Уведомление
		if (!empty($bot_token) && !empty($chat_id)) {
			$title = trim($_POST['title'] ?? 'Без названия');
			$resp = trim($_POST['responsible'] ?? 'Не указан');
			$resp_name = $db->querySingle("SELECT name FROM users WHERE username='$resp'", true)['name'] ?? $resp;
			$text = "🆕 <b>Новая задача</b>\n<blockquote>👤 <b>Автор:</b> <i>$user_name</i>\n📋 <b>Задача:</b> <i>$title</i>\n🧑‍💻 <b>Исполнитель:</b> <i>$resp_name</i></blockquote>";
			sendTelegram($bot_token, $chat_id, $text);
		}
		break;

	case 'update_task':
		$stmt=$db->prepare("UPDATE tasks SET title=:t,description=:d,responsible=:r,deadline=:dl,importance=:i WHERE id=:id");
		foreach([':t'=>'title',':d'=>'description',':r'=>'responsible',':dl'=>'deadline',':i'=>'importance'] as $k=>$v)
			$stmt->bindValue($k,$_POST[$v]);
		$stmt->bindValue(':id',(int)$_POST['id']);
		$stmt->execute();break;

	case 'delete_task':
		if(!$isAdmin) exit('forbidden');
		$id=(int)$_POST['id'];
		// Получаем данные задачи перед удалением
		$task_data = $db->querySingle("SELECT title FROM tasks WHERE id=$id", true);
		$db->exec("DELETE FROM tasks WHERE id=$id");
		// Уведомление
		if (!empty($bot_token) && !empty($chat_id) && $task_data) {
			$text = "🚮 <b>Задача удалена</b>\n<blockquote>👤 <b>Кем:</b> <i>$user_name</i>\n📋 <b>Задача:</b> <i>{$task_data['title']}</i></blockquote>";
			sendTelegram($bot_token, $chat_id, $text);
		}
		break;

	// <-- НОВЫЙ CASE: Загрузка данных задачи для редактирования
	case 'get_task':
		$id = (int)$_POST['id'];
		$stmt = $db->prepare("SELECT * FROM tasks WHERE id = :id");
		$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
		$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
		echo json_encode($res ?: [], JSON_UNESCAPED_UNICODE);
		break;

	case 'move_task':
		$task_id = (int)$_POST['task_id'];
		$col_id = (int)$_POST['column_id'];
		
		// Получаем текущую колонку задачи (старая)
		$old_col_id = $db->querySingle("SELECT column_id FROM tasks WHERE id = $task_id");
		$old_auto_complete = $db->querySingle("SELECT auto_complete FROM columns WHERE id = $old_col_id") ?? 0;
		
		// Обновляем колонку и moved_at
		$stmt = $db->prepare("UPDATE tasks SET column_id = :c, moved_at = :m WHERE id = :t");
		$stmt->bindValue(':c', $col_id, SQLITE3_INTEGER);
		$stmt->bindValue(':m', date('Y-m-d H:i:s'), SQLITE3_TEXT);
		$stmt->bindValue(':t', $task_id, SQLITE3_INTEGER);
		$stmt->execute();
		
		// ОЧИСТКА УВЕДОМЛЕНИЙ: Удаляем все отправленные уведомления для этой задачи
		$db->exec("DELETE FROM sent_notifications WHERE task_id = {$task_id}");
		error_log("Cleared notifications for task ID: {$task_id} after move");
		
		// Получаем auto_complete новой колонки
		$new_auto_complete = $db->querySingle("SELECT auto_complete FROM columns WHERE id = $col_id") ?? 0;
		
		// Устанавливаем completed в соответствии с новой колонкой
		$db->exec("UPDATE tasks SET completed = $new_auto_complete WHERE id = $task_id");
		
		// Уведомление: логика в зависимости от старой и новой колонки
		$task_title = $db->querySingle("SELECT title FROM tasks WHERE id=$task_id", true)['title'] ?? 'Без названия';
		$col_name = $db->querySingle("SELECT name FROM columns WHERE id=$col_id", true)['name'] ?? 'Неизвестная колонка';
		$resp = $db->querySingle("SELECT responsible FROM tasks WHERE id=$task_id", true)['responsible'] ?? 'Не указан';
		$resp_name = $db->querySingle("SELECT name FROM users WHERE username='$resp'", true)['name'] ?? $resp;
		
		if (!empty($bot_token) && !empty($chat_id)) {
			if ($new_auto_complete == 1) {
				// Перемещение в завершающую колонку
				$text = "✅ <b>Задача завершена</b>\n<blockquote>👤 <b>Кем:</b> <i>$user_name</i>\n📋 <b>Задача:</b> <i>$task_title</i>\n🧑‍💻 <b>Исполнитель:</b> <i>$resp_name</i></blockquote>";
			} elseif ($old_auto_complete == 1 && $new_auto_complete == 0) {
				// Возобновление из завершающей колонки
				$text = "🔄 <b>Задача возобновлена</b>\n<blockquote>👤 <b>Кем:</b> <i>$user_name</i>\n📋 <b>Задача:</b> <i>$task_title</i>\n📂 <b>В колонку:</b> <i>$col_name</i>\n🧑‍💻 <b>Исполнитель:</b> <i>$resp_name</i></blockquote>";
			} else {
				// Обычное перемещение
				$text = "↔️ <b>Задача перемещена</b>\n<blockquote>👤 <b>Кем:</b> <i>$user_name</i>\n📋 <b>Задача:</b> <i>$task_title</i>\n📂 <b>В колонку:</b> <i>$col_name</i></blockquote>";
			}
			sendTelegram($bot_token, $chat_id, $text);
		}
		break;

	case 'archive_now':
		$id = (int)$_POST['id'];
		$row = $db->querySingle("SELECT * FROM tasks WHERE id=$id", true);
		if ($row) {
			$stmt=$db->prepare("INSERT INTO archive (title,description,responsible,responsible_name,deadline,importance,archived_at)
				VALUES (:t,:d,:r,:rn,:dl,:i,:a)");
			foreach([':t'=>'title',':d'=>'description',':r'=>'responsible',':dl'=>'deadline',':i'=>'importance'] as $k=>$v)
				$stmt->bindValue($k,$row[$v]);
			$resp_name = $db->querySingle("SELECT name FROM users WHERE username='{$row['responsible']}'", true)['name'] ?? $row['responsible'];
			$stmt->bindValue(':rn', $resp_name);
			$stmt->bindValue(':a',date('Y-m-d H:i:s'));
			$stmt->execute();
			$db->exec("DELETE FROM tasks WHERE id=$id");
			// Уведомление (обновлено на имя)
			if (!empty($bot_token) && !empty($chat_id)) {
				$title = $row['title'] ?? 'Без названия';
				$resp_name = $row['responsible_name'] ?? 'Не указан';
				$text = "⏸️ <b>Задача заархивирована</b>\n<blockquote>👤 <b>Кем:</b> <i>$user_name</i>\n📋 <b>Задача:</b> <i>$title</i></blockquote>";
				sendTelegram($bot_token, $chat_id, $text);
			}
		} 
		break;

	case 'get_archive':
		$res=$db->query("SELECT * FROM archive ORDER BY archived_at DESC");
		$list=[];while($r=$res->fetchArray(SQLITE3_ASSOC))$list[]=$r;
		echo json_encode($list,JSON_UNESCAPED_UNICODE);
		break;

	case 'restore_task':
		$id=(int)$_POST['id'];
		$row=$db->query("SELECT * FROM archive WHERE id=$id")->fetchArray(SQLITE3_ASSOC);
		if($row){
			$stmt=$db->prepare("INSERT INTO tasks (title,description,responsible,deadline,importance,column_id,created_at)
				VALUES (:t,:d,:r,:dl,:i,:c,:cr)");
			foreach([':t'=>'title',':d'=>'description',':r'=>'responsible',':dl'=>'deadline',':i'=>'importance'] as $k=>$v)
				$stmt->bindValue($k,$row[$v]);
			$stmt->bindValue(':c',1); // возвращаем в первую колонку
			$stmt->bindValue(':cr',date('Y-m-d H:i:s'));
			$stmt->execute();
			$db->exec("DELETE FROM archive WHERE id=$id");
			// Уведомление о восстановлении
			if (!empty($bot_token) && !empty($chat_id)) {
				$title = $row['title'] ?? 'Без названия';
				$resp = $row['responsible'] ?? 'Не указан';
				$resp_name = $db->querySingle("SELECT name FROM users WHERE username='$resp'", true)['name'] ?? $resp;
				$first_col = $db->querySingle("SELECT name FROM columns WHERE id=1");
				$text = "↩️ <b>Задача восстановлена</b>\n<blockquote>👤 <b>Кем:</b> <i>$user_name</i>\n📋 <b>Задача:</b> <i>$title</i></blockquote>";
				sendTelegram($bot_token, $chat_id, $text);
			}
		} break;

	case 'get_users':
		$res=$db->query("SELECT username, is_admin, name FROM users ORDER BY username");
		$list=[];while($r=$res->fetchArray(SQLITE3_ASSOC))$list[]=$r;
		echo json_encode($list,JSON_UNESCAPED_UNICODE);break;

	case 'get_user':
		if(!$isAdmin) exit('forbidden');
		$username = trim($_POST['username']);
		$stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
		$stmt->bindValue(':u', $username, SQLITE3_TEXT);
		$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
		echo json_encode($res, JSON_UNESCAPED_UNICODE);
		break;

	case 'add_user':
		if(!$isAdmin) exit('forbidden');
		$username=trim($_POST['username']);
		$pass=password_hash(trim($_POST['password']),PASSWORD_DEFAULT);
		$is_adm=(int)($_POST['is_admin']??0);
		$full_name=trim($_POST['name']??'');
		$stmt = $db->prepare("INSERT INTO users (username, password, is_admin, name) VALUES (:u, :p, :a, :n)");
		$stmt->bindValue(':u', $username, SQLITE3_TEXT);
		$stmt->bindValue(':p', $pass, SQLITE3_TEXT);
		$stmt->bindValue(':a', $is_adm, SQLITE3_INTEGER);
		$stmt->bindValue(':n', $full_name, SQLITE3_TEXT);
		$stmt->execute();
		break;

	case 'update_user':
		if(!$isAdmin) exit('forbidden');
		$username=trim($_POST['username']);
		$is_adm=(int)($_POST['is_admin']??0);
		$full_name=trim($_POST['name']??'');
		$password = trim($_POST['password'] ?? '');
		if ($password) {
			$hashed_pass = password_hash($password, PASSWORD_DEFAULT);
			$stmt = $db->prepare("UPDATE users SET is_admin=:a, name=:n, password=:p WHERE username=:u");
			$stmt->bindValue(':p', $hashed_pass, SQLITE3_TEXT);
		} else {
			$stmt = $db->prepare("UPDATE users SET is_admin=:a, name=:n WHERE username=:u");
		}
		$stmt->bindValue(':a', $is_adm, SQLITE3_INTEGER);
		$stmt->bindValue(':n', $full_name, SQLITE3_TEXT);
		$stmt->bindValue(':u', $username, SQLITE3_TEXT);
		$stmt->execute();
		break;

	case 'delete_user':
		if(!$isAdmin) exit('forbidden');
		$name=trim($_POST['username']);
		$db->exec("DELETE FROM users WHERE username='$name' AND username!='user1'");
		break;
		
	case 'clear_archive':
		if(!$isAdmin) exit('forbidden');
		$db->exec("DELETE FROM archive");
		echo json_encode(['success' => true]);
		break;
		
	case 'get_links':
		$res = $db->query("SELECT id, name, url FROM links ORDER BY name");
		$list = [];
		while ($r = $res->fetchArray(SQLITE3_ASSOC)) $list[] = $r;
		echo json_encode($list, JSON_UNESCAPED_UNICODE);
		break;
	
	case 'add_link':
		$name = trim($_POST['name']);
		$url = trim($_POST['url']);
		if ($name && $url) {
			$stmt = $db->prepare("INSERT INTO links (name, url) VALUES (:n, :u)");
			$stmt->bindValue(':n', $name);
			$stmt->bindValue(':u', $url);
			$stmt->execute();
		}
		echo json_encode(['success' => true]);
		break;
	
	case 'delete_link':
		$id = (int)$_POST['id'];
		$db->exec("DELETE FROM links WHERE id = $id");
		echo json_encode(['success' => true]);
		break;
}
?>