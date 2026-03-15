<?php
// =============================================
//  StudyVault — Upload API
//  Limits:
//    Per file  : 100 MB
//    Per user  : 1 GB total
//    Platform  : 60 GB total
// =============================================
session_start();
header('Content-Type: application/json');
include_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized. Please login.']); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Only POST allowed.']); exit();
}

$title       = isset($_POST['title'])       ? trim($_POST['title'])       : '';
$subject     = isset($_POST['subject'])     ? trim($_POST['subject'])     : '';
$category_id = isset($_POST['category_id']) && $_POST['category_id']!=='' ? intval($_POST['category_id']) : null;
$uploaded_by = $_SESSION['user_id'];

if (empty($title) || empty($subject)) {
    echo json_encode(['status'=>'error','message'=>'Title and subject are required.']); exit();
}
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCodes = [UPLOAD_ERR_INI_SIZE=>'File exceeds server upload limit.',UPLOAD_ERR_FORM_SIZE=>'File too large.',UPLOAD_ERR_NO_FILE=>'No file selected.'];
    $msg = $errCodes[$_FILES['file']['error'] ?? 0] ?? 'Upload error.';
    echo json_encode(['status'=>'error','message'=>$msg]); exit();
}

$file    = $_FILES['file'];
$fileSize= $file['size'];
$allowed = ['pdf','doc','docx','ppt','pptx','txt','png','jpg','jpeg'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// ── Size Limits ──────────────────────────────────
define('LIMIT_FILE',    100  * 1024 * 1024);        // 100 MB
define('LIMIT_USER',    1    * 1024 * 1024 * 1024); // 1 GB per user
define('LIMIT_TOTAL',   60   * 1024 * 1024 * 1024); // 60 GB platform

if ($fileSize > LIMIT_FILE) {
    echo json_encode(['status'=>'error','message'=>'File too large. Maximum file size is 100 MB.']); exit();
}
if (!in_array($ext, $allowed)) {
    echo json_encode(['status'=>'error','message'=>'File type not allowed. Accepted: PDF, DOC, DOCX, PPT, PPTX, TXT, PNG, JPG.']); exit();
}

$conn = getDB();

// ── Check user's storage quota ────────────────────
$uRow = $conn->query("SELECT COALESCE(SUM(file_size),0) as used FROM notes WHERE uploaded_by=$uploaded_by")->fetch_assoc();
$userUsed = (int)$uRow['used'];
if (($userUsed + $fileSize) > LIMIT_USER) {
    $remainMB = max(0, round((LIMIT_USER - $userUsed) / 1024 / 1024, 1));
    echo json_encode(['status'=>'error','message'=>"Storage limit exceeded. You have $remainMB MB remaining (1 GB total limit per user)."]); exit();
}

// ── Check platform total storage ─────────────────
$pRow = $conn->query("SELECT COALESCE(SUM(file_size),0) as total FROM notes")->fetch_assoc();
$platformUsed = (int)$pRow['total'];
if (($platformUsed + $fileSize) > LIMIT_TOTAL) {
    echo json_encode(['status'=>'error','message'=>'Platform storage is full. Please contact an administrator.']); exit();
}

// ── Move file to uploads/ ─────────────────────────
$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$uniqueName  = uniqid('note_', true) . '.' . $ext;
$destPath    = $uploadDir . $uniqueName;
$relativePath= 'uploads/' . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['status'=>'error','message'=>'Failed to save file. Check uploads/ folder permissions.']); exit();
}

// ── Save to DB (include file_size) ────────────────
$stmt = $conn->prepare(
    "INSERT INTO notes (title, subject, category_id, file_name, file_path, file_size, uploaded_by)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssissii", $title, $subject, $category_id, $file['name'], $relativePath, $fileSize, $uploaded_by);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Notes uploaded successfully!',
        'file'    => $uniqueName,
        'size_mb' => round($fileSize / 1024 / 1024, 2)
    ]);
} else {
    unlink($destPath); // rollback
    echo json_encode(['status'=>'error','message'=>'Database error. Please try again.']);
}

$stmt->close();
$conn->close();
?>
