<?php
/**
 * CurriculumAgent - 📚 체계적 진도형 에이전트
 * MD 파일의 W-X-S-P-E-R-T-A 프레임워크를 실제 구현
 */

require_once 'AgentCore.php';

class CurriculumAgent extends AgentCore {
    
    protected function extractCoreBeliefFromMD() {
        return '진도는 전략, 보정은 일상.';
    }
    
    protected function getStrategicApproach() {
        return [
            'basic_flow' => '단원 마스터리 → 누적 복습(7:3) → 월간 커리 리셋',
            'target_mastery' => 0.8,
            'advance_review_ratio' => '7:3',
            'monthly_reset' => true
        ];
    }
    
    protected function getModeConnections() {
        return [
            'exam_switch' => 'D-30 시험모드로 자동 전환',
            'custom_support' => '기초 결손 시 개인맞춤형 보강',
            'mission_recovery' => '동기저하 시 목표달성형 미션 활성화',
            'thinking_blocks' => '사고력 필요 시 블록 삽입',
            'autonomous_delegation' => '상위권 자율학습 권한 위임'
        ];
    }
    
    protected function getRequiredContext() {
        return [
            'grade_range' => '학년 및 범위',
            'recent_scores' => '최근 단원 스코어',
            'error_patterns' => '오답 패턴 분석',
            'advance_review_ratio' => '선행/복습 비율',
            'exam_calendar' => '시험 캘린더',
            'study_time_log' => '학습시간 로그',
            'knowledge_gaps' => '결손 개념 리스트'
        ];
    }
    
    protected function evaluateSwitchingTriggers($input) {
        $triggers = [];
        $daysToExam = $this->calculateDaysToExam($input);
        $progressGap = $this->calculateProgressGap($input);
        $achievementRate = $this->calculateAchievementRate($input);
        $autonomyRate = $this->calculateAutonomyRate($input);
        
        if ($daysToExam <= 30) {
            $triggers[] = ['type' => 'exam_mode', 'priority' => 'high', 'action' => 'switch_to_exam_centered'];
        }
        
        if ($progressGap > 0.1 || $achievementRate < 0.8) {
            $triggers[] = ['type' => 'support_mode', 'priority' => 'medium', 'action' => 'blend_custom_mission'];
        }
        
        if ($autonomyRate >= 0.7) {
            $triggers[] = ['type' => 'autonomous_mode', 'priority' => 'low', 'action' => 'delegate_planning'];
        }
        
        return $triggers;
    }
    
    protected function calculateContextScore($input) {
        $score = 0;
        $maxScore = 100;
        
        // 필수 컨텍스트 완성도
        $requiredContext = $this->getRequiredContext();
        $availableData = 0;
        foreach ($requiredContext as $key => $description) {
            if (isset($input[$key]) && !empty($input[$key])) {
                $availableData++;
            }
        }
        $score += ($availableData / count($requiredContext)) * 40;
        
        // 데이터 품질
        if ($this->validateDataQuality($input)) {
            $score += 30;
        }
        
        // 최신성
        if ($this->checkDataFreshness($input)) {
            $score += 30;
        }
        
        return min($score, $maxScore);
    }
    
    protected function getStandardVariables() {
        return [
            'weeks' => '주차별 단원 목록',
            'target_mastery' => '80% (A/B/C 등급)',
            'advance_review_ratio' => '7:3',
            'weekly_test' => '주간 테스트 실시',
            'monthly_reset' => '월간 리셋 주기',
            'slip_threshold' => '10% (이탈 임계치)',
            'd_day' => '시험 D-day'
        ];
    }
    
    protected function generateDataModel($input) {
        return [
            'mode' => 'curriculum',
            'weeks' => $this->generateWeeklyPlan($input),
            'thresholds' => [
                'slip' => 0.1,
                'examSwitch' => 30
            ],
            'kpi' => [
                'pace' => 0.9,
                'mastery' => 0.8,
                'errDown' => 0.2,
                'focusHours' => 12
            ]
        ];
    }
    
    protected function executeStep($stepNum, $input) {
        switch ($stepNum) {
            case 1:
                return $this->processStep1($input); // 진단·로드맵
            case 2:
                return $this->processStep2($input); // 주간 스프린트
            case 3:
                return $this->processStep3($input); // 통합리뷰·리셋
            default:
                return 'unknown';
        }
    }
    
    private function processStep1($input) {
        // 진단 및 로드맵 설계
        $diagnosis = $this->conductDiagnosis($input);
        $roadmap = $this->generateRoadmap($diagnosis);
        
        return [
            'status' => 'completed',
            'diagnosis' => $diagnosis,
            'roadmap' => $roadmap,
            'next_step' => '주간 스프린트 실행'
        ];
    }
    
    private function processStep2($input) {
        // 주간 스프린트 (주1~3)
        $sprint = $this->executeWeeklySprint($input);
        
        return [
            'status' => 'in_progress',
            'sprint_progress' => $sprint,
            'kpi_status' => $this->evaluateKPI($input),
            'next_step' => '진행 중 또는 리뷰 준비'
        ];
    }
    
    private function processStep3($input) {
        // 통합 리뷰 및 리셋
        $review = $this->conductIntegratedReview($input);
        $reset = $this->performMonthlyReset($review);
        
        return [
            'status' => 'completed',
            'review_results' => $review,
            'reset_plan' => $reset,
            'next_step' => '새로운 사이클 시작'
        ];
    }
    
    protected function getTeacherChecklist() {
        return [
            '주차별 진도표 업데이트',
            '단원별 마스터리 채점',
            '이탈 학생 알림 및 보정안 전달',
            '시험모드 스위치 점검'
        ];
    }
    
    protected function getStudentRoutine() {
        return [
            'daily_study' => '매일 정시 학습',
            'weekly_check' => '주간 진도 체크',
            'ratio_maintain' => '7:3 비율 유지',
            'monthly_review' => '월간 종합 점검',
            'pace_adjustment' => '번아웃시 페이스 다운'
        ];
    }
    
    protected function getAutomationMapping() {
        return [
            'mathking' => [
                'pace_analysis' => '진도율 분석 및 로드맵 자동생성',
                'problem_frequency' => '출제빈도/예상문제 트래킹'
            ],
            'management' => [
                'dropout_detection' => '이탈 탐지 및 리마인더',
                'thinking_record' => '사고과정 기록'
            ]
        ];
    }
    
    protected function getReflectionQuestions() {
        return [
            '가장 많은 시간을 잡아먹은 개념/유형은? 왜?',
            '"계획→실행" 갭 %와 원인 1가지',
            '가장 효과적이었던 복습 방식 1개',
            '전이된 문제/전이 실패 문제 각 1개',
            '다음 주 제거할 활동 1개·증가할 활동 1개',
            '시험모드 스위치 필요성(Y/N)·근거'
        ];
    }
    
    protected function getImprovementRules() {
        return [
            'gap_high' => '갭 >20% → 범위 축소, 미션 난이도 재조정',
            'transfer_fail' => '전이 실패 지속 → 도제 블록 2배 증량',
            'burnout' => '번아웃 신호 → 페이스 80%로 1주 감속'
        ];
    }
    
    protected function processReflection($input) {
        $questions = $this->getReflectionQuestions();
        $responses = $input['reflection_responses'] ?? [];
        $analysis = [];
        
        foreach ($questions as $index => $question) {
            if (isset($responses[$index])) {
                $analysis[$index] = [
                    'question' => $question,
                    'response' => $responses[$index],
                    'improvement_action' => $this->generateImprovementAction($question, $responses[$index])
                ];
            }
        }
        
        return $analysis;
    }
    
    protected function extractBlendingRules() {
        return [
            '기초 결손' => '📚 + 🎯(기초 2h/일) + ⚡(5미션/일)',
            '시험 임박(D‑30)' => '📚 ↔ ✏️(기출3회독·백지복습·컨디션 루틴)',
            '사고력 필요' => '📚 + 🔍(모델링/코칭/스캐폴딩) + 🧠(주간 메타인지)',
            '자율 상위권' => '📚 + 🚀(계획권한 70%, 월 2회 멘토링)'
        ];
    }
    
    protected function matchesSituation($situation, $condition) {
        $situationMap = [
            'basic_gap' => '기초 결손',
            'exam_approaching' => '시험 임박(D‑30)',
            'thinking_needed' => '사고력 필요',
            'high_autonomy' => '자율 상위권'
        ];
        
        return isset($situationMap[$situation]) && $situationMap[$situation] === $condition;
    }
    
    protected function calculateCurrentKPI($metric, $input) {
        switch ($metric) {
            case '주간 진도달성':
                return $this->calculateProgressAchievement($input);
            case '단원 마스터리':
                return $this->calculateMastery($input);
            case '오답감소율':
                return $this->calculateErrorReduction($input);
            case '집중 학습시간':
                return $this->calculateFocusHours($input);
            default:
                return 0;
        }
    }
    
    protected function compareKPI($current, $target) {
        $targetValue = floatval(str_replace(['≥', '%'], ['', ''], $target));
        $currentValue = floatval(str_replace('%', '', $current));
        
        if ($currentValue >= $targetValue) {
            return 'achieved';
        } elseif ($currentValue >= $targetValue * 0.8) {
            return 'close';
        } else {
            return 'needs_improvement';
        }
    }
    
    // 헬퍼 메서드들
    private function calculateDaysToExam($input) {
        if (isset($input['exam_date'])) {
            $examDate = strtotime($input['exam_date']);
            $today = time();
            return max(0, ceil(($examDate - $today) / (24 * 60 * 60)));
        }
        return 999; // 시험일 미정
    }
    
    private function calculateProgressGap($input) {
        $planned = $input['planned_progress'] ?? 100;
        $actual = $input['actual_progress'] ?? 0;
        return abs($planned - $actual) / $planned;
    }
    
    private function calculateAchievementRate($input) {
        $targets = $input['targets'] ?? [];
        $achievements = $input['achievements'] ?? [];
        
        if (empty($targets)) return 1.0;
        
        $totalRate = 0;
        foreach ($targets as $key => $target) {
            $achieved = $achievements[$key] ?? 0;
            $totalRate += min($achieved / $target, 1.0);
        }
        
        return $totalRate / count($targets);
    }
    
    private function calculateAutonomyRate($input) {
        return $input['autonomy_rate'] ?? 0.5;
    }
    
    private function validateDataQuality($input) {
        // 데이터 품질 검증 로직
        return !empty($input) && is_array($input);
    }
    
    private function checkDataFreshness($input) {
        $lastUpdate = $input['last_update'] ?? 0;
        $weekAgo = time() - (7 * 24 * 60 * 60);
        return $lastUpdate > $weekAgo;
    }
    
    private function generateWeeklyPlan($input) {
        // 주간 계획 생성 로직
        return [
            ['week' => 1, 'units' => ['함수개념', '평면벡터'], 'targetMastery' => 0.8],
            ['week' => 2, 'units' => ['미분기초', '벡터연산'], 'targetMastery' => 0.85]
        ];
    }
    
    private function conductDiagnosis($input) {
        return [
            'current_level' => $input['current_score'] ?? 70,
            'weak_areas' => $input['weak_areas'] ?? ['확률', '수열'],
            'study_pattern' => $input['study_pattern'] ?? '시각형'
        ];
    }
    
    private function generateRoadmap($diagnosis) {
        return [
            'phase1' => '기초 강화 (2주)',
            'phase2' => '약점 보완 (4주)',
            'phase3' => '통합 완성 (2주)'
        ];
    }
    
    private function executeWeeklySprint($input) {
        return [
            'current_week' => $input['current_week'] ?? 1,
            'progress' => $input['weekly_progress'] ?? 75,
            'tasks_completed' => $input['tasks_completed'] ?? 8,
            'tasks_total' => $input['tasks_total'] ?? 10
        ];
    }
    
    private function conductIntegratedReview($input) {
        return [
            'mastery_scores' => $input['mastery_scores'] ?? [],
            'time_efficiency' => $input['time_efficiency'] ?? 0.8,
            'improvement_areas' => ['시간 관리', '오답 분석']
        ];
    }
    
    private function performMonthlyReset($review) {
        return [
            'new_targets' => $this->adjustTargetsBasedOnReview($review),
            'schedule_optimization' => true,
            'resource_reallocation' => $this->reallocateResources($review)
        ];
    }
    
    private function generateImprovementAction($question, $response) {
        // 질문-응답 기반 개선 액션 생성
        return '개선 액션 분석 필요';
    }
    
    private function calculateProgressAchievement($input) {
        return $input['progress_achievement'] ?? 85;
    }
    
    private function calculateMastery($input) {
        return $input['mastery_score'] ?? 78;
    }
    
    private function calculateErrorReduction($input) {
        return $input['error_reduction'] ?? 22;
    }
    
    private function calculateFocusHours($input) {
        return $input['focus_hours'] ?? 14;
    }
    
    private function adjustTargetsBasedOnReview($review) {
        return ['새로운 목표 설정'];
    }
    
    private function reallocateResources($review) {
        return ['자원 재배치 계획'];
    }
}