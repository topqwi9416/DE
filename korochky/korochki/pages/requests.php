<?php
require_once __DIR__ . '/../helpers/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$db = getDB();

// Получаем заявки текущего пользователя
$stmt = $db->prepare("
    SELECT 
        r.id_request,
        c.name_course,
        r.data_nach,
        p.method AS payment_method,
        r.status_id,
        rv.comment AS review_comment,
        rv.stars AS review_stars
    FROM requests r
    LEFT JOIN courses c ON r.course_id = c.id_course
    LEFT JOIN payments p ON r.payment_id = p.id_payment
    LEFT JOIN reviews rv ON r.review_id = rv.id_review
    WHERE r.user_id = :user_id
    ORDER BY r.id_request DESC
");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$requests = $stmt->fetchAll();

$statuses = [1 => 'Новая', 2 => 'Идёт обучение', 3 => 'Обучение завершено'];

$pageTitle = 'Мои заявки — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => '/DE/korochky/korochki/pages/index.php'],
    ['title' => 'Мои заявки', 'url' => ''],
];

$navButtons = [
    ['title' => htmlspecialchars($_SESSION['user_fio'] ?? $_SESSION['user_login']), 
     'url' => '#', 'class' => 'btn-user-name', 'is_text' => true],
    ['title' => 'Выход', 
     'url' => '/DE/korochky/korochki/pages/auth.php?logout=1', 'class' => 'btn-logout'],
];

ob_start();
?>
<section class="requests-page">
    <h2 class="page-title">Мои заявки</h2>
    
    <div class="requests-actions">
        <a href="/DE/korochky/korochki/pages/request_form.php" class="btn btn-primary">
            Подать новую заявку
        </a>
    </div>
    
    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            У вас пока нет заявок. 
            <a href="/DE/korochky/korochki/pages/request_form.php">Подать первую заявку</a>
        </div>
    <?php else: ?>
        <div class="requests-grid">
            <?php foreach ($requests as $req): ?>
                <div class="request-card">
                    <h3 class="request-card-title">Заявка №<?= (int)$req['id_request'] ?></h3>
                    
                    <div class="request-card-row">
                        <span class="request-card-label">Курс:</span>
                        <span class="request-card-value"><?= htmlspecialchars($req['name_course'] ?? '—') ?></span>
                    </div>
                    
                    <div class="request-card-row">
                        <span class="request-card-label">Статус:</span>
                        <span class="request-card-value status status-<?= (int)$req['status_id'] ?>">
                            <?= htmlspecialchars($statuses[$req['status_id']] ?? 'Неизвестно') ?>
                        </span>
                    </div>
                    
                    <div class="request-card-row">
                        <span class="request-card-label">Оплата:</span>
                        <span class="request-card-value"><?= htmlspecialchars($req['payment_method'] ?? '—') ?></span>
                    </div>
                    
                    <div class="request-card-row">
                        <span class="request-card-label">Дата начала:</span>
                        <span class="request-card-value"><?= htmlspecialchars($req['data_nach'] ?? '—') ?></span>
                    </div>
                    
                    <div class="request-card-row">
                        <span class="request-card-label">Отзыв:</span>
                        <span class="request-card-value">
                            <?php if (!empty($req['review_comment'])): ?>
                                <?= htmlspecialchars($req['review_comment']) ?>
                                <?php if (!empty($req['review_stars'])): ?>
                                    <span class="stars">
                                        <?= str_repeat('★', (int)$req['review_stars']) ?>
                                        <?= str_repeat('☆', 5 - (int)$req['review_stars']) ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <em style="color:#999;">не оставлен</em>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';