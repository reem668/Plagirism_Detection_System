<?php

class PlagiarismService
{
    public function check(string $text, array $existing): array
    {
        $words = preg_split('/\s+/', strtolower($text));
        $totalChunks = max(1, count($words) - 4);

        $matchCount = 0;
        $matchingWords = [];

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunk = implode(' ', array_slice($words, $i, 5));

            foreach ($existing as $sub) {
                if (strpos(strtolower($sub['text_content']), $chunk) !== false) {
                    $matchCount++;
                    $matchingWords = array_merge(
                        $matchingWords,
                        array_slice($words, $i, 5)
                    );
                    break;
                }
            }
        }

        $percent = ($matchCount / $totalChunks) * 100;

        return [
            'plagiarised'   => round($percent, 2),
            'exact'         => intval($percent * 0.3),
            'partial'       => intval($percent * 0.7),
            'matchingWords' => array_unique($matchingWords)
        ];
    }
}
