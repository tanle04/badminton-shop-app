<?php

class AdminPanelService
{
    private $baseUrl;
    private $apiKey;
    private $timeout;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/admin-panel.php';
        
        $this->baseUrl = $config['base_url'];
        $this->apiKey = $config['api_key'];
        $this->timeout = $config['timeout'];
    }

    /**
     * Make HTTP request using cURL
     */
    private function makeRequest($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET' && $data) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log("AdminPanel API Error: " . $error);
            return null;
        }
        
        if ($httpCode >= 400) {
            error_log("AdminPanel API HTTP Error: " . $httpCode . " - " . $response);
            return null;
        }
        
        return json_decode($response, true);
    }

    /**
     * Khởi tạo conversation
     */
    public function initConversation($customerId, $subject = null)
    {
        $data = [
            'customer_id' => (int)$customerId,
            'subject' => $subject ?? 'Hỗ trợ từ Mobile App',
            'priority' => 'normal',
        ];
        
        return $this->makeRequest('POST', '/api/bridge/support/init-conversation', $data);
    }

    /**
     * Gửi tin nhắn
     */
    public function sendMessage($conversationId, $customerId, $message, $attachmentUrl = null)
    {
        $data = [
            'conversation_id' => $conversationId,
            'customer_id' => (int)$customerId,
            'message' => $message,
            'attachment_url' => $attachmentUrl,
            'attachment_name' => $attachmentUrl ? basename($attachmentUrl) : null,
        ];
        
        return $this->makeRequest('POST', '/api/bridge/support/send-message', $data);
    }

    /**
     * Lấy tin nhắn
     */
    public function getMessages($conversationId, $customerId, $page = 1)
    {
        $params = [
            'conversation_id' => $conversationId,
            'customer_id' => (int)$customerId,
            'page' => $page,
            'per_page' => 50,
        ];
        
        return $this->makeRequest('GET', '/api/bridge/support/messages', $params);
    }

    /**
     * Đếm tin nhắn chưa đọc
     */
    public function getUnreadCount($conversationId, $customerId)
    {
        $params = [
            'conversation_id' => $conversationId,
            'customer_id' => (int)$customerId,
        ];
        
        return $this->makeRequest('GET', '/api/bridge/support/unread-count', $params);
    }

    /**
     * Health check
     */
    public function healthCheck()
    {
        try {
            $result = $this->makeRequest('GET', '/api/bridge/support/health');
            return $result !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}