<?php
/**
 * Evolution Stages Viewer - 진화 단계 문서 뷰어
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/evolution_stages_viewer.php
 * 
 * EVOLUTION_STAGES.md 파일을 읽어서 웹에서 표시하는 뷰어
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole ? $userrole->data : 'student';

// 마크다운 파일 경로
$md_file = __DIR__ . '/../EVOLUTION_STAGES.md';

if (!file_exists($md_file)) {
    die('Error: EVOLUTION_STAGES.md 파일을 찾을 수 없습니다. (파일 경로: ' . htmlspecialchars($md_file) . ', 라인: ' . __LINE__ . ')');
}

// 마크다운 파일 읽기
$content = file_get_contents($md_file);
if ($content === false) {
    die('Error: 파일을 읽을 수 없습니다. (파일: ' . htmlspecialchars(__FILE__) . ', 라인: ' . __LINE__ . ')');
}

/**
 * 간단한 마크다운을 HTML로 변환하는 함수
 * @param string $text 마크다운 텍스트
 * @return string HTML 문자열
 */
function simple_markdown($text) {
    // 코드 블록 먼저 처리 (다른 변환에 영향받지 않도록)
    $code_blocks = [];
    $code_block_index = 0;
    $text = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function($matches) use (&$code_blocks, &$code_block_index) {
        $lang = !empty($matches[1]) ? $matches[1] : '';
        $code = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
        $placeholder = "___CODE_BLOCK_{$code_block_index}___";
        $code_blocks[$code_block_index] = '<pre><code class="language-' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '">' . $code . '</code></pre>';
        $code_block_index++;
        return $placeholder;
    }, $text);
    
    // 인라인 코드 처리
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    
    // 헤더 처리 (순서 중요: ### -> ## -> #)
    $text = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    
    // 강조 (Bold)
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    
    // 이탤릭
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    
    // 링크
    $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank">$1</a>', $text);
    
    // 수평선
    $text = preg_replace('/^---$/m', '<hr>', $text);
    
    // 인용구 (blockquote) 처리
    $text = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $text);
    // 연속된 blockquote를 하나로 합치기
    $text = preg_replace('/(<\/blockquote>\s*<blockquote>)+/', '<br>', $text);
    
    // 테이블 처리
    $lines = explode("\n", $text);
    $in_table = false;
    $table_html = '';
    $processed_lines = [];
    
    foreach ($lines as $line) {
        if (preg_match('/^\|(.+)\|$/', $line)) {
            if (!$in_table) {
                $in_table = true;
                $table_html = '<table class="markdown-table">';
            }
            
            // 헤더 행인지 확인 (다음 행이 구분선인지 체크)
            $cells = array_map('trim', explode('|', $line));
            $cells = array_filter($cells, function($cell) { return $cell !== ''; });
            $cells = array_values($cells);
            
            $row_html = '<tr>';
            foreach ($cells as $cell) {
                // 구분선 행 처리
                if (preg_match('/^:?-+:?$/', $cell)) {
                    continue; // 구분선 행은 건너뛰기
                }
                $row_html .= '<td>' . trim($cell) . '</td>';
            }
            $row_html .= '</tr>';
            $table_html .= $row_html;
        } else {
            if ($in_table) {
                $in_table = false;
                $table_html .= '</table>';
                $processed_lines[] = $table_html;
                $table_html = '';
            }
            $processed_lines[] = $line;
        }
    }
    
    if ($in_table) {
        $table_html .= '</table>';
        $processed_lines[] = $table_html;
    }
    
    $text = implode("\n", $processed_lines);
    
    // 리스트 처리 (순서 없는 리스트)
    $text = preg_replace('/^\- (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text);
    
    // 순서 있는 리스트
    $text = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>)/s', '<ol>$1</ol>', $text);
    
    // 코드 블록 복원
    foreach ($code_blocks as $index => $code_html) {
        $text = str_replace("___CODE_BLOCK_{$index}___", $code_html, $text);
    }
    
    // 단락 처리 (빈 줄 기준)
    $paragraphs = preg_split('/\n\s*\n/', $text);
    $html_paragraphs = [];
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) continue;
        
        // 이미 HTML 태그가 있으면 그대로 사용
        if (preg_match('/^<(h[1-6]|ul|ol|table|pre|hr)/', $para)) {
            $html_paragraphs[] = $para;
        } else {
            $html_paragraphs[] = '<p>' . $para . '</p>';
        }
    }
    
    return implode("\n", $html_paragraphs);
}

$html_content = simple_markdown($content);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>진화 단계 - Evolution Stages</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .back-link:hover {
            background: #5568d3;
        }
        
        .markdown-content {
            line-height: 1.8;
        }
        
        .markdown-content h1 {
            color: #667eea;
            margin-top: 40px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .markdown-content h2 {
            color: #5568d3;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .markdown-content h3 {
            color: #444;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        
        .markdown-content h4 {
            color: #666;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .markdown-content p {
            margin-bottom: 15px;
        }
        
        .markdown-content ul,
        .markdown-content ol {
            margin: 15px 0;
            padding-left: 30px;
        }
        
        .markdown-content li {
            margin-bottom: 8px;
        }
        
        .markdown-content code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #e83e8c;
        }
        
        .markdown-content pre {
            background: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            margin: 20px 0;
        }
        
        .markdown-content pre code {
            background: transparent;
            padding: 0;
            color: #333;
            font-size: 0.9em;
        }
        
        .markdown-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .markdown-table th,
        .markdown-table td {
            border: 1px solid #e0e0e0;
            padding: 12px;
            text-align: left;
        }
        
        .markdown-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .markdown-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .markdown-content hr {
            border: none;
            border-top: 2px solid #e0e0e0;
            margin: 30px 0;
        }
        
        .markdown-content a {
            color: #667eea;
            text-decoration: none;
        }
        
        .markdown-content a:hover {
            text-decoration: underline;
        }
        
        .markdown-content strong {
            font-weight: 600;
            color: #333;
        }
        
        .markdown-content em {
            font-style: italic;
            color: #666;
        }
        
        .markdown-content blockquote {
            border-left: 4px solid #667eea;
            padding: 10px 20px;
            margin: 20px 0;
            background: #f8f9fa;
            color: #555;
            font-style: italic;
        }
        
        .markdown-content blockquote strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="back-link">← 돌아가기</a>
            <h1>📈 Agent 22 - 진화 단계 (Evolution Stages)</h1>
        </div>
        
        <div class="markdown-content">
            <?php echo $html_content; ?>
        </div>
    </div>
</body>
</html>

