<?php
namespace App\Models;

use App\Helpers\Database;

class User
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return int|bool User ID or false on failure
     */
    public function create(array $data)
    {
        $query = "INSERT INTO Users (username, email, password, status) 
                  VALUES (:username, :email, :password, :status)";
        
        try {
            $stmt = $this->db->prepare($query);
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->bindValue(':username', $data['username']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':password', $data['password']);
            $stmt->bindValue(':status', $data['status'] ?? 'unverified');
            
            $stmt->execute();
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // Log error
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Find a user by username or email
     * 
     * @param string $usernameOrEmail Username or email
     * @return array|false User data or false if not found
     */
    public function findByUsernameOrEmail(string $usernameOrEmail)
    {
        $query = "SELECT * FROM Users WHERE username = :identifier OR email = :identifier LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':identifier', $usernameOrEmail);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ? $result : false;
        } catch (\PDOException $e) {
            // Log error
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|false User data or false if not found
     */
    public function findById(int $id)
    {
        $query = "SELECT id, username, email, role, status, created_at FROM Users WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ? $result : false;
        } catch (\PDOException $e) {
            // Log error
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user status
     * 
     * @param int $id User ID
     * @param string $status New status
     * @return bool Success or failure
     */
    public function updateStatus(int $id, string $status)
    {
        $query = "UPDATE Users SET status = :status WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Log error
            error_log($e->getMessage());
            return false;
        }
    }
}
