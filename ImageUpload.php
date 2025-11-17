<?php
/**
 * ImageUpload Class - Handles image uploads for listings
 */
class ImageUpload {
    private $conn;
    private $upload_dir = 'uploads/listings/';
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $max_file_size = 5242880; // 5MB
    private $max_images_per_listing = 10;

    public function __construct($db) {
        $this->conn = $db;
        
        // Create upload directory if it doesn't exist
        if(!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    // Upload image
    public function uploadImage($listing_id, $file) {
        // Validate file
        $validation = $this->validateImage($file);
        if($validation !== true) {
            return ['success' => false, 'error' => $validation];
        }

        // Check max images limit
        if(!$this->canUploadMore($listing_id)) {
            return ['success' => false, 'error' => "Maximum {$this->max_images_per_listing} images allowed per listing"];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '_' . time() . '.' . $extension;
        $filepath = $this->upload_dir . $filename;

        // Move uploaded file
        if(!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Failed to save image'];
        }

        // Get image dimensions
        list($width, $height) = getimagesize($filepath);

        // Create thumbnail
        $this->createThumbnail($filepath, $width, $height);

        // Save to database
        $is_primary = $this->isFirstImage($listing_id);
        
        $query = "INSERT INTO listing_images 
                  (listing_id, filename, original_filename, file_path, file_size, 
                   mime_type, width, height, is_primary) 
                  VALUES (:listing_id, :filename, :original, :filepath, :filesize, 
                          :mimetype, :width, :height, :is_primary)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listing_id);
        $stmt->bindParam(':filename', $filename);
        $stmt->bindParam(':original', $file['name']);
        $stmt->bindParam(':filepath', $filepath);
        $stmt->bindParam(':filesize', $file['size']);
        $stmt->bindParam(':mimetype', $file['type']);
        $stmt->bindParam(':width', $width);
        $stmt->bindParam(':height', $height);
        $stmt->bindParam(':is_primary', $is_primary, PDO::PARAM_BOOL);
        
        if($stmt->execute()) {
            return [
                'success' => true, 
                'image_id' => $this->conn->lastInsertId(),
                'filename' => $filename,
                'filepath' => $filepath
            ];
        }
        
        // Clean up file if database insert fails
        unlink($filepath);
        return ['success' => false, 'error' => 'Failed to save image data'];
    }

    // Validate image
    private function validateImage($file) {
        if($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload error occurred';
        }

        if($file['size'] > $this->max_file_size) {
            return 'File size exceeds 5MB limit';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if(!in_array($mime_type, $this->allowed_types)) {
            return 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed';
        }

        // Check if it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if($image_info === false) {
            return 'File is not a valid image';
        }

        return true;
    }

    // Check if can upload more images
    private function canUploadMore($listing_id) {
        $query = "SELECT COUNT(*) as count FROM listing_images WHERE listing_id = :listing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listing_id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['count'] < $this->max_images_per_listing;
    }

    // Check if this is the first image
    private function isFirstImage($listing_id) {
        $query = "SELECT COUNT(*) as count FROM listing_images WHERE listing_id = :listing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listing_id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['count'] == 0;
    }

    // Get listing images
    public function getListingImages($listing_id) {
        $query = "SELECT * FROM listing_images 
                  WHERE listing_id = :listing_id 
                  ORDER BY is_primary DESC, display_order ASC, uploaded_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listing_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Get primary image
    public function getPrimaryImage($listing_id) {
        $query = "SELECT * FROM listing_images 
                  WHERE listing_id = :listing_id AND is_primary = TRUE 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listing_id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Set primary image
    public function setPrimaryImage($image_id, $listing_id) {
        // Remove primary flag from all images in listing
        $query = "UPDATE listing_images SET is_primary = FALSE WHERE listing_id = :listing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listing_id);
        $stmt->execute();
        
        // Set new primary
        $query = "UPDATE listing_images SET is_primary = TRUE WHERE id = :image_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':image_id', $image_id);
        return $stmt->execute();
    }

    // Delete image
    public function deleteImage($image_id, $user_id) {
        // Get image data
        $query = "SELECT li.*, l.user_id 
                  FROM listing_images li
                  LEFT JOIN listings l ON li.listing_id = l.id
                  WHERE li.id = :image_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':image_id', $image_id);
        $stmt->execute();
        $image = $stmt->fetch();
        
        if(!$image || $image['user_id'] != $user_id) {
            return false;
        }

        // Delete file
        if(file_exists($image['file_path'])) {
            unlink($image['file_path']);
        }

        // Delete thumbnail
        $thumb_path = str_replace('.', '_thumb.', $image['file_path']);
        if(file_exists($thumb_path)) {
            unlink($thumb_path);
        }

        // Delete from database
        $query = "DELETE FROM listing_images WHERE id = :image_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':image_id', $image_id);
        
        return $stmt->execute();
    }

    // Create thumbnail
    private function createThumbnail($filepath, $width, $height) {
        $thumb_width = 300;
        $thumb_height = ($height / $width) * $thumb_width;

        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        
        // Create image resource
        switch(strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($filepath);
                break;
            case 'png':
                $source = imagecreatefrompng($filepath);
                break;
            case 'gif':
                $source = imagecreatefromgif($filepath);
                break;
            case 'webp':
                $source = imagecreatefromwebp($filepath);
                break;
            default:
                return false;
        }

        // Create thumbnail
        $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);

        // Save thumbnail
        $thumb_path = str_replace('.', '_thumb.', $filepath);
        
        switch(strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumb, $thumb_path, 85);
                break;
            case 'png':
                imagepng($thumb, $thumb_path, 8);
                break;
            case 'gif':
                imagegif($thumb, $thumb_path);
                break;
            case 'webp':
                imagewebp($thumb, $thumb_path, 85);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumb);
    }
}
?>