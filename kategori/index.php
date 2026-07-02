<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    verify_csrf();
    try {
        $db->prepare("INSERT INTO kategori (nama_kategori) VALUES (:n)")->execute([':n' => $_POST['nama_kategori']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori ditambahkan'];
        header('Location: index.php'); exit;
    } catch (PDOException $e) { $error = 'Gagal: ' . $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    verify_csrf();
    try {
        $db->prepare("UPDATE kategori SET nama_kategori=:n WHERE id_kategori=:id")->execute([':n' => $_POST['nama_kategori'], ':id' => $_POST['id_kategori']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori diupdate'];
        header('Location: index.php'); exit;
    } catch (PDOException $e) { $error = 'Gagal: ' . $e->getMessage(); }
}

if (isset($_GET['hapus'])) {
    try {
        $db->prepare("DELETE FROM kategori WHERE id_kategori = :id")->execute([':id' => $_GET['hapus']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori dihapus'];
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Kategori masih dipakai buku'];
    }
    header('Location: index.php'); exit;
}

require_once __DIR__ . '/../includes/header.php';

$kategori = $db->query("SELECT k.*, (SELECT COUNT(*) FROM buku_kategori WHERE id_kategori = k.id_kategori) AS jumlah FROM kategori k ORDER BY k.nama_kategori")->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-tags"></i> Kategori</h4>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1rem;">

<div class="card-simple">
    <div class="card-head">Tambah Kategori</div>
    <div class="card-body">
        <?php if (isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="nama_kategori" class="form-control" required>
            </div>
            <button type="submit" name="tambah" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah</button>
        </form>
    </div>
</div>

<div class="card-simple">
    <div class="card-head">Daftar Kategori</div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Nama</th><th>Buku</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($kategori as $k): ?>
                    <tr>
                        <td><?= htmlspecialchars($k['nama_kategori']) ?></td>
                        <td><span class="badge bg-primary"><?= $k['jumlah'] ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="edit(<?= $k['id_kategori'] ?>,'<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>')"><i class="bi bi-pencil"></i></button>
                            <a href="?hapus=<?= $k['id_kategori'] ?>" class="btn btn-sm btn-danger" data-confirm="Hapus kategori ini?"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>

<div class="modal" id="modalEdit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.3);z-index:999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:1.5rem;width:90%;max-width:380px;">
        <h5 style="margin-bottom:1rem;">Edit Kategori</h5>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_kategori" id="editId">
            <input type="hidden" name="edit" value="1">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="nama_kategori" id="editNama" class="form-control" required>
            </div>
            <div style="display:flex;gap:0.5rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalEdit').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function edit(id, nama) {
    document.getElementById('editId').value = id;
    document.getElementById('editNama').value = nama;
    document.getElementById('modalEdit').style.display = 'flex';
}
document.getElementById('modalEdit').onclick = function(e) { if(e.target==this) this.style.display='none'; }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
