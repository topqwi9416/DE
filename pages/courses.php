<?php
require_once __DIR__ . '/../helpers/db.php';

// ---------- Настройки страницы ----------
$pageTitle = 'Каталог курсов — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => '/DE/korochky/korochki/pages/index.php'],
    ['title' => 'Каталог курсов', 'url' => ''],
];

// Кнопки навигации в шапке
if (!empty($_SESSION['user_id'])) {
    $navButtons = [
        ['title' => htmlspecialchars($_SESSION['user_fio'] ?? $_SESSION['user_login']), 'url' => '#', 'class' => 'btn-user-name', 'is_text' => true],
        ['title' => 'Выход', 'url' => '/DE/korochky/korochki/pages/auth.php?logout=1', 'class' => 'btn-logout'],
    ];
} else {
    $navButtons = [
        ['title' => 'Регистрация', 'url' => '/DE/korochky/korochki/pages/register.php', 'class' => 'btn-register'],
        ['title' => 'Вход', 'url' => '/DE/korochky/korochki/pages/auth.php', 'class' => 'btn-login'],
    ];
}

// Получаем список курсов из БД
$db = getDB();
$stmt = $db->query("SELECT id_course, name_course FROM courses ORDER BY id_course");
$courses = $stmt->fetchAll();

// ---------- Контент ----------
ob_start();
?>
<section class="courses-page">
    <h2 class="page-title">Каталог курсов</h2>
    
    <?php if (empty($courses)): ?>
        <div class="alert alert-info">Курсы временно отсутствуют. Загляните позже!</div>
    <?php else: ?>
        <div class="courses-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <h3 class="course-title"><?= htmlspecialchars($course['name_course']) ?></h3>
                    <p class="course-description">
                        Освойте востребованную профессию с нуля под руководством опытных преподавателей. 
                        По окончании выдается официальный документ установленного образца.
                    </p>
                    <div class="course-actions">
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <!-- Если авторизован, передаем ID курса в форму -->
                            <a href="/DE/korochky/korochki/pages/request_form.php?course_id=<?= (int)$course['id_course'] ?>" class="btn btn-primary">
                                Подать заявку
                            </a>
                        <?php else: ?>
                            <!-- Если нет, отправляем на авторизацию -->
                            <a href="/DE/korochky/korochki/pages/auth.php" class="btn btn-primary">
                                Войти для записи
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();

// ---------- Подключаем шаблон ----------
require __DIR__ . '/layout.php';