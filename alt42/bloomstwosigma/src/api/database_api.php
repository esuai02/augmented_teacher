<?php
/**
 * Database API Controller
 * 데이터베이스 관련 API 엔드포인트들을 처리
 */

// Moodle 설정 및 인증
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET["userid"] ?? null;
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? 'student';

require_once(__DIR__ . '/../database/controllers/DatabaseController.php');
require_once(__DIR__ . '/../database/models/FieldDescriptionModel.php');
require_once(__DIR__ . '/../database/models/TableDescriptionModel.php');
require_once(__DIR__ . '/../database/models/ExperimentModel.php');

class DatabaseAPI {
    private $dbController;
    private $fieldDescriptionModel;
    private $tableDescriptionModel;
    private $experimentModel;
    
    public function __construct() {
        $this->dbController = new DatabaseController();
        $this->fieldDescriptionModel = new FieldDescriptionModel();
        $this->tableDescriptionModel = new TableDescriptionModel();
        $this->experimentModel = new ExperimentModel();
    }
    
    public function handleRequest() {
        // 모든 요청 로그
        error_log('API 요청: ' . $_SERVER['REQUEST_METHOD'] . ' - Action: ' . ($_POST['action'] ?? 'NONE'));
        error_log('POST 데이터: ' . json_encode($_POST));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            error_log('잘못된 요청: METHOD=' . $_SERVER['REQUEST_METHOD'] . ', ACTION=' . ($_POST['action'] ?? 'NONE'));
            $this->sendError('Invalid request method or missing action');
            return;
        }
        
        $action = $_POST['action'];
        
        switch ($action) {
            case 'get_db_tables':
                $this->getDBTables();
                break;
                
            case 'get_table_fields':
                $this->getTableFields();
                break;
                
            case 'get_table_data':
                $this->getTableData();
                break;
                
            case 'get_db_stats':
                $this->getDBStats();
                break;
                
            case 'test_connection':
                $this->testConnection();
                break;
                
            case 'save_field_description':
                $this->saveFieldDescription();
                break;
                
            case 'get_field_descriptions':
                $this->getFieldDescriptions();
                break;
                
            case 'save_multiple_field_descriptions':
                $this->saveMultipleFieldDescriptions();
                break;
                
            case 'create_field_descriptions_table':
                $this->createFieldDescriptionsTable();
                break;
                
            // 테이블 설명 관련 액션들
            case 'save_table_description':
                $this->saveTableDescription();
                break;
                
            case 'get_table_description':
                $this->getTableDescription();
                break;
                
            case 'get_all_table_descriptions':
                $this->getAllTableDescriptions();
                break;
                
            // 실험 관리 액션들
            case 'save_experiment':
                $this->saveExperiment();
                break;
                
            case 'get_experiment':
                $this->getExperiment();
                break;
                
            case 'get_experiments_list':
                $this->getExperimentsList();
                break;
                
            case 'save_intervention_method':
                $this->saveInterventionMethod();
                break;
                
            case 'save_tracking_config':
                $this->saveTrackingConfig();
                break;
                
            case 'save_group_assignment':
                $this->saveGroupAssignment();
                break;
                
            case 'save_database_connection':
                $this->saveDatabaseConnection();
                break;
                
            case 'save_experiment_result':
                $this->saveExperimentResult();
                break;
                
            case 'save_hypothesis':
                $this->saveHypothesis();
                break;
                
            case 'log_experiment_activity':
                $this->logExperimentActivity();
                break;
                
            default:
                $this->sendError('Unknown action: ' . $action);
        }
    }
    
    private function getDBTables() {
        $page = intval($_POST['page'] ?? 1);
        $limit = intval($_POST['limit'] ?? 50);
        $search = $_POST['search'] ?? '';
        
        $result = $this->dbController->getDBTables($page, $limit, $search);
        $this->sendResponse($result);
    }
    
    private function getTableFields() {
        $tableName = $_POST['table_name'] ?? '';
        if (empty($tableName)) {
            $this->sendError('Table name is required');
            return;
        }
        
        $result = $this->dbController->getTableFields($tableName);
        $this->sendResponse($result);
    }
    
    private function getTableData() {
        $tableName = $_POST['table_name'] ?? '';
        $limit = intval($_POST['limit'] ?? 10);
        $offset = intval($_POST['offset'] ?? 0);
        
        if (empty($tableName)) {
            $this->sendError('Table name is required');
            return;
        }
        
        $result = $this->dbController->getTableData($tableName, $limit, $offset);
        $this->sendResponse($result);
    }
    
    private function getDBStats() {
        $result = $this->dbController->getDBStats();
        $this->sendResponse($result);
    }
    
    private function testConnection() {
        $result = $this->dbController->testDBConnection();
        $this->sendResponse($result);
    }
    
    private function saveFieldDescription() {
        $tableName = $_POST['table_name'] ?? '';
        $fieldName = $_POST['field_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = $_POST['type'] ?? null;
        
        if (empty($tableName) || empty($fieldName)) {
            $this->sendError('Table name and field name are required');
            return;
        }
        
        $result = $this->fieldDescriptionModel->saveFieldDescription($tableName, $fieldName, $description, $type);
        $this->sendResponse($result);
    }
    
    private function getFieldDescriptions() {
        $tableName = $_POST['table_name'] ?? '';
        
        if (empty($tableName)) {
            $this->sendError('Table name is required');
            return;
        }
        
        $result = $this->fieldDescriptionModel->getFieldDescriptions($tableName);
        $this->sendResponse($result);
    }
    
    private function saveMultipleFieldDescriptions() {
        $tableName = $_POST['table_name'] ?? '';
        $fieldDescriptions = $_POST['field_descriptions'] ?? [];
        
        if (empty($tableName)) {
            $this->sendError('Table name is required');
            return;
        }
        
        // JSON 문자열인 경우 디코딩
        if (is_string($fieldDescriptions)) {
            $fieldDescriptions = json_decode($fieldDescriptions, true);
        }
        
        $result = $this->fieldDescriptionModel->saveMultipleFieldDescriptions($tableName, $fieldDescriptions);
        $this->sendResponse($result);
    }
    
    private function createFieldDescriptionsTable() {
        // 더이상 사용하지 않음 - MySQL COLUMN COMMENT 사용으로 대체됨
        $this->sendResponse([
            'success' => true,
            'message' => 'mdl_alt42_field_descriptions 테이블은 더이상 사용되지 않습니다. MySQL COLUMN COMMENT를 사용합니다.'
        ]);
    }
    
    // 테이블 설명 관련 메서드들
    private function saveTableDescription() {
        $tableName = $_POST['table_name'] ?? '';
        $type = $_POST['type'] ?? 'A';
        $description = $_POST['description'] ?? '';
        
        if (empty($tableName)) {
            $this->sendError('테이블명이 필요합니다.');
            return;
        }
        
        $result = $this->tableDescriptionModel->saveTableDescription($tableName, $type, $description);
        $this->sendResponse($result);
    }
    
    private function getTableDescription() {
        $tableName = $_POST['table_name'] ?? '';
        
        if (empty($tableName)) {
            $this->sendError('테이블명이 필요합니다.');
            return;
        }
        
        $result = $this->tableDescriptionModel->getTableDescription($tableName);
        $this->sendResponse($result);
    }
    
    private function getAllTableDescriptions() {
        $result = $this->tableDescriptionModel->getAllTableDescriptions();
        $this->sendResponse($result);
    }
    
    // 실험 관리 API 메서드들
    private function saveExperiment() {
        $experimentData = [
            'id' => $_POST['experiment_id'] ?? null,
            'experiment_name' => $_POST['experiment_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'start_date' => $_POST['start_date'] ?? '',
            'duration_weeks' => $_POST['duration_weeks'] ?? 8,
            'status' => $_POST['status'] ?? 'planned',
            'created_by' => $_POST['created_by'] ?? 0
        ];
        
        // 디버깅 로그
        error_log('실험 저장 요청 데이터: ' . json_encode($experimentData));
        
        if (empty($experimentData['experiment_name'])) {
            error_log('실험명이 비어있음');
            $this->sendError('실험명이 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->saveExperiment($experimentData);
        error_log('실험 저장 결과: ' . json_encode($result));
        
        $this->sendResponse($result);
    }
    
    private function getExperiment() {
        $experimentId = $_POST['experiment_id'] ?? '';
        
        if (empty($experimentId)) {
            $this->sendError('실험 ID가 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->getExperiment($experimentId);
        $this->sendResponse($result);
    }
    
    private function getExperimentsList() {
        $createdBy = $_POST['created_by'] ?? null;
        $status = $_POST['status'] ?? null;
        $limit = intval($_POST['limit'] ?? 50);
        $offset = intval($_POST['offset'] ?? 0);
        
        $result = $this->experimentModel->getExperimentsList($createdBy, $status, $limit, $offset);
        $this->sendResponse($result);
    }
    
    private function saveInterventionMethod() {
        $experimentId = $_POST['experiment_id'] ?? '';
        $methodData = [
            'method_type' => $_POST['method_type'] ?? 'metacognitive',
            'method_name' => $_POST['method_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'is_active' => $_POST['is_active'] ?? 1
        ];
        
        if (empty($experimentId) || empty($methodData['method_name'])) {
            $this->sendError('실험 ID와 방법명이 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->saveInterventionMethod($experimentId, $methodData);
        $this->sendResponse($result);
    }
    
    private function saveTrackingConfig() {
        $experimentId = $_POST['experiment_id'] ?? '';
        $configData = [
            'config_name' => $_POST['config_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'tracking_type' => $_POST['tracking_type'] ?? 'performance',
            'data_source' => $_POST['data_source'] ?? '',
            'collection_frequency' => $_POST['collection_frequency'] ?? 'weekly',
            'is_active' => $_POST['is_active'] ?? 1
        ];
        
        if (empty($experimentId) || empty($configData['config_name'])) {
            $this->sendError('실험 ID와 설정명이 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->saveTrackingConfig($experimentId, $configData);
        $this->sendResponse($result);
    }
    
    private function saveGroupAssignment() {
        $experimentId = $_POST['experiment_id'] ?? '';
        $userId = $_POST['user_id'] ?? '';
        $groupType = $_POST['group_type'] ?? 'control';
        $interventionMethodId = $_POST['intervention_method_id'] ?? null;
        $teacherId = $_POST['teacher_id'] ?? null;
        $assignedBy = $_POST['assigned_by'] ?? null;
        
        if (empty($experimentId) || empty($userId)) {
            $this->sendError('실험 ID와 사용자 ID가 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->saveGroupAssignment($experimentId, $userId, $groupType, $interventionMethodId, $teacherId, $assignedBy);
        $this->sendResponse($result);
    }
    
    private function saveDatabaseConnection() {
        $experimentId = $_POST['experiment_id'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $databaseName = $_POST['database_name'] ?? 'mathking';
        $purpose = $_POST['purpose'] ?? '';
        $conditions = $_POST['conditions'] ?? '';
        
        if (empty($experimentId) || empty($tableName)) {
            $this->sendError('실험 ID와 테이블명이 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->saveDatabaseConnection($experimentId, $tableName, $databaseName, $purpose, $conditions);
        $this->sendResponse($result);
    }
    
    private function saveExperimentResult() {
        $experimentId = $_POST['experiment_id'] ?? '';
        $resultData = [
            'result_type' => $_POST['result_type'] ?? 'analysis',
            'result_title' => $_POST['result_title'] ?? '',
            'result_content' => $_POST['result_content'] ?? '',
            'result_data' => $_POST['result_data'] ?? '',
            'author_id' => $_POST['author_id'] ?? 0,
            'collection_date' => $_POST['collection_date'] ?? null
        ];
        
        if (empty($experimentId) || empty($resultData['result_title'])) {
            $this->sendError('실험 ID와 결과 제목이 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->saveExperimentResult($experimentId, $resultData);
        $this->sendResponse($result);
    }
    
    private function saveHypothesis() {
        $experimentId = $_POST['experiment_id'] ?? '';
        $hypothesisText = $_POST['hypothesis_text'] ?? '';
        $hypothesisType = $_POST['hypothesis_type'] ?? 'primary';
        $authorId = $_POST['author_id'] ?? null;
        
        // 디버깅 로그
        error_log('가설 저장 요청 데이터: ' . json_encode([
            'experiment_id' => $experimentId,
            'hypothesis_text' => $hypothesisText,
            'hypothesis_type' => $hypothesisType,
            'author_id' => $authorId
        ]));
        
        if (empty($experimentId) || empty($hypothesisText)) {
            error_log('실험 ID 또는 가설 내용이 비어있음');
            $this->sendError('실험 ID와 가설 내용이 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->saveHypothesis($experimentId, $hypothesisText, $hypothesisType, $authorId);
        error_log('가설 저장 결과: ' . json_encode($result));
        
        $this->sendResponse($result);
    }
    
    private function logExperimentActivity() {
        $experimentId = $_POST['experiment_id'] ?? '';
        $logType = $_POST['log_type'] ?? 'modify';
        $logMessage = $_POST['log_message'] ?? '';
        $logData = $_POST['log_data'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        
        if (empty($experimentId) || empty($logMessage)) {
            $this->sendError('실험 ID와 로그 메시지가 필요합니다.');
            return;
        }
        
        $result = $this->experimentModel->logExperimentActivity($experimentId, $logType, $logMessage, $logData, $userId);
        $this->sendResponse($result);
    }
    
    private function sendResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    private function sendError($message) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}

// API 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $api = new DatabaseAPI();
    $api->handleRequest();
}