<?php
/**
 * JsonSafeHelper - Integration layer for safe JSON handling
 *
 * Provides 3-layer protection:
 * 1. Normalize keys (Korean → English)
 * 2. Encode formulas (LaTeX/MathML → {{FORMULA:base64}})
 * 3. Validate JSON structure
 *
 * Usage:
 *   $json = JsonSafeHelper::safeEncode($data);
 *   $data = JsonSafeHelper::safeDecode($json);
 *
 * @package PatternBank
 * @version 1.0.0
 */

require_once __DIR__ . '/FormulaEncoder.php';
require_once __DIR__ . '/ApiResponseNormalizer.php';

class JsonSafeHelper {

    /**
     * Safely encode data to JSON with 3-layer protection
     *
     * Layer 1: Normalize Korean keys to English
     * Layer 2: Encode formulas to safe markers
     * Layer 3: Validate and encode to JSON
     *
     * @param mixed $data Data to encode
     * @return string JSON string
     * @throws Exception If encoding fails
     */
    public static function safeEncode($data): string {
        try {
            // Validate input (P0 Fix #2)
            if ($data === null) {
                error_log("[JsonSafeHelper::safeEncode at " . __FILE__ . ":" . __LINE__ . "] Warning: null input, returning empty JSON object");
                return '{}';
            }

            // Layer 1: Normalize keys
            if (is_array($data)) {
                $normalized = ApiResponseNormalizer::normalize($data);
                // Validate after normalization (P0 Fix #1)
                ApiResponseNormalizer::validate($normalized);
            } else {
                $normalized = $data;
            }

            // Layer 2: Encode formulas
            $encoded = FormulaEncoder::encode($normalized);

            // Layer 3: Validate and encode to JSON
            $json = json_encode($encoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                throw new Exception("[JsonSafeHelper::safeEncode at " . __FILE__ . ":" . __LINE__ . "] JSON encoding failed: " . json_last_error_msg());
            }

            // Final validation (defense in depth)
            if (!self::isValid($json)) {
                throw new Exception("[JsonSafeHelper::safeEncode at " . __FILE__ . ":" . __LINE__ . "] Post-encoding validation failed");
            }

            return $json;

        } catch (Exception $e) {
            // Include file and line information (P0 Fix #4)
            error_log("[JsonSafeHelper::safeEncode Error at " . __FILE__ . ":" . __LINE__ . "] " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Safely decode JSON and restore formulas
     *
     * @param string $json JSON string
     * @return array Decoded data with restored formulas
     * @throws Exception If decoding fails
     */
    public static function safeDecode(string $json): array {
        try {
            // Decode JSON
            $data = json_decode($json, true);

            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("[JsonSafeHelper::safeDecode at " . __FILE__ . ":" . __LINE__ . "] JSON decoding failed: " . json_last_error_msg());
            }

            // Enforce array return type (P0 Fix #4)
            if (!is_array($data)) {
                error_log("[JsonSafeHelper::safeDecode at " . __FILE__ . ":" . __LINE__ . "] Warning: Decoded JSON is not an array: " . gettype($data));
                return []; // Return empty array for non-array types
            }

            // Restore formulas
            $restored = FormulaEncoder::decode($data);

            return $restored;

        } catch (Exception $e) {
            // Include file and line information (P0 Fix #4)
            error_log("[JsonSafeHelper::safeDecode Error at " . __FILE__ . ":" . __LINE__ . "] " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate JSON structure
     *
     * @param string $json JSON string
     * @return bool True if valid
     */
    public static function isValid(string $json): bool {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
