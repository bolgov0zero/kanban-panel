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

// Avatar color from --sm-avatar-1..6 palette using username hash
function getUserAvatarVar($username) {
	$idx = (abs(crc32($username)) % 6) + 1;
	return "var(--sm-avatar-{$idx})";
}

$userAvatars = [];
$userColorVars = [];
foreach ($userNames as $username => $name) {
	$userAvatars[$username] = getAvatarFromName($name);
	$userColorVars[$username] = getUserAvatarVar($username);
}

$totalTasks = $db->querySingle("SELECT COUNT(*) FROM tasks WHERE completed=0");

$version_data = json_decode(file_get_contents(__DIR__ . '/version.json'), true);
$version = $version_data['version'] ?? '—';

// Members for hero avatar stack
$allUsers = [];
$resAll = $db->query("SELECT username, name FROM users ORDER BY id LIMIT 6");
while ($u = $resAll->fetchArray(SQLITE3_ASSOC)) {
	$allUsers[] = $u;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Kanban Board</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="design-tokens.css">
<link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Topbar -->
<header class="topbar">
	<div class="topbar-logo">K</div>
	<span class="topbar-brand">Kanban</span>
	<div class="topbar-spacer"></div>
	<span class="topbar-meta"><?= date('d.m.Y') ?></span>
	<span class="topbar-sep">·</span>
	<span class="topbar-day"><?php
		$days = ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'];
		echo $days[date('w')];
	?></span>

	<button onclick="openArchive()" class="topbar-icon-btn" title="Архив">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8M1 3h22v5H1zM10 12h4"/></svg>
	</button>
	<?php if ($isAdmin): ?>
	<button onclick="openUserSettings()" class="topbar-icon-btn" title="Настройки">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
	</button>
	<?php endif; ?>
	<a href="logout.php" class="topbar-icon-btn" title="Выйти">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
	</a>
	<div class="topbar-avatar" style="background:<?= getUserAvatarVar($user) ?>;" title="<?= htmlspecialchars($user_name) ?>">
		<?= getAvatarFromName($user_name) ?>
	</div>
</header>

<!-- Hero -->
<div class="hero">
	<div>
		<h1 class="hero-title">Доска <em>задач</em></h1>
		<div class="hero-sub">
			<span><strong><?= $totalTasks ?></strong> в работе</span>
			<span class="hero-sub-sep">·</span>
			<span>0 запланировано</span>
			<span class="hero-sub-sep">·</span>
			<span>0 завершено сегодня</span>
		</div>
	</div>
	<div class="hero-right">
		<div class="avatar-stack">
			<?php foreach ($allUsers as $u): ?>
			<div class="avatar-stack-item" style="background:<?= getUserAvatarVar($u['username']) ?>;" title="<?= htmlspecialchars($u['name'] ?: $u['username']) ?>">
				<?= getAvatarFromName($u['name'] ?: $u['username']) ?>
			</div>
			<?php endforeach; ?>
		</div>
		<button onclick="openAddTask()" class="btn-hero">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M12 5v14"/></svg>
			Новая задача
		</button>
	</div>
</div>

<!-- Board -->
<div class="board" id="board">
	<?php
	$columns = $db->query("SELECT * FROM columns ORDER BY id");
	while ($col = $columns->fetchArray(SQLITE3_ASSOC)):
		$tasks_count = $db->querySingle("SELECT COUNT(*) FROM tasks WHERE column_id={$col['id']}");
		$accent = htmlspecialchars($col['bg_color']);
		$countStr = str_pad($tasks_count, 2, '0', STR_PAD_LEFT);
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
			<div class="column-title-group">
				<span class="column-title"><?= htmlspecialchars($col['name']) ?></span>
				<span class="column-count"><?= $countStr ?></span>
			</div>
			<div class="column-actions">
				<button onclick="editColumn(<?= $col['id'] ?>)" class="col-icon-btn" title="Редактировать">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
				</button>
				<button onclick="openAddTask(<?= $col['id'] ?>)" class="col-icon-btn" title="Добавить задачу">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5v14"/></svg>
				</button>
			</div>
		</div>

		<div class="col-divider"></div>

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

				$author     = $task['author'] ?? $user;
				$authorName = $task['author_display_name'] ?? $author;
				$respName   = $task['responsible_display_name'] ?? $task['responsible'];
				$authorAvatar = $userAvatars[$author] ?? getAvatarFromName($author);
				$respAvatar   = $userAvatars[$task['responsible']] ?? getAvatarFromName($task['responsible']);
				$respColorVar = $userColorVars[$task['responsible']] ?? getUserAvatarVar($task['responsible']);
			?>
			<div draggable="true"
				 ondragstart="drag(event)"
				 id="task<?= $task['id'] ?>"
				 class="card<?= $task['completed'] ? ' card--done' : '' ?>"
				 <?php if($col['timer'] && !empty($task['moved_at'])): ?>
				 data-moved-at="<?= htmlspecialchars($task['moved_at']) ?>"
				 data-task-id="<?= $task['id'] ?>"
				 <?php endif; ?>>

				<div class="card-top">
					<span class="card-created created-date" data-created="<?= htmlspecialchars($task['created_at']) ?>"></span>
					<div style="display:flex;gap:2px;align-items:center;">
						<?php if ($col['auto_complete']): ?>
						<button onclick="archiveNow(<?= $task['id'] ?>)" class="card-menu-btn" title="Архивировать">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8M1 3h22v5H1zM10 12h4"/></svg>
						</button>
						<?php endif; ?>
						<button onclick="editTask(<?= $task['id'] ?>)" class="card-menu-btn" title="Редактировать">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
						</button>
					</div>
				</div>

				<div class="card-title"><?= htmlspecialchars($task['title']) ?></div>

				<?php
				$desc = $task['description'] ?? '';
				if (!empty($desc)):
					$descEsc = htmlspecialchars($desc, ENT_QUOTES);
					$descEsc = preg_replace_callback('/\[([^\[\]]+)\]\((https?:\/\/[^\s\)]+)\)/i', function($m) {
						return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer" class="task-link">' . $m[1] . '</a>';
					}, $descEsc);
					$descEsc = preg_replace_callback('/(?<![="\'])\b(https?:\/\/[^\s<&"\']+)/i', function($m) {
						$url = $m[1];
						$host = parse_url($url, PHP_URL_HOST) ?: (strlen($url) > 30 ? substr($url, 0, 30) . '…' : $url);
						return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="task-link">' . $host . '</a>';
					}, $descEsc);
					$descEsc = nl2br($descEsc, false);
				?>
				<div class="card-desc"><?= $descEsc ?></div>
				<?php endif; ?>

				<div class="card-foot">
					<div class="card-foot-left">
						<span class="priority-chip <?= $priClass ?>">
							<span class="chip-dot"></span><?= htmlspecialchars($importance) ?>
						</span>
						<?php if (!empty($task['deadline'])): ?>
						<span class="card-deadline deadline-tag" data-deadline="<?= htmlspecialchars($task['deadline']) ?>">
							<span class="deadline-text"></span>
						</span>
						<?php endif; ?>
						<?php if($col['timer'] && !empty($task['moved_at'])): ?>
						<span class="card-timer" id="timer-<?= $task['id'] ?>">⏱ —</span>
						<?php endif; ?>
					</div>
					<div class="card-people">
						<span class="avatar-xs ghost" title="Автор: <?= htmlspecialchars($authorName) ?>"><?= $authorAvatar ?></span>
						<span class="people-arrow">→</span>
						<span class="avatar-xs" style="background:<?= $respColorVar ?>;" title="Исполнитель: <?= htmlspecialchars($respName) ?>"><?= $respAvatar ?></span>
					</div>
				</div>
			</div>
			<?php endwhile; ?>

			<?php if ($tasks_count == 0): ?>
			<div class="col-empty">Пока пусто</div>
			<?php endif; ?>
		</div>

		<button class="add-card-btn" onclick="openAddTask(<?= $col['id'] ?>)">
			<span class="add-card-icon">+</span>
			Добавить задачу
		</button>
	</div>
	<?php endwhile; ?>

	<!-- Ghost new column -->
	<button class="ghost-column" onclick="openAddColumn()">
		<div class="ghost-column-label">+ новая колонка</div>
		<div class="ghost-column-hint">например, «На ревью» или «Заблокировано»</div>
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

// Drag class
document.addEventListener('dragstart', function(e) {
	const card = e.target.closest('.card');
	if (card) card.classList.add('dragging');
});
document.addEventListener('dragend', function(e) {
	const card = e.target.closest('.card');
	if (card) card.classList.remove('dragging');
});
</script>

<?php include 'modals.php'; ?>
</body>
</html>
