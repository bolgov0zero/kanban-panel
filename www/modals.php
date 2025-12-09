<?php
// Этот файл содержит HTML для всех модальных окон
// Функции управления находятся в script.js
?>

<!-- Main Modal Container -->
<div id="modal-bg" class="modal-backdrop hidden">
	<div id="modal-container" class="modal-container">
		<div id="modal-content" class="modal-content">
			<!-- контент вставляется динамически -->
		</div>
	</div>
</div>

<!-- Link Picker Modal -->
<div id="link-picker" class="modal-backdrop hidden">
	<div class="modal-container">
		<div class="link-picker-container">
			<div class="link-picker-header">
				<h3 class="link-picker-title">Быстрые ссылки</h3>
				<button onclick="closeLinkPicker()" class="link-picker-close">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
					</svg>
				</button>
			</div>
			<div id="links-list" class="links-list"></div>
			<?php if ($isAdmin): ?>
			<div class="link-picker-form">
				<input id="linkName" placeholder="Имя ссылки" class="link-input">
				<input id="linkUrl" placeholder="https://..." class="link-input">
				<button onclick="saveLink()" class="link-add-btn">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
					</svg>
					Добавить ссылку
				</button>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Archive Modal Template -->
<div id="archive-modal-template" style="display: none;">
	<div class="modal-container large">
		<div class="modal-header">
			<h2 class="modal-title">Архив задач</h2>
			<button onclick="closeModal()" class="modal-close-btn">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</div>

		<div class="modal-body">
			<div class="archive-list">
				<!-- Archive items will be inserted here -->
			</div>
		</div>

		<div class="modal-footer">
			<button onclick="closeModal()" class="btn-secondary">Закрыть</button>
		</div>
	</div>
</div>

<!-- Settings Modal Template -->
<div id="settings-modal-template" style="display: none;">
	<div class="modal-container xlarge">
		<div class="modal-header">
			<h2 class="modal-title">Управление системой</h2>
			<button onclick="closeModal()" class="modal-close-btn">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</div>

		<div class="modal-body" style="padding: 0;">
			<div class="settings-layout">
				<!-- Боковое меню -->
				<div class="settings-sidebar">
					<div class="settings-nav">
						<button data-tab="users" class="settings-menu-item active">
							<div class="nav-icon">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
								</svg>
							</div>
							<span class="nav-text">Пользователи</span>
						</button>
						
						<button data-tab="timers" class="settings-menu-item"> <!-- НОВАЯ ВКЛАДКА -->
							<div class="nav-icon">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
								</svg>
							</div>
							<span class="nav-text">Таймеры</span>
						</button>
						
						<button data-tab="integrations" class="settings-menu-item">
							<div class="nav-icon">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
								</svg>
							</div>
							<span class="nav-text">Интеграции</span>
						</button>
						
						<button data-tab="system" class="settings-menu-item">
							<div class="nav-icon">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
								</svg>
							</div>
							<span class="nav-text">Система</span>
						</button>
						
						<button data-tab="testing" class="settings-menu-item">
							<div class="nav-icon">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
								</svg>
							</div>
							<span class="nav-text">Тестирование</span>
						</button>
					</div>
					
					<div class="sidebar-footer">
						<div class="system-status">
							<div class="status-indicator online"></div>
							<span class="status-text">Система активна</span>
						</div>
					</div>
				</div>

				<!-- Основной контент -->
				<div class="settings-main">
					<!-- Вкладка Пользователи -->
					<div id="users-tab" class="tab-content active">
						<!-- ... существующий контент ... -->
					</div>

					<!-- НОВАЯ ВКЛАДКА: Таймеры -->
					<div id="timers-tab" class="tab-content">
						<div class="tab-header">
							<h3 class="tab-title">Управление таймерами и уведомлениями</h3>
							<p class="tab-description">Настройка автоматических уведомлений о задачах и времени отчетов</p>
						</div>

						<div class="content-section">
							<h4 class="section-title">Основные настройки таймеров</h4>
							
							<div class="form-group mb-4">
								<label class="checkbox-label large">
									<input id="timerEnabled" type="checkbox" class="checkbox-input">
									<span class="checkbox-custom"></span>
									<span class="checkbox-text">Включить автоматические уведомления</span>
								</label>
								<p class="form-hint">При отключении уведомления отправляться не будут</p>
							</div>

							<div class="form-grid">
								<div class="form-group">
									<label class="form-label">Время уведомления (часы)</label>
									<div class="input-with-unit">
										<input id="timerHours" type="number" min="1" max="720" class="form-input" placeholder="24">
										<span class="input-unit">часов</span>
									</div>
									<p class="form-hint">Через сколько часов отправлять уведомление о задаче в колонке</p>
								</div>

								<div class="form-group">
									<label class="form-label">Предварительное уведомление</label>
									<div class="input-with-unit">
										<input id="notifyBeforeHours" type="number" min="0" max="24" class="form-input" placeholder="2">
										<span class="input-unit">часов</span>
									</div>
									<p class="form-hint">За сколько часов до основного уведомления отправлять предупреждение</p>
								</div>
							</div>

							<div class="form-group">
								<label class="form-label">Время ежедневного отчета</label>
								<input id="reportTime" type="time" class="form-input" value="10:00">
								<p class="form-hint">Время отправки ежедневного отчета по задачам (по Москве)</p>
							</div>

							<div class="action-buttons">
								<button onclick="saveTimerSettings()" class="btn-primary">
									<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
									</svg>
									Сохранить настройки
								</button>
							</div>
						</div>

						<div class="content-section">
							<h4 class="section-title">Информация о текущих настройках</h4>
							<div class="settings-info-box">
								<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
									<div>
										<h6 class="font-medium mb-2">Настройки уведомлений</h6>
										<ul class="settings-info-list" id="current-timer-settings">
											<li><span class="status-dot blue"></span> Загрузка...</li>
										</ul>
									</div>
									<div>
										<h6 class="font-medium mb-2">Следующие проверки</h6>
										<ul class="settings-info-list">
											<li><span class="status-dot green"></span> Таймеры: каждую минуту</li>
											<li><span class="status-dot green"></span> Отчет: ежедневно в <span id="next-report-time">10:00</span></li>
											<li><span class="status-dot green"></span> Cron активен</li>
										</ul>
									</div>
								</div>
							</div>
						</div>

						<div class="content-section">
							<h4 class="section-title">Тестирование уведомлений</h4>
							<div class="testing-grid">
								<div class="testing-card">
									<div class="testing-card-header">
										<div class="testing-icon testing-icon-yellow">
											<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
											</svg>
										</div>
										<h5 class="testing-card-title">Основное уведомление</h5>
									</div>
									<p class="testing-card-description">Тест уведомления о задаче, которая находится в колонке установленное время</p>
									<button onclick="testTimerNotification()" class="testing-btn testing-btn-yellow">
										Тест основного уведомления
									</button>
								</div>

								<div class="testing-card">
									<div class="testing-card-header">
										<div class="testing-icon testing-icon-blue">
											<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
											</svg>
										</div>
										<h5 class="testing-card-title">Предварительное уведомление</h5>
									</div>
									<p class="testing-card-description">Тест уведомления за N часов до истечения времени</p>
									<button onclick="testTimerReminder()" class="testing-btn testing-btn-blue">
										Тест предварительного уведомления
									</button>
								</div>
							</div>
						</div>
					</div>

					<!-- Вкладка Интеграции -->
					<div id="integrations-tab" class="tab-content">
						<!-- ... существующий контент ... -->
					</div>

					<!-- Вкладка Система -->
					<div id="system-tab" class="tab-content">
						<!-- ... существующий контент ... -->
					</div>

					<!-- Вкладка Тестирование -->
					<div id="testing-tab" class="tab-content">
						<!-- ... существующий контент ... -->
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Edit User Modal Template -->
<div id="edit-user-modal-template" style="display: none;">
	<div class="modal-container medium">
		<div class="modal-header">
			<h2 class="modal-title">Редактировать пользователя</h2>
			<button onclick="closeModal()" class="modal-close-btn">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</div>

		<div class="modal-body">
			<div class="form-group">
				<label class="form-label">Логин</label>
				<input id='editUser' class='form-input' readonly>
			</div>

			<div class="form-group">
				<label class="form-label">Имя</label>
				<input id='editName' class='form-input' placeholder='Полное имя'>
			</div>

			<div class="form-group">
				<label class="form-label">Новый пароль</label>
				<input id='editPass' type='password' class='form-input' placeholder='Оставьте пустым, чтобы не менять'>
			</div>

			<div class="checkbox-group">
				<label class="checkbox-label">
					<input id='editIsAdmin' type='checkbox' class='checkbox-input'>
					<span class="checkbox-custom"></span>
					<span class="checkbox-text">Администратор</span>
				</label>
			</div>
		</div>

		<div class="modal-footer">
			<button onclick='closeModal()' class='btn-secondary'>Отмена</button>
			<button onclick='updateUser()' class='btn-primary'>Сохранить</button>
		</div>
	</div>
</div>

<!-- Add Column Modal Template -->
<div id="add-column-modal-template" style="display: none;">
	<div class="modal-container xlarge">
		<div class="modal-header">
			<h2 class="modal-title">Добавить колонку</h2>
			<button onclick="closeModal()" class="modal-close-btn">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</div>

		<div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
			<div class="form-group">
				<label class="form-label">Название колонки</label>
				<input id='colName' placeholder='Например: В работе' class='form-input'>
			</div>

			<div class="form-group">
				<label class="form-label">Цвет заголовка</label>
				<div class="color-input-group">
					<input id='colBg' type='color' value='#374151' class='color-input'>
					<span class="color-value" id="colBgValue">#374151</span>
				</div>
			</div>

			<div class="checkbox-group">
				<label class="checkbox-label">
					<input id='autoComplete' type='checkbox' class='checkbox-input'>
					<span class="checkbox-custom"></span>
					<span class="checkbox-text">Автоматически завершать задачи</span>
				</label>
			</div>

			<div class="checkbox-group">
				<label class="checkbox-label">
					<input id='timer' type='checkbox' class='checkbox-input'>
					<span class="checkbox-custom"></span>
					<span class="checkbox-text">Включить таймер для задач</span>
				</label>
			</div>
		</div>

		<div class="modal-footer">
			<button onclick='closeModal()' class='btn-secondary'>Отмена</button>
			<button onclick='saveColumn()' class='btn-primary'>Создать колонку</button>
		</div>
	</div>
</div>

<!-- Edit Column Modal Template -->
<div id="edit-column-modal-template" style="display: none;">
	<div class="modal-container xlarge">
		<div class="modal-header">
			<h2 class="modal-title">Редактировать колонку</h2>
			<button onclick="closeModal()" class="modal-close-btn">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</div>

		<div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
			<div class="form-group">
				<label class="form-label">Название колонки</label>
				<input id='editColName' class='form-input'>
			</div>

			<div class="form-group">
				<label class="form-label">Цвет заголовка</label>
				<div class="color-input-group">
					<input id='editColBg' type='color' class='color-input'>
					<span class="color-value" id="editColBgValue">#374151</span>
				</div>
			</div>

			<div class="checkbox-group">
				<label class="checkbox-label">
					<input id='editAutoComplete' type='checkbox' class='checkbox-input'>
					<span class="checkbox-custom"></span>
					<span class="checkbox-text">Автоматически завершать задачи</span>
				</label>
			</div>

			<div class="checkbox-group">
				<label class="checkbox-label">
					<input id='editTimer' type='checkbox' class='checkbox-input'>
					<span class="checkbox-custom"></span>
					<span class="checkbox-text">Включить таймер для задач</span>
				</label>
			</div>
		</div>

		<div class="modal-footer">
			<button onclick='deleteColumn()' class='btn-danger'>Удалить</button>
			<button onclick='closeModal()' class='btn-secondary'>Отмена</button>
			<button onclick='updateColumn()' class='btn-primary'>Сохранить</button>
		</div>
	</div>
</div>

<!-- Add Task Modal Template -->
<div id="add-task-modal-template" style="display: none;">
	<div class="modal-container task-mod-win">
		<div class="modal-header">
			<h2 class="modal-title">Новая задача</h2>
			<button onclick="closeModal()" class="modal-close-btn">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</div>

		<div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
			<div class="form-group">
				<label class="form-label">Заголовок задачи</label>
				<input id='taskTitle' placeholder='Например: Подготовить отчёт' class='form-input'>
			</div>

			<div class="form-group">
				<label class="form-label">Описание</label>
				<div class="textarea-with-picker">
					<textarea id='taskDesc' placeholder='Описание задачи...' class='form-input' style="min-height: 100px;"></textarea>
					<button type="button" onclick="openLinkPicker()" class="link-picker-btn" title="Добавить ссылку">
						<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
						</svg>
					</button>
				</div>
			</div>

			<div class="form-grid">
				<div class="form-group">
					<label class="form-label">Исполнитель</label>
					<select id='taskResp' class='form-select'></select>
				</div>

				<div class="form-group">
					<label class="form-label">Срок выполнения</label>
					<input id='taskDeadline' type='date' class='form-input'>
				</div>
			</div>

			<div class="form-grid">
				<div class="form-group">
					<label class="form-label">Приоритет</label>
					<select id='taskImp' class='form-select'>
						<option value='не срочно'>🟢 Не срочно</option>
						<option value='средне'>🟡 Средне</option>
						<option value='срочно'>🔴 Срочно</option>
					</select>
				</div>

				<div class="form-group">
					<label class="form-label">Колонка</label>
					<select id='taskCol' class='form-select'></select>
				</div>
			</div>
		</div>

		<div class="modal-footer">
			<button onclick='closeModal()' class='btn-secondary'>Отмена</button>
			<button onclick='saveTask()' class='btn-primary'>Создать задачу</button>
		</div>
	</div>
</div>

<!-- Edit Task Modal Template -->
<div id="edit-task-modal-template" style="display: none;">
	<div class="modal-container task-mod-win">
		<div class="modal-header">
			<h2 class="modal-title">Редактировать задачу</h2>
			<button onclick="closeModal()" class="modal-close-btn">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>
		</div>

		<div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
			<div class="form-group">
				<label class="form-label">Заголовок задачи</label>
				<input id='editTaskTitle' class='form-input'>
			</div>

			<div class="form-group">
				<label class="form-label">Описание</label>
				<div class="textarea-with-picker">
					<textarea id='editTaskDesc' class='form-input' style="min-height: 100px;"></textarea>
					<button type="button" onclick="openLinkPicker()" class="link-picker-btn" title="Добавить ссылку">
						<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
						</svg>
					</button>
				</div>
			</div>

			<div class="form-grid">
				<div class="form-group">
					<label class="form-label">Исполнитель</label>
					<select id='editTaskResp' class='form-select'></select>
				</div>

				<div class="form-group">
					<label class="form-label">Срок выполнения</label>
					<input id='editTaskDeadline' type='date' class='form-input'>
				</div>
			</div>

			<div class="form-grid">
				<div class="form-group">
					<label class="form-label">Приоритет</label>
					<select id='editTaskImp' class='form-select'>
						<option value='не срочно'>🟢 Не срочно</option>
						<option value='средне'>🟡 Средне</option>
						<option value='срочно'>🔴 Срочно</option>
					</select>
				</div>

				<div class="form-group">
					<label class="form-label">Колонка</label>
					<select id='editTaskCol' class='form-select'></select>
				</div>
			</div>
		</div>

		<div class="modal-footer">
			<button onclick='deleteTask()' class='btn-danger'>Удалить</button>
			<button onclick='closeModal()' class='btn-secondary'>Отмена</button>
			<button onclick='updateTask()' class='btn-primary'>Сохранить</button>
		</div>
	</div>
</div>