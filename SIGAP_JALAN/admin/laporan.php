<?php
require_once __DIR__ . '/auth_admin.php';
require_once __DIR__ . '/../controllers/LaporanController.php';

$laporanCtrl = new LaporanController();

$flash     = '';
$flashType = '';

// -- UPDATE STATUS --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $ok        = $laporanCtrl->updateStatus((int)$_POST['laporan_id'], $_POST['status'] ?? '');
    $flash     = $ok ? 'Status berhasil diperbarui.' : 'Gagal memperbarui status.';
    $flashType = $ok ? 'success' : 'error';
}

// -- UPDATE TINGKAT --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_tingkat') {
    $ok        = $laporanCtrl->updateTingkat((int)$_POST['laporan_id'], $_POST['tingkat'] ?? '');
    $flash     = $ok ? 'Tingkat kerusakan berhasil diperbarui.' : 'Gagal memperbarui tingkat.';
    $flashType = $ok ? 'success' : 'error';
}

// -- DELETE --
if (isset($_GET['hapus'])) {
    $laporanCtrl->delete((int)$_GET['hapus']);
    header('Location: laporan.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) {
    $flash     = 'Pengaduan berhasil dihapus.';
    $flashType = 'success';
}

// -- FILTERS --
$filterStatus  = $_GET['status']  ?? '';
$filterTingkat = $_GET['tingkat'] ?? '';
$search        = trim($_GET['q']  ?? '');

$allLaporan = $laporanCtrl->getAll(200);

if ($filterStatus || $filterTingkat || $search) {
    $allLaporan = array_filter($allLaporan, function($l) use ($filterStatus, $filterTingkat, $search) {
        if ($filterStatus && $l['status'] !== $filterStatus) {
            return false;
        }

        if ($filterTingkat && $l['tingkat'] !== $filterTingkat) {
            return false;
        }

        if ($search) {
            $haystack = ($l['lokasi_nama'] ?? '') . ($l['jenis_kerusakan'] ?? '') . ($l['nama_pelapor'] ?? '') . ($l['pelapor_akun'] ?? '');
            if (stripos($haystack, $search) === false) {
                return false;
            }
        }

        return true;
    });
}

$detail = null;
if (isset($_GET['detail'])) {
    $detail = $laporanCtrl->getById((int)$_GET['detail']);
}

$stats = $laporanCtrl->getStats();

$pageTitle  = 'Data Pengaduan';
$activePage = 'laporan';
include __DIR__ . '/views/layout.php';
?>

<div class="page-header">
    <div class="page-title">📋 Data Pengaduan</div>
    <div class="page-sub">Kelola semua laporan · Admin dapat mengubah tingkat kerusakan dan status</div>
</div>

<?php if ($flash): ?>
<div class="flash <?= $flashType ?>">
    <?= $flashType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($flash) ?>
</div>
<?php endif; ?>

<!-- Quick stats -->
<div class="stats-row" style="margin-bottom:20px;">
    <a href="laporan.php" style="text-decoration:none;">
        <div class="stat-box">
            <div class="stat-ico" style="background:#f3f4f6;">🗂️</div>
            <div class="stat-info">
                <div class="num"><?= array_sum($stats) ?></div>
                <div class="lbl">Total</div>
            </div>
        </div>
    </a>
    <a href="laporan.php?status=menunggu" style="text-decoration:none;">
        <div class="stat-box">
            <div class="stat-ico" style="background:#fef9c3;">⏳</div>
            <div class="stat-info">
                <div class="num"><?= $stats['menunggu'] ?? 0 ?></div>
                <div class="lbl">Menunggu</div>
            </div>
        </div>
    </a>
    <a href="laporan.php?status=diproses" style="text-decoration:none;">
        <div class="stat-box">
            <div class="stat-ico" style="background:#dbeafe;">⚙️</div>
            <div class="stat-info">
                <div class="num"><?= $stats['diproses'] ?? 0 ?></div>
                <div class="lbl">Diproses</div>
            </div>
        </div>
    </a>
    <a href="laporan.php?status=selesai" style="text-decoration:none;">
        <div class="stat-box">
            <div class="stat-ico" style="background:#d1fae5;">✅</div>
            <div class="stat-info">
                <div class="num"><?= $stats['selesai'] ?? 0 ?></div>
                <div class="lbl">Selesai</div>
            </div>
        </div>
    </a>
</div>

<!-- Filter & Search -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:14px 18px;">
        <form method="GET" action="laporan.php">
            <div class="toolbar">
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" name="q" placeholder="Cari lokasi, jenis, pelapor..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="status" class="filter-select">
                    <option value="">Semua Status</option>
                    <option value="menunggu" <?= $filterStatus === 'menunggu' ? 'selected' : '' ?>>⏳ Menunggu</option>
                    <option value="diproses" <?= $filterStatus === 'diproses' ? 'selected' : '' ?>>⚙️ Diproses</option>
                    <option value="selesai"  <?= $filterStatus === 'selesai'  ? 'selected' : '' ?>>✅ Selesai</option>
                    <option value="ditolak"  <?= $filterStatus === 'ditolak'  ? 'selected' : '' ?>>❌ Ditolak</option>
                </select>
                <select name="tingkat" class="filter-select">
                    <option value="">Semua Tingkat</option>
                    <option value="ringan" <?= $filterTingkat === 'ringan' ? 'selected' : '' ?>>🟢 Ringan</option>
                    <option value="sedang" <?= $filterTingkat === 'sedang' ? 'selected' : '' ?>>🟡 Sedang</option>
                    <option value="berat"  <?= $filterTingkat === 'berat'  ? 'selected' : '' ?>>🔴 Berat</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
                <a href="laporan.php" style="padding:9px 14px;background:#f3f4f6;border-radius:9px;font-size:.82rem;font-weight:600;text-decoration:none;color:var(--text);">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- ===== DETAIL LENGKAP LAPORAN ===== -->
<?php if ($detail):
    $komentar   = $laporanCtrl->getKomentar($detail['id']);
    $likeCount  = $laporanCtrl->getLikeCount($detail['id']);
    $namaTampil = $detail['pelapor_akun'] ?? $detail['nama_pelapor'] ?? 'Warga Anonim';

    $tLabel = match($detail['tingkat']) {
        'berat'  => '🔴 Berat',
        'sedang' => '🟡 Sedang',
        'ringan' => '🟢 Ringan',
        default  => 'Sedang'
    };
    $tClass = $detail['tingkat'];

    $sLabel = match($detail['status']) {
        'menunggu' => '⏳ Menunggu',
        'diproses' => '⚙️ Diproses',
        'selesai'  => '✅ Selesai',
        'ditolak'  => '❌ Ditolak',
        default    => $detail['status']
    };
?>
<style>
.det-wrap {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
    align-items: start;
    margin-bottom: 20px;
}

@media (max-width: 900px) {
    .det-wrap {
        grid-template-columns: 1fr;
    }
}

.det-foto {
    width: 100%;
    border-radius: 12px;
    object-fit: cover;
    max-height: 360px;
    cursor: zoom-in;
    display: block;
}

.det-foto-placeholder {
    width: 100%;
    height: 220px;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 3rem;
}

.det-info-row {
    display: flex;
    gap: 0;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}

.det-info-row:last-child {
    border-bottom: none;
}

.det-info-label {
    font-size: .75rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .4px;
    width: 100px;
    flex-shrink: 0;
    padding-top: 1px;
}

.det-info-val {
    font-size: .88rem;
    color: var(--text);
    flex: 1;
    line-height: 1.5;
}

.section-divider {
    font-size: .75rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .8px;
    padding: 14px 0 8px;
    border-bottom: 2px solid #f3f4f6;
    margin-bottom: 10px;
}

.ctrl-block {
    background: #f9fafb;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 12px;
}

.ctrl-title {
    font-size: .8rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tingkat-admin-chip {
    padding: 8px 4px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: .15s;
    font-family: 'Inter', sans-serif;
}

.tingkat-admin-chip b {
    font-size: .75rem;
    font-weight: 700;
    display: block;
}

.tingkat-admin-chip.active.ringan {
    border-color: #16a34a;
    background: #f0fdf4;
}

.tingkat-admin-chip.active.ringan b {
    color: #16a34a;
}

.tingkat-admin-chip.active.sedang {
    border-color: #d97706;
    background: #fffbeb;
}

.tingkat-admin-chip.active.sedang b {
    color: #d97706;
}

.tingkat-admin-chip.active.berat {
    border-color: #dc2626;
    background: #fef2f2;
}

.tingkat-admin-chip.active.berat b {
    color: #dc2626;
}

.tl-wrap {
    padding: 4px 0;
}

.tl-item {
    display: flex;
    gap: 12px;
    position: relative;
    padding-bottom: 18px;
}

.tl-item:last-child {
    padding-bottom: 0;
}

.tl-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 13px;
    top: 27px;
    width: 2px;
    bottom: 0;
    background: #e5e7eb;
}

.tl-dot {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .72rem;
    font-weight: 700;
    flex-shrink: 0;
    z-index: 1;
    background: #e5e7eb;
    color: #9ca3af;
}

.tl-dot.done {
    background: var(--primary);
    color: #fff;
}

.tl-dot.active {
    background: var(--primary);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(30, 64, 175, .18);
}

.tl-info {
    flex: 1;
    padding-top: 3px;
}

.tl-label {
    font-size: .85rem;
    font-weight: 600;
    color: var(--text);
}

.tl-label.pending {
    color: #9ca3af;
}

.tl-desc {
    font-size: .76rem;
    color: var(--text-muted);
    margin-top: 2px;
    line-height: 1.4;
}

.kom-item {
    display: flex;
    gap: 9px;
    margin-bottom: 10px;
}

.kom-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .65rem;
    font-weight: 700;
    flex-shrink: 0;
}

.kom-bubble {
    background: #f3f4f6;
    border-radius: 0 10px 10px 10px;
    padding: 8px 12px;
    flex: 1;
}

.kom-nama {
    font-size: .73rem;
    font-weight: 700;
    color: #2563eb;
    margin-bottom: 2px;
}

.kom-text {
    font-size: .82rem;
    color: var(--text);
    line-height: 1.4;
}

.kom-time {
    font-size: .67rem;
    color: var(--text-muted);
    margin-top: 3px;
}

.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f3f4f6;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--text-muted);
}

/* Zoom overlay */
.zoom-ov {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .92);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.zoom-ov.open {
    display: flex;
}

.zoom-ov img {
    max-width: 92vw;
    max-height: 90vh;
    object-fit: contain;
    border-radius: 8px;
}

.zoom-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, .2);
    border: none;
    color: #fff;
    border-radius: 50%;
    font-size: 1.1rem;
    cursor: pointer;
}
</style>

<!-- Back + Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
    <div>
        <a href="laporan.php" style="display:inline-flex;align-items:center;gap:5px;font-size:.82rem;font-weight:600;color:var(--text-muted);text-decoration:none;margin-bottom:4px;">← Kembali ke Daftar</a>
        <div style="font-size:1.25rem;font-weight:800;color:var(--text);">Detail Laporan #<?= $detail['id'] ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:2px;">Dilaporkan <?= date('d M Y, H:i', strtotime($detail['created_at'])) ?> · Terakhir diupdate <?= date('d M Y, H:i', strtotime($detail['updated_at'])) ?></div>
    </div>
    <div style="display:flex;gap:8px;">
        <span class="stat-pill">❤️ <?= $likeCount ?> suka</span>
        <span class="stat-pill">💬 <?= count($komentar) ?> komentar</span>
        <a href="laporan.php?hapus=<?= $detail['id'] ?>" class="btn btn-danger btn-sm"
           onclick="return confirm('Yakin hapus pengaduan ini? Tindakan ini tidak bisa dibatalkan.')">🗑️ Hapus</a>
    </div>
</div>

<div class="det-wrap">

    <!-- KOLOM KIRI: Info Laporan -->
    <div>
        <!-- Foto -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="padding:16px;">
                <?php if ($detail['foto']): ?>
                <img src="../uploads/<?= htmlspecialchars($detail['foto']) ?>" class="det-foto"
                     alt="Foto Laporan" onclick="openZoom(this.src)">
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:8px;text-align:center;">Klik foto untuk perbesar</div>
                <?php else: ?>
                <div class="det-foto-placeholder">
                    <span>🚧</span>
                    <p style="font-size:.85rem;font-weight:600;color:#6b7280;">Tidak ada foto</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Lengkap -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-head">
                <div class="card-title">📋 Informasi Lengkap</div>
            </div>
            <div class="card-body" style="padding:12px 18px;">
                <div class="det-info-row">
                    <div class="det-info-label">ID Laporan</div>
                    <div class="det-info-val" style="font-weight:700;color:#2563eb;">#<?= $detail['id'] ?></div>
                </div>
                <div class="det-info-row">
                    <div class="det-info-label">Pelapor</div>
                    <div class="det-info-val">
                        <span style="font-weight:600;"><?= htmlspecialchars($namaTampil) ?></span>
                        <?php if (!$detail['user_id']): ?>
                        <span style="font-size:.7rem;color:var(--text-muted);margin-left:6px;">(anonim, tanpa akun)</span>
                        <?php else: ?>
                        <span style="font-size:.7rem;color:var(--text-muted);margin-left:6px;">(akun terdaftar)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="det-info-row">
                    <div class="det-info-label">Jenis</div>
                    <div class="det-info-val" style="font-weight:600;"><?= htmlspecialchars($detail['jenis_kerusakan']) ?></div>
                </div>
                <div class="det-info-row">
                    <div class="det-info-label">Lokasi</div>
                    <div class="det-info-val">📍 <?= htmlspecialchars($detail['lokasi_nama'] ?: 'Tidak tersedia') ?></div>
                </div>
                <div class="det-info-row">
                    <div class="det-info-label">Tingkat</div>
                    <div class="det-info-val"><span class="badge badge-<?= $tClass ?>"><?= $tLabel ?></span></div>
                </div>
                <div class="det-info-row">
                    <div class="det-info-label">Status</div>
                    <div class="det-info-val"><span class="badge badge-<?= $detail['status'] ?>"><?= $sLabel ?></span></div>
                </div>
                <div class="det-info-row">
                    <div class="det-info-label">Tanggal</div>
                    <div class="det-info-val"><?= date('d M Y, H:i', strtotime($detail['created_at'])) ?></div>
                </div>
                <div class="det-info-row">
                    <div class="det-info-label">Diupdate</div>
                    <div class="det-info-val"><?= date('d M Y, H:i', strtotime($detail['updated_at'])) ?></div>
                </div>
                <?php if ($detail['deskripsi']): ?>
                <div class="det-info-row">
                    <div class="det-info-label">Deskripsi</div>
                    <div class="det-info-val"><?= nl2br(htmlspecialchars($detail['deskripsi'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline Perbaikan -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-head">
                <div class="card-title">📈 Progress Penanganan</div>
            </div>
            <div class="card-body" style="padding:14px 18px;">
                <?php
                $tlSteps = [
                    ['key' => 'menunggu',  'label' => 'Laporan Diterima',           'desc' => 'Laporan masuk & tercatat dalam sistem.'],
                    ['key' => 'diproses',  'label' => 'Verifikasi & Tindak Lanjut', 'desc' => 'Laporan diverifikasi dan diteruskan ke dinas terkait.'],
                    ['key' => 'perbaikan', 'label' => 'Perbaikan Lapangan',         'desc' => 'Tim sedang melakukan perbaikan di lokasi.'],
                    ['key' => 'selesai',   'label' => 'Selesai Diperbaiki',         'desc' => 'Perbaikan telah selesai dilaksanakan.'],
                ];

                $sOrd  = ['menunggu' => 1, 'diproses' => 2, 'selesai' => 4, 'ditolak' => 99];
                $kOrd  = ['menunggu' => 1, 'diproses' => 2, 'perbaikan' => 3, 'selesai' => 4];
                $curOrd = $sOrd[$detail['status']] ?? 1;

                foreach ($tlSteps as $step):
                    $stepOrd = $kOrd[$step['key']];
                    $done    = ($detail['status'] !== 'ditolak') && ($curOrd >= $stepOrd || $detail['status'] === 'selesai');
                    $active  = ($detail['status'] === $step['key']) || ($detail['status'] === 'diproses' && $step['key'] === 'perbaikan' && $curOrd >= 2);

                    if ($step['key'] === 'menunggu') {
                        $done = true;
                    }
                ?>
                <div class="tl-item">
                    <div class="tl-dot <?= $active ? 'active' : ($done ? 'done' : '') ?>">
                        <?= $done ? '✓' : ($step['key'] === 'perbaikan' ? '🔧' : '○') ?>
                    </div>
                    <div class="tl-info">
                        <div class="tl-label <?= !$done ? 'pending' : '' ?>"><?= $step['label'] ?></div>
                        <?php if ($done): ?>
                        <div class="tl-desc"><?= $step['desc'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($detail['status'] === 'ditolak'): ?>
                <div class="tl-item">
                    <div class="tl-dot" style="background:#dc2626;color:#fff;">✕</div>
                    <div class="tl-info">
                        <div class="tl-label" style="color:#dc2626;">Laporan Ditolak</div>
                        <div class="tl-desc">Laporan tidak dapat diproses lebih lanjut.</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Komentar Warga -->
        <div class="card">
            <div class="card-head">
                <div class="card-title">💬 Komentar Warga (<?= count($komentar) ?>)</div>
            </div>
            <div class="card-body" style="padding:14px 18px;">
                <?php if (empty($komentar)): ?>
                <div style="text-align:center;color:var(--text-muted);font-size:.85rem;padding:20px 0;">Belum ada komentar</div>
                <?php else: ?>
                <?php foreach ($komentar as $k): ?>
                <div class="kom-item">
                    <div class="kom-avatar">
                        <?php
                        $n = $k['nama_akun'] ?? $k['nama'] ?? 'W';
                        $w = explode(' ', trim($n));
                        echo strtoupper(substr($w[0], 0, 1) . (isset($w[1]) ? substr($w[1], 0, 1) : ''));
                        ?>
                    </div>
                    <div class="kom-bubble">
                        <div class="kom-nama"><?= htmlspecialchars($k['nama_akun'] ?? $k['nama']) ?></div>
                        <div class="kom-text"><?= htmlspecialchars($k['komentar']) ?></div>
                        <div class="kom-time"><?= date('d M Y, H:i', strtotime($k['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: Kontrol Admin -->
    <div>
        <!-- Ubah Status -->
        <div class="card" style="margin-bottom:12px;">
            <div class="card-head">
                <div class="card-title">⚙️ Ubah Status</div>
            </div>
            <div class="card-body" style="padding:14px 16px;">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="laporan_id" value="<?= $detail['id'] ?>">
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;">
                        <?php foreach (['menunggu' => '⏳ Menunggu', 'diproses' => '⚙️ Diproses', 'selesai' => '✅ Selesai', 'ditolak' => '❌ Ditolak'] as $sv => $sl): ?>
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:2px solid <?= $detail['status'] === $sv ? 'var(--primary)' : '#e5e7eb' ?>;background:<?= $detail['status'] === $sv ? 'var(--primary-bg)' : '#fff' ?>;cursor:pointer;transition:.15s;">
                            <input type="radio" name="status" value="<?= $sv ?>" <?= $detail['status'] === $sv ? 'checked' : '' ?> style="accent-color:#2563eb;">
                            <span style="font-size:.88rem;font-weight:<?= $detail['status'] === $sv ? '700' : '500' ?>;"><?= $sl ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">💾 Simpan Status</button>
                </form>
            </div>
        </div>

        <!-- Ubah Tingkat -->
        <div class="card" style="margin-bottom:12px;">
            <div class="card-head">
                <div class="card-title">⚠️ Ubah Tingkat Kerusakan</div>
            </div>
            <div class="card-body" style="padding:14px 16px;">
                <form method="POST">
                    <input type="hidden" name="action" value="update_tingkat">
                    <input type="hidden" name="laporan_id" value="<?= $detail['id'] ?>">
                    <div style="display:flex;gap:8px;margin-bottom:12px;">
                        <?php foreach (['ringan' => '🟢 Ringan', 'sedang' => '🟡 Sedang', 'berat' => '🔴 Berat'] as $k => $v): ?>
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="tingkat" value="<?= $k ?>" <?= $detail['tingkat'] === $k ? 'checked' : '' ?> style="display:none;" class="tingkat-radio" data-val="<?= $k ?>">
                            <div class="tingkat-admin-chip <?= $detail['tingkat'] === $k ? 'active ' . $k : '' ?>" data-for="<?= $k ?>">
                                <b><?= $v ?></b>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;background:var(--primary-mid);">💾 Simpan Tingkat</button>
                </form>
            </div>
        </div>

        <!-- Ringkasan Interaksi -->
        <div class="card" style="margin-bottom:12px;">
            <div class="card-head">
                <div class="card-title">📊 Interaksi Publik</div>
            </div>
            <div class="card-body" style="padding:12px 16px;">
                <div style="display:flex;gap:10px;">
                    <div style="flex:1;text-align:center;background:#fff0f0;border-radius:10px;padding:12px;">
                        <div style="font-size:1.5rem;font-weight:800;color:#e53e3e;"><?= $likeCount ?></div>
                        <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">❤️ Suka</div>
                    </div>
                    <div style="flex:1;text-align:center;background:#eff6ff;border-radius:10px;padding:12px;">
                        <div style="font-size:1.5rem;font-weight:800;color:#3b82f6;"><?= count($komentar) ?></div>
                        <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">💬 Komentar</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hapus -->
        <div class="card">
            <div class="card-head">
                <div class="card-title" style="color:#dc2626;">⚠️ Zona Berbahaya</div>
            </div>
            <div class="card-body" style="padding:14px 16px;">
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:12px;line-height:1.5;">Menghapus laporan akan menghapus semua data terkait termasuk komentar dan likes secara permanen.</p>
                <a href="laporan.php?hapus=<?= $detail['id'] ?>" class="btn btn-danger" style="width:100%;justify-content:center;"
                   onclick="return confirm('Yakin hapus laporan #<?= $detail['id'] ?>? Ini tidak dapat dibatalkan.')">🗑️ Hapus Laporan Ini</a>
            </div>
        </div>
    </div>

</div>

<!-- Zoom foto overlay -->
<div class="zoom-ov" id="zoom-ov" onclick="this.classList.remove('open')">
    <img id="zoom-img" src="" alt="">
    <button class="zoom-close" onclick="document.getElementById('zoom-ov').classList.remove('open')">✕</button>
</div>
<script>
function openZoom(src) {
    document.getElementById('zoom-img').src = src;
    document.getElementById('zoom-ov').classList.add('open');
}

document.querySelectorAll('.tingkat-radio').forEach(function(r) {
    r.addEventListener('change', function() {
        document.querySelectorAll('.tingkat-admin-chip').forEach(function(c) {
            c.classList.remove('active', 'ringan', 'sedang', 'berat');
        });

        var chip = document.querySelector('.tingkat-admin-chip[data-for="' + this.dataset.val + '"]');
        if (chip) {
            chip.classList.add('active', this.dataset.val);
        }
    });
});
</script>
<?php endif; ?>

<!-- Data Table — hanya tampil jika tidak sedang lihat detail -->
<?php if (!$detail): ?>
<div class="card">
    <div class="card-head">
        <div class="card-title">📋 Daftar Pengaduan (<?= count($allLaporan) ?>)</div>
    </div>
    <?php if (empty($allLaporan)): ?>
    <div class="card-body" style="text-align:center;color:var(--text-muted);padding:40px;">Tidak ada data</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="tbl">
        <thead>
            <tr>
                <th>#</th>
                <th>Foto</th>
                <th>Jenis Kerusakan</th>
                <th>Lokasi</th>
                <th>Tingkat</th>
                <th>Status</th>
                <th>Pelapor</th>
                <th>Tanggal</th>
                <th>Aksi Cepat</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allLaporan as $l): ?>
        <tr>
            <td><?= $l['id'] ?></td>
            <td>
                <?php if ($l['foto']): ?>
                <img src="../uploads/<?= htmlspecialchars($l['foto']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                <div style="width:44px;height:44px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🚧</div>
                <?php endif; ?>
            </td>
            <td style="font-weight:600;"><?= htmlspecialchars($l['jenis_kerusakan']) ?></td>
            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.82rem;color:var(--text-muted);">
                <?= htmlspecialchars($l['lokasi_nama'] ?: '-') ?>
            </td>
            <td>
                <?php
                $t      = $l['tingkat'];
                $tLabel = match($t) {
                    'berat'  => '🔴 Berat',
                    'sedang' => '🟡 Sedang',
                    'ringan' => '🟢 Ringan',
                    default  => 'Sedang'
                };
                echo "<span class='badge badge-{$t}'>{$tLabel}</span>";
                ?>
            </td>
            <td>
                <!-- Quick status update inline -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="laporan_id" value="<?= $l['id'] ?>">
                    <select name="status" class="form-control" style="padding:5px 8px;font-size:.75rem;width:120px;"
                            onchange="this.form.submit()">
                        <option value="menunggu" <?= $l['status'] === 'menunggu' ? 'selected' : '' ?>>⏳ Menunggu</option>
                        <option value="diproses" <?= $l['status'] === 'diproses' ? 'selected' : '' ?>>⚙️ Diproses</option>
                        <option value="selesai"  <?= $l['status'] === 'selesai'  ? 'selected' : '' ?>>✅ Selesai</option>
                        <option value="ditolak"  <?= $l['status'] === 'ditolak'  ? 'selected' : '' ?>>❌ Ditolak</option>
                    </select>
                </form>
            </td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($l['nama_pelapor'] ?? $l['pelapor_akun'] ?? 'Anonim') ?></td>
            <td style="white-space:nowrap;font-size:.78rem;color:var(--text-muted);"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
            <td>
                <!-- Quick tingkat update -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update_tingkat">
                    <input type="hidden" name="laporan_id" value="<?= $l['id'] ?>">
                    <select name="tingkat" class="form-control" style="padding:5px 8px;font-size:.75rem;width:110px;"
                            onchange="this.form.submit()">
                        <option value="ringan" <?= $l['tingkat'] === 'ringan' ? 'selected' : '' ?>>🟢 Ringan</option>
                        <option value="sedang" <?= $l['tingkat'] === 'sedang' ? 'selected' : '' ?>>🟡 Sedang</option>
                        <option value="berat"  <?= $l['tingkat'] === 'berat'  ? 'selected' : '' ?>>🔴 Berat</option>
                    </select>
                </form>
            </td>
            <td>
                <a href="laporan.php?detail=<?= $l['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
                <a href="laporan.php?hapus=<?= $l['id'] ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Yakin hapus?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; // end if(!$detail) ?>

<?php include __DIR__ . '/views/layout_footer.php'; ?>