<?php
require_once __DIR__ . '/../helpers/db.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pageTitle   = 'Регистрация';
$breadcrumbs = [['Главная', page_url('index.php')], ['Регистрация', null]];

$errors = [];
$old = ['login' => '', 'fio' => '', 'phone' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $login    = trim($_POST['login'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $fio      = preg_replace('/\s+/u', ' ', trim($_POST['fio'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $old      = compact('login', 'fio', 'phone', 'email');

    if (!preg_match('/^[A-Za-z0-9]{6,50}$/', $login)) {
        $errors['login'] = 'Логин: только латиница и цифры, не менее 6 символов.';
    }
    if (mb_strlen($password) < 8) {
        $errors['password'] = 'Пароль должен содержать не менее 8 символов.';
    }
    if (!preg_match('/^[А-Яа-яЁё ]+$/u', $fio) || mb_strlen($fio) > 255) {
        $errors['fio'] = 'ФИО: только кириллица и пробелы.';
    }
    if (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors['phone'] = 'Телефон в формате 8(XXX)XXX-XX-XX.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        $errors['email'] = 'Введите корректный адрес электронной почты.';
    }

    if (!$errors) {
        $db = getDB();

        $stmt = $db->prepare("SELECT 1 FROM users WHERE login = :l");
        $stmt->execute([':l' => $login]);
        if ($stmt->fetch()) {
            $errors['login'] = 'Этот логин уже занят.';
        }
        $stmt = $db->prepare("SELECT 1 FROM users WHERE email = :e");
        $stmt->execute([':e' => $email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Этот email уже зарегистрирован.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $db->prepare("
                INSERT INTO users (login, password, fio, phone, email, role_id)
                VALUES (:login, :password, :fio, :phone, :email, 2)
            ");
            $stmt->execute([
                ':login'    => $login,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':fio'      => $fio,
                ':phone'    => $phone,
                ':email'    => $email,
            ]);
            set_flash('success', 'Регистрация прошла успешно! Теперь войдите в аккаунт.');
            redirect('auth.php');
        } catch (PDOException $e) {
            // 1062 — нарушена уникальность (логин/email заняли одновременно с нами)
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $errors['login'] = 'Логин или email уже заняты.';
            } else {
                error_log('register: ' . $e->getMessage());
                $errors['general'] = 'Не удалось завершить регистрацию. Попробуйте позже.';
            }
        }
    }
}

ob_start();
?>
<div class="auth-page">
    <h1 class="page-title">Регистрация</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" value="<?= e($old['login']) ?>" maxlength="50" required>
            <small class="field-hint">Только латиница и цифры, не менее 6 символов</small>
            <?php if (isset($errors['login'])): ?><div class="field-error"><?= e($errors['login']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
            <small class="field-hint">Не менее 8 символов</small>
            <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="fio">ФИО</label>
            <input type="text" id="fio" name="fio" value="<?= e($old['fio']) ?>" maxlength="255" required>
            <small class="field-hint">Только кириллица и пробелы</small>
            <?php if (isset($errors['fio'])): ?><div class="field-error"><?= e($errors['fio']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" placeholder="8(999)999-99-99" maxlength="15" data-phone-mask required>
            <?php if (isset($errors['phone'])): ?><div class="field-error"><?= e($errors['phone']) ?></div><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" maxlength="255" required>
            <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
    </form>

    <p class="auth-footer">Уже есть аккаунт? <a href="<?= e(page_url('auth.php')) ?>">Войти</a></p>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
