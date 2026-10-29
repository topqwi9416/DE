<?php
/**
 * Общий шаблон. Страница задаёт:
 *   $pageTitle   — заголовок вкладки
 *   $breadcrumbs — массив [[название, ссылка|null], ...] (необязательно)
 *   $content     — HTML содержимого (ob_start / ob_get_clean)
 * Шапка, меню и flash-сообщения выводятся здесь, в страницах их дублировать не нужно.
 */
$pageTitle   = $pageTitle   ?? 'Корочки.есть';
$breadcrumbs = $breadcrumbs ?? [];
$flashes     = get_flash();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Корочки.есть</title>
    <link rel="stylesheet" href="<?= e(url('styles/style.css')) ?>">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a class="logo" href="<?= e(page_url('index.php')) ?>">
            <img src="<?= e(url('media/image02.jpg')) ?>" alt="Корочки.есть">
            <span>Корочки.есть</span>
        </a>

        <nav class="main-nav">
            <?php if (is_logged_in()): ?>
                <a href="<?= e(page_url('index.php')) ?>">Главная</a>
                <a href="<?= e(page_url('requests.php')) ?>">Мои заявки</a>
                <?php if (is_admin()): ?>
                    <a href="<?= e(page_url('admin.php')) ?>">Панель администратора</a>
                <?php endif; ?>
                <span class="nav-user"><?= e($_SESSION['user_fio'] ?? $_SESSION['user_login'] ?? '') ?></span>
                <form class="nav-logout" method="post" action="<?= e(page_url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-logout">Выход</button>
                </form>
            <?php else: ?>
                <a class="nav-btn" href="<?= e(page_url('register.php')) ?>">Регистрация</a>
                <a class="nav-btn nav-btn-primary" href="<?= e(page_url('auth.php')) ?>">Вход</a>
            <?php endif; ?>
        </nav>
    </div>

    <?php if ($breadcrumbs): ?>
        <div class="breadcrumbs">
            <?php foreach ($breadcrumbs as $i => [$title, $link]): ?>
                <?php if ($i > 0): ?><span>/</span><?php endif; ?>
                <?php if ($link): ?>
                    <a href="<?= e($link) ?>"><?= e($title) ?></a>
                <?php else: ?>
                    <?= e($title) ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>

<main>
    <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Корочки.есть — портал дополнительного профессионального образования</p>
</footer>

<script src="<?= e(url('scripts/script.js')) ?>"></script>
</body>
</html>
