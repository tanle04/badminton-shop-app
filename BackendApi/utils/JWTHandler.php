<?php
/**
 * JWT Handler Class
 * Location: /public_html/utils/JWTHandler.php hoặc /public_html/api/utils/JWTHandler.php
 */

class JWTHandler {
    
    private $secret_key;
    
    public function __construct() {
        // Lấy secret key từ environment hoặc dùng default
        $this->secret_key = getenv('JWT_SECRET') ?: 'your_secret_key_here_change_this';
    }
    
    /**
     * Decode JWT token
     * 
     * @param string $token
     * @return object|null
     */
    public function decodeToken($token) {
        try {
            // Split token into parts
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                error_log('❌ JWT: Invalid token format (expected 3 parts)');
                return null;
            }
            
            list($header, $payload, $signature) = $parts;
            
            // Verify signature
            $valid_signature = $this->generateSignature($header, $payload);
            
            if (!hash_equals($valid_signature, $signature)) {
                error_log('❌ JWT: Invalid signature');
                return null;
            }
            
            // Decode payload
            $decoded_payload = $this->base64UrlDecode($payload);
            $data = json_decode($decoded_payload);
            
            if (!$data) {
                error_log('❌ JWT: Failed to decode payload JSON');
                return null;
            }
            
            // Check expiration
            if (isset($data->exp) && $data->exp < time()) {
                error_log('❌ JWT: Token expired');
                return null;
            }
            
            error_log('✅ JWT: Token decoded successfully for customer: ' . ($data->customerID ?? 'unknown'));
            
            return $data;
            
        } catch (Exception $e) {
            error_log('❌ JWT Decode Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Encode JWT token
     * 
     * @param array $payload
     * @return string
     */
    public function encodeToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        // Add expiration if not set (24 hours)
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + (24 * 60 * 60);
        }
        
        $payload_json = json_encode($payload);
        
        $header_encoded = $this->base64UrlEncode($header);
        $payload_encoded = $this->base64UrlEncode($payload_json);
        
        $signature = $this->generateSignature($header_encoded, $payload_encoded);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature;
    }
    
    /**
     * Generate signature
     */
    private function generateSignature($header, $payload) {
        $data = $header . '.' . $payload;
        $signature = hash_hmac('sha256', $data, $this->secret_key, true);
        return $this->base64UrlEncode($signature);
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}