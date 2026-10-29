<?php
require_once __DIR__ . '/../helpers/db.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pageTitle   = 'Вход';
$breadcrumbs = [['Главная', page_url('index.php')], ['Вход', null]];

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $login    = trim($_POST['login'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'Введите логин и пароль.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id_user, login, password, fio, role_id FROM users WHERE login = :l");
        $stmt->execute([':l' => $login]);
        $user = $stmt->fetch();

        $ok = false;
        if ($user) {
            $stored = $user['password'];
            if (str_starts_with($stored, '$')) {
                // Современный хеш (password_hash)
                $ok = password_verify($password, $stored);
            } else {
                // Старый MD5-хеш: проверяем и сразу заменяем на безопасный
                $ok = hash_equals($stored, md5($password));
                if ($ok) {
                    $upd = $db->prepare("UPDATE users SET password = :p WHERE id_user = :id");
                    $upd->execute([':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['id_user']]);
                }
            }
        }

        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = (int)$user['id_user'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_fio']   = $user['fio'];
            $_SESSION['role_id']    = (int)$user['role_id'];
            redirect($user['role_id'] == 1 ? 'admin.php' : 'index.php');
        }

        $error = 'Неверный логин или пароль.';
    }
}

ob_start();
?>
<div class="auth-page">
    <h1 class="page-title">Вход</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" value="<?= e($login) ?>" maxlength="50" autofocus required>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Войти</button>
    </form>

    <p class="auth-footer">Нет аккаунта? <a href="<?= e(page_url('register.php')) ?>">Зарегистрироваться</a></p>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
