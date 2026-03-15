<?php
// =============================================
//  StudyVault — Register API  (DISABLED)
//  Account creation is now handled by Admin only
//  via api/createUserAPI.php
// =============================================

header('Content-Type: application/json');
http_response_code(403);
echo json_encode([
    'status'  => 'error',
    'message' => 'Self-registration is disabled. Please contact an administrator to create an account.'
]);
exit();
?>
