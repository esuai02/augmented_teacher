<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$contentsid = $_GET['cid'] ?? '';
$contentstype = $_GET['ctype'] ?? '';
$studentid = $_GET['studentid'] ?? $USER->id; // studentid 파라미터 추가

if (empty($contentsid)) {
    echo json_encode(['success' => false, 'error' => '필수 파라미터가 없습니다.']);
    exit;
}

try {
    if ($contentstype == 'interaction') {
        // 테이블 존재 확인 후 데이터 가져오기
        if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
            $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $contentsid));
            
            if ($interaction) {
                // 나레이션 텍스트가 비어있는 경우 solution_text 사용
                $narrationText = $interaction->narration_text;
                if (empty($narrationText) && !empty($interaction->solution_text)) {
                    // solution_text를 대화 형식으로 변환
                    $narrationText = convertSolutionToDialogue($interaction->solution_text);
                }
                
                // 문제 텍스트가 비어있는 경우 기본 텍스트 제공
                $problemText = $interaction->problem_text;
                if (empty($problemText)) {
                    $problemText = '선생님이 업로드한 문제를 해결해보겠습니다.';
                }
                
                // DB에서 화이트보드 ID 조회
                // 1순위: ktm_teaching_interactions 테이블에 저장된 wboardid 사용
                $wboardid = null;
                
                if (!empty($interaction->wboardid)) {
                    $wboardid = $interaction->wboardid;
                    error_log(sprintf(
                        '[get_dialogue_data.php] File: %s, Line: %d, Found wboardid from ktm_teaching_interactions: %s',
                        basename(__FILE__),
                        __LINE__,
                        $wboardid
                    ));
                } else {
                    // 2순위: abessi_messages 테이블에서 조회 (기존 로직)
                    // contentsid = interaction->id, contentstype = 2, userid = studentid
                    
                    // 먼저 active=1인 레코드 조회 시도
                    try {
                        $wbRecord = $DB->get_record_sql(
                            "SELECT wboardid, id, timemodified, active FROM {abessi_messages} 
                             WHERE contentsid = ? 
                             AND contentstype = 2 
                             AND userid = ? 
                             AND active = 1 
                             ORDER BY timemodified DESC LIMIT 1",
                            [$interaction->id, $studentid]
                        );
                        
                        if ($wbRecord && !empty($wbRecord->wboardid)) {
                            $wboardid = $wbRecord->wboardid;
                            error_log(sprintf(
                                '[get_dialogue_data.php] File: %s, Line: %d, Found wboardid from abessi_messages (active=1): %s (record id: %d)',
                                basename(__FILE__),
                                __LINE__,
                                $wboardid,
                                $wbRecord->id
                            ));
                        }
                    } catch (Exception $e) {
                        error_log('화이트보드 ID 조회 오류 (active=1): ' . $e->getMessage());
                    }
                    
                    // active=1인 레코드가 없으면 active 조건 없이 조회
                    if (empty($wboardid)) {
                        try {
                            $wbRecord = $DB->get_record_sql(
                                "SELECT wboardid, id, timemodified, active FROM {abessi_messages} 
                                 WHERE contentsid = ? 
                                 AND contentstype = 2 
                                 AND userid = ? 
                                 ORDER BY timemodified DESC LIMIT 1",
                                [$interaction->id, $studentid]
                            );
                            
                            if ($wbRecord && !empty($wbRecord->wboardid)) {
                                $wboardid = $wbRecord->wboardid;
                                error_log(sprintf(
                                    '[get_dialogue_data.php] File: %s, Line: %d, Found wboardid from abessi_messages (any active): %s (record id: %d, active: %s)',
                                    basename(__FILE__),
                                    __LINE__,
                                    $wboardid,
                                    $wbRecord->id,
                                    $wbRecord->active ?? 'NULL'
                                ));
                            } else {
                                // 모든 레코드 확인 (디버깅용)
                                $allRecords = $DB->get_records_sql(
                                    "SELECT id, wboardid, contentsid, contentstype, userid, active, timemodified FROM {abessi_messages} 
                                     WHERE contentsid = ? 
                                     AND contentstype = 2 
                                     ORDER BY timemodified DESC LIMIT 5",
                                    [$interaction->id]
                                );
                                error_log(sprintf(
                                    '[get_dialogue_data.php] File: %s, Line: %d, No wboardid found. Searched: contentsid=%d, contentstype=2, userid=%d. Found %d records (without userid filter)',
                                    basename(__FILE__),
                                    __LINE__,
                                    $interaction->id,
                                    $studentid,
                                    count($allRecords)
                                ));
                                if (!empty($allRecords)) {
                                    foreach ($allRecords as $rec) {
                                        error_log(sprintf(
                                            '  - Record ID: %d, wboardid: %s, userid: %s, active: %s',
                                            $rec->id,
                                            $rec->wboardid ?? 'NULL',
                                            $rec->userid ?? 'NULL',
                                            $rec->active ?? 'NULL'
                                        ));
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            error_log('화이트보드 ID 조회 오류 (any active): ' . $e->getMessage());
                        }
                    }
                    
                    // DB에서 찾지 못한 경우 기본값 생성
                    if (empty($wboardid)) {
                        $wboardid = 'WB_' . $interaction->id . '_' . $studentid . '_' . date('Y_m_d');
                        error_log(sprintf(
                            '[get_dialogue_data.php] File: %s, Line: %d, Using generated wboardid: %s',
                            basename(__FILE__),
                            __LINE__,
                            $wboardid
                        ));
                    }
                }
                
                // 디버그: DB에서 조회된 모든 관련 레코드 확인
                $debugRecords = [];
                try {
                    $allWbRecords = $DB->get_records_sql(
                        "SELECT id, wboardid, contentsid, contentstype, userid, active, timemodified 
                         FROM {abessi_messages} 
                         WHERE contentsid = ? 
                         AND contentstype = 2 
                         ORDER BY timemodified DESC LIMIT 10",
                        [$interaction->id]
                    );
                    foreach ($allWbRecords as $rec) {
                        $debugRecords[] = [
                            'id' => $rec->id,
                            'wboardid' => $rec->wboardid ?? 'NULL',
                            'userid' => $rec->userid ?? 'NULL',
                            'active' => $rec->active ?? 'NULL',
                            'timemodified' => $rec->timemodified ?? 'NULL'
                        ];
                    }
                } catch (Exception $e) {
                    error_log('디버그 레코드 조회 오류: ' . $e->getMessage());
                }
                
                $response = [
                    'success' => true,
                    'narrationText' => $narrationText ?: '해설을 준비하고 있습니다.',
                    'problemImage' => $interaction->problem_image ?? '',
                    'solutionImage' => $interaction->solution_image ?? '',
                    'problemText' => $problemText,
                    'audioUrl' => $interaction->audio_url ?? '',
                    'solutionText' => $interaction->solution_text ?? '',
                    'faqtext' => $interaction->faqtext ?? '',
                    'wboardid' => $wboardid,
                    'contentsid' => $interaction->id,
                    'contentstype' => 2,
                    'type' => $interaction->type ?? '',
                    'debug' => [
                        'searched_contentsid' => $interaction->id,
                        'searched_contentstype' => 2,
                        'searched_userid' => $studentid,
                        'found_wboardid' => $wboardid,
                        'all_records' => $debugRecords
                    ],
                    'interactionData' => [
                        'id' => $interaction->id,
                        'status' => $interaction->status,
                        'type' => $interaction->type ?? '',
                        'created' => date('Y-m-d H:i:s', $interaction->timecreated)
                    ]
                ];
                
                echo json_encode($response);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => '상호작용 데이터를 찾을 수 없습니다. ID: ' . $contentsid
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'ktm_teaching_interactions 테이블이 존재하지 않습니다.'
            ]);
        }
    } else {
        // 기존 테이블에서 데이터 가져오기 (하위 호환성)
        $result = $DB->get_record_sql(
            "SELECT * FROM mdl_abrainalignment_gptresults 
             WHERE type LIKE 'conversation' 
             AND contentsid LIKE ? 
             AND contentstype LIKE ? 
             ORDER BY id DESC LIMIT 1",
            [$contentsid, $contentstype]
        );
        
        if ($result) {
            // 문제 정보 가져오기
            $problem = $DB->get_record_sql(
                "SELECT * FROM mdl_abessi_messages 
                 WHERE contentsid = ? 
                 AND contentstype = ? 
                 ORDER BY id DESC LIMIT 1",
                [$contentsid, $contentstype]
            );
            
            $response = [
                'success' => true,
                'narrationText' => $result->outputtext ?? '',
                'problemImage' => $problem->image_url ?? '',
                'problemText' => $problem->problem_text ?? '',
                'audioUrl' => $result->audio_url ?? ''
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '데이터를 찾을 수 없습니다.'
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류: ' . $e->getMessage()
    ]);
}

// solution_text를 대화 형식으로 변환하는 함수
function convertSolutionToDialogue($solutionText) {
    if (empty($solutionText)) {
        return '';
    }
    
    // 이미 대화 형식인지 확인
    if (strpos($solutionText, '선생님:') !== false || strpos($solutionText, '학생:') !== false) {
        return $solutionText;
    }
    
    // 문장을 나누고 대화 형식으로 변환
    $sentences = preg_split('/[.!?。！？]/', $solutionText);
    $sentences = array_filter($sentences, function($s) { return trim($s) !== ''; });
    
    $dialogue = '';
    $isTeacher = true;
    
    foreach ($sentences as $index => $sentence) {
        $sentence = trim($sentence);
        if (empty($sentence)) continue;
        
        if ($isTeacher) {
            $dialogue .= "선생님: " . $sentence . ".\n";
            // 학생 응답 추가 (몇 문장마다)
            if ($index % 3 == 2) {
                $responses = [
                    "네, 이해했어요!",
                    "아, 그렇게 하는 거군요!",
                    "좀 더 자세히 설명해주실 수 있나요?",
                    "다음 단계는 어떻게 하나요?"
                ];
                $dialogue .= "학생: " . $responses[array_rand($responses)] . "\n";
            }
        }
        $isTeacher = !$isTeacher;
    }
    
    // 마지막에 학생 감사 인사 추가
    if (!empty($dialogue)) {
        $dialogue .= "학생: 감사합니다! 이제 이해됐어요.";
    }
    
    return $dialogue;
}
?>