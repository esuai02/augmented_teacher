<?php
/**
 * Module Data API
 * Provides dynamic module configuration data from database
 */

// Include database configuration
require_once(__DIR__ . '/plugin_db_config.php');

// Set JSON response headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class ModuleDataAPI {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * Get module data by category key
     */
    public function getModuleData($category_key) {
        try {
            // Get category information
            $categorySql = "SELECT * FROM mdl_ktm_categories WHERE category_key = ? AND is_active = 1";
            $stmt = $this->db->prepare($categorySql);
            $stmt->execute([$category_key]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                return [
                    'success' => false,
                    'error' => "Category '$category_key' not found"
                ];
            }
            
            // Get tabs for this category
            $tabsSql = "SELECT * FROM mdl_ktm_tabs WHERE category_id = ? AND is_active = 1 ORDER BY display_order";
            $stmt = $this->db->prepare($tabsSql);
            $stmt->execute([$category['id']]);
            $tabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get items for each tab
            $itemsSql = "SELECT * FROM mdl_ktm_menu_items WHERE tab_id = ? AND is_active = 1 ORDER BY display_order";
            $itemStmt = $this->db->prepare($itemsSql);
            
            $formattedTabs = [];
            foreach ($tabs as $tab) {
                $itemStmt->execute([$tab['id']]);
                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format items
                $formattedItems = [];
                foreach ($items as $item) {
                    $formattedItem = [
                        'title' => $item['title'],
                        'description' => $item['description']
                    ];
                    
                    // Parse JSON details if present
                    if ($item['details']) {
                        $details = json_decode($item['details'], true);
                        if ($details) {
                            $formattedItem['details'] = $details;
                        }
                    }
                    
                    if ($item['has_chain_interaction']) {
                        $formattedItem['hasLink'] = true;
                    }
                    
                    $formattedItems[] = $formattedItem;
                }
                
                $formattedTabs[] = [
                    'id' => $tab['tab_key'],
                    'title' => $tab['title'],
                    'description' => $tab['description'],
                    'explanation' => $tab['description'], // For backward compatibility
                    'items' => $formattedItems
                ];
            }
            
            // Format the response to match the expected structure
            $moduleData = [
                'title' => $category['title'],
                'description' => $category['description'],
                'tabs' => $formattedTabs
            ];
            
            return [
                'success' => true,
                'data' => $moduleData,
                'agent' => [
                    'name' => $category['agent_name'],
                    'role' => $category['agent_role'],
                    'avatar' => $category['agent_avatar']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching module data: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all categories
     */
    public function getAllCategories() {
        try {
            $sql = "SELECT category_key, title, description, agent_name, agent_role, agent_avatar 
                    FROM mdl_ktm_categories 
                    WHERE is_active = 1 
                    ORDER BY display_order";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Process request
try {
    $pdo = getDBConnection();
    $api = new ModuleDataAPI($pdo);
    
    $action = $_GET['action'] ?? 'getModuleData';
    $response = [];
    
    switch ($action) {
        case 'getModuleData':
            $category = $_GET['category'] ?? null;
            if (!$category) {
                throw new Exception('Category parameter is required');
            }
            $response = $api->getModuleData($category);
            break;
            
        case 'getAllCategories':
            $response = $api->getAllCategories();
            break;
            
        default:
            throw new Exception("Unknown action: $action");
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}