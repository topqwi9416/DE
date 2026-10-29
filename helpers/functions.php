<?php
/**
 * Общие функции сайта: пути, экранирование, flash-сообщения,
 * CSRF-защита, проверка доступа, работа с датами, пагинация.
 */

// ---------- Пути ----------
// BASE_URL определяется автоматически (папка проекта относительно корня сайта),
// поэтому проект можно положить в любую папку htdocs без правки ссылок.
if (!defined('BASE_URL')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    define('BASE_URL', rtrim(dirname(dirname($script)), '/'));
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function page_url(string $page): string
{
    return url('pages/' . ltrim($page, '/'));
}

function redirect(string $page): void
{
    header('Location: ' . page_url($page));
    exit;
}

// ---------- Вывод ----------
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------- Flash-сообщения (показываются один раз после редиректа) ----------
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Вызывать в начале обработки любого POST-запроса */
function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        die('Недействительный токен формы. Вернитесь назад, обновите страницу и повторите действие.');
    }
}

// ---------- Доступ ----------
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && (int)($_SESSION['role_id'] ?? 0) === 1;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('info', 'Для этого действия нужно войти в аккаунт.');
        redirect('auth.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        set_flash('error', 'Доступ только для администратора.');
        redirect('index.php');
    }
}

// ---------- Даты ----------
/** '2026-09-15' → '15.09.2026' */
function format_date(?string $ymd): string
{
    if (!$ymd) {
        return '—';
    }
    $d = DateTime::createFromFormat('Y-m-d', $ymd);
    return $d ? $d->format('d.m.Y') : e($ymd);
}

/** Принимает 'ГГГГ-ММ-ДД' (поле date) или 'ДД.ММ.ГГГГ'. Возвращает 'ГГГГ-ММ-ДД' или null */
function parse_date(string $input): ?string
{
    $input = trim($input);
    foreach (['Y-m-d', 'd.m.Y'] as $format) {
        $d = DateTime::createFromFormat($format, $input);
        $errors = DateTime::getLastErrors();
        if ($d && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $d->format('Y-m-d');
        }
    }
    return null;
}

// ---------- Справочники ----------
/** [id => название] из таблицы statuses */
function get_statuses(PDO $db): array
{
    return $db->query("SELECT id_status, name_status FROM statuses ORDER BY id_status")
              ->fetchAll(PDO::FETCH_KEY_PAIR);
}

// ---------- Пагинация ----------
const PER_PAGE = 6;

/** Возвращает [текущая страница, всего страниц, смещение] */
function paginate(int $total, int $perPage = PER_PAGE): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    return [$page, $pages, ($page - 1) * $perPage];
}

function render_pagination(int $page, int $pages, string $base): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Страницы">';
    if ($page > 1) {
        $html .= '<a href="' . e($base . '?page=' . ($page - 1)) . '">&laquo;</a>';
    }
    for ($i = 1; $i <= $pages; $i++) {
        $cls = $i === $page ? ' class="active"' : '';
        $html .= '<a' . $cls . ' href="' . e($base . '?page=' . $i) . '">' . $i . '</a>';
    }
    if ($page < $pages) {
        $html .= '<a href="' . e($base . '?page=' . ($page + 1)) . '">&raquo;</a>';
    }
    return $html . '</nav>';
}

// ---------- Отображение звёзд ----------
function render_stars(int $n): string
{
    $n = max(0, min(5, $n));
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
