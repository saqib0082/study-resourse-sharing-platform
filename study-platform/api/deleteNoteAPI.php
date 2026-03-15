<?php
// =============================================
//  StudyVault — Delete Note API
//  File: api/deleteNoteAPI.php
//  Method: DELETE
//  Body: { "id": NOTE_ID }
//  Rules:
//    - Admin can delete any note
//    - Student can only delete their own note
// =============================================

session_start();
header('Content-Type: application/json');
include_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please login.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['status' => 'error', 'message' => 'Only DELETE method allowed.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$noteId  = isset($data['id']) ? intval($data['id']) : 0;
$userId  = $_SESSION['user_id'];
$role    = $_SESSION['user_role'];

if ($noteId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid note ID.']);
    exit();
}

$conn = getDB();

// Fetch note to verify ownership & get file path
$stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->bind_param("i", $noteId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Note not found.']);
    $stmt->close(); $conn->close();
    exit();
}

$note = $result->fetch_assoc();
$stmt->close();

// Check permission: admin can delete any, student only their own
if ($role !== 'admin' && $note['uploaded_by'] !== $userId) {
    echo json_encode(['status' => 'error', 'message' => 'You can only delete your own notes.']);
    $conn->close();
    exit();
}

// Delete the physical file from disk
$filePath = dirname(__DIR__) . '/' . $note['file_path'];
if (file_exists($filePath)) {
    unlink($filePath);
}

// Delete from database
$del = $conn->prepare("DELETE FROM notes WHERE id = ?");
$del->bind_param("i", $noteId);

if ($del->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Note deleted successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete note from database.']);
}

$del->close();
$conn->close();
?>