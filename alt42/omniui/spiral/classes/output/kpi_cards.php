<?php declare(strict_types=1);
/**
 * KPI Cards Renderable Component
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable component for KPI dashboard cards
 */
final class kpi_cards implements \renderable, \templatable {
    
    private array $kpiData;
    private array $trends;
    
    /**
     * Constructor
     * 
     * @param array $kpiData Current KPI data
     * @param array $trends Optional trend data for comparison
     */
    public function __construct(array $kpiData, array $trends = []) {
        $this->kpiData = $kpiData;
        $this->trends = $trends;
    }
    
    /**
     * Export data for template
     * 
     * @param \renderer_base $output
     * @return array Template data
     */
    public function export_for_template(\renderer_base $output): array {
        $cards = [];
        
        // 7:3 비율 준수율 카드
        $cards[] = [
            'title' => get_string('kpi_ratio', 'local_spiral'),
            'value' => $this->kpiData['ratio'] ?? 0,
            'unit' => '%',
            'icon' => 'fa-balance-scale',
            'color' => $this->getColorClass($this->kpiData['ratio'] ?? 0, 65, 75),
            'trend' => $this->getTrendDirection('ratio'),
            'description' => get_string('kpi_ratio_desc', 'local_spiral')
        ];
        
        // 충돌 발생률 카드
        $cards[] = [
            'title' => get_string('kpi_conflict', 'local_spiral'),
            'value' => $this->kpiData['conflict'] ?? 0,
            'unit' => '%',
            'icon' => 'fa-exclamation-triangle',
            'color' => $this->getColorClass($this->kpiData['conflict'] ?? 0, 5, 2, true), // 낮을수록 좋음
            'trend' => $this->getTrendDirection('conflict', true), // 낮아지는 것이 좋음
            'description' => get_string('kpi_conflict_desc', 'local_spiral'),
            'alert' => ($this->kpiData['conflict'] ?? 0) > 5 ? 'warning' : ''
        ];
        
        // 완료율 카드
        $cards[] = [
            'title' => get_string('kpi_completion', 'local_spiral'),
            'value' => $this->kpiData['completion'] ?? 0,
            'unit' => '%',
            'icon' => 'fa-check-circle',
            'color' => $this->getColorClass($this->kpiData['completion'] ?? 0, 80, 90),
            'trend' => $this->getTrendDirection('completion'),
            'description' => get_string('kpi_completion_desc', 'local_spiral')
        ];
        
        // 교사 수정 횟수 카드
        $cards[] = [
            'title' => get_string('kpi_modcnt', 'local_spiral'),
            'value' => $this->kpiData['modcnt'] ?? 0,
            'unit' => '건',
            'icon' => 'fa-edit',
            'color' => 'info',
            'trend' => $this->getTrendDirection('modcnt'),
            'description' => get_string('kpi_modcnt_desc', 'local_spiral')
        ];
        
        // 사용자 만족도 카드
        $cards[] = [
            'title' => get_string('kpi_satisfaction', 'local_spiral'),
            'value' => $this->kpiData['satisfaction'] ?? 0,
            'unit' => '점',
            'icon' => 'fa-smile',
            'color' => $this->getColorClass($this->kpiData['satisfaction'] ?? 0, 70, 85),
            'trend' => $this->getTrendDirection('satisfaction'),
            'description' => get_string('kpi_satisfaction_desc', 'local_spiral')
        ];
        
        // 시스템 활용률 카드
        $cards[] = [
            'title' => get_string('kpi_utilization', 'local_spiral'),
            'value' => $this->kpiData['utilization'] ?? 0,
            'unit' => '%',
            'icon' => 'fa-users',
            'color' => $this->getColorClass($this->kpiData['utilization'] ?? 0, 50, 70),
            'trend' => $this->getTrendDirection('utilization'),
            'description' => get_string('kpi_utilization_desc', 'local_spiral')
        ];
        
        return [
            'cards' => $cards,
            'last_updated' => userdate($this->kpiData['timestamp'] ?? time()),
            'has_alerts' => $this->hasAlerts(),
            'summary' => $this->generateSummary()
        ];
    }
    
    /**
     * Get color class based on value and thresholds
     */
    private function getColorClass(float $value, float $warningThreshold, float $successThreshold, bool $inverse = false): string {
        if ($inverse) {
            // 낮을수록 좋은 지표 (충돌률 등)
            if ($value <= $successThreshold) {
                return 'success';
            } elseif ($value <= $warningThreshold) {
                return 'warning';
            } else {
                return 'danger';
            }
        } else {
            // 높을수록 좋은 지표
            if ($value >= $successThreshold) {
                return 'success';
            } elseif ($value >= $warningThreshold) {
                return 'warning';
            } else {
                return 'danger';
            }
        }
    }
    
    /**
     * Get trend direction compared to previous period
     */
    private function getTrendDirection(string $metric, bool $inverse = false): string {
        if (empty($this->trends) || count($this->trends) < 2) {
            return 'stable';
        }
        
        $current = $this->kpiData[$metric] ?? 0;
        $previous = 0;
        
        // Find corresponding metric in trends
        $metricMap = [
            'ratio' => 'ratio_achievement',
            'conflict' => 'conflict_rate',
            'completion' => 'completion_rate',
            'modcnt' => 'modification_count',
            'satisfaction' => 'satisfaction_score',
            'utilization' => 'utilization_rate'
        ];
        
        $trendKey = $metricMap[$metric] ?? $metric;
        
        if (isset($this->trends[1]->$trendKey)) {
            $previous = $this->trends[1]->$trendKey;
        }
        
        $diff = $current - $previous;
        $threshold = 1.0; // 1% 이상 변화
        
        if (abs($diff) < $threshold) {
            return 'stable';
        }
        
        if ($inverse) {
            return $diff > 0 ? 'down' : 'up'; // 증가는 나쁨, 감소는 좋음
        } else {
            return $diff > 0 ? 'up' : 'down'; // 증가는 좋음, 감소는 나쁨
        }
    }
    
    /**
     * Check if there are any alerts
     */
    private function hasAlerts(): bool {
        $conflictRate = $this->kpiData['conflict'] ?? 0;
        $ratioAchievement = $this->kpiData['ratio'] ?? 0;
        $completionRate = $this->kpiData['completion'] ?? 0;
        
        return $conflictRate > 5 || 
               $ratioAchievement < 65 || 
               $ratioAchievement > 75 || 
               $completionRate < 80;
    }
    
    /**
     * Generate summary text
     */
    private function generateSummary(): string {
        $ratio = $this->kpiData['ratio'] ?? 0;
        $conflict = $this->kpiData['conflict'] ?? 0;
        $completion = $this->kpiData['completion'] ?? 0;
        
        if ($ratio >= 65 && $ratio <= 75 && $conflict <= 5 && $completion >= 80) {
            return get_string('kpi_summary_excellent', 'local_spiral');
        } elseif ($ratio >= 60 && $conflict <= 10 && $completion >= 70) {
            return get_string('kpi_summary_good', 'local_spiral');
        } else {
            return get_string('kpi_summary_needs_attention', 'local_spiral');
        }
    }
}