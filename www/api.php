<?php
error_reporting(0);
date_default_timezone_set('Europe/Moscow');  // <-- Добавлено: UTC+3 (Москва)
session_start();
if (!isset($_SESSION['user'])) exit('auth required');
$db = new SQLite3(__DIR__ . '/db/db.sqlite');
$db->busyTimeout(3000);
$user = $_SESSION['user'];
$isAdmin = $_SESSION['is_admin'] ?? 0;
$action = $_POST['action'] ?? '';

// Функция отправки Email
function sendEmail($text) {
	global $email_settings;
	if (!($email_settings['enabled'] ?? 0)) return false;
	if (empty($email_settings['host']) || empty($email_settings['to_email'])) return false;

	require_once __DIR__ . '/vendor/autoload.php';
	$mail = new PHPMailer\PHPMailer\PHPMailer(true);
	try {
		$mail->isSMTP();
		$mail->Host = $email_settings['host'];
		$mail->Port = (int)($email_settings['port'] ?? 587);
		$mail->SMTPAuth = !empty($email_settings['username']);
		$mail->Username = $email_settings['username'] ?? '';
		$mail->Password = $email_settings['password'] ?? '';
		$enc = strtolower($email_settings['encryption'] ?? 'tls');
		if ($enc === 'ssl') {
			$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
		} elseif ($enc === 'tls') {
			$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
		} else {
			$mail->SMTPSecure = '';
			$mail->SMTPAutoTLS = false;
		}
		$mail->CharSet = 'UTF-8';
		$mail->Timeout = 5;
		$mail->setFrom($email_settings['from_email'], $email_settings['from_name'] ?? 'Kanban');
		$mail->addAddress($email_settings['to_email']);
		$mail->isHTML(true);
		$mail->Subject = trim(strip_tags(explode("\n", $text)[0])) ?: 'Уведомление Kanban';
		$body = str_replace(
			['<blockquote>', '</blockquote>'],
			['<blockquote style="border-left:3px solid #ccc;margin:4px 0;padding:4px 10px;color:#555;">', '</blockquote>'],
			$text
		);
		$mail->Body = nl2br($body);
		$mail->AltBody = strip_tags(str_replace("\n", " ", $text));
		$mail->send();
		return true;
	} catch (Exception $e) {
		error_log("Email error: " . $mail->ErrorInfo);
		return false;
	}
}

// Функция отправки Telegram
function sendTelegram($bot_token, $chat_id, $text) {
	global $tg_notifications_enabled;
	if (!$tg_notifications_enabled) return false;
	if (empty($bot_token) || empty($chat_id)) return false;
	$url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML']),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 5,
		CURLOPT_CONNECTTIMEOUT => 5,
	]);
	$result = curl_exec($ch);
	curl_close($ch);
	return json_decode($result, true)['ok'] ?? false;
}

// Получаем Telegram настройки
$tg_settings = $db->querySingle("SELECT bot_token, chat_id, daily_report_time, timer_notification_minutes, notifications_enabled FROM telegram_settings WHERE id=1", true);
$bot_token = $tg_settings['bot_token'] ?? '';
$chat_id = $tg_settings['chat_id'] ?? '';
$tg_notifications_enabled = ($tg_settings['notifications_enabled'] ?? 1) ? true : false;

// Получаем Email настройки
$email_settings = $db->querySingle("SELECT * FROM email_settings WHERE id=1", true) ?: [];

// Получаем имя текущего пользователя
$user_name_stmt = $db->prepare("SELECT name FROM users WHERE username = :u");
$user_name_stmt->bindValue(':u', $user, SQLITE3_TEXT);
$user_name = $user_name_stmt->execute()->fetchArray(SQLITE3_ASSOC)['name'] ?? $user;

switch ($action) {
	case 'get_telegram_settings':
		if(!$isAdmin) exit('forbidden');
		$stmt = $db->prepare("SELECT bot_token, chat_id, daily_report_time, timer_notification_minutes, notifications_enabled FROM telegram_settings WHERE id=1");
		$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
		echo json_encode($res ?: ['bot_token' => '', 'chat_id' => '', 'daily_report_time' => '10:00', 'timer_notification_minutes' => 1440, 'notifications_enabled' => 1], JSON_UNESCAPED_UNICODE);
		break;

	case 'save_telegram_settings':
		if(!$isAdmin) exit('forbidden');
		$token = trim($_POST['bot_token'] ?? '');
		$chat = trim($_POST['chat_id'] ?? '');
		$daily_report_time = trim($_POST['daily_report_time'] ?? '10:00');
		$timer_minutes = (int)($_POST['timer_notification_minutes'] ?? 1440);
		$notif_enabled = isset($_POST['notifications_enabled']) ? (int)$_POST['notifications_enabled'] : 1;

		// Валидация времени
		if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $daily_report_time)) {
			$daily_report_time = '10:00';
		}

		// Валидация минут (от 1 минуты до 30 дней)
		if ($timer_minutes < 1) $timer_minutes = 1;
		if ($timer_minutes > 43200) $timer_minutes = 43200; // 30 дней

		$stmt = $db->prepare("INSERT OR REPLACE INTO telegram_settings (id, bot_token, chat_id, daily_report_time, timer_notification_minutes, notifications_enabled) VALUES (1, :t, :c, :drt, :tnm, :ne)");
		$stmt->bindValue(':t', $token, SQLITE3_TEXT);
		$stmt->bindValue(':c', $chat, SQLITE3_TEXT);
		$stmt->bindValue(':drt', $daily_report_time, SQLITE3_TEXT);
		$stmt->bindValue(':tnm', $timer_minutes, SQLITE3_INTEGER);
		$stmt->bindValue(':ne', $notif_enabled, SQLITE3_INTEGER);
		$ok = $stmt->execute();
		echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : $db->lastErrorMsg()]);
		break;

	case 'test_telegram':
		if(!$isAdmin) exit('forbidden');
		$text = "🔔 <b>Тестовое уведомление</b> от Kanban-доски\nДата: " . date('Y-m-d H:i:s');
		$result = sendTelegram($bot_token, $chat_id, $text);
		echo json_encode(['success' => $result]);
		break;

	case 'get_email_settings':
		if(!$isAdmin) exit('forbidden');
		$res = $db->querySingle("SELECT * FROM email_settings WHERE id=1", true);
		echo json_encode($res ?: [], JSON_UNESCAPED_UNICODE);
		break;

	case 'save_email_settings':
		if(!$isAdmin) exit('forbidden');
		$stmt = $db->prepare("INSERT OR REPLACE INTO email_settings
			(id, enabled, host, port, encryption, username, password, from_email, from_name, to_email)
			VALUES (1, :en, :h, :p, :enc, :u, :pw, :fe, :fn, :te)");
		$stmt->bindValue(':en',  (int)($_POST['enabled'] ?? 0),           SQLITE3_INTEGER);
		$stmt->bindValue(':h',   trim($_POST['host'] ?? ''),              SQLITE3_TEXT);
		$stmt->bindValue(':p',   (int)($_POST['port'] ?? 587),            SQLITE3_INTEGER);
		$stmt->bindValue(':enc', trim($_POST['encryption'] ?? 'tls'),     SQLITE3_TEXT);
		$stmt->bindValue(':u',   trim($_POST['username'] ?? ''),          SQLITE3_TEXT);
		$stmt->bindValue(':pw',  trim($_POST['password'] ?? ''),          SQLITE3_TEXT);
		$stmt->bindValue(':fe',  trim($_POST['from_email'] ?? ''),        SQLITE3_TEXT);
		$stmt->bindValue(':fn',  trim($_POST['from_name'] ?? 'Kanban'),   SQLITE3_TEXT);
		$stmt->bindValue(':te',  trim($_POST['to_email'] ?? ''),          SQLITE3_TEXT);
		$ok = $stmt->execute();
		echo json_encode(['success' => (bool)$ok]);
		break;

	case 'test_email':
		if(!$isAdmin) exit('forbidden');
		$text = "🔔 <b>Тестовое уведомление</b> от Kanban-доски\nДата: " . date('Y-m-d H:i:s');
		$result = sendEmail($text);
		echo json_encode(['success' => $result]);
		break;

	// НОВЫЙ CASE: Тестирование таймера
	case 'test_timer_notification':
		if(!$isAdmin) exit('forbidden');

		$timer_minutes = $tg_settings['timer_notification_minutes'] ?? 1440;
		$hours = floor($timer_minutes / 60);
		$mins  = $timer_minutes % 60;
		$time_text = $hours > 0 ? "{$hours}ч {$mins}м" : "{$mins}м";

		$task_row = $db->query("SELECT t.*, c.name as column_name, COALESCE(u.name, t.responsible) as responsible_name
			FROM tasks t JOIN columns c ON t.column_id = c.id LEFT JOIN users u ON t.responsible = u.username
			WHERE c.timer = 1 LIMIT 1")->fetchArray(SQLITE3_ASSOC);

		if ($task_row) {
			$message = "⏰ <b>ТЕСТ: Уведомление о таймере ({$time_text})</b>\n"
				. "<blockquote>"
				. "📋 <b>Задача:</b> <i>" . htmlspecialchars($task_row['title']) . "</i>\n"
				. "📂 <b>Колонка:</b> <i>" . htmlspecialchars($task_row['column_name']) . "</i>\n"
				. "🧑‍💻 <b>Исполнитель:</b> <i>" . htmlspecialchars($task_row['responsible_name']) . "</i>\n"
				. "</blockquote>\n<i>Тестовое уведомление</i>";
		} else {
			$message = "⏰ <b>ТЕСТ: Уведомление о таймере ({$time_text})</b>\n"
				. "<blockquote>📋 <b>Задача:</b> <i>Пример задачи</i>\n📂 <b>Колонка:</b> <i>В работе</i>\n🧑‍💻 <b>Исполнитель:</b> <i>Иван Иванов</i>\n</blockquote>\n<i>Тестовое уведомление (нет задач с таймером)</i>";
		}

		$tg_ok    = sendTelegram($bot_token, $chat_id, $message);
		$email_ok = sendEmail($message);
		if ($tg_ok || $email_ok) echo json_encode(['success' => true]);
		else echo json_encode(['success' => false, 'error' => 'Ни один канал уведомлений не настроен или не отвечает']);
		break;

	case 'test_daily_report':
		if(!$isAdmin) exit('forbidden');

		$res2 = $db->query("SELECT c.name as column_name, t.title as task_title,
				COALESCE(u.name, t.responsible) as responsible_name, t.importance
			FROM tasks t JOIN columns c ON t.column_id = c.id LEFT JOIN users u ON t.responsible = u.username
			WHERE t.completed = 0 ORDER BY c.id, t.importance DESC, t.created_at");

		$tasks_by_column = [];
		while ($row2 = $res2->fetchArray(SQLITE3_ASSOC)) {
			$tasks_by_column[$row2['column_name']][] = $row2;
		}

		$message = "📊 <b>ТЕСТ: Ежедневный отчет</b>\n\n";
		if (empty($tasks_by_column)) {
			$message .= "🎉 <b>Все задачи завершены!</b>";
		} else {
			foreach ($tasks_by_column as $col_name => $col_tasks) {
				$message .= "<b>📂 {$col_name}</b>\n";
				foreach ($col_tasks as $col_task) {
					$message .= "<blockquote>📋 <b>Задача:</b> <i>{$col_task['task_title']}</i>\n🧑‍💻 <b>Исполнитель:</b> <i>{$col_task['responsible_name']}</i></blockquote>";
				}
			}
			$total = array_sum(array_map('count', $tasks_by_column));
			$message .= "\n\n<b>Всего открытых задач:</b> {$total}";
		}

		$tg_ok    = sendTelegram($bot_token, $chat_id, $message);
		$email_ok = sendEmail($message);
		if ($tg_ok || $email_ok) echo json_encode(['success' => true]);
		else echo json_encode(['success' => false, 'error' => 'Ни один канал уведомлений не настроен или не отвечает']);
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
			sendEmail($text);
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
			sendEmail($text);
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
			sendEmail($text);
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
				sendEmail($text);
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
				sendEmail($text);
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