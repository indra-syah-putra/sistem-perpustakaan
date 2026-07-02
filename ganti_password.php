<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$db = getConnection();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $lama = $_POST['password_lama'] ?? '';
    $baru = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['password_konfirmasi'] ?? '';

    if (!$lama || !$baru || !$konfirmasi) {
        $error = 'Semua field harus diisi';
    } elseif ($baru !== $konfirmasi) {
        $error = 'Password baru tidak cocok';
    } elseif (strlen($baru) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        $st = $db->prepare("SELECT password FROM user WHERE id_user = :id");
        $st->execute([':id' => $user['id_user']]);
        $row = $st->fetch();
        if (!password_verify($lama, $row['password'])) {
            $error = 'Password lama salah';
        } else {
            $up = $db->prepare("UPDATE user SET password = :p WHERE id_user = :id");
            $up->execute([':p' => password_hash($baru, PASSWORD_DEFAULT), ':id' => $user['id_user']]);
            $success = 'Password berhasil diubah';
        }
    }
}
?>

<div class="page-header">
    <h4><i class="bi bi-key"></i> Ganti Password</h4>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div class="card-simple" style="max-width:480px;">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Password Lama <span class="text-danger">*</span></label>
                <input type="password" name="password_lama" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password Baru <span class="text-danger">*</span></label>
                <input type="password" name="password_baru" class="form-control" minlength="6" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password Baru <span class="text-danger">*</span></label>
                <input type="password" name="password_konfirmasi" class="form-control" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
