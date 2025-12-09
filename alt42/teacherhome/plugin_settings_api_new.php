<?php
/**
 * 새로운 플러그인 설정 API (정규화된 테이블 구조 사용)
 * 개별 필드로 분리된 카드 설정 관리
 */

require_once __DIR__ . '/plugin_db_config.php';

class PluginSettingsAPINew {
    private $db;
    
    public function __construct() {
        try {
            global $dsn, $username, $password;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $this->db = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die(json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    
    /**
     * 카드 플러그인 설정 저장 (새로운 구조)
     */
    public function saveCardPluginSettingNew($user_id, $category, $card_title, $card_index, $plugin_id, $config, $card_id = null) {
        try {
            // user_id를 정수로 변환
            $user_id = intval($user_id);
            
            // 기본값 설정
            $data = [
                ':user_id' => $user_id,
                ':category' => $category,
                ':card_title' => $card_title,
                ':card_index' => intval($card_index),
                ':plugin_id' => $plugin_id,
                
                // 공통 필드
                ':plugin_name' => $config['plugin_name'] ?? null,
                ':card_description' => $config['card_description'] ?? $config['description'] ?? null,
                
                // internal_link
                ':internal_url' => $config['internal_url'] ?? null,
                
                // external_link
                ':external_url' => $config['external_url'] ?? null,
                ':open_new_tab' => isset($config['open_new_tab']) ? (int)$config['open_new_tab'] : 0,
                
                // send_message
                ':message_content' => $config['message_content'] ?? null,
                ':message_type' => $config['message_type'] ?? null,
                
                // agent
                ':agent_type' => $config['agent_type'] ?? null,
                ':agent_code' => $config['agent_code'] ?? null,
                ':agent_url' => $config['agent_url'] ?? null,
                ':agent_prompt' => $config['agent_prompt'] ?? null,
                ':agent_parameters' => isset($config['agent_parameters']) ? 
                    (is_string($config['agent_parameters']) ? $config['agent_parameters'] : json_encode($config['agent_parameters'])) : null,
                ':agent_description' => $config['agent_description'] ?? null,
                
                // agent_config
                ':agent_config_title' => $config['agent_config']['title'] ?? null,
                ':agent_config_description' => $config['agent_config']['description'] ?? null,
                ':agent_config_details' => isset($config['agent_config']['details']) ? 
                    (is_string($config['agent_config']['details']) ? $config['agent_config']['details'] : json_encode($config['agent_config']['details'])) : null,
                ':agent_config_action' => $config['agent_config']['action'] ?? null,
                
                // 추가 설정
                ':extra_config' => null,
                
                // 시스템 필드
                ':is_active' => $config['is_active'] ?? 1,
                ':display_order' => $config['display_order'] ?? 0
            ];
            
            // ID가 있으면 업데이트, 없으면 삽입
            if ($card_id) {
                // 업데이트
                $sql = "UPDATE mdl_alt42DB_card_plugin_settings SET 
                        plugin_name = :plugin_name,
                        card_description = :card_description,
                        internal_url = :internal_url,
                        external_url = :external_url,
                        open_new_tab = :open_new_tab,
                        message_content = :message_content,
                        message_type = :message_type,
                        agent_type = :agent_type,
                        agent_code = :agent_code,
                        agent_url = :agent_url,
                        agent_prompt = :agent_prompt,
                        agent_parameters = :agent_parameters,
                        agent_description = :agent_description,
                        agent_config_title = :agent_config_title,
                        agent_config_description = :agent_config_description,
                        agent_config_details = :agent_config_details,
                        agent_config_action = :agent_config_action,
                        extra_config = :extra_config,
                        is_active = :is_active,
                        display_order = :display_order,
                        timemodified = UNIX_TIMESTAMP()
                        WHERE id = :id AND user_id = :user_id";
                
                $data[':id'] = $card_id;
                unset($data[':category']);
                unset($data[':card_title']);
                unset($data[':card_index']);
                unset($data[':plugin_id']);
            } else {
                // 삽입
                $sql = "INSERT INTO mdl_alt42DB_card_plugin_settings (
                        user_id, category, card_title, card_index, plugin_id,
                        plugin_name, card_description,
                        internal_url, external_url, open_new_tab,
                        message_content, message_type,
                        agent_type, agent_code, agent_url, agent_prompt, agent_parameters, agent_description,
                        agent_config_title, agent_config_description, agent_config_details, agent_config_action,
                        extra_config,
                        is_active, display_order, timecreated, timemodified
                    ) VALUES (
                        :user_id, :category, :card_title, :card_index, :plugin_id,
                        :plugin_name, :card_description,
                        :internal_url, :external_url, :open_new_tab,
                        :message_content, :message_type,
                        :agent_type, :agent_code, :agent_url, :agent_prompt, :agent_parameters, :agent_description,
                        :agent_config_title, :agent_config_description, :agent_config_details, :agent_config_action,
                        :extra_config,
                        :is_active, :display_order, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
                    )
                    ON DUPLICATE KEY UPDATE
                        plugin_name = VALUES(plugin_name),
                        card_description = VALUES(card_description),
                        internal_url = VALUES(internal_url),
                        external_url = VALUES(external_url),
                        open_new_tab = VALUES(open_new_tab),
                        message_content = VALUES(message_content),
                        message_type = VALUES(message_type),
                        agent_type = VALUES(agent_type),
                        agent_code = VALUES(agent_code),
                        agent_url = VALUES(agent_url),
                        agent_prompt = VALUES(agent_prompt),
                        agent_parameters = VALUES(agent_parameters),
                        agent_description = VALUES(agent_description),
                        agent_config_title = VALUES(agent_config_title),
                        agent_config_description = VALUES(agent_config_description),
                        agent_config_details = VALUES(agent_config_details),
                        agent_config_action = VALUES(agent_config_action),
                        extra_config = VALUES(extra_config),
                        is_active = VALUES(is_active),
                        display_order = VALUES(display_order),
                        timemodified = UNIX_TIMESTAMP()";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            
            return [
                'success' => true,
                'message' => '카드 플러그인 설정이 저장되었습니다.',
                'id' => $card_id ?: $this->db->lastInsertId()
            ];
            
        } catch (Exception $e) {
            error_log("saveCardPluginSettingNew error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 카드 플러그인 설정 조회 (새로운 구조)
     */
    public function getCardPluginSettingsNew($user_id, $category = null) {
        try {
            $sql = "SELECT 
                    id,
                    user_id,
                    category,
                    card_title,
                    card_index,
                    plugin_id,
                    plugin_name,
                    card_description,
                    internal_url,
                    external_url,
                    open_new_tab,
                    message_content,
                    message_type,
                    agent_type,
                    agent_code,
                    agent_url,
                    agent_prompt,
                    agent_parameters,
                    agent_description,
                    agent_config_title,
                    agent_config_description,
                    agent_config_details,
                    agent_config_action,
                    extra_config,
                    is_active,
                    display_order,
                    timecreated,
                    timemodified
                FROM mdl_alt42DB_card_plugin_settings 
                WHERE user_id = :user_id";
                
            $params = [':user_id' => $user_id];
            
            if ($category) {
                $sql .= " AND category = :category";
                $params[':category'] = $category;
            }
            
            $sql .= " ORDER BY display_order ASC, id ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 결과를 기존 형식으로 변환 (호환성 유지)
            foreach ($results as &$row) {
                // plugin_config 객체 재구성
                $row['plugin_config'] = [
                    'plugin_name' => $row['plugin_name'],
                    'card_description' => $row['card_description']
                ];
                
                // 플러그인 타입별 필드 추가
                switch ($row['plugin_id']) {
                    case 'internal_link':
                        $row['plugin_config']['internal_url'] = $row['internal_url'];
                        $row['plugin_config']['open_new_tab'] = (bool)$row['open_new_tab'];
                        break;
                        
                    case 'external_link':
                        $row['plugin_config']['external_url'] = $row['external_url'];
                        $row['plugin_config']['open_new_tab'] = (bool)$row['open_new_tab'];
                        break;
                        
                    case 'send_message':
                        $row['plugin_config']['message_content'] = $row['message_content'];
                        $row['plugin_config']['message_type'] = $row['message_type'];
                        break;
                        
                    case 'agent':
                        $row['plugin_config']['agent_type'] = $row['agent_type'];
                        $row['plugin_config']['agent_code'] = $row['agent_code'];
                        $row['plugin_config']['agent_url'] = $row['agent_url'];
                        $row['plugin_config']['agent_prompt'] = $row['agent_prompt'];
                        $row['plugin_config']['agent_parameters'] = $row['agent_parameters'] ? 
                            json_decode($row['agent_parameters'], true) : null;
                        $row['plugin_config']['agent_description'] = $row['agent_description'];
                        
                        // agent_config 재구성
                        if ($row['agent_config_title'] || $row['agent_config_description'] || 
                            $row['agent_config_details'] || $row['agent_config_action']) {
                            $row['plugin_config']['agent_config'] = [
                                'title' => $row['agent_config_title'],
                                'description' => $row['agent_config_description'],
                                'details' => $row['agent_config_details'] ? 
                                    json_decode($row['agent_config_details'], true) : null,
                                'action' => $row['agent_config_action']
                            ];
                        }
                        break;
                }
                
                // null 값 제거
                $row['plugin_config'] = array_filter($row['plugin_config'], function($value) {
                    return $value !== null;
                });
                
                // 개별 필드 제거 (plugin_config에 포함되므로)
                unset($row['plugin_name']);
                unset($row['card_description']);
                unset($row['internal_url']);
                unset($row['external_url']);
                unset($row['open_new_tab']);
                unset($row['message_content']);
                unset($row['message_type']);
                unset($row['agent_type']);
                unset($row['agent_code']);
                unset($row['agent_url']);
                unset($row['agent_prompt']);
                unset($row['agent_parameters']);
                unset($row['agent_description']);
                unset($row['agent_config_title']);
                unset($row['agent_config_description']);
                unset($row['agent_config_details']);
                unset($row['agent_config_action']);
                unset($row['extra_config']);
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
     * 카드 플러그인 설정 삭제 (새로운 구조)
     */
    public function deleteCardPluginSettingNew($user_id, $category, $card_title, $card_id = null, $card_index = null) {
        try {
            // user_id를 정수로 변환
            $user_id = intval($user_id);
            
            // ID가 있으면 ID로 삭제, 없으면 card_index로 삭제
            if ($card_id) {
                $sql = "DELETE FROM mdl_alt42DB_card_plugin_settings 
                       WHERE id = ? AND user_id = ? AND category = ? AND card_title = ?";
                $params = [$card_id, $user_id, $category, $card_title];
            } else {
                $sql = "DELETE FROM mdl_alt42DB_card_plugin_settings 
                       WHERE card_index = ? AND user_id = ? AND category = ? AND card_title = ?";
                $params = [$card_index, $user_id, $category, $card_title];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => '카드가 성공적으로 삭제되었습니다.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => '삭제할 카드를 찾을 수 없습니다.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("deleteCardPluginSettingNew error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// API 엔드포인트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $api = new PluginSettingsAPINew();
    $response = ['success' => false, 'error' => 'Invalid action'];
    
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'saveCardSetting':
            $user_id = $_REQUEST['user_id'] ?? 0;
            $category = $_REQUEST['category'] ?? '';
            $card_title = $_REQUEST['card_title'] ?? '';
            $card_index = $_REQUEST['card_index'] ?? 0;
            $plugin_id = $_REQUEST['plugin_id'] ?? '';
            $config = $_REQUEST['config'] ?? [];
            $card_id = $_REQUEST['card_id'] ?? null;
            
            if (is_string($config)) {
                $config = json_decode($config, true);
            }
            
            $response = $api->saveCardPluginSettingNew($user_id, $category, $card_title, $card_index, $plugin_id, $config, $card_id);
            break;
            
        case 'getCardSettings':
            $user_id = $_REQUEST['user_id'] ?? 0;
            $category = $_REQUEST['category'] ?? null;
            
            $response = $api->getCardPluginSettingsNew($user_id, $category);
            break;
            
        case 'deleteCardSetting':
            $user_id = $_REQUEST['user_id'] ?? 0;
            $category = $_REQUEST['category'] ?? '';
            $card_title = $_REQUEST['card_title'] ?? '';
            $card_id = $_REQUEST['card_id'] ?? null;
            $card_index = $_REQUEST['card_index'] ?? null;
            
            $response = $api->deleteCardPluginSettingNew($user_id, $category, $card_title, $card_id, $card_index);
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>