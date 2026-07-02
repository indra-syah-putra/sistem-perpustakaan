<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';

// Rate limiting
$max_attempts = 5;
$lockout_time = 15 * 60;
$ip = $_SERVER['REMOTE_ADDR'];
$attempts_key = 'login_attempts_' . $ip;

if (!isset($_SESSION[$attempts_key])) {
    $_SESSION[$attempts_key] = ['count' => 0, 'first_attempt' => time()];
}

$attempts = &$_SESSION[$attempts_key];

if ($attempts['count'] >= $max_attempts) {
    $elapsed = time() - $attempts['first_attempt'];
    if ($elapsed < $lockout_time) {
        $sisa = ceil(($lockout_time - $elapsed) / 60);
        $error = "Terlalu banyak percobaan. Coba lagi $sisa menit lagi.";
    } else {
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    verify_csrf();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $db = getConnection();
            $stmt = $db->prepare("SELECT * FROM user WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id_user' => $user['id_user'],
                    'username' => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role' => $user['role'],
                ];
                unset($_SESSION[$attempts_key]);
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            } else {
                $attempts['count']++;
                if ($attempts['count'] === 1) $attempts['first_attempt'] = time();
                $error = 'Username atau password salah';
            }
        } catch (PDOException $e) {
            $error = 'Koneksi database gagal';
        }
    } else {
        $error = 'Silakan isi username dan password';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="logo">
                <div class="icon-wrap">
                    <i class="bi bi-book-half"></i>
                </div>
                <h3>SIPerpus</h3>
                <p><?= APP_NAME ?></p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1.25rem;">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        var btn = this.querySelector('.btn-login');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
    });
    </script>
</body>
</html>
