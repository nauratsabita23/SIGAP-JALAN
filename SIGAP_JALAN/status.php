<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/LaporanController.php';

$auth = new AuthController();
$auth->requireLogin();
$me = $auth->getUser();

$laporanCtrl = new LaporanController();
$myLaporan   = $laporanCtrl->getByUserId($me['id']);

// Detail single laporan
$detail   = null;
$timeline = [];

if (isset($_GET['id'])) {
    $detail = $laporanCtrl->getById((int)$_GET['id']);

    if ($detail && (int)$detail['user_id'] !== (int)$me['id'] && $me['role'] !== 'admin') {
        $detail = null; // bukan milik user ini
    }

    if ($detail) {
        $timeline = $laporanCtrl->getStatusTimeline($detail);
    }
}

function badgeTingkat(string $t): string
{
    return match($t) {
        'berat'  => '<span class="badge badge-berat">🔴 Berat</span>',
        'sedang' => '<span class="badge badge-sedang">🟡 Sedang</span>',
        'ringan' => '<span class="badge badge-ringan">🟢 Ringan</span>',
        default  => '<span class="badge badge-sedang">Sedang</span>',
    };
}

function badgeStatus(string $s): string
{
    return match($s) {
        'menunggu' => '<span class="badge badge-menunggu">⏳ Menunggu</span>',
        'diproses' => '<span class="badge badge-diproses">⚙️ Diproses</span>',
        'selesai'  => '<span class="badge badge-selesai">✅ Selesai</span>',
        'ditolak'  => '<span class="badge badge-ditolak">❌ Ditolak</span>',
        default    => '',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAP Jalan — Laporan Saya</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0284c7;
            --primary-mid: #38bdf8;
            --primary-light: #e0f2fe;
            --bg: #f0f8ff;
            --white: #fff;
            --border: #bae6fd;
            --text: #0c2a4a;
            --muted: #4a7fa5;
            --radius: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .topbar {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .topbar-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            text-decoration: none;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-btn {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: 'Inter', sans-serif;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            text-decoration: none;
        }

        .topbar-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .wrap {
            max-width: 760px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .page-title {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .page-sub {
            font-size: .83rem;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            margin-bottom: 14px;
        }

        .card-header {
            padding: 14px 18px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: .92rem;
            font-weight: 700;
        }

        .laporan-item {
            display: flex;
            gap: 14px;
            padding: 14px 18px;
            border-bottom: 1px solid #f9fafb;
            align-items: flex-start;
            text-decoration: none;
            color: inherit;
            transition: .15s;
        }

        .laporan-item:last-child {
            border-bottom: none;
        }

        .laporan-item:hover {
            background: #fafafa;
        }

        .lap-thumb {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .lap-thumb-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: var(--green-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .lap-info {
            flex: 1;
        }

        .lap-jenis {
            font-size: .9rem;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .lap-lokasi {
            font-size: .78rem;
            color: var(--muted);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .lap-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 600;
        }

        .badge-menunggu {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-diproses {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-selesai {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-ditolak {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-berat {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-sedang {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-ringan {
            background: #d1fae5;
            color: #065f46;
        }

        .lap-time {
            font-size: .7rem;
            color: var(--muted);
            white-space: nowrap;
        }

        /* Detail */
        .detail-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            margin-bottom: 16px;
        }

        .detail-banner {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
            padding: 20px;
            color: #fff;
        }

        .detail-banner h2 {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .detail-banner p {
            font-size: .8rem;
            opacity: .75;
            margin-top: 3px;
        }

        .detail-body {
            padding: 18px;
        }

        .detail-photo {
            width: 100%;
            max-height: 280px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .info-row {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: .78rem;
            font-weight: 700;
            color: var(--muted);
            width: 110px;
            flex-shrink: 0;
        }

        .info-val {
            font-size: .85rem;
            color: var(--text);
            flex: 1;
        }

        /* Timeline */
        .timeline {
            padding: 18px;
        }

        .tl-item {
            display: flex;
            gap: 14px;
            position: relative;
            padding-bottom: 20px;
        }

        .tl-item:last-child {
            padding-bottom: 0;
        }

        .tl-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 30px;
            width: 2px;
            bottom: 0;
            background: #e0e0e0;
        }

        .tl-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 700;
            flex-shrink: 0;
            z-index: 1;
            background: #e0e0e0;
            color: #999;
        }

        .tl-dot.done {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
            color: #fff;
        }

        .tl-dot.active {
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(3, 105, 161, 0.25);
        }

        .tl-info {
            flex: 1;
            padding-top: 4px;
        }

        .tl-label {
            font-size: .88rem;
            font-weight: 700;
            color: var(--text);
        }

        .tl-desc {
            font-size: .78rem;
            color: var(--muted);
            margin-top: 3px;
        }

        .tl-dot.pending {
            background: #f3f4f6;
            color: #9ca3af;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="index.php" class="topbar-logo">🚧 SIGAP Jalan</a>
        <div class="topbar-right">
            <span style="font-size: .8rem; color: rgba(255, 255, 255, 0.7);"><?= htmlspecialchars($me['nama']) ?></span>
            <a href="logout.php" class="topbar-btn">Keluar</a>
        </div>
    </div>

    <div class="wrap">

        <?php if ($detail): ?>
            <a href="status.php" class="btn-back">← Semua Laporan Saya</a>
            
            <div class="detail-card">
                <div class="detail-banner">
                    <h2><?= htmlspecialchars($detail['jenis_kerusakan']) ?></h2>
                    <p>📍 <?= htmlspecialchars($detail['lokasi_nama'] ?: 'Lokasi tidak tersedia') ?></p>
                </div>
                <div class="detail-body">
                    <?php if ($detail['foto']): ?>
                        <img src="uploads/<?= htmlspecialchars($detail['foto']) ?>" class="detail-photo" alt="Foto">
                    <?php endif; ?>
                    
                    <div class="info-row">
                        <div class="info-label">Tingkat</div>
                        <div class="info-val"><?= badgeTingkat($detail['tingkat']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status</div>
                        <div class="info-val"><?= badgeStatus($detail['status']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Lokasi</div>
                        <div class="info-val"><?= htmlspecialchars($detail['lokasi_nama'] ?: '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Deskripsi</div>
                        <div class="info-val"><?= nl2br(htmlspecialchars($detail['deskripsi'] ?: '-')) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Dilaporkan</div>
                        <div class="info-val"><?= date('d M Y H:i', strtotime($detail['created_at'])) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">📈 Perkembangan Laporan</div>
                </div>
                <div class="timeline">
                    <?php foreach ($timeline as $step): ?>
                        <div class="tl-item">
                            <div class="tl-dot <?= $step['active'] ? 'active' : ($step['done'] ? 'done' : 'pending') ?>">
                                <?= $step['done'] ? '✓' : $step['icon'] ?>
                            </div>
                            
                            <div class="tl-info">
                                <div class="tl-label" style="<?= !$step['done'] ? 'color: #9ca3af' : '' ?>">
                                    <?= $step['label'] ?>
                                </div>
                                <?php if ($step['done']): ?>
                                    <div class="tl-desc"><?= $step['desc'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($myLaporan)): ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">🛣️</div>
                        <h3>Belum Ada Laporan</h3>
                        <p>Kamu belum pernah membuat laporan.</p>
                        
                        <a href="index.php" style="
                            display: inline-block;
                            margin-top: 14px;
                            padding: 10px 20px;
                            background: linear-gradient(135deg, #0369a1, #0ea5e9);
                            color: #fff;
                            border-radius: 8px;
                            text-decoration: none;
                            font-weight: 600;
                            font-size: .85rem;
                        ">
                            Buat Laporan Sekarang
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <?php foreach ($myLaporan as $l): ?>
                        <a href="status.php?id=<?= $l['id'] ?>" class="laporan-item">
                            <?php if ($l['foto']): ?>
                                <img src="uploads/<?= htmlspecialchars($l['foto']) ?>" class="lap-thumb" alt="">
                            <?php else: ?>
                                <div class="lap-thumb-placeholder">🚧</div>
                            <?php endif; ?>
                            
                            <div class="lap-info">
                                <div class="lap-jenis"><?= htmlspecialchars($l['jenis_kerusakan']) ?></div>
                                <div class="lap-lokasi">📍 <?= htmlspecialchars($l['lokasi_nama'] ?: 'Lokasi tidak tersedia') ?></div>
                                <div class="lap-badges">
                                    <?= badgeTingkat($l['tingkat']) ?>
                                    <?= badgeStatus($l['status']) ?>
                                </div>
                            </div>
                            
                            <div class="lap-time"><?= date('d M Y', strtotime($l['created_at'])) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>