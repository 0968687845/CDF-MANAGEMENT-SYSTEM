<?php
// CDF Management System — utils functions

function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = sanitize($value);
            }
        } else {
            $input = trim($input);
            $input = stripslashes($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }
function validateNRC($nrc) {
        return preg_match('/^\d{6}\/\d{2}\/\d{1}$/', $nrc);
    }
function validatePhone($phone) {
        return preg_match('/^(\+260|0)[0-9]{9}$/', $phone);
    }
function validatePassword($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }
function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
function logActivity($user_id, $action, $description) {
        global $pdo;
        
        $query = "INSERT INTO activity_log SET 
                  user_id = :user_id,
                  action = :action,
                  description = :description,
                  ip_address = :ip_address";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        
        return $stmt->execute();
    }
function getActivityLog($user_id = null, $limit = 50) {
        global $pdo;
        
        if ($user_id) {
            $query = "SELECT al.*, u.first_name, u.last_name 
                      FROM activity_log al
                      LEFT JOIN users u ON al.user_id = u.id
                      WHERE al.user_id = :user_id
                      ORDER BY al.created_at DESC
                      LIMIT :limit";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
        } else {
            $query = "SELECT al.*, u.first_name, u.last_name 
                      FROM activity_log al
                      LEFT JOIN users u ON al.user_id = u.id
                      ORDER BY al.created_at DESC
                      LIMIT :limit";
            $stmt = $pdo->prepare($query);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function getRecentActivities($user_id, $limit = 5) {
        global $pdo;
        
        $query = "SELECT al.*, 
                         CASE 
                             WHEN al.action LIKE '%project%' THEN 'project-diagram'
                             WHEN al.action LIKE '%message%' THEN 'envelope'
                             WHEN al.action LIKE '%progress%' THEN 'sync-alt'
                             WHEN al.action LIKE '%expense%' THEN 'receipt'
                             ELSE 'history'
                         END as icon,
                         CASE 
                             WHEN al.action LIKE '%create%' THEN 'success'
                             WHEN al.action LIKE '%update%' THEN 'primary'
                             WHEN al.action LIKE '%delete%' THEN 'danger'
                             ELSE 'info'
                         END as type
                  FROM activity_log al
                  WHERE al.user_id = :user_id
                  ORDER BY al.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function formatDate($date, $format = 'M j, Y') {
        if (empty($date) || $date == '0000-00-00') {
            return 'N/A';
        }
        return date($format, strtotime($date));
    }
function formatCurrency($amount) {
        return 'ZMW ' . number_format($amount, 2);
    }
function getStatusBadgeClass($status) {
        switch($status) {
            case 'completed': return 'success';
            case 'in-progress': return 'primary';
            case 'planning': return 'info';
            case 'delayed': return 'danger';
            default: return 'secondary';
        }
    }
function getProgressBarClass($progress) {
        if ($progress >= 80) return 'success';
        if ($progress >= 50) return 'primary';
        if ($progress >= 30) return 'warning';
        return 'danger';
    }
function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $weeks = floor($diff->d / 7);
        $days = $diff->d - ($weeks * 7);

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        
        $values = array(
            'y' => $diff->y,
            'm' => $diff->m,
            'w' => $weeks,
            'd' => $days,
            'h' => $diff->h,
            'i' => $diff->i,
            's' => $diff->s,
        );
        
        foreach ($string as $k => &$v) {
            if ($values[$k]) {
                $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
function getConstituencies() {
        return [
            "Lusaka Central", "Ndola Central", "Livingstone", "Kitwe Central", "Chongwe",
            "Kabwe Central", "Mongu Central", "Solwezi Central", "Chipata Central", "Kasama Central",
            "Mufulira", "Choma", "Mazabuka", "Luangwa", "Petauke", "Mpika", "Chinsali", "Kapiri Mposhi",
            "Mansa", "Kalulushi", "Chililabombwe", "Chingola", "Kalomo", "Senanga", "Sesheke", "Kaoma",
            "Mumbwa", "Monze", "Gwembe", "Siavonga", "Katete", "Lundazi", "Chadiza", "Nyimba", "Isoka",
            "Nakonde", "Kasempa", "Mwinilunga", "Kabompo", "Zambezi", "Chavuma", "Mwense", "Nchelenge",
            "Kawambwa", "Samfya", "Mporokoso", "Kaputa", "Luwingu", "Mbala", "Milenge", "Chembe", "Mwansabombwe"
        ];
    }
function handleError($error) {
        error_log("CDF System Error: " . $error);
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "An error occurred. Please try again.";
        }
        return false;
    }
