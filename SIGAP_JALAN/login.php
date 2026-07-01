<?php
require_once __DIR__ . '/controllers/AuthController.php';

$auth = new AuthController();

if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$mode    = $_GET['mode'] ?? 'login';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $res = $auth->login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');

        if ($res['ok']) {
            header('Location: index.php');
            exit;
        }

        $error = $res['msg'];
    } elseif ($action === 'register') {
        $res = $auth->register(
            trim($_POST['nama'] ?? ''),
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? '',
            $_POST['konfirm'] ?? ''
        );

        if ($res['ok']) {
            $success = $res['msg'];
            $mode = 'login';
        } else {
            $error = $res['msg'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAP Jalan — <?= $mode === 'register' ? 'Daftar' : 'Masuk' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-bg: #eff6ff;
            --white: #fff;
            --gray2: #bfdbfe;
            --text: #0f172a;
            --text-muted: #475569;
            --danger: #dc2626;
            --success: #16a34a;
        }

        body {
            background: linear-gradient(160deg, #0c2461 0%, #1d6fc4 55%, #38bdf8 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }

        .card-wrap {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.09);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 28px;
            padding: 40px 32px;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.25);
        }

        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 28px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            font-size: 2.5rem;
        }

        .welcome-text {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .welcome-sub {
            font-size: .85rem;
            color: rgba(255, 255, 255, 0.65);
        }

        .tab-row {
            display: flex;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 24px;
        }

        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-size: .85rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.6);
            transition: all .2s;
        }

        .tab.active {
            background: #fff;
            color: var(--primary);
        }

        .field-group {
            margin-bottom: 16px;
        }

        .field-label {
            font-size: .78rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 6px;
            letter-spacing: .5px;
            display: block;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: rgba(255, 255, 255, 0.4);
        }

        .field-input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            background: rgba(255, 255, 255, 0.12);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: .92rem;
            font-family: 'Inter', sans-serif;
            transition: .2s;
        }

        .field-input::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }

        .field-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.18);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #fff;
            color: var(--primary);
            border: none;
            border-radius: 14px;
            font-size: .98rem;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            letter-spacing: .5px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            transition: .15s;
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(124, 58, 237, .35);
        }

        .register-link {
            text-align: center;
            margin-top: 18px;
            font-size: .82rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .register-link a {
            color: #93c5fd;
            font-weight: 600;
            text-decoration: none;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: .82rem;
            font-weight: 600;
        }

        .alert-danger {
            background: rgba(220, 38, 38, .2);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, .3);
        }

        .alert-success {
            background: rgba(255, 107, 53, .15);
            color: #ffb38a;
            border: 1px solid rgba(255, 107, 53, .3);
        }

        .back-home {
            display: block;
            text-align: center;
            margin-top: 14px;
            font-size: .8rem;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
        }

        .back-home:hover {
            color: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>
<body>
    <div class="card-wrap">
        <div class="logo-wrap">
            <div class="logo-icon">🚧</div>
            <div class="welcome-text">SIGAP JALAN</div>
            <div class="welcome-sub">Sistem Informasi Pelaporan Jalan</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="tab-row">
            <div class="tab <?= $mode !== 'register' ? 'active' : '' ?>" onclick="location.href='?mode=login'">Masuk</div>
            <div class="tab <?= $mode === 'register' ? 'active' : '' ?>" onclick="location.href='?mode=register'">Daftar</div>
        </div>

        <?php if ($mode === 'register'): ?>
            <form method="POST" action="?mode=register">
                <input type="hidden" name="action" value="register">
                
                <div class="field-group">
                    <label class="field-label">NAMA LENGKAP</label>
                    <div class="input-wrap">
                        <span class="input-icon">👤</span>
                        <input type="text" name="nama" class="field-input" placeholder="Nama lengkap Anda"
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">EMAIL</label>
                    <div class="input-wrap">
                        <span class="input-icon">✉️</span>
                        <input type="email" name="email" class="field-input" placeholder="email@contoh.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">PASSWORD</label>
                    <div class="input-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" class="field-input" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">KONFIRMASI PASSWORD</label>
                    <div class="input-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="konfirm" class="field-input" placeholder="Ulangi password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Daftar Sekarang</button>
            </form>
            <div class="register-link">Sudah punya akun? <a href="?mode=login">Masuk</a></div>
        <?php else: ?>
            <form method="POST" action="?mode=login">
                <input type="hidden" name="action" value="login">
                
                <div class="field-group">
                    <label class="field-label">EMAIL</label>
                    <div class="input-wrap">
                        <span class="input-icon">✉️</span>
                        <input type="email" name="email" class="field-input" placeholder="Masukkan email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="field-group">
                    <label class="field-label">PASSWORD</label>
                    <div class="input-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" class="field-input" placeholder="Masukkan password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Masuk</button>
            </form>
            <div class="register-link">Belum punya akun? <a href="?mode=register">Daftar</a></div>
        <?php endif; ?>
        
        <a href="index.php" class="back-home">← Kembali ke Beranda (tanpa login)</a>
    </div>
</body>
</html>