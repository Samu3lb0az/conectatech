<?php
if (!isset($_SESSION)) {
    session_start();
}
// Impede cache para evitar retorno após logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}
