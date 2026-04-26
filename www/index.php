<?php
date_default_timezone_set('Europe/Moscow');
session_start();
if (!isset($_SESSION['user'])) { header('Location: auth.php'); exit; }

$db = new SQLite3(__DIR__ . '/db/db.sqlite');
$user = $_SESSION['user'];
$isAdmin = $_SESSION['is_admin'] ?? 0;

// автоархив через 6 часов после завершения
$tasks = $db->query("SELECT t.*, COALESCE(u.name, t.responsible) AS responsible_name FROM tasks t LEFT JOIN users u ON t.responsible = u.username WHERE completed = 1");
while ($t = $tasks->fetchArray(SQLITE3_ASSOC)) {
	$completionTime = !empty($t['moved_at']) ? $t['moved_at'] : $t['created_at'];
	if (time() - strtotime($completionTime) > 21600) {
		$stmt = $db->prepare("INSERT INTO archive (title, description, responsible, responsible_name, deadline, importance, archived_at) VALUES (:t,:d,:r,:rn,:dl,:i,:a)");
		foreach([':t'=>'title',':d'=>'description',':r'=>'responsible',':dl'=>'deadline',':i'=>'importance'] as $k=>$v)
			$stmt->bindValue($k, $t[$v]);
		$stmt->bindValue(':rn', $t['responsible_name']);
		$stmt->bindValue(':a', date('Y-m-d H:i:s'));
		$stmt->execute();
		$db->exec("DELETE FROM tasks WHERE id={$t['id']}");
	}
}

// Получаем имена всех пользователей
$userNames = [];
$resUsers = $db->query("SELECT username, name FROM users");
while ($u = $resUsers->fetchArray(SQLITE3_ASSOC)) {
	$userNames[$u['username']] = $u['name'] ?: $u['username'];
}
$user_name = $userNames[$user] ?? $user;

function getAvatarFromName($name) {
	if (empty($name)) return '?';
	$words = explode(' ', trim($name));
	$initials = '';
	if (isset($words[0]) && !empty($words[0])) {
		$initials .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($words[0], 0, 1, 'UTF-8'), 'UTF-8') : substr($words[0], 0, 2);
	}
	if (isset($words[1]) && !empty($words[1])) {
		$initials .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($words[1], 0, 1, 'UTF-8'), 'UTF-8') : substr($words[1], 0, 2);
	}
	return $initials ?: (function_exists('mb_substr') ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') : substr($name, 0, 2));
}

function getUserColor($username) {
	$colors = ['#00d4aa','#7c5cff','#ff5d6c','#ffb547','#4adf8a','#00b4d8','#e040fb','#ff7043'];
	return $colors[abs(crc32($username)) % count($colors)];
}

$userAvatars = [];
$userColors  = [];
foreach ($userNames as $username => $name) {
	$userAvatars[$username] = getAvatarFromName($name);
	$userColors[$username]  = getUserColor($username);
}

$columns = $db->query("SELECT * FROM columns ORDER BY id");
$totalTasks = $db->querySingle("SELECT COUNT(*) FROM tasks WHERE completed=0");

$version_data = json_decode(file_get_contents(__DIR__ . '/version.json'), true);
$version = $version_data['version'] ?? '—';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Kanban Board</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Ambient background -->
<div class="bg-ambient"></div>

<!-- Topbar -->
<header class="topbar">
	<div class="topbar-left">
		<div class="logo">K</div>
		<span class="brand">Kanban Доска</span>
		<span class="topbar-meta"><?= $totalTasks ?> задач · <?= date('d.m.Y') ?></span>
	</div>
	<div class="topbar-right">
		<button onclick="openAddTask()" class="icon-btn" title="Новая задача">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
		</button>
		<button onclick="openArchive()" class="icon-btn" title="Архив">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8M1 3h22v5H1zM10 12h4"/></svg>
		</button>
		<?php if ($isAdmin): ?>
		<button onclick="openUserSettings()" class="icon-btn" title="Настройки">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06A2 2 0 1 1 7.04 4.24l.06.06A1.7 1.7 0 0 0 9 4.64 1.7 1.7 0 0 0 10.04 3.08V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9c.62.26 1.04.86 1.04 1.56V11a2 2 0 0 1 0 4h-.09a1.7 1.7 0 0 0-1.56 1Z"/></svg>
		</button>
		<?php endif; ?>
		<div class="topbar-divider"></div>
		<div class="user-chip">
			<span class="avatar" style="background: <?= getUserColor($user) ?>"><?= getAvatarFromName($user_name) ?></span>
			<?= htmlspecialchars($user_name) ?>
		</div>
		<a href="logout.php" class="icon-btn" title="Выйти">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
		</a>
	</div>
</header>

<!-- Board -->
<div class="board" id="board">
	<?php while ($col = $columns->fetchArray(SQLITE3_ASSOC)):
		$tasks_count = $db->querySingle("SELECT COUNT(*) FROM tasks WHERE column_id={$col['id']}");
		$accent = htmlspecialchars($col['bg_color']);
	?>
	<div class="column"
		 data-col-id="<?= $col['id'] ?>"
		 data-col-bg="<?= $accent ?>"
		 data-auto-complete="<?= $col['auto_complete'] ?>"
		 data-timer="<?= $col['timer'] ?>"
		 style="--col-color:<?= $accent ?>;"
		 ondrop="drop(event)"
		 ondragover="allowDrop(event)"
		 ondragenter="highlightDrop(this,true,event)"
		 ondragleave="highlightDrop(this,false,event)">

		<div class="column-header">
			<div class="column-title">
				<span class="dot"></span>
				<?= htmlspecialchars($col['name']) ?>
			</div>
			<div class="column-actions">
				<span class="count-chip"><?= $tasks_count ?></span>
				<button onclick="editColumn(<?= $col['id'] ?>)" class="icon-btn icon-btn-sm" title="Редактировать">
					<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
				</button>
			</div>
		</div>

		<div class="col-list" id="col<?= $col['id'] ?>">
			<?php
			$tq = $db->query("SELECT t.*,
					COALESCE(u1.name, t.responsible) as responsible_display_name,
					COALESCE(u2.name, t.author) as author_display_name,
					c.timer as column_timer
				FROM tasks t
				LEFT JOIN users u1 ON t.responsible = u1.username
				LEFT JOIN users u2 ON t.author = u2.username
				JOIN columns c ON t.column_id = c.id
				WHERE t.column_id={$col['id']}
				ORDER BY t.created_at DESC");

			while($task = $tq->fetchArray(SQLITE3_ASSOC)):
				$importance = $task['importance'] ?? 'не срочно';
				$priClass = $importance === 'срочно' ? 'p-high' : ($importance === 'средне' ? 'p-med' : 'p-low');
				$isUrgent  = $importance === 'срочно';

				$author     = $task['author'] ?? $user;
				$authorName = $task['author_display_name'] ?? $author;
				$respName   = $task['responsible_display_name'] ?? $task['responsible'];
				$authorAvatar = $userAvatars[$author] ?? getAvatarFromName($author);
				$respAvatar   = $userAvatars[$task['responsible']] ?? getAvatarFromName($task['responsible']);
				$respColor    = $userColors[$task['responsible']] ?? getUserColor($task['responsible']);
			?>
			<div draggable="true"
				 ondragstart="drag(event)"
				 id="task<?= $task['id'] ?>"
				 class="card<?= $task['completed'] ? ' card--done' : '' ?>"
				 style="--col-color:<?= $accent ?>;"
				 <?php if($col['timer'] && !empty($task['moved_at'])): ?>
				 data-moved-at="<?= htmlspecialchars($task['moved_at']) ?>"
				 data-task-id="<?= $task['id'] ?>"
				 <?php endif; ?>>

				<div class="card-meta">
					<span class="card-time<?= $isUrgent ? ' urgent' : '' ?> created-date" data-created="<?= htmlspecialchars($task['created_at']) ?>"></span>
					<button onclick="editTask(<?= $task['id'] ?>)" class="icon-btn icon-btn-sm" title="Редактировать">
						<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
					</button>
				</div>

				<div class="card-title"><?= htmlspecialchars($task['title']) ?></div>

				<?php if (!empty($task['description'])): ?>
				<div class="card-desc"><?= htmlspecialchars($task['description']) ?></div>
				<?php endif; ?>

				<div class="card-foot">
					<div style="display:flex;flex-direction:column;gap:4px;">
						<span class="priority <?= $priClass ?>">
							<span class="pdot"></span><?= htmlspecialchars($importance) ?>
						</span>
						<?php if (!empty($task['deadline'])): ?>
						<span class="card-time deadline-tag" data-deadline="<?= htmlspecialchars($task['deadline']) ?>" style="font-size:10px;">
							<span class="deadline-text"></span>
						</span>
						<?php endif; ?>
						<?php if($col['timer'] && !empty($task['moved_at'])): ?>
						<span class="card-time timer-display" id="timer-<?= $task['id'] ?>" style="font-size:10px;">⏱ —</span>
						<?php endif; ?>
					</div>
					<div class="card-people">
						<span class="avatar avatar-ghost" title="Автор: <?= htmlspecialchars($authorName) ?>"><?= $authorAvatar ?></span>
						<span class="people-arrow">→</span>
						<span class="avatar" style="background:<?= $respColor ?>;" title="Исполнитель: <?= htmlspecialchars($respName) ?>"><?= $respAvatar ?></span>
					</div>
				</div>
			</div>
			<?php endwhile; ?>
		</div>

		<button class="add-card-btn" onclick="openAddTask(<?= $col['id'] ?>)">+ Добавить задачу</button>
	</div>
	<?php endwhile; ?>

	<!-- New column button -->
	<button class="column" onclick="openAddColumn()"
		style="flex:0 0 220px;min-height:120px;border:1px dashed var(--border-strong);background:transparent;color:var(--text-tertiary);cursor:pointer;display:grid;place-items:center;font-family:var(--font-sans);font-size:13px;backdrop-filter:none;-webkit-backdrop-filter:none;">
		+ Новая колонка
	</button>
</div>

<!-- Footer -->
<footer class="footer">
	© 2026 bolgov0zero · версия <span class="ver"><?= htmlspecialchars($version) ?></span>
</footer>

<script>
window.isAdmin = <?= json_encode($isAdmin) ?>;
</script>
<script src="script.js" defer></script>

<!-- Date & Time Scripts -->
<script>
function parseMoscowDate(dateStr) {
	if (dateStr && dateStr.length === 10) dateStr += ' 00:00:00';
	return new Date(dateStr.replace(' ', 'T') + '+03:00');
}

function updateCreatedDates() {
	document.querySelectorAll('.created-date[data-created]').forEach(el => {
		const d = parseMoscowDate(el.getAttribute('data-created'));
		el.textContent = d.toLocaleDateString('ru-RU', {
			day:'2-digit', month:'2-digit', year:'numeric',
			hour:'2-digit', minute:'2-digit',
			timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone
		});
	});
}

function updateDeadlines() {
	document.querySelectorAll('.deadline-tag[data-deadline]').forEach(el => {
		const d = parseMoscowDate(el.getAttribute('data-deadline'));
		const txt = el.querySelector('.deadline-text');
		if (txt) txt.textContent = '📅 ' + d.toLocaleDateString('ru-RU', {day:'2-digit',month:'2-digit',year:'numeric',timeZone:Intl.DateTimeFormat().resolvedOptions().timeZone});
	});
}

function updateTimers() {
	document.querySelectorAll('.card[data-moved-at]').forEach(task => {
		const movedAtStr = task.getAttribute('data-moved-at');
		const taskId = task.getAttribute('data-task-id');
		const timerEl = document.getElementById('timer-' + taskId);
		if (!timerEl || !movedAtStr) return;
		const diff = new Date() - parseMoscowDate(movedAtStr);
		const totalHours = diff / 3600000;
		if (totalHours >= 24) {
			const days = Math.floor(totalHours / 24);
			timerEl.textContent = `⏱ ${days}д ${Math.floor(totalHours % 24)}ч`;
		} else {
			timerEl.textContent = `⏱ ${Math.floor(totalHours)}ч ${Math.floor((totalHours % 1)*60)}м`;
		}
		if (totalHours >= 22) timerEl.classList.add('urgent');
		else timerEl.classList.remove('urgent');
	});
}

document.addEventListener('DOMContentLoaded', function() {
	updateCreatedDates();
	updateDeadlines();
	updateTimers();
	setInterval(updateTimers, 60000);
});
</script>

<?php include 'modals.php'; ?>
</body>
</html>
<?php
function getContrastColor($hex){
	if(!$hex) return "#fff";
	$hex = ltrim($hex,'#');
	if(strlen($hex) === 3) $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
	$r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
	return (0.299*$r + 0.587*$g + 0.114*$b) > 160 ? "#000" : "#fff";
}
?>
