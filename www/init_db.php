<?php
date_default_timezone_set('Europe/Moscow');
$db = new SQLite3(__DIR__ . '/var/www/html/db/db.sqlite');

function ensureColumn($table, $column, $definition) {
	global $db;
	$exists = false;
	$res = $db->query("PRAGMA table_info($table)");
	while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
		if ($row['name'] === $column) {
			$exists = true;
			break;
		}
	}
	if (!$exists) {
		$db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
	}
}

// === Создание таблиц ===
$db->exec("CREATE TABLE IF NOT EXISTS users (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	username TEXT UNIQUE,
	password TEXT,
	is_admin INTEGER DEFAULT 0,
	name TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS telegram_settings (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	bot_token TEXT,
	chat_id TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS columns (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT NOT NULL,
	bg_color TEXT DEFAULT '#374151',
	auto_complete INTEGER DEFAULT 0,
	timer INTEGER DEFAULT 0
)");

$db->exec("CREATE TABLE IF NOT EXISTS tasks (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	title TEXT,
	description TEXT,
	responsible TEXT,
	deadline TEXT,
	importance TEXT,
	column_id INTEGER,
	completed INTEGER DEFAULT 0,
	created_at TEXT,
	moved_at TEXT,
	author TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS archive (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	title TEXT,
	description TEXT,
	responsible TEXT,
	responsible_name TEXT,
	deadline TEXT,
	importance TEXT,
	archived_at TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS links (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT NOT NULL,
	url TEXT NOT NULL
)");

// === Дополнительные поля ===
ensureColumn('users', 'name', 'TEXT');
ensureColumn('columns', 'auto_complete', 'INTEGER DEFAULT 0');
ensureColumn('columns', 'timer', 'INTEGER DEFAULT 0');
ensureColumn('tasks', 'completed', 'INTEGER DEFAULT 0');
ensureColumn('tasks', 'created_at', 'TEXT');
ensureColumn('tasks', 'moved_at', 'TEXT');
ensureColumn('tasks', 'author', 'TEXT');
ensureColumn('archive', 'responsible_name', 'TEXT');

// === Начальные Telegram настройки: добавляем только если не существуют ===
$tg_exists = $db->querySingle("SELECT COUNT(*) FROM telegram_settings WHERE id=1");
if ($tg_exists == 0) {
	$stmt = $db->prepare("INSERT INTO telegram_settings (id, bot_token, chat_id) VALUES (1, '', '')");
	$stmt->execute();
}

// === Заполнение responsible_name для существующих записей в archive (если нужно) ===
$archive_count = $db->querySingle("SELECT COUNT(*) FROM archive");
if ($archive_count > 0) {
	$db->exec("
		UPDATE archive 
		SET responsible_name = COALESCE(u.name, archive.responsible) 
		FROM users u 
		WHERE archive.responsible = u.username
	");
}

// === Создаем несколько начальных колонок если их нет ===
$columns_count = $db->querySingle("SELECT COUNT(*) FROM columns");
if ($columns_count == 0) {
	$initial_columns = [
		['name' => 'Новые', 'bg_color' => '#374151', 'auto_complete' => 0, 'timer' => 0],
		['name' => 'В работе', 'bg_color' => '#1e40af', 'auto_complete' => 0, 'timer' => 1],
		['name' => 'На проверке', 'bg_color' => '#7c3aed', 'auto_complete' => 0, 'timer' => 0],
		['name' => 'Завершено', 'bg_color' => '#059669', 'auto_complete' => 1, 'timer' => 0]
	];
	
	$stmt = $db->prepare("INSERT INTO columns (name, bg_color, auto_complete, timer) VALUES (:name, :bg_color, :auto_complete, :timer)");
	
	foreach ($initial_columns as $column) {
		$stmt->bindValue(':name', $column['name'], SQLITE3_TEXT);
		$stmt->bindValue(':bg_color', $column['bg_color'], SQLITE3_TEXT);
		$stmt->bindValue(':auto_complete', $column['auto_complete'], SQLITE3_INTEGER);
		$stmt->bindValue(':timer', $column['timer'], SQLITE3_INTEGER);
		$stmt->execute();
	}
}

echo "<h2 class='text-green-500 font-bold'>База данных успешно инициализирована!</h2>";
echo "<p>Создано несколько начальных колонок для работы.</p>";
echo "<p><a href='auth.php' class='text-blue-400 underline'>Перейти к авторизации</a></p>";
?>