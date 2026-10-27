<?php
require_once __DIR__ . '/../helpers/db.php';

// Проверяем авторизацию
if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Проверяем, что пользователь — администратор (role_id = 1)
if (($_SESSION['role_id'] ?? 0) != 1) {
    header('Location: index.php');
    exit;
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$db = getDB();

$statuses = [
    1 => 'Новая',
    2 => 'Идёт обучение',
    3 => 'Обучение завершено',
];

$success = '';
$error = '';

// ---------- Обработка смены статуса ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $new_status = (int)($_POST['new_status'] ?? 0);
    
    if ($request_id > 0 && isset($statuses[$new_status])) {
        try {
            $stmt = $db->prepare("UPDATE requests SET status_id = :status WHERE id_request = :id");
            $stmt->execute([
                ':status' => $new_status,
                ':id'     => $request_id,
            ]);
            $success = "Статус заявки №{$request_id} изменён на «{$statuses[$new_status]}»";
        } catch (PDOException $e) {
            $error = 'Ошибка при обновлении статуса: ' . $e->getMessage();
        }
    }
}

// ---------- Обработка удаления заявки ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    
    if ($request_id > 0) {
        try {
            // Сначала удаляем связанный отзыв (если есть)
            $stmt = $db->prepare("SELECT review_id FROM requests WHERE id_request = :id");
            $stmt->execute([':id' => $request_id]);
            $req = $stmt->fetch();
            
            if ($req && !empty($req['review_id'])) {
                $stmt = $db->prepare("DELETE FROM reviews WHERE id_review = :id");
                $stmt->execute([':id' => $req['review_id']]);
            }
            
            // Удаляем заявку
            $stmt = $db->prepare("DELETE FROM requests WHERE id_request = :id");
            $stmt->execute([':id' => $request_id]);
            $success = "Заявка №{$request_id} удалена";
        } catch (PDOException $e) {
            $error = 'Ошибка при удалении заявки: ' . $e->getMessage();
        }
    }
}

// Получаем ВСЕ заявки с данными пользователей
$stmt = $db->query("
    SELECT 
        r.id_request,
        u.fio AS user_fio,
        u.login AS user_login,
        u.email AS user_email,
        u.phone AS user_phone,
        c.name_course,
        r.data_nach,
        p.method AS payment_method,
        r.status_id,
        rv.comment AS review_comment,
        rv.stars AS review_stars
    FROM requests r
    LEFT JOIN users u ON r.user_id = u.id_user
    LEFT JOIN courses c ON r.course_id = c.id_course
    LEFT JOIN payments p ON r.payment_id = p.id_payment
    LEFT JOIN reviews rv ON r.review_id = rv.id_review
    ORDER BY r.id_request DESC
");
$requests = $stmt->fetchAll();

// Статистика
$total = count($requests);
$new_count = count(array_filter($requests, fn($r) => $r['status_id'] == 1));
$progress_count = count(array_filter($requests, fn($r) => $r['status_id'] == 2));
$done_count = count(array_filter($requests, fn($r) => $r['status_id'] == 3));

// ---------- Настройки страницы ----------
$pageTitle = 'Панель администратора — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная',      'url' => '/DE/korochky/korochki/pages/index.php'],
    ['title' => 'Панель админа', 'url' => ''],
];

$navButtons = [
    ['title' => htmlspecialchars($_SESSION['user_fio'] ?? $_SESSION['user_login']), 
     'url' => '#', 'class' => 'btn-user-name', 'is_text' => true],
    ['title' => 'Выход', 
     'url' => '/DE/korochky/korochki/pages/admin.php?logout=1', 'class' => 'btn-logout'],
];

// ---------- Контент ----------
ob_start();
?>
<section class="admin-page">
    <h2 class="page-title">Панель администратора</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Статистика -->
    <div class="admin-stats">
        <div class="stat-card stat-total">
            <div class="stat-number"><?= $total ?></div>
            <div class="stat-label">Всего заявок</div>
        </div>
        <div class="stat-card stat-new">
            <div class="stat-number"><?= $new_count ?></div>
            <div class="stat-label">Новых</div>
        </div>
        <div class="stat-card stat-progress">
            <div class="stat-number"><?= $progress_count ?></div>
            <div class="stat-label">В процессе</div>
        </div>
        <div class="stat-card stat-done">
            <div class="stat-number"><?= $done_count ?></div>
            <div class="stat-label">Завершено</div>
        </div>
    </div>
    
    <?php if (empty($requests)): ?>
        <div class="alert alert-info">Заявок пока нет.</div>
    <?php else: ?>
        <div class="requests-grid">
            <?php foreach ($requests as $req): ?>
                <div class="request-card admin-card status-<?= (int)$req['status_id'] ?>">
                    <h3 class="request-card-title">Заявка №<?= (int)$req['id_request'] ?></h3>
                    
                    <div class="request-card-row">
                        <span class="request-card-label">Пользователь:</span>
                        <span class="request-card-value">
                            <strong><?= htmlspecialchars($req['user_fio'] ?? '—') ?></strong>
                            <br><small style="color:#888;">
                                @<?= htmlspecialchars($req['user_login'] ?? '') ?>
                                <?php if (!empty($req['user_email'])): ?>
                                    <br>📧 <?= htmlspecialchars($req['user_email']) ?>
                                <?php endif; ?>
                                <?php if (!empty($req['user_phone'])): ?>
                                    <br>📞 <?= htmlspecialchars($req['user_phone']) ?>
                                <?php endif; ?>
                            </small>
                        </span>
                    </div>
                    
                    <div class="request-card-row">
                        <span class="request-card-label">Курс:</span>
                        <span class="request-card-value"><?= htmlspecialchars($req['name_course'] ?? '—') ?></span>
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
                                <div>
                                    <?= htmlspecialchars($req['review_comment']) ?>
                                    <?php if (!empty($req['review_stars'])): ?>
                                        <span class="stars">
                                            <?= str_repeat('★', (int)$req['review_stars']) ?>
                                            <?= str_repeat('☆', 5 - (int)$req['review_stars']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <em style="color:#999;">не оставлен</em>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <!-- Форма смены статуса -->
                    <form method="POST" action="" class="admin-status-form">
                        <input type="hidden" name="request_id" value="<?= (int)$req['id_request'] ?>">
                        
                        <div class="form-group" style="margin-top:12px;">
                            <label for="status_<?= (int)$req['id_request'] ?>">Изменить статус:</label>
                            <select id="status_<?= (int)$req['id_request'] ?>" name="new_status" required>
                                <?php foreach ($statuses as $id => $name): ?>
                                    <option value="<?= $id ?>" 
                                            <?= $req['status_id'] == $id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="admin-actions">
                            <button type="submit" name="update_status" value="1" class="btn btn-primary btn-sm">
                                 Сохранить
                            </button>
                            <button type="submit" name="delete_request" value="1" 
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Удалить заявку №<?= (int)$req['id_request'] ?>?')">
                                🗑 Удалить
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';