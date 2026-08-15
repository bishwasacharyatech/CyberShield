<?php
require_once 'includes/config.php';

if (!empty($_SESSION['uid']) && !empty($_SESSION['role'])) {
    header('Location: ' . BASE_URL . '/' . $_SESSION['role'] . '/dashboard.php');
    exit;
}

require_once 'landing.php';
?>