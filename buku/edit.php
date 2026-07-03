<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM buku WHERE id_buku = :id");
$stmt->execute([':id' => $id]);
$b = $stmt->fetch();

if (!$b) { $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Buku tidak ditemukan']; header('Location: index.php'); exit; }

$kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$stmtKat = $db->prepare("SELECT id_kategori FROM buku_kategori WHERE id_buku = :id");
$stmtKat->execute([':id' => $id]);
$selected = $stmtKat->fetchAll(\PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE buku SET judul=:judul, pengarang=:pengarang, penerbit=:penerbit, tahun_terbit=:tahun, isbn=:isbn, stok=:stok WHERE id_buku=:id");
        $stmt->execute([
            ':judul' => $_POST['judul'], ':pengarang' => $_POST['pengarang'],
            ':penerbit' => $_POST['penerbit'] ?: null, ':tahun' => $_POST['tahun_terbit'] ?: null,
            ':isbn' => $_POST['isbn'] ?: null, ':stok' => $_POST['stok'] ?? 0, ':id' => $id,
        ]);
        $db->prepare("DELETE FROM buku_kategori WHERE id_buku = :id")->execute([':id' => $id]);
        if (!empty($_POST['kategori'])) {
            $sk = $db->prepare("INSERT INTO buku_kategori (id_buku, id_kategori) VALUES (:b, :k)");
            foreach ($_POST['kategori'] as $k) $sk->execute([':b' => $id, ':k' => $k]);
        }
        $db->commit();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Buku berhasil diupdate'];
        header('Location: index.php');
        exit;
    } catch (Exception $e) { $db->rollBack(); $error = 'Gagal: ' . $e->getMessage(); }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-pencil"></i> Edit Buku</h4>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="card-simple" style="max-width:650px;">
    <div class="card-body">
        <?php if (isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Judul <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($b['judul']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Pengarang <span class="text-danger">*</span></label>
                    <input type="text" name="pengarang" class="form-control" value="<?= htmlspecialchars($b['pengarang']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" class="form-control" value="<?= htmlspecialchars($b['penerbit']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <select name="tahun_terbit" class="form-select">
                        <option value="">--</option>
                        <?php for($t=date('Y');$t>=1980;$t--): ?>
                        <option value="<?= $t ?>" <?= $b['tahun_terbit']==$t?'selected':'' ?>><?= $t ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($b['isbn']) ?>">
                </div>
                <div class="form-group">
                    <label>Stok <span class="text-danger">*</span></label>
                    <input type="number" name="stok" class="form-control" value="<?= $b['stok'] ?>" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <div class="checkbox-grid">
                    <?php foreach ($kategori_list as $k): ?>
                    <label><input type="checkbox" name="kategori[]" value="<?= $k['id_kategori'] ?>" <?= in_array($k['id_kategori'], $selected)?'checked':'' ?>> <?= htmlspecialchars($k['nama_kategori']) ?></label>
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
