<?php
require_once __DIR__ . '/../config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
}
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    return $pdo;
}
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_input(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function check_csrf(): void { if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('طلب غير صالح.'); } }
function user_id(): ?int { return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; }
function require_login(): void { if (!user_id()) { header('Location: login.php'); exit; } }
function redirect(string $url): never { header('Location: '.$url); exit; }
function site_by_id(int $id): array|false { $q=db()->prepare('SELECT * FROM sites WHERE id=? AND user_id=?');$q->execute([$id,user_id()]);return $q->fetch(); }
function site_slug(string $value): string { $slug=strtolower(trim($value));$slug=preg_replace('/[^a-z0-9-]+/','-',$slug);return trim((string)$slug,'-'); }
function form_value(string $key, array $site=[]): string { return e((string)($_POST[$key] ?? $site[$key] ?? '')); }
function header_html(string $title): void { ?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> | لانش كِت</title><link rel="stylesheet" href="assets/css/style.css"><script src="assets/js/app.js" defer></script></head><body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">✳</span> لانش كِت <small>LAUNCHKIT</small></a><nav aria-label="التنقل الرئيسي"><a href="index.php">الرئيسية</a><a href="templates.php">القوالب</a><a href="about.php">كيف يعمل؟</a><?php if (user_id()): ?><a href="dashboard.php">لوحة التحكم</a><a href="logout.php">خروج</a><?php else: ?><a href="login.php">دخول</a><a class="nav-button" href="register.php">ابدأ الآن</a><?php endif; ?></nav></header><main>
<?php }
function footer_html(): void { ?></main><footer class="footer"><span>© <?= date('Y') ?> لانش كِت — اصنع حضورك الرقمي.</span><span>مشروع CSC457</span></footer></body></html><?php }
