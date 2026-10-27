<?php
require_once __DIR__ . '/../helpers/db.php';

// Проверяем авторизацию
if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$db = getDB();

$errors = [];
$success = '';

// Получаем ID заявки из URL
$request_id = (int)($_GET['id'] ?? 0);

if ($request_id <= 0) {
    header('Location: requests.php');
    exit;
}

// Получаем данные заявки и проверяем, что она принадлежит текущему пользователю
$stmt = $db->prepare("
    SELECT 
        r.id_request,
        c.name_course,
        r.status_id,
        r.review_id,
        rv.comment AS existing_comment,
        rv.stars AS existing_stars
    FROM requests r
    LEFT JOIN courses c ON r.course_id = c.id_course
    LEFT JOIN reviews rv ON r.review_id = rv.id_review
    WHERE r.id_request = :id AND r.user_id = :user_id
    LIMIT 1
");
$stmt->execute([
    ':id'      => $request_id,
    ':user_id' => $_SESSION['user_id'],
]);
$request = $stmt->fetch();

// Если заявка не найдена или не принадлежит пользователю
if (!$request) {
    header('Location: requests.php');
    exit;
}

// Проверяем, что обучение завершено (status_id = 3)
if ($request['status_id'] != 3) {
    $_SESSION['error'] = 'Оставить отзыв можно только после завершения обучения.';
    header('Location: requests.php');
    exit;
}

// Проверяем, не оставлен ли уже отзыв
if (!empty($request['review_id'])) {
    $_SESSION['error'] = 'Вы уже оставили отзыв на эту заявку.';
    header('Location: requests.php');
    exit;
}

// ---------- Обработка формы ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');
    $stars   = (int)($_POST['stars'] ?? 0);

    // Валидация
    if ($comment === '') {
        $errors['comment'] = 'Введите отзыв';
    } elseif (mb_strlen($comment) > 10) {
        $errors['comment'] = 'Отзыв должен содержать не более 10 символов';
    }

    if ($stars < 1 || $stars > 5) {
        $errors['stars'] = 'Выберите оценку от 1 до 5 звёзд';
    }

    if (empty($errors)) {
        try {
            // Вставляем отзыв
            $stmt = $db->prepare("
                INSERT INTO reviews (comment, stars) 
                VALUES (:comment, :stars)
            ");
            $stmt->execute([
                ':comment' => $comment,
                ':stars'   => $stars,
            ]);
            $review_id = (int)$db->lastInsertId();

            // Привязываем отзыв к заявке
            $stmt = $db->prepare("
                UPDATE requests SET review_id = :review_id 
                WHERE id_request = :id
            ");
            $stmt->execute([
                ':review_id' => $review_id,
                ':id'        => $request_id,
            ]);

            $success = 'Отзыв успешно сохранён!';
            
            // Редирект через 2 секунды
            header('Refresh: 2; URL=requests.php');
        } catch (PDOException $e) {
            $errors['auth'] = 'Ошибка при сохранении отзыва. Попробуйте позже.';
        }
    }
}

// ---------- Настройки страницы ----------
$pageTitle = 'Оставить отзыв — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная',      'url' => '/DE/korochky/korochki/pages/index.php'],
    ['title' => 'Мои заявки',   'url' => '/DE/korochky/korochki/pages/requests.php'],
    ['title' => 'Отзыв',        'url' => ''],
];

$navButtons = [
    ['title' => htmlspecialchars($_SESSION['user_fio'] ?? $_SESSION['user_login']), 
     'url' => '#', 'class' => 'btn-user-name', 'is_text' => true],
    ['title' => 'Выход', 
     'url' => '/DE/korochky/korochki/pages/auth.php?logout=1', 'class' => 'btn-logout'],
];

// ---------- Контент ----------
ob_start();
?>
<section class="review-page">
    <h2 class="page-title">Оставить отзыв</h2>
    
    <div class="review-info">
        <p><strong>Заявка №<?= (int)$request['id_request'] ?></strong></p>
        <p>Курс: <?= htmlspecialchars($request['name_course'] ?? '—') ?></p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
            <br>
            <a href="/DE/korochky/korochki/pages/requests.php">← Вернуться к заявкам</a>
        </div>
    <?php else: ?>
        <form class="auth-form" method="POST" action="" novalidate>
            <?php if (!empty($errors['auth'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errors['auth']) ?></div>
            <?php endif; ?>

            <!-- Звёзды -->
            <div class="form-group">
                <label>Ваша оценка</label>
                <div class="stars-rating" id="starsRating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="stars" id="starsInput" value="<?= (int)($_POST['stars'] ?? 0) ?>">
                <?php if (!empty($errors['stars'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['stars']) ?></div>
                <?php else: ?>
                    <div class="field-hint">Выберите оценку от 1 до 5 звёзд</div>
                <?php endif; ?>
            </div>

            <!-- Комментарий -->
            <div class="form-group">
                <label for="comment">Комментарий</label>
                <textarea 
                    id="comment" 
                    name="comment" 
                    rows="3" 
                    maxlength="10"
                    placeholder="Ваш отзыв (до 10 символов)"
                    required
                ><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                <div class="char-counter">
                    <span id="charCount"><?= mb_strlen($_POST['comment'] ?? '') ?></span> / 10 символов
                </div>
                <?php if (!empty($errors['comment'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['comment']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Кнопка -->
            <button type="submit" class="btn btn-primary btn-block">Отправить отзыв</button>

            <!-- Ссылка назад -->
            <div class="auth-footer">
                <a href="/DE/korochky/korochki/pages/requests.php">← Вернуться к моим заявкам</a>
            </div>
        </form>
    <?php endif; ?>
</section>

<!-- Скрипт для звёзд и счётчика символов -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Звёзды ===
    const stars = document.querySelectorAll('.star');
    const starsInput = document.getElementById('starsInput');
    let selectedStars = parseInt(starsInput.value) || 0;

    // Отображаем начальное состояние
    updateStarsDisplay(selectedStars);

    stars.forEach(star => {
        star.addEventListener('click', function() {
            selectedStars = parseInt(this.dataset.value);
            starsInput.value = selectedStars;
            updateStarsDisplay(selectedStars);
        });

        star.addEventListener('mouseenter', function() {
            const hoverValue = parseInt(this.dataset.value);
            updateStarsDisplay(hoverValue, true);
        });
    });

    document.getElementById('starsRating').addEventListener('mouseleave', function() {
        updateStarsDisplay(selectedStars);
    });

    function updateStarsDisplay(value, isHover = false) {
        stars.forEach(star => {
            const starValue = parseInt(star.dataset.value);
            if (starValue <= value) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    // === Счётчик символов ===
    const commentInput = document.getElementById('comment');
    const charCount = document.getElementById('charCount');

    if (commentInput && charCount) {
        commentInput.addEventListener('input', function() {
            const len = this.value.length;
            charCount.textContent = len;
            
            if (len > 10) {
                this.value = this.value.substring(0, 10);
                charCount.textContent = 10;
            }
            
            if (len >= 10) {
                charCount.style.color = '#d32f2f';
            } else {
                charCount.style.color = '';
            }
        });
    }
});
</script>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';