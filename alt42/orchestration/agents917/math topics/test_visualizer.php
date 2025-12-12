<?php
/**
 * OWL 온톨로지 시각화 도구 - 테스트 버전 (로그인 불필요)
 * 
 * 파일: test_visualizer.php
 * 위치: alt42/orchestration/agents/math topics/
 */

// 에러 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 디버깅 모드
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';

// 디렉토리 경로
$base_dir = __DIR__;
$owl_dir = $base_dir;

// 디버깅 정보 수집
$debug_info = [];

// OWL 파일 목록 가져오기
$owl_files = [];
if (is_dir($owl_dir)) {
    $files = scandir($owl_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'owl') {
            $owl_files[] = $file;
        }
    }
    sort($owl_files);
}

// 선택된 파일 처리
$selected_file = isset($_GET['file']) ? $_GET['file'] : '';
$json_data = null;
$error_message = '';

if ($selected_file && in_array($selected_file, $owl_files)) {
    $owl_path = $owl_dir . '/' . $selected_file;
    
    // 디렉토리 쓰기 권한 확인 및 JSON 경로 결정
    $can_write = is_writable($owl_dir);
    $temp_dir = sys_get_temp_dir();
    
    if ($can_write) {
        $json_path = $owl_dir . '/' . pathinfo($selected_file, PATHINFO_FILENAME) . '.json';
    } else {
        // 임시 디렉토리 사용
        $json_path = $temp_dir . '/' . pathinfo($selected_file, PATHINFO_FILENAME) . '.json';
        $debug_info[] = "경고: 디렉토리에 쓰기 권한이 없습니다. 임시 디렉토리 사용: " . $temp_dir;
    }
    
    $debug_info[] = "선택된 파일: " . $selected_file;
    $debug_info[] = "OWL 경로: " . $owl_path;
    $debug_info[] = "JSON 경로: " . $json_path;
    $debug_info[] = "OWL 파일 존재: " . (file_exists($owl_path) ? '예' : '아니오');
    $debug_info[] = "JSON 파일 존재: " . (file_exists($json_path) ? '예' : '아니오');
    $debug_info[] = "디렉토리 쓰기 가능: " . ($can_write ? '예' : '아니오');
    
    if (file_exists($owl_path)) {
        $debug_info[] = "OWL 파일 크기: " . filesize($owl_path) . " bytes";
        $debug_info[] = "OWL 파일 수정 시간: " . date('Y-m-d H:i:s', filemtime($owl_path));
    }
    
    // JSON 파일이 없거나 OWL 파일이 더 최신이면 파싱 실행
    if (!file_exists($json_path) || (file_exists($owl_path) && filemtime($owl_path) > filemtime($json_path))) {
        $debug_info[] = "파싱 필요: JSON 파일이 없거나 OWL 파일이 더 최신입니다.";
        
        // Python 경로 시도 (서버 환경에 맞게 조정)
        $python_paths = ['python3', 'python', '/usr/bin/python3', '/usr/bin/python'];
        $python_cmd = null;
        $found_python = null;
        
        foreach ($python_paths as $python) {
            $test_cmd = escapeshellarg($python) . ' --version 2>&1';
            $test_output = shell_exec($test_cmd);
            $debug_info[] = "Python 테스트 ($python): " . ($test_output ?: '실패');
            
            if ($test_output && strpos($test_output, 'Python') !== false) {
                $found_python = $python;
                $python_cmd = escapeshellarg($python) . ' ' . escapeshellarg($base_dir . '/owl_parser.py') . ' ' . 
                             escapeshellarg($owl_path) . ' ' . escapeshellarg($json_path) . ' 2>&1';
                break;
            }
        }
        
        if ($python_cmd) {
            $debug_info[] = "사용할 Python: " . $found_python;
            $debug_info[] = "실행 명령: " . $python_cmd;
            
            // Python 스크립트 파일 존재 확인
            $parser_path = $base_dir . '/owl_parser.py';
            $debug_info[] = "파서 스크립트 존재: " . (file_exists($parser_path) ? '예' : '아니오');
            if (file_exists($parser_path)) {
                $debug_info[] = "파서 스크립트 권한: " . substr(sprintf('%o', fileperms($parser_path)), -4);
            }
            
            $output = shell_exec($python_cmd);
            $debug_info[] = "Python 실행 결과: " . ($output ?: '(출력 없음)');
            
            if (!file_exists($json_path)) {
                $error_message = "파싱 실패: " . htmlspecialchars($output ?: '알 수 없는 오류');
                $debug_info[] = "오류: JSON 파일이 생성되지 않았습니다.";
            } else {
                $debug_info[] = "성공: JSON 파일이 생성되었습니다. 크기: " . filesize($json_path) . " bytes";
            }
        } else {
            $error_message = "Python을 찾을 수 없습니다. 테스트한 경로: " . implode(', ', $python_paths);
            $debug_info[] = "오류: 사용 가능한 Python을 찾을 수 없습니다.";
        }
    } else {
        $debug_info[] = "캐시된 JSON 파일 사용";
    }
    
    // JSON 파일 로드
    if (file_exists($json_path)) {
        $json_content = file_get_contents($json_path);
        $debug_info[] = "JSON 파일 읽기 성공. 크기: " . strlen($json_content) . " bytes";
        
        $json_data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = "JSON 파싱 오류: " . json_last_error_msg() . " (코드: " . json_last_error() . ")";
            $json_data = null;
            $debug_info[] = "JSON 파싱 실패: " . $error_message;
        } else {
            $debug_info[] = "JSON 파싱 성공. 노드 수: " . (isset($json_data['nodes']) ? count($json_data['nodes']) : 0);
        }
    } else {
        $debug_info[] = "JSON 파일이 존재하지 않습니다.";
    }
} else if ($selected_file) {
    $error_message = "선택한 파일이 유효하지 않습니다: " . htmlspecialchars($selected_file);
    $debug_info[] = "오류: 파일이 목록에 없습니다.";
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWL 온톨로지 시각화 도구 (테스트)</title>
    <link rel="stylesheet" href="ontology_visualizer.css">
    <script src="https://d3js.org/d3.v7.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 OWL 온톨로지 시각화 도구 (테스트 모드)</h1>
            <div class="file-selector">
                <label for="owl-file-select">온톨로지 파일 선택:</label>
                <select id="owl-file-select">
                    <option value="">-- 파일 선택 --</option>
                    <?php foreach ($owl_files as $file): ?>
                        <option value="<?php echo htmlspecialchars($file); ?>" 
                                <?php echo ($selected_file === $file) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($file); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </header>

        <?php if ($debug_mode && !empty($debug_info)): ?>
            <div class="debug-panel">
                <h3>🔍 디버그 정보</h3>
                <ul>
                    <?php foreach ($debug_info as $info): ?>
                        <li><?php echo htmlspecialchars($info); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <strong>오류:</strong> <?php echo $error_message; ?>
                <?php if (!$debug_mode): ?>
                    <br><small><a href="?file=<?php echo urlencode($selected_file); ?>&debug=1">디버그 모드 활성화</a></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($selected_file && !$json_data && !$error_message): ?>
            <div class="loading-message">
                <p>⏳ 온톨로지 파일을 파싱하는 중...</p>
            </div>
        <?php endif; ?>

        <?php if ($json_data): ?>
            <div class="info-panel">
                <h2>온톨로지 정보</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>파일:</strong> 
                        <span><?php echo htmlspecialchars($json_data['metadata']['filename']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>제목:</strong> 
                        <span><?php echo htmlspecialchars($json_data['metadata']['title']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>노드 수:</strong> 
                        <span><?php echo count($json_data['nodes']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>관계 수:</strong> 
                        <span><?php echo count($json_data['links']); ?></span>
                    </div>
                </div>
            </div>

            <div class="controls">
                <button id="reset-zoom">🔍 리셋</button>
                <button id="toggle-labels">🏷️ 라벨 토글</button>
                <button id="filter-stage">📊 단계별 필터</button>
                <select id="layout-select">
                    <option value="force">Force (기본)</option>
                    <option value="hierarchical">계층형</option>
                    <option value="circular">원형</option>
                </select>
                <?php if (!$debug_mode): ?>
                    <a href="?file=<?php echo urlencode($selected_file); ?>&debug=1" style="margin-left: auto; padding: 8px 16px; background: #ffc107; color: #000; text-decoration: none; border-radius: 4px; font-size: 12px;">🔍 디버그</a>
                <?php endif; ?>
            </div>

            <div id="graph-container"></div>

            <div class="legend">
                <h3>범례</h3>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="legend-color" style="background: #1f77b4;"></span>
                        <span>precedes 관계</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background: #ff7f0e;"></span>
                        <span>dependsOn 관계</span>
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                // 데이터 전달 (즉시 실행)
                (function() {
                    window.graphData = <?php echo json_encode($json_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                    console.log('graphData 설정됨:', window.graphData ? window.graphData.nodes.length + ' nodes' : 'null');
                })();
            </script>
            <script type="text/javascript" src="ontology_visualizer.js?v=<?php echo time(); ?>"></script>
        <?php else: ?>
            <div class="placeholder">
                <p>위에서 온톨로지 파일을 선택하세요.</p>
                <?php if (!$debug_mode): ?>
                    <p><a href="?debug=1">디버그 모드 활성화</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

