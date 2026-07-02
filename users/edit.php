<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getConnection();
$error = '';
$id = $_GET['id'] ?? 0;

$st = $db->prepare("SELECT * FROM user WHERE id_user = :id");
$st->execute([':id' => $id]);
$u = $st->fetch();

if (!$u) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'User tidak ditemukan'];
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'petugas';

    if (!$nama) {
        $error = 'Nama lengkap harus diisi';
    } else {
        $sql = "UPDATE user SET nama_lengkap = :n, role = :r" . ($password ? ", password = :p" : "") . " WHERE id_user = :id";
        $params = [':n' => $nama, ':r' => $role, ':id' => $id];
        if ($password) {
            if (strlen($password) < 6) { $error = 'Password minimal 6 karakter'; } else { $params[':p'] = password_hash($password, PASSWORD_DEFAULT); }
        }
        if (!$error) {
            $db->prepare($sql)->execute($params);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'User berhasil diupdate'];
            header('Location: index.php');
            exit;
        }
    }
}

require_role(['admin']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-pencil"></i> Edit Pengguna</h4>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card-simple" style="max-width:480px;">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" disabled style="background:#f3f4f6;">
            </div>
            <div class="form-group">
                <label>Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($u['nama_lengkap']) ?>" required>
            </div>
            <div class="form-group">
                <label>Password <small style="color:#9ca3af;">(kosongkan jika tidak diubah)</small></label>
                <input type="password" name="password" class="form-control" minlength="6">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-select">
                    <option value="petugas" <?= $u['role']=='petugas'?'selected':'' ?>>Petugas</option>
                    <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Simpan</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
