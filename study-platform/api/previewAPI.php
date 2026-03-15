<?php
// =============================================
//  StudyVault — Preview API  (v3 - works on localhost)
//  File: api/previewAPI.php
//  Method: GET  ?id=NOTE_ID
//  - Does NOT increment download counter
//  - TXT    → raw text JSON
//  - Images → base64 data URI JSON
//  - PDF    → base64 data URI JSON  (rendered by PDF.js in frontend)
//  - DOC/DOCX → base64 data URI JSON (rendered by mammoth.js)
//  - PPT/PPTX → error with download suggestion
// =============================================

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit();
}

include_once '../config/db.php';
$conn = getDB();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid note ID.']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Note not found.']);
    exit();
}

$note     = $result->fetch_assoc();
$stmt->close();
$conn->close();

$ext      = strtolower(pathinfo($note['file_name'], PATHINFO_EXTENSION));
$filePath = dirname(__DIR__) . '/' . $note['file_path'];

if (!file_exists($filePath)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'File not found on server.']);
    exit();
}

header('Content-Type: application/json');

// TXT
if ($ext === 'txt') {
    echo json_encode(['status' => 'text', 'content' => file_get_contents($filePath), 'file_name' => $note['file_name']]);
    exit();
}

// PPT/PPTX - no browser-side renderer available
if (in_array($ext, ['ppt', 'pptx'])) {
    echo json_encode(['status' => 'ppt', 'file_name' => $note['file_name']]);
    exit();
}

$mimeMap = [
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$typeMap = [
    'pdf'  => 'pdf',
    'png'  => 'image', 'jpg' => 'image', 'jpeg' => 'image', 'gif' => 'image', 'webp' => 'image',
    'doc'  => 'word',  'docx' => 'word',
];

if (!isset($mimeMap[$ext])) {
    echo json_encode(['status' => 'error', 'message' => 'Preview not available for .' . strtoupper($ext) . ' files.']);
    exit();
}

$fileSize = filesize($filePath);
if ($fileSize > 15 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'File too large to preview (' . round($fileSize/1024/1024,1) . ' MB). Please download it.']);
    exit();
}

$base64  = base64_encode(file_get_contents($filePath));
$dataUri = 'data:' . $mimeMap[$ext] . ';base64,' . $base64;

echo json_encode([
    'status'    => $typeMap[$ext],
    'data_uri'  => $dataUri,
    'mime'      => $mimeMap[$ext],
    'file_name' => $note['file_name'],
]);
exit();
?>