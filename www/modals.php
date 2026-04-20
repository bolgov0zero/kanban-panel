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
					<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
					<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
				<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
			<h2 class="modal-title" style="font-size:0.75rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-3);">Настройки</h2>
			<button onclick="closeModal()" class="modal-close-btn">×</button>
		</div>

		<div class="modal-body" style="padding: 0;">
			<div class="settings-layout">
				<!-- Боковое меню -->
				<div class="settings-sidebar">
					<div class="sidebar-label">Раздел</div>
					<div class="settings-nav">
						<button data-tab="users" class="settings-menu-item active">
							<div class="nav-icon">
								<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
								</svg>
							</div>
							<span class="nav-text">Пользователи</span>
						</button>
						
						<button data-tab="integrations" class="settings-menu-item">
							<div class="nav-icon">
								<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
								</svg>
							</div>
							<span class="nav-text">Интеграции</span>
						</button>
						
						<button data-tab="system" class="settings-menu-item">
							<div class="nav-icon">
								<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
								</svg>
							</div>
							<span class="nav-text">Система</span>
						</button>
						
						<!-- НОВАЯ ВКЛАДКА: Тестирование -->
						<button data-tab="testing" class="settings-menu-item">
							<div class="nav-icon">
								<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
						<div class="tab-header">
							<h3 class="tab-title">Управление пользователями</h3>
							<p class="tab-description">Добавление и редактирование пользователей системы</p>
						</div>

						<div class="content-section">
							<h4 class="section-title">Новый пользователь</h4>
							<div class="form-grid compact">
								<div class="form-group">
									<label class="form-label">Логин *</label>
									<input id="newUser" placeholder="Уникальный идентификатор" class="form-input" required>
								</div>
								<div class="form-group">
									<label class="form-label">Пароль *</label>
									<input id="newPass" type="password" placeholder="Минимум 6 символов" class="form-input" required>
								</div>
								<div class="form-group">
									<label class="form-label">Полное имя</label>
									<input id="newName" placeholder="Иван Иванов" class="form-input">
								</div>
								<div class="form-group">
									<label class="form-label">Права в системе</label>
									<label class="checkbox-label large">
										<input id="newIsAdmin" type="checkbox" class="checkbox-input">
										<span class="checkbox-custom"></span>
										<span class="checkbox-text">Администратор системы</span>
									</label>
								</div>
							</div>
							<button onclick="addUser()" class="btn-primary full-width">
								<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
								</svg>
								Создать пользователя
							</button>
						</div>

						<div class="content-section">
							<div class="section-header">
								<h4 class="section-title">Активные пользователи</h4>
								<span class="users-count" id="users-count">0 пользователей</span>
							</div>
							<div id="users-list" class="users-list">
								<!-- Users will be loaded here -->
							</div>
						</div>
					</div>

					<!-- Вкладка Интеграции -->
					<div id="integrations-tab" class="tab-content">
						<div class="tab-header">
							<h3 class="tab-title">Интеграции и ссылки</h3>
							<p class="tab-description">Управление быстрыми ссылками и внешними интеграциями</p>
						</div>

						<div class="content-section">
							<h4 class="section-title">Общие настройки уведомлений</h4>
							<p class="tab-description" style="margin-bottom:1rem;">Применяются ко всем включённым каналам: Telegram, Email.</p>
							<div class="form-grid">
								<div class="form-group">
									<label class="form-label">Время ежедневного отчета (МСК)</label>
									<input id="dailyReportTime" type="time" value="10:00" class="form-input">
									<p class="form-hint">Отчёт по открытым задачам отправляется каждый день в это время</p>
								</div>
								<div class="form-group">
									<label class="form-label">Интервал уведомления таймера</label>
									<input id="timerNotificationMinutes" type="number" min="1" max="43200" value="1440" class="form-input">
									<p class="form-hint">Через сколько минут уведомлять о задаче в колонке с таймером (1–43200)</p>
								</div>
							</div>
							<div class="action-buttons">
								<button onclick="saveNotificationGlobals()" class="btn-primary">
									<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
									</svg>
									Сохранить
								</button>
							</div>
						</div>

						<div class="content-section">
							<h4 class="section-title">Telegram уведомления</h4>
							<div class="form-group" style="margin-bottom:12px;">
								<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
									<input type="checkbox" id="tgEnabled" style="width:16px;height:16px;cursor:pointer;">
									<span class="form-label" style="margin:0;">Включить уведомления в Telegram</span>
								</label>
							</div>
							<div class="form-grid">
								<div class="form-group">
									<label class="form-label">Токен бота</label>
									<input id="tgToken" placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz" class="form-input">
									<p class="form-hint">Получите у @BotFather в Telegram</p>
								</div>
								<div class="form-group">
									<label class="form-label">Chat ID</label>
									<input id="tgChat" placeholder="123456789" class="form-input">
									<p class="form-hint">ID чата для отправки уведомлений</p>
								</div>
							</div>
							<div class="action-buttons">
								<button onclick="saveTelegram()" class="btn-primary">
									<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
									</svg>
									Сохранить настройки
								</button>
								<button onclick="testTelegram()" class="btn-secondary">
									<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
									</svg>
									Тест уведомления
								</button>
							</div>
						</div>

						<div class="content-section">
							<h4 class="section-title">Email уведомления</h4>
							<div class="form-group" style="margin-bottom:12px;">
								<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
									<input type="checkbox" id="emailEnabled" style="width:16px;height:16px;cursor:pointer;">
									<span class="form-label" style="margin:0;">Включить уведомления на Email</span>
								</label>
							</div>
							<div class="form-grid">
								<div class="form-group">
									<label class="form-label">SMTP хост</label>
									<input id="emailHost" placeholder="smtp.gmail.com" class="form-input">
								</div>
								<div class="form-group">
									<label class="form-label">Порт</label>
									<input id="emailPort" type="number" placeholder="587" value="587" class="form-input">
								</div>
							</div>
							<div class="form-grid">
								<div class="form-group">
									<label class="form-label">Шифрование</label>
									<select id="emailEncryption" class="form-input">
										<option value="tls">TLS (STARTTLS)</option>
										<option value="ssl">SSL</option>
										<option value="none">Нет</option>
									</select>
								</div>
								<div class="form-group">
									<label class="form-label">Логин</label>
									<input id="emailUsername" placeholder="user@example.com" class="form-input">
								</div>
							</div>
							<div class="form-grid">
								<div class="form-group">
									<label class="form-label">Пароль</label>
									<input id="emailPassword" type="password" placeholder="••••••••" class="form-input">
								</div>
								<div class="form-group">
									<label class="form-label">От кого (адрес)</label>
									<input id="emailFromEmail" placeholder="kanban@example.com" class="form-input">
								</div>
							</div>
							<div class="form-grid">
								<div class="form-group">
									<label class="form-label">От кого (имя)</label>
									<input id="emailFromName" placeholder="Kanban" value="Kanban" class="form-input">
								</div>
								<div class="form-group">
									<label class="form-label">Кому (получатель)</label>
									<input id="emailToEmail" placeholder="admin@example.com" class="form-input">
								</div>
							</div>
							<div class="action-buttons">
								<button onclick="saveEmail()" class="btn-primary">
									<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
									</svg>
									Сохранить настройки
								</button>
								<button onclick="testEmail()" class="btn-secondary">
									<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
									</svg>
									Тест уведомления
								</button>
							</div>
						</div>

						<div class="content-section">
							<h4 class="section-title">Быстрые ссылки</h4>
							<div class="form-grid compact">
								<div class="form-group">
									<label class="form-label">Название ссылки</label>
									<input id="newLinkName" placeholder="Документация проекта" class="form-input">
								</div>
								<div class="form-group">
									<label class="form-label">URL адрес</label>
									<input id="newLinkUrl" placeholder="https://example.com/docs" class="form-input">
								</div>
							</div>
							<button onclick="adminAddLink()" class="btn-primary full-width">
								<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
								</svg>
								Добавить ссылку
							</button>
						</div>

						<div class="content-section">
							<div class="section-header">
								<h4 class="section-title">Список ссылок</h4>
								<span class="links-count" id="links-count">0 ссылок</span>
							</div>
							<div id="admin-links-list" class="links-grid">
								<!-- Links will be loaded here -->
							</div>
						</div>
					</div>

					<!-- Вкладка Система -->
					<div id="system-tab" class="tab-content">
						<div class="tab-header">
							<h3 class="tab-title">Системная информация</h3>
							<p class="tab-description">Техническая информация о системе и управление данными</p>
						</div>

						<div class="content-section">
							<h4 class="section-title">Информация о сервере</h4>
							<div class="system-info">
								<div class="info-row">
									<span class="info-label">Версия PHP</span>
									<span class="info-value"><?php echo phpversion(); ?></span>
								</div>
								<div class="info-row">
									<span class="info-label">База данных</span>
									<span class="info-value">SQLite3 <?php echo class_exists('SQLite3') ? '✓' : '✗'; ?></span>
								</div>
								<div class="info-row">
									<span class="info-label">Время сервера</span>
									<span class="info-value"><?php echo date('d.m.Y H:i:s'); ?></span>
								</div>
								<div class="info-row">
									<span class="info-label">Cron активен</span>
									<span class="info-value" id="cron-status">Проверка...</span>
								</div>
							</div>
						</div>

						<div class="content-section">
							<h4 class="section-title">Управление данными</h4>
							<div class="danger-actions">
								<div class="danger-action">
									<div class="danger-info">
										<h5 class="danger-title">Очистка архива</h5>
										<p class="danger-description">Удаление всех завершенных задач из архива</p>
									</div>
									<button onclick="clearArchive()" class="btn-danger">
										Очистить архив
									</button>
								</div>
							</div>
						</div>
					</div>

					<!-- НОВАЯ ВКЛАДКА: Тестирование -->
					<div id="testing-tab" class="tab-content">
						<div class="tab-header">
							<h3 class="tab-title">Тестирование системы уведомлений</h3>
							<p class="tab-description">Проверка работы автоматических уведомлений и отчетов</p>
						</div>

						<div class="content-section">
							<h4 class="section-title">Уведомления</h4>
							<div class="testing-container">
								<div class="testing-card">
									<div class="testing-card-header">
										<div class="testing-card-icon" style="background:rgba(245,158,11,0.15);">
											<svg width="18" height="18" fill="none" stroke="#f59e0b" stroke-width="1.75" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
										</div>
										<span class="testing-card-title">Уведомление таймера</span>
									</div>
									<p class="testing-card-description">Тест уведомления о задаче, которая находится в колонке заданное время.</p>
									<button onclick="testTimerNotification()" class="testing-button testing-button-yellow">
										<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
										Тест таймера
									</button>
								</div>

								<div class="testing-card">
									<div class="testing-card-header">
										<div class="testing-card-icon" style="background:rgba(34,197,94,0.15);">
											<svg width="18" height="18" fill="none" stroke="#22c55e" stroke-width="1.75" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
										</div>
										<span class="testing-card-title">Ежедневный отчет</span>
									</div>
									<p class="testing-card-description">Тест ежедневного отчета по открытым задачам.</p>
									<button onclick="testDailyReport()" class="testing-button testing-button-green">
										<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
										Тест отчета
									</button>
								</div>

								<div class="testing-card">
									<div class="testing-card-header">
										<div class="testing-card-icon" style="background:rgba(139,92,246,0.15);">
											<svg width="18" height="18" fill="none" stroke="#8b5cf6" stroke-width="1.75" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
										</div>
										<span class="testing-card-title">Статус Cron</span>
									</div>
									<p class="testing-card-description">Проверка статуса автоматических задач и логов.</p>
									<button onclick="checkCronStatus()" class="testing-button testing-button-purple">
										<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
										Проверить Cron
									</button>
								</div>
							</div>

							<div class="testing-status-container">
								<h5 class="testing-status-title">Статус тестирования</h5>
								<div id="testing-status">Нажмите кнопку выше для тестирования.</div>
							</div>
						</div>

						<div class="content-section">
							<h4 class="section-title">Информация о настройках</h4>
							<div class="settings-info-grid">
								<div class="settings-info-section">
									<h6>Автоматические уведомления</h6>
									<ul class="settings-info-list">
										<li><span class="status-indicator green"></span>Таймер: проверяется каждую минуту</li>
										<li><span class="status-indicator green"></span>Ежедневный отчет: в настраиваемое время</li>
										<li><span class="status-indicator green"></span>Автоархив: через 6 часов после завершения</li>
									</ul>
								</div>
								<div class="settings-info-section">
									<h6>Текущие настройки</h6>
									<ul class="settings-info-list">
										<li><span class="status-indicator blue"></span>Время отчета: <span id="current-report-time">10:00</span></li>
										<li><span class="status-indicator blue"></span>Таймер: <span id="current-timer-minutes">1440</span> минут</li>
									</ul>
								</div>
							</div>
						</div>
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
				<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
				<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
				<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
					<input id='editTimer' type='checkbox' class="checkbox-input">
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
				<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
						<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
				<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
						<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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