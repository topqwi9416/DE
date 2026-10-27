<?php
require_once __DIR__ . '/../helpers/db.php';

// ---------- Обработка выхода ----------
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ---------- Настройки страницы ----------
$pageTitle = 'Главная — Корочки.Есть';

// Хлебные крошки
$breadcrumbs = [
    ['title' => 'Главная', 'url' => '/DE/korochky/korochki/pages/index.php'],
];

// Кнопки навигации в шапке — меняются в зависимости от авторизации
if (!empty($_SESSION['user_id'])) {
    $navButtons = [
        ['title' => htmlspecialchars($_SESSION['user_fio'] ?? $_SESSION['user_login']), 
         'url' => '#', 'class' => 'btn-user-name', 'is_text' => true],
        ['title' => 'Выход', 
         'url' => '/DE/korochky/korochki/pages/index.php?logout=1', 'class' => 'btn-logout'],
    ];
} else {
    $navButtons = [
        ['title' => 'Регистрация', 'url' => '/DE/korochky/korochki/pages/register.php', 'class' => 'btn-register'],
        ['title' => 'Вход',        'url' => '/DE/korochky/korochki/pages/auth.php',     'class' => 'btn-login'],
    ];
}

// ---------- Получаем список изображений для слайдера ----------
$mediaDir = __DIR__ . '/../media/';
$images = [];
if (is_dir($mediaDir)) {
    $files = scandir($mediaDir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
            $images[] = '/DE/korochky/korochki/media/' . $file;
        }
    }
}

// ---------- Основной контент ----------
ob_start();
?>

<!-- Слайдер -->
<?php if (!empty($images)): ?>
<section class="slider-section">
    <div class="slider-container">
        <div class="slider-wrapper">
            <?php foreach ($images as $index => $image): ?>
                <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($image) ?>" alt="Слайд <?= $index + 1 ?>">
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Кнопки навигации -->
        <button class="slider-btn slider-btn-prev" onclick="changeSlide(-1)">&#10094;</button>
        <button class="slider-btn slider-btn-next" onclick="changeSlide(1)">&#10095;</button>
        
        <!-- Индикаторы (точки) -->
        <div class="slider-dots">
            <?php foreach ($images as $index => $image): ?>
                <span class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index ?>)"></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="hero">
    <h2 class="hero-title">Добро пожаловать на портал «Корочки.Есть»!</h2>
    <p class="hero-description">
        Портал <strong>«Корочки.Есть»</strong> представляет собой информационную
        систему для записи на онлайн-курсы дополнительного профессионального
        образования.
    </p>
    <p class="hero-description">
        У нас вы найдёте широкий выбор курсов по программированию, веб-дизайну,
        проектированию баз данных и другим направлениям. Получите официальный
        документ об образовании, не выходя из дома!
    </p>
    <div class="hero-actions">
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="/DE/korochky/korochki/pages/requests.php" class="btn btn-primary">
                Мои заявки
            </a>
            <?php if (($_SESSION['role_id'] ?? 0) == 1): ?>
                <a href="/DE/korochky/korochki/pages/admin.php" class="btn btn-secondary">
                    ⚙️ Панель админа
                </a>
            <?php endif; ?>
            <a href="/DE/korochky/korochki/pages/index.php?logout=1" class="btn btn-outline">
                Выйти
            </a>
        <?php else: ?>
            <a href="/DE/korochky/korochki/pages/register.php" class="btn btn-primary">
                Зарегистрироваться
            </a>
            <a href="/DE/korochky/korochki/pages/auth.php" class="btn btn-secondary">
                Войти в кабинет
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="features">
    <h3>Почему выбирают нас</h3>
    <div class="features-grid">
        <div class="feature-card">
            <h4>🎓 Официальные документы</h4>
            <p>По окончании курса вы получаете диплом или удостоверение установленного образца.</p>
        </div>
        <div class="feature-card">
            <h4>💻 Онлайн-обучение</h4>
            <p>Учитесь в удобное для вас время из любой точки мира.</p>
        </div>
        <div class="feature-card">
            <h4>👨‍ Опытные преподаватели</h4>
            <p>Курсы ведут практикующие специалисты с многолетним опытом.</p>
        </div>
    </div>
</section>

<!-- Скрипт слайдера -->
<script>
let slideIndex = 0;
let slideInterval;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');
const totalSlides = slides.length;

function showSlide(n) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    slideIndex = (n + totalSlides) % totalSlides;
    
    slides[slideIndex].classList.add('active');
    dots[slideIndex].classList.add('active');
}

function changeSlide(n) {
    showSlide(slideIndex + n);
    resetInterval();
}

function currentSlide(n) {
    showSlide(n);
    resetInterval();
}

function startInterval() {
    slideInterval = setInterval(() => {
        showSlide(slideIndex + 1);
    }, 5000); // Переключение каждые 5 секунд
}

function resetInterval() {
    clearInterval(slideInterval);
    startInterval();
}

// Запуск автопереключения
if (totalSlides > 0) {
    startInterval();
}
</script>
<?php
$content = ob_get_clean();

// ---------- Подключаем шаблон ----------
require __DIR__ . '/layout.php';