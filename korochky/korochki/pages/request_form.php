<?php
require_once __DIR__ . '/../helpers/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$db = getDB();

$errors = [];
$success = '';

// Получаем списки для выпадающих меню
$courses = $db->query("SELECT id_course, name_course FROM courses ORDER BY id_course")->fetchAll();
$payments = $db->query("SELECT id_payment, method FROM payments ORDER BY id_payment")->fetchAll();

$statuses = [1 => 'Новая', 2 => 'Идёт обучение', 3 => 'Обучение завершено'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id  = (int)($_POST['course_id']  ?? 0);
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    $data_nach  = trim($_POST['data_nach']   ?? '');
    
    if ($course_id <= 0) {
        $errors['course_id'] = 'Выберите курс';
    }
    if ($payment_id <= 0) {
        $errors['payment_id'] = 'Выберите способ оплаты';
    }
    if ($data_nach === '') {
        $errors['data_nach'] = 'Выберите дату начала обучения';
    } elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $data_nach)) {
        $errors['data_nach'] = 'Дата должна быть в формате ДД.ММ.ГГГГ';
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO requests (course_id, data_nach, payment_id, status_id, user_id) 
                    VALUES (:course_id, :data_nach, :payment_id, 1, :user_id)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':course_id'  => $course_id,
                ':data_nach'  => $data_nach,
                ':payment_id' => $payment_id,
                ':user_id'    => $_SESSION['user_id'],
            ]);
            
            $success = 'Заявка успешно подана!';
            $course_id = 0;
            $payment_id = 0;
            $data_nach = '';
        } catch (PDOException $e) {
            $errors['auth'] = 'Ошибка при сохранении заявки. Попробуйте позже.';
        }
    }
}

$pageTitle = 'Подача заявки — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => '/DE/korochky/korochki/pages/index.php'],
    ['title' => 'Мои заявки', 'url' => '/DE/korochky/korochki/pages/requests.php'],
    ['title' => 'Подача заявки', 'url' => ''],
];

$navButtons = [
    ['title' => htmlspecialchars($_SESSION['user_fio'] ?? $_SESSION['user_login']), 
     'url' => '#', 'class' => 'btn-user-name', 'is_text' => true],
    ['title' => 'Выход', 
     'url' => '/DE/korochky/korochki/pages/auth.php?logout=1', 'class' => 'btn-logout'],
];

ob_start();
?>
<section class="request-form-page">
    <h2 class="page-title">Подача заявки</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form class="auth-form" method="POST" action="" novalidate>
        <?php if (!empty($errors['auth'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errors['auth']) ?></div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="course_id">Наименование курса</label>
            <select id="course_id" name="course_id" required>
                <option value="">— Выберите курс —</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= (int)$course['id_course'] ?>" 
                            <?= $course_id == $course['id_course'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['name_course']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['course_id'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['course_id']) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Статус</label>
            <input type="text" value="<?= htmlspecialchars($statuses[1]) ?>" disabled 
                   style="background:#f5f5f5; color:#666;">
            <div class="field-hint">Новая заявка автоматически получает статус «Новая»</div>
        </div>
        
        <div class="form-group">
            <label for="payment_id">Способ оплаты</label>
            <select id="payment_id" name="payment_id" required>
                <option value="">— Выберите способ оплаты —</option>
                <?php foreach ($payments as $payment): ?>
                    <option value="<?= (int)$payment['id_payment'] ?>"
                            <?= $payment_id == $payment['id_payment'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($payment['method']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['payment_id'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['payment_id']) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="data_nach">Желаемая дата начала обучения</label>
            <input type="text"
                   id="data_nach"
                   name="data_nach"
                   value="<?= htmlspecialchars($data_nach) ?>"
                   placeholder="ДД.ММ.ГГГГ"
                   required>
            <?php if (!empty($errors['data_nach'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['data_nach']) ?></div>
            <?php else: ?>
                <div class="field-hint">формат: ДД.ММ.ГГГГ (например, 15.09.2026)</div>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Подать заявку</button>
        
        <div class="auth-footer">
            <a href="/DE/korochky/korochki/pages/requests.php">← Вернуться к моим заявкам</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('data_nach');
    
    dateInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.substring(0, 8);
        
        let formatted = '';
        if (value.length > 0) {
            formatted = value.substring(0, 2);
        }
        if (value.length > 2) {
            formatted += '.' + value.substring(2, 4);
        }
        if (value.length > 4) {
            formatted += '.' + value.substring(4, 8);
        }
        
        e.target.value = formatted;
    });
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';