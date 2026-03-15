<?php
// =============================================
//  StudyVault — Download API
//  File: api/downloadAPI.php
//  Method: GET  ?id=NOTE_ID
//  - Increments download counter
//  - Streams file to browser
// =============================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once '../config/db.php';
$conn = getDB();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("Invalid note ID.");
}

// Fetch note from DB
$stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Note not found.");
}

$note = $result->fetch_assoc();

// Build absolute file path
$filePath = dirname(__DIR__) . '/' . $note['file_path'];

if (!file_exists($filePath)) {
    die("File not found on server. It may have been deleted.");
}

// Increment download counter
$updateStmt = $conn->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id = ?");
$updateStmt->bind_param("i", $id);
$updateStmt->execute();
$updateStmt->close();

$stmt->close();
$conn->close();

// Determine MIME type
$ext = strtolower(pathinfo($note['file_name'], PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt'  => 'text/plain',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

// Stream file to browser as download
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($note['file_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

// Clear any output buffers
ob_clean();
flush();

readfile($filePath);
exit();
?>