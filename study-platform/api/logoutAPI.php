<?php
// =============================================
//  StudyVault — Logout API  (FIXED)
// =============================================

session_start();
include_once __DIR__ . '/../config/db.php';

// Mark user offline before destroying session
if (isset($_SESSION['user_id'])) {
    $conn = getDB();
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) NOT NULL DEFAULT 0");
    $upd = $conn->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
    $upd->bind_param("i", $_SESSION['user_id']);
    $upd->execute();
    $upd->close();
    $conn->close();
}

session_unset();
session_destroy();
header("Location: ../login.php");
exit();
?>
