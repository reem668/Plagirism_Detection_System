<?php
namespace Models;

class Submission {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    /**
     * Create a new submission
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO submissions 
            (user_id, teacher, text_content, file_path, stored_name, file_size, similarity, exact_match, partial_match, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) die("Prepare failed: ".$this->conn->error);

        $stmt->bind_param(
            "issssiiii",
            $data['user_id'],
            $data['teacher'],
            $data['text_content'],
            $data['file_path'],
            $data['stored_name'],
            $data['file_size'],
            $data['similarity'],
            $data['exact_match'],
            $data['partial_match']
        );

        if (!$stmt->execute()) die("Execute failed: ".$stmt->error);

        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Get submissions for a user with optional status
     */
    public function getByUser(int $uid, string $status = 'active'): array {
        $stmt = $this->conn->prepare("
            SELECT * FROM submissions WHERE user_id = ? AND status=? ORDER BY created_at DESC
        ");
        $stmt->bind_param("is", $uid, $status);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Find submission by ID
     */
    public function find(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Get all active submissions text for plagiarism comparison
     */
    public function getAllSubmissions(): array {
        $res = $this->conn->query("SELECT text_content FROM submissions WHERE status='active'");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
}
