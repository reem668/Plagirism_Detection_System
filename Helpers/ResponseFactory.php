<?php
namespace Helpers;

/**
 * ResponseFactory - Factory Pattern Implementation
 * Centralizes JSON response creation for consistency
 */
class ResponseFactory {
    
    /**
     * Create success response
     */
    public static function success($message, $data = null) {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
    
    /**
     * Create error response
     */
    public static function error($message, $code = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        http_response_code($code);
        return $response;
    }
    
    /**
     * Create paginated response
     */
    public static function paginated($data, $pagination) {
        return [
            'success' => true,
            'data' => $data,
            'pagination' => $pagination
        ];
    }
    
    /**
     * Send JSON response and exit
     */
    public static function json($response) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

