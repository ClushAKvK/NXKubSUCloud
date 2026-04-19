<?php
/** @var array|null $resource */
/** @var array|null $read_eval */
/** @var array|null $download_eval */
/** @var bool $supports_browser_read */
/** @var string $index_url */
/** @var string $message */
/** @var string|null $read_url */
/** @var string|null $download_url */
?>

<div class="ea-app-scroll">
<div class="ea-wrap">
    <a href="<?= htmlspecialchars($index_url) ?>" class="ea-back-link">← Назад к каталогу</a>

    <?php if ($message !== ''): ?>
        <div class="ea-alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($resource === null): ?>
        <section class="ea-card">
            <h1>Ресурс не найден</h1>
        </section>
    <?php else: ?>
        <section class="ea-card">
            <h1><?= htmlspecialchars($resource['title']) ?></h1>
            <div class="ea-meta">
                <span>Курс: <?= htmlspecialchars($resource['course_code']) ?></span>
                <span>Чувствительность: <?= htmlspecialchars($resource['sensitivity']) ?></span>
                <span>Владелец: <?= htmlspecialchars($resource['owner_uid']) ?></span>
                <span>Путь: <?= htmlspecialchars($resource['owner_path']) ?></span>
            </div>

            <p><?= nl2br(htmlspecialchars($resource['description'] ?? '')) ?></p>

<div class="ea-evals">
    <?php if (!empty($supports_browser_read)): ?>
        <div class="ea-eval">
            <strong>READ (PDF)</strong>
            <span class="ea-decision ea-<?= strtolower($read_eval['decision']) ?>">
                <?= htmlspecialchars($read_eval['decision']) ?>
            </span>
            <span>Risk: <?= htmlspecialchars((string)$read_eval['risk']) ?></span>
        </div>
    <?php endif; ?>

    <div class="ea-eval">
        <strong>DOWNLOAD</strong>
        <span class="ea-decision ea-<?= strtolower($download_eval['decision']) ?>">
            <?= htmlspecialchars($download_eval['decision']) ?>
        </span>
        <span>Risk: <?= htmlspecialchars((string)$download_eval['risk']) ?></span>
    </div>
</div>

            <h3>Объяснение решения</h3>
            <ul>
                <?php foreach ($download_eval['reasons'] as $reason): ?>
                    <li><?= htmlspecialchars($reason) ?></li>
                <?php endforeach; ?>
            </ul>

<div class="ea-actions">
    <?php if (!empty($supports_browser_read) && !empty($read_url)): ?>
        <a class="button button-primary"
           href="<?= htmlspecialchars($read_url) ?>"
           target="_blank"
           rel="noopener noreferrer">Читать в браузере</a>
    <?php endif; ?>

    <?php if (!empty($download_url)): ?>
        <a class="button" href="<?= htmlspecialchars($download_url) ?>">Скачать</a>
    <?php endif; ?>
</div>

        </section>
    <?php endif; ?>
</div>
</div>