<?php
/** @var array $resource */
/** @var string $stream_url */
/** @var string $index_url */
/** @var string $card_url */
/** @var string $message */
?>

<div class="ea-app-scroll">
    <div class="ea-wrap">
        <div class="ea-reader-header">
            <div>
                <h1><?= htmlspecialchars($resource['title'] ?? 'PDF viewer') ?></h1>
                <p class="ea-subtitle">
                    <?= htmlspecialchars($message) ?>
                </p>
            </div>

            <div class="ea-actions">
                <a class="button" href="<?= htmlspecialchars($card_url) ?>">К карточке</a>
                <a class="button" href="<?= htmlspecialchars($index_url) ?>">К каталогу</a>
            </div>
        </div>

        <div class="ea-reader-frame-wrap">
            <iframe
                class="ea-reader-frame"
                src="<?= htmlspecialchars($stream_url) ?>"
                title="PDF reader"
                referrerpolicy="same-origin">
            </iframe>
        </div>
    </div>
</div>