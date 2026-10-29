<?php
require_once __DIR__ . '/../helpers/db.php';
require_login();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

$request_id = (int)($_GET['id'] ?? $_POST['request_id'] ?? 0);

// Заявка должна принадлежать текущему пользователю
$stmt = $db->prepare("
    SELECT r.id_request, r.status_id, c.name_course, rv.id_review
    FROM requests r
    JOIN courses c ON c.id_course = r.course_id
    LEFT JOIN reviews rv ON rv.request_id = r.id_request
    WHERE r.id_request = :id AND r.user_id = :u
");
$stmt->execute([':id' => $request_id, ':u' => $uid]);
$request = $stmt->fetch();

if (!$request) {
    set_flash('error', 'Заявка не найдена.');
    redirect('requests.php');
}
if ((int)$request['status_id'] !== 3) {
    set_flash('error', 'Отзыв можно оставить только после завершения обучения.');
    redirect('requests.php');
}
if ($request['id_review'] !== null) {
    set_flash('info', 'Вы уже оставили отзыв на этот курс.');
    redirect('requests.php');
}

$pageTitle   = 'Отзыв о курсе';
$breadcrumbs = [['Главная', page_url('index.php')], ['Мои заявки', page_url('requests.php')], ['Отзыв', null]];

$comment = '';
$stars   = 0;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $comment = trim($_POST['comment'] ?? '');
    $stars   = (int)($_POST['stars'] ?? 0);

    $len = mb_strlen($comment);
    if ($len < 1 || $len > 10) {
        $errors['comment'] = 'Отзыв должен содержать от 1 до 10 символов.';
    }
    if ($stars < 1 || $stars > 5) {
        $errors['stars'] = 'Поставьте оценку от 1 до 5 звёзд.';
    }

    if (!$errors) {
        try {
            $stmt = $db->prepare("INSERT INTO reviews (request_id, comment, stars) VALUES (:r, :c, :s)");
            $stmt->execute([':r' => $request_id, ':c' => $comment, ':s' => $stars]);
            set_flash('success', 'Спасибо за отзыв!');
            redirect('requests.php');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                set_flash('info', 'Вы уже оставили отзыв на этот курс.');
                redirect('requests.php');
            }
            error_log('review: ' . $e->getMessage());
            $errors['general'] = 'Не удалось сохранить отзыв. Попробуйте позже.';
        }
    }
}

ob_start();
?>
<div class="review-page">
    <h1 class="page-title">Отзыв о курсе</h1>

    <div class="review-info">
        <p><strong>Курс:</strong> <?= e($request['name_course']) ?></p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="request_id" value="<?= (int)$request_id ?>">

        <div class="form-group">
            <label>Оценка</label>
            <div class="stars-rating" data-stars-input>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star<?= $i <= $stars ? ' active' : '' ?>" data-value="<?= $i ?>">★</span>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="stars" value="<?= (int)$stars ?>">
            <?php if (isset($errors['stars'])): ?><div class="field-error"><?= e($errors['stars']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="comment">Отзыв</label>
            <textarea id="comment" name="comment" rows="3" maxlength="10" data-counter="comment-counter"><?= e($comment) ?></textarea>
            <div class="char-counter"><span id="comment-counter"><?= mb_strlen($comment) ?></span> / 10</div>
            <?php if (isset($errors['comment'])): ?><div class="field-error"><?= e($errors['comment']) ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Отправить отзыв</button>
        <a class="btn btn-secondary btn-block" href="<?= e(page_url('requests.php')) ?>">Отмена</a>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
