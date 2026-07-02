<?php
require_once __DIR__ . '/../config/database.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ' . (empty(BASE_URL) ? '' : BASE_URL) . '/login.php');
    exit;
}

$db = getConnection();
$id = $_GET['id'] ?? 0;

try {
    // Cek apakah anggota punya peminjaman aktif
    $stmt = $db->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id AND status = 'dipinjam'");
    $stmt->execute([':id' => $id]);
    $aktif = $stmt->fetchColumn();
    
    if ($aktif > 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anggota tidak bisa dihapus karena masih memiliki peminjaman aktif'];
    } else {
        $stmt = $db->prepare("DELETE FROM anggota WHERE id_anggota = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anggota berhasil dihapus'];
    }
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . $e->getMessage()];
}

header('Location: index.php');
exit;
