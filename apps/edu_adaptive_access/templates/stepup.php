<?php
/** @var string $challenge_id */
/** @var string $resource_title */
/** @var string $action_label */
/** @var int $attempts_left */
/** @var int $remaining_seconds */
/** @var string $debug_code */
/** @var string $verify_url */
/** @var string $index_url */
/** @var string $error_message */
?>

<div class="ea-app-scroll">
    <div class="ea-wrap ea-wrap-stepup">
        <section class="ea-card ea-stepup-card">
            <h1>Дополнительное подтверждение</h1>
            <p class="ea-subtitle">
                Для действия <strong><?= htmlspecialchars($action_label) ?></strong>
                по ресурсу <strong><?= htmlspecialchars($resource_title) ?></strong>
                требуется второй шаг подтверждения.
            </p>

            <div class="ea-alert">
                <strong>MVP demo code:</strong> <?= htmlspecialchars($debug_code) ?>
            </div>

            <p class="ea-note">
                Код действует <?= htmlspecialchars((string)ceil($remaining_seconds / 60)) ?> мин.
                Осталось попыток: <?= htmlspecialchars((string)$attempts_left) ?>.
            </p>

            <?php if ($error_message !== ''): ?>
                <div class="ea-alert"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars($verify_url) ?>" class="ea-stepup-form">
                <label>Код подтверждения</label>
                <input
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="Введите код"
                    required
                />

                <div class="ea-actions">
                    <button type="submit">Подтвердить</button>
                    <a class="button" href="<?= htmlspecialchars($index_url) ?>">Отмена</a>
                </div>
            </form>
        </section>
    </div>
</div>