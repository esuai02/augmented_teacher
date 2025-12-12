<?php
/**
 * OWL 온톨로지 시각화 도구
 * 
 * 파일: ontology_visualizer.php
 * 위치: alt42/orchestration/agents/math topics/
 */

// 테스트 모드 (로그인 우회 - 개발용)
$test_mode = isset($_GET['test']) && $_GET['test'] === '1';

if (!$test_mode) {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;
    require_login();
} else {
    // 테스트 모드: 기본 설정만
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 디버깅 모드 (개발 중)
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 사용자 역할 확인
if (!$test_mode) {
    $userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
    $role = $userrole ? $userrole->data : '';
} else {
    $role = '';
}

// 디렉토리 경로
$base_dir = __DIR__;
$owl_dir = $base_dir;

// JSON 오류 위치 찾기 함수
function findJsonErrorPosition($json_string) {
    // 간단한 오류 위치 추정 (정확하지 않을 수 있음)
    $lines = explode("\n", $json_string);
    $line_num = 1;
    foreach ($lines as $line) {
        $test_json = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_SYNTAX) {
            return ['position' => strlen(implode("\n", array_slice($lines, 0, $line_num - 1))), 'line' => $line_num];
        }
        $line_num++;
    }
    return null;
}

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
    // 번호 순서대로 정렬 (파일명 앞의 숫자 기준)
    usort($owl_files, function($a, $b) {
        // 파일명에서 숫자 추출 (앞부분의 숫자만)
        preg_match('/^(\d+)/', $a, $matches_a);
        preg_match('/^(\d+)/', $b, $matches_b);
        
        $num_a = isset($matches_a[1]) ? (int)$matches_a[1] : 9999;
        $num_b = isset($matches_b[1]) ? (int)$matches_b[1] : 9999;
        
        // 숫자 순서로 정렬, 같으면 알파벳 순서
        if ($num_a === $num_b) {
            return strcmp($a, $b);
        }
        return $num_a - $num_b;
    });
}

// 선택된 파일 처리
$selected_file = isset($_GET['file']) ? $_GET['file'] : '';
$json_data = null;
$error_message = '';

if ($selected_file && in_array($selected_file, $owl_files)) {
    $owl_path = $owl_dir . '/' . $selected_file;
    $json_path = $owl_dir . '/' . pathinfo($selected_file, PATHINFO_FILENAME) . '.json';
    
    $debug_info[] = "선택된 파일: " . $selected_file;
    $debug_info[] = "OWL 경로: " . $owl_path;
    $debug_info[] = "JSON 경로: " . $json_path;
    $debug_info[] = "OWL 파일 존재: " . (file_exists($owl_path) ? '예' : '아니오');
    $debug_info[] = "JSON 파일 존재: " . (file_exists($json_path) ? '예' : '아니오');
    
    if (file_exists($owl_path)) {
        $debug_info[] = "OWL 파일 크기: " . filesize($owl_path) . " bytes";
        $debug_info[] = "OWL 파일 수정 시간: " . date('Y-m-d H:i:s', filemtime($owl_path));
    }
    if (file_exists($json_path)) {
        $debug_info[] = "JSON 파일 크기: " . filesize($json_path) . " bytes";
    }
    
    // JSON 파일이 없거나 비어있거나 OWL 파일이 더 최신이면 파싱 실행
    $json_exists = file_exists($json_path);
    $json_size = $json_exists ? filesize($json_path) : 0;
    // 캐시 무효화를 위해 항상 파싱하도록 (개발 중) 또는 OWL이 더 최신이면 파싱
    $force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    $needs_parsing = $force_refresh || !$json_exists || $json_size == 0 || 
                     (file_exists($owl_path) && filemtime($owl_path) > filemtime($json_path));
    
    if ($needs_parsing) {
        if (!$json_exists) {
            $debug_info[] = "파싱 필요: JSON 파일이 존재하지 않습니다.";
        } elseif ($json_size == 0) {
            $debug_info[] = "파싱 필요: JSON 파일이 비어있습니다 (크기: 0 bytes).";
        } else {
            $debug_info[] = "파싱 필요: OWL 파일이 더 최신입니다.";
        }
        
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
                break;
            }
        }
        
        if ($found_python) {
            $debug_info[] = "사용할 Python: " . $found_python;
            
            // Python 스크립트 파일 존재 확인
            $parser_path = $base_dir . '/owl_parser.py';
            $debug_info[] = "파서 스크립트 존재: " . (file_exists($parser_path) ? '예' : '아니오');
            if (file_exists($parser_path)) {
                $debug_info[] = "파서 스크립트 권한: " . substr(sprintf('%o', fileperms($parser_path)), -4);
            }
            
            // proc_open을 사용하여 한글 파일명 처리
            // PHP 7.1.9에서는 proc_open이 배열을 지원하지 않으므로 문자열로 변환
            // 한글 파일명을 위해 각 경로를 작은따옴표로 감싸기
            $cmd_string = escapeshellarg($found_python) . ' ' . 
                         escapeshellarg($parser_path) . ' ' . 
                         escapeshellarg($owl_path) . ' ' . 
                         escapeshellarg($json_path) . ' 2>&1';
            
            $descriptorspec = array(
                0 => array("pipe", "r"),  // stdin
                1 => array("pipe", "w"),  // stdout
                2 => array("pipe", "w")   // stderr
            );
            
            $debug_info[] = "실행 명령: " . $cmd_string;
            $debug_info[] = "OWL 경로 확인: " . (file_exists($owl_path) ? '존재함' : '없음');
            
            // 환경 변수 설정 (한글 파일명 처리)
            $env = array();
            $env['LC_ALL'] = 'en_US.UTF-8';
            $env['LANG'] = 'en_US.UTF-8';
            
            $process = proc_open($cmd_string, $descriptorspec, $pipes, null, $env);
            
            if (is_resource($process)) {
                fclose($pipes[0]); // stdin 닫기
                
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                
                $return_value = proc_close($process);
                
                $output = $stdout . $stderr;
                $debug_info[] = "Python 실행 결과 (return code: $return_value): " . ($output ? htmlspecialchars($output) : '(출력 없음)');
                
                if (!file_exists($json_path) || filesize($json_path) == 0) {
                    $error_message = "파싱 실패: " . htmlspecialchars($output ?: '알 수 없는 오류');
                    $debug_info[] = "오류: JSON 파일이 생성되지 않았거나 비어있습니다.";
                } else {
                    $debug_info[] = "성공: JSON 파일이 생성되었습니다. 크기: " . filesize($json_path) . " bytes";
                }
            } else {
                $error_message = "프로세스 실행 실패";
                $debug_info[] = "오류: proc_open 실패";
            }
        } else {
            $error_message = "Python을 찾을 수 없습니다. 테스트한 경로: " . implode(', ', $python_paths);
            $debug_info[] = "오류: 사용 가능한 Python을 찾을 수 없습니다.";
        }
    } else {
        $debug_info[] = "캐시된 JSON 파일 사용 (크기: " . $json_size . " bytes)";
    }
    
    // JSON 파일 로드
    if (file_exists($json_path)) {
        $json_content = file_get_contents($json_path);
        $debug_info[] = "JSON 파일 읽기 성공. 크기: " . strlen($json_content) . " bytes";
        
        // JSON 내용의 처음 500자 확인 (디버깅용)
        if ($debug_mode) {
            $debug_info[] = "JSON 내용 미리보기 (처음 500자): " . htmlspecialchars(substr($json_content, 0, 500));
        }
        
        // JSON 유효성 사전 검사
        $json_content_trimmed = trim($json_content);
        if (empty($json_content_trimmed)) {
            $error_message = "JSON 파일이 비어있습니다.";
            $json_data = null;
            $debug_info[] = "오류: JSON 파일이 비어있습니다.";
        } else {
            // BOM 제거 (UTF-8 BOM 문제 해결)
            if (substr($json_content, 0, 3) === "\xEF\xBB\xBF") {
                $json_content = substr($json_content, 3);
                $debug_info[] = "UTF-8 BOM 제거됨";
            }
            
            $json_data = json_decode($json_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error_message = "JSON 파싱 오류: " . json_last_error_msg() . " (코드: " . json_last_error() . ")";
                $json_data = null;
                $debug_info[] = "JSON 파싱 실패: " . $error_message;
                
                // 오류 위치 찾기 (대략적인 위치)
                $error_pos = json_last_error() === JSON_ERROR_SYNTAX ? findJsonErrorPosition($json_content) : null;
                if ($error_pos) {
                    $debug_info[] = "JSON 오류 위치 (대략): 문자 " . $error_pos['position'] . " 근처";
                    $debug_info[] = "오류 위치 주변 내용: " . htmlspecialchars(substr($json_content, max(0, $error_pos['position'] - 50), 100));
                }
            } else {
                $node_count = isset($json_data['nodes']) ? count($json_data['nodes']) : 0;
                $link_count = isset($json_data['links']) ? count($json_data['links']) : 0;
                $debug_info[] = "JSON 파싱 성공. 노드 수: {$node_count}, 링크 수: {$link_count}";
                
                // 링크 타입별 통계
                if (isset($json_data['links']) && is_array($json_data['links'])) {
                    $link_types = [];
                    foreach ($json_data['links'] as $link) {
                        $type = isset($link['type']) ? $link['type'] : 'unknown';
                        $link_types[$type] = ($link_types[$type] ?? 0) + 1;
                    }
                    $debug_info[] = "링크 타입별 통계: " . json_encode($link_types, JSON_UNESCAPED_UNICODE);
                }
            }
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
    <title>OWL 온톨로지 시각화 도구</title>
    <link rel="stylesheet" href="ontology_visualizer.css?v=<?php echo time(); ?>">
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        function handleFileSelect(selectElement) {
            var url = window.location.pathname;
            var params = [];
            
            <?php if ($test_mode): ?>
            params.push('test=1');
            <?php endif; ?>
            
            <?php if ($debug_mode): ?>
            params.push('debug=1');
            <?php endif; ?>
            
            if (selectElement.value) {
                params.unshift('file=' + encodeURIComponent(selectElement.value));
            }
            
            var query = params.length > 0 ? '?' + params.join('&') : '';
            window.location.href = url + query;
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 OWL 온톨로지 시각화 도구</h1>
            <div class="header-right">
                <div class="file-selector">
                    <label for="owl-file-select">온톨로지 파일 선택:</label>
                    <select id="owl-file-select" onchange="handleFileSelect(this)">
                        <option value="">-- 파일 선택 --</option>
                        <?php foreach ($owl_files as $file): ?>
                            <option value="<?php echo htmlspecialchars($file); ?>" 
                                    <?php echo ($selected_file === $file) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($file); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a href="../agent_orchestration/dataindex.php?agentid=agent03_goals_analysis" class="dashboard-icon" title="데이터 대시보드 (Agent 03)">
                    <span class="dashboard-icon-text">📈</span>
                </a>
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

            <script type="text/javascript">
                // 데이터 전달 (즉시 실행)
                (function() {
                    window.graphData = <?php echo json_encode($json_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                    console.log('graphData 설정됨:', window.graphData ? window.graphData.nodes.length + ' nodes' : 'null');
                })();
            </script>
            <script type="text/javascript" src="ontology_visualizer.js?v=<?php echo time(); ?>"></script>
        <?php else: ?>
            <div class="area-cards-container">
                <div class="area-cards-grid">
                    <?php 
                    // 영역 이름 추출 함수
                    function getAreaName($filename) {
                        // 파일명에서 숫자와 _ontology.owl 제거
                        $name = preg_replace('/^\d+\s*/', '', $filename); // 앞의 숫자 제거
                        $name = preg_replace('/_ontology\.owl$/', '', $name); // _ontology.owl 제거
                        
                        // 한글이 포함된 경우 그대로 사용
                        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $name)) {
                            return trim($name);
                        }
                        
                        // 영문인 경우 언더스코어를 공백으로 변환하고 각 단어 첫 글자 대문자
                        $name = str_replace('_', ' ', $name);
                        $name = ucwords(strtolower($name));
                        
                        // 특정 영역명 한글 변환
                        $nameMap = [
                            'numbers' => '수',
                            'exponential logarithm' => '지수와 로그',
                            'expression calculation' => '식의 계산',
                            'sets and propositions' => '집합과 명제',
                            'plane coordinates' => '평면좌표',
                            'solid figures' => '입체도형',
                            'space coordinates' => '공간좌표',
                            'vector' => '벡터',
                            'number of cases and probability' => '경우의 수와 확률',
                            'statistics' => '통계',
                            'differentiation' => '미분',
                            'integration' => '적분',
                            'functions' => '함수'
                        ];
                        
                        $lowerName = strtolower($name);
                        if (isset($nameMap[$lowerName])) {
                            return $nameMap[$lowerName];
                        }
                        
                        return trim($name);
                    }
                    
                    foreach ($owl_files as $file): 
                        $areaName = getAreaName($file);
                        $fileNumber = preg_match('/^(\d+)/', $file, $matches) ? $matches[1] : '';
                    ?>
                        <div class="area-card" onclick="window.location.href='?file=<?php echo urlencode($file); ?><?php echo $test_mode ? '&test=1' : ''; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>'">
                            <div class="area-card-number"><?php echo htmlspecialchars($fileNumber); ?></div>
                            <div class="area-card-title"><?php echo htmlspecialchars($areaName); ?></div>
                            <div class="area-card-icon">📊</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="area-description">위의 카드를 클릭하여 해당 영역의 온톨로지 그래프를 확인하세요.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

