<?php
require_once __DIR__ . '/../helpers/db.php';
require_login();

$db = getDB();

$pageTitle   = 'Подача заявки';
$breadcrumbs = [['Главная', page_url('index.php')], ['Мои заявки', page_url('requests.php')], ['Подача заявки', null]];

$courses  = $db->query("SELECT id_course, name_course FROM courses ORDER BY id_course")->fetchAll(PDO::FETCH_KEY_PAIR);
$payments = $db->query("SELECT id_payment, method FROM payments ORDER BY id_payment")->fetchAll(PDO::FETCH_KEY_PAIR);
$statuses = get_statuses($db);

// Значения по умолчанию. Курс можно предвыбрать ссылкой ?course_id=N
$course_id  = (int)($_GET['course_id'] ?? 0);
$payment_id = 0;
$data_nach  = '';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // При отправке приоритет у значений формы (POST)
    $course_id  = (int)($_POST['course_id']  ?? 0);
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    $data_nach  = trim($_POST['data_nach']   ?? '');

    if (!isset($courses[$course_id])) {
        $errors['course_id'] = 'Выберите курс из списка.';
    }
    if (!isset($payments[$payment_id])) {
        $errors['payment_id'] = 'Выберите способ оплаты.';
    }

    $dateSql = parse_date($data_nach);
    if ($dateSql === null) {
        $errors['data_nach'] = 'Укажите существующую дату.';
    } elseif ($dateSql < date('Y-m-d')) {
        $errors['data_nach'] = 'Дата начала не может быть в прошлом.';
    } elseif ($dateSql > date('Y-m-d', strtotime('+2 years'))) {
        $errors['data_nach'] = 'Дата начала не должна быть позже чем через 2 года.';
    }

    if (!$errors) {
        try {
            $stmt = $db->prepare("
                INSERT INTO requests (user_id, course_id, payment_id, status_id, data_nach)
                VALUES (:u, :c, :p, 1, :d)
            ");
            $stmt->execute([
                ':u' => $_SESSION['user_id'],
                ':c' => $course_id,
                ':p' => $payment_id,
                ':d' => $dateSql,
            ]);
            set_flash('success', 'Заявка успешно подана!');
            redirect('requests.php');
        } catch (PDOException $e) {
            error_log('request_form: ' . $e->getMessage());
            $errors['general'] = 'Не удалось сохранить заявку. Попробуйте позже.';
        }
    }
}

// Значение для поля date должно быть в формате ГГГГ-ММ-ДД
$dateValue = $data_nach !== '' ? (parse_date($data_nach) ?? '') : '';

ob_start();
?>
<div class="request-form-page">
    <h1 class="page-title">Подача заявки</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="course_id">Наименование курса</label>
            <select id="course_id" name="course_id" required>
                <option value="">— выберите курс —</option>
                <?php foreach ($courses as $id => $name): ?>
                    <option value="<?= (int)$id ?>"<?= $course_id === (int)$id ? ' selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course_id'])): ?><div class="field-error"><?= e($errors['course_id']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="data_nach">Желаемая дата начала обучения</label>
            <input type="date" id="data_nach" name="data_nach" value="<?= e($dateValue) ?>"
                   min="<?= e(date('Y-m-d')) ?>" required>
            <?php if (isset($errors['data_nach'])): ?><div class="field-error"><?= e($errors['data_nach']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="payment_id">Способ оплаты</label>
            <select id="payment_id" name="payment_id" required>
                <option value="">— выберите способ —</option>
                <?php foreach ($payments as $id => $method): ?>
                    <option value="<?= (int)$id ?>"<?= $payment_id === (int)$id ? ' selected' : '' ?>><?= e($method) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['payment_id'])): ?><div class="field-error"><?= e($errors['payment_id']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="status_view">Статус</label>
            <select id="status_view" disabled>
                <?php foreach ($statuses as $id => $name): ?>
                    <option<?= (int)$id === 1 ? ' selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="field-hint">Новой заявке автоматически присваивается статус «<?= e($statuses[1] ?? 'Новая') ?>»</small>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Подать заявку</button>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
