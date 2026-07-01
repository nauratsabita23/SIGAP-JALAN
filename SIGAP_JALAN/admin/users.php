<?php
require_once __DIR__ . '/auth_admin.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/LaporanController.php';

$userCtrl    = new UserController();
$laporanCtrl = new LaporanController();

$flash     = '';
$flashType = '';

// -- DELETE user --
if (isset($_GET['hapus'])) {
    $uid       = (int)$_GET['hapus'];
    $result    = $userCtrl->delete($uid, $adminUser['id']);
    $flash     = $result['msg'];
    $flashType = $result['ok'] ? 'success' : 'danger';
}

// -- UPDATE user --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $uid   = (int)$_POST['user_id'];
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = in_array($_POST['role'] ?? '', ['admin', 'warga']) ? $_POST['role'] : 'warga';

    if (empty($nama) || empty($email)) {
        $flash     = 'Nama dan email tidak boleh kosong.';
        $flashType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash     = 'Format email tidak valid.';
        $flashType = 'danger';
    } else {
        $userCtrl->update($uid, $nama, $email, $role, $adminUser['id'])['ok'];
        $flash     = 'Data pengguna berhasil diperbarui.';
        $flashType = 'success';
    }
}

$search = trim($_GET['q'] ?? '');
$users  = $userCtrl->getAll($search);

$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = $userCtrl->findById((int)$_GET['edit']);
}

// view detail user
$viewUser    = null;
$userLaporan = [];
if (isset($_GET['detail'])) {
    $viewUser    = $userCtrl->findById((int)$_GET['detail']);
    $userLaporan = $laporanCtrl->getByUserId((int)$_GET['detail']);
}

$pageTitle  = 'Data Pengguna';
$activePage = 'users';
include __DIR__ . '/views/layout.php';
?>

<div class="page-header">
    <div class="page-title">👥 Data Pengguna</div>
    <div class="page-sub">Total <?= count($users) ?> pengguna terdaftar dalam sistem</div>
</div>

<?php if ($flash): ?>
<div class="flash <?= $flashType ?>">
    <?= $flashType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($flash) ?>
</div>
<?php endif; ?>

<!-- Detail user modal -->
<?php if ($viewUser): ?>
<div class="card" style="border:2px solid #d1fae5;margin-bottom:22px;">
    <div class="card-head">
        <div class="card-title">👤 Detail Pengguna: <?= htmlspecialchars($viewUser['nama']) ?></div>
        <a href="users.php" class="btn btn-sm btn-secondary">✕ Tutup</a>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <div style="font-size:.75rem;color:var(--text-muted);font-weight:700;margin-bottom:4px;text-transform:uppercase;">Nama</div>
                <div style="font-weight:600;"><?= htmlspecialchars($viewUser['nama']) ?></div>
            </div>
            <div>
                <div style="font-size:.75rem;color:var(--text-muted);font-weight:700;margin-bottom:4px;text-transform:uppercase;">Email</div>
                <div><?= htmlspecialchars($viewUser['email']) ?></div>
            </div>
            <div>
                <div style="font-size:.75rem;color:var(--text-muted);font-weight:700;margin-bottom:4px;text-transform:uppercase;">Role</div>
                <span class="badge badge-<?= $viewUser['role'] ?>"><?= $viewUser['role'] === 'admin' ? '🛡️ Admin' : '👤 Warga' ?></span>
            </div>
            <div>
                <div style="font-size:.75rem;color:var(--text-muted);font-weight:700;margin-bottom:4px;text-transform:uppercase;">Bergabung</div>
                <div><?= date('d M Y, H:i', strtotime($viewUser['created_at'])) ?></div>
            </div>
        </div>

        <div style="font-weight:700;font-size:.9rem;margin-bottom:12px;color:var(--text);">
            📋 Riwayat Pengaduan (<?= count($userLaporan) ?>)
        </div>

        <?php if (empty($userLaporan)): ?>
        <p style="color:var(--text-muted);font-size:.85rem;">Pengguna ini belum pernah membuat laporan.</p>
        <?php else: ?>
        <table class="tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Lokasi</th>
                    <th>Jenis Kerusakan</th>
                    <th>Tingkat</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($userLaporan as $l): ?>
            <tr>
                <td style="color:var(--text-muted);"><?= $l['id'] ?></td>
                <td><?= htmlspecialchars($l['lokasi_nama'] ?: '—') ?></td>
                <td><?= htmlspecialchars($l['jenis_kerusakan']) ?></td>
                <td><span class="badge badge-<?= $l['tingkat'] ?>"><?= ucfirst($l['tingkat']) ?></span></td>
                <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
                <td style="font-size:.8rem;color:var(--text-muted);"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <div class="card-title">Daftar Semua Pengguna</div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <form method="GET" style="display:flex;gap:6px;align-items:center;">
                <div class="search-wrap">
                    <span>🔍</span>
                    <input type="text" name="q" placeholder="Cari nama / email..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                <?php if ($search): ?>
                <a href="users.php" class="btn btn-sm btn-secondary">✕ Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (empty($users)): ?>
    <div class="empty-state">
        <div class="ico">🔍</div>
        <p>Tidak ada pengguna ditemukan</p>
    </div>
    <?php else: ?>
    <table class="tbl">
        <thead>
            <tr>
                <th>#</th>
                <th>Pengguna</th>
                <th>Email</th>
                <th>Role</th>
                <th>Bergabung</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $i => $u): ?>
        <tr>
            <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="avatar"><?= strtoupper(mb_substr($u['nama'], 0, 1)) ?></div>
                    <div>
                        <div style="font-weight:600;"><?= htmlspecialchars($u['nama']) ?></div>
                        <?php if ($u['id'] === $adminUser['id']): ?>
                        <div style="font-size:.7rem;color:var(--primary-mid);">⭐ Akun Anda</div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td style="color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? '🛡️ Admin' : '👤 Warga' ?></span></td>
            <td style="font-size:.8rem;color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                    <a href="users.php?detail=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">👁️ Detail</a>
                    <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <?php if ($u['id'] !== $adminUser['id']): ?>
                    <a href="users.php?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Hapus pengguna <?= addslashes(htmlspecialchars($u['nama'])) ?>?')">🗑️</a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div class="modal-bg <?= $editUser ? 'open' : '' ?>" id="editModal">
    <div class="modal-box">
        <div class="modal-title">✏️ Edit Pengguna</div>
        <?php if ($editUser): ?>
        <form method="POST">
            <input type="hidden" name="action"  value="update">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($editUser['nama']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control">
                    <option value="warga" <?= $editUser['role'] === 'warga' ? 'selected' : '' ?>>👤 Warga</option>
                    <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>🛡️ Admin</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" style="flex:1;">💾 Simpan</button>
                <a href="users.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/views/layout_footer.php'; ?>