<?php
// admin/views/layout.php
// Requires: $pageTitle, $activePage, $adminUser
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — SIGAP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #1e40af;
    --primary-dark: #1e3a8a;
    --primary-bg: #eff6ff;
    --primary-mid: #3b82f6;
    --gray: #f0f4f8;
    --gray2: #dbeafe;
    --text: #0f172a;
    --text-muted: #4b6584;
    --danger: #dc2626;
    --danger-bg: #fef2f2;
    --success: #16a34a;
    --success-bg: #f0fdf4;
    --warning: #d97706;
    --warning-bg: #fffbeb;
}

body {
    font-family: 'Inter', sans-serif;
    background: #e8f0fe;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.topbar {
    height: 60px;
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: sticky;
    top: 0;
    z-index: 300;
    box-shadow: 0 4px 20px rgba(30, 64, 175, .35);
}

.topbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    font-size: 1.05rem;
    letter-spacing: 1px;
}

.topbar-pin {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.topbar-badge {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 6px;
    padding: 3px 10px;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: 1px;
    margin-left: 4px;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
}

.topbar-user {
    font-size: .82rem;
    color: rgba(255, 255, 255, 0.75);
}

.logout-btn {
    padding: 7px 14px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    color: #fff;
    font-size: .78rem;
    font-weight: 600;
    text-decoration: none;
    transition: .2s;
}

.logout-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.view-site-btn {
    padding: 7px 14px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    color: #fff;
    font-size: .78rem;
    font-weight: 600;
    text-decoration: none;
    transition: .2s;
}

.view-site-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.app-body {
    display: flex;
    flex: 1;
    min-height: calc(100vh - 60px);
}

.sidebar {
    width: 240px;
    flex-shrink: 0;
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 60px;
    height: calc(100vh - 60px);
    overflow-y: auto;
}

.nav-label {
    font-size: .65rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.35);
    padding: 14px 20px 5px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 20px;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: .87rem;
    font-weight: 500;
    transition: all .2s;
    border-left: 3px solid transparent;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.nav-link.active {
    background: rgba(59, 130, 246, 0.3);
    color: #fff;
    border-left-color: #93c5fd;
    font-weight: 600;
}

.nav-link .icon {
    font-size: 17px;
    width: 20px;
    text-align: center;
}

.nav-divider {
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin: 8px 16px;
}

.sidebar-foot {
    padding: 16px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    margin-top: auto;
}

.sidebar-foot-name {
    font-size: .85rem;
    font-weight: 600;
    color: #fff;
}

.sidebar-foot-role {
    font-size: .7rem;
    color: rgba(255, 255, 255, 0.45);
    margin-top: 2px;
}

.main {
    flex: 1;
    padding: 28px;
    overflow-x: hidden;
}

.page-header {
    margin-bottom: 22px;
}

.page-title {
    font-size: 1.45rem;
    font-weight: 800;
    color: var(--text);
}

.page-sub {
    font-size: .82rem;
    color: var(--text-muted);
    margin-top: 3px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
    display: flex;
    align-items: center;
    gap: 14px;
}

.stat-ico {
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.stat-info .num {
    font-size: 1.9rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1;
}

.stat-info .lbl {
    font-size: .75rem;
    color: var(--text-muted);
    margin-top: 3px;
    font-weight: 500;
}

.card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(30, 64, 175, .07);
    overflow: hidden;
    margin-bottom: 22px;
}

.card-head {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.card-title {
    font-size: .95rem;
    font-weight: 700;
    color: var(--text);
}

.card-body {
    padding: 20px;
}

.tbl {
    width: 100%;
    border-collapse: collapse;
}

.tbl th {
    padding: 11px 16px;
    text-align: left;
    font-size: .72rem;
    font-weight: 700;
    color: var(--text-muted);
    background: #f9fafb;
    border-bottom: 1px solid var(--gray2);
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}

.tbl td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--gray2);
    font-size: .87rem;
    color: var(--text);
    vertical-align: middle;
}

.tbl tr:last-child td {
    border-bottom: none;
}

.tbl tr:hover td {
    background: #fdf8ff;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
}

.badge-menunggu {
    background: #fef3c7;
    color: #92400e;
}

.badge-diproses {
    background: #ede9fe;
    color: #5b21b6;
}

.badge-selesai {
    background: #d1fae5;
    color: #065f46;
}

.badge-ditolak {
    background: #fee2e2;
    color: #991b1b;
}

.badge-ringan {
    background: #d1fae5;
    color: #065f46;
}

.badge-sedang {
    background: #fef3c7;
    color: #92400e;
}

.badge-berat {
    background: #fee2e2;
    color: #991b1b;
}

.badge-admin {
    background: #ede9fe;
    color: #5b21b6;
}

.badge-warga {
    background: #e0f2fe;
    color: #075985;
}

/* Buttons */
.btn {
    padding: 8px 16px;
    border-radius: 9px;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: .15s;
}

.btn-primary {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    color: #fff;
}

.btn-primary:hover {
    opacity: .9;
}

.btn-danger {
    background: var(--danger-bg);
    color: var(--danger);
}

.btn-danger:hover {
    background: #fecaca;
}

.btn-sm {
    padding: 5px 12px;
    font-size: .75rem;
}

/* Form */
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: .8rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 5px;
}

.form-control {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid var(--gray2);
    border-radius: 9px;
    font-size: .88rem;
    font-family: 'Inter', sans-serif;
    background: #fff;
    color: var(--text);
    transition: .2s;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
}

/* Flash */
.flash {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 600;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flash.success {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid #bbf7d0;
}

.flash.error {
    background: var(--danger-bg);
    color: var(--danger);
    border: 1px solid #fecaca;
}

/* Filters */
.toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 200px;
    background: #fff;
    border: 1.5px solid var(--gray2);
    border-radius: 9px;
    padding: 8px 12px;
}

.search-box input {
    border: none;
    outline: none;
    background: transparent;
    font-family: 'Inter', sans-serif;
    font-size: .88rem;
    flex: 1;
}

.filter-select {
    padding: 9px 12px;
    border: 1.5px solid var(--gray2);
    border-radius: 9px;
    font-family: 'Inter', sans-serif;
    font-size: .85rem;
    background: #fff;
    cursor: pointer;
}

.btn-filter {
    padding: 9px 16px;
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    color: #fff;
    border: none;
    border-radius: 9px;
    font-family: 'Inter', sans-serif;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 900px) {
    .sidebar {
        display: none;
    }

    .main {
        padding: 16px;
    }

    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }

    .tbl {
        font-size: .8rem;
    }

    .tbl th,
    .tbl td {
        padding: 10px 10px;
    }
}
</style>
</head>
<body>
<div class="topbar">
    <div class="topbar-brand">
        <div class="topbar-pin">📍</div>
        SIGAP JALAN
        <span class="topbar-badge">ADMIN</span>
    </div>
    <div class="topbar-right">
        <a href="../index.php" class="view-site-btn">🌐 Lihat Situs</a>
        <span class="topbar-user"><?= htmlspecialchars($adminUser['nama']) ?></span>
        <a href="logout.php" class="logout-btn">Keluar</a>
    </div>
</div>

<div class="app-body">
<aside class="sidebar">
    <nav>
        <div class="nav-label">Menu Admin</div>
        <a href="index.php" class="nav-link <?= ($activePage==='dashboard')?'active':'' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="laporan.php" class="nav-link <?= ($activePage==='laporan')?'active':'' ?>">
            <span class="icon">📋</span> Data Pengaduan
        </a>
        <a href="users.php" class="nav-link <?= ($activePage==='users')?'active':'' ?>">
            <span class="icon">👥</span> Data Pengguna
        </a>
        <hr class="nav-divider">
        <a href="../index.php" class="nav-link">
            <span class="icon">🌐</span> Lihat Situs
        </a>
        <a href="logout.php" class="nav-link">
            <span class="icon">🚪</span> Keluar
        </a>
    </nav>
    <div class="sidebar-foot">
        <div class="sidebar-foot-name"><?= htmlspecialchars($adminUser['nama']) ?></div>
        <div class="sidebar-foot-role">🛡️ Administrator</div>
    </div>
</aside>

<main class="main">