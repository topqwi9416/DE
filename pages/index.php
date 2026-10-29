<?php
require_once __DIR__ . '/../helpers/db.php';

$pageTitle = 'Главная';

// Только фото аудиторий (логотипы и иконки в слайдер не попадают).
// image07_web.jpg — облегчённая копия image07.jpg (4000×2667, 5,4 МБ).
$slideFiles = ['image06.jpg', 'image07_web.jpg', 'image08.webp', 'image09.webp',
               'image10.webp', 'image11.jpg', 'image12.webp', 'image13.webp'];
$slides = array_values(array_filter($slideFiles, fn($f) => is_file(__DIR__ . '/../media/' . $f)));

ob_start();
?>
<?php if ($slides): ?>
<section class="slider-section">
    <div class="slider-container" id="slider">
        <div class="slider-wrapper">
            <?php foreach ($slides as $i => $file): ?>
                <div class="slide<?= $i === 0 ? ' active' : '' ?>">
                    <img src="<?= e(url('media/' . $file)) ?>" alt="Учебная аудитория <?= $i + 1 ?>"<?= $i > 0 ? ' loading="lazy"' : '' ?>>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="slider-btn slider-btn-prev" aria-label="Назад">&#10094;</button>
        <button type="button" class="slider-btn slider-btn-next" aria-label="Вперёд">&#10095;</button>
        <div class="slider-dots">
            <?php foreach ($slides as $i => $file): ?>
                <span class="dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>"></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="hero">
    <h1 class="hero-title">Портал «Корочки.есть»</h1>
    <p class="hero-description">
        Информационная система для записи на онлайн-курсы дополнительного
        профессионального образования. Выберите курс, подайте заявку и следите
        за её статусом в личном кабинете.
    </p>
    <div class="hero-actions">
        <?php if (is_logged_in()): ?>
            <a class="btn btn-primary" href="<?= e(page_url('request_form.php')) ?>">Подать заявку</a>
            <a class="btn btn-secondary" href="<?= e(page_url('requests.php')) ?>">Мои заявки</a>
            <?php if (is_admin()): ?>
                <a class="btn btn-secondary" href="<?= e(page_url('admin.php')) ?>">Панель администратора</a>
            <?php endif; ?>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(page_url('register.php')) ?>">Регистрация</a>
            <a class="btn btn-secondary" href="<?= e(page_url('auth.php')) ?>">Вход</a>
        <?php endif; ?>
    </div>
</section>

<section class="features">
    <h3>Почему выбирают нас</h3>
    <div class="features-grid">
        <div class="feature-card">
            <h4>Быстро</h4>
            <p>Заявка оформляется за пару минут, без лишних документов.</p>
        </div>
        <div class="feature-card">
            <h4>Удобно</h4>
            <p>Все ваши заявки и их статусы собраны в одном личном кабинете.</p>
        </div>
        <div class="feature-card">
            <h4>Прозрачно</h4>
            <p>Видно, на каком этапе заявка, а после обучения можно оставить отзыв.</p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
