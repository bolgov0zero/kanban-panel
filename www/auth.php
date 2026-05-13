<?php
session_start();
$db = new SQLite3(__DIR__ . '/db/db.sqlite');

$first_user = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Проверяем, есть ли пользователи
	$user_count = $db->querySingle("SELECT COUNT(*) FROM users");
	
	if ($user_count == 0) {
		// Создание первого пользователя (админа)
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		$name = trim($_POST['name'] ?? '');
		
		if (empty($username) || empty($password)) {
			$error = "Логин и пароль обязательны";
		} else {
			$hashed_pass = password_hash($password, PASSWORD_DEFAULT);
			$stmt = $db->prepare("INSERT INTO users (username, password, is_admin, name) VALUES (:u, :p, 1, :n)");
			$stmt->bindValue(':u', $username, SQLITE3_TEXT);
			$stmt->bindValue(':p', $hashed_pass, SQLITE3_TEXT);
			$stmt->bindValue(':n', $name, SQLITE3_TEXT);
			$result = $stmt->execute();
			
			if ($result) {
				$_SESSION['user'] = $username;
				$_SESSION['is_admin'] = 1;
				header('Location: index.php');
				exit;
			} else {
				$error = "Ошибка создания пользователя";
			}
		}
	} else {
		// Обычная авторизация
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);

		$stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
		$stmt->bindValue(':username', $username);
		$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

		if ($res && password_verify($password, $res['password'])) {
			$_SESSION['user'] = $res['username'];
			$_SESSION['is_admin'] = $res['is_admin'];
			header('Location: index.php');
			exit;
		} else {
			$error = "Неверное имя пользователя или пароль";
		}
	}
} else {
	// При GET: проверяем, нужно ли создать первого пользователя
	$user_count = $db->querySingle("SELECT COUNT(*) FROM users");
	if ($user_count == 0) {
		$first_user = true;
	}
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>Вход · Kanban</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="design-tokens.css">
	<style>
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
		html, body {
			height: 100%;
			font-family: var(--sm-font-sans);
			background: var(--sm-bg);
			color: var(--sm-fg);
			-webkit-font-smoothing: antialiased;
		}
		body {
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
			padding: 24px;
		}
		.auth-card {
			background: var(--sm-panel);
			border: 1px solid var(--sm-hairline);
			border-radius: var(--sm-r-2xl);
			box-shadow: var(--sm-shadow-modal);
			padding: 40px 36px 36px;
			width: 100%;
			max-width: 380px;
			display: flex;
			flex-direction: column;
			gap: 24px;
		}
		.auth-logo {
			width: 36px;
			height: 36px;
			border-radius: var(--sm-r-md);
			background: var(--sm-fg);
			color: var(--sm-panel);
			font-size: 18px;
			font-family: var(--sm-font-serif);
			font-style: italic;
			font-weight: 700;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.auth-title {
			font-family: var(--sm-font-serif);
			font-style: italic;
			font-size: 28px;
			font-weight: 400;
			letter-spacing: -0.02em;
			color: var(--sm-fg);
			line-height: 1.1;
			margin-top: 4px;
		}
		.auth-title em { color: var(--sm-accent); font-style: italic; }
		.auth-sub {
			font-size: 13px;
			color: var(--sm-fg-mute);
			margin-top: 2px;
		}
		.auth-fields { display: flex; flex-direction: column; gap: 12px; }
		.field-label {
			font-size: 12px;
			font-weight: 500;
			color: var(--sm-fg-dim);
			margin-bottom: 5px;
			display: block;
		}
		.auth-input {
			width: 100%;
			padding: 9px 12px;
			border-radius: var(--sm-r-md);
			border: 1px solid var(--sm-hairline-strong);
			background: var(--sm-bg);
			color: var(--sm-fg);
			font-family: var(--sm-font-sans);
			font-size: 13px;
			outline: none;
			transition: border-color 120ms, box-shadow 120ms;
			appearance: none;
		}
		.auth-input::placeholder { color: var(--sm-fg-faint); }
		.auth-input:focus {
			border-color: var(--sm-fg);
			box-shadow: var(--sm-focus-ring);
		}
		.auth-btn {
			width: 100%;
			padding: 10px 16px;
			border-radius: var(--sm-r-pill);
			background: var(--sm-fg);
			color: var(--sm-bg);
			border: none;
			cursor: pointer;
			font-family: var(--sm-font-sans);
			font-size: 13px;
			font-weight: 500;
			transition: background 120ms;
		}
		.auth-btn:hover { background: #2c2318; }
		.auth-error {
			font-size: 12px;
			color: var(--sm-danger);
			background: var(--sm-danger-soft);
			border-radius: var(--sm-r-md);
			padding: 9px 12px;
		}
		.auth-hint {
			font-size: 11px;
			color: var(--sm-fg-faint);
			text-align: center;
		}
	</style>
</head>
<body>
	<div class="auth-card">
		<div>
			<div class="auth-logo">K</div>
			<?php if ($first_user): ?>
			<div class="auth-title" style="margin-top:14px;">Добро <em>пожаловать</em></div>
			<p class="auth-sub">Создайте первого администратора системы</p>
			<?php else: ?>
			<div class="auth-title" style="margin-top:14px;">Вход в <em>систему</em></div>
			<p class="auth-sub">Kanban · управление задачами</p>
			<?php endif; ?>
		</div>

		<?php if (!empty($error)): ?>
		<div class="auth-error"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>

		<form method="POST" class="auth-fields">
			<div>
				<label class="field-label">Логин</label>
				<input name="username" placeholder="Имя пользователя" class="auth-input" autocomplete="username" required>
			</div>
			<div>
				<label class="field-label">Пароль</label>
				<input name="password" type="password" placeholder="••••••••" class="auth-input" autocomplete="current-password" required>
			</div>
			<?php if ($first_user): ?>
			<div>
				<label class="field-label">Полное имя <span style="color:var(--sm-fg-faint);font-weight:400;">(необязательно)</span></label>
				<input name="name" placeholder="Иван Иванов" class="auth-input">
			</div>
			<?php endif; ?>
			<button type="submit" class="auth-btn" style="margin-top:4px;">
				<?= $first_user ? 'Создать аккаунт' : 'Войти' ?>
			</button>
		</form>

		<?php if ($first_user): ?>
		<p class="auth-hint">Этот аккаунт получит права администратора</p>
		<?php endif; ?>
	</div>
</body>
</html>