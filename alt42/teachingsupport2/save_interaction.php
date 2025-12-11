<?php
/**
 * save_interaction.php - 학생-선생님 상호작용 저장 API
 * 파일 위치: alt42/teachingsupport/save_interaction.php
 *
 * 모든 응답은 JSON 형식으로 반환됩니다.
 * 에러 발생 시에도 JSON 형식을 유지합니다.
 */

// 출력 버퍼링 시작 (HTML 에러 출력 방지)
ob_start();

// JSON 헤더를 최우선으로 설정
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// 에러 출력을 로그로만 보내기 (화면에 출력하지 않음)
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    // Moodle config 로드 시도
    if (!file_exists("/home/moodle/public_html/moodle/config.php")) {
        throw new Exception('Moodle config 파일을 찾을 수 없습니다. (파일 위치: save_interaction.php:23)');
    }

    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;

    // require_login() 주석 처리됨 - 필요시 활성화
    // require_login();

} catch (Exception $configError) {
    // 버퍼 지우고 JSON 에러 응답
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Config 로드 실패: ' . $configError->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
    exit;
}

// POST 데이터 받기
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// JSON 파싱 에러 체크
if (json_last_error() !== JSON_ERROR_NONE && !empty($rawInput)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'JSON 파싱 실패: ' . json_last_error_msg(),
        'raw_input_preview' => substr($rawInput, 0, 100),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
    exit;
}

$action = $input['action'] ?? '';

try {
    $time = time();
    
    // 데이터베이스 테이블 존재 확인 및 생성
    try {
        $dbman = $DB->get_manager();
        
        // ktm_teaching_interactions 테이블 생성
        if (!$dbman->table_exists('ktm_teaching_interactions')) {
            try {
                // 간단한 SQL로 테이블 생성 (Moodle XMLDB 대신)
                $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_teaching_interactions (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    userid BIGINT(10) NOT NULL,
                    teacherid BIGINT(10) DEFAULT NULL,
                    wboardid VARCHAR(255) DEFAULT NULL,
                    type VARCHAR(50) DEFAULT NULL,
                    problem_type VARCHAR(255) DEFAULT NULL,
                    problem_image LONGTEXT DEFAULT NULL,
                    problem_text LONGTEXT DEFAULT NULL,
                    solution_text LONGTEXT DEFAULT NULL,
                    narration_text LONGTEXT DEFAULT NULL,
                    audio_url TEXT DEFAULT NULL,
                    faqtext LONGTEXT DEFAULT NULL,
                    modification_prompt LONGTEXT DEFAULT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    timecreated BIGINT(10) NOT NULL,
                    timemodified BIGINT(10) NOT NULL,
                    PRIMARY KEY (id),
                    INDEX userid_idx (userid),
                    INDEX teacherid_idx (teacherid),
                    INDEX wboardid_idx (wboardid),
                    INDEX type_idx (type)
                )";
                
                $DB->execute($sql);
            } catch (Exception $e) {
                error_log('ktm_teaching_interactions 테이블 생성 실패: ' . $e->getMessage());
            }
        } else {
            // 테이블이 이미 존재하는 경우 필드 추가 (없으면)
            try {
                // faqtext 필드 추가
                $columnExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'faqtext');
                if (!$columnExists) {
                    $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN faqtext LONGTEXT DEFAULT NULL";
                    $DB->execute($sql);
                    error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', faqtext 필드 추가 완료');
                }
            } catch (Exception $e) {
                error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', faqtext 필드 추가 실패: ' . $e->getMessage());
            }
            
            try {
                // wboardid 필드 추가
                $wboardidExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'wboardid');
                if (!$wboardidExists) {
                    $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN wboardid VARCHAR(255) DEFAULT NULL";
                    $DB->execute($sql);
                    // 인덱스 추가
                    $indexSql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD INDEX wboardid_idx (wboardid)";
                    try {
                        $DB->execute($indexSql);
                    } catch (Exception $idxError) {
                        // 인덱스가 이미 존재할 수 있으므로 무시
                        error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', wboardid 인덱스 추가 실패 (무시 가능): ' . $idxError->getMessage());
                    }
                    error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', wboardid 필드 추가 완료');
                }
            } catch (Exception $e) {
                error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', wboardid 필드 추가 실패: ' . $e->getMessage());
            }
            
            try {
                // type 필드 추가
                $typeExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'type');
                if (!$typeExists) {
                    $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN type VARCHAR(50) DEFAULT NULL";
                    $DB->execute($sql);
                    // 인덱스 추가
                    $indexSql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD INDEX type_idx (type)";
                    try {
                        $DB->execute($indexSql);
                    } catch (Exception $idxError) {
                        // 인덱스가 이미 존재할 수 있으므로 무시
                        error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', type 인덱스 추가 실패 (무시 가능): ' . $idxError->getMessage());
                    }
                    error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', type 필드 추가 완료');
                }
            } catch (Exception $e) {
                error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', type 필드 추가 실패: ' . $e->getMessage());
            }
        }
        
        // ktm_teaching_events 테이블 생성
        if (!$dbman->table_exists('ktm_teaching_events')) {
            try {
                $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_teaching_events (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    userid BIGINT(10) NOT NULL,
                    interactionid BIGINT(10) DEFAULT NULL,
                    event_type VARCHAR(100) NOT NULL,
                    event_description LONGTEXT DEFAULT NULL,
                    metadata LONGTEXT DEFAULT NULL,
                    timecreated BIGINT(10) NOT NULL,
                    PRIMARY KEY (id),
                    INDEX userid_idx (userid),
                    INDEX interactionid_idx (interactionid)
                )";
                
                $DB->execute($sql);
            } catch (Exception $e) {
                error_log('ktm_teaching_events 테이블 생성 실패: ' . $e->getMessage());
            }
        }
    } catch (Exception $table_error) {
        error_log('테이블 생성 중 오류: ' . $table_error->getMessage());
    }
    
    switch($action) {
        case 'create_interaction':
            // 새로운 상호작용 레코드 생성
            $interaction = new stdClass();
            $interaction->userid = (int)($input['studentId'] ?? $USER->id);  // 학생 ID
            $interaction->teacherid = (int)($input['teacherId'] ?? $USER->id);  // 선생님 ID - 현재 사용자 ID를 기본값으로 사용
            
            // wboardid 생성: capturequestion_userid_NN_TT 형식
            // NN: userid, TT: unixtime
            $interaction->wboardid = 'capturequestion_userid_' . $interaction->userid . '_' . $time;
            
            // type 필드 설정: 풀이요청 전송 시 capture로 설정
            $interaction->type = 'capture';
            
            $interaction->problem_type = $input['problemType'] ?? '';
            $interaction->problem_text = $input['problemText'] ?? '';
            
            // 이미지 처리 - base64를 파일로 저장
            if (!empty($input['problemImage']) && strpos($input['problemImage'], 'data:') === 0) {
                try {
                    // 이미지 크기 로깅
                    $imageDataLength = strlen($input['problemImage']);
                    $imageSizeMB = round($imageDataLength / 1024 / 1024, 2);
                    error_log(sprintf(
                        '[save_interaction.php] 이미지 처리 시작 - 크기: %sMB (%s bytes)',
                        $imageSizeMB,
                        number_format($imageDataLength)
                    ));

                    // base64 데이터 파싱
                    list($type, $data) = explode(';', $input['problemImage']);
                    list(, $data) = explode(',', $data);
                    $imageData = base64_decode($data);

                    if ($imageData === false) {
                        throw new Exception('base64 디코딩 실패');
                    }

                    $decodedSizeMB = round(strlen($imageData) / 1024 / 1024, 2);
                    error_log(sprintf(
                        '[save_interaction.php] base64 디코딩 완료 - 디코딩 후 크기: %sMB',
                        $decodedSizeMB
                    ));

                    // MIME 타입에서 확장자 추출
                    $mimeType = str_replace('data:', '', $type);
                    $extension = 'jpg';
                    if (strpos($mimeType, 'png') !== false) {
                        $extension = 'png';
                    } elseif (strpos($mimeType, 'gif') !== false) {
                        $extension = 'gif';
                    }

                    error_log(sprintf('[save_interaction.php] MIME 타입: %s, 확장자: %s', $mimeType, $extension));

                    // 고유한 파일명 생성
                    $uniqueFilename = 'problem_' . time() . '_' . uniqid() . '.' . $extension;
                    $uploadDir = __DIR__ . '/images/';

                    // 디렉토리 생성
                    if (!file_exists($uploadDir)) {
                        error_log('[save_interaction.php] 이미지 디렉토리 생성 시도: ' . $uploadDir);
                        if (!mkdir($uploadDir, 0755, true)) {
                            throw new Exception('이미지 디렉토리 생성 실패: ' . $uploadDir);
                        }
                        error_log('[save_interaction.php] 이미지 디렉토리 생성 완료');
                    }

                    // 파일 저장
                    $imagePath = $uploadDir . $uniqueFilename;
                    error_log(sprintf('[save_interaction.php] 파일 저장 시도: %s', $imagePath));

                    $writeResult = file_put_contents($imagePath, $imageData);
                    if ($writeResult === false) {
                        throw new Exception('이미지 파일 저장 실패: ' . $imagePath);
                    }

                    error_log(sprintf(
                        '[save_interaction.php] 파일 저장 완료 - 경로: %s, 크기: %s bytes',
                        $imagePath,
                        number_format($writeResult)
                    ));

                    // DB에는 상대 경로만 저장
                    $interaction->problem_image = 'images/' . $uniqueFilename;
                    error_log(sprintf('[save_interaction.php] DB 저장용 상대 경로: %s', $interaction->problem_image));

                } catch (Exception $imgError) {
                    error_log(sprintf(
                        '[save_interaction.php] 이미지 저장 오류: %s (파일: %s, 라인: %d)',
                        $imgError->getMessage(),
                        basename(__FILE__),
                        $imgError->getLine()
                    ));
                    // 이미지 저장 실패시 base64 그대로 저장 (fallback)
                    $interaction->problem_image = $input['problemImage'] ?? '';
                    error_log('[save_interaction.php] Fallback: base64 데이터를 DB에 직접 저장');
                }
            } else {
                $interaction->problem_image = $input['problemImage'] ?? '';
                error_log('[save_interaction.php] base64 데이터 아님 또는 비어있음');
            }
            $interaction->modification_prompt = $input['modificationPrompt'] ?? '';
            $interaction->status = 'pending';
            $interaction->timecreated = $time;
            $interaction->timemodified = $time;
            
            // 학생 ID 유효성 검사
            if (!$interaction->userid) {
                throw new Exception('학생 ID가 필요합니다.');
            }
            
            try {
                // 먼저 테이블 존재 확인
                if (!$DB->get_manager()->table_exists('ktm_teaching_interactions')) {
                    throw new Exception('ktm_teaching_interactions 테이블이 존재하지 않습니다.');
                }

                // 데이터 유효성 재검사
                if (empty($interaction->userid) || $interaction->userid <= 0) {
                    throw new Exception('유효하지 않은 학생 ID: ' . $interaction->userid);
                }

                // 학생이 실제 존재하는지 확인
                $student_exists = $DB->get_record('user', array('id' => $interaction->userid));
                if (!$student_exists) {
                    throw new Exception('존재하지 않는 학생 ID: ' . $interaction->userid);
                }

                // DB 삽입 전 데이터 크기 로깅
                $image_size = strlen($interaction->problem_image ?? '');
                $image_size_mb = round($image_size / 1024 / 1024, 2);
                error_log(sprintf(
                    '[save_interaction.php] DB 삽입 시도 - 학생ID: %d, 이미지 크기: %sMB (%s bytes)',
                    $interaction->userid,
                    $image_size_mb,
                    number_format($image_size)
                ));

                $interaction_id = $DB->insert_record('ktm_teaching_interactions', $interaction);

                if (!$interaction_id) {
                    throw new Exception('상호작용 레코드 생성에 실패했습니다.');
                }

                error_log(sprintf(
                    '[save_interaction.php] DB 삽입 성공 - Interaction ID: %d',
                    $interaction_id
                ));

                // type 필드를 확실하게 저장하기 위해 insert_record 후 별도로 업데이트
                // Moodle의 insert_record가 XMLDB 스키마에 없는 필드를 무시할 수 있으므로
                // 직접 SQL로 업데이트
                try {
                    $updateSql = "UPDATE {$CFG->prefix}ktm_teaching_interactions SET type = :type WHERE id = :id";
                    $DB->execute($updateSql, array('type' => 'capture', 'id' => $interaction_id));
                    error_log('[save_interaction.php] type 필드를 capture로 업데이트 완료 (ID: ' . $interaction_id . ')');
                } catch (Exception $typeError) {
                    // type 필드 업데이트 실패는 치명적이지 않으므로 로그만 남기고 계속 진행
                    error_log('[save_interaction.php] type 필드 업데이트 실패: ' . $typeError->getMessage());
                }
            } catch (dml_exception $e) {
                error_log(sprintf(
                    '[save_interaction.php] DML Exception - 메시지: %s, 디버그 정보: %s',
                    $e->getMessage(),
                    $e->debuginfo ?? 'N/A'
                ));

                // 데이터 크기 확인
                $image_size = strlen($interaction->problem_image ?? '');
                $image_size_mb = round($image_size / 1024 / 1024, 2);
                error_log(sprintf(
                    '[save_interaction.php] 이미지 데이터 크기: %sMB (%s bytes)',
                    $image_size_mb,
                    number_format($image_size)
                ));

                // MySQL max_allowed_packet 확인
                try {
                    $maxPacket = $DB->get_record_sql("SHOW VARIABLES LIKE 'max_allowed_packet'");
                    if ($maxPacket) {
                        $maxPacketMB = round($maxPacket->value / 1024 / 1024, 2);
                        error_log(sprintf(
                            '[save_interaction.php] MySQL max_allowed_packet: %sMB',
                            $maxPacketMB
                        ));

                        if ($image_size > $maxPacket->value) {
                            throw new Exception(sprintf(
                                '이미지 크기(%sMB)가 MySQL max_allowed_packet(%sMB)을 초과합니다. 서버 관리자에게 문의하세요.',
                                $image_size_mb,
                                $maxPacketMB
                            ));
                        }
                    }
                } catch (Exception $checkError) {
                    error_log('[save_interaction.php] max_allowed_packet 확인 실패: ' . $checkError->getMessage());
                }

                throw new Exception(sprintf(
                    '데이터베이스 쓰기 오류: %s (이미지 크기: %sMB)',
                    $e->getMessage(),
                    $image_size_mb
                ));
            } catch (Exception $e) {
                error_log(sprintf(
                    '[save_interaction.php] General Exception - %s (파일: %s, 라인: %d)',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));
                throw new Exception('상호작용 레코드 생성 실패: ' . $e->getMessage());
            }
            
            // 이벤트 로그 추가
            try {
                $event = new stdClass();
                $event->userid = $interaction->userid;
                $event->interactionid = $interaction_id;
                $event->event_type = 'start';
                $event->event_description = '문제 분석 시작';
                $event->timecreated = $time;

                $DB->insert_record('ktm_teaching_events', $event);
            } catch (Exception $e) {
                // 이벤트 로그 실패는 치명적이지 않으므로 계속 진행
                error_log('이벤트 로그 실패: ' . $e->getMessage());
            }

            // JSON 출력 전에 버퍼 정리 (Moodle config로부터의 잠재적 출력 제거)
            ob_clean();

            // Content-Type 헤더 재설정 (확실하게)
            header('Content-Type: application/json; charset=UTF-8', true);

            echo json_encode([
                'success' => true,
                'interactionId' => $interaction_id,
                'debug' => [
                    'studentId' => $interaction->userid,
                    'teacherId' => $interaction->teacherid,
                    'problemType' => $interaction->problem_type
                ]
            ]);

            // 버퍼 종료 및 출력
            ob_end_flush();
            exit;
            break;
            
        case 'update_solution':
            // 해설 업데이트
            $interaction_id = $input['interactionId'] ?? 0;
            $solution = $input['solution'] ?? '';
            $imageUrl = $input['imageUrl'] ?? '';
            $modificationPrompt = $input['modificationPrompt'] ?? '';
            
            if ($interaction_id) {
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                if ($interaction) {
                    // 해설이 있으면 업데이트
                    if (!empty($solution)) {
                        $interaction->solution_text = $solution;
                        $interaction->status = 'analyzing';
                    }
                    
                    // 이미지 URL이 있으면 업데이트
                    if (!empty($imageUrl)) {
                        $interaction->problem_image = $imageUrl;
                    }
                    
                    // 수정 프롬프트가 있으면 업데이트
                    if (!empty($modificationPrompt)) {
                        $interaction->modification_prompt = $modificationPrompt;
                    }
                    
                    $interaction->timemodified = $time;
                    $DB->update_record('ktm_teaching_interactions', $interaction);
                    
                    // 이벤트 로그 추가
                    $event = new stdClass();
                    $event->userid = $interaction->userid;
                    $event->interactionid = $interaction_id;
                    $event->event_type = 'analysis_complete';
                    $event->event_description = '문제 분석 완료';
                    $event->timecreated = $time;
                    
                    $DB->insert_record('ktm_teaching_events', $event);
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => '상호작용 레코드를 찾을 수 없습니다.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => '필수 파라미터가 없습니다.']);
            }
            break;
            
        case 'update_narration':
            // 나레이션 업데이트
            $interaction_id = $input['interactionId'] ?? 0;
            $narration = $input['narration'] ?? '';
            
            if ($interaction_id && $narration) {
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                if ($interaction) {
                    $interaction->narration_text = $narration;
                    $interaction->timemodified = $time;
                    
                    $DB->update_record('ktm_teaching_interactions', $interaction);
                    
                    // 이벤트 로그 추가
                    $event = new stdClass();
                    $event->userid = $interaction->userid;
                    $event->interactionid = $interaction_id;
                    $event->event_type = 'narration_complete';
                    $event->event_description = '나레이션 생성 완료';
                    $event->timecreated = $time;
                    
                    $DB->insert_record('ktm_teaching_events', $event);
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => '상호작용 레코드를 찾을 수 없습니다.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => '필수 파라미터가 없습니다.']);
            }
            break;
            
        case 'update_audio':
            // 오디오 URL 업데이트
            $interaction_id = $input['interactionId'] ?? 0;
            $audio_url = $input['audioUrl'] ?? '';
            
            if ($interaction_id && $audio_url) {
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                if ($interaction) {
                    $interaction->audio_url = $audio_url;
                    $interaction->status = 'completed';
                    $interaction->timemodified = $time;
                    
                    $DB->update_record('ktm_teaching_interactions', $interaction);
                    
                    // 이벤트 로그 추가
                    $event = new stdClass();
                    $event->userid = $interaction->userid;
                    $event->interactionid = $interaction_id;
                    $event->event_type = 'audio_complete';
                    $event->event_description = '음성 생성 완료';
                    $event->timecreated = $time;
                    
                    $DB->insert_record('ktm_teaching_events', $event);
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => '상호작용 레코드를 찾을 수 없습니다.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => '필수 파라미터가 없습니다.']);
            }
            break;
            
        case 'log_event':
            // 일반 이벤트 로그
            $event = new stdClass();
            $event->userid = $input['userId'] ?? $USER->id;
            $event->interactionid = $input['interactionId'] ?? null;
            $event->event_type = $input['eventType'] ?? 'view';
            $event->event_description = $input['description'] ?? '';
            $event->metadata = json_encode($input['metadata'] ?? []);
            $event->timecreated = $time;
            
            $DB->insert_record('ktm_teaching_events', $event);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'update_status':
            // 상태만 업데이트
            $interaction_id = $input['interactionId'] ?? 0;
            $status = $input['status'] ?? '';
            
            if ($interaction_id && $status) {
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                if ($interaction) {
                    // 상태 업데이트 전 로그
                    error_log("save_interaction.php - Updating status for ID: $interaction_id from [{$interaction->status}] to [$status]");
                    
                    $interaction->status = $status;
                    $interaction->timemodified = $time;
                    
                    $DB->update_record('ktm_teaching_interactions', $interaction);
                    
                    // 업데이트 확인
                    $updated = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                    error_log("save_interaction.php - Status after update: [{$updated->status}]");
                    
                    // 이벤트 로그 추가
                    $event = new stdClass();
                    $event->userid = $interaction->userid;
                    $event->interactionid = $interaction_id;
                    $event->event_type = 'status_update';
                    $event->event_description = '상태 변경: ' . $status;
                    $event->timecreated = $time;
                    
                    $DB->insert_record('ktm_teaching_events', $event);
                    
                    echo json_encode(['success' => true, 'new_status' => $updated->status]);
                } else {
                    echo json_encode(['success' => false, 'error' => '상호작용 레코드를 찾을 수 없습니다.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => '필수 파라미터가 없습니다.']);
            }
            break;
            
        case 'update_faq':
            // FAQ 텍스트 업데이트
            $interaction_id = $input['interactionId'] ?? 0;
            $faqtext = $input['faqtext'] ?? '';
            
            error_log(sprintf(
                '[save_interaction.php] File: %s, Line: %d, update_faq 호출됨',
                basename(__FILE__),
                __LINE__
            ));
            error_log(sprintf(
                '[save_interaction.php] 파라미터 확인 - interactionId: %s, faqtext 길이: %d, faqtext 미리보기: %s',
                $interaction_id,
                strlen($faqtext),
                substr($faqtext, 0, 100)
            ));
            error_log('[save_interaction.php] 전체 입력 데이터 키: ' . implode(', ', array_keys($input)));
            
            if (!$interaction_id || $interaction_id <= 0) {
                error_log('[save_interaction.php] 오류: interactionId가 유효하지 않습니다.');
                echo json_encode(['success' => false, 'error' => 'interactionId가 유효하지 않습니다.', 'interaction_id' => $interaction_id]);
                break;
            }
            
            if (empty($faqtext) || trim($faqtext) === '') {
                error_log('[save_interaction.php] 오류: faqtext가 비어있습니다.');
                echo json_encode(['success' => false, 'error' => 'faqtext가 비어있습니다.', 'faqtext_length' => strlen($faqtext)]);
                break;
            }
            
            try {
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                
                if (!$interaction) {
                    error_log(sprintf('[save_interaction.php] 오류: 상호작용 레코드를 찾을 수 없습니다. ID: %d', $interaction_id));
                    echo json_encode(['success' => false, 'error' => '상호작용 레코드를 찾을 수 없습니다. ID: ' . $interaction_id]);
                    break;
                }
                
                error_log(sprintf('[save_interaction.php] 레코드 찾음: ID=%d, 현재 faqtext 길이=%d', $interaction_id, strlen($interaction->faqtext ?? '')));
                
                // faqtext 필드가 없으면 추가
                try {
                    $columnExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'faqtext');
                    if (!$columnExists) {
                        error_log('[save_interaction.php] faqtext 필드가 없어서 추가 시도');
                        $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN faqtext LONGTEXT DEFAULT NULL";
                        $DB->execute($sql);
                        error_log('[save_interaction.php] faqtext 필드 추가 완료');
                    } else {
                        error_log('[save_interaction.php] faqtext 필드 존재 확인됨');
                    }
                } catch (Exception $e) {
                    error_log('[save_interaction.php] faqtext 필드 확인/추가 오류: ' . $e->getMessage());
                    // 필드 추가 실패해도 계속 진행 (이미 존재할 수 있음)
                }
                
                // 레코드 다시 조회 (필드 추가 후)
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                
                // faqtext 필드가 확실히 존재하는지 다시 확인하고 없으면 추가
                try {
                    $fieldCheckSql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_teaching_interactions LIKE 'faqtext'";
                    $fieldExists = $DB->get_record_sql($fieldCheckSql);
                    
                    if (!$fieldExists) {
                        error_log('[save_interaction.php] faqtext 필드가 없어서 추가 시도');
                        $alterSql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN faqtext LONGTEXT DEFAULT NULL";
                        $DB->execute($alterSql);
                        error_log('[save_interaction.php] faqtext 필드 추가 완료');
                    } else {
                        error_log('[save_interaction.php] faqtext 필드 존재 확인됨');
                    }
                } catch (Exception $e) {
                    error_log('[save_interaction.php] 필드 확인/추가 오류: ' . $e->getMessage());
                    // 계속 진행
                }
                
                // Moodle의 update_record는 XMLDB 스키마에 없는 필드를 무시할 수 있으므로
                // 직접 SQL UPDATE를 사용하여 faqtext 필드를 저장
                try {
                    // SQL 직접 업데이트 (faqtext 필드) - named parameter 사용
                    $sql = "UPDATE {$CFG->prefix}ktm_teaching_interactions 
                            SET faqtext = :faqtext, timemodified = :timemodified 
                            WHERE id = :id";
                    
                    $params = [
                        'faqtext' => $faqtext,
                        'timemodified' => $time,
                        'id' => $interaction_id
                    ];
                    
                    error_log(sprintf('[save_interaction.php] SQL 업데이트 시도 - ID: %d, faqtext 길이: %d', $interaction_id, strlen($faqtext)));
                    error_log('[save_interaction.php] SQL: ' . $sql);
                    error_log('[save_interaction.php] Params: ' . json_encode(array_keys($params)) . ', faqtext 길이: ' . strlen($faqtext));
                    
                    $updateResult = $DB->execute($sql, $params);
                    
                    error_log(sprintf('[save_interaction.php] SQL execute 결과: %s', $updateResult ? 'true' : 'false'));
                    
                    // 잠시 대기 후 저장 확인 (DB 반영 시간 고려)
                    usleep(100000); // 0.1초 대기
                    
                    // 저장 확인 (캐시 무시를 위해 직접 SQL 조회)
                    $checkSql = "SELECT faqtext FROM {$CFG->prefix}ktm_teaching_interactions WHERE id = :id";
                    $checkParams = ['id' => $interaction_id];
                    $savedRecord = $DB->get_record_sql($checkSql, $checkParams);
                    $savedFaqtext = $savedRecord->faqtext ?? '';
                    
                    error_log(sprintf(
                        '[save_interaction.php] File: %s, Line: %d, FAQ 텍스트 저장 확인 (ID: %d, 입력 길이: %d, 저장 확인: %d)',
                        basename(__FILE__),
                        __LINE__,
                        $interaction_id,
                        strlen($faqtext),
                        strlen($savedFaqtext)
                    ));
                    
                    if (strlen($savedFaqtext) > 0) {
                        echo json_encode(['success' => true, 'saved_length' => strlen($savedFaqtext), 'interaction_id' => $interaction_id]);
                    } else {
                        // 한 번 더 직접 SQL로 시도 (파라미터 바인딩 문제일 수 있음)
                        error_log('[save_interaction.php] 첫 번째 시도 실패, 직접 SQL로 재시도');
                        try {
                            // set_field를 사용하여 직접 필드 업데이트 시도
                            $setResult = $DB->set_field('ktm_teaching_interactions', 'faqtext', $faqtext, array('id' => $interaction_id));
                            $setResult2 = $DB->set_field('ktm_teaching_interactions', 'timemodified', $time, array('id' => $interaction_id));
                            
                            error_log('[save_interaction.php] set_field 결과 - faqtext: ' . ($setResult ? 'true' : 'false') . ', timemodified: ' . ($setResult2 ? 'true' : 'false'));
                            
                            // 다시 확인
                            $savedRecord2 = $DB->get_record_sql($checkSql, $checkParams);
                            $savedFaqtext2 = $savedRecord2->faqtext ?? '';
                            
                            if (strlen($savedFaqtext2) > 0) {
                                error_log('[save_interaction.php] set_field로 저장 성공');
                                echo json_encode(['success' => true, 'saved_length' => strlen($savedFaqtext2), 'interaction_id' => $interaction_id, 'method' => 'set_field']);
                            } else {
                                error_log('[save_interaction.php] set_field로도 실패');
                                echo json_encode([
                                    'success' => false, 
                                    'error' => 'FAQ가 저장되지 않았습니다.', 
                                    'interaction_id' => $interaction_id, 
                                    'update_result' => $updateResult,
                                    'set_field_result' => $setResult
                                ]);
                            }
                        } catch (Exception $directError) {
                            error_log('[save_interaction.php] set_field 오류: ' . $directError->getMessage());
                            echo json_encode([
                                'success' => false, 
                                'error' => 'FAQ 저장 실패: ' . $directError->getMessage(), 
                                'interaction_id' => $interaction_id
                            ]);
                        }
                    }
                } catch (Exception $sqlError) {
                    error_log(sprintf(
                        '[save_interaction.php] SQL 업데이트 오류: %s',
                        $sqlError->getMessage()
                    ));
                    error_log('[save_interaction.php] Stack trace: ' . $sqlError->getTraceAsString());
                    
                    // fallback: update_record 시도
                    try {
                        $interaction->faqtext = $faqtext;
                        $interaction->timemodified = $time;
                        $fallbackResult = $DB->update_record('ktm_teaching_interactions', $interaction);
                        error_log('[save_interaction.php] Fallback update_record 결과: ' . ($fallbackResult ? 'true' : 'false'));
                        
                        if ($fallbackResult) {
                            // fallback 후에도 확인
                            $savedRecord3 = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                            $savedFaqtext3 = $savedRecord3->faqtext ?? '';
                            
                            if (strlen($savedFaqtext3) > 0) {
                                echo json_encode(['success' => true, 'saved_length' => strlen($savedFaqtext3), 'interaction_id' => $interaction_id, 'method' => 'fallback']);
                            } else {
                                echo json_encode(['success' => false, 'error' => 'Fallback도 실패: 저장 후 확인 시 비어있음']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'error' => 'SQL 및 fallback 모두 실패: ' . $sqlError->getMessage()]);
                        }
                    } catch (Exception $fallbackError) {
                        error_log('[save_interaction.php] Fallback도 실패: ' . $fallbackError->getMessage());
                        echo json_encode(['success' => false, 'error' => '모든 저장 방법 실패: ' . $sqlError->getMessage()]);
                    }
                }
            } catch (Exception $e) {
                error_log(sprintf(
                    '[save_interaction.php] File: %s, Line: %d, update_faq 처리 중 예외 발생: %s',
                    basename(__FILE__),
                    __LINE__,
                    $e->getMessage()
                ));
                error_log('[save_interaction.php] Stack trace: ' . $e->getTraceAsString());
                echo json_encode(['success' => false, 'error' => '저장 중 오류 발생: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_type':
            // type 업데이트 (askhint -> asksolution 등)
            $interaction_id = $input['interactionId'] ?? 0;
            $type = $input['type'] ?? '';
            
            if ($interaction_id && $type) {
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                if ($interaction) {
                    $old_type = $interaction->type ?? 'null';
                    $interaction->type = $type;
                    $interaction->timemodified = $time;
                    
                    $DB->update_record('ktm_teaching_interactions', $interaction);
                    
                    error_log(sprintf(
                        '[save_interaction.php] File: %s, Line: %d, type 업데이트: ID=%d, %s -> %s',
                        basename(__FILE__),
                        __LINE__,
                        $interaction_id,
                        $old_type,
                        $type
                    ));
                    
                    // 이벤트 로그 추가
                    try {
                        $event = new stdClass();
                        $event->userid = $interaction->userid;
                        $event->interactionid = $interaction_id;
                        $event->event_type = 'type_changed';
                        $event->event_description = 'type 변경: ' . $old_type . ' -> ' . $type;
                        $event->timecreated = $time;
                        
                        $DB->insert_record('ktm_teaching_events', $event);
                    } catch (Exception $e) {
                        error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', 이벤트 로그 실패: ' . $e->getMessage());
                    }
                    
                    echo json_encode(['success' => true, 'type' => $type, 'old_type' => $old_type]);
                } else {
                    echo json_encode(['success' => false, 'error' => '상호작용 레코드를 찾을 수 없습니다. (save_interaction.php:' . __LINE__ . ')']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => '필수 파라미터가 없습니다. (interactionId: ' . $interaction_id . ', type: ' . $type . ') (save_interaction.php:' . __LINE__ . ')']);
            }
            break;
            
        case 'update_teacherid':
            // teacherid 업데이트
            $interaction_id = $input['interactionId'] ?? 0;
            $teacher_id = $input['teacherId'] ?? $USER->id;
            
            if ($interaction_id && $teacher_id) {
                $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
                if ($interaction) {
                    $interaction->teacherid = (int)$teacher_id;
                    $interaction->timemodified = $time;
                    
                    $DB->update_record('ktm_teaching_interactions', $interaction);
                    
                    // 이벤트 로그 추가
                    try {
                        $event = new stdClass();
                        $event->userid = $interaction->userid;
                        $event->interactionid = $interaction_id;
                        $event->event_type = 'teacher_assigned';
                        $event->event_description = '선생님 배정: ' . $teacher_id;
                        $event->timecreated = $time;
                        
                        $DB->insert_record('ktm_teaching_events', $event);
                    } catch (Exception $e) {
                        error_log('[save_interaction.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', 이벤트 로그 실패: ' . $e->getMessage());
                    }
                    
                    echo json_encode(['success' => true, 'teacherid' => $teacher_id]);
                } else {
                    echo json_encode(['success' => false, 'error' => '상호작용 레코드를 찾을 수 없습니다.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => '필수 파라미터가 없습니다.']);
            }
            break;
            
        case 'copy_interaction':
            // 기존 풀이 복사 (다른 풀이 요청 시 사용)
            $sourceInteractionId = $input['sourceInteractionId'] ?? 0;
            $studentId = $input['studentId'] ?? $USER->id;
            $teacherId = $input['teacherId'] ?? $USER->id;
            $newStatus = $input['newStatus'] ?? 'pending';  // 새로운 status (기본값: pending)
            $newModificationPrompt = $input['newModificationPrompt'] ?? null;  // 새로운 수정 프롬프트

            if (!$sourceInteractionId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'sourceInteractionId가 필요합니다.',
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                break;
            }

            try {
                // 원본 상호작용 가져오기
                $sourceInteraction = $DB->get_record('ktm_teaching_interactions', array('id' => $sourceInteractionId));

                if (!$sourceInteraction) {
                    echo json_encode([
                        'success' => false,
                        'error' => '원본 상호작용을 찾을 수 없습니다.',
                        'file' => __FILE__,
                        'line' => __LINE__
                    ]);
                    break;
                }

                // 새로운 상호작용 생성 (userid, id, timecreated, timemodified 제외하고 복사)
                $newInteraction = new stdClass();
                $newInteraction->userid = (int)$studentId;  // 새로운 userid 사용
                $newInteraction->teacherid = (int)$teacherId;
                $newInteraction->wboardid = 'capturequestion_userid_' . $studentId . '_' . $time;
                $newInteraction->type = $sourceInteraction->type ?? 'capture';
                $newInteraction->problem_type = $sourceInteraction->problem_type ?? '';
                $newInteraction->problem_image = $sourceInteraction->problem_image ?? '';
                $newInteraction->problem_text = $sourceInteraction->problem_text ?? '';

                // 새 요청이므로 solution과 narration은 비워둠
                $newInteraction->solution_text = '';
                $newInteraction->narration_text = '';
                $newInteraction->audio_url = '';
                $newInteraction->faqtext = '';

                // modification_prompt는 새로운 값이 제공되면 사용, 아니면 원본 사용
                $newInteraction->modification_prompt = $newModificationPrompt ?? ($sourceInteraction->modification_prompt ?? '');

                // status는 새로운 값 사용 (기본값: pending)
                $newInteraction->status = $newStatus;

                $newInteraction->timecreated = $time;  // 새로운 시간
                $newInteraction->timemodified = $time;  // 새로운 시간
                
                $newInteractionId = $DB->insert_record('ktm_teaching_interactions', $newInteraction);
                
                if (!$newInteractionId) {
                    throw new Exception('상호작용 복사 실패');
                }
                
                // 이벤트 로그 추가
                try {
                    $event = new stdClass();
                    $event->userid = $studentId;
                    $event->interactionid = $newInteractionId;
                    $event->event_type = 'copy_interaction';
                    $event->event_description = '기존 풀이 복사 (원본 ID: ' . $sourceInteractionId . ')';
                    $event->timecreated = $time;
                    
                    $DB->insert_record('ktm_teaching_events', $event);
                } catch (Exception $e) {
                    error_log('이벤트 로그 실패: ' . $e->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'interactionId' => $newInteractionId,
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
            } catch (Exception $e) {
                error_log(sprintf(
                    '[save_interaction.php] File: %s, Line: %d, copy_interaction 처리 중 예외 발생: %s',
                    basename(__FILE__),
                    __LINE__,
                    $e->getMessage()
                ));
                echo json_encode([
                    'success' => false,
                    'error' => '복사 중 오류 발생: ' . $e->getMessage(),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '알 수 없는 액션입니다.']);
    }
    
} catch (Exception $e) {
    // 상세한 오류 정보 제공
    error_log(sprintf(
        '[save_interaction.php] File: %s, Line: %d, Exception 발생: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));
    error_log('[save_interaction.php] Stack trace: ' . $e->getTraceAsString());

    // 출력 버퍼 지우기 (에러 메시지만 출력)
    ob_end_clean();

    $error_info = [
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action ?? 'unknown',
        'debug_info' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'studentId' => $input['studentId'] ?? 'not provided',
            'teacherId' => $input['teacherId'] ?? ($USER->id ?? 'unknown'),
            'php_version' => PHP_VERSION,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    // 로그에 오류 기록
    error_log('save_interaction.php 오류: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    echo json_encode($error_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 정상 종료 시 출력 버퍼 flush
if (ob_get_length()) {
    ob_end_flush();
}
?>