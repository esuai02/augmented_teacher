<?php
/**
 * FormulaEncoder - Safely encode/decode mathematical formulas in JSON
 *
 * File: /mnt/c/1 Project/augmented_teacher/alt42/patternbank/lib/FormulaEncoder.php
 *
 * Converts LaTeX/MathML formulas to safe markers before JSON encoding,
 * preventing parse errors from special characters.
 */

class FormulaEncoder {
    /**
     * Regex patterns for detecting formulas
     */
    private static $patterns = [
        '/\$\$(?:[^$]|\$(?!\$))+\$\$/s',  // Display math (allows single $ inside)
        '/\$(?:[^$]|\\\$)+\$/s',          // Inline math (allows escaped \$)
        '/\\\\(?:[a-zA-Z]+(?:\{[^}]*\})*(?:_\{[^}]*\})?(?:\^\{[^}]*\})?)/s',  // LaTeX commands
        '/<math(?:\s[^>]*)?(?:\/>|>.*?<\/math>)/is',  // MathML with self-closing support
    ];

    /**
     * Encode formulas in data structure to safe markers
     *
     * @param mixed $data Data containing formulas
     * @return mixed Data with encoded formulas
     */
    public static function encode($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::encode($value);
            }
            return $data;
        }

        if (is_string($data)) {
            return self::encodeString($data);
        }

        return $data;
    }

    /**
     * Encode formulas in a single string
     *
     * @param string $str String containing formulas
     * @return string String with encoded formulas
     */
    private static function encodeString($str) {
        foreach (self::$patterns as $pattern) {
            $str = preg_replace_callback($pattern, function($matches) {
                $formula = $matches[0];
                $encoded = base64_encode($formula);
                return '{{FORMULA:' . $encoded . '}}';
            }, $str);
        }
        return $str;
    }

    /**
     * Decode formulas from markers back to original
     *
     * @param mixed $data Data with encoded formulas
     * @return mixed Data with restored formulas
     */
    public static function decode($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::decode($value);
            }
            return $data;
        }

        if (is_string($data)) {
            return self::decodeString($data);
        }

        return $data;
    }

    /**
     * Decode formulas in a single string
     *
     * @param string $str String with encoded formulas
     * @return string String with restored formulas
     */
    private static function decodeString($str) {
        return preg_replace_callback('/\{\{FORMULA:([A-Za-z0-9+\/=]+)\}\}/', function($matches) {
            $decoded = base64_decode($matches[1], true);  // Strict mode
            if ($decoded === false) {
                error_log("[FormulaEncoder Error] Invalid base64 in marker: " . $matches[1]);
                return $matches[0];  // Leave marker as-is on error
            }
            return $decoded;
        }, $str);
    }

    /**
     * Strip all formulas from data (fallback for errors)
     *
     * @param mixed $data Data containing formulas
     * @return mixed Data without formulas
     */
    public static function stripFormulas($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::stripFormulas($value);
            }
            return $data;
        }

        if (is_string($data)) {
            foreach (self::$patterns as $pattern) {
                $data = preg_replace($pattern, '[수식]', $data);
            }
            // Also strip encoded markers
            $data = preg_replace('/\{\{FORMULA:[^}]+\}\}/', '[수식]', $data);
        }

        return $data;
    }
}
