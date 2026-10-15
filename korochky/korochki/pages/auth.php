<?php
require_once __DIR__ . '/../helpers/db.php';

// ВАЖНО: обработка выхода должна быть ПЕРВОЙ, до проверки авторизации!
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Если пользователь уже авторизован — отправляем на главную
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$login  = '';

// ---------- Обработка формы ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login']    ?? '');
    $password = $_POST['password']      ?? '';

    // --- Валидация ---
    if ($login === '') {
        $errors['login'] = 'Введите логин';
    } elseif (!preg_match('/^[A-Za-z0-9]+$/', $login)) {
        $errors['login'] = 'Логин должен содержать только латиницу и цифры';
    }

    if ($password === '') {
        $errors['password'] = 'Введите пароль';
    }

    // --- Проверка в БД ---
    if (empty($errors)) {
        try {
            $db  = getDB();
            $sql = 'SELECT id_user, login, password, fio, role_id FROM users WHERE login = :login LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch();

            if ($user && md5($password) === $user['password']) {
                $_SESSION['user_id']    = $user['id_user'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_fio']   = $user['fio'];
                $_SESSION['role_id']    = $user['role_id'] ?? 2;
                
                header('Location: index.php');
                exit;
            } else {
                $errors['auth'] = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            $errors['auth'] = 'Ошибка базы данных. Попробуйте позже.';
        }
    }
}

// ---------- Настройки страницы ----------
$pageTitle = 'Авторизация — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная',      'url' => '/DE/korochky/korochki/pages/index.php'],
    ['title' => 'Авторизация',  'url' => ''],
];

// ---------- Контент ----------
ob_start();
?>
<section class="auth-page">
    <h2 class="page-title">Авторизация</h2>
    <form class="auth-form" method="POST" action="" novalidate>
        <?php if (!empty($errors['auth'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errors['auth']) ?></div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login"
                   value="<?= htmlspecialchars($login) ?>"
                   placeholder="Например: user"
                   pattern="[A-Za-z0-9]+"
                   required>
            <?php if (!empty($errors['login'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['login']) ?></div>
            <?php else: ?>
                <div class="field-hint">только латиница и цифры</div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password"
                   placeholder="Введите пароль"
                   required>
            <?php if (!empty($errors['password'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['password']) ?></div>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Войти</button>
        
        <div class="auth-footer">
            Нет аккаунта?
            <a href="/DE/korochky/korochki/pages/register.php">Зарегистрироваться</a>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';