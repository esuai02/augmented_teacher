<?php
/**
 * KTM 코파일럿 플러그인 설정 API - 개선된 버전
 * 작성일: 2024-12-31
 * 
 * 주요 개선사항:
 * - 데이터베이스 계층 분리
 * - 입력 검증 및 sanitization 강화
 * - 에러 처리 개선
 * - 트랜잭션 지원
 * - 성능 최적화
 * - 보안 강화
 */

// 데이터베이스 연결 관리자
class DatabaseManager {
    private static $instance = null;
    private $connection = null;
    private $transactionCount = 0;
    
    private function __construct() {
        // Singleton pattern
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection($config) {
        if ($this->connection === null) {
            try {
                $dsn = sprintf(
                    "%s:host=%s;dbname=%s;charset=%s",
                    $config['type'],
                    $config['host'],
                    $config['name'],
                    $config['charset']
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                ];
                
                $this->connection = new PDO(
                    $dsn,
                    $config['user'],
                    $config['pass'],
                    $options
                );
            } catch (PDOException $e) {
                throw new DatabaseException("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->connection;
    }
    
    public function beginTransaction() {
        if ($this->transactionCount === 0) {
            $this->connection->beginTransaction();
        }
        $this->transactionCount++;
    }
    
    public function commit() {
        if ($this->transactionCount === 1) {
            $this->connection->commit();
        }
        $this->transactionCount--;
    }
    
    public function rollback() {
        if ($this->transactionCount === 1) {
            $this->connection->rollBack();
        }
        $this->transactionCount = 0;
    }
}

// 커스텀 예외 클래스들
class DatabaseException extends Exception {}
class ValidationException extends Exception {}
class AuthorizationException extends Exception {}
class NotFoundException extends Exception {}

// 입력 검증 클래스
class InputValidator {
    
    public static function validateUserId($userId) {
        if (!is_numeric($userId) || $userId <= 0) {
            throw new ValidationException("Invalid user ID");
        }
        return (int)$userId;
    }
    
    public static function validatePluginId($pluginId) {
        if (empty($pluginId) || !preg_match('/^[a-z0-9_]+$/', $pluginId)) {
            throw new ValidationException("Invalid plugin ID format");
        }
        return $pluginId;
    }
    
    public static function validateSettingName($name) {
        if (empty($name) || strlen($name) > 100) {
            throw new ValidationException("Invalid setting name");
        }
        return $name;
    }
    
    public static function validateCategory($category) {
        if (!empty($category) && strlen($category) > 50) {
            throw new ValidationException("Invalid category");
        }
        return $category;
    }
    
    public static function validateCardTitle($title) {
        if (empty($title) || strlen($title) > 200) {
            throw new ValidationException("Invalid card title");
        }
        return $title;
    }
    
    public static function validateJson($data) {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ValidationException("Invalid JSON data");
            }
            return $decoded;
        }
        return $data;
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

// 데이터 액세스 계층
class PluginSettingsRepository {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    // 플러그인 타입 관련 메서드
    public function getPluginTypes($activeOnly = true) {
        $sql = "SELECT * FROM mdl_alt42DB_plugin_types";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY plugin_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function ensurePluginTypeExists($pluginId) {
        $sql = "SELECT COUNT(*) FROM mdl_alt42DB_plugin_types WHERE plugin_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pluginId]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function createDefaultPluginTypes() {
        $defaultTypes = [
            ['internal_link', '내부링크 열기', '🔗', '플랫폼 내 다른 페이지로 이동'],
            ['external_link', '외부링크 열기', '🌐', '외부 사이트나 도구 연결'],
            ['send_message', '메시지 발송', '📨', '사용자에게 자동 메시지 전송']
        ];
        
        $sql = "INSERT IGNORE INTO mdl_alt42DB_plugin_types 
                (plugin_id, plugin_title, plugin_icon, plugin_description, timecreated, timemodified) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $currentTime = time();
        
        foreach ($defaultTypes as $type) {
            $stmt->execute([
                $type[0], $type[1], $type[2], $type[3],
                $currentTime, $currentTime
            ]);
        }
    }
    
    // 사용자 플러그인 설정 관련 메서드
    public function getUserPluginSetting($userId, $pluginId, $settingName) {
        $sql = "SELECT * FROM mdl_alt42DB_user_plugin_settings 
                WHERE user_id = ? AND plugin_id = ? AND setting_name = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $pluginId, $settingName]);
        
        return $stmt->fetch();
    }
    
    public function upsertUserPluginSetting($data) {
        $sql = "INSERT INTO mdl_alt42DB_user_plugin_settings 
                (user_id, plugin_id, setting_name, setting_value, category, timecreated, timemodified)
                VALUES (:user_id, :plugin_id, :setting_name, :setting_value, :category, :time, :time)
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                category = VALUES(category),
                timemodified = VALUES(timemodified)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    // 카드 플러그인 설정 관련 메서드
    public function getCardPluginSetting($userId, $category, $cardTitle, $pluginId) {
        $sql = "SELECT * FROM mdl_alt42DB_card_plugin_settings 
                WHERE user_id = ? AND category = ? AND card_title = ? AND plugin_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $category, $cardTitle, $pluginId]);
        
        return $stmt->fetch();
    }
    
    public function upsertCardPluginSetting($data) {
        $sql = "INSERT INTO mdl_alt42DB_card_plugin_settings 
                (user_id, category, card_title, card_index, plugin_id, plugin_config, 
                 display_order, timecreated, timemodified)
                VALUES (:user_id, :category, :card_title, :card_index, :plugin_id, 
                        :plugin_config, :display_order, :time, :time)
                ON DUPLICATE KEY UPDATE 
                plugin_config = VALUES(plugin_config),
                card_index = VALUES(card_index),
                display_order = VALUES(display_order),
                timemodified = VALUES(timemodified)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    // 설정 조회 메서드
    public function getUserSettings($userId, $pluginId = null, $category = null) {
        $sql = "SELECT ups.*, pt.plugin_title, pt.plugin_icon, pt.plugin_description 
                FROM mdl_alt42DB_user_plugin_settings ups
                LEFT JOIN mdl_alt42DB_plugin_types pt ON ups.plugin_id = pt.plugin_id
                WHERE ups.user_id = :user_id AND ups.is_enabled = 1";
        
        $params = ['user_id' => $userId];
        
        if ($pluginId) {
            $sql .= " AND ups.plugin_id = :plugin_id";
            $params['plugin_id'] = $pluginId;
        }
        
        if ($category) {
            $sql .= " AND ups.category = :category";
            $params['category'] = $category;
        }
        
        $sql .= " ORDER BY ups.plugin_id, ups.setting_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getCardSettings($userId, $category = null, $cardTitle = null) {
        $sql = "SELECT cps.*, pt.plugin_title, pt.plugin_icon, pt.plugin_description 
                FROM mdl_alt42DB_card_plugin_settings cps
                LEFT JOIN mdl_alt42DB_plugin_types pt ON cps.plugin_id = pt.plugin_id
                WHERE cps.user_id = :user_id AND cps.is_active = 1";
        
        $params = ['user_id' => $userId];
        
        if ($category) {
            $sql .= " AND cps.category = :category";
            $params['category'] = $category;
        }
        
        if ($cardTitle) {
            $sql .= " AND cps.card_title = :card_title";
            $params['card_title'] = $cardTitle;
        }
        
        $sql .= " ORDER BY cps.category, cps.card_title, cps.display_order";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    // 삭제 관련 메서드
    public function deleteUserSetting($userId, $pluginId, $settingName, $category = null) {
        $sql = "DELETE FROM mdl_alt42DB_user_plugin_settings 
                WHERE user_id = :user_id AND plugin_id = :plugin_id AND setting_name = :setting_name";
        
        $params = [
            'user_id' => $userId,
            'plugin_id' => $pluginId,
            'setting_name' => $settingName
        ];
        
        if ($category) {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function deleteCardSetting($userId, $category, $cardTitle, $pluginId) {
        $sql = "DELETE FROM mdl_alt42DB_card_plugin_settings 
                WHERE user_id = ? AND category = ? AND card_title = ? AND plugin_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $category, $cardTitle, $pluginId]);
    }
    
    public function deleteCardSettingById($id, $userId) {
        $sql = "DELETE FROM mdl_alt42DB_card_plugin_settings 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }
    
    // 통계 관련 메서드
    public function getPluginUsageStats($pluginId = null) {
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
        
        if ($pluginId) {
            $sql .= " AND pt.plugin_id = :plugin_id";
            $params['plugin_id'] = $pluginId;
        }
        
        $sql .= " GROUP BY pt.plugin_id, pt.plugin_title ORDER BY pt.plugin_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}

// 히스토리 관리 서비스
class HistoryService {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function recordChange($userId, $pluginId, $settingType, $referenceId, $oldValue, $newValue, $changeReason = null) {
        $sql = "INSERT INTO mdl_alt42DB_plugin_settings_history 
                (user_id, plugin_id, setting_type, reference_id, old_value, new_value, change_reason, timecreated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $pluginId,
            $settingType,
            $referenceId,
            json_encode($oldValue),
            json_encode($newValue),
            $changeReason,
            time()
        ]);
    }
    
    public function getHistory($userId, $pluginId = null, $limit = 50) {
        $sql = "SELECT psh.*, pt.plugin_title 
                FROM mdl_alt42DB_plugin_settings_history psh
                LEFT JOIN mdl_alt42DB_plugin_types pt ON psh.plugin_id = pt.plugin_id
                WHERE psh.user_id = :user_id";
        
        $params = ['user_id' => $userId];
        
        if ($pluginId) {
            $sql .= " AND psh.plugin_id = :plugin_id";
            $params['plugin_id'] = $pluginId;
        }
        
        $sql .= " ORDER BY psh.timecreated DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}

// 메인 API 서비스 클래스
class KTMPluginSettingsAPIImproved {
    private $repository;
    private $historyService;
    private $dbManager;
    
    public function __construct($databaseConfig) {
        $this->dbManager = DatabaseManager::getInstance();
        $db = $this->dbManager->getConnection($databaseConfig);
        
        $this->repository = new PluginSettingsRepository($db);
        $this->historyService = new HistoryService($db);
    }
    
    // 플러그인 타입 관련 메서드
    public function getPluginTypes($activeOnly = true) {
        try {
            $types = $this->repository->getPluginTypes($activeOnly);
            
            // 타입이 없으면 기본 타입 생성
            if (empty($types)) {
                $this->repository->createDefaultPluginTypes();
                $types = $this->repository->getPluginTypes($activeOnly);
            }
            
            return $this->successResponse(['data' => $types]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 사용자 플러그인 설정 저장
    public function saveUserPluginSetting($userId, $pluginId, $settingName, $settingValue, $category = null) {
        try {
            // 입력 검증
            $userId = InputValidator::validateUserId($userId);
            $pluginId = InputValidator::validatePluginId($pluginId);
            $settingName = InputValidator::validateSettingName($settingName);
            $category = InputValidator::validateCategory($category);
            
            $this->dbManager->beginTransaction();
            
            // 기존 값 조회
            $oldSetting = $this->repository->getUserPluginSetting($userId, $pluginId, $settingName);
            $oldValue = $oldSetting ? json_decode($oldSetting['setting_value'], true) : null;
            $isNew = !$oldSetting;
            
            // 저장
            $data = [
                'user_id' => $userId,
                'plugin_id' => $pluginId,
                'setting_name' => $settingName,
                'setting_value' => json_encode($settingValue),
                'category' => $category,
                'time' => time()
            ];
            
            $this->repository->upsertUserPluginSetting($data);
            
            // 히스토리 기록
            $changeReason = $isNew ? 'New plugin created' : 'Plugin setting updated';
            $this->historyService->recordChange(
                $userId, $pluginId, 'user_setting', $settingName,
                $oldValue, $settingValue, $changeReason
            );
            
            $this->dbManager->commit();
            
            return $this->successResponse([
                'message' => '사용자 플러그인 설정이 저장되었습니다.',
                'is_new' => $isNew
            ]);
            
        } catch (ValidationException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 카드 플러그인 설정 저장
    public function saveCardPluginSetting($userId, $category, $cardTitle, $cardIndex, $pluginId, $pluginConfig, $displayOrder = 0) {
        try {
            // 입력 검증
            $userId = InputValidator::validateUserId($userId);
            $pluginId = InputValidator::validatePluginId($pluginId);
            $category = InputValidator::validateCategory($category);
            $cardTitle = InputValidator::validateCardTitle($cardTitle);
            $cardIndex = (int)$cardIndex;
            $displayOrder = (int)$displayOrder;
            
            $this->dbManager->beginTransaction();
            
            // 플러그인 타입 존재 확인
            if (!$this->repository->ensurePluginTypeExists($pluginId)) {
                $this->repository->createDefaultPluginTypes();
                if (!$this->repository->ensurePluginTypeExists($pluginId)) {
                    throw new ValidationException("Plugin type '$pluginId' does not exist");
                }
            }
            
            // 기존 값 조회
            $oldSetting = $this->repository->getCardPluginSetting($userId, $category, $cardTitle, $pluginId);
            $oldValue = $oldSetting ? json_decode($oldSetting['plugin_config'], true) : null;
            $isNew = !$oldSetting;
            
            // 저장
            $data = [
                'user_id' => $userId,
                'category' => $category,
                'card_title' => $cardTitle,
                'card_index' => $cardIndex,
                'plugin_id' => $pluginId,
                'plugin_config' => json_encode($pluginConfig),
                'display_order' => $displayOrder,
                'time' => time()
            ];
            
            $this->repository->upsertCardPluginSetting($data);
            
            // 히스토리 기록
            $changeReason = $isNew ? 'New card plugin created' : 'Card plugin configuration updated';
            $this->historyService->recordChange(
                $userId, $pluginId, 'card_setting', $cardTitle,
                $oldValue, $pluginConfig, $changeReason
            );
            
            $this->dbManager->commit();
            
            return $this->successResponse([
                'message' => '카드 플러그인 설정이 저장되었습니다.',
                'is_new' => $isNew
            ]);
            
        } catch (ValidationException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 사용자 플러그인 설정 조회
    public function getUserPluginSettings($userId, $pluginId = null, $category = null) {
        try {
            $userId = InputValidator::validateUserId($userId);
            if ($pluginId) $pluginId = InputValidator::validatePluginId($pluginId);
            if ($category) $category = InputValidator::validateCategory($category);
            
            $results = $this->repository->getUserSettings($userId, $pluginId, $category);
            
            // JSON 디코딩
            foreach ($results as &$row) {
                $row['setting_value'] = json_decode($row['setting_value'], true);
            }
            
            return $this->successResponse(['data' => $results]);
            
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 카드 플러그인 설정 조회
    public function getCardPluginSettings($userId, $category = null, $cardTitle = null) {
        try {
            $userId = InputValidator::validateUserId($userId);
            if ($category) $category = InputValidator::validateCategory($category);
            if ($cardTitle) $cardTitle = InputValidator::validateCardTitle($cardTitle);
            
            $results = $this->repository->getCardSettings($userId, $category, $cardTitle);
            
            // JSON 디코딩
            foreach ($results as &$row) {
                $row['plugin_config'] = json_decode($row['plugin_config'], true);
            }
            
            return $this->successResponse(['data' => $results]);
            
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 플러그인 설정 삭제
    public function deleteUserPluginSetting($userId, $pluginId, $settingName, $category = null) {
        try {
            $userId = InputValidator::validateUserId($userId);
            $pluginId = InputValidator::validatePluginId($pluginId);
            $settingName = InputValidator::validateSettingName($settingName);
            if ($category) $category = InputValidator::validateCategory($category);
            
            $this->dbManager->beginTransaction();
            
            // 삭제 전 현재 값 조회
            $oldSetting = $this->repository->getUserPluginSetting($userId, $pluginId, $settingName);
            if (!$oldSetting) {
                throw new NotFoundException("Setting not found");
            }
            
            // 삭제
            $this->repository->deleteUserSetting($userId, $pluginId, $settingName, $category);
            
            // 히스토리 기록
            $oldValue = json_decode($oldSetting['setting_value'], true);
            $this->historyService->recordChange(
                $userId, $pluginId, 'user_setting', $settingName,
                $oldValue, null, 'Plugin setting deleted'
            );
            
            $this->dbManager->commit();
            
            return $this->successResponse(['message' => '사용자 플러그인 설정이 삭제되었습니다.']);
            
        } catch (NotFoundException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 404);
        } catch (ValidationException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 카드 플러그인 설정 삭제
    public function deleteCardPluginSetting($userId, $category, $cardTitle, $pluginId) {
        try {
            $userId = InputValidator::validateUserId($userId);
            $pluginId = InputValidator::validatePluginId($pluginId);
            $category = InputValidator::validateCategory($category);
            $cardTitle = InputValidator::validateCardTitle($cardTitle);
            
            $this->dbManager->beginTransaction();
            
            // 삭제 전 현재 값 조회
            $oldSetting = $this->repository->getCardPluginSetting($userId, $category, $cardTitle, $pluginId);
            if (!$oldSetting) {
                throw new NotFoundException("Card setting not found");
            }
            
            // 삭제
            $this->repository->deleteCardSetting($userId, $category, $cardTitle, $pluginId);
            
            // 히스토리 기록
            $oldValue = json_decode($oldSetting['plugin_config'], true);
            $this->historyService->recordChange(
                $userId, $pluginId, 'card_setting', $cardTitle,
                $oldValue, null, 'Card plugin deleted'
            );
            
            $this->dbManager->commit();
            
            return $this->successResponse(['message' => '카드 플러그인 설정이 삭제되었습니다.']);
            
        } catch (NotFoundException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 404);
        } catch (ValidationException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // ID로 카드 설정 삭제
    public function deleteCardPluginSettingById($userId, $category, $cardTitle, $cardId, $cardIndex) {
        try {
            $userId = InputValidator::validateUserId($userId);
            $category = InputValidator::validateCategory($category);
            $cardTitle = InputValidator::validateCardTitle($cardTitle);
            
            $this->dbManager->beginTransaction();
            
            // ID 또는 인덱스로 조회
            if ($cardId) {
                $sql = "SELECT * FROM mdl_alt42DB_card_plugin_settings 
                       WHERE id = ? AND user_id = ? AND category = ? AND card_title = ?";
                $params = [$cardId, $userId, $category, $cardTitle];
            } else {
                $sql = "SELECT * FROM mdl_alt42DB_card_plugin_settings 
                       WHERE card_index = ? AND user_id = ? AND category = ? AND card_title = ?";
                $params = [$cardIndex, $userId, $category, $cardTitle];
            }
            
            $stmt = $this->repository->db->prepare($sql);
            $stmt->execute($params);
            $card = $stmt->fetch();
            
            if (!$card) {
                throw new NotFoundException("Card not found");
            }
            
            // 삭제
            $this->repository->deleteCardSettingById($card['id'], $userId);
            
            // 히스토리 기록
            $oldValue = json_decode($card['plugin_config'], true);
            $this->historyService->recordChange(
                $userId, $card['plugin_id'], 'card_setting', $cardTitle,
                $oldValue, null, 'Card plugin deleted by ID'
            );
            
            $this->dbManager->commit();
            
            return $this->successResponse(['message' => '카드가 성공적으로 삭제되었습니다.']);
            
        } catch (NotFoundException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 404);
        } catch (ValidationException $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->dbManager->rollback();
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 플러그인 사용 통계
    public function getPluginUsageStats($pluginId = null) {
        try {
            if ($pluginId) $pluginId = InputValidator::validatePluginId($pluginId);
            
            $stats = $this->repository->getPluginUsageStats($pluginId);
            
            return $this->successResponse(['data' => $stats]);
            
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 설정 히스토리 조회
    public function getSettingHistory($userId, $pluginId = null, $limit = 50) {
        try {
            $userId = InputValidator::validateUserId($userId);
            if ($pluginId) $pluginId = InputValidator::validatePluginId($pluginId);
            $limit = min(max((int)$limit, 1), 1000); // 1-1000 범위
            
            $history = $this->historyService->getHistory($userId, $pluginId, $limit);
            
            // JSON 디코딩
            foreach ($history as &$row) {
                $row['old_value'] = json_decode($row['old_value'], true);
                $row['new_value'] = json_decode($row['new_value'], true);
            }
            
            return $this->successResponse(['data' => $history]);
            
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    // 응답 헬퍼 메서드
    private function successResponse($data) {
        return array_merge(['success' => true], $data);
    }
    
    private function errorResponse($message, $code = 500) {
        error_log("API Error: $message");
        return [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
    }
}

// API 라우터 클래스
class APIRouter {
    private $api;
    private $routes = [];
    
    public function __construct(KTMPluginSettingsAPIImproved $api) {
        $this->api = $api;
        $this->registerRoutes();
    }
    
    private function registerRoutes() {
        // GET 라우트
        $this->routes['GET'] = [
            'get_plugin_types' => function($params) {
                $activeOnly = isset($params['active_only']) ? (bool)$params['active_only'] : true;
                return $this->api->getPluginTypes($activeOnly);
            },
            'get_user_settings' => function($params) {
                return $this->api->getUserPluginSettings(
                    $params['user_id'],
                    $params['plugin_id'] ?? null,
                    $params['category'] ?? null
                );
            },
            'get_card_settings' => function($params) {
                return $this->api->getCardPluginSettings(
                    $params['user_id'],
                    $params['category'] ?? null,
                    $params['card_title'] ?? null
                );
            },
            'get_usage_stats' => function($params) {
                return $this->api->getPluginUsageStats($params['plugin_id'] ?? null);
            },
            'get_setting_history' => function($params) {
                return $this->api->getSettingHistory(
                    $params['user_id'],
                    $params['plugin_id'] ?? null,
                    $params['limit'] ?? 50
                );
            }
        ];
        
        // POST 라우트
        $this->routes['POST'] = [
            'save_user_setting' => function($params) {
                return $this->api->saveUserPluginSetting(
                    $params['user_id'],
                    $params['plugin_id'],
                    $params['setting_name'],
                    $params['setting_value'],
                    $params['category'] ?? null
                );
            },
            'save_card_setting' => function($params) {
                return $this->api->saveCardPluginSetting(
                    $params['user_id'],
                    $params['category'],
                    $params['card_title'],
                    $params['card_index'],
                    $params['plugin_id'],
                    $params['plugin_config'],
                    $params['display_order'] ?? 0
                );
            },
            'delete_user_setting' => function($params) {
                return $this->api->deleteUserPluginSetting(
                    $params['user_id'],
                    $params['plugin_id'],
                    $params['setting_name'],
                    $params['category'] ?? null
                );
            },
            'delete_card_setting' => function($params) {
                return $this->api->deleteCardPluginSetting(
                    $params['user_id'],
                    $params['category'],
                    $params['card_title'],
                    $params['plugin_id']
                );
            },
            'delete_card_setting_by_id' => function($params) {
                return $this->api->deleteCardPluginSettingById(
                    $params['user_id'],
                    $params['category'],
                    $params['card_title'],
                    $params['card_id'] ?? null,
                    $params['card_index'] ?? null
                );
            }
        ];
    }
    
    public function handle($method, $action, $params) {
        if (!isset($this->routes[$method][$action])) {
            return [
                'success' => false,
                'error' => 'Invalid action',
                'code' => 400
            ];
        }
        
        try {
            return $this->routes[$method][$action]($params);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
}

// API 엔드포인트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    
    try {
        // 데이터베이스 설정 로드
        require_once __DIR__ . '/plugin_db_config.php';
        
        $databaseConfig = [
            'type' => $dbtype ?? 'mysql',
            'host' => DB_HOST,
            'name' => DB_NAME,
            'charset' => DB_CHARSET,
            'user' => DB_USER,
            'pass' => DB_PASS
        ];
        
        // API 초기화
        $api = new KTMPluginSettingsAPIImproved($databaseConfig);
        $router = new APIRouter($api);
        
        // 요청 파라미터 가져오기
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_REQUEST['action'] ?? '';
        
        // POST 요청의 경우 JSON 바디도 처리
        if ($method === 'POST') {
            $jsonInput = file_get_contents('php://input');
            if ($jsonInput) {
                $jsonData = json_decode($jsonInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $_REQUEST = array_merge($_REQUEST, $jsonData);
                }
            }
        }
        
        // 요청 처리
        $response = $router->handle($method, $action, $_REQUEST);
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => 'Internal server error',
            'code' => 500
        ];
        error_log("API Fatal Error: " . $e->getMessage());
    }
    
    // 응답 전송
    if (isset($response['code'])) {
        http_response_code($response['code']);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>