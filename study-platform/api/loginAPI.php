<?php
// =============================================
//  StudyVault — Login API  (FIXED)
// =============================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed.']);
    exit();
}

$data     = json_decode(file_get_contents("php://input"), true);
$email    = isset($data['email'])    ? trim($data['email'])    : '';
$password = isset($data['password']) ? trim($data['password']) : '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit();
}

$conn = getDB();

// Auto-migration: add last_login & is_online columns if missing
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) NOT NULL DEFAULT 0");

$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    $stmt->close(); $conn->close(); exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    $conn->close(); exit();
}

// Update last_login timestamp & mark online
$upd = $conn->prepare("UPDATE users SET last_login = NOW(), is_online = 1 WHERE id = ?");
$upd->bind_param("i", $user['id']);
$upd->execute();
$upd->close();

// Set session variables
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role']  = $user['role'];

echo json_encode([
    'status'  => 'success',
    'message' => 'Login successful.',
    'user'    => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]
]);

$conn->close();
?>
