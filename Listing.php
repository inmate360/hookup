<?php
/**
 * Listing Class - Handles classified ads/listings
 */
class Listing {
    private $conn;
    private $table = 'listings';

    public $id;
    public $user_id;
    public $category_id;
    public $title;
    public $description;
    public $location;
    public $age;
    public $gender;
    public $seeking;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new listing
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, category_id, title, description, location, age, gender, seeking, expires_at) 
                  VALUES (:user_id, :category_id, :title, :description, :location, :age, :gender, :seeking, 
                          DATE_ADD(NOW(), INTERVAL 30 DAY))";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->location = htmlspecialchars(strip_tags($this->location));
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':age', $this->age);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':seeking', $this->seeking);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Get all listings with filters
    public function getAll($category_id = null, $location = null, $limit = 50, $offset = 0) {
        $query = "SELECT l.*, c.name as category_name, c.slug as category_slug 
                  FROM " . $this->table . " l
                  LEFT JOIN categories c ON l.category_id = c.id
                  WHERE l.status = 'active' AND l.expires_at > NOW()";
        
        if($category_id) {
            $query .= " AND l.category_id = :category_id";
        }
        
        if($location) {
            $query .= " AND l.location LIKE :location";
        }
        
        $query .= " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if($category_id) {
            $stmt->bindParam(':category_id', $category_id);
        }
        
        if($location) {
            $location_param = "%{$location}%";
            $stmt->bindParam(':location', $location_param);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get single listing by ID
    public function getById($id) {
        $query = "SELECT l.*, c.name as category_name, c.slug as category_slug,
                         u.username, u.id as poster_id
                  FROM " . $this->table . " l
                  LEFT JOIN categories c ON l.category_id = c.id
                  LEFT JOIN users u ON l.user_id = u.id
                  WHERE l.id = :id AND l.status = 'active'
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            // Increment view count
            $this->incrementViews($id);
            return $stmt->fetch();
        }
        
        return null;
    }

    // Get user's listings
    public function getUserListings($user_id) {
        $query = "SELECT l.*, c.name as category_name 
                  FROM " . $this->table . " l
                  LEFT JOIN categories c ON l.category_id = c.id
                  WHERE l.user_id = :user_id AND l.status != 'deleted'
                  ORDER BY l.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Update listing
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET title = :title, 
                      description = :description, 
                      location = :location,
                      age = :age,
                      gender = :gender,
                      seeking = :seeking,
                      category_id = :category_id
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->location = htmlspecialchars(strip_tags($this->location));
        
        // Bind
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':age', $this->age);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':seeking', $this->seeking);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }

    // Delete listing (soft delete)
    public function delete($id, $user_id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'deleted' 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    // Increment view count
    private function incrementViews($id) {
        $query = "UPDATE " . $this->table . " 
                  SET views = views + 1 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    // Search listings
    public function search($keyword, $limit = 50) {
        $query = "SELECT l.*, c.name as category_name 
                  FROM " . $this->table . " l
                  LEFT JOIN categories c ON l.category_id = c.id
                  WHERE l.status = 'active' 
                  AND l.expires_at > NOW()
                  AND (l.title LIKE :keyword OR l.description LIKE :keyword OR l.location LIKE :keyword)
                  ORDER BY l.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $search_term = "%{$keyword}%";
        $stmt->bindParam(':keyword', $search_term);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>