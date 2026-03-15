<?php
// =============================================
//  StudyVault — Get Notes API
//  File: api/getNotesAPI.php
//  Method: GET
//  Params: ?q=search&cat=category_id&filter=mine
//  Returns: JSON array of notes
// =============================================

session_start();
header('Content-Type: application/json');
include_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit();
}

$conn   = getDB();
$userId = $_SESSION['user_id'];

$search   = isset($_GET['q'])      ? trim($_GET['q'])      : '';
$catId    = isset($_GET['cat'])    ? intval($_GET['cat'])  : 0;
$myNotes  = isset($_GET['filter']) && $_GET['filter'] === 'mine';

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $where .= " AND (n.title LIKE ? OR n.subject LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}
if ($catId > 0) {
    $where .= " AND n.category_id = ?";
    $params[] = $catId;
    $types .= "i";
}
if ($myNotes) {
    $where .= " AND n.uploaded_by = ?";
    $params[] = $userId;
    $types .= "i";
}

$sql  = "SELECT n.id, n.title, n.subject, n.file_name, n.file_path, n.downloads,
                n.upload_date, u.name as uploader, c.name as category
         FROM notes n
         JOIN users u ON n.uploaded_by = u.id
         LEFT JOIN categories c ON n.category_id = c.id
         $where
         ORDER BY n.upload_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[] = [
        'id'          => $row['id'],
        'title'       => $row['title'],
        'subject'     => $row['subject'],
        'category'    => $row['category'],
        'file_name'   => $row['file_name'],
        'ext'         => strtoupper(pathinfo($row['file_name'], PATHINFO_EXTENSION)),
        'downloads'   => $row['downloads'],
        'uploader'    => $row['uploader'],
        'upload_date' => date('M j, Y', strtotime($row['upload_date'])),
        'download_url'=> 'api/downloadAPI.php?id=' . $row['id']
    ];
}

echo json_encode([
    'status' => 'success',
    'count'  => count($notes),
    'notes'  => $notes
]);

$stmt->close();
$conn->close();
?>