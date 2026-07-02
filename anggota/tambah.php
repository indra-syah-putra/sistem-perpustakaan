<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getConnection();
$last = $db->query("SELECT MAX(CAST(SUBSTRING(no_anggota, 4) AS UNSIGNED)) AS last_no FROM anggota")->fetch();
$next_no = ($last['last_no'] ?? 0) + 1;
$auto_no = 'AGT' . str_pad($next_no, 3, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $stmt = $db->prepare("INSERT INTO anggota (no_anggota, nisn, kelas, nama, email, no_telp, alamat, tgl_daftar) VALUES (:no_anggota, :nisn, :kelas, :nama, :email, :no_telp, :alamat, :tgl_daftar)");
        $stmt->execute([
            ':no_anggota' => $_POST['no_anggota'],
            ':nisn' => $_POST['nisn'],
            ':kelas' => $_POST['kelas'] ?: null,
            ':nama' => $_POST['nama'],
            ':email' => $_POST['email'] ?: null,
            ':no_telp' => $_POST['no_telp'] ?: null,
            ':alamat' => $_POST['alamat'] ?: null,
            ':tgl_daftar' => $_POST['tgl_daftar'] ?: date('Y-m-d'),
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anggota berhasil ditambahkan'];
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Gagal: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-plus"></i> Tambah Anggota</h4>
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
                    <input type="text" name="no_anggota" class="form-control" value="<?= $auto_no ?>" readonly>
                </div>
                <div class="form-group">
                    <label>NISN <span class="text-danger">*</span></label>
                    <input type="text" name="nisn" class="form-control" placeholder="Nomor Induk Siswa" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nama <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="kelas" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label>No Telepon</label>
                    <input type="text" name="no_telp" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Tanggal Daftar</label>
                <input type="date" name="tgl_daftar" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control" rows="2"></textarea>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
