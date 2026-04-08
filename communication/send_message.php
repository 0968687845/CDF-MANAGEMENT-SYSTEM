<?php
require_once '../functions.php';
requireRole('officer');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_id'], $_POST['message'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo 'error';
        exit;
    }
    $sender_id = $_SESSION['user_id'];
    $recipient_id = $_POST['recipient_id'];
    $message = trim($_POST['message']);
    $subject = $_POST['subject'] ?? 'Project Update';
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    
    if (!empty($message)) {
        $success = sendMessage($sender_id, $recipient_id, $subject, $message, $is_urgent);
        echo $success ? 'success' : 'error';
    }
}
?>