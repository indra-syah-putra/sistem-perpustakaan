<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_role(['admin']);

$db = getConnection();

// Auto-buat tabel pengaturan jika belum ada (hanya sekali)
if (!$db->query("SHOW TABLES LIKE 'pengaturan'")->fetch()) {
    $db->exec("CREATE TABLE pengaturan (
        id_setting INT AUTO_INCREMENT PRIMARY KEY,
        nama_setting VARCHAR(50) NOT NULL UNIQUE,
        nilai_setting VARCHAR(255) NOT NULL,
        deskripsi VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $db->exec("INSERT INTO pengaturan (nama_setting, nilai_setting, deskripsi) VALUES
        ('denda_per_hari', '1000', 'Denda keterlambatan per hari (Rupiah)'),
        ('max_pinjam', '3', 'Maksimal buku yang bisa dipinjam per anggota'),
        ('max_hari_pinjam', '14', 'Maksimal lama peminjaman (hari)')");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fields = ['denda_per_hari', 'max_pinjam', 'max_hari_pinjam'];
    $errors = [];

    foreach ($fields as $f) {
        $val = $_POST[$f] ?? '';
        if ($val === '' || !is_numeric($val) || (int)$val < 0) {
            $errors[] = ucwords(str_replace('_', ' ', $f)) . ' harus diisi dengan angka valid';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE pengaturan SET nilai_setting = :nilai WHERE nama_setting = :nama");
            foreach ($fields as $f) {
                $stmt->execute([':nilai' => (int)$_POST[$f], ':nama' => $f]);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pengaturan berhasil disimpan'];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan. Silakan coba lagi.';
        }
    } else {
        $error = $errors;
    }
}

require_once __DIR__ . '/../includes/header.php';

$settings = $db->query("SELECT nama_setting, nilai_setting, deskripsi FROM pengaturan ORDER BY id_setting")->fetchAll();
$vals = [];
foreach ($settings as $s) {
    $vals[$s['nama_setting']] = $s;
}
?>

<div class="page-header">
    <h4><i class="bi bi-gear"></i> Pengaturan Aplikasi</h4>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php if (is_array($error)): ?><?php foreach ($error as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?><?php else: ?><?= htmlspecialchars($error) ?><?php endif; ?></div>
<?php endif; ?>

<div class="card-simple" style="max-width:550px;">
    <div class="card-head">Konfigurasi Umum</div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Denda per Hari (Rp)</label>
                <input type="number" name="denda_per_hari" class="form-control"
                    value="<?= htmlspecialchars($vals['denda_per_hari']['nilai_setting'] ?? DENDA_PER_HARI) ?>" min="0" required>
                <small style="color:#6b7280;"><?= htmlspecialchars($vals['denda_per_hari']['deskripsi'] ?? '') ?></small>
            </div>
            <div class="form-group">
                <label>Maksimal Buku Dipinjam per Anggota</label>
                <input type="number" name="max_pinjam" class="form-control"
                    value="<?= htmlspecialchars($vals['max_pinjam']['nilai_setting'] ?? MAX_PINJAM) ?>" min="1" required>
                <small style="color:#6b7280;"><?= htmlspecialchars($vals['max_pinjam']['deskripsi'] ?? '') ?></small>
            </div>
            <div class="form-group">
                <label>Maksimal Lama Pinjam (Hari)</label>
                <input type="number" name="max_hari_pinjam" class="form-control"
                    value="<?= htmlspecialchars($vals['max_hari_pinjam']['nilai_setting'] ?? MAX_HARI_PINJAM) ?>" min="1" required>
                <small style="color:#6b7280;"><?= htmlspecialchars($vals['max_hari_pinjam']['deskripsi'] ?? '') ?></small>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Pengaturan</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
