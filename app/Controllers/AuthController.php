<?php
namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

class AuthController
{
    private $user;
    
    public function __construct()
    {
        $this->user = new User();
    }
    
    /**
     * Register a new user
     */
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Missing required fields'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Validate email
        if (!v::email()->validate($data['email'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Validate username
        if (!v::alnum('_')->noWhitespace()->length(3, 20)->validate($data['username'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Username must be 3-20 characters, alphanumeric with underscores only'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Validate password
        if (!v::length(8, null)->validate($data['password'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Password must be at least 8 characters long'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Check if user already exists
        if ($this->user->findByUsernameOrEmail($data['email']) || $this->user->findByUsernameOrEmail($data['username'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Username or email already exists'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Create user
        $userId = $this->user->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'unverified'
        ]);
        
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Failed to create user'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
        
        // Success response
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'id' => $userId,
                'username' => $data['username'],
                'email' => $data['email']
            ]
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }
    
    /**
     * Login user
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['username']) || !isset($data['password'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Missing required fields'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Find user
        $user = $this->user->findByUsernameOrEmail($data['username']);
        
        // Check if user exists and password is correct
        if (!$user || !password_verify($data['password'], $user['password'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $user['status'] === 'unverified' ? 'Please verify your email' : 'Your account is inactive'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        // Generate JWT
        $now = time();
        $payload = [
            'sub' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => $now,
            'exp' => $now + (60 * 60 * 24) // Token expires in 24 hours
        ];
        
        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
        
        // Success response
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'token' => $jwt,
                'expires' => $now + (60 * 60 * 24),
                'user' => [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    
    /**
     * Get current user info
     */
    public function me(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        if (!$user || !isset($user->sub)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        // Get user from database
        $userData = $this->user->findById($user->sub);
        
        if (!$userData) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'User not found'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        // Success response
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $userData
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    
    /**
     * Verify email
     */
    public function verifyEmail(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        if (!isset($data['token'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Missing verification token'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        try {
            // In a real application, you would decode a special verification token
            // and extract the user ID. For this demo, we'll simulate this process:
            
            // Simulate token verification - in reality you would decode and verify a proper token
            $token = $data['token'];
            $userId = 1; // This would be extracted from the token
            
            // Update user status
            $success = $this->user->updateStatus($userId, 'active');
            
            if (!$success) {
                throw new \Exception('Failed to update user status');
            }
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Email verified successfully'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired verification token'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}
