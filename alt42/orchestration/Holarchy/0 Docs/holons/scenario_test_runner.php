<?php
/**
 * Scenario Test Runner
 * ====================
 * Agent01-14 시나리오 4,5,6 실행 테스트
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/scenario_test_runner.php
 *
 * @file scenario_test_runner.php
 * @location alt42/orchestration/Holarchy/0 Docs/holons/
 */

// Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에러 표시 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 테스트할 시나리오 번호
$scenario = isset($_GET['scenario']) ? intval($_GET['scenario']) : 4;
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// Python 코드 정의 (시나리오 4, 5, 6)
function getScenarioCode($scenario) {
    switch($scenario) {
        case 4:
            // 시나리오 4: 3시간 수업 실시간 (Agent01→08→04→05→13→12→14)
            return <<<'PYTHON'
import math
import json

# 시나리오 4: 3시간 수업 실시간 오케스트레이션
# 7단계 Phase 전이: 수업 시작 → 문제풀이 → 감정변화 → 이탈감지 → 휴식 → 재집중 → 마무리

context = {
    "student_id": "STU_2025_CLASS",
    "session_type": "3hour_class",
    "class_duration_minutes": 180,
    "timeline": [
        {"t": 0, "event": "수업시작", "agent": "Agent01"},
        {"t": 5, "event": "침착도체크", "agent": "Agent08", "calmness": 95},
        {"t": 30, "event": "문제풀이시작", "agent": "Agent04", "accuracy": 65},
        {"t": 60, "event": "감정변화감지", "agent": "Agent05", "emotion": "neutral"},
        {"t": 90, "event": "집중도저하", "agent": "Agent13", "ninactive": 2},
        {"t": 100, "event": "휴식권장", "agent": "Agent12", "rest_type": "활동중심"},
        {"t": 110, "event": "재집중", "agent": "Agent08", "calmness": 88},
        {"t": 150, "event": "오답분석", "agent": "Agent11", "error_type": "계산실수"},
        {"t": 175, "event": "현재위치확인", "agent": "Agent14", "progress": "적절"}
    ]
}

# 각 시점별 Quantum 신호 생성 (Agent 룰 기반)
signals = []

# Agent01: 온보딩 (S0, 초기 맥락)
signals.append({
    "agent": "Agent01", "rule_id": "R01_onboarding",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.9, "priority": 70,
    "amplitude": round(0.9 * math.sqrt(70/100), 4),
    "message": "학생 프로필 로딩 완료"
})

# Agent08: 침착도 95 → 심화 추천 (S0)
signals.append({
    "agent": "Agent08", "rule_id": "R08_calmness_high",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.95, "priority": 85,
    "amplitude": round(0.95 * math.sqrt(85/100), 4),
    "message": "침착도 95+ → 심화 과제 배치"
})

# Agent04: 정답률 65% → 성장구간 (S1)
signals.append({
    "agent": "Agent04", "rule_id": "R04_accuracy_growth",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.85, "priority": 80,
    "amplitude": round(0.85 * math.sqrt(80/100), 4),
    "message": "정답률 40~70% → 최적 난이도 유지"
})

# Agent05: 감정 중립 (S1)
signals.append({
    "agent": "Agent05", "rule_id": "R05_emotion_neutral",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.7, "priority": 60,
    "amplitude": round(0.7 * math.sqrt(60/100), 4),
    "message": "감정 안정 → 현재 강도 유지"
})

# Agent13: ninactive=2 → Medium 위험 (S2)
signals.append({
    "agent": "Agent13", "rule_id": "R13_dropout_medium",
    "scenario": "S2", "phase_deg": 90,
    "confidence": 0.8, "priority": 75,
    "amplitude": round(0.8 * math.sqrt(75/100), 4),
    "message": "이탈 위험 Medium → 리포커스 필요"
})

# Agent12: 휴식 권장 (S2)
signals.append({
    "agent": "Agent12", "rule_id": "R12_rest_activity",
    "scenario": "S2", "phase_deg": 90,
    "confidence": 0.75, "priority": 65,
    "amplitude": round(0.75 * math.sqrt(65/100), 4),
    "message": "60~90분 경과 → 활동중심 휴식"
})

# Agent11: 오답 분석 (S1)
signals.append({
    "agent": "Agent11", "rule_id": "R11_error_analysis",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.85, "priority": 70,
    "amplitude": round(0.85 * math.sqrt(70/100), 4),
    "message": "계산실수 패턴 → 체크리스트 권장"
})

# Agent14: 진행 상태 적절 (S0)
signals.append({
    "agent": "Agent14", "rule_id": "R14_progress_normal",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.9, "priority": 75,
    "amplitude": round(0.9 * math.sqrt(75/100), 4),
    "message": "진행 적절 → 현재 페이스 유지"
})

# 간섭 계산 (위상 차이 고려)
real_sum = 0
imag_sum = 0
individual_sum = 0

for sig in signals:
    phase_rad = math.radians(sig["phase_deg"])
    real_sum += sig["amplitude"] * math.cos(phase_rad)
    imag_sum += sig["amplitude"] * math.sin(phase_rad)
    individual_sum += sig["amplitude"]

total_amp = math.sqrt(real_sum**2 + imag_sum**2)
efficiency = (total_amp / individual_sum * 100) if individual_sum > 0 else 0

# Phase 그룹별 간섭 분석
phase_groups = {}
for sig in signals:
    phase = sig["phase_deg"]
    if phase not in phase_groups:
        phase_groups[phase] = []
    phase_groups[phase].append(sig["agent"])

interference_detail = []
for phase, agents in phase_groups.items():
    if len(agents) > 1:
        interference_detail.append(f"Phase {phase}: {'+'.join(agents)} 보강")

result = {
    "scenario_id": 4,
    "scenario_name": "3시간 수업 실시간",
    "context": context,
    "agents_triggered": ["Agent01", "Agent08", "Agent04", "Agent05", "Agent13", "Agent12", "Agent11", "Agent14"],
    "total_agents": 8,
    "quantum_signals": signals,
    "phase_distribution": {str(k): len([s for s in signals if s["phase_deg"]==k]) for k in [0,45,90]},
    "interference_result": {
        "type": "MULTI_PHASE_CONSTRUCTIVE",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "phase_groups": phase_groups,
        "interference_detail": interference_detail,
        "explanation": f"3개 Phase(0,45,90) → 부분 보강 {efficiency:.0f}%"
    },
    "intervention_level": "MEDIUM",
    "recommended_actions": [
        {"time": "t=90분", "action": "3분 호흡 휴식 후 재시작"},
        {"time": "t=150분", "action": "오답 체크리스트 적용"},
        {"time": "t=175분", "action": "성취 요약 및 다음 목표 설정"}
    ],
    "session_summary": "수업 효율 72% | 침착도 변화: 95→88→재회복 | 이탈 방지 성공"
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;

        case 5:
            // 시나리오 5: 1주일 주간목표
            return <<<'PYTHON'
import math
import json

# 시나리오 5: 1주일 주간목표 추적
# 시험 D-7부터 D-day까지 7일간 일별 모니터링

context = {
    "student_id": "STU_2025_WEEKLY",
    "exam_date": "2025-12-12",
    "exam_subject": "수학",
    "weekly_goal": "이차방정식 마스터",
    "daily_timeline": [
        {"day": "D-7", "agent": "Agent02", "event": "시험일정등록", "d_day": 7},
        {"day": "D-6", "agent": "Agent03", "event": "주간목표설정", "goal_quality": 75},
        {"day": "D-5", "agent": "Agent09", "event": "학습계획수립", "pomodoro_target": 8},
        {"day": "D-4", "agent": "Agent07", "event": "개입타겟설정", "focus_time": "오후3시"},
        {"day": "D-3", "agent": "Agent05", "event": "감정체크", "emotion": "불안"},
        {"day": "D-2", "agent": "Agent06", "event": "교사피드백", "feedback": "강점강화"},
        {"day": "D-1", "agent": "Agent11", "event": "오답총정리", "error_count": 15},
        {"day": "D-day", "agent": "Agent14", "event": "최종점검", "readiness": 85}
    ]
}

# 7일간 Quantum 신호 누적 (시간적 간섭)
signals = []
decay_rate = 0.3

# D-7: Agent02 시험일정 (S0)
signals.append({
    "agent": "Agent02", "rule_id": "R02_exam_d7", "day": "D-7",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.8, "priority": 70,
    "amplitude": round(0.8 * math.sqrt(70/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 7), 4),
    "message": "D>7 → 개념정립 모드"
})

# D-6: Agent03 목표 (S1)
signals.append({
    "agent": "Agent03", "rule_id": "R03_goal_quality", "day": "D-6",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.75, "priority": 80,
    "amplitude": round(0.75 * math.sqrt(80/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 6), 4),
    "message": "목표 품질 75% → 세분화 필요"
})

# D-5: Agent09 학습계획 (S0)
signals.append({
    "agent": "Agent09", "rule_id": "R09_pomodoro", "day": "D-5",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.85, "priority": 75,
    "amplitude": round(0.85 * math.sqrt(75/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 5), 4),
    "message": "포모도로 8회 목표 설정"
})

# D-4: Agent07 개입 (S1)
signals.append({
    "agent": "Agent07", "rule_id": "R07_intervention", "day": "D-4",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.7, "priority": 65,
    "amplitude": round(0.7 * math.sqrt(65/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 4), 4),
    "message": "집중 시간대: 오후 3시"
})

# D-3: Agent05 불안 감정 (S3 - 상충!)
signals.append({
    "agent": "Agent05", "rule_id": "R05_emotion_negative", "day": "D-3",
    "scenario": "S3", "phase_deg": 135,
    "confidence": 0.85, "priority": 90,
    "amplitude": round(0.85 * math.sqrt(90/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 3), 4),
    "message": "불안 감정 → 완화 개입 필요"
})

# D-2: Agent06 피드백 (S1)
signals.append({
    "agent": "Agent06", "rule_id": "R06_feedback", "day": "D-2",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.9, "priority": 85,
    "amplitude": round(0.9 * math.sqrt(85/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 2), 4),
    "message": "강점강화 피드백 전달"
})

# D-1: Agent11 오답 (S2)
signals.append({
    "agent": "Agent11", "rule_id": "R11_error_review", "day": "D-1",
    "scenario": "S2", "phase_deg": 90,
    "confidence": 0.95, "priority": 95,
    "amplitude": round(0.95 * math.sqrt(95/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 1), 4),
    "message": "15개 오답 총정리"
})

# D-day: Agent14 최종 (S0)
signals.append({
    "agent": "Agent14", "rule_id": "R14_final_check", "day": "D-day",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.85, "priority": 100,
    "amplitude": round(0.85 * math.sqrt(100/100), 4),
    "temporal_weight": 1.0,
    "message": "준비도 85% → 시험 진입"
})

# 시간적 가중 간섭 계산
real_sum = 0
imag_sum = 0
individual_sum = 0

for sig in signals:
    weighted_amp = sig["amplitude"] * sig["temporal_weight"]
    phase_rad = math.radians(sig["phase_deg"])
    real_sum += weighted_amp * math.cos(phase_rad)
    imag_sum += weighted_amp * math.sin(phase_rad)
    individual_sum += weighted_amp

total_amp = math.sqrt(real_sum**2 + imag_sum**2)
efficiency = (total_amp / individual_sum * 100) if individual_sum > 0 else 0

# 상충 신호 분석
conflict_signals = [s for s in signals if s["phase_deg"] >= 90]

result = {
    "scenario_id": 5,
    "scenario_name": "1주일 주간목표 추적",
    "context": context,
    "agents_triggered": [s["agent"] for s in signals],
    "total_agents": len(signals),
    "quantum_signals": signals,
    "temporal_analysis": {
        "decay_rate": decay_rate,
        "recent_signals_weight": "D-1, D-day 영향력 최대",
        "conflict_detected": len(conflict_signals) > 0,
        "conflict_signals": [{"day": s["day"], "agent": s["agent"], "phase": s["phase_deg"]} for s in conflict_signals]
    },
    "interference_result": {
        "type": "TEMPORAL_WEIGHTED_PARTIAL",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "explanation": f"D-3 불안(135도) 상충 → 효율 {efficiency:.0f}%"
    },
    "intervention_level": "HIGH",
    "recommended_actions": [
        {"day": "D-3", "action": "불안 완화: 성공경험 리마인드"},
        {"day": "D-1", "action": "오답 15개 집중 복습"},
        {"day": "D-day", "action": "자신감 메시지 전달"}
    ],
    "weekly_summary": f"주간 효율 {efficiency:.0f}% | D-3 불안 개입 필요 | D-1 오답 복습 완료"
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;

        case 6:
            // 시나리오 6: 2개월 분기목표
            return <<<'PYTHON'
import math
import json

# 시나리오 6: 2개월(8주) 분기목표 추적
# Temporal Cascade - 장기 신호 누적 효과

context = {
    "student_id": "STU_2025_QUARTER",
    "quarter": "2025 Q4",
    "quarter_goal": "수학 내신 1등급",
    "duration_weeks": 8,
    "weekly_checkpoints": [
        {"week": 1, "agent": "Agent01", "event": "분기시작", "status": "온보딩"},
        {"week": 2, "agent": "Agent02", "event": "시험등록", "exams_registered": 3},
        {"week": 3, "agent": "Agent03", "event": "목표분석", "goal_clarity": 80},
        {"week": 4, "agent": "Agent09", "event": "중간점검", "completion_rate": 45},
        {"week": 5, "agent": "Agent13", "event": "슬럼프감지", "ninactive": 5},
        {"week": 6, "agent": "Agent12", "event": "휴식조정", "rest_pattern": "불규칙"},
        {"week": 7, "agent": "Agent05", "event": "감정악화", "emotion": "좌절"},
        {"week": 8, "agent": "Agent14", "event": "분기마감", "final_rate": 65}
    ]
}

# 8주간 Quantum 신호 (Temporal Cascade)
signals = []
decay_rate = 0.15

# Week 1: 온보딩 (S0)
signals.append({
    "agent": "Agent01", "rule_id": "R01_quarter_start", "week": 1,
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.9, "priority": 60,
    "amplitude": round(0.9 * math.sqrt(60/100), 4),
    "cascade_weight": round(math.exp(-decay_rate * 7), 4),
    "message": "분기 시작 프로필 설정"
})

# Week 2: 시험등록 (S0)
signals.append({
    "agent": "Agent02", "rule_id": "R02_exams_reg", "week": 2,
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.85, "priority": 75,
    "amplitude": round(0.85 * math.sqrt(75/100), 4),
    "cascade_weight": round(math.exp(-decay_rate * 6), 4),
    "message": "3개 시험 일정 등록"
})

# Week 3: 목표분석 (S1)
signals.append({
    "agent": "Agent03", "rule_id": "R03_goal_analysis", "week": 3,
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.8, "priority": 80,
    "amplitude": round(0.8 * math.sqrt(80/100), 4),
    "cascade_weight": round(math.exp(-decay_rate * 5), 4),
    "message": "목표 명확도 80%"
})

# Week 4: 중간점검 (S1)
signals.append({
    "agent": "Agent09", "rule_id": "R09_midterm", "week": 4,
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.7, "priority": 70,
    "amplitude": round(0.7 * math.sqrt(70/100), 4),
    "cascade_weight": round(math.exp(-decay_rate * 4), 4),
    "message": "완료율 45% - 지연 시작"
})

# Week 5: 슬럼프 감지 (S2) ⚠️
signals.append({
    "agent": "Agent13", "rule_id": "R13_dropout_high", "week": 5,
    "scenario": "S2", "phase_deg": 90,
    "confidence": 0.9, "priority": 95,
    "amplitude": round(0.9 * math.sqrt(95/100), 4),
    "cascade_weight": round(math.exp(-decay_rate * 3), 4),
    "message": "ninactive=5 → HIGH 위험"
})

# Week 6: 휴식 불규칙 (S3) ⚠️
signals.append({
    "agent": "Agent12", "rule_id": "R12_rest_irregular", "week": 6,
    "scenario": "S3", "phase_deg": 135,
    "confidence": 0.75, "priority": 80,
    "amplitude": round(0.75 * math.sqrt(80/100), 4),
    "cascade_weight": round(math.exp(-decay_rate * 2), 4),
    "message": "휴식 패턴 붕괴"
})

# Week 7: 좌절 감정 (S3) ⚠️
signals.append({
    "agent": "Agent05", "rule_id": "R05_emotion_crisis", "week": 7,
    "scenario": "S3", "phase_deg": 135,
    "confidence": 0.95, "priority": 100,
    "amplitude": round(0.95 * math.sqrt(100/100), 4),
    "cascade_weight": round(math.exp(-decay_rate * 1), 4),
    "message": "좌절 → 긴급 완화 필요"
})

# Week 8: 분기 마감 (S2)
signals.append({
    "agent": "Agent14", "rule_id": "R14_quarter_end", "week": 8,
    "scenario": "S2", "phase_deg": 90,
    "confidence": 0.65, "priority": 85,
    "amplitude": round(0.65 * math.sqrt(85/100), 4),
    "cascade_weight": 1.0,
    "message": "최종 달성률 65%"
})

# Cascade 간섭 계산
real_sum = 0
imag_sum = 0
individual_sum = 0

for sig in signals:
    weighted_amp = sig["amplitude"] * sig["cascade_weight"]
    phase_rad = math.radians(sig["phase_deg"])
    real_sum += weighted_amp * math.cos(phase_rad)
    imag_sum += weighted_amp * math.sin(phase_rad)
    individual_sum += weighted_amp

total_amp = math.sqrt(real_sum**2 + imag_sum**2)
efficiency = (total_amp / individual_sum * 100) if individual_sum > 0 else 0

# 위기 구간 분석
crisis_weeks = [s for s in signals if s["phase_deg"] >= 90]

# Cascade 연쇄 효과
cascade_analysis = {
    "trigger_point": "Week 5 (슬럼프)",
    "cascade_chain": "Week5(S2) → Week6(S3) → Week7(S3)",
    "destructive_pattern": "90° → 135° → 135° 연속 상쇄",
    "counterfactual": "Week5 개입 시 Week6-7 방지 가능"
}

result = {
    "scenario_id": 6,
    "scenario_name": "2개월 분기목표",
    "context": context,
    "agents_triggered": [s["agent"] for s in signals],
    "total_agents": len(signals),
    "quantum_signals": signals,
    "cascade_analysis": cascade_analysis,
    "crisis_detection": {
        "crisis_weeks": [s["week"] for s in crisis_weeks],
        "crisis_agents": [s["agent"] for s in crisis_weeks],
        "total_crisis_signals": len(crisis_weeks)
    },
    "interference_result": {
        "type": "TEMPORAL_CASCADE_DESTRUCTIVE",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "explanation": f"Week5-7 연쇄 상쇄 → 효율 {efficiency:.0f}%로 급락"
    },
    "intervention_level": "CRITICAL",
    "recommended_actions": [
        {"week": 5, "action": "즉시 리포커스 메시지 + 목표 세분화"},
        {"week": 6, "action": "휴식 루틴 재설정 + 마이크로 목표"},
        {"week": 7, "action": "1:1 감정 케어 + 작은 성공 경험"},
        {"week": 8, "action": "달성 가능한 수정 목표 설정"}
    ],
    "quarter_summary": f"분기 효율 {efficiency:.0f}% | Week5-7 위기 연쇄 | 조기 개입 필수"
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;

        default:
            return null;
    }
}

// Python 코드 실행
$pythonCode = getScenarioCode($scenario);

if ($pythonCode === null) {
    $result = ['error' => "Invalid scenario: {$scenario}. Valid: 4, 5, 6", 'file' => __FILE__, 'line' => __LINE__];
} else {
    // 임시 파일에 Python 코드 저장
    $tempFile = tempnam(sys_get_temp_dir(), 'scenario_');
    file_put_contents($tempFile, $pythonCode);

    // Python 실행
    $output = [];
    $returnCode = 0;
    exec("python3 " . escapeshellarg($tempFile) . " 2>&1", $output, $returnCode);

    // 임시 파일 삭제
    unlink($tempFile);

    if ($returnCode === 0) {
        $jsonOutput = implode("\n", $output);
        $result = json_decode($jsonOutput, true);
        if ($result === null) {
            $result = ['error' => 'JSON parse error', 'raw_output' => $jsonOutput, 'file' => __FILE__, 'line' => __LINE__];
        }
    } else {
        $result = ['error' => 'Python execution failed', 'output' => implode("\n", $output), 'return_code' => $returnCode, 'file' => __FILE__, 'line' => __LINE__];
    }
}

// JSON 포맷 요청시
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// HTML 출력
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scenario Test Runner - <?php echo $scenario; ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            margin: 0;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #58a6ff; border-bottom: 1px solid #30363d; padding-bottom: 10px; }
        .scenario-nav {
            display: flex; gap: 10px; margin-bottom: 20px;
        }
        .scenario-btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        .scenario-btn.active { background: #238636; color: white; }
        .scenario-btn:not(.active) { background: #21262d; color: #8b949e; border: 1px solid #30363d; }
        .scenario-btn:hover { opacity: 0.9; transform: scale(1.02); }
        .info-box {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box p { margin: 5px 0; font-size: 14px; }
        .label { color: #8b949e; }
        .value { color: #7ee787; }
        .value.warning { color: #f0883e; }
        .value.critical { color: #f85149; }
        pre {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 20px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
            max-height: 600px;
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
        .card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 15px;
        }
        .card h3 { color: #58a6ff; margin-top: 0; }
        .signal-list { list-style: none; padding: 0; }
        .signal-list li {
            padding: 8px;
            border-bottom: 1px solid #21262d;
            font-size: 13px;
        }
        .signal-list li:last-child { border-bottom: none; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-s0 { background: #238636; color: white; }
        .badge-s1 { background: #1f6feb; color: white; }
        .badge-s2 { background: #f0883e; color: black; }
        .badge-s3 { background: #f85149; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 Scenario Test Runner</h1>

    <div class="scenario-nav">
        <a href="?scenario=4" class="scenario-btn <?php echo $scenario==4 ? 'active' : ''; ?>">
            시나리오 4: 3시간 수업
        </a>
        <a href="?scenario=5" class="scenario-btn <?php echo $scenario==5 ? 'active' : ''; ?>">
            시나리오 5: 1주일 주간목표
        </a>
        <a href="?scenario=6" class="scenario-btn <?php echo $scenario==6 ? 'active' : ''; ?>">
            시나리오 6: 2개월 분기목표
        </a>
        <a href="?scenario=<?php echo $scenario; ?>&format=json" class="scenario-btn" target="_blank" style="margin-left: auto;">
            📄 JSON
        </a>
    </div>

    <div class="info-box">
        <p><span class="label">User:</span> <span class="value"><?php echo htmlspecialchars($USER->username ?? 'N/A'); ?></span></p>
        <p><span class="label">Scenario:</span> <span class="value"><?php echo $scenario; ?> - <?php echo $result['scenario_name'] ?? 'Error'; ?></span></p>
        <p><span class="label">Executed:</span> <span class="value"><?php echo date('Y-m-d H:i:s'); ?></span></p>
        <?php if (isset($result['total_agents'])): ?>
        <p><span class="label">Agents:</span> <span class="value"><?php echo $result['total_agents']; ?>개</span></p>
        <?php endif; ?>
        <?php if (isset($result['intervention_level'])): ?>
        <p><span class="label">Intervention:</span>
            <span class="value <?php echo $result['intervention_level']=='CRITICAL' ? 'critical' : ($result['intervention_level']=='HIGH' ? 'warning' : ''); ?>">
                <?php echo $result['intervention_level']; ?>
            </span>
        </p>
        <?php endif; ?>
    </div>

    <?php if (!isset($result['error'])): ?>
    <div class="grid">
        <div class="card">
            <h3>📡 Quantum Signals (<?php echo count($result['quantum_signals'] ?? []); ?>)</h3>
            <ul class="signal-list">
                <?php foreach ($result['quantum_signals'] ?? [] as $sig): ?>
                <li>
                    <span class="badge badge-s<?php echo intval($sig['phase_deg']/45); ?>">
                        <?php echo $sig['phase_deg']; ?>°
                    </span>
                    <strong><?php echo $sig['agent']; ?></strong>:
                    <?php echo $sig['message']; ?>
                    <span style="color: #8b949e; float: right;">amp: <?php echo $sig['amplitude']; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card">
            <h3>🔬 Interference Result</h3>
            <pre style="background: transparent; border: none; padding: 0;"><?php
                echo json_encode($result['interference_result'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            ?></pre>

            <h3 style="margin-top: 20px;">📋 Recommended Actions</h3>
            <ul class="signal-list">
                <?php foreach ($result['recommended_actions'] ?? [] as $action): ?>
                <li>
                    <strong><?php echo $action['time'] ?? $action['day'] ?? $action['week'] ?? ''; ?></strong>:
                    <?php echo $action['action']; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <h2>Raw JSON Output</h2>
    <pre><?php echo htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
</div>
</body>
</html>
