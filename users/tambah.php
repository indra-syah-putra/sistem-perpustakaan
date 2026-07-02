<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'petugas';

    if (!$username || !$nama || !$password) {
        $error = 'Semua field harus diisi';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        try {
            $st = $db->prepare("INSERT INTO user (username, password, nama_lengkap, role) VALUES (:u, :p, :n, :r)");
            $st->execute([':u' => $username, ':p' => password_hash($password, PASSWORD_DEFAULT), ':n' => $nama, ':r' => $role]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'User berhasil ditambahkan'];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal: ' . ($e->getCode() == 23000 ? 'Username sudah ada' : $e->getMessage());
        }
    }
}

require_role(['admin']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-plus"></i> Tambah Pengguna</h4>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card-simple" style="max-width:480px;">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama_lengkap" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-select">
                    <option value="petugas">Petugas</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Simpan</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
