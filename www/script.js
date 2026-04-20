// Глобальные переменные
let users = [];
let columns = [];
let links = [];
let currentEditId = null;

// === Drag & Drop ===
function allowDrop(ev) { ev.preventDefault(); }
function drag(ev) { ev.dataTransfer.setData("text", ev.target.id); }

function highlightDrop(el, on, ev) {
	if (!on && ev && el.contains(ev.relatedTarget)) return; // не убираем если курсор перешёл в дочерний элемент
	if (on) el.classList.add('drop-hover');
	else el.classList.remove('drop-hover');
}

function drop(ev) {
	ev.preventDefault();
	let taskId = ev.dataTransfer.getData("text").replace('task', '');
	let colId  = ev.currentTarget.dataset.colId;
	let task   = document.getElementById('task' + taskId);
	let target = ev.currentTarget.querySelector('#col' + colId);
	if (!target) return;

	// Обновляем только цвет бордера под новую колонку
	let colBg = ev.currentTarget.dataset.colBg || '#374151';
	task.style.borderLeftColor = colBg;
	target.appendChild(task);

	ev.currentTarget.classList.remove('drop-hover');

	// Сначала отправляем запрос, reload только после завершения
	fetch('api.php', {
		method: 'POST',
		body: new URLSearchParams({ action: 'move_task', task_id: taskId, column_id: colId })
	}).then(() => location.reload());
}

function getContrastColor(hex) {
	if (!hex) return '#fff';
	hex = hex.replace('#', '');
	if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
	let r = parseInt(hex.substr(0, 2), 16);
	let g = parseInt(hex.substr(2, 2), 16);
	let b = parseInt(hex.substr(4, 2), 16);
	return (0.299 * r + 0.587 * g + 0.114 * b) > 160 ? '#000' : '#fff';
}

function loadUsers() {
	return fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_users' }) })
		.then(r => r.json())
		.then(data => { 
			users = data;
			window.users = data; // Обновляем глобальную переменную
			return data; 
		})
		.catch(err => console.error('Error loading users:', err));
}

function loadColumns() {
	return fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_columns' }) })
		.then(r => r.json())
		.then(data => { 
			columns = data;
			window.columns = data; // Обновляем глобальную переменную
			return data; 
		})
		.catch(err => console.error('Error loading columns:', err));
}

function loadLinks() {
	return fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_links' }) })
		.then(r => r.json())
		.then(data => { links = data; return data; })
		.catch(err => console.error('Error loading links:', err));
}

// Загрузка при старте
document.addEventListener('DOMContentLoaded', function() {
	loadUsers();
	loadColumns();
	loadLinks();
});

function openModal(html) {
	const modalBg = document.getElementById('modal-bg');
	const modalContent = document.getElementById('modal-content');
	
	if (!modalBg || !modalContent) {
		console.error('Modal elements not found');
		return;
	}
	
	modalContent.innerHTML = html;
	modalBg.classList.remove('hidden');
	
	// Перепривязываем обработчики для кнопок ссылок СРАЗУ
	setTimeout(() => {
		const linkPickerBtns = modalContent.querySelectorAll('.link-picker-btn');
		linkPickerBtns.forEach(btn => {
			btn.onclick = openLinkPicker;
		});
		
		// Также перепривязываем обработчики для текстовых полей описания
		const textareas = modalContent.querySelectorAll('textarea');
		textareas.forEach(textarea => {
			// Сохраняем ссылку на текстовое поле для использования в insertLink
			textarea.dataset.hasPicker = 'true';
		});
	}, 0); // Уменьшаем таймаут до 0
	
	// Добавляем обработчик Escape для закрытия
	const handleEscape = (e) => {
		if (e.key === 'Escape') {
			closeModal();
		}
	};
	
	document.addEventListener('keydown', handleEscape);
	modalBg._escapeHandler = handleEscape;
	
	// Фокусируемся на первом инпуте
	const firstInput = modalContent.querySelector('input, textarea, select');
	if (firstInput) {
		setTimeout(() => firstInput.focus(), 100);
	}
	
	// Предотвращаем прокрутку body при открытой модалке
	document.body.style.overflow = 'hidden';
}

function closeModal() {
	const modalBg = document.getElementById('modal-bg');
	if (modalBg) {
		modalBg.classList.add('hidden');
		currentEditId = null;
		
		// Убираем обработчик Escape
		if (modalBg._escapeHandler) {
			document.removeEventListener('keydown', modalBg._escapeHandler);
		}
	}
	
	// Восстанавливаем прокрутку body
	document.body.style.overflow = '';
}

function closeLinkPicker() {
	const linkPicker = document.getElementById('link-picker');
	if (linkPicker) {
		linkPicker.classList.add('hidden');
	}
}

// === Колонки ===
function openAddColumn() {
	const template = document.getElementById('add-column-modal-template');
	if (template) {
		openModal(template.innerHTML);
		
		// Настройка обновления цветов
		setTimeout(() => {
			setupColorInputs('colBg', 'colBgValue');
			setupColorInputs('taskBg', 'taskBgValue');
		}, 100);
	}
}

function editColumn(id) {
	currentEditId = id;
	
	fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_column', id }) })
		.then(r => r.json())
		.then(c => {
			if (!c) {
				alert('Колонка не найдена');
				return;
			}
			
			const template = document.getElementById('edit-column-modal-template');
			if (template) {
				openModal(template.innerHTML);
				
				// Заполняем данные после открытия модалки
				setTimeout(() => {
					fillColumnForm(c);
				}, 100);
			}
		})
		.catch(err => {
			console.error('Error loading column:', err);
			alert('Ошибка при загрузке колонки');
		});
}

function setupColorInputs(inputId, valueId) {
	const colorInput = document.getElementById(inputId);
	const valueElement = document.getElementById(valueId);
	
	if (colorInput && valueElement) {
		colorInput.addEventListener('input', function(e) {
			valueElement.textContent = e.target.value;
		});
	}
}

function saveColumn() {
	const name = document.getElementById('colName')?.value;
	
	if (!name) {
		alert('Введите название колонки');
		return;
	}
	
	let data = new URLSearchParams({
		action: 'add_column',
		name: name,
		bg_color: document.getElementById('colBg')?.value || '#374151',
		auto_complete: document.getElementById('autoComplete')?.checked ? 1 : 0,
		timer: document.getElementById('timer')?.checked ? 1 : 0
	});
	
	closeModal();
	fetch('api.php', { method: 'POST', body: data })
	.then(response => {
		if (response.ok) location.reload();
		else alert('Ошибка при создании колонки');
	})
	.catch(() => alert('Ошибка при создании колонки'));
}

function fillColumnForm(column) {
	const nameInput = document.getElementById('editColName');
	const colBgInput = document.getElementById('editColBg');
	const colBgValue = document.getElementById('editColBgValue');
	const autoCompleteInput = document.getElementById('editAutoComplete');
	const timerInput = document.getElementById('editTimer');
	
	if (nameInput) nameInput.value = column.name || '';
	if (colBgInput) {
		colBgInput.value = column.bg_color || '#FFFFFF';
		if (colBgValue) colBgValue.textContent = column.bg_color || '#FFFFFF';
	}
	if (autoCompleteInput) autoCompleteInput.checked = column.auto_complete == 1;
	if (timerInput) timerInput.checked = column.timer == 1;
	
	// Настройка обновления цвета в реальном времени
	setupColorInputs('editColBg', 'editColBgValue');
}

function updateColumn() {
	if (!currentEditId) return;
	
	const name = document.getElementById('editColName')?.value;
	
	if (!name) {
		alert('Введите название колонки');
		return;
	}
	
	let data = new URLSearchParams({
		action: 'update_column',
		id: currentEditId,
		name: name,
		bg_color: document.getElementById('editColBg')?.value || '#374151',
		auto_complete: document.getElementById('editAutoComplete')?.checked ? 1 : 0,
		timer: document.getElementById('editTimer')?.checked ? 1 : 0
	});
	
	closeModal();
	fetch('api.php', { method: 'POST', body: data })
	.then(response => {
		if (response.ok) location.reload();
		else alert('Ошибка при обновлении колонки');
	})
	.catch(() => alert('Ошибка при обновлении колонки'));
}

// Новая система вкладок для настроек
function fillSettingsData(usersData, tgData, emailData, linksData) {
	// Заполняем список пользователей
	const usersList = document.getElementById('users-list');
	if (usersList) {
		usersList.innerHTML = usersData.map(u => `
			<div class="user-card">
				<div class="user-info">
					<div class="user-avatar-medium">${getAvatarFromName(u.name || u.username)}</div>
					<div class="user-details">
						<div class="user-username">${u.username}</div>
						<div class="user-name">${u.name || 'Без имени'}</div>
					</div>
				</div>
				<div class="user-actions">
					${u.is_admin ? '<span class="admin-badge">Admin</span>' : ''}
					<button onclick="editUserSettings('${u.username}')" class="user-action-btn user-edit-btn" title="Редактировать">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
						</svg>
					</button>
					<button onclick="deleteUser('${u.username}')" class="user-action-btn user-delete-btn" title="Удалить">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
						</svg>
					</button>
				</div>
			</div>
		`).join('');
		
		// Обновляем счетчик пользователей
		const usersCount = document.getElementById('users-count');
		if (usersCount) {
			usersCount.textContent = usersData.length + ' пользователей';
		}
	}

	// Заполняем список ссылок
	const linksList = document.getElementById('admin-links-list');
	if (linksList) {
		linksList.innerHTML = linksData.map(l => `
			<div class="link-card">
				<div class="link-info">
					<div class="link-name">${l.name}</div>
					<div class="link-url">${l.url}</div>
				</div>
				<div class="user-actions">
					<button onclick="deleteLink(${l.id})" class="user-action-btn user-delete-btn" title="Удалить">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
						</svg>
					</button>
				</div>
			</div>
		`).join('');
		
		// Обновляем счетчик ссылок
		const linksCount = document.getElementById('links-count');
		if (linksCount) {
			linksCount.textContent = linksData.length + ' ссылок';
		}
	}

	// Заполняем Telegram настройки
	const tgToken = document.getElementById('tgToken');
	const tgChat = document.getElementById('tgChat');
	const dailyReportTime = document.getElementById('dailyReportTime');
	const timerNotificationMinutes = document.getElementById('timerNotificationMinutes');
	
	if (tgToken) tgToken.value = tgData.bot_token || '';
	if (tgChat) tgChat.value = tgData.chat_id || '';
	if (dailyReportTime) dailyReportTime.value = tgData.daily_report_time || '10:00';
	if (timerNotificationMinutes) {
		timerNotificationMinutes.value = tgData.timer_notification_minutes || 1440;
	}
	const tgEnabled = document.getElementById('tgEnabled');
	if (tgEnabled) tgEnabled.checked = (tgData.notifications_enabled ?? 1) == 1;

	// Заполняем Email настройки
	if (emailData) {
		const f = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
		document.getElementById('emailEnabled') && (document.getElementById('emailEnabled').checked = emailData.enabled == 1);
		f('emailHost', emailData.host);
		f('emailPort', emailData.port || 587);
		f('emailUsername', emailData.username);
		f('emailPassword', emailData.password);
		f('emailFromEmail', emailData.from_email);
		f('emailFromName', emailData.from_name || 'Kanban');
		f('emailToEmail', emailData.to_email);
		const enc = document.getElementById('emailEncryption');
		if (enc) enc.value = emailData.encryption || 'tls';
	}
	
	// Обновляем информацию о текущих настройках на вкладке тестирования
	const currentReportTime = document.getElementById('current-report-time');
	const currentTimerMinutes = document.getElementById('current-timer-minutes');
	
	if (currentReportTime) currentReportTime.textContent = tgData.daily_report_time || '10:00';
	if (currentTimerMinutes) currentTimerMinutes.textContent = tgData.timer_notification_minutes || 1440;
}

// Инициализация вкладок настроек
function initSettingsTabs() {
	const menuItems = document.querySelectorAll('.settings-menu-item');
	const tabContents = document.querySelectorAll('.tab-content');
	
	menuItems.forEach(item => {
		item.addEventListener('click', function() {
			const tabName = this.getAttribute('data-tab');
			
			if (!tabName) {
				console.error('Элемент меню не имеет data-tab атрибута:', this);
				return;
			}
			
			// Убираем активный класс у всех
			menuItems.forEach(i => i.classList.remove('active'));
			tabContents.forEach(tab => tab.classList.remove('active'));
			
			// Добавляем активный класс текущему
			this.classList.add('active');
			
			const targetTab = document.getElementById(tabName + '-tab');
			if (targetTab) {
				targetTab.classList.add('active');
				
				// Если открыли вкладку тестирования, обновляем статус cron
				if (tabName === 'testing') {
					checkCronStatus();
				}
			}
		});
	});
	
	// Активируем первую вкладку по умолчанию
	if (menuItems.length > 0) {
		const firstTab = menuItems[0].getAttribute('data-tab');
		const firstTabContent = document.getElementById(firstTab + '-tab');
		if (firstTabContent) {
			menuItems[0].classList.add('active');
			firstTabContent.classList.add('active');
		}
	}
}

// Настройка вкладки тестирования
function setupTestingTab() {
	// Перепривязываем обработчики на случай динамической загрузки
	const testTelegramBtn = document.querySelector('button[onclick="testTelegram()"]');
	const testTimerBtn = document.querySelector('button[onclick="testTimerNotification()"]');
	const testReportBtn = document.querySelector('button[onclick="testDailyReport()"]');
	const testCronBtn = document.querySelector('button[onclick="checkCronStatus()"]');
	
	if (testTelegramBtn) testTelegramBtn.onclick = testTelegram;
	if (testTimerBtn) testTimerBtn.onclick = testTimerNotification;
	if (testReportBtn) testReportBtn.onclick = testDailyReport;
	if (testCronBtn) testCronBtn.onclick = checkCronStatus;
}

function getAvatarFromName(name) {
	if (!name) return '?';
	
	const words = name.split(' ').filter(word => word.length > 0);
	let initials = '';
	
	if (words.length > 0) {
		initials += words[0].charAt(0).toUpperCase();
	}
	if (words.length > 1) {
		initials += words[1].charAt(0).toUpperCase();
	}
	
	return initials || name.charAt(0).toUpperCase();
}

function deleteColumn() {
	if (!currentEditId) return;
	if (!confirm('Удалить колонку и все задачи в ней?')) return;

	closeModal();
	fetch('api.php', {
		method: 'POST',
		body: new URLSearchParams({ action: 'delete_column', id: currentEditId })
	})
	.then(response => {
		if (response.ok) location.reload();
		else alert('Ошибка при удалении колонки');
	})
	.catch(() => alert('Ошибка при удалении колонки'));
}

// === Задачи ===
function openAddTask(columnId = null) {
	const template = document.getElementById('add-task-modal-template');
	if (template) {
		openModal(template.innerHTML);
		
		// Заполняем данные после открытия модалки
		setTimeout(() => {
			fillTaskForm(null, columnId);
		}, 100);
	}
}

function editTask(id) {
	currentEditId = id;
	
	fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_task', id }) })
		.then(r => r.json())
		.then(task => {
			if (!task || !task.id) {
				alert('Задача не найдена');
				return;
			}
			
			const template = document.getElementById('edit-task-modal-template');
			if (template) {
				openModal(template.innerHTML);
				
				// Ждем полного рендеринга модального окна
				setTimeout(() => {
					fillEditTaskForm(task);
				}, 150);
			}
		})
		.catch(err => {
			console.error('Error loading task:', err);
			alert('Ошибка при загрузке задачи');
		});
}

// Новая функция специально для редактирования задачи
function fillEditTaskForm(task) {
	// Заполняем основные поля
	const titleInput = document.getElementById('editTaskTitle');
	const descInput = document.getElementById('editTaskDesc');
	const deadlineInput = document.getElementById('editTaskDeadline');
	const impSelect = document.getElementById('editTaskImp');
	const respSelect = document.getElementById('editTaskResp');
	const colSelect = document.getElementById('editTaskCol');
	
	if (titleInput) titleInput.value = task.title || '';
	if (descInput) descInput.value = task.description || '';
	
	// Форматируем дату для input type="date"
	if (deadlineInput && task.deadline) {
		const date = new Date(task.deadline + 'T00:00:00');
		const formattedDate = date.toISOString().split('T')[0];
		deadlineInput.value = formattedDate;
	}
	
	if (impSelect) impSelect.value = task.importance || 'не срочно';
	
	// Заполняем исполнителей
	if (respSelect && window.users) {
		respSelect.innerHTML = window.users.map(u => 
			`<option value='${u.username}' ${u.username === task.responsible ? 'selected' : ''}>${u.name || u.username}</option>`
		).join('');
	}
	
	// Заполняем колонки
	if (colSelect && window.columns) {
		colSelect.innerHTML = window.columns.map(c => 
			`<option value='${c.id}' ${c.id == task.column_id ? 'selected' : ''}>${c.name}</option>`
		).join('');
	}
}

// Обновим также функцию fillTaskForm для создания задач
function fillTaskForm(task = null, defaultColumnId = null) {
	// Для создания задач используем старые ID полей
	const titleInput = document.getElementById('taskTitle');
	const descInput = document.getElementById('taskDesc');
	const deadlineInput = document.getElementById('taskDeadline');
	const impSelect = document.getElementById('taskImp');
	const respSelect = document.getElementById('taskResp');
	const colSelect = document.getElementById('taskCol');
	
	if (titleInput) titleInput.value = task ? task.title : '';
	if (descInput) descInput.value = task ? task.description : '';
	
	if (deadlineInput) {
		if (task && task.deadline) {
			const date = new Date(task.deadline + 'T00:00:00');
			deadlineInput.value = date.toISOString().split('T')[0];
		} else {
			deadlineInput.value = '';
		}
	}
	
	if (impSelect) impSelect.value = task ? task.importance : 'не срочно';
	
	// Заполняем исполнителей
	if (respSelect && window.users) {
		respSelect.innerHTML = window.users.map(u => 
			`<option value='${u.username}' ${(task && u.username === task.responsible) ? 'selected' : ''}>${u.name || u.username}</option>`
		).join('');
	}
	
	// Заполняем колонки
	if (colSelect && window.columns) {
		colSelect.innerHTML = window.columns.map(c => 
			`<option value='${c.id}' ${(task && c.id == task.column_id) || (!task && defaultColumnId == c.id) ? 'selected' : ''}>${c.name}</option>`
		).join('');
	}
}

function saveTask() {
	const title = document.getElementById('taskTitle')?.value;
	
	if (!title) {
		alert('Введите заголовок задачи');
		return;
	}
	
	let data = new URLSearchParams({
		action: 'add_task',
		title: title,
		description: document.getElementById('taskDesc')?.value || '',
		responsible: document.getElementById('taskResp')?.value || '',
		deadline: document.getElementById('taskDeadline')?.value || '',
		importance: document.getElementById('taskImp')?.value || 'не срочно',
		column_id: document.getElementById('taskCol')?.value || '1'
	});
	
	closeModal();
	fetch('api.php', { method: 'POST', body: data })
	.then(response => {
		if (response.ok) location.reload();
		else alert('Ошибка при создании задачи');
	})
	.catch(() => alert('Ошибка при создании задачи'));
}

function updateTask() {
	if (!currentEditId) return;

	const title = document.getElementById('editTaskTitle')?.value;
	if (!title) { alert('Введите заголовок задачи'); return; }

	let data = new URLSearchParams({
		action: 'update_task',
		id: currentEditId,
		title: title,
		description: document.getElementById('editTaskDesc')?.value || '',
		responsible: document.getElementById('editTaskResp')?.value || '',
		deadline: document.getElementById('editTaskDeadline')?.value || '',
		importance: document.getElementById('editTaskImp')?.value || 'не срочно'
	});

	closeModal();
	fetch('api.php', { method: 'POST', body: data })
	.then(response => {
		if (response.ok) location.reload();
		else alert('Ошибка при обновлении задачи');
	})
	.catch(() => alert('Ошибка при обновлении задачи'));
}

function deleteTask() {
	if (!currentEditId) return;
	if (!confirm('Удалить задачу?')) return;

	closeModal();
	fetch('api.php', {
		method: 'POST',
		body: new URLSearchParams({ action: 'delete_task', id: currentEditId })
	})
	.then(response => {
		if (response.ok) location.reload();
		else alert('Ошибка при удалении задачи');
	})
	.catch(() => alert('Ошибка при удалении задачи'));
}

// === Архив ===
function openArchive() {
	fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_archive' }) })
		.then(r => r.json())
		.then(archive => {
			const template = document.getElementById('archive-modal-template');
			if (template) {
				let html = template.innerHTML;
				
				// Заменяем содержимое archive-list
				const archiveHTML = archive.length ? archive.map(t => `
					<div class="archive-item">
						<h4 class="archive-title">${t.title}</h4>
						<p class="archive-description">${t.description || ''}</p>
						<div class="archive-meta">
							<span>👤 ${t.responsible_name || t.responsible}</span>
							<button onclick="restore(${t.id})" class="restore-btn">Восстановить</button>
						</div>
					</div>
				`).join('') : '<p class="text-gray-400 text-center py-4">Архив пуст</p>';
				
				html = html.replace('<!-- Archive items will be inserted here -->', archiveHTML);
				
				// Скрываем кнопку очистки если не админ
				if (!window.isAdmin) {
					html = html.replace('<button onclick="clearArchive()" class="btn-danger">Очистить архив</button>', '');
				}
				
				openModal(html);
			}
		})
		.catch(err => {
			console.error('Error loading archive:', err);
			alert('Ошибка при загрузке архива');
		});
}

function restore(id) {
	fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'restore_task', id }) })
		.then(() => location.reload())
		.catch(err => {
			console.error('Error restoring task:', err);
			alert('Ошибка при восстановлении задачи');
		});
}

function clearArchive() {
	if (!confirm('Удалить ВСЕ задачи из архива? Это действие необратимо!')) return;
	
	fetch('api.php', { 
		method: 'POST', 
		body: new URLSearchParams({ action: 'clear_archive' }) 
	})
	.then(r => r.json())
	.then(res => {
		if (res.success) {
			alert('Архив очищен!');
			closeModal();
			location.reload();
		} else {
			alert('Ошибка очистки: ' + (res.error || 'Неизвестная ошибка'));
		}
	})
	.catch(err => {
		console.error('Error clearing archive:', err);
		alert('Ошибка сети: ' + err);
	});
}

// === Настройки ===
function openUserSettings() {
	Promise.all([
		loadUsers(),
		fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_telegram_settings' }) }).then(r => r.json()),
		fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_email_settings' }) }).then(r => r.json()),
		loadLinks()
	]).then(([usersData, tgData, emailData, linksData]) => {
		const template = document.getElementById('settings-modal-template');
		if (template) {
			openModal(template.innerHTML);
			
			// Заполняем данные и инициализируем вкладки
			setTimeout(() => {
				fillSettingsData(usersData, tgData, emailData, linksData);
				initSettingsTabs(); // Инициализируем вкладки после заполнения данных
				setupTestingTab(); // Настраиваем вкладку тестирования
				
				// Обновляем статус Cron
				checkCronStatus();
			}, 100);
		}
	})
	.catch(err => {
		console.error('Error loading settings:', err);
		alert('Ошибка при загрузке настроек');
	});
}

// Редактирование пользователя в настройках
function editUserSettings(username) {
	currentEditId = username;
	
	fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_user', username }) })
		.then(r => r.json())
		.then(u => {
			const template = document.getElementById('edit-user-modal-template');
			if (template) {
				openModal(template.innerHTML);
				
				// Заполняем данные
				setTimeout(() => {
					const editUser = document.getElementById('editUser');
					const editName = document.getElementById('editName');
					const editIsAdmin = document.getElementById('editIsAdmin');
					
					if (editUser) editUser.value = u.username || '';
					if (editName) editName.value = u.name || '';
					if (editIsAdmin) editIsAdmin.checked = u.is_admin == 1;
				}, 100);
			}
		})
		.catch(err => {
			console.error('Error loading user:', err);
			alert('Ошибка при загрузке пользователя');
		});
}

function updateUser() {
	if (!currentEditId) return;
	
	let data = new URLSearchParams({
		action: 'update_user',
		username: currentEditId,
		name: document.getElementById('editName')?.value || '',
		is_admin: document.getElementById('editIsAdmin')?.checked ? 1 : 0
	});
	
	const password = document.getElementById('editPass')?.value;
	if (password) {
		data.append('password', password);
	}
	
	fetch('api.php', { 
		method: 'POST', 
		body: data 
	})
	.then(response => {
		if (response.ok) {
			location.reload();
		} else {
			alert('Ошибка при обновлении пользователя');
		}
	})
	.catch(err => {
		console.error('Error:', err);
		alert('Ошибка при обновлении пользователя');
	});
}

function addUser() {
	const username = document.getElementById('newUser')?.value;
	const password = document.getElementById('newPass')?.value;
	
	if (!username || !password) {
		alert('Заполните логин и пароль');
		return;
	}
	
	let data = new URLSearchParams({
		action: 'add_user',
		username: username,
		password: password,
		name: document.getElementById('newName')?.value || '',
		is_admin: document.getElementById('newIsAdmin')?.checked ? 1 : 0
	});
	
	fetch('api.php', { 
		method: 'POST', 
		body: data 
	})
	.then(response => {
		if (response.ok) {
			location.reload();
		} else {
			alert('Ошибка при создании пользователя');
		}
	})
	.catch(err => {
		console.error('Error:', err);
		alert('Ошибка при создании пользователя');
	});
}

function deleteUser(username) {
	if (!confirm(`Удалить пользователя ${username}?`)) return;
	
	fetch('api.php', { 
		method: 'POST', 
		body: new URLSearchParams({ action: 'delete_user', username }) 
	})
	.then(response => {
		if (response.ok) {
			location.reload();
		} else {
			alert('Ошибка при удалении пользователя');
		}
	})
	.catch(err => {
		console.error('Error:', err);
		alert('Ошибка при удалении пользователя');
	});
}

// Обновленная функция сохранения Telegram настроек
function saveTelegram() {
	const token = document.getElementById('tgToken')?.value || '';
	const chat = document.getElementById('tgChat')?.value || '';
	const dailyReportTime = document.getElementById('dailyReportTime')?.value || '10:00';
	const timerMinutes = parseInt(document.getElementById('timerNotificationMinutes')?.value || '1440');
	
	// Валидация времени
	if (!/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(dailyReportTime)) {
		alert('Неверный формат времени. Используйте формат ЧЧ:ММ (например, 10:00)');
		return;
	}
	
	// Валидация минут
	if (timerMinutes < 1 || timerMinutes > 43200) {
		alert('Количество минут должно быть от 1 до 43200 (30 дней)');
		return;
	}
	
	const notifEnabled = document.getElementById('tgEnabled')?.checked ? 1 : 0;
	let data = new URLSearchParams({
		action: 'save_telegram_settings',
		bot_token: token,
		chat_id: chat,
		daily_report_time: dailyReportTime,
		timer_notification_minutes: timerMinutes,
		notifications_enabled: notifEnabled
	});
	
	fetch('api.php', { 
		method: 'POST', 
		body: data 
	})
	.then(r => r.json())
	.then(res => {
		if (res.success) {
			alert('Настройки успешно сохранены!');
			// Обновляем информацию о текущих настройках
			const currentReportTime = document.getElementById('current-report-time');
			const currentTimerMinutes = document.getElementById('current-timer-minutes');
			
			if (currentReportTime) currentReportTime.textContent = dailyReportTime;
			if (currentTimerMinutes) currentTimerMinutes.textContent = timerMinutes;
		} else {
			alert('Ошибка сохранения настроек');
		}
	})
	.catch(err => {
		console.error('Error saving telegram:', err);
		alert('Ошибка сохранения');
	});
}

function saveEmail() {
	const g = id => document.getElementById(id)?.value || '';
	const data = new URLSearchParams({
		action: 'save_email_settings',
		enabled: document.getElementById('emailEnabled')?.checked ? 1 : 0,
		host: g('emailHost'),
		port: g('emailPort') || 587,
		encryption: g('emailEncryption') || 'tls',
		username: g('emailUsername'),
		password: g('emailPassword'),
		from_email: g('emailFromEmail'),
		from_name: g('emailFromName') || 'Kanban',
		to_email: g('emailToEmail')
	});
	fetch('api.php', { method: 'POST', body: data })
		.then(r => r.json())
		.then(res => alert(res.success ? 'Email настройки сохранены!' : 'Ошибка сохранения'))
		.catch(() => alert('Ошибка сети'));
}

function testEmail() {
	fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'test_email' }) })
		.then(r => r.json())
		.then(res => alert(res.success ? '✅ Тестовое письмо отправлено!' : '❌ Ошибка отправки. Проверьте настройки Email.'))
		.catch(() => alert('❌ Ошибка сети'));
}

function testTelegram() {
	fetch('api.php', { 
		method: 'POST', 
		body: new URLSearchParams({ action: 'test_telegram' }) 
	})
	.then(r => r.json())
	.then(res => {
		if (res.success) {
			updateTestingStatus('✅ Тестовое уведомление успешно отправлено в Telegram!', 'success');
		} else {
			updateTestingStatus('❌ Ошибка отправки тестового уведомления. Проверьте настройки Telegram.', 'error');
		}
	})
	.catch(err => {
		console.error('Error testing telegram:', err);
		updateTestingStatus('❌ Ошибка сети при тестировании Telegram.', 'error');
	});
}

// === НОВЫЕ ФУНКЦИИ ТЕСТИРОВАНИЯ ===

// Тестирование уведомления о таймере
function testTimerNotification() {
	updateTestingStatus('⏳ Отправка тестового уведомления о таймере...', 'loading');
	
	fetch('api.php', { 
		method: 'POST', 
		body: new URLSearchParams({ action: 'test_timer_notification' }) 
	})
	.then(r => r.json())
	.then(res => {
		if (res.success) {
			updateTestingStatus('✅ Тестовое уведомление о таймере успешно отправлено!', 'success');
		} else {
			const errorMsg = res.error ? `: ${res.error}` : '';
			updateTestingStatus(`❌ Ошибка отправки уведомления таймера${errorMsg}`, 'error');
		}
	})
	.catch(err => {
		console.error('Error testing timer notification:', err);
		updateTestingStatus('❌ Ошибка сети при тестировании уведомления таймера.', 'error');
	});
}

// Тестирование ежедневного отчета
function testDailyReport() {
	updateTestingStatus('⏳ Отправка тестового ежедневного отчета...', 'loading');
	
	fetch('api.php', { 
		method: 'POST', 
		body: new URLSearchParams({ action: 'test_daily_report' }) 
	})
	.then(r => r.json())
	.then(res => {
		if (res.success) {
			updateTestingStatus('✅ Тестовый ежедневный отчет успешно отправлен!', 'success');
		} else {
			const errorMsg = res.error ? `: ${res.error}` : '';
			updateTestingStatus(`❌ Ошибка отправки ежедневного отчета${errorMsg}`, 'error');
		}
	})
	.catch(err => {
		console.error('Error testing daily report:', err);
		updateTestingStatus('❌ Ошибка сети при тестировании ежедневного отчета.', 'error');
	});
}

// Проверка статуса Cron
function checkCronStatus() {
	updateTestingStatus('⏳ Проверка статуса Cron...', 'loading');
	
	// Проверяем, когда последний раз выполнялся cron
	const cronStatusEl = document.getElementById('cron-status');
	if (cronStatusEl) {
		cronStatusEl.textContent = 'Проверка...';
	}
	
	// Простая проверка - пытаемся получить время последнего выполнения
	fetch('scheduled_kanban.php')
		.then(response => {
			if (response.ok) {
				if (cronStatusEl) {
					cronStatusEl.textContent = '✅ Активен';
					cronStatusEl.classList.add('text-green-500');
					cronStatusEl.classList.remove('text-red-500');
				}
				updateTestingStatus('✅ Cron активен и работает корректно.', 'success');
			} else {
				throw new Error('Cron script not accessible');
			}
		})
		.catch(err => {
			if (cronStatusEl) {
				cronStatusEl.textContent = '❌ Ошибка';
				cronStatusEl.classList.add('text-red-500');
				cronStatusEl.classList.remove('text-green-500');
			}
			updateTestingStatus('⚠️ Cron недоступен или произошла ошибка. Проверьте настройки сервера.', 'warning');
			console.error('Error checking cron status:', err);
		});
}

// Обновление статуса тестирования
function updateTestingStatus(message, type = 'info') {
	const statusEl = document.getElementById('testing-status');
	if (!statusEl) return;
	
	let icon = '';
	let colorClass = '';
	
	switch (type) {
		case 'success':
			icon = '✅ ';
			colorClass = 'text-green-400';
			break;
		case 'error':
			icon = '❌ ';
			colorClass = 'text-red-400';
			break;
		case 'warning':
			icon = '⚠️ ';
			colorClass = 'text-yellow-400';
			break;
		case 'loading':
			icon = '⏳ ';
			colorClass = 'text-blue-400';
			break;
		default:
			icon = 'ℹ️ ';
			colorClass = 'text-gray-400';
	}
	
	statusEl.innerHTML = `<span class="${colorClass}">${icon}${message}</span>`;
	
	// Автоматически очищаем сообщение через 10 секунд (кроме загрузки)
	if (type !== 'loading') {
		setTimeout(() => {
			if (statusEl.innerHTML.includes(message)) {
				statusEl.innerHTML = 'Нажмите на кнопки выше для тестирования различных уведомлений.';
			}
		}, 10000);
	}
}

// === Ссылки ===
function openLinkPicker() {
	const linkPicker = document.getElementById('link-picker');
	if (linkPicker) {
		linkPicker.classList.remove('hidden');
		loadLinksList();
		
		// Убедимся, что пикер поверх основной модалки
		linkPicker.style.zIndex = '100';
	}
}

function loadLinksList() {
	fetch('api.php', { method: 'POST', body: new URLSearchParams({ action: 'get_links' }) })
		.then(r => r.json())
		.then(data => {
			const linksList = document.getElementById('links-list');
			if (linksList) {
				linksList.innerHTML = data.length ? data.map(l => `
					<div class="flex justify-between items-center p-1 hover:bg-gray-600 rounded">
						<span class="text-sm cursor-pointer text-blue-400 hover:underline" onclick="insertLink('${l.name}', '${l.url}')">${l.name}</span>
						<button onclick="deleteLink(${l.id})" class="text-red-400 text-xs">✖</button>
					</div>
				`).join('') : '<p class="text-gray-500 text-xs">Нет сохранённых ссылок</p>';
			}
		})
		.catch(err => console.error('Error loading links:', err));
}

function insertLink(name, url) {
	// Получаем текущее активное текстовое поле
	let desc = null;
	
	// Ищем активное текстовое поле в текущей модалке
	const modalContent = document.getElementById('modal-content');
	if (modalContent) {
		// Сначала пробуем найти текстовое поле, которое сейчас в фокусе
		const activeElement = document.activeElement;
		if (activeElement && (activeElement.tagName === 'TEXTAREA' || 
			(activeElement.tagName === 'INPUT' && activeElement.type === 'text'))) {
			desc = activeElement;
		}
		
		// Если не нашли активный элемент, ищем текстовые поля с описанием задачи
		if (!desc) {
			desc = modalContent.querySelector('#editTaskDesc') || 
				   modalContent.querySelector('#taskDesc');
		}
	}
	
	if (!desc) {
		console.error('Could not find textarea for inserting link');
		alert('Сначала кликните в поле описания, чтобы вставить ссылку');
		return;
	}
	
	const start = desc.selectionStart;
	const end = desc.selectionEnd;
	const text = desc.value;
	const insert = `[${name}](${url})`;
	
	desc.value = text.slice(0, start) + insert + text.slice(end);
	desc.focus();
	desc.setSelectionRange(start + insert.length, start + insert.length);
	
	// Закрываем пикер ссылок
	closeLinkPicker();
}

function saveLink() {
	const name = document.getElementById('linkName')?.value.trim();
	const url = document.getElementById('linkUrl')?.value.trim();
	
	if (!name || !url) {
		alert('Заполните имя и URL');
		return;
	}
	
	fetch('api.php', {
		method: 'POST',
		body: new URLSearchParams({ action: 'add_link', name, url })
	})
	.then(() => {
		document.getElementById('linkName').value = '';
		document.getElementById('linkUrl').value = '';
		loadLinksList();
	})
	.catch(err => {
		console.error('Error saving link:', err);
		alert('Ошибка сохранения ссылки');
	});
}

function adminAddLink() {
	const name = document.getElementById('newLinkName')?.value.trim();
	const url = document.getElementById('newLinkUrl')?.value.trim();
	
	if (!name || !url) {
		alert('Заполните поля');
		return;
	}
	
	fetch('api.php', {
		method: 'POST',
		body: new URLSearchParams({ action: 'add_link', name, url })
	})
	.then(() => {
		document.getElementById('newLinkName').value = '';
		document.getElementById('newLinkUrl').value = '';
		openUserSettings(); // Перезагружаем настройки
	})
	.catch(err => {
		console.error('Error adding link:', err);
		alert('Ошибка добавления ссылки');
	});
}

function deleteLink(id) {
	if (!confirm('Удалить ссылку?')) return;
	
	fetch('api.php', {
		method: 'POST',
		body: new URLSearchParams({ action: 'delete_link', id })
	})
	.then(() => {
		loadLinksList();
		// Если открыты настройки, обновляем их
		if (!document.getElementById('modal-bg').classList.contains('hidden')) {
			openUserSettings();
		}
	})
	.catch(err => {
		console.error('Error deleting link:', err);
		alert('Ошибка удаления ссылки');
	});
}

function archiveNow(id) {
	if (!confirm('Отправить в архив?')) return;
	
	fetch('api.php', { 
		method: 'POST', 
		body: new URLSearchParams({ action: 'archive_now', id }) 
	})
	.then(() => location.reload())
	.catch(err => {
		console.error('Error archiving task:', err);
		alert('Ошибка архивирования задачи');
	});
}