<?php
/** @var array $_ */
/** @var array $profile */
/** @var array $policy */
/** @var array $resources */
/** @var bool $is_admin */
/** @var string $save_profile_url */
/** @var string $save_global_url */
/** @var string $save_resource_url */
/** @var string $delete_resource_url */
?>

<div class="ea-app-scroll">
<div class="ea-wrap">
    <h1>Adaptive Access MVP</h1>
    <p class="ea-subtitle">
        Демонстрационный слой адаптивного доступа для образовательных ресурсов поверх Nextcloud.
    </p>

    <div class="ea-grid">
        <section class="ea-card">
            <h2>Мой профиль</h2>
            <div class="ea-badges">
                <span class="ea-badge"><?= htmlspecialchars($profile['display_name']) ?></span>
                <span class="ea-badge ea-role"><?= htmlspecialchars($profile['role']) ?></span>
            </div>

            <form method="post" action="<?= htmlspecialchars($save_profile_url) ?>">
                <label>Код курса / дисциплины</label>
                <input type="text" name="course_code" value="<?= htmlspecialchars($profile['course_code']) ?>" placeholder="Например: ML-2026"/>

                <label>Кафедра / подразделение</label>
                <input type="text" name="department" value="<?= htmlspecialchars($profile['department']) ?>" placeholder="Например: ИВТ"/>

                <label class="ea-checkbox">
                    <input type="checkbox" name="managed_device" <?= $profile['managed_device'] ? 'checked' : '' ?>/>
                    Устройство считается управляемым
                </label>

                <button type="submit">Сохранить профиль</button>
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
            Сначала загрузите файл в обычный Nextcloud Files, затем здесь укажите путь относительно корня ваших файлов,
            например: <code>/Учебные/ML/lecture1.pdf</code>
        </p>

        <form method="post" action="<?= htmlspecialchars($save_resource_url) ?>" class="ea-resource-form">
            <div class="ea-two-cols">
                <div>
                    <label>Название</label>
                    <input type="text" name="title" required placeholder="Лекция 1"/>

                    <label>Путь к файлу</label>
                    <input type="text" name="owner_path" required placeholder="/Учебные/ML/lecture1.pdf"/>

                    <label>Курс / дисциплина</label>
                    <input type="text" name="course_code" required placeholder="ML-2026"/>
                </div>

                <div>
                    <label>Класс чувствительности</label>
                    <select name="sensitivity">
                        <option value="public">public</option>
                        <option value="learning" selected>learning</option>
                        <option value="personal">personal</option>
                        <option value="exam">exam</option>
                    </select>

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

            <button type="submit">Зарегистрировать ресурс</button>
        </form>
    </section>

    <section class="ea-card">
        <h2>Каталог ресурсов</h2>

        <?php if (count($resources) === 0): ?>
            <p>Пока нет зарегистрированных ресурсов.</p>
        <?php endif; ?>

        <div class="ea-list">
            <?php foreach ($resources as $resource): ?>
                <article class="ea-item">
                    <div class="ea-item-top">
                        <div>
                            <h3><?= htmlspecialchars($resource['title']) ?></h3>
                            <div class="ea-meta">
                                <span>Курс: <?= htmlspecialchars($resource['course_code']) ?></span>
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
            <strong>READ (PDF)</strong>
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