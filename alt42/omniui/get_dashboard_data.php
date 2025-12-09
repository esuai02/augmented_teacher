<?php
header('Content-Type: application/json; charset=utf-8');

// 직접 DB 연결 방식으로 변경
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB 접속 정보 직접 설정
$CFG = new stdClass();
$CFG->dbhost = '58.180.27.46';
$CFG->dbname = 'mathking';
$CFG->dbuser = 'moodle';
$CFG->dbpass = '@MCtrigd7128';
$CFG->prefix = 'mdl_';

try {
    // PDO 연결
    $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // URL 파라미터에서 userid 가져오기
    $userid = isset($_GET['userid']) ? intval($_GET['userid']) : null;
    
    if (!$userid) {
        throw new Exception('사용자 ID가 필요합니다.');
    }
    $response = array();
    
    // 사용자 정보 조회
    $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_users WHERE userid = ?");
    $stmt->execute([$userid]);
    $user_info = $stmt->fetch();
    
    if ($user_info) {
        // 시험 정보 조회
        $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? LIMIT 1");
        $stmt->execute([$user_info['school_name'], $user_info['grade']]);
        $exam_info = $stmt->fetch();
        
        if ($exam_info) {
            // 시험 날짜 정보 조회
            $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_exam_dates WHERE exam_id = ? AND user_id = ?");
            $stmt->execute([$exam_info['exam_id'], $user_info['id']]);
            $exam_dates = $stmt->fetch();
            
            // 학습 상태 조회
            $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_study_status WHERE user_id = ? AND exam_id = ?");
            $stmt->execute([$user_info['id'], $exam_info['exam_id']]);
            $study_status = $stmt->fetch();
            
            // D-Day 계산
            if ($exam_dates && $exam_dates['math_date']) {
                $exam_date = new DateTime($exam_dates['math_date']);
                $today = new DateTime();
                $interval = $today->diff($exam_date);
                $dday = $interval->invert ? -$interval->days : $interval->days;
                
                // 단계 결정
                if ($dday <= 7) {
                    $phase = 'finish';
                } elseif ($dday <= 21) {
                    $phase = 'intensive';
                } else {
                    $phase = 'prepare';
                }
                
                $response['dday'] = $dday;
                $response['phase'] = $phase;
            }
            
            // 학습 진행률 계산 (임시 로직 - 실제로는 학습 기록 기반)
            $total_days = 30; // 전체 학습 기간
            $elapsed_days = max(0, $total_days - ($dday ?? 30));
            $progress = min(100, round(($elapsed_days / $total_days) * 100));
            
            $response['progress'] = $progress;
            $response['exam_info'] = array(
                'type' => $exam_info['exam_type'],
                'school' => $user_info['school_name'],
                'grade' => $user_info['grade']
            );
            
            // 학습 상태
            if ($study_status) {
                $response['study_status'] = $study_status['status'];
            }
            
            // 오늘의 학습 통계 (임시 데이터)
            $response['today_stats'] = array(
                'study_time' => 45, // 분
                'solved_problems' => 12,
                'correct_rate' => 75
            );
            
            // 알림 메시지
            $response['notifications'] = array();
            
            if ($dday <= 3) {
                $response['notifications'][] = array(
                    'type' => 'urgent',
                    'message' => '시험이 얼마 남지 않았어요! 최종 점검을 시작하세요.'
                );
            } elseif ($dday <= 7) {
                $response['notifications'][] = array(
                    'type' => 'warning',
                    'message' => '이번 주가 마지막 주입니다. 약점 보완에 집중하세요.'
                );
            }
            
            // 추천 콘텐츠 (단계별)
            switch($phase ?? 'prepare') {
                case 'prepare':
                    $response['recommendations'] = array(
                        array('type' => 'concept', 'title' => '함수의 극한 개념 정리', 'priority' => 'high'),
                        array('type' => 'problem', 'title' => '기본 문제 세트 1', 'priority' => 'medium'),
                        array('type' => 'video', 'title' => '수학 개념 동영상 강의', 'priority' => 'low')
                    );
                    break;
                    
                case 'intensive':
                    $response['recommendations'] = array(
                        array('type' => 'weakness', 'title' => '미분 응용 취약점 보완', 'priority' => 'high'),
                        array('type' => 'problem', 'title' => '심화 문제 풀이', 'priority' => 'high'),
                        array('type' => 'review', 'title' => '오답노트 복습', 'priority' => 'medium')
                    );
                    break;
                    
                case 'finish':
                    $response['recommendations'] = array(
                        array('type' => 'summary', 'title' => '핵심 공식 총정리', 'priority' => 'high'),
                        array('type' => 'mock', 'title' => '실전 모의고사', 'priority' => 'high'),
                        array('type' => 'tips', 'title' => '시험 당일 팁', 'priority' => 'medium')
                    );
                    break;
            }
        }
    }
    
    $response['success'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
?>