<?php
/**
 * 단계별 진단 - ?step=N 으로 각 단계를 개별 테스트
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

echo "=== Step {$step} Diagnostics ===\n\n";

try {
    switch ($step) {
        case 0:
            echo "Available steps:\n";
            echo "?step=1 - Load Moodle config\n";
            echo "?step=2 - Calculate paths\n";
            echo "?step=3 - Load AbstractPersonaEngine.php\n";
            echo "?step=4 - Load BaseRuleParser.php\n";
            echo "?step=5 - Load BaseConditionEvaluator.php\n";
            echo "?step=6 - Load BaseActionExecutor.php\n";
            echo "?step=7 - Load BaseDataContext.php\n";
            echo "?step=8 - Load BaseResponseGenerator.php\n";
            echo "?step=9 - Load persona_engine.config.php\n";
            echo "?step=10 - Load Agent03PersonaEngine.php\n";
            echo "?step=11 - Check classes\n";
            echo "?step=12 - Instantiate engine\n";
            echo "?step=13 - Process message\n";
            break;

        case 1:
            echo "Loading Moodle config...\n";
            include_once("/home/moodle/public_html/moodle/config.php");
            echo "SUCCESS: Moodle config loaded\n";
            echo "MOODLE_INTERNAL = " . (defined('MOODLE_INTERNAL') ? 'true' : 'false') . "\n";
            break;

        case 2:
            echo "Calculating paths...\n";
            $engineBasePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine';
            echo "Engine base path: {$engineBasePath}\n";
            echo "Path exists: " . (is_dir($engineBasePath) ? 'YES' : 'NO') . "\n";
            break;

        case 3:
            echo "Loading AbstractPersonaEngine.php...\n";
            $path = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/AbstractPersonaEngine.php';
            echo "Path: {$path}\n";
            echo "Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: File loaded\n";
                echo "Class exists: " . (class_exists('AbstractPersonaEngine') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 4:
            echo "Loading BaseRuleParser.php...\n";
            // First load dependencies
            require_once(dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
            $path = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/impl/BaseRuleParser.php';
            echo "Path: {$path}\n";
            echo "Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: File loaded\n";
                echo "Class exists: " . (class_exists('BaseRuleParser') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 5:
            echo "Loading BaseConditionEvaluator.php...\n";
            require_once(dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
            $path = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/impl/BaseConditionEvaluator.php';
            echo "Path: {$path}\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: File loaded\n";
                echo "Class exists: " . (class_exists('BaseConditionEvaluator') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 6:
            echo "Loading BaseActionExecutor.php...\n";
            require_once(dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
            $path = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/impl/BaseActionExecutor.php';
            echo "Path: {$path}\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: File loaded\n";
                echo "Class exists: " . (class_exists('BaseActionExecutor') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 7:
            echo "Loading BaseDataContext.php...\n";
            require_once(dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
            $path = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/impl/BaseDataContext.php';
            echo "Path: {$path}\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: File loaded\n";
                echo "Class exists: " . (class_exists('BaseDataContext') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 8:
            echo "Loading BaseResponseGenerator.php...\n";
            require_once(dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
            $path = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/impl/BaseResponseGenerator.php';
            echo "Path: {$path}\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: File loaded\n";
                echo "Class exists: " . (class_exists('BaseResponseGenerator') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 9:
            echo "Loading persona_engine.config.php...\n";
            $path = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/config/persona_engine.config.php';
            echo "Path: {$path}\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: Config file loaded\n";
                echo "PERSONA_ENGINE_CONFIG defined: " . (defined('PERSONA_ENGINE_CONFIG') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 10:
            echo "Loading all dependencies first...\n";
            $engineBasePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine';
            require_once($engineBasePath . '/core/AbstractPersonaEngine.php');
            require_once($engineBasePath . '/impl/BaseRuleParser.php');
            require_once($engineBasePath . '/impl/BaseConditionEvaluator.php');
            require_once($engineBasePath . '/impl/BaseActionExecutor.php');
            require_once($engineBasePath . '/impl/BaseDataContext.php');
            require_once($engineBasePath . '/impl/BaseResponseGenerator.php');
            require_once($engineBasePath . '/config/persona_engine.config.php');
            echo "Dependencies loaded.\n\n";

            echo "Loading Agent03PersonaEngine.php...\n";
            $path = __DIR__ . '/../engine/Agent03PersonaEngine.php';
            echo "Path: {$path}\n";
            echo "Real path: " . realpath($path) . "\n";
            if (file_exists($path)) {
                require_once($path);
                echo "SUCCESS: File loaded\n";
                echo "Agent03PersonaEngine class exists: " . (class_exists('Agent03PersonaEngine') ? 'YES' : 'NO') . "\n";
                echo "Agent03DataContext class exists: " . (class_exists('Agent03DataContext') ? 'YES' : 'NO') . "\n";
            }
            break;

        case 11:
            echo "Loading all files and checking classes...\n";
            $engineBasePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine';
            require_once($engineBasePath . '/core/AbstractPersonaEngine.php');
            require_once($engineBasePath . '/impl/BaseRuleParser.php');
            require_once($engineBasePath . '/impl/BaseConditionEvaluator.php');
            require_once($engineBasePath . '/impl/BaseActionExecutor.php');
            require_once($engineBasePath . '/impl/BaseDataContext.php');
            require_once($engineBasePath . '/impl/BaseResponseGenerator.php');
            require_once($engineBasePath . '/config/persona_engine.config.php');
            require_once(__DIR__ . '/../engine/Agent03PersonaEngine.php');

            $classes = [
                'AbstractPersonaEngine',
                'BaseRuleParser',
                'BaseConditionEvaluator',
                'BaseActionExecutor',
                'BaseDataContext',
                'BaseResponseGenerator',
                'Agent03PersonaEngine',
                'Agent03DataContext'
            ];

            foreach ($classes as $class) {
                echo "{$class}: " . (class_exists($class) ? 'EXISTS' : 'MISSING') . "\n";
            }
            break;

        case 12:
            echo "Loading all files...\n";
            $engineBasePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine';
            require_once($engineBasePath . '/core/AbstractPersonaEngine.php');
            require_once($engineBasePath . '/impl/BaseRuleParser.php');
            require_once($engineBasePath . '/impl/BaseConditionEvaluator.php');
            require_once($engineBasePath . '/impl/BaseActionExecutor.php');
            require_once($engineBasePath . '/impl/BaseDataContext.php');
            require_once($engineBasePath . '/impl/BaseResponseGenerator.php');
            require_once($engineBasePath . '/config/persona_engine.config.php');
            require_once(__DIR__ . '/../engine/Agent03PersonaEngine.php');

            echo "Instantiating Agent03PersonaEngine...\n";
            $engine = new Agent03PersonaEngine('agent03');
            echo "SUCCESS: Engine instantiated!\n";
            echo "Engine class: " . get_class($engine) . "\n";
            break;

        case 13:
            include_once("/home/moodle/public_html/moodle/config.php");
            global $DB, $USER;

            $engineBasePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine';
            require_once($engineBasePath . '/core/AbstractPersonaEngine.php');
            require_once($engineBasePath . '/impl/BaseRuleParser.php');
            require_once($engineBasePath . '/impl/BaseConditionEvaluator.php');
            require_once($engineBasePath . '/impl/BaseActionExecutor.php');
            require_once($engineBasePath . '/impl/BaseDataContext.php');
            require_once($engineBasePath . '/impl/BaseResponseGenerator.php');
            require_once($engineBasePath . '/config/persona_engine.config.php');
            require_once(__DIR__ . '/../engine/Agent03PersonaEngine.php');

            echo "Processing test message...\n";
            $engine = new Agent03PersonaEngine('agent03');
            $result = $engine->process(1, '목표를 세우고 싶어요', ['source' => 'test']);
            echo "SUCCESS: Message processed!\n";
            echo "Result success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
            if (isset($result['response']['text'])) {
                echo "Response: " . substr($result['response']['text'], 0, 200) . "...\n";
            }
            break;

        default:
            echo "Unknown step. Use ?step=0 for help.\n";
    }

    echo "\n=== Step {$step} Complete ===\n";

} catch (Exception $e) {
    echo "\n!!! EXCEPTION !!!\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n!!! FATAL ERROR !!!\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
