<?php
namespace Tests\Support;

class TestHarness
{
    private $suiteName;
    private $passes = 0;
    private $fails = 0;
    private $results = [];

    public function __construct(string $suiteName)
    {
        $this->suiteName = $suiteName;
    }

    public function run(string $name, callable $callback): void
    {
        try {
            $callback($this);
            $this->passes++;
            $this->results[] = "[PASS] {$name}";
        } catch (\Throwable $e) {
            $this->fails++;
            $this->results[] = "[FAIL] {$name} :: " . $e->getMessage();
        }
    }

    public function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true) . " got " . var_export($actual, true);
            throw new \RuntimeException($msg);
        }
    }

    public function assertTrue($condition, string $message = ''): void
    {
        if (!$condition) {
            throw new \RuntimeException($message ?: 'Condition is not true');
        }
    }

    public function report(): void
    {
        echo "=== {$this->suiteName} ===" . PHP_EOL;
        foreach ($this->results as $result) {
            echo $result . PHP_EOL;
        }
        echo "Passed: {$this->passes}, Failed: {$this->fails}" . PHP_EOL;
        if ($this->fails > 0) {
            exit(1);
        }
    }
}

