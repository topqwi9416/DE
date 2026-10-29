<?php
require_once __DIR__ . '/../helpers/db.php';
require_admin();

$db = getDB();
$statuses = get_statuses($db);

$pageTitle   = 'Панель администратора';
$breadcrumbs = [['Главная', page_url('index.php')], ['Панель администратора', null]];

// ---------- Обработка действий (POST → редирект обратно на ту же страницу) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $request_id = (int)($_POST['request_id'] ?? 0);
    $back = 'admin.php' . (!empty($_POST['page']) ? '?page=' . (int)$_POST['page'] : '');

    try {
        if (isset($_POST['update_status'])) {
            $new_status = (int)($_POST['status_id'] ?? 0);

            if (!isset($statuses[$new_status])) {
                set_flash('error', 'Выбран несуществующий статус.');
            } else {
                // Если отзыв уже оставлен, статус «Обучение завершено» менять нельзя
                $stmt = $db->prepare("SELECT 1 FROM reviews WHERE request_id = :id");
                $stmt->execute([':id' => $request_id]);
                if ($stmt->fetch() && $new_status !== 3) {
                    set_flash('error', "К заявке №{$request_id} уже оставлен отзыв — статус нельзя вернуть назад.");
                } else {
                    $stmt = $db->prepare("UPDATE requests SET status_id = :s WHERE id_request = :id");
                    $stmt->execute([':s' => $new_status, ':id' => $request_id]);
                    set_flash('success', "Статус заявки №{$request_id} изменён на «{$statuses[$new_status]}».");
                }
            }
        } elseif (isset($_POST['delete_request'])) {
            // Отзыв удалится сам (ON DELETE CASCADE)
            $stmt = $db->prepare("DELETE FROM requests WHERE id_request = :id");
            $stmt->execute([':id' => $request_id]);
            set_flash('success', "Заявка №{$request_id} удалена.");
        }
    } catch (PDOException $e) {
        error_log('admin: ' . $e->getMessage());
        set_flash('error', 'Не удалось выполнить действие. Попробуйте позже.');
    }

    redirect($back);
}

// ---------- Статистика (по всей таблице, а не по текущей странице) ----------
$counts = $db->query("SELECT status_id, COUNT(*) FROM requests GROUP BY status_id")
             ->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);

// ---------- Список заявок ----------
[$page, $pages, $offset] = paginate((int)$total);
$limit = PER_PAGE;

$requests = $db->query("
    SELECT r.id_request, r.data_nach, r.status_id,
           u.fio, u.login, u.email, u.phone,
           c.name_course, p.method, s.name_status,
           rv.comment, rv.stars
    FROM requests r
    JOIN users    u ON u.id_user    = r.user_id
    JOIN courses  c ON c.id_course  = r.course_id
    JOIN payments p ON p.id_payment = r.payment_id
    JOIN statuses s ON s.id_status  = r.status_id
    LEFT JOIN reviews rv ON rv.request_id = r.id_request
    ORDER BY r.id_request DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

ob_start();
?>
<div class="admin-page">
    <h1 class="page-title">Панель администратора</h1>

    <div class="admin-stats">
        <div class="stat-card stat-total">
            <div class="stat-number"><?= (int)$total ?></div>
            <div class="stat-label">Всего заявок</div>
        </div>
        <?php
        $statClass = [1 => 'stat-new', 2 => 'stat-progress', 3 => 'stat-done'];
        foreach ($statuses as $id => $name): ?>
            <div class="stat-card <?= $statClass[$id] ?? '' ?>">
                <div class="stat-number"><?= (int)($counts[$id] ?? 0) ?></div>
                <div class="stat-label"><?= e($name) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$requests): ?>
        <div class="alert alert-info">Заявок пока нет.</div>
    <?php else: ?>
        <div class="requests-grid">
            <?php foreach ($requests as $r): ?>
                <article class="request-card admin-card status-<?= (int)$r['status_id'] ?>">
                    <h2 class="request-card-title">№<?= (int)$r['id_request'] ?> · <?= e($r['name_course']) ?></h2>

                    <div class="request-card-row">
                        <span class="request-card-label">Пользователь</span>
                        <span class="request-card-value">
                            <?= e($r['fio']) ?><br>
                            <small><?= e($r['login']) ?> · <?= e($r['phone']) ?><br><?= e($r['email']) ?></small>
                        </span>
                    </div>
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
                                <span class="stars"><?= e(render_stars((int)$r['stars'])) ?></span>
                            <?php else: ?>
                                <span class="muted">нет</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <form class="admin-status-form" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int)$r['id_request'] ?>">
                        <input type="hidden" name="page" value="<?= (int)$page ?>">
                        <select name="status_id" aria-label="Статус заявки №<?= (int)$r['id_request'] ?>">
                            <?php foreach ($statuses as $id => $name): ?>
                                <option value="<?= (int)$id ?>"<?= (int)$id === (int)$r['status_id'] ? ' selected' : '' ?>><?= e($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Сохранить</button>
                        <button type="submit" name="delete_request" class="btn btn-danger btn-sm"
                                data-confirm="Удалить заявку №<?= (int)$r['id_request'] ?> вместе с отзывом?">Удалить</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>

        <?= render_pagination($page, $pages, page_url('admin.php')) ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
