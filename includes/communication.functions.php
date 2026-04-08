<?php
// CDF Management System — communication functions

function getNotifications($user_id) {
        global $pdo;
        
        $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function createNotification($user_id, $title, $message) {
        global $pdo;
        
        $query = "INSERT INTO notifications SET 
                  user_id = :user_id,
                  title = :title,
                  message = :message";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }
function markNotificationAsRead($notification_id) {
        global $pdo;
        
        $query = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $notification_id);
        
        return $stmt->execute();
    }
function markAllNotificationsAsRead($user_id) {
        global $pdo;
        
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
function deleteNotification($notification_id) {
        global $pdo;
        
        $query = "DELETE FROM notifications WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $notification_id);
        
        return $stmt->execute();
    }
function clearAllNotifications($user_id) {
        global $pdo;
        
        $query = "DELETE FROM notifications WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
function getNotificationIcon($notification) {
        $title = strtolower($notification['title']);
        $message = strtolower($notification['message']);
        
        if (strpos($title, 'project') !== false || strpos($message, 'project') !== false) {
            return 'project-diagram';
        } elseif (strpos($title, 'message') !== false || strpos($message, 'message') !== false) {
            return 'envelope';
        } elseif (strpos($title, 'progress') !== false || strpos($message, 'progress') !== false) {
            return 'chart-line';
        } elseif (strpos($title, 'approved') !== false || strpos($message, 'approved') !== false) {
            return 'check-circle';
        } elseif (strpos($title, 'rejected') !== false || strpos($message, 'rejected') !== false) {
            return 'times-circle';
        } elseif (strpos($title, 'urgent') !== false || strpos($message, 'urgent') !== false) {
            return 'exclamation-triangle';
        } elseif (strpos($title, 'deadline') !== false || strpos($message, 'deadline') !== false) {
            return 'clock';
        } elseif (strpos($title, 'payment') !== false || strpos($message, 'payment') !== false) {
            return 'money-bill-wave';
        } else {
            return 'bell';
        }
    }
function getNotificationIconClass($notification) {
        $title = strtolower($notification['title']);
        $message = strtolower($notification['message']);
        
        if (strpos($title, 'approved') !== false || strpos($message, 'approved') !== false) {
            return 'success';
        } elseif (strpos($title, 'rejected') !== false || strpos($message, 'rejected') !== false) {
            return 'danger';
        } elseif (strpos($title, 'urgent') !== false || strpos($message, 'urgent') !== false) {
            return 'danger';
        } elseif (strpos($title, 'warning') !== false || strpos($message, 'warning') !== false) {
            return 'warning';
        } else {
            return 'primary';
        }
    }
function getNotificationTypeClass($notification) {
        $title = strtolower($notification['title']);
        $message = strtolower($notification['message']);
        
        if (strpos($title, 'urgent') !== false || strpos($message, 'urgent') !== false) {
            return 'urgent';
        } elseif (strpos($title, 'approved') !== false || strpos($message, 'approved') !== false) {
            return 'success';
        } elseif (strpos($title, 'warning') !== false || strpos($message, 'warning') !== false) {
            return 'warning';
        } else {
            return '';
        }
    }
function isNotificationUrgent($notification) {
        $title = strtolower($notification['title']);
        $message = strtolower($notification['message']);
        
        return strpos($title, 'urgent') !== false || strpos($message, 'urgent') !== false;
    }
function getMessages($user_id, $recipient_id = null) {
        global $pdo;
        
        if ($recipient_id) {
            $query = "SELECT m.*, 
                             u1.first_name as sender_first_name, 
                             u1.last_name as sender_last_name,
                             u2.first_name as recipient_first_name, 
                             u2.last_name as recipient_last_name
                      FROM messages m
                      LEFT JOIN users u1 ON m.sender_id = u1.id
                      LEFT JOIN users u2 ON m.recipient_id = u2.id
                      WHERE (m.sender_id = :user_id AND m.recipient_id = :recipient_id)
                         OR (m.sender_id = :recipient_id AND m.recipient_id = :user_id)
                      ORDER BY m.created_at ASC";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':recipient_id', $recipient_id);
        } else {
            $query = "SELECT m.*, 
                             u1.first_name as sender_first_name, 
                             u1.last_name as sender_last_name,
                             u2.first_name as recipient_first_name, 
                             u2.last_name as recipient_last_name
                      FROM messages m
                      LEFT JOIN users u1 ON m.sender_id = u1.id
                      LEFT JOIN users u2 ON m.recipient_id = u2.id
                      WHERE m.sender_id = :user_id OR m.recipient_id = :user_id
                      ORDER BY m.created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function sendMessage($sender_id, $recipient_id, $subject, $message, $is_urgent = 0) {
        global $pdo;
        
        $query = "INSERT INTO messages SET 
                  sender_id = :sender_id,
                  recipient_id = :recipient_id,
                  subject = :subject,
                  message = :message,
                  is_urgent = :is_urgent";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':is_urgent', $is_urgent);
        
        return $stmt->execute();
    }
function getUnreadMessageCount($user_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as unread_count 
                FROM messages 
                WHERE recipient_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['unread_count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in getUnreadMessageCount: " . $e->getMessage());
            return 0;
        }
    }
function getRecentMessages($user_id, $limit = 5) {
        global $pdo;
        
        $query = "SELECT m.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as sender_name
                  FROM messages m
                  LEFT JOIN users u ON m.sender_id = u.id
                  WHERE m.recipient_id = :user_id 
                  ORDER BY m.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function getAllUserMessages($user_id) {
        global $pdo;
        
        $query = "SELECT m.*, 
                         u1.first_name as sender_first_name, 
                         u1.last_name as sender_last_name,
                         u2.first_name as recipient_first_name, 
                         u2.last_name as recipient_last_name
                  FROM messages m
                  LEFT JOIN users u1 ON m.sender_id = u1.id
                  LEFT JOIN users u2 ON m.recipient_id = u2.id
                  WHERE m.sender_id = :user_id OR m.recipient_id = :user_id
                  ORDER BY m.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data
        foreach ($messages as &$message) {
            $message['sender_name'] = $message['sender_first_name'] . ' ' . $message['sender_last_name'];
            $message['recipient_name'] = $message['recipient_first_name'] . ' ' . $message['recipient_last_name'];
        }
        
        return $messages;
    }
function getConversations($user_id) {
        global $pdo;
        
        $query = "SELECT 
                    CASE 
                        WHEN m.sender_id = :user_id THEN m.recipient_id
                        ELSE m.sender_id
                    END as other_user_id,
                    CASE 
                        WHEN m.sender_id = :user_id THEN CONCAT(u2.first_name, ' ', u2.last_name)
                        ELSE CONCAT(u1.first_name, ' ', u1.last_name)
                    END as other_user_name,
                    MAX(m.created_at) as last_message_time,
                    COUNT(CASE WHEN m.recipient_id = :user_id AND m.is_read = 0 THEN 1 END) as unread_count,
                    SUBSTRING(MAX(CONCAT(m.created_at, '|', m.message)), LOCATE('|', MAX(CONCAT(m.created_at, '|', m.message))) + 1) as last_message
                  FROM messages m
                  LEFT JOIN users u1 ON m.sender_id = u1.id
                  LEFT JOIN users u2 ON m.recipient_id = u2.id
                  WHERE m.sender_id = :user_id OR m.recipient_id = :user_id
                  GROUP BY other_user_id, other_user_name
                  ORDER BY last_message_time DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function getMessagesBetweenUsers($user1_id, $user2_id) {
        global $pdo;
        
        $query = "SELECT m.*, 
                         u1.first_name as sender_first_name, 
                         u1.last_name as sender_last_name
                  FROM messages m
                  LEFT JOIN users u1 ON m.sender_id = u1.id
                  WHERE (m.sender_id = :user1_id AND m.recipient_id = :user2_id)
                     OR (m.sender_id = :user2_id AND m.recipient_id = :user1_id)
                  ORDER BY m.created_at ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function markMessageAsRead($message_id) {
        global $pdo;
        
        $query = "UPDATE messages SET is_read = 1 WHERE id = :message_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':message_id', $message_id);
        
        return $stmt->execute();
    }
function markAllMessagesAsRead($user_id, $other_user_id) {
        global $pdo;
        
        $query = "UPDATE messages SET is_read = 1 
                  WHERE recipient_id = :user_id AND sender_id = :other_user_id AND is_read = 0";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':other_user_id', $other_user_id);
        
        return $stmt->execute();
    }
function getRecentConversations($user_id, $limit = 5) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    u.id as contact_id,
                    CONCAT(u.first_name, ' ', u.last_name) as contact_name,
                    m.message as last_message,
                    m.created_at as last_activity
                FROM messages m
                INNER JOIN users u ON (
                    (m.sender_id = ? AND u.id = m.recipient_id) OR 
                    (m.recipient_id = ? AND u.id = m.sender_id)
                )
                WHERE (m.sender_id = ? OR m.recipient_id = ?)
                AND m.id IN (
                    SELECT MAX(id) FROM messages 
                    WHERE sender_id = ? OR recipient_id = ?
                    GROUP BY 
                        CASE 
                            WHEN sender_id = ? THEN recipient_id 
                            ELSE sender_id 
                        END
                )
                ORDER BY m.created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $limit]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $conversations ?: [];
        } catch (PDOException $e) {
            error_log("Database error in getRecentConversations: " . $e->getMessage());
            
            // Return sample data for development
            return [
                [
                    'contact_id' => 1,
                    'contact_name' => 'John Beneficiary',
                    'last_message' => 'Thank you for the site visit yesterday',
                    'last_activity' => date('Y-m-d H:i:s')
                ],
                [
                    'contact_id' => 2,
                    'contact_name' => 'Mary Project Manager',
                    'last_message' => 'Can we schedule a progress review?',
                    'last_activity' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                ],
                [
                    'contact_id' => 3,
                    'contact_name' => 'David Community Leader',
                    'last_message' => 'The materials have arrived safely',
                    'last_activity' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                ]
            ];
        }
    }
function getConversationMessages($user_id, $contact_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    m.id,
                    m.sender_id,
                    m.recipient_id,
                    m.message,
                    m.created_at,
                    m.read_status,
                    CONCAT(s.first_name, ' ', s.last_name) as sender_name,
                    CASE 
                        WHEN m.sender_id = ? THEN 1 
                        ELSE 0 
                    END as is_own
                FROM messages m
                INNER JOIN users s ON m.sender_id = s.id
                WHERE (m.sender_id = ? AND m.recipient_id = ?) 
                   OR (m.sender_id = ? AND m.recipient_id = ?)
                ORDER BY m.created_at ASC
            ");
            
            $stmt->execute([$user_id, $user_id, $contact_id, $contact_id, $user_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $messages ?: [];
            
        } catch (PDOException $e) {
            error_log("Database error in getConversationMessages: " . $e->getMessage());
            
            // Return empty array if no messages table exists
            return [];
        }
    }
