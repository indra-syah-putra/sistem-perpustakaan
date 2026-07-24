<?php
// ============================================================
// CONFIGURASI DATABASE
// ============================================================

require_once __DIR__ . '/../.env.php';

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'perpustakaan'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Auto-detect BASE_URL: jika .env kosong, hitung otomatis dari document root
$env_base = env('BASE_URL');
if ($env_base !== '' && $env_base !== null) {
    define('BASE_URL', $env_base);
} else {
    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
    $appRoot = str_replace('\\', '/', rtrim(dirname(__DIR__), '/\\'));
    define('BASE_URL', substr($appRoot, strlen($docRoot)));
}
define('APP_NAME', env('APP_NAME', 'Sistem Informasi Perpustakaan'));
define('MAX_PINJAM', (int)env('MAX_PINJAM', 3));
define('DENDA_PER_HARI', (int)env('DENDA_PER_HARI', 1000));
define('MAX_HARI_PINJAM', (int)env('MAX_HARI_PINJAM', 14));

// Koneksi database dengan PDO
function getConnection() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Koneksi database gagal. Silakan hubungi administrator.');
        }
    }
    
    return $db;
}

// Helper: format rupiah
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper: tanggal Indonesia
function tgl_indo($tanggal) {
    if ($tanggal == null) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $t = date_create($tanggal);
    $bln = $bulan[(int)$t->format('m')];
    return $t->format('d') . ' ' . $bln . ' ' . $t->format('Y');
}

// Helper: status badge
function status_badge($status) {
    $map = [
        'dipinjam' => 'warning',
        'dikembalikan' => 'success',
        'terlambat' => 'danger',
        'aktif' => 'success',
        'nonaktif' => 'secondary',
    ];
    $color = $map[$status] ?? 'secondary';
    return "<span class='badge bg-{$color}'>" . htmlspecialchars($status) . "</span>";
}

// Helper: ambil nilai pengaturan dari database
function setting($key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        try {
            $db = getConnection();
            $rows = $db->query("SELECT nama_setting, nilai_setting FROM pengaturan")->fetchAll();
            $cache = [];
            foreach ($rows as $r) $cache[$r['nama_setting']] = $r['nilai_setting'];
        } catch (PDOException $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

// ============================================================
// ROLE-BASED ACCESS CONTROL
// ============================================================

// Cek apakah user memiliki role tertentu
function has_role($roles) {
    if (!isset($_SESSION['user'])) return false;
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($_SESSION['user']['role'], $roles);
}

// Redirect jika user tidak punya akses
function require_role($roles) {
    if (!has_role($roles)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anda tidak memiliki akses ke halaman ini'];
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

// CSRF
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF token tidak valid');
    }
}
