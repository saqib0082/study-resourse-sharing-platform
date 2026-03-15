<?php
// =============================================
//  StudyVault — Update User Role API (Admin only)
//  File: api/updateRoleAPI.php
//  Method: POST
//  Body: { "id": USER_ID, "role": "admin"|"student" }
// =============================================

session_start();
header('Content-Type: application/json');
include_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admins only.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed.']);
    exit();
}

$data   = json_decode(file_get_contents("php://input"), true);
$userId = isset($data['id'])   ? intval($data['id'])        : 0;
$role   = isset($data['role']) ? trim($data['role'])        : '';

$allowedRoles = ['admin', 'student'];

if ($userId <= 0 || !in_array($role, $allowedRoles)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit();
}

// Prevent admin from changing their own role
if ($userId === $_SESSION['user_id']) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot change your own role.']);
    exit();
}

$conn = getDB();
$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->bind_param("si", $role, $userId);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Role updated to ' . $role . '.', 'new_role' => $role]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update role.']);
}

$stmt->close();
$conn->close();
?>