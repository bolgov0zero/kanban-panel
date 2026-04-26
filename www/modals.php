<?php
// Модальные окна. Функции управления — в script.js
?>

<!-- Main Modal Backdrop -->
<div id="modal-bg" class="modal-backdrop hidden" onclick="if(event.target===this)closeModal()">
	<div id="modal-content" style="display:contents"></div>
</div>

<!-- Link Picker -->
<div id="link-picker" class="modal-backdrop hidden" onclick="if(event.target===this)closeLinkPicker()" style="z-index:200">
	<div class="link-picker-container">
		<div class="link-picker-header">
			<span class="link-picker-title">Быстрые ссылки</span>
			<button onclick="closeLinkPicker()" class="link-picker-close">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
			</button>
		</div>
		<div id="links-list" class="links-list"></div>
		<?php if ($isAdmin): ?>
		<div class="link-picker-form">
			<input id="linkName" placeholder="Название" class="input">
			<input id="linkUrl" placeholder="https://..." class="input">
			<button onclick="saveLink()" class="link-add-btn">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
				Добавить
			</button>
		</div>
		<?php endif; ?>
	</div>
</div>

<!-- Archive Modal Template -->
<div id="archive-modal-template" style="display:none">
<div class="modal modal-md">
	<div class="modal-header">
		<span class="modal-title">Архив задач</span>
		<button onclick="closeModal()" class="icon-btn icon-btn-sm">
			<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<div class="modal-body">
		<div class="archive-list"><!-- Archive items will be inserted here --></div>
	</div>
	<div class="modal-footer">
		<button onclick="closeModal()" class="btn">Закрыть</button>
	</div>
</div>
</div>

<!-- Settings Modal Template -->
<div id="settings-modal-template" style="display:none">
<div class="modal modal-lg">
	<div class="modal-header">
		<span class="modal-title">Настройки</span>
		<button onclick="closeModal()" class="icon-btn icon-btn-sm">
			<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<div class="modal-body" style="padding:0;overflow:hidden;">
		<div class="settings-layout">
			<!-- Nav -->
			<div class="settings-nav">
				<div class="settings-nav-label">Раздел</div>
				<button data-tab="users" class="settings-nav-item active">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
					Пользователи
				</button>
				<button data-tab="integrations" class="settings-nav-item">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 1 0-7.07-7.07l-1.5 1.5M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 1 0 7.07 7.07l1.5-1.5"/></svg>
					Интеграции
				</button>
				<button data-tab="system" class="settings-nav-item">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
					Система
				</button>
				<button data-tab="testing" class="settings-nav-item">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6M10 3v6L4.5 18.5A2 2 0 0 0 6.2 21h11.6a2 2 0 0 0 1.7-3l-5.5-9V3"/></svg>
					Тестирование
				</button>
				<div class="settings-status">
					<div class="status-pill">
						<span class="dot"></span>
						Система активна
					</div>
				</div>
			</div>

			<!-- Pane -->
			<div class="settings-pane">

				<!-- Пользователи -->
				<div id="users-tab" class="tab-content active">
					<h2>Пользователи</h2>
					<p class="pane-sub">Управление учётными записями системы</p>

					<div class="section-card">
						<h3>Новый пользователь</h3>
						<div class="form-grid">
							<div>
								<label class="field-label">Логин <span class="req">*</span></label>
								<input id="newUser" placeholder="Уникальный идентификатор" class="input">
							</div>
							<div>
								<label class="field-label">Пароль <span class="req">*</span></label>
								<input id="newPass" type="password" placeholder="Минимум 6 символов" class="input">
							</div>
							<div>
								<label class="field-label">Полное имя</label>
								<input id="newName" placeholder="Иван Иванов" class="input">
							</div>
							<div style="display:flex;align-items:flex-end;">
								<label class="checkbox">
									<input id="newIsAdmin" type="checkbox">
									<span class="box"></span>
									Администратор
								</label>
							</div>
						</div>
						<button onclick="addUser()" class="btn btn-primary" style="width:100%;margin-top:4px;">
							<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
							Создать пользователя
						</button>
					</div>

					<div class="section-card">
						<h3 style="display:flex;justify-content:space-between;">
							<span>Активные пользователи</span>
							<span class="section-meta" id="users-count">0 пользователей</span>
						</h3>
						<div id="users-list"></div>
					</div>
				</div>

				<!-- Интеграции -->
				<div id="integrations-tab" class="tab-content">
					<h2>Интеграции</h2>
					<p class="pane-sub">Уведомления и внешние сервисы</p>

					<div class="section-card">
						<h3>Общие настройки уведомлений</h3>
						<p class="field-help" style="margin-bottom:12px;">Применяются ко всем включённым каналам: Telegram, Email.</p>
						<div class="form-grid">
							<div>
								<label class="field-label">Время ежедневного отчёта (МСК)</label>
								<input id="dailyReportTime" type="time" value="10:00" class="input input-mono">
								<span class="field-help">Отчёт по открытым задачам отправляется каждый день в это время</span>
							</div>
							<div>
								<label class="field-label">Интервал уведомления таймера (мин)</label>
								<input id="timerNotificationMinutes" type="number" min="1" max="43200" value="1440" class="input input-mono">
								<span class="field-help">Через сколько минут уведомлять о задаче в колонке с таймером</span>
							</div>
						</div>
						<div class="action-buttons">
							<button onclick="saveNotificationGlobals()" class="btn btn-primary">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2ZM17 21v-8H7v8M7 3v5h8"/></svg>
								Сохранить
							</button>
						</div>
					</div>

					<div class="section-card">
						<h3>Telegram уведомления</h3>
						<div style="margin-bottom:12px;">
							<label class="checkbox">
								<input type="checkbox" id="tgEnabled">
								<span class="box"></span>
								Включить уведомления в Telegram
							</label>
						</div>
						<div class="form-grid">
							<div>
								<label class="field-label">Токен бота</label>
								<input id="tgToken" placeholder="1234567890:ABCdef..." class="input input-mono">
								<span class="field-help">Получите у @BotFather</span>
							</div>
							<div>
								<label class="field-label">Chat ID</label>
								<input id="tgChat" placeholder="123456789" class="input input-mono">
								<span class="field-help">ID чата для отправки</span>
							</div>
						</div>
						<div class="action-buttons">
							<button onclick="saveTelegram()" class="btn btn-primary">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2ZM17 21v-8H7v8M7 3v5h8"/></svg>
								Сохранить
							</button>
							<button onclick="testTelegram()" class="btn">Тест</button>
						</div>
					</div>

					<div class="section-card">
						<h3>Email уведомления</h3>
						<div style="margin-bottom:12px;">
							<label class="checkbox">
								<input type="checkbox" id="emailEnabled">
								<span class="box"></span>
								Включить уведомления на Email
							</label>
						</div>
						<div class="form-grid">
							<div>
								<label class="field-label">SMTP хост</label>
								<input id="emailHost" placeholder="smtp.gmail.com" class="input">
							</div>
							<div>
								<label class="field-label">Порт</label>
								<input id="emailPort" type="number" placeholder="587" value="587" class="input input-mono">
							</div>
						</div>
						<div class="form-grid">
							<div>
								<label class="field-label">Шифрование</label>
								<select id="emailEncryption" class="select">
									<option value="tls">TLS (STARTTLS)</option>
									<option value="ssl">SSL</option>
									<option value="none">Нет</option>
								</select>
							</div>
							<div>
								<label class="field-label">Логин</label>
								<input id="emailUsername" placeholder="user@example.com" class="input">
							</div>
						</div>
						<div class="form-grid">
							<div>
								<label class="field-label">Пароль</label>
								<input id="emailPassword" type="password" placeholder="••••••••" class="input">
							</div>
							<div>
								<label class="field-label">Адрес отправителя</label>
								<input id="emailFromEmail" placeholder="kanban@example.com" class="input">
							</div>
						</div>
						<div class="form-grid">
							<div>
								<label class="field-label">Имя отправителя</label>
								<input id="emailFromName" placeholder="Kanban" value="Kanban" class="input">
							</div>
							<div>
								<label class="field-label">Получатель</label>
								<input id="emailToEmail" placeholder="admin@example.com" class="input">
							</div>
						</div>
						<div class="action-buttons">
							<button onclick="saveEmail()" class="btn btn-primary">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2ZM17 21v-8H7v8M7 3v5h8"/></svg>
								Сохранить
							</button>
							<button onclick="testEmail()" class="btn">Тест</button>
						</div>
					</div>

					<div class="section-card">
						<h3 style="display:flex;justify-content:space-between;">
							<span>Быстрые ссылки</span>
							<span class="section-meta" id="links-count">0 ссылок</span>
						</h3>
						<div class="form-grid form-grid-1">
							<div>
								<label class="field-label">Название</label>
								<input id="newLinkName" placeholder="Документация" class="input">
							</div>
							<div>
								<label class="field-label">URL</label>
								<input id="newLinkUrl" placeholder="https://example.com" class="input">
							</div>
						</div>
						<button onclick="adminAddLink()" class="btn btn-primary" style="width:100%;">
							<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
							Добавить ссылку
						</button>
						<div id="admin-links-list" style="margin-top:12px;"></div>
					</div>
				</div>

				<!-- Система -->
				<div id="system-tab" class="tab-content">
					<h2>Система</h2>
					<p class="pane-sub">Информация о сервере и управление данными</p>

					<div class="section-card">
						<h3>Информация о сервере</h3>
						<div class="info-row">
							<span class="k">Версия PHP</span>
							<span class="v"><?php echo phpversion(); ?></span>
						</div>
						<div class="info-row">
							<span class="k">База данных</span>
							<span class="v ok">SQLite3 <?php echo class_exists('SQLite3') ? '✓' : '✗'; ?></span>
						</div>
						<div class="info-row">
							<span class="k">Время сервера</span>
							<span class="v"><?php echo date('d.m.Y H:i:s'); ?></span>
						</div>
						<div class="info-row">
							<span class="k">Cron</span>
							<span class="v" id="cron-status">Проверка...</span>
						</div>
					</div>

					<div class="section-card">
						<h3>Управление данными</h3>
						<div class="warn-card">
							<div class="warn-card-text">
								<strong>Очистка архива</strong>
								<span>Безвозвратное удаление всех задач из архива</span>
							</div>
							<button onclick="clearArchive()" class="btn btn-danger">Очистить архив</button>
						</div>
					</div>
				</div>

				<!-- Тестирование -->
				<div id="testing-tab" class="tab-content">
					<h2>Тестирование</h2>
					<p class="pane-sub">Проверка автоматических уведомлений</p>

					<div class="section-card">
						<h3>Уведомления</h3>

						<div class="test-card">
							<div class="test-card-head">
								<div class="test-card-icon">
									<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 6v6l4 2"/></svg>
								</div>
								<span class="test-card-title">Уведомление таймера</span>
							</div>
							<p class="test-card-body">Тест уведомления о задаче, которая находится в колонке заданное время.</p>
							<button onclick="testTimerNotification()" class="btn btn-warning test-card-btn">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 6v6l4 2"/></svg>
								Тест таймера
							</button>
						</div>

						<div class="test-card">
							<div class="test-card-head">
								<div class="test-card-icon green">
									<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8ZM14 2v6h6M9 13h6M9 17h6"/></svg>
								</div>
								<span class="test-card-title">Ежедневный отчёт</span>
							</div>
							<p class="test-card-body">Тест ежедневного отчёта по открытым задачам.</p>
							<button onclick="testDailyReport()" class="btn btn-success test-card-btn">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8ZM14 2v6h6M9 13h6M9 17h6"/></svg>
								Тест отчёта
							</button>
						</div>

						<div class="test-card">
							<div class="test-card-head">
								<div class="test-card-icon purple">
									<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
								</div>
								<span class="test-card-title">Статус Cron</span>
							</div>
							<p class="test-card-body">Проверка доступности скрипта автозадач.</p>
							<button onclick="checkCronStatus()" class="btn test-card-btn">
								<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
								Проверить Cron
							</button>
						</div>

						<div class="testing-status-box">
							<div id="testing-status">Нажмите кнопку выше для тестирования.</div>
						</div>
					</div>

					<div class="section-card">
						<h3>Текущие настройки</h3>
						<div class="settings-info-grid">
							<div class="settings-info-section">
								<h6>Автоматика</h6>
								<ul class="settings-info-list">
									<li>Таймер: каждую минуту</li>
									<li>Отчёт: в настраиваемое время</li>
									<li>Автоархив: через 6 часов</li>
								</ul>
							</div>
							<div class="settings-info-section">
								<h6>Настройки</h6>
								<ul class="settings-info-list">
									<li>Время отчёта: <span id="current-report-time">10:00</span></li>
									<li>Таймер: <span id="current-timer-minutes">1440</span> мин</li>
								</ul>
							</div>
						</div>
					</div>
				</div>

			</div><!-- .settings-pane -->
		</div><!-- .settings-layout -->
	</div>
</div>
</div>

<!-- Edit User Modal Template -->
<div id="edit-user-modal-template" style="display:none">
<div class="modal modal-sm">
	<div class="modal-header">
		<span class="modal-title">Редактировать пользователя</span>
		<button onclick="closeModal()" class="icon-btn icon-btn-sm">
			<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<div class="modal-body">
		<div style="margin-bottom:14px;">
			<label class="field-label">Логин</label>
			<input id="editUser" class="input" readonly>
		</div>
		<div style="margin-bottom:14px;">
			<label class="field-label">Имя</label>
			<input id="editName" class="input" placeholder="Полное имя">
		</div>
		<div style="margin-bottom:14px;">
			<label class="field-label">Новый пароль</label>
			<input id="editPass" type="password" class="input" placeholder="Оставьте пустым, чтобы не менять">
		</div>
		<label class="checkbox">
			<input id="editIsAdmin" type="checkbox">
			<span class="box"></span>
			Администратор
		</label>
	</div>
	<div class="modal-footer">
		<button onclick="closeModal()" class="btn">Отмена</button>
		<button onclick="updateUser()" class="btn btn-primary">Сохранить</button>
	</div>
</div>
</div>

<!-- Add Column Modal Template -->
<div id="add-column-modal-template" style="display:none">
<div class="modal modal-sm">
	<div class="modal-header">
		<span class="modal-title">Новая колонка</span>
		<button onclick="closeModal()" class="icon-btn icon-btn-sm">
			<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<div class="modal-body">
		<div style="margin-bottom:14px;">
			<label class="field-label">Название</label>
			<input id="colName" placeholder="Например: В работе" class="input">
		</div>
		<div style="margin-bottom:14px;">
			<label class="field-label">Цвет</label>
			<div class="color-pick">
				<input id="colBg" type="color" value="#374151" class="color-swatch">
				<span class="color-hex-val" id="colBgValue">#374151</span>
			</div>
		</div>
		<div style="margin-bottom:10px;">
			<label class="checkbox">
				<input id="autoComplete" type="checkbox">
				<span class="box"></span>
				Автоматически завершать задачи
			</label>
		</div>
		<div>
			<label class="checkbox">
				<input id="timer" type="checkbox">
				<span class="box"></span>
				Включить таймер для задач
			</label>
		</div>
	</div>
	<div class="modal-footer">
		<button onclick="closeModal()" class="btn">Отмена</button>
		<button onclick="saveColumn()" class="btn btn-primary">Создать</button>
	</div>
</div>
</div>

<!-- Edit Column Modal Template -->
<div id="edit-column-modal-template" style="display:none">
<div class="modal modal-sm">
	<div class="modal-header">
		<span class="modal-title">Редактировать колонку</span>
		<button onclick="closeModal()" class="icon-btn icon-btn-sm">
			<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<div class="modal-body">
		<div style="margin-bottom:14px;">
			<label class="field-label">Название</label>
			<input id="editColName" class="input">
		</div>
		<div style="margin-bottom:14px;">
			<label class="field-label">Цвет</label>
			<div class="color-pick">
				<input id="editColBg" type="color" class="color-swatch">
				<span class="color-hex-val" id="editColBgValue">#374151</span>
			</div>
		</div>
		<div style="margin-bottom:10px;">
			<label class="checkbox">
				<input id="editAutoComplete" type="checkbox">
				<span class="box"></span>
				Автоматически завершать задачи
			</label>
		</div>
		<div>
			<label class="checkbox">
				<input id="editTimer" type="checkbox">
				<span class="box"></span>
				Включить таймер для задач
			</label>
		</div>
	</div>
	<div class="modal-footer between">
		<button onclick="deleteColumn()" class="btn btn-danger">Удалить</button>
		<div style="display:flex;gap:8px;">
			<button onclick="closeModal()" class="btn">Отмена</button>
			<button onclick="updateColumn()" class="btn btn-primary">Сохранить</button>
		</div>
	</div>
</div>
</div>

<!-- Add Task Modal Template -->
<div id="add-task-modal-template" style="display:none">
<div class="modal modal-md">
	<div class="modal-header">
		<span class="modal-title">Новая задача</span>
		<button onclick="closeModal()" class="icon-btn icon-btn-sm">
			<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<div class="modal-body">
		<div style="margin-bottom:14px;">
			<label class="field-label">Заголовок <span class="req">*</span></label>
			<input id="taskTitle" placeholder="Например: Подготовить отчёт" class="input">
		</div>
		<div style="margin-bottom:14px;">
			<label class="field-label">Описание</label>
			<div style="position:relative;">
				<textarea id="taskDesc" placeholder="Описание задачи..." class="textarea"></textarea>
				<button type="button" onclick="openLinkPicker()" class="link-picker-btn icon-btn icon-btn-sm" title="Добавить ссылку" style="position:absolute;top:8px;right:8px;">
					<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 1 0-7.07-7.07l-1.5 1.5M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 1 0 7.07 7.07l1.5-1.5"/></svg>
				</button>
			</div>
		</div>
		<div class="form-grid">
			<div>
				<label class="field-label">Исполнитель</label>
				<select id="taskResp" class="select"></select>
			</div>
			<div>
				<label class="field-label">Срок выполнения</label>
				<input id="taskDeadline" type="date" class="input input-mono">
			</div>
		</div>
		<div class="form-grid">
			<div>
				<label class="field-label">Приоритет</label>
				<select id="taskImp" class="select">
					<option value="не срочно">🟢 Не срочно</option>
					<option value="средне">🟡 Средне</option>
					<option value="срочно">🔴 Срочно</option>
				</select>
			</div>
			<div>
				<label class="field-label">Колонка</label>
				<select id="taskCol" class="select"></select>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button onclick="closeModal()" class="btn">Отмена</button>
		<button onclick="saveTask()" class="btn btn-primary">Создать задачу</button>
	</div>
</div>
</div>

<!-- Edit Task Modal Template -->
<div id="edit-task-modal-template" style="display:none">
<div class="modal modal-md">
	<div class="modal-header">
		<span class="modal-title">Редактировать задачу</span>
		<button onclick="closeModal()" class="icon-btn icon-btn-sm">
			<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<div class="modal-body">
		<div style="margin-bottom:14px;">
			<label class="field-label">Заголовок <span class="req">*</span></label>
			<input id="editTaskTitle" class="input">
		</div>
		<div style="margin-bottom:14px;">
			<label class="field-label">Описание</label>
			<div style="position:relative;">
				<textarea id="editTaskDesc" class="textarea"></textarea>
				<button type="button" onclick="openLinkPicker()" class="link-picker-btn icon-btn icon-btn-sm" title="Добавить ссылку" style="position:absolute;top:8px;right:8px;">
					<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 1 0-7.07-7.07l-1.5 1.5M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 1 0 7.07 7.07l1.5-1.5"/></svg>
				</button>
			</div>
		</div>
		<div class="form-grid">
			<div>
				<label class="field-label">Исполнитель</label>
				<select id="editTaskResp" class="select"></select>
			</div>
			<div>
				<label class="field-label">Срок выполнения</label>
				<input id="editTaskDeadline" type="date" class="input input-mono">
			</div>
		</div>
		<div class="form-grid">
			<div>
				<label class="field-label">Приоритет</label>
				<select id="editTaskImp" class="select">
					<option value="не срочно">🟢 Не срочно</option>
					<option value="средне">🟡 Средне</option>
					<option value="срочно">🔴 Срочно</option>
				</select>
			</div>
			<div>
				<label class="field-label">Колонка</label>
				<select id="editTaskCol" class="select"></select>
			</div>
		</div>
	</div>
	<div class="modal-footer between">
		<button onclick="deleteTask()" class="btn btn-danger">Удалить</button>
		<div style="display:flex;gap:8px;">
			<button onclick="closeModal()" class="btn">Отмена</button>
			<button onclick="updateTask()" class="btn btn-primary">Сохранить</button>
		</div>
	</div>
</div>
</div>
