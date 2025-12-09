<?php
/**
 * WXsperta 버전 관리 API
 * 21개 에이전트 카드의 프로젝트 및 속성 버전 관리
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

// API 액션 파라미터
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole ? $userrole->data : 'student';

// UUID 생성 함수
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// JSON diff 계산 함수
function calculateJsonDiff($old_json, $new_json) {
    $old = json_decode($old_json, true);
    $new = json_decode($new_json, true);
    
    $diff = [
        'added' => [],
        'modified' => [],
        'deleted' => []
    ];
    
    // 간단한 diff 구현 (실제로는 더 정교한 알고리즘 필요)
    foreach ($new as $key => $value) {
        if (!isset($old[$key])) {
            $diff['added'][$key] = $value;
        } elseif ($old[$key] !== $value) {
            $diff['modified'][$key] = ['old' => $old[$key], 'new' => $value];
        }
    }
    
    foreach ($old as $key => $value) {
        if (!isset($new[$key])) {
            $diff['deleted'][$key] = $value;
        }
    }
    
    return json_encode($diff);
}

// 변경 사항 요약 생성
function generateDiffSummary($diff_json) {
    $diff = json_decode($diff_json, true);
    $summary_parts = [];
    
    if (count($diff['added']) > 0) {
        $summary_parts[] = count($diff['added']) . "개 추가";
    }
    if (count($diff['modified']) > 0) {
        $summary_parts[] = count($diff['modified']) . "개 수정";
    }
    if (count($diff['deleted']) > 0) {
        $summary_parts[] = count($diff['deleted']) . "개 삭제";
    }
    
    return implode(", ", $summary_parts);
}

try {
    switch ($action) {
        case 'commit':
            // 새 버전 커밋
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $commit_msg = $data['commit_msg'] ?? '';
            $is_milestone = $data['is_milestone'] ?? false;
            
            // 트랜잭션 시작
            $transaction = $DB->start_delegated_transaction();
            
            try {
                // 현재 상태 가져오기
                $current_project = $DB->get_record('wxsperta_projects_current', ['id' => 1]);
                $current_agents = $DB->get_records('wxsperta_agent_texts_current');
                
                // 새 버전 ID 생성
                $version_id = generateUUID();
                
                // 이전 버전 찾기
                $previous_version = $DB->get_record_sql(
                    "SELECT version_id FROM {wxsperta_projects_versions} ORDER BY created_at DESC LIMIT 1"
                );
                
                // 자동 커밋 메시지 생성 (비어있을 경우)
                if (empty($commit_msg)) {
                    $commit_msg = "프로젝트 업데이트 by " . $USER->firstname . " " . $USER->lastname;
                }
                
                // 프로젝트 버전 저장
                $project_version = new stdClass();
                $project_version->version_id = $version_id;
                $project_version->author_id = $USER->id;
                $project_version->author_name = $USER->firstname . " " . $USER->lastname;
                $project_version->commit_msg = $commit_msg;
                $project_version->project_json = $current_project->project_json;
                $project_version->parent_version_id = $previous_version ? $previous_version->version_id : null;
                $project_version->is_milestone = $is_milestone;
                
                $DB->insert_record('wxsperta_projects_versions', $project_version);
                
                // 에이전트 속성 버전 저장
                foreach ($current_agents as $agent) {
                    $agent_version = new stdClass();
                    $agent_version->version_id = $version_id;
                    $agent_version->card_id = $agent->card_id;
                    $agent_version->properties_json = $agent->properties_json;
                    
                    $DB->insert_record('wxsperta_agent_texts_versions', $agent_version);
                }
                
                // Diff 계산 및 저장 (이전 버전이 있을 경우)
                if ($previous_version) {
                    $prev_project = $DB->get_record('wxsperta_projects_versions', 
                        ['version_id' => $previous_version->version_id]);
                    
                    $diff_json = calculateJsonDiff($prev_project->project_json, $current_project->project_json);
                    
                    $diff_record = new stdClass();
                    $diff_record->from_version_id = $previous_version->version_id;
                    $diff_record->to_version_id = $version_id;
                    $diff_record->diff_json = $diff_json;
                    $diff_record->diff_summary = generateDiffSummary($diff_json);
                    
                    $DB->insert_record('wxsperta_version_diffs', $diff_record);
                }
                
                $transaction->allow_commit();
                
                echo json_encode([
                    'success' => true,
                    'version_id' => $version_id,
                    'message' => '버전이 성공적으로 커밋되었습니다.'
                ]);
                
            } catch (Exception $e) {
                $transaction->rollback($e);
                throw $e;
            }
            break;
            
        case 'versions':
            // 버전 목록 조회
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $versions = $DB->get_records_sql(
                "SELECT * FROM {wxsperta_projects_versions} 
                 ORDER BY created_at DESC 
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
            
            // 태그 정보 추가
            foreach ($versions as &$version) {
                $tags = $DB->get_records('wxsperta_version_tags', ['version_id' => $version->version_id]);
                $version->tags = array_values($tags);
            }
            
            echo json_encode([
                'success' => true,
                'versions' => array_values($versions),
                'total' => $DB->count_records('wxsperta_projects_versions')
            ]);
            break;
            
        case 'version':
            // 특정 버전 상세 조회
            $version_id = $_GET['id'] ?? '';
            if (empty($version_id)) {
                throw new Exception('Version ID required');
            }
            
            $version = $DB->get_record('wxsperta_projects_versions', ['version_id' => $version_id]);
            if (!$version) {
                throw new Exception('Version not found');
            }
            
            // 에이전트 속성들
            $agent_properties = $DB->get_records('wxsperta_agent_texts_versions', 
                ['version_id' => $version_id]);
            
            // 태그
            $tags = $DB->get_records('wxsperta_version_tags', ['version_id' => $version_id]);
            
            // 사용자 노트
            $notes = $DB->get_records('wxsperta_user_notes', ['version_id' => $version_id]);
            
            echo json_encode([
                'success' => true,
                'version' => $version,
                'agent_properties' => array_values($agent_properties),
                'tags' => array_values($tags),
                'notes' => array_values($notes)
            ]);
            break;
            
        case 'rollback':
            // 특정 버전으로 롤백
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            // 권한 확인 (교사만 가능)
            if ($role !== 'teacher') {
                throw new Exception('Permission denied. Only teachers can rollback versions.');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $target_version_id = $data['version_id'] ?? '';
            $reason = $data['reason'] ?? '';
            
            if (empty($target_version_id)) {
                throw new Exception('Target version ID required');
            }
            
            // 대상 버전 확인
            $target_version = $DB->get_record('wxsperta_projects_versions', 
                ['version_id' => $target_version_id]);
            if (!$target_version) {
                throw new Exception('Target version not found');
            }
            
            // 트랜잭션 시작
            $transaction = $DB->start_delegated_transaction();
            
            try {
                // 1. 현재 상태를 백업 (pre-rollback backup)
                $backup_version_id = generateUUID();
                
                // 현재 상태 가져오기
                $current_project = $DB->get_record('wxsperta_projects_current', ['id' => 1]);
                $current_agents = $DB->get_records('wxsperta_agent_texts_current');
                
                // 백업 버전 생성
                $backup_version = new stdClass();
                $backup_version->version_id = $backup_version_id;
                $backup_version->author_id = $USER->id;
                $backup_version->author_name = $USER->firstname . " " . $USER->lastname;
                $backup_version->commit_msg = "[자동 백업] 롤백 전 상태 (롤백 대상: $target_version_id)";
                $backup_version->project_json = $current_project->project_json;
                $backup_version->parent_version_id = null;
                $backup_version->is_milestone = false;
                
                $DB->insert_record('wxsperta_projects_versions', $backup_version);
                
                // 백업 에이전트 속성
                foreach ($current_agents as $agent) {
                    $agent_backup = new stdClass();
                    $agent_backup->version_id = $backup_version_id;
                    $agent_backup->card_id = $agent->card_id;
                    $agent_backup->properties_json = $agent->properties_json;
                    
                    $DB->insert_record('wxsperta_agent_texts_versions', $agent_backup);
                }
                
                // 2. 대상 버전으로 복원
                // 프로젝트 복원
                $DB->execute(
                    "UPDATE {wxsperta_projects_current} 
                     SET project_json = ?, last_updated_by = ?
                     WHERE id = 1",
                    [$target_version->project_json, $USER->id]
                );
                
                // 에이전트 속성 복원
                $target_agents = $DB->get_records('wxsperta_agent_texts_versions', 
                    ['version_id' => $target_version_id]);
                
                foreach ($target_agents as $agent) {
                    $DB->execute(
                        "INSERT INTO {wxsperta_agent_texts_current} 
                         (card_id, properties_json, last_updated_by) 
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                         properties_json = VALUES(properties_json),
                         last_updated_by = VALUES(last_updated_by)",
                        [$agent->card_id, $agent->properties_json, $USER->id]
                    );
                }
                
                // 3. 롤백 이력 기록
                $rollback_history = new stdClass();
                $rollback_history->from_version_id = $backup_version_id;
                $rollback_history->to_version_id = $target_version_id;
                $rollback_history->rollback_reason = $reason;
                $rollback_history->performed_by = $USER->id;
                $rollback_history->pre_rollback_backup_version_id = $backup_version_id;
                
                $DB->insert_record('wxsperta_rollback_history', $rollback_history);
                
                $transaction->allow_commit();
                
                echo json_encode([
                    'success' => true,
                    'rolled_back_to' => $target_version_id,
                    'backup_version_id' => $backup_version_id,
                    'message' => '성공적으로 롤백되었습니다.'
                ]);
                
            } catch (Exception $e) {
                $transaction->rollback($e);
                throw $e;
            }
            break;
            
        case 'diff':
            // 두 버전 간 차이점 계산
            $from_version = $_GET['from'] ?? '';
            $to_version = $_GET['to'] ?? '';
            
            if (empty($from_version) || empty($to_version)) {
                throw new Exception('Both from and to version IDs required');
            }
            
            // 캐시된 diff 확인
            $cached_diff = $DB->get_record('wxsperta_version_diffs', [
                'from_version_id' => $from_version,
                'to_version_id' => $to_version
            ]);
            
            if ($cached_diff) {
                echo json_encode([
                    'success' => true,
                    'diff' => json_decode($cached_diff->diff_json),
                    'summary' => $cached_diff->diff_summary,
                    'cached' => true
                ]);
            } else {
                // 실시간 계산
                $from_data = $DB->get_record('wxsperta_projects_versions', 
                    ['version_id' => $from_version]);
                $to_data = $DB->get_record('wxsperta_projects_versions', 
                    ['version_id' => $to_version]);
                
                if (!$from_data || !$to_data) {
                    throw new Exception('One or both versions not found');
                }
                
                $diff_json = calculateJsonDiff($from_data->project_json, $to_data->project_json);
                $summary = generateDiffSummary($diff_json);
                
                // 캐시 저장
                $diff_record = new stdClass();
                $diff_record->from_version_id = $from_version;
                $diff_record->to_version_id = $to_version;
                $diff_record->diff_json = $diff_json;
                $diff_record->diff_summary = $summary;
                
                $DB->insert_record('wxsperta_version_diffs', $diff_record);
                
                echo json_encode([
                    'success' => true,
                    'diff' => json_decode($diff_json),
                    'summary' => $summary,
                    'cached' => false
                ]);
            }
            break;
            
        case 'tag':
            // 버전에 태그 추가
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $version_id = $data['version_id'] ?? '';
            $tag_name = $data['tag_name'] ?? '';
            $tag_type = $data['tag_type'] ?? 'custom';
            $description = $data['description'] ?? '';
            
            if (empty($version_id) || empty($tag_name)) {
                throw new Exception('Version ID and tag name required');
            }
            
            // 버전 존재 확인
            if (!$DB->record_exists('wxsperta_projects_versions', ['version_id' => $version_id])) {
                throw new Exception('Version not found');
            }
            
            // 태그 중복 확인
            if ($DB->record_exists('wxsperta_version_tags', ['tag_name' => $tag_name])) {
                throw new Exception('Tag name already exists');
            }
            
            $tag = new stdClass();
            $tag->version_id = $version_id;
            $tag->tag_name = $tag_name;
            $tag->tag_type = $tag_type;
            $tag->description = $description;
            $tag->created_by = $USER->id;
            
            $tag_id = $DB->insert_record('wxsperta_version_tags', $tag);
            
            echo json_encode([
                'success' => true,
                'tag_id' => $tag_id,
                'message' => '태그가 추가되었습니다.'
            ]);
            break;
            
        case 'save_properties':
            // 에이전트 속성 저장 (자동 커밋 포함)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $card_id = $data['card_id'] ?? 0;
            $properties = $data['properties'] ?? [];
            $auto_commit = $data['auto_commit'] ?? true;
            
            if ($card_id < 1 || $card_id > 21) {
                throw new Exception('Invalid card ID');
            }
            
            // 속성 업데이트
            $record = new stdClass();
            $record->card_id = $card_id;
            $record->properties_json = json_encode($properties);
            $record->last_updated_by = $USER->id;
            
            $DB->execute(
                "INSERT INTO {wxsperta_agent_texts_current} 
                 (card_id, properties_json, last_updated_by) 
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                 properties_json = VALUES(properties_json),
                 last_updated_by = VALUES(last_updated_by)",
                [$record->card_id, $record->properties_json, $record->last_updated_by]
            );
            
            // 자동 커밋
            if ($auto_commit) {
                // 카드 정보 가져오기
                $agent_info = getAgentInfo($card_id);
                $commit_msg = sprintf(
                    "%s - 속성 수정 by %s",
                    $agent_info['name'],
                    $USER->firstname . " " . $USER->lastname
                );
                
                // 커밋 실행
                $_POST['action'] = 'commit';
                $_POST['commit_msg'] = $commit_msg;
                include __FILE__;
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => '속성이 저장되었습니다.'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// 헬퍼 함수: 에이전트 정보 가져오기
function getAgentInfo($card_id) {
    $agents = [
        1 => ['name' => '시간 수정체', 'category' => 'future_design'],
        2 => ['name' => '타임라인 합성기', 'category' => 'future_design'],
        3 => ['name' => '성장 엘리베이터', 'category' => 'future_design'],
        4 => ['name' => '성과지표 엔진', 'category' => 'future_design'],
        5 => ['name' => '동기 엔진', 'category' => 'execution'],
        6 => ['name' => 'SWOT 분석기', 'category' => 'execution'],
        7 => ['name' => '일일 사령부', 'category' => 'execution'],
        8 => ['name' => '내면 브랜딩', 'category' => 'execution'],
        9 => ['name' => '수직 탐사기', 'category' => 'execution'],
        10 => ['name' => '자원 정원사', 'category' => 'execution'],
        11 => ['name' => '실행 파이프라인', 'category' => 'execution'],
        12 => ['name' => '외부 브랜딩', 'category' => 'branding'],
        13 => ['name' => '성장 트리거', 'category' => 'branding'],
        14 => ['name' => '경쟁 생존 전략가', 'category' => 'branding'],
        15 => ['name' => '시간수정체 CEO', 'category' => 'knowledge_management'],
        16 => ['name' => 'AI 정원사', 'category' => 'knowledge_management'],
        17 => ['name' => '신경망 설계사', 'category' => 'knowledge_management'],
        18 => ['name' => '정보 허브', 'category' => 'knowledge_management'],
        19 => ['name' => '지식 연결망', 'category' => 'knowledge_management'],
        20 => ['name' => '지식 수정체', 'category' => 'knowledge_management'],
        21 => ['name' => '유연한 백본', 'category' => 'knowledge_management']
    ];
    
    return $agents[$card_id] ?? ['name' => 'Unknown', 'category' => 'unknown'];
}
?>