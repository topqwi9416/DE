<?php
require_once __DIR__ . '/../helpers/db.php';

echo "<h2>🔍 Диагностика авторизации</h2>";
echo "<pre style='background:#f0f0f0; padding:15px; border:1px solid #ccc;'>";

// 1. Проверяем подключение к БД
echo "=== 1. Подключение к БД ===\n";
try {
    $db = getDB();
    echo "✅ Подключение успешно\n";
} catch (Exception $e) {
    echo "❌ Ошибка подключения: " . $e->getMessage() . "\n";
    exit;
}

// 2. Смотрим всех пользователей в БД
echo "\n=== 2. Все пользователи в таблице users ===\n";
$stmt = $db->query("SELECT id_user, login, password, fio, role_id FROM users");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "ID: {$user['id_user']}\n";
    echo "  Логин: {$user['login']}\n";
    echo "  Хеш пароля в БД: {$user['password']}\n";
    echo "  ФИО: {$user['fio']}\n";
    echo "  Роль: {$user['role_id']}\n";
    echo "---\n";
}

// 3. Проверяем хеши паролей
echo "\n=== 3. Проверка хешей MD5 ===\n";
$test_passwords = ['12345', '123456', '12345678'];
foreach ($test_passwords as $pass) {
    $hash = md5($pass);
    echo "Пароль '{$pass}' -> MD5: {$hash}\n";
    
    // Ищем этот хеш в БД
    $stmt = $db->prepare("SELECT login FROM users WHERE password = :hash");
    $stmt->execute([':hash' => $hash]);
    $found = $stmt->fetch();
    
    if ($found) {
        echo "  ✅ Найден у пользователя: {$found['login']}\n";
    } else {
        echo "  ❌ Не найден ни у одного пользователя\n";
    }
}

// 4. Тестовая авторизация
echo "\n=== 4. Тест авторизации ===\n";
$test_login = 'User';
$test_pass = '12345';

echo "Попытка входа: логин='{$test_login}', пароль='{$test_pass}'\n";
echo "MD5 пароля: " . md5($test_pass) . "\n";

$stmt = $db->prepare("SELECT id_user, login, password, fio, role_id FROM users WHERE login = :login LIMIT 1");
$stmt->execute([':login' => $test_login]);
$user = $stmt->fetch();

if ($user) {
    echo "✅ Пользователь найден в БД\n";
    echo "  ID: {$user['id_user']}\n";
    echo "  Логин: {$user['login']}\n";
    echo "  Хеш в БД: {$user['password']}\n";
    echo "  Хеш введённого пароля: " . md5($test_pass) . "\n";
    
    if (md5($test_pass) === $user['password']) {
        echo "✅ Пароли совпадают!\n";
    } else {
        echo " Пароли НЕ совпадают!\n";
    }
} else {
    echo "❌ Пользователь с логином '{$test_login}' не найден\n";
}

// 5. Проверяем сессию
echo "\n=== 5. Данные сессии ===\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
print_r($_SESSION);

echo "</pre>";