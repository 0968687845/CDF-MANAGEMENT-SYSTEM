<?php
require_once '../functions.php';
requireRole('officer');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_id'])) {
    $contact_id = $_POST['contact_id'];
    $user_id = $_SESSION['user_id'];
    
    $messages = getConversationMessages($user_id, $contact_id);
    $userData = getUserData();
    
    if (!empty($messages)) {
        foreach ($messages as $message) {
            $is_own = $message['is_own'];
            $avatar = $is_own ? 
                strtoupper(substr($userData['first_name'], 0, 1)) : 
                strtoupper(substr($message['sender_name'], 0, 1));
            ?>
            <div class="message <?php echo $is_own ? 'own' : ''; ?>">
                <div class="message-avatar"><?php echo $avatar; ?></div>
                <div class="message-content">
                    <p><?php echo htmlspecialchars($message['message']); ?></p>
                    <div class="message-time"><?php echo date('g:i A', strtotime($message['created_at'])); ?></div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div class="text-center py-4 text-muted">No messages yet. Start the conversation!</div>';
    }
}
?>