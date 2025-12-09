<?php
/**
 * ApiResponseNormalizer - Standardize API response structures
 *
 * File: /mnt/c/1 Project/augmented_teacher/alt42/patternbank/lib/ApiResponseNormalizer.php
 *
 * Converts Korean/English mixed keys to standard English keys,
 * extracts JSON from mixed content, ensures consistent structure.
 */

class ApiResponseNormalizer {
    /**
     * Key mapping: Korean → English
     */
    private static $keyMap = [
        '문항' => 'question',
        '질문' => 'question',
        'question' => 'question',

        '해설' => 'solution',
        '풀이' => 'solution',
        'solution' => 'solution',

        '정답' => 'answer',
        'answer' => 'answer',

        '선택지' => 'choices',
        '보기' => 'choices',
        'choices' => 'choices',
    ];

    /**
     * Normalize API response structure
     *
     * @param mixed $data Raw API response
     * @param int $depth Recursion depth (prevent infinite loops)
     * @return array Standardized structure
     */
    public static function normalize($data, $depth = 0) {
        // Prevent infinite recursion
        if ($depth > 10) {
            error_log("ApiResponseNormalizer: Maximum recursion depth exceeded");
            return $data;
        }

        if (!is_array($data)) {
            error_log("ApiResponseNormalizer: Input is not array - " . gettype($data));
            return [];
        }

        $normalized = [];

        foreach ($data as $key => $value) {
            // Find mapped key
            $mappedKey = self::$keyMap[$key] ?? $key;

            // Recursively normalize nested associative arrays
            if (is_array($value) && self::isAssociativeArray($value)) {
                $normalized[$mappedKey] = self::normalize($value, $depth + 1);
            } else {
                $normalized[$mappedKey] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Check if array is associative (has string keys or non-sequential numeric keys)
     */
    private static function isAssociativeArray($arr): bool {
        if (empty($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Extract JSON from mixed content (text + JSON)
     *
     * @param string $content Content with JSON
     * @return string Pure JSON string
     */
    public static function extractJson(string $content): string {
        // Try JSON decode first - fastest path
        $trimmed = trim($content);
        if (json_decode($trimmed, true) !== null) {
            return $trimmed;
        }

        // Find first { or [
        $startPos = PHP_INT_MAX;
        $p1 = strpos($content, '{');
        $p2 = strpos($content, '[');

        if ($p1 !== false) $startPos = min($startPos, $p1);
        if ($p2 !== false) $startPos = min($startPos, $p2);

        if ($startPos === PHP_INT_MAX) {
            return $content; // No JSON found
        }

        // Balanced bracket extraction
        $openChar = $content[$startPos];
        $closeChar = $openChar === '{' ? '}' : ']';
        $depth = 0;
        $inString = false;
        $escape = false;

        for ($i = $startPos; $i < strlen($content); $i++) {
            $char = $content[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if (!$inString) {
                if ($char === $openChar) {
                    $depth++;
                } elseif ($char === $closeChar) {
                    $depth--;
                    if ($depth === 0) {
                        return substr($content, $startPos, $i - $startPos + 1);
                    }
                }
            }
        }

        return $content; // Malformed JSON
    }

    /**
     * Ensure data is an array of problems
     *
     * @param mixed $data Problem or array of problems
     * @return array Always array of problems
     */
    public static function ensureArray($data): array {
        if (!is_array($data)) {
            return [];
        }

        // Check if it's a single problem (has 'question' or '문항' key)
        if (isset($data['question']) || isset($data['문항']) || isset($data['solution']) || isset($data['해설'])) {
            return [$data];
        }

        // Already an array of problems
        return $data;
    }

    /**
     * Validate normalized data structure
     *
     * @param array $data Normalized data to validate
     * @return bool True if valid
     * @throws Exception if validation fails
     */
    public static function validate($data): bool {
        if (!is_array($data)) {
            throw new Exception("Normalized data must be array, " . gettype($data) . " given");
        }

        // Check for reasonable size (prevent DoS)
        $jsonSize = strlen(json_encode($data));
        if ($jsonSize > 1048576) { // 1MB limit
            throw new Exception("Normalized data exceeds size limit: " . $jsonSize . " bytes");
        }

        return true;
    }
}
