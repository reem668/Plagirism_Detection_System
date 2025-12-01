<?php
namespace Tests\Support;

class FakeMysqli
{
    public $settings = [];
    public $submissions = [];
    public $connect_error = null;
    public $error = '';
    public $insert_id = 1;
    private $inTransaction = false;

    public function __construct(array $seed = [])
    {
        $this->settings = $seed['settings'] ?? [];
        $this->submissions = $seed['submissions'] ?? [];
    }

    public function begin_transaction(): void
    {
        $this->inTransaction = true;
    }

    public function commit(): void
    {
        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        $this->inTransaction = false;
    }

    public function query(string $sql)
    {
        $sql = trim($sql);
        $lower = strtolower($sql);

        if (strpos($lower, 'select setting_key') === 0) {
            $rows = [];
            foreach ($this->settings as $key => $value) {
                $rows[] = ['setting_key' => $key, 'setting_value' => $value];
            }
            return new FakeResult($rows);
        }

        if (strpos($lower, 'select count(*) as total from submissions') === 0) {
            return new FakeResult([['total' => count($this->submissions)]]);
        }

        if (strpos($lower, "select count(*) as completed from submissions where status = 'completed'") === 0) {
            $count = $this->countByStatus('completed');
            return new FakeResult([['completed' => $count]]);
        }

        if (strpos($lower, "select count(*) as processing from submissions where status = 'processing'") === 0) {
            $count = $this->countByStatus('processing');
            return new FakeResult([['processing' => $count]]);
        }

        if (strpos($lower, 'select avg(similarity) as avg_similarity') === 0) {
            $values = array_filter(array_column($this->submissions, 'similarity'), fn($v) => $v !== null);
            $avg = $values ? array_sum($values) / count($values) : 0;
            return new FakeResult([['avg_similarity' => $avg]]);
        }

        if (strpos($lower, 'select count(*) as high_risk') === 0) {
            $count = count(array_filter($this->submissions, fn($s) => $s['similarity'] > 70));
            return new FakeResult([['high_risk' => $count]]);
        }

        if (strpos($lower, 'select count(*) as medium_risk') === 0) {
            $count = count(array_filter($this->submissions, fn($s) => $s['similarity'] > 30 && $s['similarity'] <= 70));
            return new FakeResult([['medium_risk' => $count]]);
        }

        if (strpos($lower, 'select count(*) as low_risk') === 0) {
            $count = count(array_filter($this->submissions, fn($s) => $s['similarity'] <= 30 && $s['similarity'] !== null));
            return new FakeResult([['low_risk' => $count]]);
        }

        return new FakeResult([]);
    }

    public function prepare(string $sql)
    {
        return new FakeStmt($this, $sql);
    }

    private function countByStatus(string $status): int
    {
        return count(array_filter($this->submissions, fn($s) => $s['status'] === $status));
    }
}

class FakeStmt
{
    private $conn;
    private $sql;
    private $params = [];
    private $resultRows = [];

    public function __construct(FakeMysqli $conn, string $sql)
    {
        $this->conn = $conn;
        $this->sql = strtolower(trim($sql));
    }

    public function bind_param($types, &...$params): void
    {
        $this->params = &$params;
    }

    public function execute(): bool
    {
        if (strpos($this->sql, 'select setting_value from system_settings') === 0) {
            $key = $this->params[0] ?? '';
            $value = $this->conn->settings[$key] ?? null;
            $row = $value !== null ? ['setting_value' => $value] : null;
            $this->resultRows = $row ? [$row] : [];
            return true;
        }

        if (strpos($this->sql, 'insert into system_settings') === 0) {
            $key = $this->params[0] ?? '';
            $value = $this->params[1] ?? null;
            if ($key !== '') {
                $this->conn->settings[$key] = $value;
            }
            return true;
        }

        if (strpos($this->sql, 'update submissions set status =') === 0) {
            $status = $this->params[0] ?? '';
            $id = $this->params[1] ?? null;
            foreach ($this->conn->submissions as &$submission) {
                if ($submission['id'] == $id) {
                    $submission['status'] = $status;
                }
            }
            return true;
        }

        if (strpos($this->sql, 'select * from submissions where id =') === 0) {
            $id = $this->params[0] ?? null;
            $match = array_values(array_filter($this->conn->submissions, fn($s) => $s['id'] == $id));
            $this->resultRows = $match ? [$match[0]] : [];
            return true;
        }

        return true;
    }

    public function get_result()
    {
        return new FakeResult($this->resultRows);
    }

    public function close(): void
    {
        // no-op
    }
}

class FakeResult
{
    private $rows;
    private $pointer = 0;

    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
    }

    public function fetch_assoc()
    {
        if ($this->pointer >= count($this->rows)) {
            return null;
        }
        return $this->rows[$this->pointer++];
    }

    public function fetch_all($mode = MYSQLI_ASSOC)
    {
        return $this->rows;
    }
}

