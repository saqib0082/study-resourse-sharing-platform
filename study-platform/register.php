<?php
// Registration is disabled — accounts are created by admin only
session_start();
if (isset($_SESSION['user_id'])) {
    header($_SESSION['user_role'] === 'admin' ? "Location: admin.php" : "Location: dashboard.php");
    exit();
}
header("Location: login.php");
exit();
?>
