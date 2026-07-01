<?php
require_once __DIR__ . '/auth_admin.php';
require_once __DIR__ . '/../controllers/LaporanController.php';
require_once __DIR__ . '/../controllers/UserController.php';

$laporanCtrl = new LaporanController();
$userCtrl    = new UserController();

$stats        = $laporanCtrl->getStats();
$statsTingkat = $laporanCtrl->getStatsTingkat();
$totalLap     = array_sum($stats);
$tunggu       = $stats['menunggu'] ?? 0;
$proses       = $stats['diproses'] ?? 0;
$selesai      = $stats['selesai']  ?? 0;
$ditolak      = $stats['ditolak']  ?? 0;
$totalUser    = $userCtrl->count();
$recent       = $laporanCtrl->getRecentActivity(10);

$pageTitle  = 'Dashboard Admin';
$activePage = 'dashboard';
include __DIR__ . '/views/layout.php';
?>

<div class="page-header">
    <div class="page-title">📊 Dashboard Admin</div>
    <div class="page-sub">Selamat datang, <?= htmlspecialchars($adminUser['nama']) ?>! Ringkasan data SIGAP Jalan.</div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-box">
        <div class="stat-ico" style="background:#fff0ea;">🗂️</div>
        <div class="stat-info">
            <div class="num"><?= $totalLap ?></div>
            <div class="lbl">Total Pengaduan</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-ico" style="background:#fef3c7;">⏳</div>
        <div class="stat-info">
            <div class="num"><?= $tunggu ?></div>
            <div class="lbl">Menunggu</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-ico" style="background:#fce7f3;">⚙️</div>
        <div class="stat-info">
            <div class="num"><?= $proses ?></div>
            <div class="lbl">Diproses</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-ico" style="background:#d1fae5;">✅</div>
        <div class="stat-info">
            <div class="num"><?= $selesai ?></div>
            <div class="lbl">Selesai</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-ico" style="background:#fce7f3;">👥</div>
        <div class="stat-info">
            <div class="num"><?= $totalUser ?></div>
            <div class="lbl">Pengguna</div>
        </div>
    </div>
</div>

<!-- Stats Tingkat -->
<div class="stats-row" style="margin-bottom:24px;">
    <div class="stat-box">
        <div class="stat-ico" style="background:#fee2e2;">🔴</div>
        <div class="stat-info">
            <div class="num"><?= $statsTingkat['berat'] ?? 0 ?></div>
            <div class="lbl">Kerusakan Berat</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-ico" style="background:#fef3c7;">🟡</div>
        <div class="stat-info">
            <div class="num"><?= $statsTingkat['sedang'] ?? 0 ?></div>
            <div class="lbl">Kerusakan Sedang</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-ico" style="background:#d1fae5;">🟢</div>
        <div class="stat-info">
            <div class="num"><?= $statsTingkat['ringan'] ?? 0 ?></div>
            <div class="lbl">Kerusakan Ringan</div>
        </div>
    </div>
</div>

<!-- Pengaduan Terbaru -->
<div class="card">
    <div class="card-head">
        <div class="card-title">📋 Pengaduan Terbaru</div>
        <a href="laporan.php" class="btn btn-primary btn-sm">Lihat Semua</a>
    </div>
    <?php if (empty($recent)): ?>
    <div class="card-body" style="text-align:center;color:var(--text-muted);padding:40px;">Belum ada laporan</div>
    <?php else: ?>
    <table class="tbl">
        <thead>
            <tr>
                <th>#</th>
                <th>Jenis Kerusakan</th>
                <th>Lokasi</th>
                <th>Tingkat</th>
                <th>Status</th>
                <th>Pelapor</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $l): ?>
            <tr>
                <td><?= $l['id'] ?></td>
                <td><?= htmlspecialchars($l['jenis_kerusakan']) ?></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($l['lokasi_nama'] ?: '-') ?></td>
                <td>
                    <?php
                    $t      = $l['tingkat'];
                    $tLabel = match($t) {
                        'berat'   => '🔴 Berat',
                        'sedang'  => '🟡 Sedang',
                        'ringan'  => '🟢 Ringan',
                        default   => 'Sedang'
                    };
                    echo "<span class='badge badge-{$t}'>{$tLabel}</span>";
                    ?>
                </td>
                <td>
                    <?php
                    $s      = $l['status'];
                    $sLabel = match($s) {
                        'menunggu' => '⏳ Menunggu',
                        'diproses' => '⚙️ Diproses',
                        'selesai'  => '✅ Selesai',
                        'ditolak'  => '❌ Ditolak',
                        default    => $s
                    };
                    echo "<span class='badge badge-{$s}'>{$sLabel}</span>";
                    ?>
                </td>
                <td><?= htmlspecialchars($l['nama_pelapor'] ?? $l['pelapor_akun'] ?? 'Anonim') ?></td>
                <td style="white-space:nowrap;"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                <td><a href="laporan.php?detail=<?= $l['id'] ?>" class="btn btn-sm" style="background:#f3f4f6;color:var(--text);">Detail</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/views/layout_footer.php'; ?>