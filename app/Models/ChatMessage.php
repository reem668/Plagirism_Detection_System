<?php
namespace Models;

class ChatMessage
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function addMessage(int $senderId, int $receiverId, string $message): void
    {
        $sql = "INSERT INTO chat_messages (sender_id, receiver_id, message, created_at)
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Prepare failed: '.$this->conn->error);
        }
        $stmt->bind_param('iis', $senderId, $receiverId, $message);
        $stmt->execute();
        $stmt->close();
    }

    public function getConversation(int $userId, int $otherId): array
    {
        $sql = "
            SELECT cm.sender_id, cm.message, cm.created_at,
                   u.name AS sender_name, u.role AS sender_role
            FROM chat_messages cm
            LEFT JOIN users u ON cm.sender_id = u.id
            WHERE (cm.sender_id = ? AND cm.receiver_id = ?)
               OR (cm.sender_id = ? AND cm.receiver_id = ?)
            ORDER BY cm.created_at ASC
        ";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Prepare failed: '.$this->conn->error);
        }
        $stmt->bind_param('iiii', $userId, $otherId, $otherId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
}
