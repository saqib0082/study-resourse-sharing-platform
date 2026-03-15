<?php
// =============================================
//  StudyVault — Delete User API  (Admin only)
//  File: api/deleteUserAPI.php
//  Method: DELETE
//  Body: { "id": USER_ID }
// =============================================

session_start();
header('Content-Type: application/json');
include_once '../config/db.php';

// Must be logged in AND admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admins only.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['status' => 'error', 'message' => 'Only DELETE method allowed.']);
    exit();
}

$data   = json_decode(file_get_contents("php://input"), true);
$userId = isset($data['id']) ? intval($data['id']) : 0;

if ($userId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit();
}

// Prevent admin from deleting themselves
if ($userId === $_SESSION['user_id']) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
    exit();
}

$conn = getDB();

// Get all notes of this user so we can delete their files from disk
$notesRes = $conn->query("SELECT file_path FROM notes WHERE uploaded_by = $userId");
while ($note = $notesRes->fetch_assoc()) {
    $path = dirname(__DIR__) . '/' . $note['file_path'];
    if (file_exists($path)) unlink($path);
}

// Delete user — notes will cascade-delete due to FK ON DELETE CASCADE
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete user.']);
}

$stmt->close();
$conn->close();
?>