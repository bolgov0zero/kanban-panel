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
	// Используем moved_at если есть, иначе created_at
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

// Функция для получения аватара из имени с поддержкой кириллицы
function getAvatarFromName($name) {
	if (empty($name)) return '?';
	
	$words = explode(' ', trim($name));
	$initials = '';
	
	// Берем первую букву первого слова
	if (isset($words[0]) && !empty($words[0])) {
		// Для кириллицы используем mb_ функции, если доступны
		if (function_exists('mb_substr')) {
			$initials .= mb_strtoupper(mb_substr($words[0], 0, 1, 'UTF-8'), 'UTF-8');
		} else {
			// Фолбэк для серверов без mbstring
			$firstChar = substr($words[0], 0, 2); // Берем 2 байта для кириллицы
			$initials .= $firstChar;
		}
	}
	
	// Берем первую букву второго слова (если есть)
	if (isset($words[1]) && !empty($words[1])) {
		if (function_exists('mb_substr')) {
			$initials .= mb_strtoupper(mb_substr($words[1], 0, 1, 'UTF-8'), 'UTF-8');
		} else {
			$firstChar = substr($words[1], 0, 2);
			$initials .= $firstChar;
		}
	}
	
	return $initials ?: (function_exists('mb_substr') ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') : substr($name, 0, 2));
}

// Получаем аватары для всех пользователей
$userAvatars = [];
foreach ($userNames as $username => $name) {
	$userAvatars[$username] = getAvatarFromName($name);
}

// Получаем колонки с информацией о таймере
$columns = $db->query("SELECT * FROM columns ORDER BY id");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Kanban Board</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
	<div class="header-inner">
		<div class="header-left">
			<div class="logo-icon">K</div>
			<span class="logo-text">Kanban Доска</span>
		</div>
		<div class="header-right">
			<?php if ($isAdmin): ?>
			<button onclick="openUserSettings()" class="nav-btn" title="Настройки">
				<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
			</button>
			<?php endif; ?>
			<button onclick="openAddColumn()" class="nav-btn" title="Добавить колонку">
				<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M12 10v6"/><path d="M9 13h6"/><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
			</button>
			<button onclick="openAddTask()" class="nav-btn" title="Новая задача">
				<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 15h6"/><path d="M12 18v-6"/></svg>
			</button>
			<button onclick="openArchive()" class="nav-btn" title="Архив">
				<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
			</button>
			<div class="header-divider"></div>
			<div class="ava-hed-style" title="<?= htmlspecialchars($user_name) ?>"><?= getAvatarFromName($user_name) ?></div>
			<span class="username-text"><?= htmlspecialchars($user_name) ?></span>
			<a href="logout.php" class="btn-danger">
				<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
				Выйти
			</a>
		</div>
	</div>
</header>

<!-- Main Content -->
<main>
	<div id="board">
		<?php while ($col = $columns->fetchArray(SQLITE3_ASSOC)):
			$tasks_count = $db->querySingle("SELECT COUNT(*) FROM tasks WHERE column_id={$col['id']}");
			$accent = htmlspecialchars($col['bg_color']);
		?>
		<div class="col-wrap"
			 data-col-id="<?= $col['id'] ?>"
			 data-col-bg="<?= $accent ?>"
			 data-auto-complete="<?= $col['auto_complete'] ?>"
			 data-timer="<?= $col['timer'] ?>"
			 ondrop="drop(event)"
			 ondragover="allowDrop(event)"
			 ondragenter="highlightDrop(this,true,event)"
			 ondragleave="highlightDrop(this,false,event)"
			 style="--col-accent:<?= $accent ?>;">

			<!-- Column Header -->
			<div class="col-head">
				<div class="col-head-left">
					<span class="col-dot"></span>
					<span class="col-name"><?= htmlspecialchars($col['name']) ?></span>
				</div>
				<div class="col-head-right">
					<span class="col-count"><?= $tasks_count ?></span>
					<button onclick="editColumn(<?= $col['id'] ?>)" class="col-edit-btn" title="Редактировать колонку">
						<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
					</button>
				</div>
			</div>

			<!-- Tasks -->
			<div class="tasks-list" id="col<?= $col['id'] ?>">
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
					$importanceColors = ['не срочно' => '#22c55e', 'средне' => '#eab308', 'срочно' => '#ef4444'];
					$impColor = $importanceColors[$task['importance']] ?? '#6b7280';
					$author    = $task['author'] ?? $user;
					$authorName = $task['author_display_name'] ?? $author;
					$respName  = $task['responsible_display_name'] ?? $task['responsible'];
					$authorAvatar = $userAvatars[$author] ?? getAvatarFromName($author);
					$respAvatar   = $userAvatars[$task['responsible']] ?? getAvatarFromName($task['responsible']);
				?>
				<div draggable="true"
					 ondragstart="drag(event)"
					 id="task<?= $task['id'] ?>"
					 class="task-card<?= $task['completed'] ? ' task-card--done' : '' ?>"
					 style="--card-accent:<?= $accent ?>;"
					 <?php if($col['timer'] && !empty($task['moved_at'])): ?>
					 data-moved-at="<?= htmlspecialchars($task['moved_at']) ?>"
					 data-task-id="<?= $task['id'] ?>"
					 <?php endif; ?>>

					<div class="task-top">
						<p class="task-date created-date" data-created="<?= htmlspecialchars($task['created_at']) ?>"></p>
						<button onclick="editTask(<?= $task['id'] ?>)" class="task-edit-btn" title="Редактировать">
							<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
						</button>
					</div>

					<h3 class="task-title"><?= htmlspecialchars($task['title']) ?></h3>

					<?php
					$desc = $task['description'] ?? '';
					if (!empty($desc)):
						$desc = preg_replace_callback('/(?<!\()\b(https?:\/\/[^\s<]+|www\.[^\s<]+)\b/i', function($m) {
							$url = $m[0];
							if (strpos($url, 'http') !== 0) $url = 'http://' . $url;
							$host = parse_url($url, PHP_URL_HOST);
							$short = $host ?: (strlen($url) > 30 ? substr($url, 0, 30) . '...' : $url);
							return '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer" class="task-link">' . htmlspecialchars($short, ENT_QUOTES) . '</a>';
						}, $desc);
						$desc = preg_replace_callback('/\[([^\[\]]+)\]\((https?:\/\/[^\s\)]+)\)/i', function($m) {
							return '<a href="' . htmlspecialchars($m[2], ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer" class="task-link">' . htmlspecialchars($m[1], ENT_QUOTES) . '</a>';
						}, $desc);
						$desc = nl2br($desc, false);
					?>
					<div class="task-desc"><?= $desc ?></div>
					<?php endif; ?>

					<div class="task-footer">
						<div class="task-badges">
							<?php if($task['completed']): ?>
								<span class="task-badge badge-done">✓ Выполнено</span>
							<?php else: ?>
								<span class="task-badge badge-imp" style="color:<?= $impColor ?>;background:<?= $impColor ?>18;border-color:<?= $impColor ?>40;"><?= htmlspecialchars($task['importance']) ?></span>
							<?php endif; ?>
							<?php if (!empty($task['deadline'])): ?>
							<span class="task-badge badge-deadline deadline-tag" data-deadline="<?= htmlspecialchars($task['deadline']) ?>">
								<svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
								<span class="deadline-text"></span>
							</span>
							<?php endif; ?>
							<?php if($col['timer'] && !empty($task['moved_at'])): ?>
								<span class="task-badge badge-timer timer-display" id="timer-<?= $task['id'] ?>">⏱ —</span>
							<?php endif; ?>
						</div>

						<div class="task-right">
							<?php if($task['completed']): ?>
							<button onclick="archiveNow(<?= $task['id'] ?>)" class="task-archive-btn" title="В архив">
								<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 002 2h12a2 2 0 002-2V8"/><path d="M10 12h4"/></svg>
							</button>
							<?php endif; ?>
							<div class="task-avatars">
								<span class="task-avatar" title="Автор: <?= htmlspecialchars($authorName) ?>"><?= $authorAvatar ?></span>
								<svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" class="task-avatar-arrow"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
								<span class="task-avatar task-avatar--resp" title="Исполнитель: <?= htmlspecialchars($respName) ?>"><?= $respAvatar ?></span>
							</div>
						</div>
					</div>
				</div>
				<?php endwhile; ?>
			</div>
		</div>
		<?php endwhile; ?>
	</div>
</main>
<?php
$version_data = json_decode(file_get_contents(__DIR__ . '/version.json'), true);
$version = $version_data['version'] ?? 'неизвестно';
?>
<!-- Footer -->
<footer>
	2026 <font color="#E1E1E1">©</font> bolgov0zero<br/><font color="#E1E1E1"><b>Версия:</b></font> <font color="#2E958F"><b><?php echo htmlspecialchars($version); ?></b></font>
</footer>

<!-- Scripts -->
<script>
window.isAdmin = <?= json_encode($isAdmin) ?>;
</script>

<script src="script.js" defer></script>

<!-- Date & Time Scripts -->
<script>
function parseMoscowDate(dateStr) {
	// Если дата без времени, добавляем время
	if (dateStr && dateStr.length === 10) {
		dateStr += ' 00:00:00';
	}
	
	const isoStr = dateStr.replace(' ', 'T') + '+03:00';
	return new Date(isoStr);
}

function updateCreatedDates() {
	document.querySelectorAll('.created-date[data-created]').forEach(el => {
		const moscowDate = parseMoscowDate(el.getAttribute('data-created'));
		const options = { 
			day: '2-digit', 
			month: '2-digit', 
			year: 'numeric', 
			hour: '2-digit', 
			minute: '2-digit',
			timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone
		};
		el.textContent = moscowDate.toLocaleDateString('ru-RU', options);
	});
}

function updateDeadlines() {
	document.querySelectorAll('.deadline-tag[data-deadline]').forEach(el => {
		const deadlineStr = el.getAttribute('data-deadline');
		const moscowDate = parseMoscowDate(deadlineStr);
		const deadlineTextEl = el.querySelector('.deadline-text');
		if (deadlineTextEl) {
			const options = { 
				day: '2-digit', 
				month: '2-digit', 
				year: 'numeric',
				timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone
			};
			deadlineTextEl.textContent = moscowDate.toLocaleDateString('ru-RU', options);
		}
	});
}

function updateTimers() {
	document.querySelectorAll('.task-card[data-moved-at]').forEach(task => {
		const movedAtStr = task.getAttribute('data-moved-at');
		const taskId = task.getAttribute('data-task-id');
		const timerEl = document.getElementById('timer-' + taskId);
		
		if (!timerEl || !movedAtStr) return;

		const moscowMovedDate = parseMoscowDate(movedAtStr);
		const now = new Date();
		const diff = now.getTime() - moscowMovedDate.getTime();

		// Преобразуем в часы
		const totalHours = diff / (1000 * 60 * 60);
		
		// Если прошло больше 24 часов, показываем в днях
		if (totalHours >= 24) {
			const days = Math.floor(totalHours / 24);
			const remainingHours = Math.floor(totalHours % 24);
			timerEl.textContent = `⏱️ ${days}д ${remainingHours}ч`;
			timerEl.classList.add('bg-red-600', 'bg-opacity-30');
			timerEl.classList.remove('bg-red-600', 'bg-opacity-20');
		} else {
			// Показываем в часах:минутах
			const hours = Math.floor(totalHours);
			const minutes = Math.floor((totalHours - hours) * 60);
			timerEl.textContent = `⏱️ ${hours}ч ${minutes}м`;
			
			// Меняем цвет, если близко к 24 часам
			if (totalHours >= 22) {
				timerEl.classList.add('bg-red-600', 'bg-opacity-30');
				timerEl.classList.remove('bg-red-600', 'bg-opacity-20');
			} else {
				timerEl.classList.add('bg-red-600', 'bg-opacity-20');
				timerEl.classList.remove('bg-red-600', 'bg-opacity-30');
			}
		}
	});
}

document.addEventListener('DOMContentLoaded', function() {
	updateCreatedDates();
	updateDeadlines();
	updateTimers();
	// Обновляем таймер каждую минуту
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