<?php
// Moodle 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 페이지 설정
$PAGE->set_url('/studenthome/contextual_agents/beforegoinghome/view_reports.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('귀가검사 리포트 보기');

// 학생 ID 파라미터
$studentid = optional_param('studentid', 0, PARAM_INT);
$reportid = optional_param('reportid', 0, PARAM_INT);

// 권한 체크 (선생님만 볼 수 있도록)
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    // 학생은 자신의 리포트만 볼 수 있음
    $studentid = $USER->id;
}

// 단일 리포트 조회
if ($reportid) {
    $record = $DB->get_record('alt42_goinghome', ['id' => $reportid]);
    if (!$record) {
        die('리포트를 찾을 수 없습니다.');
    }
    
    // 권한 체크
    if (!$isTeacher && $record->userid != $USER->id) {
        die('접근 권한이 없습니다.');
    }
    
    $data = json_decode($record->text, true);
    
    // 실제 학생 데이터 가져오기
    $studentId = $data['student_info']['student_id'];
    $aweekago = time() - 7 * 24 * 60 * 60;
    $hoursago = time() - 24 * 60 * 60;
    
    // 침착도 데이터
    $calmnessData = $DB->get_record_sql("
        SELECT level 
        FROM mdl_alt42_calmness 
        WHERE userid = ? 
        ORDER BY timecreated DESC 
        LIMIT 1", [$studentId]);
    
    $actualCalmness = $calmnessData ? $calmnessData->level : null;
    $calmnessGrade = '';
    if ($actualCalmness !== null) {
        if ($actualCalmness >= 95) $calmnessGrade = 'A+';
        elseif ($actualCalmness >= 90) $calmnessGrade = 'A';
        elseif ($actualCalmness >= 85) $calmnessGrade = 'B+';
        elseif ($actualCalmness >= 80) $calmnessGrade = 'B';
        elseif ($actualCalmness >= 75) $calmnessGrade = 'C+';
        elseif ($actualCalmness >= 70) $calmnessGrade = 'C';
        else $calmnessGrade = 'F';
    }
    
    // 포모도르 데이터
    $pomodoroData = $DB->get_records_sql("
        SELECT * FROM mdl_abessi_tracking 
        WHERE userid = ? AND duration > ? AND hide = 0 
        ORDER BY id DESC LIMIT 10", [$studentId, $aweekago]);
    
    $pomodoroUsage = '사용 안함';
    if (count($pomodoroData) > 2) {
        $times = array_column($pomodoroData, 'timecreated');
        $finishTimes = array_column($pomodoroData, 'timefinished');
        
        if (!empty($times) && !empty($finishTimes)) {
            $minTime = min($times);
            $maxTime = max($finishTimes);
            $avgDuration = ($maxTime - $minTime) / count($pomodoroData);
            
            if ($avgDuration <= 1800) {
                $pomodoroUsage = '알차게 사용';
            } elseif ($avgDuration < 3600) {
                $pomodoroUsage = '대충 사용';
            }
        }
    }
    
    // 오답노트 데이터
    $errorNoteData = $DB->get_records_sql("
        SELECT * FROM mdl_abessi_messages 
        WHERE userid = ? AND (student_check = 1 OR turn = 1) AND hide = 0 AND timemodified > ? 
        ORDER BY timemodified DESC LIMIT 10", [$studentId, $hoursago]);
    
    $errorNoteCount = count($errorNoteData);
    
    // 필수 질문 정의
    $requiredQuestions = [
        'calmness' => '오늘 수업 중 침착도는 어땠어?',
        'pomodoro' => '포모도르 수학일기는 어떻게 사용했어?',
        'inefficiency' => '오늘 비효율적으로 시간을 보낸 구간이 있었어?'
    ];
    
    // 랜덤 질문 정의
    $randomQuestions = [
        'weekly_goal' => '주간목표를 확인하고 오늘 목표를 정했어?',
        'daily_plan' => '오늘 계획한 진도는 다 나갔어?',
        'pace_anxiety' => '진도가 느려서 불안하지는 않았어?',
        'satisfaction' => '오늘 수업에 대한 만족도는 어때?',
        'boredom' => '공부하다가 지루한 구간은 없었어?',
        'stress_level' => '공부하다가 불안하거나 스트레스가 커진 구간은 없었어?',
        'positive_moment' => '수학공부에 대한 긍정적 인식이 생긴 장면이 있었어?',
        'problem_count' => '오늘 몇 문제나 풀었어?',
        'error_note' => '오답노트는 밀리지 않았어?',
        'concept_study' => '개념공부 과정은 적절했어?',
        'difficulty_level' => '오늘 공부한 난이도가 시험대비를 고려할 때 적합했어?',
        'easy_problems' => '너무 쉬운 문제만 풀고 있는 건 아니야?',
        'self_improvement' => '스스로 고치고 싶은 부분이 발견됐어?',
        'missed_opportunity' => '스스로 망설이다 기회를 놓친 경우는 없었어?',
        'intuition_solving' => '느낌으로 푼 문제는 없었어?',
        'forced_solving' => '무리해서 확인없이 풀이를 강행한 경우는 없었어?',
        'questions_asked' => '필요한 질문들은 모두 했어?',
        'unsaid_words' => '선생님께 할 말이 있었는데 참거나 넘어간 경우는 없었어?',
        'rest_pattern' => '휴식시간은 쉬고 공부할 때는 집중하는 패턴이 유지됐어?',
        'long_problem' => '한 문제를 너무 오래 풀다가 집중력이 떨어진 경우는 없었어?',
        'study_amount' => '오늘 공부양이 적절했다고 생각해?'
    ];
    
    $allQuestions = array_merge($requiredQuestions, $randomQuestions);
    
    // 실제 데이터 기반 주의 필요 항목 체크
    $needsAttention = [];
    
    // 실제 침착도 데이터 확인
    if ($calmnessGrade && in_array($calmnessGrade, ['C+', 'C', 'F'])) {
        $needsAttention[] = '침착도가 낮음 (실제: ' . $calmnessGrade . ')';
    }
    
    // 실제 포모도로 사용 데이터 확인
    if ($pomodoroUsage === '사용 안함') {
        $needsAttention[] = '수학일기 미사용 (실제 데이터)';
    } elseif ($pomodoroUsage === '대충 사용') {
        $needsAttention[] = '수학일기 비효율적 사용 (평균 시간 초과)';
    }
    
    // 실제 오답노트 데이터 확인
    if ($errorNoteCount === 0) {
        $needsAttention[] = '오답노트 미작성 (최근 활동 없음)';
    } elseif ($errorNoteCount < 3) {
        $needsAttention[] = '오답노트 활동 부족 (최근 ' . $errorNoteCount . '개만 작성)';
    }
    
    // 추가 데이터 기반 분석
    if ($actualCalmness !== null && $actualCalmness < 70) {
        $needsAttention[] = '매우 낮은 집중도 (' . $actualCalmness . '%)';
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>귀가검사 리포트</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: #f3f4f6;
                padding: 2rem;
            }
            
            .container {
                max-width: 800px;
                margin: 0 auto;
            }
            
            .back-button {
                display: inline-block;
                margin-bottom: 1rem;
                padding: 0.5rem 1rem;
                background: #6b7280;
                color: white;
                text-decoration: none;
                border-radius: 0.375rem;
                font-size: 0.875rem;
            }
            
            .back-button:hover {
                background: #4b5563;
            }
            
            .report {
                background: white;
                border-radius: 0.75rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                padding: 2rem;
            }
            
            h1 {
                font-size: 1.875rem;
                font-weight: bold;
                margin-bottom: 1.5rem;
                color: #1f2937;
                text-align: center;
            }
            
            .report-info {
                background: #f9fafb;
                padding: 1rem;
                border-radius: 0.5rem;
                margin-bottom: 1.5rem;
                font-size: 0.875rem;
                color: #4b5563;
            }
            
            .report-info p {
                margin: 0.375rem 0;
            }
            
            .attention-box {
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 0.5rem;
                padding: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .attention-box h3 {
                color: #991b1b;
                margin-bottom: 0.5rem;
                font-size: 1.125rem;
            }
            
            .attention-box ul {
                color: #b91c1c;
                margin-left: 1.5rem;
            }
            
            .responses-section {
                margin-top: 2rem;
            }
            
            .responses-section h3 {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 1rem;
            }
            
            .response-item {
                border-bottom: 1px solid #e5e7eb;
                padding: 1rem 0;
            }
            
            .response-item:last-child {
                border-bottom: none;
            }
            
            .response-question {
                font-weight: 500;
                color: #374151;
                margin-bottom: 0.25rem;
            }
            
            .response-answer {
                color: #2563eb;
                margin-left: 1rem;
            }
            
            .print-button {
                display: block;
                margin: 2rem auto 0;
                padding: 0.75rem 2rem;
                background: #10b981;
                color: white;
                border: none;
                border-radius: 0.5rem;
                font-size: 1rem;
                cursor: pointer;
            }
            
            .print-button:hover {
                background: #059669;
            }
            
            @media print {
                body {
                    background: white;
                    padding: 0;
                }
                
                .back-button,
                .print-button {
                    display: none;
                }
                
                .report {
                    box-shadow: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                <a href="view_reports.php<?php echo $isTeacher ? '' : '?studentid=' . $USER->id; ?>" class="back-button">← 목록으로</a>
                <a href="data_mapping_analysis_agent07.php?studentid=<?php echo $studentId; ?>" style="padding: 0.5rem 1rem; background: #8b5cf6; color: white; text-decoration: none; border-radius: 0.375rem; font-size: 0.875rem; transition: all 0.2s;" onmouseover="this.style.background='#7c3aed'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#8b5cf6'; this.style.transform='translateY(0)'">
                    🔍 Agent07 데이터 분석
                </a>
            </div>
            
            <div class="report">
                <h1>🎓 귀가검사 리포트</h1>
                
                <div class="report-info">
                    <p>👤 학생: <?php echo $data['student_name']; ?></p>
                    <p>🕐 날짜: <?php echo $data['date'] ?? date('Y년 n월 j일', $record->timecreated); ?></p>
                    <p>리포트 ID: <?php echo $data['report_id']; ?></p>
                </div>
                
                <?php if (!empty($needsAttention)): ?>
                <div class="attention-box">
                    <h3>⚠️ 주의 필요 사항</h3>
                    <ul>
                        <?php foreach ($needsAttention as $item): ?>
                        <li><?php echo $item; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="actual-data-section" style="margin-top: 1.5rem; padding: 1rem; background-color: #f0f9ff; border-radius: 8px; border: 1px solid #3b82f6;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #1e40af; margin-bottom: 1rem;">📈 실제 학습 데이터 분석</h3>
                    <div style="display: grid; gap: 0.5rem;">
                        <p><strong>침착도:</strong> <?php echo $calmnessGrade ? $calmnessGrade . ' (' . ($actualCalmness ?? 'N/A') . '%)' : '데이터 없음'; ?></p>
                        <p><strong>수학일기 사용:</strong> <?php echo $pomodoroUsage; ?></p>
                        <p><strong>오답노트 활동:</strong> 최근 <?php echo $errorNoteCount; ?>개 작성</p>
                    </div>
                </div>
                
                <div class="engagement-graph-section">
                    <h3>📊 당일 실시간 몰입도 그래프</h3>
                    <iframe 
                        src="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/calmness.php?id=<?php echo $data['student_info']['student_id']; ?>"
                        width="100%"
                        height="400"
                        frameborder="0"
                        style="border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                    </iframe>
                </div>
                
                <div class="responses-section">
                    <h3>📝 응답 내용</h3>
                    <?php foreach ($data['responses'] as $key => $value): ?>
                        <?php if (isset($allQuestions[$key])): ?>
                        <div class="response-item">
                            <p class="response-question"><?php echo $allQuestions[$key]; ?></p>
                            <p class="response-answer">→ <?php echo $value; ?></p>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <button onclick="window.print()" class="print-button">🖨️ 리포트 인쇄하기</button>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 리포트 목록 조회
$conditions = [];
$params = [];

if ($studentid) {
    $conditions[] = 'userid = :userid';
    $params['userid'] = $studentid;
}

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sql = "SELECT * FROM {alt42_goinghome} $where ORDER BY timecreated DESC";
$records = $DB->get_records_sql($sql, $params);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>귀가검사 리포트 목록</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f3f4f6;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 2rem;
            color: #1f2937;
            text-align: center;
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-form select,
        .filter-form input {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .filter-form button {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .filter-form button:hover {
            background: #2563eb;
        }
        
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .report-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .report-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }
        
        .report-card .date {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .report-card .summary {
            font-size: 0.875rem;
            color: #4b5563;
            margin-bottom: 1rem;
        }
        
        .view-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: background 0.2s;
        }
        
        .view-button:hover {
            background: #2563eb;
        }
        
        .no-reports {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }
        
        .status-good {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-warning {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="margin: 0;">📋 귀가검사 리포트 목록</h1>
            <div style="display: flex; gap: 1rem;">
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/rules/data_mapping_analysis.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#5568d3'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#667eea'; this.style.transform='translateY(0)'">
                    🔍 Agent01 데이터 분석
                </a>
                <a href="agent03_data_analysis.php<?php echo $studentid ? '?studentid=' . $studentid : ''; ?>" style="padding: 0.75rem 1.5rem; background: #f59e0b; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#d97706'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#f59e0b'; this.style.transform='translateY(0)'">
                    🎯 Agent03 분석
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent08_calmness/rules/data_mapping_analysis.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #10b981; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'">
                    🧘 Agent08 데이터 분석
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/rules/data_mapping_analysis.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #8b5cf6; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#7c3aed'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#8b5cf6'; this.style.transform='translateY(0)'">
                    📝 Agent11 데이터 분석
                </a>
                <a href="data_mapping_analysis.php?agentid=agent04_inspect_weakpoints&studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #f59e0b; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#d97706'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#f59e0b'; this.style.transform='translateY(0)'">
                    📊 Agent04 데이터 매핑 분석
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent19_interaction_content/rules/data_mapping_analysis.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #ec4899; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#db2777'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#ec4899'; this.style.transform='translateY(0)'">
                    🎬 Agent19 데이터 매핑 분석
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/rules/data_mapping_analysis.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #8b5cf6; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#7c3aed'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#8b5cf6'; this.style.transform='translateY(0)'">
                    🎯 Agent20 데이터 매핑 분석
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/rules/data_mapping_analysis.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #10b981; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'">
                    🛌 Agent12 데이터 분석
                </a>
                <a href="data_mapping_analysis_agent07.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #8b5cf6; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#7c3aed'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#8b5cf6'; this.style.transform='translateY(0)'">
                    🎯 Agent07 데이터 매핑 분석
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent21_intervention_execution/rules/data_mapping_analysis.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #ec4899; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#db2777'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#ec4899'; this.style.transform='translateY(0)'">
                    🎯 Agent21 데이터 매핑 분석
                </a>
            </div>
        </div>
        
        <?php if ($isTeacher): ?>
        <div class="filter-section">
            <form method="get" class="filter-form">
                <label>학생 선택:</label>
                <select name="studentid">
                    <option value="">전체 학생</option>
                    <?php
                    $students = $DB->get_records_sql("
                        SELECT DISTINCT u.id, u.firstname, u.lastname 
                        FROM {user} u 
                        JOIN {alt42_goinghome} g ON u.id = g.userid 
                        ORDER BY u.lastname, u.firstname
                    ");
                    foreach ($students as $student) {
                        $selected = ($studentid == $student->id) ? 'selected' : '';
                        echo "<option value='{$student->id}' $selected>{$student->lastname} {$student->firstname}</option>";
                    }
                    ?>
                </select>
                <button type="submit">필터 적용</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (empty($records)): ?>
        <div class="no-reports">
            <p>아직 리포트가 없습니다.</p>
        </div>
        <?php else: ?>
        <div class="report-grid">
            <?php foreach ($records as $record): ?>
            <?php 
                $data = json_decode($record->text, true);
                $calmness = $data['responses']['calmness'] ?? 'N/A';
                $isGood = !in_array($calmness, ['C+', 'C', 'F']);
            ?>
            <div class="report-card">
                <h3><?php echo $data['student_name']; ?></h3>
                <p class="date"><?php echo date('Y년 n월 j일 H:i', $record->timecreated); ?></p>
                <div class="summary">
                    <span class="status-badge <?php echo $isGood ? 'status-good' : 'status-warning'; ?>">
                        침착도: <?php echo $calmness; ?>
                    </span>
                    <p style="margin-top: 0.5rem;">
                        포모도르: <?php echo $data['responses']['pomodoro'] ?? 'N/A'; ?>
                    </p>
                </div>
                <a href="view_reports.php?reportid=<?php echo $record->id; ?>" class="view-button">
                    리포트 보기 →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>