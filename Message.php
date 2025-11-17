<?php
class Message {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getOrCreateConversation($user1_id, $user2_id, $listing_id = null) {
        // Ensure user1_id is always smaller for consistency
        if($user1_id > $user2_id) {
            $temp = $user1_id;
            $user1_id = $user2_id;
            $user2_id = $temp;
        }
        
        // Check if conversation exists between these users
        $query = "SELECT id FROM conversations 
                  WHERE (user1_id = :user1_id AND user2_id = :user2_id) 
                  OR (user1_id = :user2_id2 AND user2_id = :user1_id2)
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        $stmt->bindParam(':user2_id2', $user1_id);
        $stmt->bindParam(':user1_id2', $user2_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $result = $stmt->fetch();
            return $result['id'];
        }
        
        // Check if listing_id column exists
        try {
            $check_column = "SHOW COLUMNS FROM conversations LIKE 'listing_id'";
            $stmt = $this->db->prepare($check_column);
            $stmt->execute();
            $has_listing_id = $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            $has_listing_id = false;
        }
        
        // Create new conversation with or without listing_id
        if($has_listing_id) {
            $query = "INSERT INTO conversations (user1_id, user2_id, listing_id, created_at) 
                      VALUES (:user1_id, :user2_id, :listing_id, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user1_id', $user1_id);
            $stmt->bindParam(':user2_id', $user2_id);
            $stmt->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
        } else {
            $query = "INSERT INTO conversations (user1_id, user2_id, created_at) 
                      VALUES (:user1_id, :user2_id, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user1_id', $user1_id);
            $stmt->bindParam(':user2_id', $user2_id);
        }
        
        if($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function send($sender_id, $receiver_id, $message_text, $listing_id = null) {
        try {
            // Get or create conversation
            $conversation_id = $this->getOrCreateConversation($sender_id, $receiver_id, $listing_id);
            
            if(!$conversation_id) {
                return false;
            }
            
            // Insert message
            $query = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message_text, created_at) 
                      VALUES (:conversation_id, :sender_id, :receiver_id, :message_text, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':conversation_id', $conversation_id);
            $stmt->bindParam(':sender_id', $sender_id);
            $stmt->bindParam(':receiver_id', $receiver_id);
            $stmt->bindParam(':message_text', $message_text);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error sending message: " . $e->getMessage());
            return false;
        }
    }
    
    public function getConversation($conversation_id, $user_id) {
        $query = "SELECT m.*, 
                  sender.username as sender_username,
                  receiver.username as receiver_username
                  FROM messages m
                  LEFT JOIN users sender ON m.sender_id = sender.id
                  LEFT JOIN users receiver ON m.receiver_id = receiver.id
                  WHERE m.conversation_id = :conversation_id
                  ORDER BY m.created_at ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function markAsRead($conversation_id, $user_id) {
        $query = "UPDATE messages 
                  SET is_read = TRUE 
                  WHERE conversation_id = :conversation_id 
                  AND receiver_id = :user_id 
                  AND is_read = FALSE";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    public function getTotalUnreadCount($user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM messages 
                      WHERE receiver_id = :user_id AND is_read = FALSE";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch(PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getUnreadCountForConversation($conversation_id, $user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM messages 
                      WHERE conversation_id = :conversation_id 
                      AND receiver_id = :user_id 
                      AND is_read = FALSE";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':conversation_id', $conversation_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch(PDOException $e) {
            error_log("Error getting conversation unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    public function deleteConversation($conversation_id, $user_id) {
        // Verify user is part of the conversation
        $query = "SELECT id FROM conversations 
                  WHERE id = :conversation_id 
                  AND (user1_id = :user_id OR user2_id = :user_id)
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            return false;
        }
        
        // Delete all messages in conversation
        $query = "DELETE FROM messages WHERE conversation_id = :conversation_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->execute();
        
        // Delete conversation
        $query = "DELETE FROM conversations WHERE id = :conversation_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':conversation_id', $conversation_id);
        
        return $stmt->execute();
    }
    
    public function blockUser($blocker_id, $blocked_id) {
        try {
            // Create user_blocks table if it doesn't exist
            $create_table = "CREATE TABLE IF NOT EXISTS user_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                blocker_id INT NOT NULL,
                blocked_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_block (blocker_id, blocked_id),
                FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $this->db->exec($create_table);
            
            $query = "INSERT INTO user_blocks (blocker_id, blocked_id) 
                      VALUES (:blocker_id, :blocked_id)
                      ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':blocker_id', $blocker_id);
            $stmt->bindParam(':blocked_id', $blocked_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error blocking user: " . $e->getMessage());
            return false;
        }
    }
    
    public function isBlocked($user_id, $other_user_id) {
        try {
            $query = "SELECT id FROM user_blocks 
                      WHERE (blocker_id = :user_id AND blocked_id = :other_user_id)
                      OR (blocker_id = :other_user_id2 AND blocked_id = :user_id2)
                      LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':other_user_id', $other_user_id);
            $stmt->bindParam(':other_user_id2', $other_user_id);
            $stmt->bindParam(':user_id2', $user_id);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>