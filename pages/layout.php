<?php
// Ожидаемые переменные из подключающего файла:
// $pageTitle      — заголовок страницы (для <title>)
// $breadcrumbs    — массив хлебных крошек [['title' => '...', 'url' => '...'], ...]
// $navButtons     — массив кнопок навигации [['title' => '...', 'url' => '...', 'class' => '...', 'is_text' => true/false], ...]
// $content        — основной HTML-контент страницы

$pageTitle   = $pageTitle   ?? 'Корочки.Есть';
$breadcrumbs = $breadcrumbs ?? [];
$navButtons  = $navButtons  ?? [];
$content     = $content     ?? '';

// Путь к логотипу (относительно корня проекта)
$logoPath = '/DE/korochky/korochki/media/image02.jpg';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/DE/korochky/korochki/styles/style.css">
</head>
<body>

<!-- =================== ШАПКА =================== -->
<header class="site-header">
    <div class="header-top">
        <!-- Логотип -->
        <a href="/DE/korochky/korochki/pages/index.php" class="logo">
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="Логотип Корочки.Есть">
        </a>

        <!-- Название портала -->
        <h1 class="site-title">Корочки.Есть</h1>

        <!-- Навигационные кнопки (ИСПРАВЛЕНО: убран дублирующийся блок) -->
        <nav class="nav-buttons">
            <?php if (!empty($navButtons)): ?>
                <?php foreach ($navButtons as $btn): ?>
                    <?php if (!empty($btn['is_text'])): ?>
                        <!-- Если это просто текст (например, имя пользователя при авторизации) -->
                        <span class="<?= htmlspecialchars($btn['class'] ?? '') ?>">
                            <?= htmlspecialchars($btn['title']) ?>
                        </span>
                    <?php else: ?>
                        <!-- Если это ссылка (Вход, Выход, Регистрация) -->
                        <a href="<?= htmlspecialchars($btn['url'] ?? '#') ?>" 
                           class="nav-btn <?= htmlspecialchars($btn['class'] ?? '') ?>">
                            <?= htmlspecialchars($btn['title']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Хлебные крошки -->
    <?php if (!empty($breadcrumbs)): ?>
        <nav class="breadcrumbs" aria-label="Хлебные крошки">
            <ul>
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <li>
                        <?php if ($i < count($breadcrumbs) - 1 && !empty($crumb['url'])): ?>
                            <a href="<?= htmlspecialchars($crumb['url']) ?>">
                                <?= htmlspecialchars($crumb['title']) ?>
                            </a>
                        <?php else: ?>
                            <span class="current"><?= htmlspecialchars($crumb['title']) ?></span>
                        <?php endif; ?>

                        <?php if ($i < count($breadcrumbs) - 1): ?>
                            <span class="separator">/</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <div class="header-divider"></div>
</header>

<!-- =================== КОНТЕНТ =================== -->
<main class="site-content">
    <?= $content ?>
</main>

<!-- =================== ПОДВАЛ =================== -->
<footer class="site-footer">
    <div class="footer-inner">
        <p class="copyright">
            &copy; <?= date('Y') ?> Корочки.Есть — портал онлайн-курсов
            дополнительного профессионального образования.
        </p>
        <nav class="footer-nav">
            <a href="/DE/korochky/korochki/pages/index.php">Главная</a>
            <a href="#">О нас</a>
            <a href="#">Контакты</a>
        </nav>
    </div>
</footer>

<script src="/DE/korochky/korochki/scripts/script.js"></script>
</body>
</html>