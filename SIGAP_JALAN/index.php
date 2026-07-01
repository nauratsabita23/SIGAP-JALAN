<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/LaporanController.php';
require_once __DIR__ . '/controllers/StoryController.php';

$auth        = new AuthController();
$laporanCtrl = new LaporanController();
$storyCtrl   = new StoryController();
$me          = $auth->getUser();
$ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'like') {
    header('Content-Type: application/json');
    echo json_encode($laporanCtrl->toggleLike((int)($_POST['laporan_id'] ?? 0), $ip, $me['id'] ?? null));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'komentar') {
    header('Content-Type: application/json');
    $nama = trim($_POST['nama'] ?? ($me['nama'] ?? '')) ?: 'Warga Anonim';
    echo json_encode($laporanCtrl->addKomentar((int)($_POST['laporan_id'] ?? 0), $me['id'] ?? null, $nama, $_POST['komentar'] ?? ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_komentar') {
    header('Content-Type: application/json');
    echo json_encode($laporanCtrl->getKomentar((int)($_GET['laporan_id'] ?? 0)));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'view_story') {
    $storyCtrl->recordView((int)($_POST['story_id'] ?? 0), $ip);
    exit;
}

$flash     = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_laporan') {
    $namaPelapor = $me ? $me['nama'] : (trim($_POST['nama_pelapor'] ?? '') ?: 'Warga Anonim');
    $result      = $laporanCtrl->submit($me['id'] ?? null, $namaPelapor, $_POST, $_FILES);
    $flash       = $result['msg'];
    $flashType   = $result['ok'] ? 'success' : 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_story') {
    $namaGuest = $me ? null : (trim($_POST['nama_story'] ?? '') ?: 'Warga Anonim');
    $result    = $storyCtrl->create($me['id'] ?? null, $_POST['caption'] ?? '', $_FILES, $namaGuest);
    $flash     = $result['msg'];
    $flashType = $result['ok'] ? 'success' : 'error';
}

$laporanList  = $laporanCtrl->getFeed(30, $ip);
$stories      = $storyCtrl->getActiveGrouped();
$stats        = $laporanCtrl->getStats();
$statsTingkat = $laporanCtrl->getStatsTingkat();
$totalAll     = array_sum($stats);

function avatarInitial(string $nama): string
{
    $w = explode(' ', trim($nama));
    $i = strtoupper(substr($w[0], 0, 1));

    if (isset($w[1])) {
        $i .= strtoupper(substr($w[1], 0, 1));
    }

    return $i;
}

function timeAgo(string $dt): string
{
    $d = time() - strtotime($dt);

    if ($d < 60) {
        return $d . 'd lalu';
    }

    if ($d < 3600) {
        return floor($d / 60) . 'm lalu';
    }

    if ($d < 86400) {
        return floor($d / 3600) . 'j lalu';
    }

    return floor($d / 86400) . ' hari lalu';
}

function badgeTingkat(string $t): string
{
    return match($t) {
        'berat'  => '<span class="badge-tingkat berat">🔴 Berat</span>',
        'sedang' => '<span class="badge-tingkat sedang">🟡 Sedang</span>',
        'ringan' => '<span class="badge-tingkat ringan">🟢 Ringan</span>',
        default  => '<span class="badge-tingkat sedang">Sedang</span>',
    };
}

function badgeStatus(string $s): string
{
    return match($s) {
        'menunggu' => '<span class="badge-status menunggu">⏳ Menunggu</span>',
        'diproses' => '<span class="badge-status diproses">⚙️ Diproses</span>',
        'selesai'  => '<span class="badge-status selesai">✅ Selesai</span>',
        'ditolak'  => '<span class="badge-status ditolak">❌ Ditolak</span>',
        default    => '<span class="badge-status menunggu">Menunggu</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIGAP Jalan — Laporan Jalan Rusak</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #1d6fc4;
    --primary-dark: #154f91;
    --primary-light: #dbeafe;
    --primary-mid: #4a9de0;
    --secondary: #0ea5e9;
    --accent: #38bdf8;
    --bg: #f0f7ff;
    --white: #fff;
    --border: #bfdbfe;
    --text: #0c2a4a;
    --muted: #4a7fa5;
    --radius: 14px;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

.topbar {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary) 45%, var(--secondary));
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 18px;
    position: sticky;
    top: 0;
    z-index: 500;
    box-shadow: 0 4px 18px rgba(29, 111, 196, .3);
}

.topbar-logo {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #fff;
    font-weight: 800;
    font-size: 1.08rem;
    text-decoration: none;
}

.logo-icon {
    width: 33px;
    height: 33px;
    background: rgba(255, 255, 255, .2);
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.topbar-actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.tbtn {
    padding: 7px 14px;
    border-radius: 20px;
    font-size: .79rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    transition: .2s;
}

.tbtn-ghost {
    background: rgba(255, 255, 255, .18);
    color: #fff;
}

.tbtn-ghost:hover {
    background: rgba(255, 255, 255, .3);
}

.tbtn-white {
    background: #fff;
    color: var(--primary);
}

.tbtn-white:hover {
    background: var(--primary-light);
}

.topbar-av {
    width: 33px;
    height: 33px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .22);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .72rem;
    font-weight: 700;
    text-decoration: none;
    border: 2px solid rgba(255, 255, 255, .45);
}

.app {
    max-width: 1080px;
    margin: 0 auto;
    padding: 16px 14px;
    display: grid;
    grid-template-columns: 255px 1fr;
    gap: 16px;
}

@media (max-width: 800px) {
    .app {
        grid-template-columns: 1fr;
    }

    .sidebar-left {
        display: none;
    }
}

.sidebar-left {
    position: sticky;
    top: 72px;
    height: fit-content;
}

.sc {
    background: var(--white);
    border-radius: var(--radius);
    padding: 15px;
    margin-bottom: 11px;
    box-shadow: 0 2px 12px rgba(29, 111, 196, .07);
    border: 1px solid var(--border);
}

.sc-title {
    font-size: .7rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 11px;
}

.stat-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #e0f0ff;
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-num {
    font-size: .85rem;
    font-weight: 700;
    color: var(--primary);
}

.qlinks {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.qlink {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 9px;
    text-decoration: none;
    color: var(--text);
    font-size: .85rem;
    font-weight: 500;
    transition: .15s;
}

.qlink:hover,
.qlink.active {
    background: var(--primary-light);
    color: var(--primary);
}

.qlink.active {
    font-weight: 700;
}

.scta {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    border-radius: var(--radius);
    padding: 15px;
}

.scta h4 {
    font-size: .86rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.scta p {
    font-size: .74rem;
    opacity: .85;
    margin-bottom: 13px;
    line-height: 1.5;
}

.scta button {
    width: 100%;
    padding: 9px;
    background: #fff;
    color: var(--primary);
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    font-size: .82rem;
}

.feed {
    display: flex;
    flex-direction: column;
    gap: 13px;
}

.stories-bar {
    background: var(--white);
    border-radius: var(--radius);
    padding: 15px 13px;
    box-shadow: 0 2px 12px rgba(29, 111, 196, .07);
    border: 1px solid var(--border);
    display: flex;
    gap: 13px;
    overflow-x: auto;
    scrollbar-width: none;
}

.stories-bar::-webkit-scrollbar {
    display: none;
}

.story-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    flex-shrink: 0;
}

.story-ring {
    width: 63px;
    height: 63px;
    border-radius: 50%;
    background: linear-gradient(45deg, var(--primary), var(--accent), var(--secondary));
    padding: 2.5px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.story-ring.add {
    background: transparent;
    border: 2.5px dashed var(--primary);
}

.story-av {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-light), var(--primary-mid));
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-weight: 700;
    font-size: .83rem;
    overflow: hidden;
    border: 2.5px solid #fff;
}

.story-av img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.story-add-in {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--primary);
}

.story-name {
    font-size: .66rem;
    color: var(--text);
    text-align: center;
    max-width: 65px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
}

.create-post {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: 0 2px 12px rgba(29, 111, 196, .07);
    border: 1px solid var(--border);
    overflow: hidden;
}

.create-top {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 15px;
    border-bottom: 1px solid #e0f0ff;
}

.create-av {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: .8rem;
    flex-shrink: 0;
}

.create-trigger {
    flex: 1;
    background: #f0f7ff;
    border-radius: 20px;
    padding: 10px 15px;
    cursor: pointer;
    color: var(--muted);
    font-size: .86rem;
    border: 1.5px solid var(--border);
    text-align: left;
    font-family: 'Inter', sans-serif;
    transition: .15s;
}

.create-trigger:hover {
    background: var(--primary-light);
    border-color: var(--primary-mid);
}

.create-actions {
    display: flex;
    gap: 3px;
    padding: 7px 9px;
}

.ca-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 8px;
    border-radius: 8px;
    border: none;
    background: transparent;
    font-size: .79rem;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: .15s;
}

.ca-btn:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.post-card {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: 0 2px 12px rgba(29, 111, 196, .07);
    border: 1px solid var(--border);
    overflow: hidden;
}

.post-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 15px;
}

.post-av {
    width: 41px;
    height: 41px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: .8rem;
    flex-shrink: 0;
}

.post-name {
    font-size: .88rem;
    font-weight: 700;
    color: var(--text);
}

.post-sub {
    font-size: .73rem;
    color: var(--muted);
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.post-badges {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    margin-top: 5px;
}

.post-photos {
    position: relative;
    overflow: hidden;
    background: var(--primary-light);
}

.photos-slider {
    display: flex;
    transition: transform .35s ease;
}

.photos-slide {
    flex: 0 0 100%;
}

.post-img {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    display: block;
    cursor: zoom-in;
}

.photo-counter {
    position: absolute;
    top: 10px;
    right: 12px;
    background: rgba(0, 0, 0, .5);
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
}

.photo-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 31px;
    height: 31px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .88);
    border: none;
    font-size: .95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
}

.photo-nav.prev {
    left: 8px;
}

.photo-nav.next {
    right: 8px;
}

.photo-dots {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 4px;
}

.photo-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .55);
}

.photo-dot.active {
    background: #fff;
    width: 16px;
    border-radius: 3px;
}

.post-img-ph {
    width: 100%;
    height: 150px;
    background: linear-gradient(135deg, var(--primary-light), #dbeafe);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    color: var(--primary);
    font-size: 2.3rem;
}

.post-img-ph p {
    font-size: .81rem;
    font-weight: 600;
    color: var(--primary-mid);
}

.post-body {
    padding: 11px 15px;
}

.post-jenis {
    font-size: .95rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 3px;
}

.post-lokasi {
    font-size: .78rem;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 5px;
}

.post-desk {
    font-size: .84rem;
    color: #1a3a5c;
    line-height: 1.5;
}

.post-stats {
    padding: 7px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .75rem;
    color: var(--muted);
    border-top: 1px solid #e0f0ff;
}

.post-actions {
    display: flex;
    padding: 3px 8px;
    border-top: 1px solid #e0f0ff;
}

.post-action {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 9px;
    border-radius: 8px;
    border: none;
    background: transparent;
    font-size: .83rem;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: .15s;
}

.post-action:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.post-action.liked {
    color: #e53e3e;
}

.post-comments {
    padding: 10px 15px 13px;
    border-top: 1px solid #e0f0ff;
    display: none;
}

.post-comments.open {
    display: block;
}

.comments-list {
    max-height: 230px;
    overflow-y: auto;
    margin-bottom: 9px;
}

.comment-item {
    display: flex;
    gap: 8px;
    margin-bottom: 9px;
}

.comment-av {
    width: 29px;
    height: 29px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .62rem;
    font-weight: 700;
    flex-shrink: 0;
}

.comment-bubble {
    background: #f0f7ff;
    border-radius: 0 10px 10px 10px;
    padding: 7px 11px;
    flex: 1;
    border: 1px solid var(--border);
}

.comment-nama {
    font-size: .7rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 2px;
}

.comment-text {
    font-size: .81rem;
    color: var(--text);
    line-height: 1.4;
}

.comment-time {
    font-size: .66rem;
    color: var(--muted);
    margin-top: 2px;
}

.comment-form-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.guest-name-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .74rem;
    color: var(--muted);
}

.guest-name-row input {
    flex: 1;
    background: #f0f7ff;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 5px 10px;
    font-size: .74rem;
    font-family: 'Inter', sans-serif;
    outline: none;
}

.guest-name-row input:focus {
    border-color: var(--primary-mid);
}

.comment-input-row {
    display: flex;
    gap: 7px;
    align-items: center;
}

.comment-input-row input {
    flex: 1;
    background: #f0f7ff;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    padding: 8px 13px;
    font-size: .83rem;
    font-family: 'Inter', sans-serif;
    outline: none;
}

.comment-input-row input:focus {
    border-color: var(--primary-mid);
}

.comment-send {
    width: 33px;
    height: 33px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border: none;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.comment-send:hover {
    transform: scale(1.08);
}

.badge-tingkat {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: .69rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
}

.badge-tingkat.berat {
    background: #fee2e2;
    color: #991b1b;
}

.badge-tingkat.sedang {
    background: #fef3c7;
    color: #92400e;
}

.badge-tingkat.ringan {
    background: #d1fae5;
    color: #065f46;
}

.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: .69rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
}

.badge-status.menunggu {
    background: #fef3c7;
    color: #92400e;
}

.badge-status.diproses {
    background: #dbeafe;
    color: #1e40af;
}

.badge-status.selesai {
    background: #d1fae5;
    color: #065f46;
}

.badge-status.ditolak {
    background: #fee2e2;
    color: #991b1b;
}

.flash {
    padding: 11px 15px;
    border-radius: var(--radius);
    margin-bottom: 12px;
    font-size: .85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid;
}

.flash.success {
    background: #f0fdf4;
    color: #16a34a;
    border-color: #bbf7d0;
}

.flash.error {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(45, 27, 46, .6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 16px;
    backdrop-filter: blur(4px);
}

.modal-overlay.open {
    display: flex;
}

.modal {
    background: var(--white);
    border-radius: 20px;
    width: 100%;
    max-width: 530px;
    max-height: 92vh;
    overflow-y: auto;
    box-shadow: 0 24px 60px rgba(29, 111, 196, .2);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 17px 19px;
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 10;
    border-radius: 20px 20px 0 0;
}

.modal-title {
    font-size: .98rem;
    font-weight: 700;
}

.modal-close {
    width: 31px;
    height: 31px;
    border-radius: 50%;
    border: none;
    background: var(--primary-light);
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
}

.modal-body {
    padding: 19px;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    font-size: .79rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 5px;
    display: block;
}

.form-input {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: .86rem;
    font-family: 'Inter', sans-serif;
    outline: none;
    color: var(--text);
    transition: .15s;
    background: #fff;
}

.form-input:focus {
    border-color: var(--primary);
}

.form-textarea {
    resize: vertical;
    min-height: 78px;
}

.tingkat-row {
    display: flex;
    gap: 7px;
}

.tingkat-chip {
    flex: 1;
    padding: 9px 5px;
    border: 2px solid var(--border);
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: .15s;
    background: #fff;
    font-family: 'Inter', sans-serif;
}

.tingkat-chip b {
    font-size: .78rem;
    font-weight: 700;
    display: block;
    margin-bottom: 2px;
}

.tingkat-chip small {
    font-size: .65rem;
    color: var(--muted);
}

.tingkat-chip.active.ringan {
    border-color: #16a34a;
    background: #f0fdf4;
}

.tingkat-chip.active.ringan b {
    color: #16a34a;
}

.tingkat-chip.active.sedang {
    border-color: #d97706;
    background: #fffbeb;
}

.tingkat-chip.active.sedang b {
    color: #d97706;
}

.tingkat-chip.active.berat {
    border-color: #dc2626;
    background: #fef2f2;
}

.tingkat-chip.active.berat b {
    color: #dc2626;
}

.upload-zone {
    border: 2px dashed var(--border);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: .15s;
    background: #f0f7ff;
}

.upload-zone:hover {
    border-color: var(--primary);
    background: var(--primary-light);
}

.upload-zone input[type=file] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.upload-zone-icon {
    font-size: 2rem;
    margin-bottom: 7px;
}

.upload-zone-text {
    font-size: .79rem;
    color: var(--muted);
}

.upload-zone-hint {
    font-size: .71rem;
    color: var(--primary);
    margin-top: 3px;
    font-weight: 600;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(76px, 1fr));
    gap: 7px;
    margin-top: 11px;
}

.preview-thumb {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--border);
}

.preview-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.preview-thumb .rm-btn {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 19px;
    height: 19px;
    border-radius: 50%;
    background: rgba(220, 38, 38, .85);
    color: #fff;
    border: none;
    font-size: .65rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-submit {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: .93rem;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: .15s;
}

.btn-submit:hover {
    opacity: .9;
    transform: translateY(-1px);
}

.story-viewer {
    display: none;
    position: fixed;
    inset: 0;
    background: #0f0520;
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.story-viewer.open {
    display: flex;
}

.story-content {
    position: relative;
    max-width: 400px;
    width: 100%;
    max-height: 100vh;
}

.sv-progress {
    position: absolute;
    top: 10px;
    left: 8px;
    right: 8px;
    display: flex;
    gap: 3px;
    z-index: 10;
}

.sv-bar {
    height: 3px;
    background: rgba(255, 255, 255, .35);
    border-radius: 2px;
    flex: 1;
    overflow: hidden;
}

.sv-fill {
    height: 100%;
    background: #fff;
    width: 0%;
    transition: width linear;
}

.sv-header {
    position: absolute;
    top: 22px;
    left: 0;
    right: 0;
    padding: 9px 13px;
    display: flex;
    align-items: center;
    gap: 9px;
    z-index: 10;
}

.sv-av {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    border: 2px solid rgba(255, 255, 255, .5);
}

.sv-name {
    color: #fff;
    font-weight: 600;
    font-size: .86rem;
    text-shadow: 0 1px 4px rgba(0, 0, 0, .4);
}

.sv-time {
    color: rgba(255, 255, 255, .65);
    font-size: .71rem;
}

.sv-img {
    width: 100%;
    max-height: 90vh;
    object-fit: contain;
    border-radius: 10px;
}

.sv-caption {
    position: absolute;
    bottom: 48px;
    left: 0;
    right: 0;
    padding: 11px 15px;
    background: linear-gradient(transparent, rgba(26, 10, 26, .8));
    color: #fff;
    font-size: .86rem;
}

.sv-close {
    position: absolute;
    top: 17px;
    right: 13px;
    width: 33px;
    height: 33px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .2);
    border: none;
    color: #fff;
    font-size: 1.05rem;
    cursor: pointer;
    z-index: 10;
}

.sv-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .18);
    border: none;
    color: #fff;
    font-size: 1.15rem;
    cursor: pointer;
    z-index: 10;
}

.sv-nav.prev {
    left: 5px;
}

.sv-nav.next {
    right: 5px;
}

.img-zoom-ov {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .94);
    z-index: 3000;
    align-items: center;
    justify-content: center;
}

.img-zoom-ov.open {
    display: flex;
}

.img-zoom-ov img {
    max-width: 94vw;
    max-height: 94vh;
    object-fit: contain;
    border-radius: 8px;
}

.img-zoom-close {
    position: absolute;
    top: 13px;
    right: 13px;
    width: 35px;
    height: 35px;
    background: rgba(255, 255, 255, .15);
    border: none;
    color: #fff;
    border-radius: 50%;
    font-size: 1.05rem;
    cursor: pointer;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--muted);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 12px;
}
</style>
</head>
<body>
<!-- TOPBAR -->
<div class="topbar">
    <a href="index.php" class="topbar-logo">
        <div class="logo-icon">🚧</div> SIGAP Jalan
    </a>
    <div class="topbar-actions">
        <?php if ($me): ?>
            <?php if ($me['role'] === 'admin'): ?>
            <a href="admin/index.php" class="tbtn tbtn-ghost">⚙️ Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="tbtn tbtn-ghost">Keluar</a>
            <div class="topbar-av"><?= avatarInitial($me['nama']) ?></div>
        <?php else: ?>
            <a href="login.php" class="tbtn tbtn-ghost">Masuk</a>
            <a href="login.php?mode=register" class="tbtn tbtn-white">Daftar</a>
        <?php endif; ?>
    </div>
</div>

<div class="app">
<!-- SIDEBAR -->
<aside class="sidebar-left">
    <div class="sc">
        <div class="sc-title">Menu</div>
        <div class="qlinks">
            <a href="index.php" class="qlink active">🏠 Beranda</a>
            <a href="#" class="qlink" onclick="openModal('modal-laporan');return false;">📝 Buat Laporan</a>
            <a href="#" class="qlink" onclick="openModal('modal-story');return false;">✨ Buat Story</a>
            <?php if ($me): ?>
            <a href="status.php" class="qlink">📋 Laporan Saya</a>
            <?php endif; ?>
            <?php if ($me && $me['role'] === 'admin'): ?>
            <a href="admin/index.php" class="qlink">⚙️ Panel Admin</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="sc">
        <div class="sc-title">Statistik</div>
        <div class="stat-row"><span style="font-size:.82rem;">🗂️ Total</span><span class="stat-num"><?= $totalAll ?></span></div>
        <div class="stat-row"><span style="font-size:.82rem;">⏳ Menunggu</span><span class="stat-num"><?= $stats['menunggu'] ?? 0 ?></span></div>
        <div class="stat-row"><span style="font-size:.82rem;">⚙️ Diproses</span><span class="stat-num"><?= $stats['diproses'] ?? 0 ?></span></div>
        <div class="stat-row"><span style="font-size:.82rem;">✅ Selesai</span><span class="stat-num"><?= $stats['selesai'] ?? 0 ?></span></div>
        <div class="stat-row"><span style="font-size:.82rem;">🔴 Berat</span><span class="stat-num"><?= $statsTingkat['berat'] ?? 0 ?></span></div>
        <div class="stat-row"><span style="font-size:.82rem;">🟡 Sedang</span><span class="stat-num"><?= $statsTingkat['sedang'] ?? 0 ?></span></div>
        <div class="stat-row"><span style="font-size:.82rem;">🟢 Ringan</span><span class="stat-num"><?= $statsTingkat['ringan'] ?? 0 ?></span></div>
    </div>
    <div class="scta">
        <h4>Temukan Jalan Rusak?</h4>
        <p>Laporkan sekarang — tanpa perlu daftar akun!</p>
        <button onclick="openModal('modal-laporan')">🚧 Buat Laporan</button>
    </div>
</aside>

<!-- MAIN FEED -->
<main class="feed">
    <?php if ($flash): ?>
    <div class="flash <?= $flashType ?>">
        <?= $flashType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>

    <!-- STORIES -->
    <div class="stories-bar">
        <div class="story-item" onclick="openModal('modal-story')">
            <div class="story-ring add"><div class="story-add-in">+</div></div>
            <div class="story-name">Buat Story</div>
        </div>
        <?php foreach ($stories as $sg): ?>
        <div class="story-item" onclick="viewStory(<?= htmlspecialchars(json_encode($sg['stories'])) ?>, '<?= htmlspecialchars($sg['nama']) ?>')">
            <div class="story-ring">
                <div class="story-av">
                    <?php if (!empty($sg['avatar'])): ?>
                        <img src="uploads/<?= htmlspecialchars($sg['avatar']) ?>" alt="">
                    <?php else: ?>
                        <?= avatarInitial($sg['nama']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="story-name"><?= htmlspecialchars(explode(' ', $sg['nama'])[0]) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($stories)): ?>
        <div style="display:flex;align-items:center;color:var(--muted);font-size:.78rem;padding:8px 0;">Belum ada story aktif</div>
        <?php endif; ?>
    </div>

    <!-- CREATE POST -->
    <div class="create-post">
        <div class="create-top">
            <div class="create-av"><?= $me ? avatarInitial($me['nama']) : '🏘️' ?></div>
            <button class="create-trigger" onclick="openModal('modal-laporan')">Temukan jalan rusak? Laporkan sekarang...</button>
        </div>
        <div class="create-actions">
            <button class="ca-btn" onclick="openModal('modal-laporan')">📷 Foto</button>
            <button class="ca-btn" onclick="openModal('modal-laporan')">📍 Lokasi</button>
            <button class="ca-btn" onclick="openModal('modal-laporan')">🚧 Laporkan</button>
        </div>
    </div>

    <!-- FEED -->
    <?php if (empty($laporanList)): ?>
    <div class="post-card">
        <div class="empty-state">
            <div class="empty-icon">🛣️</div>
            <h3 style="margin-bottom:6px;">Belum Ada Laporan</h3>
            <p style="font-size:.83rem;">Jadilah yang pertama melaporkan!</p>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($laporanList as $l):
        $namaTampil = $l['pelapor_akun'] ?? $l['nama_pelapor'] ?? 'Warga Anonim';
        $isLiked    = $l['is_liked'] ?? false;
        $fotoList   = $laporanCtrl->getFotoLaporan($l['id']);

        if (empty($fotoList) && $l['foto']) {
            $fotoList = [['foto' => $l['foto']]];
        }
    ?>
    <div class="post-card" id="post-<?= $l['id'] ?>">
        <div class="post-header">
            <div class="post-av"><?= avatarInitial($namaTampil) ?></div>
            <div style="flex:1;">
                <div class="post-name"><?= htmlspecialchars($namaTampil) ?></div>
                <div class="post-sub">
                    <span>📍 <?= htmlspecialchars($l['lokasi_nama'] ?: 'Lokasi tidak tersedia') ?></span>
                    <span>·</span><span><?= timeAgo($l['created_at']) ?></span>
                </div>
                <div class="post-badges"><?= badgeTingkat($l['tingkat']) ?> <?= badgeStatus($l['status']) ?></div>
            </div>
        </div>

        <?php if (!empty($fotoList)):
            $fc = count($fotoList);
        ?>
        <div class="post-photos" id="ph-<?= $l['id'] ?>">
            <div class="photos-slider" id="sl-<?= $l['id'] ?>">
                <?php foreach ($fotoList as $f): ?>
                <div class="photos-slide"><img src="uploads/<?= htmlspecialchars($f['foto']) ?>" class="post-img" alt="" onclick="zoomImg(this.src)"></div>
                <?php endforeach; ?>
            </div>
            <?php if ($fc > 1): ?>
            <span class="photo-counter"><span id="sc-<?= $l['id'] ?>">1</span>/<?= $fc ?></span>
            <button class="photo-nav prev" onclick="slideP(<?= $l['id'] ?>, -1, <?= $fc ?>)">‹</button>
            <button class="photo-nav next" onclick="slideP(<?= $l['id'] ?>, 1, <?= $fc ?>)">›</button>
            <div class="photo-dots" id="pd-<?= $l['id'] ?>">
                <?php for ($di = 0; $di < $fc; $di++): ?>
                <div class="photo-dot <?= $di === 0 ? 'active' : '' ?>" id="pd-<?= $l['id'] ?>-<?= $di ?>"></div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="post-img-ph"><span>🚧</span><p><?= htmlspecialchars($l['jenis_kerusakan']) ?></p></div>
        <?php endif; ?>

        <div class="post-body">
            <div class="post-jenis"><?= htmlspecialchars($l['jenis_kerusakan']) ?></div>
            <?php if ($l['deskripsi']): ?>
            <div class="post-desk"><?= nl2br(htmlspecialchars($l['deskripsi'])) ?></div>
            <?php endif; ?>
        </div>
        <div class="post-stats">
            <span id="lc-<?= $l['id'] ?>"><?= $l['total_likes'] ?> suka</span>
            <span><?= $l['total_komentar'] ?> komentar</span>
        </div>
        <div class="post-actions">
            <button class="post-action <?= $isLiked ? 'liked' : '' ?>" id="lb-<?= $l['id'] ?>" onclick="toggleLike(<?= $l['id'] ?>)"><?= $isLiked ? '❤️ Suka' : '🤍 Suka' ?></button>
            <button class="post-action" onclick="toggleComments(<?= $l['id'] ?>)">💬 Komentar</button>
            <button class="post-action" onclick="sharePost(<?= $l['id'] ?>)">🔗 Bagikan</button>
        </div>
        <div class="post-comments" id="cm-<?= $l['id'] ?>">
            <div class="comments-list" id="cl-<?= $l['id'] ?>"><div style="text-align:center;color:var(--muted);font-size:.76rem;padding:10px;">Memuat...</div></div>
            <div class="comment-form-wrap">
                <?php if (!$me): ?>
                <div class="guest-name-row"><span>Nama:</span><input type="text" id="kn-<?= $l['id'] ?>" placeholder="Nama kamu (opsional)"></div>
                <?php endif; ?>
                <div class="comment-input-row">
                    <div class="comment-av"><?= $me ? avatarInitial($me['nama']) : '👤' ?></div>
                    <input type="text" placeholder="Tulis komentar..." id="ci-<?= $l['id'] ?>" onkeydown="if(event.key==='Enter')submitCom(<?= $l['id'] ?>)">
                    <button class="comment-send" onclick="submitCom(<?= $l['id'] ?>)">➤</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
<!-- MODAL LAPORAN -->
<div class="modal-overlay" id="modal-laporan" onclick="closeModalOut(event,'modal-laporan')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title">🚧 Laporkan Jalan Rusak</div>
        <button class="modal-close" onclick="closeModal('modal-laporan')">✕</button>
    </div>
    <div class="modal-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_laporan">
            <div class="form-group">
                <label class="form-label">📷 Foto Kerusakan <span style="color:var(--muted);font-weight:400;">(bisa lebih dari 1)</span></label>
                <div class="upload-zone" onclick="document.getElementById('fi').click()">
                    <input type="file" name="foto[]" id="fi" accept="image/*" multiple
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                           onchange="previewMulti(this)">
                    <div class="upload-zone-icon">📸</div>
                    <div class="upload-zone-text">Klik untuk pilih foto</div>
                    <div class="upload-zone-hint">Pilih banyak foto sekaligus · JPG, PNG, WEBP</div>
                </div>
                <div class="preview-grid" id="pg"></div>
            </div>
            <?php if (!$me): ?>
            <div class="form-group">
                <label class="form-label">👤 Nama Kamu <span style="color:var(--muted);font-weight:400;">(opsional)</span></label>
                <input type="text" name="nama_pelapor" class="form-input" placeholder="Nama kamu atau kosongkan">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">🔧 Jenis Kerusakan *</label>
                <input type="text" name="jenis_kerusakan" class="form-input" placeholder="Contoh: Lubang Besar, Aspal Retak..." required>
            </div>
            <div class="form-group">
                <label class="form-label">📝 Deskripsi</label>
                <textarea name="deskripsi" class="form-input form-textarea" placeholder="Jelaskan kondisi kerusakan..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">⚠️ Tingkat Kerusakan *</label>
                <div class="tingkat-row">
                    <div class="tingkat-chip ringan" onclick="selTingkat('ringan')"><b>🟢 Ringan</b><small>Retak kecil</small></div>
                    <div class="tingkat-chip sedang active" onclick="selTingkat('sedang')"><b>🟡 Sedang</b><small>Lubang sedang</small></div>
                    <div class="tingkat-chip berat" onclick="selTingkat('berat')"><b>🔴 Berat</b><small>Sangat berbahaya</small></div>
                </div>
                <input type="hidden" name="tingkat" id="inp-tingkat" value="sedang">
            </div>
            <div class="form-group">
                <label class="form-label">📍 Lokasi *</label>
                <input type="text" name="lokasi_nama" class="form-input" placeholder="Contoh: Jl. Pemuda No. 12, Parepare" required>
                <div style="font-size:.71rem;color:var(--muted);margin-top:4px;">Isi nama jalan atau nama tempat secara manual</div>
            </div>
            <button type="submit" class="btn-submit">🚀 Kirim Laporan</button>
        </form>
    </div>
</div>
</div>

<!-- MODAL STORY -->
<div class="modal-overlay" id="modal-story" onclick="closeModalOut(event,'modal-story')">
<div class="modal">
    <div class="modal-header">
        <div class="modal-title">✨ Buat Story</div>
        <button class="modal-close" onclick="closeModal('modal-story')">✕</button>
    </div>
    <div class="modal-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_story">
            <?php if (!$me): ?>
            <div class="form-group">
                <label class="form-label">👤 Nama Kamu <span style="color:var(--muted);font-weight:400;">(opsional)</span></label>
                <input type="text" name="nama_story" class="form-input" placeholder="Nama kamu atau kosongkan">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">📷 Foto Story</label>
                <div class="upload-zone" onclick="document.getElementById('sfi').click()">
                    <input type="file" name="story_foto" id="sfi" accept="image/*"
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                           onchange="prevStory(this)">
                    <div class="upload-zone-icon">📸</div>
                    <div class="upload-zone-text">Klik untuk pilih foto</div>
                    <div class="upload-zone-hint">Story aktif 24 jam · Semua orang bisa melihat</div>
                    <img id="sp" src="" alt="" style="max-width:100%;max-height:190px;border-radius:8px;margin-top:9px;display:none;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">✍️ Caption</label>
                <input type="text" name="caption" class="form-input" placeholder="Tulis sesuatu...">
            </div>
            <button type="submit" class="btn-submit">✨ Bagikan Story</button>
        </form>
    </div>
</div>
</div>

<!-- STORY VIEWER -->
<div class="story-viewer" id="story-viewer">
<div class="story-content">
    <div class="sv-progress" id="svp"></div>
    <div class="sv-header">
        <div class="sv-av" id="sv-av"></div>
        <div><div class="sv-name" id="sv-nm"></div><div class="sv-time" id="sv-tm"></div></div>
    </div>
    <img src="" id="sv-img" class="sv-img" alt="">
    <div id="sv-cap" class="sv-caption"></div>
    <button class="sv-close" onclick="closeSV()">✕</button>
    <button class="sv-nav prev" id="sv-prev" onclick="svNav(-1)">‹</button>
    <button class="sv-nav next" id="sv-next" onclick="svNav(1)">›</button>
</div>
</div>

<!-- IMG ZOOM -->
<div class="img-zoom-ov" id="img-zoom" onclick="this.classList.remove('open')">
    <img id="zi" src="" alt="">
    <button class="img-zoom-close" onclick="document.getElementById('img-zoom').classList.remove('open')">✕</button>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}

function closeModalOut(e, id) {
    if (e.target.id === id) {
        closeModal(id);
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(function(m) {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

function selTingkat(v) {
    document.querySelectorAll('.tingkat-chip').forEach(function(c) {
        c.classList.remove('active');
    });
    document.querySelector('.tingkat-chip.' + v).classList.add('active');
    document.getElementById('inp-tingkat').value = v;
}

var selFiles = [];

function previewMulti(input) {
    var nf = Array.from(input.files);
    selFiles = selFiles.concat(nf);
    renderPrev();
    input.value = '';
    updateFI();
}

function renderPrev() {
    var g = document.getElementById('pg');
    g.innerHTML = '';

    selFiles.forEach(function(f, i) {
        var r = new FileReader();
        r.onload = function(e) {
            var d = document.createElement('div');
            d.className = 'preview-thumb';
            d.innerHTML = '<img src="' + e.target.result + '"><button type="button" class="rm-btn" onclick="rmPhoto(' + i + ')">✕</button>';
            g.appendChild(d);
        };
        r.readAsDataURL(f);
    });
}

function rmPhoto(i) {
    selFiles.splice(i, 1);
    renderPrev();
    updateFI();
}

function updateFI() {
    var inp = document.getElementById('fi');
    var dt = new DataTransfer();

    selFiles.forEach(function(f) {
        dt.items.add(f);
    });

    inp.files = dt.files;
}

function prevStory(input) {
    var p = document.getElementById('sp');

    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function(e) {
            p.src = e.target.result;
            p.style.display = 'block';
        };
        r.readAsDataURL(input.files[0]);
    }
}

var slideIdx = {};

function slideP(id, dir, total) {
    if (slideIdx[id] === undefined) {
        slideIdx[id] = 0;
    }

    slideIdx[id] = (slideIdx[id] + dir + total) % total;

    var s = document.getElementById('sl-' + id);
    if (s) {
        s.style.transform = 'translateX(-' + slideIdx[id] * 100 + '%)';
    }

    var sc = document.getElementById('sc-' + id);
    if (sc) {
        sc.textContent = slideIdx[id] + 1;
    }

    for (var i = 0; i < total; i++) {
        var d = document.getElementById('pd-' + id + '-' + i);
        if (d) {
            d.className = 'photo-dot' + (i === slideIdx[id] ? ' active' : '');
        }
    }
}

async function toggleLike(id) {
    var btn = document.getElementById('lb-' + id);
    btn.disabled = true;

    try {
        var fd = new FormData();
        fd.append('action', 'like');
        fd.append('laporan_id', id);

        var d = await (await fetch('index.php', { method: 'POST', body: fd })).json();

        document.getElementById('lc-' + id).textContent = d.count + ' suka';
        btn.innerHTML = d.liked ? '❤️ Suka' : '🤍 Suka';
        btn.className = 'post-action' + (d.liked ? ' liked' : '');
    } catch (e) {}

    btn.disabled = false;
}

var loadedCom = {};

async function toggleComments(id) {
    var b = document.getElementById('cm-' + id);

    if (b.classList.contains('open')) {
        b.classList.remove('open');
        return;
    }

    b.classList.add('open');

    if (!loadedCom[id]) {
        await loadCom(id);
        loadedCom[id] = true;
    }
}

async function loadCom(id) {
    try {
        var d = await (await fetch('index.php?action=get_komentar&laporan_id=' + id)).json();
        renderCom(id, d);
    } catch (e) {}
}

function renderCom(id, data) {
    var l = document.getElementById('cl-' + id);

    if (!data.length) {
        l.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:.76rem;padding:10px;">Belum ada komentar. Jadilah yang pertama!</div>';
        return;
    }

    l.innerHTML = data.map(function(c) {
        return '<div class="comment-item"><div class="comment-av">' + gi(c.nama_akun || c.nama) + '</div><div class="comment-bubble"><div class="comment-nama">' + es(c.nama_akun || c.nama) + '</div><div class="comment-text">' + es(c.komentar) + '</div><div class="comment-time">' + ja(c.created_at) + '</div></div></div>';
    }).join('');

    l.scrollTop = l.scrollHeight;
}

async function submitCom(id) {
    var inp = document.getElementById('ci-' + id);
    var kn = document.getElementById('kn-' + id);
    var kom = inp.value.trim();

    if (!kom) {
        return;
    }

    inp.value = '';

    var fd = new FormData();
    fd.append('action', 'komentar');
    fd.append('laporan_id', id);
    fd.append('komentar', kom);

    if (kn && kn.value.trim()) {
        fd.append('nama', kn.value.trim());
    }

    try {
        var d = await (await fetch('index.php', { method: 'POST', body: fd })).json();
        if (d.ok) {
            renderCom(id, d.data);
        }
    } catch (e) {}
}

function gi(n) {
    if (!n) {
        return '?';
    }

    var w = n.trim().split(' ');
    return (w[0][0] + (w[1] ? w[1][0] : '')).toUpperCase();
}

function es(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function ja(dt) {
    var d = (Date.now() - new Date(dt).getTime()) / 1000;

    if (d < 60) {
        return Math.floor(d) + 'd lalu';
    }

    if (d < 3600) {
        return Math.floor(d / 60) + 'm lalu';
    }

    if (d < 86400) {
        return Math.floor(d / 3600) + 'j lalu';
    }

    return Math.floor(d / 86400) + ' hari lalu';
}

function sharePost(id) {
    var url = location.origin + location.pathname + '?post=' + id;

    if (navigator.share) {
        navigator.share({ title: 'SIGAP Jalan', url: url });
    } else {
        navigator.clipboard.writeText(url).then(function() {
            alert('Link disalin!');
        });
    }
}

function zoomImg(src) {
    document.getElementById('zi').src = src;
    document.getElementById('img-zoom').classList.add('open');
}

var svS = [];
var svI = 0;
var svT;

function viewStory(s, n) {
    svS = s;
    svI = 0;
    renderSV();
    document.getElementById('story-viewer').classList.add('open');
    document.body.style.overflow = 'hidden';
    startSVT();
}

function renderSV() {
    var s = svS[svI];

    document.getElementById('sv-nm').textContent = s.nama || 'Warga';
    document.getElementById('sv-av').textContent = gi(s.nama || 'W');
    document.getElementById('sv-tm').textContent = ja(s.created_at);

    var img = document.getElementById('sv-img');
    img.src = s.foto ? 'uploads/' + s.foto : '';
    img.style.display = s.foto ? 'block' : 'none';

    document.getElementById('sv-cap').textContent = s.caption || '';

    document.getElementById('sv-prev').style.opacity = svI > 0 ? '1' : '0.3';
    document.getElementById('sv-next').style.opacity = svI < svS.length - 1 ? '1' : '0.3';

    var p = document.getElementById('svp');
    p.innerHTML = svS.map(function(_, i) {
        return '<div class="sv-bar"><div class="sv-fill" id="sf-' + i + '" style="width:' + (i < svI ? '100%' : '0%') + '"></div></div>';
    }).join('');

    var fd = new FormData();
    fd.append('action', 'view_story');
    fd.append('story_id', s.id);
    fetch('index.php', { method: 'POST', body: fd });
}

function startSVT() {
    clearTimeout(svT);

    var f = document.getElementById('sf-' + svI);
    if (f) {
        f.style.transition = 'width 5s linear';
        f.style.width = '100%';
    }

    svT = setTimeout(function() {
        if (svI < svS.length - 1) {
            svI++;
            renderSV();
            startSVT();
        } else {
            closeSV();
        }
    }, 5000);
}

function svNav(d) {
    clearTimeout(svT);
    svI = Math.max(0, Math.min(svS.length - 1, svI + d));
    renderSV();
    startSVT();
}

function closeSV() {
    clearTimeout(svT);
    document.getElementById('story-viewer').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (document.getElementById('story-viewer').classList.contains('open')) {
        if (e.key === 'ArrowRight') {
            svNav(1);
        }

        if (e.key === 'ArrowLeft') {
            svNav(-1);
        }

        if (e.key === 'Escape') {
            closeSV();
        }
    }
});
</script>
</body>
</html>