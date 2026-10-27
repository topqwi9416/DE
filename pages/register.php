<?php
require_once __DIR__ . '/../helpers/db.php';

// Если пользователь уже авторизован — отправляем на главную
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$login  = '';
$fio    = '';
$phone  = '';
$email  = '';

// ---------- Обработка формы ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login']    ?? '');
    $password = $_POST['password']      ?? '';
    $fio      = trim($_POST['fio']      ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $email    = trim($_POST['email']    ?? '');

    // --- Валидация ---
    
    // Логин: только латиница и цифры, не менее 6 символов
    if ($login === '') {
        $errors['login'] = 'Введите логин';
    } elseif (!preg_match('/^[A-Za-z0-9]{6,}$/', $login)) {
        $errors['login'] = 'Логин должен содержать только латиницу и цифры, не менее 6 символов';
    }

    // Пароль: минимум 8 символов
    if ($password === '') {
        $errors['password'] = 'Введите пароль';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Пароль должен содержать минимум 8 символов';
    }

    // ФИО: только кириллица и пробелы
    if ($fio === '') {
        $errors['fio'] = 'Введите ФИО';
    } elseif (!preg_match('/^[А-Яа-яЁё\s]+$/u', $fio)) {
        $errors['fio'] = 'Используйте только символы кириллицы и пробелы';
    }

    // Email: корректный формат
    if ($email === '') {
        $errors['email'] = 'Введите адрес электронной почты';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Неверный адрес электронной почты';
    }

    // Телефон: только проверка на заполненность (формат добавляется автоматически)
    if ($phone === '') {
        $errors['phone'] = 'Введите номер телефона';
    } elseif (strlen(preg_replace('/\D/', '', $phone)) < 11) {
        $errors['phone'] = 'Номер телефона должен содержать 11 цифр';
    }

    // --- Проверка уникальности в БД ---
    if (empty($errors)) {
        try {
            $db = getDB();

            // Проверяем, не занят ли логин
            $stmt = $db->prepare("SELECT id_user FROM users WHERE login = :login LIMIT 1");
            $stmt->execute([':login' => $login]);
            if ($stmt->fetch()) {
                $errors['login'] = 'Этот логин уже занят';
            }

            // Проверяем, не занят ли email
            $stmt = $db->prepare("SELECT id_user FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Этот email уже зарегистрирован';
            }
        } catch (PDOException $e) {
            $errors['auth'] = 'Ошибка базы данных. Попробуйте позже.';
        }
    }

    // --- Регистрация ---
    if (empty($errors)) {
        try {
            $db = getDB();
            
            $sql = 'INSERT INTO users (login, password, fio, phone, email, role_id) 
                    VALUES (:login, :password, :fio, :phone, :email, 2)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':login'    => $login,
                ':password' => md5($password), // пароль хешируем в MD5
                ':fio'      => $fio,
                ':phone'    => $phone,
                ':email'    => $email,
            ]);

            // Успешная регистрация — редирект на авторизацию
            $_SESSION['success'] = 'Регистрация прошла успешно! Теперь вы можете войти.';
            header('Location: auth.php');
            exit;
        } catch (PDOException $e) {
            $errors['auth'] = 'Ошибка при регистрации. Попробуйте позже.';
        }
    }
}

// ---------- Настройки страницы ----------
$pageTitle = 'Регистрация — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная',      'url' => '/DE/korochky/korochki/pages/index.php'],
    ['title' => 'Регистрация',  'url' => ''],
];

// ---------- Контент ----------
ob_start();
?>
<section class="auth-page">
    <h2 class="page-title">Регистрация</h2>
    <form class="auth-form" method="POST" action="" novalidate>
        <?php if (!empty($errors['auth'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errors['auth']) ?></div>
        <?php endif; ?>

        <!-- Логин -->
        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text"
                   id="login"
                   name="login"
                   value="<?= htmlspecialchars($login) ?>"
                   placeholder="Например: user123"
                   pattern="[A-Za-z0-9]{6,}"
                   minlength="6"
                   required>
            <?php if (!empty($errors['login'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['login']) ?></div>
            <?php else: ?>
                <div class="field-hint">латиница и цифры, не менее 6 символов</div>
            <?php endif; ?>
        </div>

        <!-- Пароль -->
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="Введите пароль"
                   minlength="8"
                   required>
            <?php if (!empty($errors['password'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['password']) ?></div>
            <?php else: ?>
                <div class="field-hint">минимум 8 символов</div>
            <?php endif; ?>
        </div>

        <!-- ФИО -->
        <div class="form-group">
            <label for="fio">ФИО</label>
            <input type="text"
                   id="fio"
                   name="fio"
                   value="<?= htmlspecialchars($fio) ?>"
                   placeholder="Иванов Иван Иванович"
                   required>
            <?php if (!empty($errors['fio'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['fio']) ?></div>
            <?php else: ?>
                <div class="field-hint">только кириллица и пробелы</div>
            <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">Адрес электронной почты</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="<?= htmlspecialchars($email) ?>"
                   placeholder="example@mail.ru"
                   required>
            <?php if (!empty($errors['email'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Телефон -->
        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel"
                   id="phone"
                   name="phone"
                   value="<?= htmlspecialchars($phone) ?>"
                   placeholder="8(___)___-__-__"
                   maxlength="18"
                   required>
            <?php if (!empty($errors['phone'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['phone']) ?></div>
            <?php else: ?>
                <div class="field-hint">формат: 8(xxx)xxx-xx-xx (вводите только цифры)</div>
            <?php endif; ?>
        </div>

        <!-- Кнопка -->
        <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>

        <!-- Ссылка на авторизацию -->
        <div class="auth-footer">
            Уже есть аккаунт?
            <a href="/DE/korochky/korochki/pages/auth.php">Войти</a>
        </div>
    </form>
</section>

<!-- Скрипт автоматического форматирования телефона -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phone');
    
    phoneInput.addEventListener('input', function(e) {
        // Получаем только цифры из введённого значения
        let digits = e.target.value.replace(/\D/g, '');
        
        // Если первая цифра не 8 — добавляем 8
        if (digits.length > 0 && digits[0] !== '8') {
            digits = '8' + digits;
        }
        
        // Ограничиваем до 11 цифр
        digits = digits.substring(0, 11);
        
        // Форматируем: 8(xxx)xxx-xx-xx
        let formatted = '';
        if (digits.length > 0) {
            formatted = '8';
        }
        if (digits.length > 1) {
            formatted += '(' + digits.substring(1, 4);
        }
        if (digits.length >= 4) {
            formatted += ')';
        }
        if (digits.length > 4) {
            formatted += digits.substring(4, 7);
        }
        if (digits.length > 7) {
            formatted += '-' + digits.substring(7, 9);
        }
        if (digits.length > 9) {
            formatted += '-' + digits.substring(9, 11);
        }
        
        e.target.value = formatted;
    });

    // Запрещаем ввод нецифровых символов (кроме управляющих клавиш)
    phoneInput.addEventListener('keydown', function(e) {
        // Разрешаем: Backspace, Delete, Tab, Escape, Enter, стрелки
        if ([46, 8, 9, 27, 13, 37, 38, 39, 40].indexOf(e.keyCode) !== -1 ||
            // Разрешаем: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        // Разрешаем только цифры
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    // При потере фокуса — если введено меньше 11 цифр, показываем ошибку
    phoneInput.addEventListener('blur', function(e) {
        let digits = e.target.value.replace(/\D/g, '');
        if (digits.length > 0 && digits.length < 11) {
            // Можно добавить визуальную индикацию неполного номера
            e.target.style.borderColor = '#d32f2f';
        } else {
            e.target.style.borderColor = '';
        }
    });
});
</script>
<?php
$content = ob_get_clean();

// ---------- Подключаем общий шаблон ----------
require __DIR__ . '/layout.php';