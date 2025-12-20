<?php

namespace Controllers;

use Models\ChatMessage;
use Helpers\SessionManager;
use Middleware\AuthMiddleware;

class ChatController
{
    private \mysqli $conn;
    private ChatMessage $chat;
    private SessionManager $session;
    private AuthMiddleware $auth;

    public function __construct(\mysqli $conn)
    {
        $this->session = SessionManager::getInstance();
        $this->auth    = new AuthMiddleware();
        $this->conn    = $conn;
        $this->chat    = new ChatMessage($this->conn);
    }

    // $otherKey is 'instructor_id' on student page, 'student_id' on instructor page
    public function sendMessage(string $otherKey, string $messageKey): array
    {
        if (!$this->session->isLoggedIn()) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $currentUser = $this->auth->getCurrentUser();
        $senderId    = (int)($currentUser['id'] ?? 0);
        $senderRole  = $currentUser['role'] ?? '';

        // Student → instructor or instructor → student, depending on route
        $receiverId = (int)($_POST[$otherKey] ?? 0);

        $raw = $_POST[$messageKey] ?? '';
        $message = is_array($raw) ? trim($raw['text'] ?? '') : trim($raw);

        if (!$senderId || !$receiverId || $message === '') {
            return ['success' => false, 'message' => 'Missing required data'];
        }

        if (strlen($message) > 5000) {
            return ['success' => false, 'message' => 'Message too long (max 5000 characters)'];
        }

        $this->chat->addMessage($senderId, $receiverId, $message);
        return ['success' => true, 'message' => 'Message sent successfully'];
    }

    public function fetchConversation(string $otherKey): array
    {
        if (!$this->session->isLoggedIn()) {
            return ['success' => false, 'messages' => []];
        }

        $currentUser = $this->auth->getCurrentUser();
        $userId      = (int)($currentUser['id'] ?? 0);
        $userRole    = $currentUser['role'] ?? '';

        $otherId = (int)($_GET[$otherKey] ?? 0);

        if (!$userId || !$otherId) {
            return ['success' => false, 'messages' => []];
        }

        $rows = $this->chat->getConversation($userId, $otherId);

        $messages = [];
        foreach ($rows as $row) {
            $isCurrentUser = ($row['sender_id'] == $userId);
            $messages[] = [
                'sender'      => $isCurrentUser ? $userRole : ($userRole === 'student' ? 'instructor' : 'student'),
                'sender_name' => htmlspecialchars($row['sender_name'] ?? 'Unknown'),
                'message'     => htmlspecialchars($row['message']),
                'time'        => date('M j, g:i A', strtotime($row['created_at'])),
                'timestamp'   => strtotime($row['created_at']),
                'is_mine'     => $isCurrentUser,
            ];
        }

        return [
            'success'  => true,
            'messages' => $messages,
            'count'    => count($messages),
        ];
    }
}
