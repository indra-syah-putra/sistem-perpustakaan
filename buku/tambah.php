<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$db = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $judul = trim($_POST['judul'] ?? '');
    $pengarang = trim($_POST['pengarang'] ?? '');
    $stok = (int)($_POST['stok'] ?? 0);
    if (!$judul || !$pengarang) {
        $error = 'Judul dan Pengarang harus diisi';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh negatif';
    } else {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, stok) VALUES (:judul, :pengarang, :penerbit, :tahun, :isbn, :stok)");
            $stmt->execute([
                ':judul' => $judul,
                ':pengarang' => $pengarang,
                ':penerbit' => $_POST['penerbit'] ?: null,
                ':tahun' => $_POST['tahun_terbit'] ?: null,
                ':isbn' => $_POST['isbn'] ?: null,
                ':stok' => $stok,
            ]);
            $id_buku = $db->lastInsertId();
            if (!empty($_POST['kategori'])) {
                $sk = $db->prepare("INSERT INTO buku_kategori (id_buku, id_kategori) VALUES (:b, :k)");
                foreach ($_POST['kategori'] as $k) $sk->execute([':b' => $id_buku, ':k' => (int)$k]);
            }
            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Buku berhasil ditambahkan'];
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-book-plus"></i> Tambah Buku</h4>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="card-simple" style="max-width:650px;">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Judul <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Pengarang <span class="text-danger">*</span></label>
                    <input type="text" name="pengarang" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <select name="tahun_terbit" class="form-select">
                        <option value="">--</option>
                        <?php for($t=date('Y');$t>=1980;$t--): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" class="form-control">
                </div>
                <div class="form-group">
                    <label>Stok <span class="text-danger">*</span></label>
                    <input type="number" name="stok" class="form-control" value="1" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <div class="checkbox-grid">
                    <?php foreach ($kategori_list as $k): ?>
                    <label><input type="checkbox" name="kategori[]" value="<?= $k['id_kategori'] ?>"> <?= htmlspecialchars($k['nama_kategori']) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
