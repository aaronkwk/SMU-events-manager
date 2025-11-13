<?php
// sendbird_helper.php - Add this new file to handle all Sendbird API calls
// Place this file in your project root alongside config.php

class SendbirdHelper {
    private $apiHost;
    private $apiToken;
    private $maxRetries = 3;
    private $retryDelay = 1000; // milliseconds
    
    public function __construct() {
        $this->apiHost = rtrim(SENDBIRD_API_HOST, '/');
        $this->apiToken = SENDBIRD_API_TOKEN;
    }
    
    /**
     * Make Sendbird API request with retry logic and better error handling
     */
    public function request(string $method, string $path, ?array $body = null, array $options = []) {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $this->maxRetries) {
            try {
                return $this->executeRequest($method, $path, $body, $options);
            } catch (Exception $e) {
                $lastError = $e;
                $attempt++;
                
                // Don't retry on client errors (4xx)
                if (strpos($e->getMessage(), '4') === 0) {
                    throw $e;
                }
                
                // Exponential backoff
                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000 * pow(2, $attempt - 1));
                    error_log("Sendbird retry attempt $attempt for $method $path");
                }
            }
        }
        
        throw new RuntimeException("Sendbird request failed after {$this->maxRetries} attempts: " . $lastError->getMessage());
    }
    
    private function executeRequest(string $method, string $path, ?array $body, array $options) {
        $url = $this->apiHost . $path;
        $timeout = $options['timeout'] ?? 45; // Increased default timeout
        
        $ch = curl_init();
        
        $headers = ['Api-Token: ' . $this->apiToken];
        if ($method !== 'GET' && !isset($options['multipart'])) {
            $headers[] = 'Content-Type: application/json';
        }
        
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10, // Separate connection timeout
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 30,
            CURLOPT_TCP_KEEPINTVL => 10,
        ];
        
        // Add HTTP/2 support for better performance
        if (defined('CURL_HTTP_VERSION_2_0')) {
            $curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        }
        
        if ($body !== null) {
            if (isset($options['multipart'])) {
                $curlOptions[CURLOPT_POSTFIELDS] = $body;
            } else {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($body);
            }
        }
        
        curl_setopt_array($ch, $curlOptions);
        
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        
        curl_close($ch);
        
        // Log slow requests
        if ($totalTime > 5) {
            error_log("Slow Sendbird request: $method $path took {$totalTime}s");
        }
        
        if ($errno !== 0) {
            throw new RuntimeException("cURL error ($errno): $error for $method $path");
        }
        
        if ($httpCode >= 400) {
            $json = $response ? json_decode($response, true) : [];
            $message = $json['message'] ?? "HTTP $httpCode";
            throw new RuntimeException("$httpCode: $message");
        }
        
        return $response ? json_decode($response, true) : [];
    }
    
    /**
     * Batch API calls using cURL multi handle for parallel execution
     */
    public function batchRequest(array $requests) {
        $mh = curl_multi_init();
        $handles = [];
        
        foreach ($requests as $key => $req) {
            $url = $this->apiHost . $req['path'];
            $ch = curl_init();
            
            $headers = ['Api-Token: ' . $this->apiToken];
            if (($req['method'] ?? 'GET') !== 'GET') {
                $headers[] = 'Content-Type: application/json';
            }
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => $req['method'] ?? 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            
            if (isset($req['body'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req['body']));
            }
            
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        
        // Execute all handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.1);
        } while ($running > 0);
        
        // Collect results
        $results = [];
        foreach ($handles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            $results[$key] = [
                'http_code' => $httpCode,
                'data' => $response ? json_decode($response, true) : null,
                'success' => $httpCode >= 200 && $httpCode < 300
            ];
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        return $results;
    }
}
?>