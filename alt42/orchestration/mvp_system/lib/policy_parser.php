<?php
// 파일: mvp_system/lib/policy_parser.php (Line 1)
// Mathking Agentic MVP System - Policy Parser for agents/*.md files

require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/logger.php');

/**
 * PolicyParser Class
 * agents/ 폴더의 .md 파일에서 정책 및 템플릿 추출
 */
class PolicyParser {
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new MVPLogger('policy_parser');
    }

    /**
     * Parse markdown policy file
     * @param string $file_path Path to .md file
     * @return array Parsed policy data
     * @throws Exception if file not found
     */
    public function parse_markdown($file_path) {
        if (!file_exists($file_path)) {
            $error_msg = "Policy file not found: $file_path at " . __FILE__ . ":" . __LINE__;
            $this->logger->error($error_msg);
            throw new Exception($error_msg);
        }

        $this->logger->info("Parsing policy file", ['file' => $file_path]);

        $content = file_get_contents($file_path);
        $policy = [
            'file_path' => $file_path,
            'thresholds' => [],
            'patterns' => [],
            'templates' => [],
            'raw_content' => $content
        ];

        // Parse thresholds (e.g., "- 95+: 매우 침착")
        preg_match_all('/^[\s]*-\s*(\d+)([\+~\-])?(\d*)?:\s*(.+)$/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $threshold_value = intval($match[1]);
            $range_indicator = $match[2] ?? '';
            $range_end = !empty($match[3]) ? intval($match[3]) : null;
            $description = trim($match[4]);

            $threshold = [
                'value' => $threshold_value,
                'range_indicator' => $range_indicator,
                'range_end' => $range_end,
                'description' => $description
            ];

            $policy['thresholds'][] = $threshold;
        }

        // Parse pattern heuristics (e.g., "- 기준선 대비 +5 이상: 고효율 상태")
        preg_match_all('/^[\s]*-\s*기준선\s+대비\s+([+\-]\d+)\s+([이상하]*):\s*(.+)$/m', $content, $pattern_matches, PREG_SET_ORDER);

        foreach ($pattern_matches as $match) {
            $policy['patterns'][] = [
                'deviation' => $match[1],
                'condition' => $match[2],
                'action' => trim($match[3])
            ];
        }

        // Parse coaching templates (quoted strings)
        preg_match_all('/"([^"]+)"/u', $content, $template_matches);
        $policy['templates'] = array_unique($template_matches[1]);

        $this->logger->info("Policy parsed", [
            'thresholds_count' => count($policy['thresholds']),
            'patterns_count' => count($policy['patterns']),
            'templates_count' => count($policy['templates'])
        ]);

        return $policy;
    }

    /**
     * Parse agent08 calmness policy specifically
     * @return array Calm policy with structured thresholds
     */
    public function parse_calm_policy() {
        $file_path = mvp_config('AGENT08_POLICY');
        $policy = $this->parse_markdown($file_path);

        // Structure calm thresholds for easy lookup
        $structured_thresholds = [];
        foreach ($policy['thresholds'] as $threshold) {
            $key = $threshold['value'];
            $structured_thresholds[$key] = [
                'min' => $threshold['value'],
                'max' => $threshold['range_end'] ?? null,
                'description' => $threshold['description'],
                'indicator' => $threshold['range_indicator']
            ];
        }

        $policy['structured_thresholds'] = $structured_thresholds;
        return $policy;
    }

    /**
     * Parse agent20 intervention preparation template
     * @return array Intervention templates
     */
    public function parse_intervention_template() {
        $file_path = mvp_config('AGENT20_TEMPLATE');
        $policy = $this->parse_markdown($file_path);

        return [
            'templates' => $policy['templates'],
            'raw_content' => $policy['raw_content']
        ];
    }

    /**
     * Get recommendation for calm score
     * @param float $calm_score Calm score (0-100)
     * @return string Recommendation text
     */
    public function get_calm_recommendation($calm_score) {
        $policy = $this->parse_calm_policy();

        // Find matching threshold
        foreach ($policy['thresholds'] as $threshold) {
            $min = $threshold['value'];
            $max = $threshold['range_end'];

            if ($threshold['range_indicator'] === '+') {
                // 95+ format
                if ($calm_score >= $min) {
                    return $threshold['description'];
                }
            } elseif ($max !== null) {
                // 90~94 format
                if ($calm_score >= $min && $calm_score <= $max) {
                    return $threshold['description'];
                }
            } elseif ($threshold['range_indicator'] === '<') {
                // <75 format
                if ($calm_score < $min) {
                    return $threshold['description'];
                }
            }
        }

        // Default fallback
        return "표준 학습 진행 권장";
    }

    /**
     * Extract action from recommendation text
     * @param string $recommendation Recommendation text
     * @return string Action keyword (micro_break, concept_review, etc.)
     */
    public function extract_action($recommendation) {
        $action_keywords = [
            'micro_break' => ['휴식', '쉼', '호흡', '스트레칭', '복구'],
            'concept_review' => ['복습', '재학습', '개념'],
            'adaptive_difficulty' => ['심화', '난이도', '고난도', '쉬운'],
            'none' => ['표준', '진행', '계속']
        ];

        foreach ($action_keywords as $action => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($recommendation, $keyword) !== false) {
                    return $action;
                }
            }
        }

        return 'none';
    }

    /**
     * Validate policy file structure
     * @param string $file_path Path to policy file
     * @return array Validation result
     */
    public function validate_policy($file_path) {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        try {
            $policy = $this->parse_markdown($file_path);

            if (empty($policy['thresholds']) && empty($policy['templates'])) {
                $validation['warnings'][] = "No thresholds or templates found";
            }

            if (count($policy['thresholds']) < 3) {
                $validation['warnings'][] = "Less than 3 thresholds defined (recommended: 5+)";
            }

        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = $e->getMessage();
        }

        return $validation;
    }
}
?>
