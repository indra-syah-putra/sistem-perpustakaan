<?php
require_once __DIR__ . '/../config/database.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ' . (empty(BASE_URL) ? '' : BASE_URL) . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verify_csrf();

$db = getConnection();
$id = (int)($_POST['id'] ?? 0);

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id");
    $stmt->execute([':id' => $id]);
    $riwayat = $stmt->fetchColumn();
    
    if ($riwayat > 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anggota tidak bisa dihapus karena masih memiliki riwayat peminjaman'];
    } else {
        $stmt = $db->prepare("DELETE FROM anggota WHERE id_anggota = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anggota berhasil dihapus'];
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: data masih digunakan oleh record lain'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus anggota'];
    }
}

header('Location: index.php');
exit;
