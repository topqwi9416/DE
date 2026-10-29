<?php
require_once __DIR__ . '/../helpers/db.php';
require_login();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

$pageTitle   = 'Мои заявки';
$breadcrumbs = [['Главная', page_url('index.php')], ['Мои заявки', null]];

$stmt = $db->prepare("SELECT COUNT(*) FROM requests WHERE user_id = :u");
$stmt->execute([':u' => $uid]);
$total = (int)$stmt->fetchColumn();

[$page, $pages, $offset] = paginate($total);

$limit = PER_PAGE;
$stmt = $db->prepare("
    SELECT r.id_request, r.data_nach, r.status_id,
           c.name_course, p.method, s.name_status,
           rv.comment, rv.stars
    FROM requests r
    JOIN courses  c ON c.id_course  = r.course_id
    JOIN payments p ON p.id_payment = r.payment_id
    JOIN statuses s ON s.id_status  = r.status_id
    LEFT JOIN reviews rv ON rv.request_id = r.id_request
    WHERE r.user_id = :u
    ORDER BY r.id_request DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute([':u' => $uid]);
$requests = $stmt->fetchAll();

ob_start();
?>
<div class="requests-page">
    <h1 class="page-title">Мои заявки</h1>

    <div class="requests-actions">
        <a class="btn btn-primary" href="<?= e(page_url('request_form.php')) ?>">Подать заявку</a>
    </div>

    <?php if (!$requests): ?>
        <div class="alert alert-info">У вас пока нет заявок. Нажмите «Подать заявку», чтобы записаться на курс.</div>
    <?php else: ?>
        <div class="requests-grid">
            <?php foreach ($requests as $r): ?>
                <article class="request-card">
                    <h2 class="request-card-title"><?= e($r['name_course']) ?></h2>

                    <div class="request-card-row">
                        <span class="request-card-label">Статус</span>
                        <span class="request-card-value">
                            <span class="status status-<?= (int)$r['status_id'] ?>"><?= e($r['name_status']) ?></span>
                        </span>
                    </div>
                    <div class="request-card-row">
                        <span class="request-card-label">Способ оплаты</span>
                        <span class="request-card-value"><?= e($r['method']) ?></span>
                    </div>
                    <div class="request-card-row">
                        <span class="request-card-label">Желаемая дата</span>
                        <span class="request-card-value"><?= e(format_date($r['data_nach'])) ?></span>
                    </div>
                    <div class="request-card-row">
                        <span class="request-card-label">Отзыв</span>
                        <span class="request-card-value">
                            <?php if ($r['comment'] !== null): ?>
                                <?= e($r['comment']) ?>
                                <span class="stars" title="<?= (int)$r['stars'] ?> из 5"><?= e(render_stars((int)$r['stars'])) ?></span>
                            <?php elseif ((int)$r['status_id'] === 3): ?>
                                <a class="btn-link" href="<?= e(page_url('review.php?id=' . (int)$r['id_request'])) ?>">Оставить отзыв</a>
                            <?php else: ?>
                                <span class="muted">после завершения обучения</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($r['comment'] === null && (int)$r['status_id'] === 3): ?>
                    <div class="request-card-row">
                        <span class="request-card-label">Оценка</span>
                        <span class="request-card-value">
                            <a class="btn-link" href="<?= e(page_url('review.php?id=' . (int)$r['id_request'])) ?>">Оставить звёзды</a>
                        </span>
                    </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <?= render_pagination($page, $pages, page_url('requests.php')) ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
