<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../controllers/AuthController.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth   = new AuthController();
    $result = $auth->login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');

    if ($result['ok']) {
        $user = $_SESSION['user'];

        if ($user['role'] !== 'admin') {
            // Login berhasil tapi bukan admin — tolak
            session_destroy();
            $error = 'Akun ini bukan administrator.';
        } else {
            // Salin ke sesi admin
            $_SESSION['admin'] = $user;
            header('Location: index.php');
            exit;
        }
    } else {
        $error = $result['msg'];

        // Pastikan pesan lebih spesifik untuk admin
        if ($error === 'Email atau password salah.') {
            $error = 'Email/password salah atau bukan akun admin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — SIGAP Jalan</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --blue-dark: #0c2461;
    --blue-mid: #1d6fc4;
}

body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #060d1f 0%, var(--blue-dark) 40%, var(--blue-mid) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-wrap {
    width: 100%;
    max-width: 420px;
}

.brand {
    text-align: center;
    margin-bottom: 32px;
    color: #fff;
}

.brand-icon {
    width: 72px;
    height: 72px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    margin-bottom: 14px;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.brand h1 {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: 2px;
    margin-bottom: 4px;
}

.brand p {
    font-size: .82rem;
    color: rgba(255, 255, 255, 0.6);
}

.admin-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: .72rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    margin-top: 8px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.card {
    background: #fff;
    border-radius: 24px;
    padding: 36px 32px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);
}

.card h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 6px;
}

.card p {
    font-size: .82rem;
    color: #6b7280;
    margin-bottom: 24px;
}

.field {
    margin-bottom: 18px;
}

.field label {
    display: block;
    font-size: .78rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 6px;
    letter-spacing: .5px;
    text-transform: uppercase;
}

.input-wrap {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 17px;
}

.field input {
    width: 100%;
    padding: 13px 14px 13px 44px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: .92rem;
    font-family: 'Inter', sans-serif;
    background: #f9fafb;
    color: #1a1a2e;
    transition: border-color .2s;
}

.field input:focus {
    outline: none;
    border-color: var(--blue-mid);
    background: #fff;
}

.btn-login {
    width: 100%;
    padding: 14px;
    margin-top: 6px;
    background: linear-gradient(135deg, var(--blue-dark), var(--blue-mid));
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: .98rem;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 6px 20px rgba(29, 111, 196, 0.4);
    transition: transform .15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-login:hover {
    transform: translateY(-2px);
}

.alert-danger {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 11px 14px;
    font-size: .83rem;
    font-weight: 600;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.divider {
    border: none;
    border-top: 1px solid #f3f4f6;
    margin: 24px 0 18px;
}

.back-link {
    text-align: center;
    font-size: .8rem;
    color: #9ca3af;
}

.back-link a {
    color: var(--blue-dark);
    font-weight: 600;
    text-decoration: none;
}

.hint {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 11px 14px;
    font-size: .78rem;
    color: #1e40af;
    margin-top: 16px;
}

.hint strong {
    display: block;
    margin-bottom: 2px;
}
</style>
</head>
<body>
<div class="login-wrap">
    <div class="brand">
        <div class="brand-icon">🛡️</div>
        <h1>SIGAP JALAN</h1>
        <p>Sistem Informasi Pengaduan Jalan Rusak</p>
        <div class="admin-badge">⚙️ Admin Panel</div>
    </div>
    <div class="card">
        <h2>Masuk sebagai Admin</h2>
        <p>Gunakan akun administrator untuk mengakses panel ini.</p>
        <?php if ($error): ?>
        <div class="alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="field">
                <label>Email Admin</label>
                <div class="input-wrap">
                    <span class="input-icon">📧</span>
                    <input type="email" name="email" placeholder="admin@sigap.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn-login">🛡️ Masuk ke Admin Panel</button>
        </form>
        <hr class="divider">
        <div class="back-link">Bukan admin? <a href="../login.php">← Kembali ke halaman warga</a></div>
        <div class="hint">
            <strong>💡 Akun demo:</strong>
            Email: admin@sigap.id · Password: password
        </div>
    </div>
</div>
</body>
</html>