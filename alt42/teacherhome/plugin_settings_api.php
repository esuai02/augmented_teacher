<?php
/**
 * KTM 코파일럿 플러그인 설정 API
 * 작성일: 2024-12-31
 * 설명: teacherhome/index.html에서 사용하는 플러그인 세부설정을 관리하는 API
 */

class KTMPluginSettingsAPI {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * 플러그인 타입 목록 조회
     */
    public function getPluginTypes($active_only = true) {
        try {
            $sql = "SELECT * FROM mdl_alt42DB_plugin_types";
            if ($active_only) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY plugin_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 사용자별 플러그인 설정 저장/업데이트
     */
    public function saveUserPluginSetting($user_id, $plugin_id, $setting_name, $setting_value, $category = null) {
        try {
            // 기존 값 조회 (히스토리 저장용)
            $check_sql = "SELECT setting_value FROM mdl_alt42DB_user_plugin_settings 
                         WHERE user_id = ? AND plugin_id = ? AND setting_name = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->execute([$user_id, $plugin_id, $setting_name]);
            $old_value = $check_stmt->fetchColumn();
            
            // 새로운 플러그인인지 확인
            $is_new = ($old_value === false);
            
            $sql = "INSERT INTO mdl_alt42DB_user_plugin_settings 
                    (user_id, plugin_id, setting_name, setting_value, category, timecreated, timemodified)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    category = VALUES(category),
                    timemodified = VALUES(timemodified)";
            
            $current_time = time();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user_id, 
                $plugin_id, 
                $setting_name, 
                json_encode($setting_value),
                $category,
                $current_time,
                $current_time
            ]);
            
            // 변경 히스토리 저장
            if ($is_new) {
                // 새로운 플러그인 생성
                $this->saveSettingHistory(
                    $user_id,
                    $plugin_id,
                    'user_setting',
                    $setting_name,
                    null,
                    $setting_value,
                    'New plugin created'
                );
            } elseif ($old_value !== json_encode($setting_value)) {
                // 기존 플러그인 수정
                $this->saveSettingHistory(
                    $user_id,
                    $plugin_id,
                    'user_setting',
                    $setting_name,
                    json_decode($old_value, true),
                    $setting_value,
                    'Plugin setting updated'
                );
            }
            
            return [
                'success' => true,
                'message' => '사용자 플러그인 설정이 저장되었습니다.',
                'is_new' => $is_new
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 카드별 플러그인 설정 저장/업데이트
     */
    public function saveCardPluginSetting($user_id, $category, $card_title, $card_index, $plugin_id, $plugin_config, $display_order = 0) {
        try {
            // 먼저 plugin_id가 mdl_alt42DB_plugin_types 테이블에 존재하는지 확인
            $plugin_check_sql = "SELECT COUNT(*) FROM mdl_alt42DB_plugin_types WHERE plugin_id = ?";
            $plugin_check_stmt = $this->db->prepare($plugin_check_sql);
            $plugin_check_stmt->execute([$plugin_id]);
            $plugin_exists = $plugin_check_stmt->fetchColumn() > 0;
            
            if (!$plugin_exists) {
                // 플러그인 타입이 존재하지 않으면 생성
                $this->ensurePluginTypesExist();
                
                // 다시 확인
                $plugin_check_stmt->execute([$plugin_id]);
                $plugin_exists = $plugin_check_stmt->fetchColumn() > 0;
                
                if (!$plugin_exists) {
                    throw new Exception("Plugin type '$plugin_id' does not exist in mdl_alt42DB_plugin_types");
                }
            }
            
            // 기존 값 조회 (히스토리 저장용)
            $check_sql = "SELECT plugin_config FROM mdl_alt42DB_card_plugin_settings 
                         WHERE user_id = ? AND category = ? AND card_title = ? AND plugin_id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->execute([$user_id, $category, $card_title, $plugin_id]);
            $old_value = $check_stmt->fetchColumn();
            
            // 새로운 플러그인인지 확인
            $is_new = ($old_value === false);
            
            $sql = "INSERT INTO mdl_alt42DB_card_plugin_settings 
                    (user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    plugin_config = VALUES(plugin_config),
                    card_index = VALUES(card_index),
                    display_order = VALUES(display_order),
                    timemodified = VALUES(timemodified)";
            
            $current_time = time();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user_id,
                $category,
                $card_title,
                $card_index,
                $plugin_id,
                json_encode($plugin_config),
                $display_order,
                $current_time,
                $current_time
            ]);
            
            // 변경 히스토리 저장
            if ($is_new) {
                // 새로운 카드 플러그인 생성
                $this->saveSettingHistory(
                    $user_id,
                    $plugin_id,
                    'card_setting',
                    $card_title,
                    null,
                    $plugin_config,
                    'New card plugin created'
                );
            } elseif ($old_value !== json_encode($plugin_config)) {
                // 기존 카드 플러그인 수정
                $this->saveSettingHistory(
                    $user_id,
                    $plugin_id,
                    'card_setting',
                    $card_title,
                    json_decode($old_value, true),
                    $plugin_config,
                    'Card plugin configuration updated'
                );
            }
            
            return [
                'success' => true,
                'message' => '카드 플러그인 설정이 저장되었습니다.',
                'is_new' => $is_new
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 사용자별 플러그인 설정 조회
     */
    public function getUserPluginSettings($user_id, $plugin_id = null, $category = null) {
        try {
            $sql = "SELECT ups.*, pt.plugin_title, pt.plugin_icon, pt.plugin_description 
                    FROM mdl_alt42DB_user_plugin_settings ups
                    LEFT JOIN mdl_alt42DB_plugin_types pt ON ups.plugin_id = pt.plugin_id
                    WHERE ups.user_id = ? AND ups.is_enabled = 1";
            
            $params = [$user_id];
            
            if ($plugin_id) {
                $sql .= " AND ups.plugin_id = ?";
                $params[] = $plugin_id;
            }
            
            if ($category) {
                $sql .= " AND ups.category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY ups.plugin_id, ups.setting_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON 디코딩
            foreach ($results as &$row) {
                $row['setting_value'] = json_decode($row['setting_value'], true);
            }
            
            return [
                'success' => true,
                'data' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 카드별 플러그인 설정 조회
     */
    public function getCardPluginSettings($user_id, $category = null, $card_title = null) {
        try {
            $sql = "SELECT cps.*, pt.plugin_title, pt.plugin_icon, pt.plugin_description 
                    FROM mdl_alt42DB_card_plugin_settings cps
                    LEFT JOIN mdl_alt42DB_plugin_types pt ON cps.plugin_id = pt.plugin_id
                    WHERE cps.user_id = ? AND cps.is_active = 1";
            
            $params = [$user_id];
            
            if ($category) {
                $sql .= " AND cps.category = ?";
                $params[] = $category;
            }
            
            if ($card_title) {
                $sql .= " AND cps.card_title = ?";
                $params[] = $card_title;
            }
            
            $sql .= " ORDER BY cps.category, cps.card_title, cps.display_order";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON 디코딩
            foreach ($results as &$row) {
                $row['plugin_config'] = json_decode($row['plugin_config'], true);
            }
            
            return [
                'success' => true,
                'data' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 설정 삭제
     */
    public function deleteUserPluginSetting($user_id, $plugin_id, $setting_name, $category = null) {
        try {
            // 삭제 전 현재 값 조회 (히스토리 저장용)
            $check_sql = "SELECT setting_value FROM mdl_alt42DB_user_plugin_settings 
                         WHERE user_id = ? AND plugin_id = ? AND setting_name = ?";
            if ($category) {
                $check_sql .= " AND category = ?";
            }
            
            $check_params = [$user_id, $plugin_id, $setting_name];
            if ($category) {
                $check_params[] = $category;
            }
            
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->execute($check_params);
            $old_value = $check_stmt->fetchColumn();
            
            // 설정 삭제
            $sql = "DELETE FROM mdl_alt42DB_user_plugin_settings 
                    WHERE user_id = ? AND plugin_id = ? AND setting_name = ?";
            
            $params = [$user_id, $plugin_id, $setting_name];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            // 삭제 히스토리 저장
            if ($old_value !== false) {
                $this->saveSettingHistory(
                    $user_id,
                    $plugin_id,
                    'user_setting',
                    $setting_name,
                    json_decode($old_value, true),
                    null,
                    'Plugin setting deleted'
                );
            }
            
            return [
                'success' => true,
                'message' => '사용자 플러그인 설정이 삭제되었습니다.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 카드 플러그인 설정 삭제
     */
    public function deleteCardPluginSetting($user_id, $category, $card_title, $plugin_id) {
        try {
            // 삭제 전 현재 값 조회 (히스토리 저장용)
            $check_sql = "SELECT plugin_config FROM mdl_alt42DB_card_plugin_settings 
                         WHERE user_id = ? AND category = ? AND card_title = ? AND plugin_id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->execute([$user_id, $category, $card_title, $plugin_id]);
            $old_value = $check_stmt->fetchColumn();
            
            // 설정 삭제
            $sql = "DELETE FROM mdl_alt42DB_card_plugin_settings 
                    WHERE user_id = ? AND category = ? AND card_title = ? AND plugin_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $category, $card_title, $plugin_id]);
            
            // 삭제 히스토리 저장
            if ($old_value !== false) {
                $this->saveSettingHistory(
                    $user_id,
                    $plugin_id,
                    'card_setting',
                    $card_title,
                    json_decode($old_value, true),
                    null,
                    'Card plugin deleted'
                );
            }
            
            return [
                'success' => true,
                'message' => '카드 플러그인 설정이 삭제되었습니다.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 카드 설정 삭제 (ID 기반)
     */
    public function deleteCardPluginSettingById($user_id, $category, $card_title, $card_id, $card_index) {
        try {
            // ID가 있으면 ID로 조회, 없으면 card_index로 조회
            if ($card_id) {
                $check_sql = "SELECT id, plugin_id, plugin_config FROM mdl_alt42DB_card_plugin_settings 
                             WHERE id = ? AND user_id = ? AND category = ? AND card_title = ?";
                $check_stmt = $this->db->prepare($check_sql);
                $check_stmt->execute([$card_id, $user_id, $category, $card_title]);
            } else {
                $check_sql = "SELECT id, plugin_id, plugin_config FROM mdl_alt42DB_card_plugin_settings 
                             WHERE card_index = ? AND user_id = ? AND category = ? AND card_title = ?";
                $check_stmt = $this->db->prepare($check_sql);
                $check_stmt->execute([$card_index, $user_id, $category, $card_title]);
            }
            
            $card = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$card) {
                return [
                    'success' => false,
                    'error' => '삭제할 카드를 찾을 수 없습니다.'
                ];
            }
            
            // 삭제 히스토리용 데이터
            $old_value = $card['plugin_config'];
            $plugin_id = $card['plugin_id'];
            $actual_id = $card['id'];
            
            // 카드 삭제
            $delete_sql = "DELETE FROM mdl_alt42DB_card_plugin_settings WHERE id = ?";
            $delete_stmt = $this->db->prepare($delete_sql);
            $delete_stmt->execute([$actual_id]);
            
            // 삭제 히스토리 저장
            $this->saveSettingHistory(
                $user_id,
                $plugin_id,
                'card_setting',
                $card_title,
                json_decode($old_value, true),
                null,
                'Card plugin deleted by ID'
            );
            
            return [
                'success' => true,
                'message' => '카드가 성공적으로 삭제되었습니다.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 설정 히스토리 저장
     */
    public function saveSettingHistory($user_id, $plugin_id, $setting_type, $reference_id, $old_value, $new_value, $change_reason = null) {
        try {
            $sql = "INSERT INTO mdl_alt42DB_plugin_settings_history 
                    (user_id, plugin_id, setting_type, reference_id, old_value, new_value, change_reason, timecreated)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user_id,
                $plugin_id,
                $setting_type,
                $reference_id,
                json_encode($old_value),
                json_encode($new_value),
                $change_reason,
                time()
            ]);
            
            return [
                'success' => true,
                'message' => '설정 히스토리가 저장되었습니다.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 설정 히스토리 조회
     */
    public function getSettingHistory($user_id, $plugin_id = null, $limit = 50) {
        try {
            $sql = "SELECT psh.*, pt.plugin_title 
                    FROM mdl_alt42DB_plugin_settings_history psh
                    LEFT JOIN mdl_alt42DB_plugin_types pt ON psh.plugin_id = pt.plugin_id
                    WHERE psh.user_id = ?";
            
            $params = [$user_id];
            
            if ($plugin_id) {
                $sql .= " AND psh.plugin_id = ?";
                $params[] = $plugin_id;
            }
            
            $sql .= " ORDER BY psh.timecreated DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON 디코딩
            foreach ($results as &$row) {
                $row['old_value'] = json_decode($row['old_value'], true);
                $row['new_value'] = json_decode($row['new_value'], true);
            }
            
            return [
                'success' => true,
                'data' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 설정 활성화/비활성화
     */
    public function togglePluginSetting($table_type, $id, $is_active) {
        try {
            $table_name = ($table_type === 'user') ? 'mdl_alt42DB_user_plugin_settings' : 'mdl_alt42DB_card_plugin_settings';
            $column_name = ($table_type === 'user') ? 'is_enabled' : 'is_active';
            
            $sql = "UPDATE {$table_name} SET {$column_name} = ?, timemodified = ? WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$is_active, time(), $id]);
            
            return [
                'success' => true,
                'message' => '플러그인 설정 상태가 변경되었습니다.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 설정 통계 조회
     */
    public function getPluginUsageStats($plugin_id = null) {
        try {
            $sql = "SELECT 
                        pt.plugin_id,
                        pt.plugin_title,
                        COUNT(DISTINCT ups.user_id) as user_count,
                        COUNT(ups.id) as user_settings_count,
                        COUNT(DISTINCT cps.user_id) as card_user_count,
                        COUNT(cps.id) as card_settings_count
                    FROM mdl_alt42DB_plugin_types pt
                    LEFT JOIN mdl_alt42DB_user_plugin_settings ups ON pt.plugin_id = ups.plugin_id AND ups.is_enabled = 1
                    LEFT JOIN mdl_alt42DB_card_plugin_settings cps ON pt.plugin_id = cps.plugin_id AND cps.is_active = 1
                    WHERE pt.is_active = 1";
            
            $params = [];
            
            if ($plugin_id) {
                $sql .= " AND pt.plugin_id = ?";
                $params[] = $plugin_id;
            }
            
            $sql .= " GROUP BY pt.plugin_id, pt.plugin_title ORDER BY pt.plugin_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 실행 통계 저장/업데이트
     */
    public function updatePluginUsageStats($user_id, $plugin_id, $category = null, $card_title = null, $execution_data = null) {
        try {
            $sql = "INSERT INTO mdl_alt42DB_plugin_usage_stats 
                    (user_id, plugin_id, category, card_title, execution_count, last_execution, execution_data, timecreated, timemodified)
                    VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    execution_count = execution_count + 1,
                    last_execution = VALUES(last_execution),
                    execution_data = VALUES(execution_data),
                    timemodified = VALUES(timemodified)";
            
            $current_time = time();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user_id,
                $plugin_id,
                $category,
                $card_title,
                $current_time,
                json_encode($execution_data),
                $current_time,
                $current_time
            ]);
            
            return [
                'success' => true,
                'message' => '플러그인 사용 통계가 업데이트되었습니다.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 사용 통계 조회
     */
    public function getPluginUsageStatsDetailed($user_id = null, $plugin_id = null, $category = null, $limit = 100) {
        try {
            $sql = "SELECT pus.*, pt.plugin_title, pt.plugin_icon, pt.plugin_description 
                    FROM mdl_alt42DB_plugin_usage_stats pus
                    LEFT JOIN mdl_alt42DB_plugin_types pt ON pus.plugin_id = pt.plugin_id
                    WHERE 1=1";
            
            $params = [];
            
            if ($user_id) {
                $sql .= " AND pus.user_id = ?";
                $params[] = $user_id;
            }
            
            if ($plugin_id) {
                $sql .= " AND pus.plugin_id = ?";
                $params[] = $plugin_id;
            }
            
            if ($category) {
                $sql .= " AND pus.category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY pus.last_execution DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON 디코딩
            foreach ($results as &$row) {
                $row['execution_data'] = json_decode($row['execution_data'], true);
            }
            
            return [
                'success' => true,
                'data' => $results
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 플러그인 타입이 존재하는지 확인하고 없으면 생성
     */
    private function ensurePluginTypesExist() {
        try {
            // 기본 플러그인 타입 정의
            $defaultPluginTypes = [
                ['internal_link', '내부링크 열기', '🔗', '플랫폼 내 다른 페이지로 이동'],
                ['external_link', '외부링크 열기', '🌐', '외부 사이트나 도구 연결'],
                ['send_message', '메시지 발송', '📨', '사용자에게 자동 메시지 전송']
            ];
            
            $insertSql = "INSERT IGNORE INTO mdl_alt42DB_plugin_types 
                         (plugin_id, plugin_title, plugin_icon, plugin_description, timecreated, timemodified) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($insertSql);
            $current_time = time();
            
            foreach ($defaultPluginTypes as $type) {
                $stmt->execute([
                    $type[0], // plugin_id
                    $type[1], // plugin_title
                    $type[2], // plugin_icon
                    $type[3], // plugin_description
                    $current_time,
                    $current_time
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            // 오류 발생 시 로그에 기록하고 계속 진행
            error_log("Failed to ensure plugin types exist: " . $e->getMessage());
            return false;
        }
    }
    
}

// API 엔드포인트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // 데이터베이스 연결 (이 부분은 프로젝트의 DB 설정에 맞게 수정)
        $pdo = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $api = new KTMPluginSettingsAPI($pdo);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'get_plugin_types':
                echo json_encode($api->getPluginTypes());
                break;
                
            case 'save_user_setting':
                echo json_encode($api->saveUserPluginSetting(
                    $input['user_id'],
                    $input['plugin_id'],
                    $input['setting_name'],
                    $input['setting_value'],
                    $input['category'] ?? null
                ));
                break;
                
            case 'save_card_setting':
                echo json_encode($api->saveCardPluginSetting(
                    $input['user_id'],
                    $input['category'],
                    $input['card_title'],
                    $input['card_index'],
                    $input['plugin_id'],
                    $input['plugin_config'],
                    $input['display_order'] ?? 0
                ));
                break;
                
            case 'get_user_settings':
                echo json_encode($api->getUserPluginSettings(
                    $input['user_id'],
                    $input['plugin_id'] ?? null,
                    $input['category'] ?? null
                ));
                break;
                
            case 'get_card_settings':
                echo json_encode($api->getCardPluginSettings(
                    $input['user_id'],
                    $input['category'] ?? null,
                    $input['card_title'] ?? null
                ));
                break;
                
            case 'delete_user_setting':
                echo json_encode($api->deleteUserPluginSetting(
                    $input['user_id'],
                    $input['plugin_id'],
                    $input['setting_name'],
                    $input['category'] ?? null
                ));
                break;
                
            case 'delete_card_setting':
                echo json_encode($api->deleteCardPluginSetting(
                    $input['user_id'],
                    $input['category'],
                    $input['card_title'],
                    $input['plugin_id']
                ));
                break;
                
            case 'delete_card_setting_by_id':
                echo json_encode($api->deleteCardPluginSettingById(
                    $input['user_id'],
                    $input['category'],
                    $input['card_title'],
                    $input['card_id'] ?? null,
                    $input['card_index']
                ));
                break;
                
            case 'get_usage_stats':
                echo json_encode($api->getPluginUsageStats($input['plugin_id'] ?? null));
                break;
                
            case 'update_usage_stats':
                echo json_encode($api->updatePluginUsageStats(
                    $input['user_id'],
                    $input['plugin_id'],
                    $input['category'] ?? null,
                    $input['card_title'] ?? null,
                    $input['execution_data'] ?? null
                ));
                break;
                
            case 'get_usage_stats_detailed':
                echo json_encode($api->getPluginUsageStatsDetailed(
                    $input['user_id'] ?? null,
                    $input['plugin_id'] ?? null,
                    $input['category'] ?? null,
                    $input['limit'] ?? 100
                ));
                break;
                
            case 'get_setting_history':
                echo json_encode($api->getSettingHistory(
                    $input['user_id'],
                    $input['plugin_id'] ?? null,
                    $input['limit'] ?? 50
                ));
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'error' => '유효하지 않은 액션입니다.'
                ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?> 