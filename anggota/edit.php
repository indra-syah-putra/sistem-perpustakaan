<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$db = getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT a.*, k.nama_kelas FROM anggota a LEFT JOIN kelas k ON a.id_kelas = k.id_kelas WHERE a.id_anggota = :id");
$stmt->execute([':id' => $id]);
$a = $stmt->fetch();

if (!$a) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anggota tidak ditemukan'];
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nisn = trim($_POST['nisn'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    if (!$nisn || !$nama) {
        $error = 'NISN dan Nama harus diisi';
    } else {
        try {
            $stmt = $db->prepare("UPDATE anggota SET nisn=:nisn, id_kelas=:id_kelas, nama=:nama, email=:email, no_telp=:no_telp, alamat=:alamat, status=:status WHERE id_anggota=:id");
            $stmt->execute([
                ':nisn' => $nisn,
                ':id_kelas' => $_POST['id_kelas'] ?: null,
                ':nama' => $nama,
                ':email' => $_POST['email'] ?: null,
                ':no_telp' => $_POST['no_telp'] ?: null,
                ':alamat' => $_POST['alamat'] ?: null,
                ':status' => $_POST['status'],
                ':id' => $id,
            ]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anggota berhasil diupdate'];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$daftar_kelas = $db->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY tingkatan")->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-pencil"></i> Edit Anggota</h4>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="card-simple" style="max-width:600px;">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>No Anggota</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($a['no_anggota']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>NISN</label>
                    <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($a['nisn']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nama <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($a['nama']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="id_kelas" class="form-select">
                        <option value="">--</option>
                        <?php foreach ($daftar_kelas as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>" <?= $a['id_kelas']==$k['id_kelas']?'selected':'' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($a['email']) ?>">
                </div>
                <div class="form-group">
                    <label>No Telepon</label>
                    <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($a['no_telp']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="aktif" <?= $a['status']=='aktif'?'selected':'' ?>>Aktif</option>
                    <option value="nonaktif" <?= $a['status']=='nonaktif'?'selected':'' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($a['alamat']) ?></textarea>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
