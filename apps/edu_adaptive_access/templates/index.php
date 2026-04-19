<?php
/** @var array $_ */
/** @var array $profile */
/** @var array $policy */
/** @var array $resources */
/** @var array $selectable_files */
/** @var array $direction_options */
/** @var array $discipline_options */
/** @var string $academic_catalog_json */
/** @var bool $is_admin */
/** @var bool $can_register_resource */
/** @var string $save_profile_url */
/** @var string $save_global_url */
/** @var string $save_resource_url */
/** @var string $delete_resource_url */
?>

<div id="ea-catalog-data" data-catalog="<?= htmlspecialchars($academic_catalog_json, ENT_QUOTES, 'UTF-8') ?>"></div>

<div class="ea-app-scroll">
    <div class="ea-wrap">
        <h1>Adaptive Access MVP</h1>
        <p class="ea-subtitle">
            Демонстрационный слой адаптивного доступа для образовательных ресурсов поверх Nextcloud.
        </p>

        <div class="ea-grid">
            <section class="ea-card">
                <h2>Активный учебный контекст</h2>
                <div class="ea-badges">
                    <span class="ea-badge"><?= htmlspecialchars($profile['display_name']) ?></span>
                    <span class="ea-badge ea-role"><?= htmlspecialchars($profile['role']) ?></span>
                </div>

                <form method="post" action="<?= htmlspecialchars($save_profile_url) ?>">
                    <label>Направление</label>
                    <select name="direction_code" id="ea-direction-select">
                        <option value="">Выберите направление</option>
                        <?php foreach ($direction_options as $direction): ?>
                            <option
                                value="<?= htmlspecialchars($direction['code']) ?>"
                                <?= $profile['direction_code'] === $direction['code'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($direction['code'] . ' — ' . $direction['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Дисциплина</label>
                    <select
                        name="discipline_name"
                        id="ea-discipline-select"
                        data-selected="<?= htmlspecialchars($profile['discipline_name']) ?>"
                    >
                        <option value="">Выберите дисциплину</option>
                        <?php foreach ($discipline_options as $discipline): ?>
                            <option
                                value="<?= htmlspecialchars($discipline) ?>"
                                <?= $profile['discipline_name'] === $discipline ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($discipline) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Или добавить новую дисциплину</label>
                    <input
                        type="text"
                        name="new_discipline"
                        id="ea-new-discipline"
                        placeholder="Например: Методика преподавания ИКТ"
                    />

                    <label class="ea-checkbox">
                        <input type="checkbox" name="managed_device" <?= $profile['managed_device'] ? 'checked' : '' ?>/>
                        Устройство считается управляемым
                    </label>

                    <button type="submit">Сохранить контекст</button>
                </form>
            </section>

            <?php if ($is_admin): ?>
                <section class="ea-card">
                    <h2>Глобальная политика</h2>
                    <form method="post" action="<?= htmlspecialchars($save_global_url) ?>">
                        <label>Режим</label>
                        <select name="mode">
                            <option value="normal" <?= $policy['mode'] === 'normal' ? 'selected' : '' ?>>normal</option>
                            <option value="deadline" <?= $policy['mode'] === 'deadline' ? 'selected' : '' ?>>deadline</option>
                            <option value="exam" <?= $policy['mode'] === 'exam' ? 'selected' : '' ?>>exam</option>
                        </select>

                        <label>Порог tau1</label>
                        <input type="number" step="0.01" min="0" max="1" name="tau1" value="<?= htmlspecialchars((string)$policy['tau1']) ?>"/>

                        <label>Порог tau2</label>
                        <input type="number" step="0.01" min="0" max="1" name="tau2" value="<?= htmlspecialchars((string)$policy['tau2']) ?>"/>

                        <label>Trusted CIDRs</label>
                        <textarea name="trusted_cidrs" rows="5"><?= htmlspecialchars($policy['trusted_cidrs']) ?></textarea>

                        <label>Группа преподавателей</label>
                        <input type="text" name="teachers_group" value="<?= htmlspecialchars($policy['teachers_group']) ?>"/>

                        <label>Группа студентов</label>
                        <input type="text" name="students_group" value="<?= htmlspecialchars($policy['students_group']) ?>"/>

                        <button type="submit">Сохранить политику</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <section class="ea-card">
            <h2>Регистрация учебного ресурса</h2>
            <p class="ea-note">
                Сначала загрузите файл в обычное хранилище Nextcloud Files.
                Затем выберите его из списка ниже — путь подставится автоматически.
            </p>

            <div class="ea-context-summary">
                <div class="ea-context-summary-item">
                    <span>Текущее направление</span>
                    <strong>
                        <?= $profile['direction_code'] !== ''
                            ? htmlspecialchars($profile['direction_code'] . ' — ' . $profile['direction_title'])
                            : 'Не выбрано' ?>
                    </strong>
                </div>

                <div class="ea-context-summary-item">
                    <span>Текущая дисциплина</span>
                    <strong><?= $profile['discipline_name'] !== '' ? htmlspecialchars($profile['discipline_name']) : 'Не выбрана' ?></strong>
                </div>
            </div>

            <?php if (!$can_register_resource): ?>
                <div class="ea-alert">
                    Перед регистрацией ресурса выберите в активном учебном контексте направление и дисциплину.
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars($save_resource_url) ?>" class="ea-resource-form">
                <input type="hidden" name="owner_path" id="ea-owner-path" required />

                <label>Название</label>
                <input
                    type="text"
                    name="title"
                    id="ea-resource-title"
                    required
                    placeholder="Название ресурса"
                />

                <label>Файл из основного хранилища</label>

                <div class="ea-file-picker">
                    <input
                        type="text"
                        id="ea-file-filter"
                        class="ea-file-filter"
                        placeholder="Фильтр по имени или пути"
                        autocomplete="off"
                    />

                    <select id="ea-file-select" class="ea-file-select" size="16">
                        <?php foreach ($selectable_files as $file): ?>
                            <option
                                value="<?= htmlspecialchars($file['path']) ?>"
                                data-path="<?= htmlspecialchars($file['path']) ?>"
                                data-name="<?= htmlspecialchars($file['name']) ?>"
                                data-ext="<?= htmlspecialchars($file['extension']) ?>"
                            >
                                <?= htmlspecialchars($file['path']) ?>
                                <?php if (!empty($file['browser_readable'])): ?> [PDF / browser-read]<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="ea-file-selected">
                        <div class="ea-selected-row">
                            <div class="ea-selected-label">Автоподстановка названия</div>
                            <div class="ea-selected-value" id="ea-resource-title-preview">Файл не выбран</div>
                        </div>

                        <div class="ea-selected-row">
                            <div class="ea-selected-label">Имя файла</div>
                            <div class="ea-selected-value" id="ea-file-name-preview">—</div>
                        </div>

                        <div class="ea-selected-row">
                            <div class="ea-selected-label">Полный путь</div>
                            <div class="ea-selected-value" id="ea-file-selected-text">Файл не выбран</div>
                        </div>
                    </div>
                </div>

                <div class="ea-two-cols">
                    <div>
                        <label>Класс чувствительности</label>
                        <select name="sensitivity">
                            <option value="public">public</option>
                            <option value="learning" selected>learning</option>
                            <option value="personal">personal</option>
                            <option value="exam">exam</option>
                        </select>
                    </div>

                    <div>
                        <label>Открыть не раньше</label>
                        <input type="datetime-local" name="open_from"/>

                        <label>Дедлайн / срок действия</label>
                        <input type="datetime-local" name="due_until"/>
                    </div>
                </div>

                <label>Описание</label>
                <textarea name="description" rows="3" placeholder="Короткое описание ресурса"></textarea>

                <label class="ea-checkbox">
                    <input type="checkbox" name="allow_student_download"/>
                    Разрешить студенту скачивание
                </label>

                <button type="submit" <?= !$can_register_resource ? 'disabled' : '' ?>>Зарегистрировать ресурс</button>
            </form>
        </section>

        <section class="ea-card">
            <h2>Каталог ресурсов</h2>
            <p class="ea-note">
                Отображаются ресурсы текущего учебного контекста.
            </p>

            <?php if (count($resources) === 0): ?>
                <p>Для текущего контекста пока нет зарегистрированных ресурсов.</p>
            <?php endif; ?>

            <div class="ea-list">
                <?php foreach ($resources as $resource): ?>
                    <article class="ea-item">
                        <div class="ea-item-top">
                            <div>
                                <h3><?= htmlspecialchars($resource['title']) ?></h3>
                                <div class="ea-meta">
                                    <?php if (!empty($resource['direction_code'])): ?>
                                        <span><?= htmlspecialchars($resource['direction_code']) ?></span>
                                    <?php endif; ?>

                                    <?php if (!empty($resource['discipline_name'])): ?>
                                        <span><?= htmlspecialchars($resource['discipline_name']) ?></span>
                                    <?php endif; ?>

                                    <span>Чувствительность: <?= htmlspecialchars($resource['sensitivity']) ?></span>
                                    <span>Владелец: <?= htmlspecialchars($resource['owner_uid']) ?></span>
                                </div>
                            </div>

                            <div class="ea-actions">
                                <a class="button" href="<?= htmlspecialchars($resource['view_url']) ?>">Открыть карточку</a>

                                <?php if (!empty($resource['supports_browser_read'])): ?>
                                    <a class="button button-primary"
                                       href="<?= htmlspecialchars($resource['read_url']) ?>"
                                       target="_blank"
                                       rel="noopener noreferrer">Читать в браузере</a>
                                <?php endif; ?>

                                <a class="button" href="<?= htmlspecialchars($resource['download_url']) ?>">Скачать</a>
                            </div>
                        </div>

                        <p><?= nl2br(htmlspecialchars($resource['description'] ?? '')) ?></p>

                        <div class="ea-evals">
                            <?php if (!empty($resource['supports_browser_read'])): ?>
                                <div class="ea-eval">
                                    <strong>VIEW (PDF)</strong>
                                    <span class="ea-decision ea-<?= strtolower($resource['read_eval']['decision']) ?>">
                                        <?= htmlspecialchars($resource['read_eval']['decision']) ?>
                                    </span>
                                    <span>Risk: <?= htmlspecialchars((string)$resource['read_eval']['risk']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="ea-eval">
                                <strong>DOWNLOAD</strong>
                                <span class="ea-decision ea-<?= strtolower($resource['download_eval']['decision']) ?>">
                                    <?= htmlspecialchars($resource['download_eval']['decision']) ?>
                                </span>
                                <span>Risk: <?= htmlspecialchars((string)$resource['download_eval']['risk']) ?></span>
                            </div>
                        </div>

                        <details>
                            <summary>Пояснение решения</summary>
                            <ul>
                                <?php foreach ($resource['download_eval']['reasons'] as $reason): ?>
                                    <li><?= htmlspecialchars($reason) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>

                        <?php if ($profile['uid'] === $resource['owner_uid'] || $is_admin): ?>
                            <form method="post" action="<?= htmlspecialchars($delete_resource_url) ?>" class="ea-inline-form">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($resource['id']) ?>"/>
                                <button type="submit" class="ea-danger">Удалить</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>