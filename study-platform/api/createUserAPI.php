<?php
// =============================================
//  StudyVault — Create User API (Admin Only)
//  File: api/createUserAPI.php
//  Method: POST
// =============================================

session_start();
header('Content-Type: application/json');

include_once __DIR__ . '/../config/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admins only.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed.']);
    exit();
}

$data     = json_decode(file_get_contents("php://input"), true);
$name     = isset($data['name'])     ? trim($data['name'])     : '';
$email    = isset($data['email'])    ? trim($data['email'])    : '';
$password = isset($data['password']) ? trim($data['password']) : '';
$role     = isset($data['role'])     ? trim($data['role'])     : 'student';

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit();
}
if (strlen($name) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Name must be at least 2 characters.']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit();
}
if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit();
}
if (!in_array($role, ['student', 'admin'])) {
    $role = 'student';
}

$conn = getDB();

$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'This email is already registered.']);
    $check->close(); $conn->close(); exit();
}
$check->close();

$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hashed, $role);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => "User \"$name\" created successfully."]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create user: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
