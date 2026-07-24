<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$db = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $lama = $_POST['password_lama'] ?? '';
    $baru = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['password_konfirmasi'] ?? '';

    if (!$lama || !$baru || !$konfirmasi) {
        $_SESSION['pw_error'] = 'Semua field harus diisi';
    } elseif ($baru !== $konfirmasi) {
        $_SESSION['pw_error'] = 'Password baru tidak cocok';
    } elseif (strlen($baru) < 6) {
        $_SESSION['pw_error'] = 'Password minimal 6 karakter';
    } else {
        $st = $db->prepare("SELECT password FROM user WHERE id_user = :id");
        $st->execute([':id' => $_SESSION['user']['id_user']]);
        $row = $st->fetch();
        if (!password_verify($lama, $row['password'])) {
            $_SESSION['pw_error'] = 'Password lama salah';
        } else {
            $up = $db->prepare("UPDATE user SET password = :p WHERE id_user = :id");
            $up->execute([':p' => password_hash($baru, PASSWORD_DEFAULT), ':id' => $_SESSION['user']['id_user']]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password berhasil diubah'];
            header('Location: ganti_password.php');
            exit;
        }
    }
    header('Location: ganti_password.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
$success = isset($_SESSION['flash']) && $_SESSION['flash']['type'] === 'success' ? $_SESSION['flash']['message'] : '';
$error = $_SESSION['pw_error'] ?? '';
unset($_SESSION['pw_error']);
?>

<div class="pw-top-bottom">
    <div class="pw-banner">
        <div class="pw-banner-bg">
            <svg class="pw-banner-svg" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="70" cy="70" r="55" fill="rgba(255,255,255,0.07)"/>
                <path d="M70 25L100 40V70C100 90 87 103 70 110C53 103 40 90 40 70V40L70 25Z" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-linejoin="round"/>
                <rect x="60" y="68" width="20" height="18" rx="3" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
                <path d="M65 68V64C65 62.3 66.3 61 68 61H72C73.7 61 75 62.3 75 64V68" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="70" cy="77" r="2" fill="rgba(255,255,255,0.7)"/>
                <line x1="70" y1="79" x2="70" y2="82" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="pw-banner-content">
            <h3>Ganti Password</h3>
            <p>Perbarui password akun Anda secara berkala untuk menjaga keamanan data.</p>
        </div>
    </div>

    <div class="pw-form-area">
        <?php if ($success): ?>
            <div class="pw-alert pw-alert-ok"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="pw-alert pw-alert-err"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="pw-form">
            <?= csrf_field() ?>
            <div class="pw-field">
                <label>Password Lama</label>
                <div class="pw-input-wrap">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password_lama" placeholder="Masukkan password lama" required>
                </div>
            </div>
            <div class="pw-row-2">
                <div class="pw-field">
                    <label>Password Baru</label>
                    <div class="pw-input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="password_baru" placeholder="Masukkan password baru" minlength="6" required>
                    </div>
                </div>
                <div class="pw-field">
                    <label>Konfirmasi Password Baru</label>
                    <div class="pw-input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="password_konfirmasi" placeholder="Ulangi password baru" minlength="6" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="pw-submit"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
        </form>
    </div>
</div>

<style>
.pw-top-bottom {
    max-width: 560px;
    margin: 1.5rem auto 0;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06), 0 0 0 1px rgba(0,0,0,0.03);
    overflow: hidden;
    animation: pwFadeUp 0.35s ease-out;
}

@keyframes pwFadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.pw-banner {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    padding: 2rem 2rem 1.75rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.pw-banner-bg {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.5;
}

.pw-banner-svg {
    width: 160px;
    height: 160px;
    animation: pwFloat 3s ease-in-out infinite;
}

@keyframes pwFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

.pw-banner-content {
    position: relative;
    z-index: 1;
}

.pw-banner-content h3 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 0.3rem;
}

.pw-banner-content p {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.75);
    max-width: 360px;
    margin: 0 auto;
    line-height: 1.5;
}

.pw-form-area {
    padding: 1.75rem 2rem 2rem;
}

.pw-alert {
    padding: 0.6rem 0.85rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    animation: pwAlertIn 0.25s ease-out;
}

@keyframes pwAlertIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.pw-alert-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.pw-alert-err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.pw-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.pw-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.pw-field label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.35rem;
}

.pw-input-wrap {
    position: relative;
}

.pw-input-wrap i {
    position: absolute;
    left: 0.8rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.95rem;
    color: #c4c0d4;
    pointer-events: none;
    transition: color 0.2s;
}

.pw-input-wrap input {
    width: 100%;
    padding: 0.65rem 0.75rem 0.65rem 2.5rem;
    font-size: 0.85rem;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    outline: none;
    background: #fafafa;
    font-family: inherit;
    color: #1f2937;
    transition: all 0.2s;
}

.pw-input-wrap input::placeholder {
    color: #c4c4c4;
}

.pw-input-wrap input:focus {
    border-color: #4f46e5;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.08);
}

.pw-input-wrap:focus-within i {
    color: #4f46e5;
}

.pw-submit {
    width: 100%;
    padding: 0.7rem;
    margin-top: 0.25rem;
    font-size: 0.85rem;
    font-weight: 600;
    background: #4f46e5;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}

.pw-submit:hover {
    background: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}

.pw-submit:active {
    transform: translateY(0);
}

@media (max-width: 540px) {
    .pw-row-2 { grid-template-columns: 1fr; }
    .pw-form-area { padding: 1.25rem 1.5rem 1.5rem; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
