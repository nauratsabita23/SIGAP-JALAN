<?php
// Admin session guard — include di setiap halaman admin
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$adminUser = $_SESSION['admin'];
