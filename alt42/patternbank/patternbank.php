<?php 
 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;

$cntid = $_GET["contentsid"]; 
$cnttype = $_GET["contentstype"]; 
$studentid = $_GET["userid"];
$wboardid = $_GET["wboardid"];
$print = $_GET["print"];
 
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname; 
 
if($cnttype==1) 
    { 
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $eventid=1;
    $maintext=$cnttext->maintext;
    if($print==0)$papertest=$cnttext->reflections0;
    else $papertest=$cnttext->reflections1;
  

	$ctext=$cnttext->pageicontent;
	if($cnttext->reflections!=NULL)$reflections=$cnttext->reflections.'<hr>';
	$htmlDom = new DOMDocument;
 
	@$htmlDom->loadHTML($ctext);
	$imageTags = $htmlDom->getElementsByTagName('img');
	$extractedImages = array(); 
	$nimg=0;
	foreach($imageTags as $imageTag)
		{
		$nimg++;
		$imgSrc = $imageTag->getAttribute('src');
		$imgSrc = str_replace(' ', '%20', $imgSrc); 
		if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
		}
}

// OpenAI 설정 파일에서 실제 사용되는 설정 값 읽어오기
$openaiConfigPath = __DIR__ . '/config/openai_config.php';
if (file_exists($openaiConfigPath)) {
    // 설정 파일을 읽어서 실제 사용되는 값 추출
    $configContent = file_get_contents($openaiConfigPath);
    
    // 기본값 설정
    $aiModel = 'gpt-4o-mini';
    $aiTemperature = 0.1;
    $aiMaxTokens = 2000;
    
    // 모델명 추출 (실제 사용되는 부분에서: $model = 'gpt-4o-mini'; 형태)
    // GPT-4o API 호출 주석 다음에 나오는 $model = 부분을 찾음
    if (preg_match("/\/\/\s*GPT-4o\s+API\s+호출.*?\n.*?\$model\s*=\s*['\"]([^'\"]+)['\"]/s", $configContent, $matches)) {
        $aiModel = $matches[1];
    } elseif (preg_match("/\$model\s*=\s*['\"]([^'\"]+)['\"]/", $configContent, $matches)) {
        // 일반적인 패턴으로도 시도
        $aiModel = $matches[1];
    }
    
    // Temperature 추출 ('temperature' => 0.1, 형태)
    if (preg_match("/'temperature'\s*=>\s*([0-9.]+)/", $configContent, $matches)) {
        $aiTemperature = floatval($matches[1]);
    }
    
    // Max Tokens 추출 ('max_tokens' => 2000, 형태)
    if (preg_match("/'max_tokens'\s*=>\s*([0-9]+)/", $configContent, $matches)) {
        $aiMaxTokens = intval($matches[1]);
    }
} else {
    // 기본값
    $aiModel = 'gpt-4o-mini';
    $aiTemperature = 0.1;
    $aiMaxTokens = 2000;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pattern Bank Interface</title>
    <!-- MathJax for LaTeX rendering -->
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)'], ['$', '$']],
                displayMath: [['\\[', '\\]'], ['$$', '$$']]
            },
            startup: {
                pageReady: () => {
                    return MathJax.startup.defaultPageReady().then(() => {
                        console.log('MathJax initial typesetting complete');
                    });
                }
            }
        };
        
        // MathJax가 완전히 로드될 때까지 기다리는 헬퍼 함수
        async function waitForMathJax() {
            if (window.MathJax && window.MathJax.startup && window.MathJax.startup.promise) {
                await window.MathJax.startup.promise;
            }
            // MathJax가 아직 로드되지 않았으면 최대 5초 대기
            let attempts = 0;
            while (!window.MathJax || !window.MathJax.typesetPromise) {
                if (attempts++ > 50) {
                    console.warn('MathJax 로드 대기 시간 초과');
                    return false;
                }
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            return true;
        }
        
        // LaTeX 수식을 감지하고 $로 감싸는 함수
        function wrapLatexInDollars(text) {
            if (!text || typeof text !== 'string') return text;
            
            // 이미 $로 감싸져 있으면 그대로 반환
            const dollarCount = (text.match(/\$/g) || []).length;
            if (dollarCount >= 2) {
                return text;
            }
            
            // LaTeX 명령어 패턴 (예: \frac, \sqrt, \sum, \int, \lim 등)
            const latexCommandPattern = /\\[a-zA-Z]+(\{[^}]*\})*/;
            
            // LaTeX 명령어가 있는지 확인
            const hasLatex = latexCommandPattern.test(text);
            
            if (!hasLatex) {
                return text;
            }
            
            // 선택지 번호(①, ② 등)가 있는 경우와 없는 경우 모두 처리
            // 예: "① \frac{1}{8}" -> "① $\frac{1}{8}$"
            // 예: "\frac{1}{8}" -> "$\frac{1}{8}$"
            
            // 선택지 번호가 있는 경우
            const choiceNumberMatch = text.match(/^([①-⑤]\s*)(.+)$/);
            if (choiceNumberMatch) {
                const prefix = choiceNumberMatch[1];
                const content = choiceNumberMatch[2].trim();
                // content에 LaTeX가 있으면 $로 감싸기
                if (latexCommandPattern.test(content)) {
                    return prefix + '$' + content + '$';
                }
                return text;
            }
            
            // 선택지 번호가 없는 경우 - 전체를 $로 감싸기
            return '$' + text + '$';
        }
        
        // 줄바꿈 처리 헬퍼 함수 (\n을 유지하되 HTML 이스케이프 처리)
        function preserveNewlines(text) {
            if (!text || typeof text !== 'string') return text;
            // HTML 특수문자 이스케이프 처리
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            // \n은 white-space: pre-line CSS로 처리됨
        }
        
        // 수식 렌더링 헬퍼 함수
        async function renderMath(element) {
            const isReady = await waitForMathJax();
            if (!isReady) {
                console.warn('MathJax가 준비되지 않았습니다.');
                return;
            }
            
            try {
                if (Array.isArray(element)) {
                    await MathJax.typesetPromise(element);
                } else {
                    await MathJax.typesetPromise([element]);
                }
            } catch (error) {
                console.error('MathJax 렌더링 오류:', error);
            }
        }
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f5f6fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            width: 100%;
            flex-shrink: 0;
        }

        h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
        }

        .exam-button {
            padding: 12px 24px;
            background-color: #9b59b6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .exam-button:hover {
            background-color: #8e44ad;
            transform: translateY(-1px);
        }
        
        .exam-button:hover[onclick*="printSelectedProblems"] {
            background-color: #d35400;
        }

        /* 3칼럼 레이아웃 */
        .main-content {
            display: flex;
            flex: 1;
            gap: 15px;
            overflow: hidden;
            width: 100%;
        }

        /* 좌측 칼럼: 대표유형 + 유형 분석 */
        .left-column {
            width: 350px;
            min-width: 350px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* 중앙 칼럼: 문제 목록 */
        .center-column {
            width: 100px;
            min-width: 100px;
            max-width: 100px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px 10px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .problem-list-section {
            margin-bottom: 20px;
        }

        .problem-list-title {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }

        .problem-list-items {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        /* 우측 칼럼: 문제 상세 */
        .right-column {
            flex: 1;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .problem-detail-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #95a5a6;
            font-size: 16px;
            text-align: center;
        }

        /* 상단 섹션 (제거됨 - 좌측 칼럼으로 이동) */
        .top-section {
            display: none;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            min-width: 0;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .representative-type {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .type-title {
            font-size: 16px;
            font-weight: 600;
            color: #3498db;
            margin-bottom: 10px;
        }

        .type-content {
            color: #555;
            line-height: 1.8;
        }

        .analysis-text {
            color: #555;
            line-height: 1.8;
            text-align: justify;
            cursor: pointer;
            position: relative;
            min-height: 100px;
        }
        
        .analysis-text:hover {
            background-color: #f9f9f9;
        }
        
        .analysis-text[contenteditable="true"] {
            background-color: #fff;
            border: 2px solid #3498db;
            padding: 10px;
            cursor: text;
        }
        
        .analysis-save-indicator {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 12px;
            color: #27ae60;
            display: none;
        }

        /* 하단 섹션 (제거됨) */
        .bottom-section {
            display: none;
        }

        /* 문제 블록 스타일 */
        .problem-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-start;
        }

        .problem-block {
            background-color: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
            user-select: none; /* 더블클릭 시 텍스트 선택 방지 */
        }

        /* 중앙 칼럼용 문제 블록 스타일 */
        .center-column .problem-block {
            width: 100%;
            height: 50px;
            font-size: 11px;
            padding: 5px;
        }

        .center-column .problem-block:hover {
            background-color: #e8f4f8;
            border-color: #3498db;
            transform: translateX(2px);
        }

        .center-column .problem-block.selected {
            background-color: #e3f2fd;
            border-color: #2196f3;
            border-width: 3px;
        }

        .problem-block:hover {
            background-color: #e8f4f8;
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .problem-block.selected {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }
        
        .problem-block.similar {
            border-color: #90EE90;
            border-width: 3px;
        }
        
        .problem-block.similar:hover {
            border-color: #7FDD7F;
        }
        
        .problem-block.modified {
            border-color: #87CEEB;
            border-width: 3px;
        }
        
        .problem-block.modified:hover {
            border-color: #5DADE2;
        }

        /* 툴팁 스타일 */
        .tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 100;
            max-width: 200px;
            white-space: normal;
            text-align: left;
            font-weight: normal;
        }

        .tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #333;
        }

        .problem-block:hover .tooltip {
            opacity: 1;
            visibility: visible;
            bottom: calc(100% + 8px);
        }

        .add-button {
            padding: 12px 20px;
            background-color: #f8f9fa;
            color: #2c3e50;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .add-button.compact {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .generator-button {
            padding: 8px 12px;
            background-color: #f8f9fa;
            color: #2c3e50;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .generator-button:hover {
            background-color: #e9ecef;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .add-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .add-button:active {
            transform: translateY(0);
        }

        .add-button.similar {
            background-color: #90EE90;
            border-color: #90EE90;
        }

        .add-button.similar:hover {
            background-color: #7FDD7F;
            border-color: #7FDD7F;
        }

        .add-button.variant {
            background-color: #87CEEB;
            border-color: #87CEEB;
        }

        .add-button.variant:hover {
            background-color: #5DADE2;
            border-color: #5DADE2;
        }

        /* 로딩 애니메이션 */
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(44, 62, 80, 0.3);
            border-radius: 50%;
            border-top-color: #2c3e50;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 모달 스타일 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close:hover {
            color: #2c3e50;
        }

        .modal h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        /* 시험지 모달 스타일 */
        .exam-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .exam-content {
            background-color: white;
            margin: 20px auto;
            padding: 0;
            width: 210mm;
            min-height: 297mm;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .exam-paper {
            padding: 15mm 25mm;
            font-family: 'Batang', serif;
            color: #000;
            line-height: 1.8;
        }

        .exam-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #000;
            padding-bottom: 20px;
        }

        .exam-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .exam-info {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14px;
        }

        .student-info {
            display: flex;
            gap: 30px;
        }

        .student-info span {
            display: inline-block;
            min-width: 150px;
            border-bottom: 1px solid #000;
        }

        .exam-section {
            margin-bottom: 30px;
            margin-top: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            padding: 5px 10px;
            background-color: #f0f0f0;
            border-left: 4px solid #333;
        }

        .exam-problem {
            margin-bottom: 25px;
            padding-left: 20px;
            page-break-inside: avoid;
        }

        .problem-number {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .problem-content {
            margin-bottom: 15px;
            line-height: 2;
        }

        .answer-space {
            height: 60px;
            border: 1px solid #ccc;
            margin-top: 10px;
            padding: 10px;
            background-color: #fafafa;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1001;
        }

        .print-button:hover {
            background-color: #34495e;
        }

        /* 교재 스타일 - 문제 상세 표시 */
        .problem-detail-container {
            margin-bottom: 20px;
        }

        .textbook-problem-box {
            margin-bottom: 30px;
            padding: 25px;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #3498db;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .textbook-problem-label {
            font-size: 14px;
            font-weight: 600;
            color: #3498db;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .textbook-problem-content {
            font-size: 17px;
            line-height: 1.85;
            color: #2c3e50;
            margin-bottom: 15px;
            word-break: keep-all;
            white-space: pre-line;
        }

        .textbook-solution-box {
            padding: 25px;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #27ae60;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .textbook-solution-label {
            font-size: 14px;
            font-weight: 600;
            color: #27ae60;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .textbook-solution-content {
            font-size: 16px;
            line-height: 1.85;
            color: #2c3e50;
            word-break: keep-all;
            white-space: pre-line;
        }

        .textbook-choices-container {
            margin-top: 20px;
            padding: 15px 0;
        }

        .textbook-choice-item {
            margin: 10px 0;
            padding: 12px 15px;
            font-size: 16px;
            line-height: 1.75;
            color: #2c3e50;
            background-color: #ffffff;
            border-left: 3px solid #95a5a6;
            border-radius: 2px;
            transition: all 0.2s;
        }

        .textbook-choice-item:hover {
            background-color: #f8f9fa;
            border-left-color: #3498db;
        }

        .textbook-problem-image,
        .textbook-solution-image {
            margin: 20px 0;
            text-align: center;
        }

        .textbook-problem-image img,
        .textbook-solution-image img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* 교재 스타일 - 시험지 인쇄 */
        .textbook-exam-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 30px;
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ffffff;
        }

        .textbook-exam-problem {
            margin-bottom: 35px;
            page-break-inside: avoid;
            padding-bottom: 20px;
            border-bottom: 1px solid #e8e8e8;
        }

        .textbook-exam-problem:last-child {
            border-bottom: none;
        }

        .textbook-problem-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .textbook-problem-number {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            min-width: 35px;
            padding-top: 2px;
        }

        .textbook-problem-body {
            flex: 1;
        }

        .textbook-problem-text {
            font-size: 17px;
            line-height: 1.85;
            color: #2c3e50;
            margin-bottom: 15px;
            word-break: keep-all;
            white-space: pre-line;
        }

        /* 교재 스타일 - 해설지 인쇄 */
        .textbook-solution-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 30px;
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ffffff;
        }

        .textbook-solution-title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 40px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }

        .textbook-solution-problem {
            margin-bottom: 40px;
            page-break-inside: avoid;
            padding-bottom: 25px;
            border-bottom: 1px solid #e8e8e8;
        }

        .textbook-solution-problem:last-child {
            border-bottom: none;
        }

        .textbook-solution-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .textbook-solution-number {
            font-size: 18px;
            font-weight: 700;
            color: #27ae60;
            min-width: 35px;
            padding-top: 2px;
        }

        .textbook-solution-body {
            flex: 1;
        }

        .textbook-solution-text {
            font-size: 16px;
            line-height: 1.85;
            color: #2c3e50;
            word-break: keep-all;
            margin-top: 10px;
            white-space: pre-line;
        }

        /* 인쇄 스타일 */
        @media print {
            body * {
                visibility: hidden;
            }
            .exam-content,
            .exam-content *,
            .textbook-exam-container,
            .textbook-exam-container *,
            .textbook-solution-container,
            .textbook-solution-container * {
                visibility: visible;
            }
            .exam-content,
            .textbook-exam-container,
            .textbook-solution-container {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
                box-shadow: none;
                width: 100%;
            }
            .print-button,
            .close {
                display: none !important;
            }
            .textbook-exam-problem,
            .textbook-solution-problem {
                page-break-inside: avoid;
                margin-bottom: 30px;
            }
            .textbook-problem-text,
            .textbook-solution-text,
            .textbook-problem-content,
            .textbook-solution-content {
                font-size: 15pt;
                line-height: 1.8;
                white-space: pre-line;
            }
            .textbook-choice-item {
                font-size: 14pt;
                page-break-inside: avoid;
            }
        } 

        /* 반응형 디자인 */ 
        @media (max-width: 1200px) {
            .left-column {
                width: 300px;
                min-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            .left-column {
                width: 100%;
                min-width: 100%;
                max-height: 40vh;
            }
            .center-column {
                width: 100%;
                min-width: 100%;
                max-width: 100%;
                max-height: 30vh;
                flex-direction: row;
                overflow-x: auto;
            }
            .problem-list-section {
                min-width: 150px;
            }
            .right-column {
                width: 100%;
                max-height: 30vh;
            }
        } 
    </style> 
</head>  
<body>
    <div class="container">
        <div class="header-section"> 
            <h1>KTM 유형별 문제은행</h1> 
            <div style="display: flex; gap: 10px;">
                <button class="exam-button" onclick="printSelectedProblems()" style="background-color: #e67e22;">
                    🖨️ 시험지 인쇄
                </button>
                <button class="exam-button" onclick="printSolutionSheet()" style="background-color: #27ae60;">
                    📖 해설지 인쇄
                </button>
            </div>
        </div>
        
        <!-- 메인 콘텐츠: 3칼럼 레이아웃 -->
        <div class="main-content">
            <!-- 좌측 칼럼: 대표유형 + 유형 분석 -->
            <div class="left-column">
                <!-- 대표유형 -->
                <div class="card">
                    <h2 class="card-header">📋 대표유형</h2>
                    <img src="<?php echo $imgSrc; ?>" alt="대표유형 이미지" style="width: 100%; height: auto; border-radius: 8px; margin-bottom: 15px;">
                </div>
                
                <!-- 유형 분석글 -->
                <div class="card">
                    <h2 class="card-header">📊 유형 분석</h2>
                    <div class="analysis-text" id="analysisText" 
                         ondblclick="enableAnalysisEdit()" 
                         title="더블클릭하여 수정">
                        <?php 
                        // DB에서 analysis 필드 가져오기
                        $analysisText = '';
                        if ($cnttype == 1 && isset($cnttext)) {
                            // analysis 필드 확인
                            $columns = $DB->get_columns('icontent_pages');
                            $has_analysis_field = false;
                            foreach ($columns as $column) {
                                if ($column->name === 'analysis') {
                                    $has_analysis_field = true;
                                    break;
                                }
                            }
                            
                            if ($has_analysis_field && !empty($cnttext->analysis)) {
                                $analysisText = $cnttext->analysis;
                            } elseif (!empty($cnttext->reflections0)) {
                                // analysis 필드가 없으면 reflections0 사용
                                $analysisText = $cnttext->reflections0;
                            }
                        }
                        
                        // 유형 분석이 없으면 안내 메시지 표시
                        if (empty($analysisText)) {
                            $analysisText = '<em style="color: #95a5a6;">유형 분석이 없습니다. 더블클릭하여 입력하세요.</em>';
                        }
                        
                        echo $analysisText;
                        ?>
                    </div>
                </div>
            </div>

            <!-- 중앙 칼럼: 문제 목록 (100px 폭) -->
            <div class="center-column">
                <div class="problem-list-section">
                    <div class="problem-list-title">유사문제</div>
                    <div class="problem-list-items" id="similarProblemsList">
                        <!-- 유사문제들이 동적으로 로드됩니다 -->
                    </div>
                </div>
                <div class="problem-list-section">
                    <div class="problem-list-title">변형문제</div>
                    <div class="problem-list-items" id="modifiedProblemsList">
                        <!-- 변형문제들이 동적으로 로드됩니다 -->
                    </div>
                </div>
                <div style="margin-top: auto; padding-top: 15px; border-top: 1px solid #ddd;">
                    <button class="add-button similar compact" onclick="addSimilarProblem()" style="width: 100%; margin-bottom: 5px; font-size: 11px; padding: 6px;" title="유사문제 추가">
                        <span>➕ 유사</span>
                        <div class="loading" id="similarLoading"></div>
                    </button>
                    <button class="add-button variant compact" onclick="addModifiedProblem()" style="width: 100%; font-size: 11px; padding: 6px;" title="변형문제 추가">
                        <span>➕ 변형</span>
                        <div class="loading" id="variantLoading"></div>
                    </button>
                    
                    <!-- API 설정 정보 표시 (동적) -->
                    <div style="margin-top: 10px; padding: 8px; background-color: #f8f9fa; border-radius: 5px; font-size: 9px; color: #6c757d; line-height: 1.4;">
                        <div style="font-weight: bold; margin-bottom: 4px; color: #495057;">🤖 생성 설정</div>
                        <div>모델: <strong style="color: #2c3e50;"><?php echo htmlspecialchars($aiModel); ?></strong></div>
                        <div>Temperature: <strong style="color: #2c3e50;"><?php echo htmlspecialchars($aiTemperature); ?></strong></div>
                        <div>Max Tokens: <strong style="color: #2c3e50;"><?php echo htmlspecialchars($aiMaxTokens); ?></strong></div>
                    </div>
                </div>
            </div>

            <!-- 우측 칼럼: 선택된 문제 상세 -->
            <div class="right-column" id="problemDetailColumn">
                <div class="problem-detail-placeholder">
                    문제를 클릭하면 상세 정보가 여기에 표시됩니다.
                </div>
            </div>
        </div>
    </div>

    <!-- 일반 모달 -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">새 문제 생성</h3>
            <div class="modal-problem">
                <p id="modalMessage"></p>
            </div>
        </div> 
    </div>    
    
    <!-- 프롬프트 입력 모달 -->
    <div id="promptModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePromptModal()">&times;</span>
            <h3>유사문제 생성 프롬프트</h3>
            <div style="margin: 20px 0;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">추가 지시사항을 입력하세요:</label>
                <textarea id="promptInput" style="width: 100%; height: 150px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;" 
                    placeholder="예: 난이도를 높여줘, 삼각함수를 포함해줘, 실생활 예제로 만들어줘 등..."></textarea>
                <div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 5px; font-size: 13px; color: #666;">
                    <strong>선택된 문제:</strong> <span id="selectedProblemInfo">-</span>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closePromptModal()" style="padding: 10px 20px; background-color: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer;">취소</button>
                <button onclick="generateWithPrompt()" style="padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">생성 후 교체</button>
            </div>
        </div>
    </div>
    
    <!-- JSON 입력 모달 -->
    <div id="jsonModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeJsonModal()">&times;</span>
            <h3>문제 JSON 입력</h3>
            <div style="margin: 20px 0;">
                <label for="jsonInput" style="display: block; margin-bottom: 10px; font-weight: 600;">JSON 데이터를 입력하세요:</label>
                <textarea id="jsonInput" style="width: 100%; height: 350px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; font-size: 14px;" placeholder='{
  "문항": "문제 내용 (LaTeX 수식: $x^2$ 또는 \\(x^2\\) 형식 사용 가능)",
  "선택지": [
    "① 선택지 1",
    "② 선택지 2",
    "③ 선택지 3",
    "④ 선택지 4",
    "⑤ 선택지 5"
  ],
  "해설": "해설 내용 (LaTeX 수식: $x^2$ 또는 \\(x^2\\) 형식 사용 가능)"
}'></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeJsonModal()" style="padding: 10px 20px; background-color: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer;">취소</button>
                <button onclick="saveJsonProblem()" style="padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">저장</button>
            </div>
        </div>
    </div>

    <!-- 문제 상세 정보 모달 -->
    <div id="problemDetailModal" class="modal">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <span class="close" onclick="closeProblemDetailModal()">&times;</span>
            <h3>문제 상세 정보</h3>
            <div style="margin: 20px 0;">
                <div class="textbook-problem-box" style="margin-bottom: 30px;">
                    <div class="textbook-problem-label">문제</div>
                    <div id="problemQuestion" contenteditable="true" class="textbook-problem-content" 
                         style="border: 2px solid transparent; transition: border-color 0.3s; min-height: 100px;" 
                         onfocus="this.style.borderColor='#3498db'; this.style.backgroundColor='#ffffff';" 
                         onblur="this.style.borderColor='transparent'; this.style.backgroundColor='transparent';"></div>
                    <div id="problemChoices" contenteditable="true" style="border: 2px solid transparent; transition: border-color 0.3s;"
                         onfocus="this.style.borderColor='#3498db'" 
                         onblur="this.style.borderColor='transparent'"></div>
                    <div id="problemQuestionImage" class="textbook-problem-image"></div>
                </div>
                <div class="textbook-solution-box">
                    <div class="textbook-solution-label">해설</div>
                    <div id="problemSolution" contenteditable="true" class="textbook-solution-content" 
                         style="border: 2px solid transparent; transition: border-color 0.3s; min-height: 100px;"
                         onfocus="this.style.borderColor='#27ae60'; this.style.backgroundColor='#ffffff';" 
                         onblur="this.style.borderColor='transparent'; this.style.backgroundColor='transparent';"></div>
                    <div id="problemSolutionImage" class="textbook-solution-image"></div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: space-between;">
                    <button onclick="showJsonEditor()" style="padding: 10px 20px; background-color: #f39c12; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">📝 JSON 교체</button>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="saveProblemChanges()" style="padding: 10px 20px; background-color: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">💾 저장</button>
                        <button onclick="closeProblemDetailModal()" style="padding: 10px 20px; background-color: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">취소</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 시험지 모달 -->
    <div id="examModal" class="exam-modal">
        <button class="print-button" onclick="printExam()">🖨️ 인쇄하기</button>
        <span class="close" style="position: fixed; top: 20px; left: 20px; z-index: 1001; color: white; font-size: 40px;" onclick="closeExamModal()">&times;</span>
        <div class="exam-content">
            <div class="exam-paper" id="examPaper">
                <!-- 시험지 내용이 여기 동적으로 생성됩니다 -->
            </div>
        </div>
    </div>

    <script>
        // PHP 변수를 JavaScript로 전달
        const PHP_VARS = {
            cntid: '<?php echo $cntid; ?>',
            cnttype: '<?php echo $cnttype; ?>',
            userid: '<?php echo $USER->id; ?>'
        };

        // 원본 문제 정보
        const originalProblem = {
            type: "수열의 규칙성 찾기",
            pattern: "등비수열",
            example: "2, 4, 8, 16, ?",
            answer: "32",
            difficulty: "중급"
        };

        // 페이지 로드 시 문제들 불러오기
        window.addEventListener('DOMContentLoaded', async function() {
            // 테이블 구조 확인
            await checkTableStructure();
            await loadProblems();
        });
        
        // 테이블 구조 확인 함수
        async function checkTableStructure() {
            const formData = new FormData();
            formData.append('action', 'check_table');
            
            try {
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                console.log('Table structure:', result);
                
                if (!result.has_type_field) {
                    console.error('WARNING: type field not found in database table!');
                    alert('경고: 데이터베이스 테이블에 type 필드가 없습니다. 관리자에게 문의하세요.');
                } else {
                    console.log('Type field info:', result.type_field);
                }
            } catch (e) {
                console.error('Failed to check table structure:', e);
            }
        }

        // DB에서 문제들 불러오기
        async function loadProblems() {
            const formData = new FormData();
            formData.append('action', 'load_problems');
            formData.append('cntid', '<?php echo $cntid; ?>');
            formData.append('cnttype', '<?php echo $cnttype; ?>');
            try {
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                // 응답이 JSON인지 확인
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    return;
                }
                
                const result = await response.json();
                
                if (result.success && result.problems) {
                    let similarCount = 1;
                    let modifiedCount = 1;
                    
                    result.problems.forEach(problem => {
                        const problemType = problem.type || 'similar';
                        const problemData = {
                            id: problem.id,
                            number: problemType === 'similar' ? similarCount++ : modifiedCount++,
                            inputanswer: problem.inputanswer,
                            question: problem.question,
                            solution: problem.solution,
                            type: problemType
                        };
                        
                        // 타입에 따라 적절한 리스트에 추가
                        if (problemType === 'similar') {
                            addProblemBlock('similarProblemsList', problemData);
                        } else {
                            addProblemBlock('modifiedProblemsList', problemData);
                        }
                    });
                }
            } catch (e) {
                console.error('문제 로드 중 오류:', e);
            }
        }

        // 현재 문제 타입 저장
        window.currentProblemType = '';

        // 선택된 문제 블록 저장
        let selectedProblemBlock = null;
        
        // 문제 선택 해제 함수
        function deselectProblem() {
            document.querySelectorAll('.problem-block.selected').forEach(item => {
                item.classList.remove('selected');
            });
            selectedProblemBlock = null;
        }
        
        // 문서 전체 클릭 이벤트: 문제 블록이 아닌 곳 클릭 시 선택 해제
        document.addEventListener('click', function(e) {
            // 문제 블록이나 버튼이 아닌 곳 클릭 시 선택 해제
            const clickedElement = e.target;
            const isProblemBlock = clickedElement.closest('.problem-block');
            const isButton = clickedElement.closest('button');
            const isModal = clickedElement.closest('.modal');
            
            if (!isProblemBlock && !isButton && !isModal) {
                deselectProblem();
            }
        });
        
        // 유사문제 추가 (OpenAI API 자동 생성)
        async function addSimilarProblem() {
            window.currentProblemType = 'similar';
            
            // 선택된 문제가 있으면 교체 모드
            const currentSelectedBlock = selectedProblemBlock;
            const isReplaceMode = currentSelectedBlock && currentSelectedBlock.classList.contains('similar');
            
            // 선택된 문제가 있지만 유사문제가 아니면 프롬프트 입력 팝업 표시
            if (currentSelectedBlock && !isReplaceMode) {
                showPromptModal('similar');
                return;
            }
            
            // 교체 모드일 때 확인 다이얼로그 표시
            let shouldReplace = false;
            if (isReplaceMode) {
                const confirmReplace = confirm('선택한 유사문제를 새로 생성된 문제로 교체하시겠습니까?');
                if (!confirmReplace) {
                    return; // 사용자가 취소하면 중단
                }
                shouldReplace = true;
            }
            
            // 로딩 표시
            const loadingDiv = document.getElementById('similarLoading');
            if (loadingDiv) {
                loadingDiv.style.display = 'inline-block';
            }
            
            // 버튼 비활성화
            const button = event.target.closest('button');
            if (button) {
                button.disabled = true;
            }
            
            try {
                // OpenAI API를 통한 자동 생성
                const formData = new FormData();
                formData.append('action', 'generate_similar');
                formData.append('cntid', PHP_VARS.cntid);
                formData.append('cnttype', PHP_VARS.cnttype);
                formData.append('problemType', 'similar');
                
                // 원본 이미지 URL이 있으면 전달 (선택적)
                const imgElement = document.querySelector('.left-column .card img');
                const imgSrc = imgElement ? imgElement.src : null;
                if (imgSrc && imgSrc !== 'undefined' && imgSrc.trim() !== '') {
                    formData.append('imageUrl', imgSrc);
                    console.log('Image URL 전달:', imgSrc);
                } else {
                    console.warn('이미지 URL을 찾을 수 없습니다.');
                }
                
                // 유형 분석 텍스트 전달 (안내 메시지 제외)
                const analysisText = document.getElementById('analysisText');
                if (analysisText) {
                    const text = analysisText.innerText.trim();
                    // 안내 메시지가 아닌 실제 유형 분석만 전달
                    if (text && !text.includes('유형 분석이 없습니다')) {
                        formData.append('analysisText', text);
                        console.log('유형 분석 텍스트 전달됨');
                    } else {
                        console.warn('유형 분석 텍스트가 없습니다.');
                    }
                }
                
                console.log('Generating similar problems via OpenAI API...');
                
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                console.log('Generation result:', result);
                
                if (result.success && result.problems) {
                    // 선택된 문제가 있고 유사문제이면 교체
                    if (shouldReplace && currentSelectedBlock && result.problems.length > 0) {
                        const problemToReplace = result.problems[0];
                        const problemId = currentSelectedBlock.getAttribute('data-id');
                        
                        console.log('교체 모드: 문제 ID', problemId);
                        
                        // 서버에서 문제 업데이트
                        const updateFormData = new FormData();
                        updateFormData.append('action', 'update_problem');
                        updateFormData.append('id', problemId);
                        updateFormData.append('question', problemToReplace.question);
                        updateFormData.append('solution', problemToReplace.solution);
                        if (problemToReplace.choices && problemToReplace.choices.length > 0) {
                            updateFormData.append('choices', JSON.stringify(problemToReplace.choices));
                        }
                        
                        try {
                            const updateResponse = await fetch('patternbank_ajax.php', {
                                method: 'POST',
                                body: updateFormData,
                                credentials: 'same-origin'
                            });
                            const updateResult = await updateResponse.json();
                            
                            if (updateResult.success) {
                                // 블록의 데이터 속성 업데이트
                                currentSelectedBlock.setAttribute('data-question', problemToReplace.question);
                                currentSelectedBlock.setAttribute('data-solution', problemToReplace.solution);
                                currentSelectedBlock.setAttribute('data-inputanswer', problemToReplace.choices ? JSON.stringify(problemToReplace.choices) : '');
                                
                                // 우측 칼럼 내용도 업데이트
                                await showProblemDetailInColumn(problemId);
                                
                                // 선택 해제
                                deselectProblem();
                                
                                alert('문제가 성공적으로 교체되었습니다.');
                            } else {
                                throw new Error(updateResult.message || '업데이트 실패');
                            }
                        } catch (updateError) {
                            console.error('문제 교체 중 오류:', updateError);
                            alert('문제 교체 중 오류가 발생했습니다: ' + updateError.message);
                        }
                    } else {
                        // 생성된 문제들을 UI에 추가
                        const grid = document.getElementById('similarProblemsList');
                        const startNumber = grid.children.length + 1;
                        
                        result.problems.forEach((problem, index) => {
                            addProblemBlock('similarProblemsList', {
                                id: problem.id,
                                number: startNumber + index,
                                question: problem.question,
                                solution: problem.solution,
                                inputanswer: problem.choices ? JSON.stringify(problem.choices) : '',
                                type: 'similar'
                            });
                        });
                        
                        // MathJax 렌더링
                        await renderMath(document.getElementById('similarProblemsList'));
                        
                        // 성공 메시지
                        let successMsg = result.message || `${result.problems.length}개의 유사문제가 생성되었습니다.`;
                        
                        // 토큰 제한으로 잘린 경우 경고 추가
                        if (result.is_truncated || result.warning) {
                            successMsg += '\n\n⚠️ 경고: ' + (result.warning || '응답이 토큰 제한으로 인해 잘렸을 수 있습니다.');
                        }
                        
                        alert(successMsg);
                    }
                    
                } else {
                    // 실패 시 오류 메시지 표시
                    console.error('Generation failed:', result);
                    let errorMessage = '❌ 문제 생성 실패\n\n';
                    
                    // 오류 타입별 상세 메시지 구성
                    const errorType = result.error_type || 'unknown_error';
                    const errorDetails = result.error_details || {};
                    
                    // 기본 오류 메시지
                    errorMessage += '오류 내용: ' + (result.error || '알 수 없는 오류') + '\n\n';
                    
                    // 오류 타입별 상세 정보 추가
                    if (errorType === 'network_error') {
                        errorMessage += '🔴 실패 원인: 네트워크 연결 오류\n';
                        errorMessage += '   - API 서버에 연결할 수 없습니다\n';
                        errorMessage += '   - 네트워크 연결을 확인해주세요\n';
                        if (errorDetails.message) {
                            errorMessage += `   - 상세: ${errorDetails.message}\n`;
                        }
                    } else if (errorType === 'api_error' || errorType === 'token_error') {
                        errorMessage += '🔴 실패 원인: API 오류';
                        if (result.is_token_error) {
                            errorMessage += ' (최대 토큰 수 초과)\n';
                            errorMessage += `   - 현재 Max Tokens 설정: ${result.max_tokens || 2000}\n`;
                            errorMessage += '   - 응답이 너무 길어서 잘렸을 수 있습니다\n\n';
                            errorMessage += '해결 방법:\n';
                            errorMessage += '1. 설정 파일에서 max_tokens 값을 늘려주세요\n';
                            errorMessage += '2. 또는 프롬프트를 단축해주세요';
                        } else {
                            errorMessage += '\n';
                            if (result.http_code) {
                                errorMessage += `   - HTTP 상태 코드: ${result.http_code}\n`;
                            }
                            if (errorDetails.code) {
                                errorMessage += `   - 오류 코드: ${errorDetails.code}\n`;
                            }
                            if (errorDetails.message) {
                                errorMessage += `   - 상세: ${errorDetails.message}\n`;
                            }
                        }
                    } else if (errorType === 'parsing_error') {
                        errorMessage += '🔴 실패 원인: 응답 파싱 오류\n';
                        errorMessage += '   - API 응답을 JSON 형식으로 파싱하는 중 오류 발생\n';
                        if (result.is_truncated) {
                            errorMessage += '   - 응답이 토큰 제한으로 잘렸을 수 있습니다\n';
                        }
                        if (errorDetails.message) {
                            errorMessage += `   - 상세: ${errorDetails.message}\n`;
                        }
                    } else if (errorType === 'validation_error') {
                        errorMessage += '🔴 실패 원인: 데이터 검증 오류\n';
                        errorMessage += '   - 생성된 문제 형식이 올바르지 않습니다\n';
                        if (errorDetails.missing_fields && errorDetails.missing_fields.length > 0) {
                            errorMessage += `   - 누락된 필드: ${errorDetails.missing_fields.join(', ')}\n`;
                        }
                        if (result.is_truncated) {
                            errorMessage += '   - 응답이 토큰 제한으로 잘렸을 수 있습니다\n';
                        }
                    } else if (errorType === 'database_error') {
                        errorMessage += '🔴 실패 원인: 데이터베이스 오류\n';
                        errorMessage += '   - 데이터베이스에 저장하는 중 오류가 발생했습니다\n';
                        if (errorDetails.errors && errorDetails.errors.length > 0) {
                            errorMessage += `   - 상세 오류:\n`;
                            errorDetails.errors.forEach(err => {
                                errorMessage += `     • ${err}\n`;
                            });
                        }
                        if (errorDetails.message) {
                            errorMessage += `   - ${errorDetails.message}\n`;
                        }
                    } else {
                        errorMessage += '🔴 실패 원인: 알 수 없는 오류\n';
                        if (errorDetails.description) {
                            errorMessage += `   - ${errorDetails.description}\n`;
                        }
                    }
                    
                    // 오류 코드가 있으면 추가
                    if (result.error_code) {
                        errorMessage += `\n오류 코드: ${result.error_code}`;
                    }
                    
                    alert(errorMessage);
                    
                    // 토큰 오류가 아니면 수동 입력 모달로 폴백
                    if (!result.is_token_error && errorType !== 'network_error') {
                        document.getElementById('jsonModal').style.display = 'block';
                        document.getElementById('jsonInput').value = '';
                    }
                }
                
            } catch (error) {
                console.error('Error generating similar problems:', error);
                let errorMessage = '❌ 문제 생성 중 오류 발생\n\n';
                errorMessage += '오류 내용: ' + (error.message || '알 수 없는 오류') + '\n\n';
                
                if (error.message && error.message.includes('token')) {
                    errorMessage += '🔴 실패 원인: 최대 토큰 수 초과\n';
                    errorMessage += '   - 설정 파일에서 max_tokens 값을 늘려주세요.';
                } else if (error.message && (error.message.includes('network') || error.message.includes('CURL'))) {
                    errorMessage += '🔴 실패 원인: 네트워크 연결 오류\n';
                    errorMessage += '   - 네트워크 연결을 확인해주세요.';
                } else {
                    errorMessage += '🔴 실패 원인: 알 수 없는 오류\n';
                    errorMessage += '   - 수동 입력 모드로 전환합니다.';
                }
                alert(errorMessage);
                
                // 수동 입력 모달 표시
                document.getElementById('jsonModal').style.display = 'block';
                document.getElementById('jsonInput').value = '';
                
            } finally {
                // 로딩 숨기기
                if (loadingDiv) {
                    loadingDiv.style.display = 'none';
                }
                // 버튼 활성화
                if (button) {
                    button.disabled = false;
                }
            }
        }

        // 변형문제 추가 (OpenAI API 자동 생성)
        async function addModifiedProblem() {
            window.currentProblemType = 'modified';
            
            // 선택된 문제가 있으면 교체 모드
            const currentSelectedBlock = selectedProblemBlock;
            const isReplaceMode = currentSelectedBlock && currentSelectedBlock.classList.contains('modified');
            
            // 선택된 문제가 있지만 변형문제가 아니면 프롬프트 입력 팝업 표시
            if (currentSelectedBlock && !isReplaceMode) {
                showPromptModal('modified');
                return;
            }
            
            // 교체 모드일 때 확인 다이얼로그 표시
            let shouldReplace = false;
            if (isReplaceMode) {
                const confirmReplace = confirm('선택한 변형문제를 새로 생성된 문제로 교체하시겠습니까?');
                if (!confirmReplace) {
                    return; // 사용자가 취소하면 중단
                }
                shouldReplace = true;
            }
            
            // 로딩 표시
            const loadingDiv = document.getElementById('variantLoading');
            if (loadingDiv) {
                loadingDiv.style.display = 'inline-block';
            }
            
            // 버튼 비활성화
            const button = event.target.closest('button');
            if (button) {
                button.disabled = true;
            }
            
            try {
                // OpenAI API를 통한 자동 생성
                const formData = new FormData();
                formData.append('action', 'generate_similar');
                formData.append('cntid', PHP_VARS.cntid);
                formData.append('cnttype', PHP_VARS.cnttype);
                formData.append('problemType', 'modified');
                
                // 원본 이미지 URL이 있으면 전달 (선택적)
                const imgElement = document.querySelector('.left-column .card img');
                const imgSrc = imgElement ? imgElement.src : null;
                if (imgSrc && imgSrc !== 'undefined' && imgSrc.trim() !== '') {
                    formData.append('imageUrl', imgSrc);
                    console.log('Image URL 전달:', imgSrc);
                } else {
                    console.warn('이미지 URL을 찾을 수 없습니다.');
                }
                
                // 유형 분석 텍스트 전달 (안내 메시지 제외)
                const analysisText = document.getElementById('analysisText');
                if (analysisText) {
                    const text = analysisText.innerText.trim();
                    // 안내 메시지가 아닌 실제 유형 분석만 전달
                    if (text && !text.includes('유형 분석이 없습니다')) {
                        formData.append('analysisText', text);
                        console.log('유형 분석 텍스트 전달됨');
                    } else {
                        console.warn('유형 분석 텍스트가 없습니다.');
                    }
                }
                
                console.log('Generating modified problems via OpenAI API...');
                
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                console.log('Generation result:', result);
                
                if (result.success && result.problems) {
                    // 선택된 문제가 있고 변형문제이면 교체
                    if (shouldReplace && currentSelectedBlock && result.problems.length > 0) {
                        const problemToReplace = result.problems[0];
                        const problemId = currentSelectedBlock.getAttribute('data-id');
                        
                        console.log('교체 모드: 문제 ID', problemId);
                        
                        // 서버에서 문제 업데이트
                        const updateFormData = new FormData();
                        updateFormData.append('action', 'update_problem');
                        updateFormData.append('id', problemId);
                        updateFormData.append('question', problemToReplace.question);
                        updateFormData.append('solution', problemToReplace.solution);
                        if (problemToReplace.choices && problemToReplace.choices.length > 0) {
                            updateFormData.append('choices', JSON.stringify(problemToReplace.choices));
                        }
                        
                        try {
                            const updateResponse = await fetch('patternbank_ajax.php', {
                                method: 'POST',
                                body: updateFormData,
                                credentials: 'same-origin'
                            });
                            const updateResult = await updateResponse.json();
                            
                            if (updateResult.success) {
                                // 블록의 데이터 속성 업데이트
                                currentSelectedBlock.setAttribute('data-question', problemToReplace.question);
                                currentSelectedBlock.setAttribute('data-solution', problemToReplace.solution);
                                currentSelectedBlock.setAttribute('data-inputanswer', problemToReplace.choices ? JSON.stringify(problemToReplace.choices) : '');
                                
                                // 우측 칼럼 내용도 업데이트
                                await showProblemDetailInColumn(problemId);
                                
                                // 선택 해제
                                deselectProblem();
                                
                                alert('문제가 성공적으로 교체되었습니다.');
                            } else {
                                throw new Error(updateResult.message || '업데이트 실패');
                            }
                        } catch (updateError) {
                            console.error('문제 교체 중 오류:', updateError);
                            alert('문제 교체 중 오류가 발생했습니다: ' + updateError.message);
                        }
                    } else {
                        // 생성된 문제들을 UI에 추가
                        const grid = document.getElementById('modifiedProblemsList');
                        const startNumber = grid.children.length + 1;
                        
                        result.problems.forEach((problem, index) => {
                            addProblemBlock('modifiedProblemsList', {
                                id: problem.id,
                                number: startNumber + index,
                                question: problem.question,
                                solution: problem.solution,
                                inputanswer: problem.choices ? JSON.stringify(problem.choices) : '',
                                type: 'modified'
                            });
                        });
                        
                        // MathJax 렌더링
                        await renderMath(document.getElementById('modifiedProblemsList'));
                        
                        // 성공 메시지
                        let successMsg = result.message || `${result.problems.length}개의 변형문제가 생성되었습니다.`;
                        
                        // 토큰 제한으로 잘린 경우 경고 추가
                        if (result.is_truncated || result.warning) {
                            successMsg += '\n\n⚠️ 경고: ' + (result.warning || '응답이 토큰 제한으로 인해 잘렸을 수 있습니다.');
                        }
                        
                        alert(successMsg);
                    }
                    
                } else {
                    // 실패 시 오류 메시지 표시
                    console.error('Generation failed:', result);
                    let errorMessage = '❌ 문제 생성 실패\n\n';
                    
                    // 오류 타입별 상세 메시지 구성
                    const errorType = result.error_type || 'unknown_error';
                    const errorDetails = result.error_details || {};
                    
                    // 기본 오류 메시지
                    errorMessage += '오류 내용: ' + (result.error || '알 수 없는 오류') + '\n\n';
                    
                    // 오류 타입별 상세 정보 추가
                    if (errorType === 'network_error') {
                        errorMessage += '🔴 실패 원인: 네트워크 연결 오류\n';
                        errorMessage += '   - API 서버에 연결할 수 없습니다\n';
                        errorMessage += '   - 네트워크 연결을 확인해주세요\n';
                        if (errorDetails.message) {
                            errorMessage += `   - 상세: ${errorDetails.message}\n`;
                        }
                    } else if (errorType === 'api_error' || errorType === 'token_error') {
                        errorMessage += '🔴 실패 원인: API 오류';
                        if (result.is_token_error) {
                            errorMessage += ' (최대 토큰 수 초과)\n';
                            errorMessage += `   - 현재 Max Tokens 설정: ${result.max_tokens || 2000}\n`;
                            errorMessage += '   - 응답이 너무 길어서 잘렸을 수 있습니다\n\n';
                            errorMessage += '해결 방법:\n';
                            errorMessage += '1. 설정 파일에서 max_tokens 값을 늘려주세요\n';
                            errorMessage += '2. 또는 프롬프트를 단축해주세요';
                        } else {
                            errorMessage += '\n';
                            if (result.http_code) {
                                errorMessage += `   - HTTP 상태 코드: ${result.http_code}\n`;
                            }
                            if (errorDetails.code) {
                                errorMessage += `   - 오류 코드: ${errorDetails.code}\n`;
                            }
                            if (errorDetails.message) {
                                errorMessage += `   - 상세: ${errorDetails.message}\n`;
                            }
                        }
                    } else if (errorType === 'parsing_error') {
                        errorMessage += '🔴 실패 원인: 응답 파싱 오류\n';
                        errorMessage += '   - API 응답을 JSON 형식으로 파싱하는 중 오류 발생\n';
                        if (result.is_truncated) {
                            errorMessage += '   - 응답이 토큰 제한으로 잘렸을 수 있습니다\n';
                        }
                        if (errorDetails.message) {
                            errorMessage += `   - 상세: ${errorDetails.message}\n`;
                        }
                    } else if (errorType === 'validation_error') {
                        errorMessage += '🔴 실패 원인: 데이터 검증 오류\n';
                        errorMessage += '   - 생성된 문제 형식이 올바르지 않습니다\n';
                        if (errorDetails.missing_fields && errorDetails.missing_fields.length > 0) {
                            errorMessage += `   - 누락된 필드: ${errorDetails.missing_fields.join(', ')}\n`;
                        }
                        if (result.is_truncated) {
                            errorMessage += '   - 응답이 토큰 제한으로 잘렸을 수 있습니다\n';
                        }
                    } else if (errorType === 'database_error') {
                        errorMessage += '🔴 실패 원인: 데이터베이스 오류\n';
                        errorMessage += '   - 데이터베이스에 저장하는 중 오류가 발생했습니다\n';
                        if (errorDetails.errors && errorDetails.errors.length > 0) {
                            errorMessage += `   - 상세 오류:\n`;
                            errorDetails.errors.forEach(err => {
                                errorMessage += `     • ${err}\n`;
                            });
                        }
                        if (errorDetails.message) {
                            errorMessage += `   - ${errorDetails.message}\n`;
                        }
                    } else {
                        errorMessage += '🔴 실패 원인: 알 수 없는 오류\n';
                        if (errorDetails.description) {
                            errorMessage += `   - ${errorDetails.description}\n`;
                        }
                    }
                    
                    // 오류 코드가 있으면 추가
                    if (result.error_code) {
                        errorMessage += `\n오류 코드: ${result.error_code}`;
                    }
                    
                    alert(errorMessage);
                    
                    // 토큰 오류가 아니면 수동 입력 모달로 폴백
                    if (!result.is_token_error && errorType !== 'network_error') {
                        document.getElementById('jsonModal').style.display = 'block';
                        document.getElementById('jsonInput').value = '';
                    }
                }
                
            } catch (error) {
                console.error('Error generating modified problems:', error);
                let errorMessage = '❌ 문제 생성 중 오류 발생\n\n';
                errorMessage += '오류 내용: ' + (error.message || '알 수 없는 오류') + '\n\n';
                
                if (error.message && error.message.includes('token')) {
                    errorMessage += '🔴 실패 원인: 최대 토큰 수 초과\n';
                    errorMessage += '   - 설정 파일에서 max_tokens 값을 늘려주세요.';
                } else if (error.message && error.message.includes('network') || error.message.includes('CURL')) {
                    errorMessage += '🔴 실패 원인: 네트워크 연결 오류\n';
                    errorMessage += '   - 네트워크 연결을 확인해주세요.';
                } else {
                    errorMessage += '🔴 실패 원인: 알 수 없는 오류\n';
                    errorMessage += '   - 수동 입력 모드로 전환합니다.';
                }
                alert(errorMessage);
                
                // 수동 입력 모달 표시
                document.getElementById('jsonModal').style.display = 'block';
                document.getElementById('jsonInput').value = '';
                
            } finally {
                // 로딩 숨기기
                if (loadingDiv) {
                    loadingDiv.style.display = 'none';
                }
                // 버튼 활성화
                if (button) {
                    button.disabled = false;
                }
            }
        }
 
        // 유사문제 생성 (시뮬레이션)
        function generateSimilarProblem() {
            const patterns = [
                { content: "7, 14, 28, 56, ?", answer: "112", difficulty: "하" },
                { content: "4, 8, 16, 32, ?", answer: "64", difficulty: "하" },
                { content: "6, 12, 24, 48, ?", answer: "96", difficulty: "하" },
                { content: "10, 20, 40, 80, ?", answer: "160", difficulty: "하" }
            ];
            return patterns[Math.floor(Math.random() * patterns.length)];
        }

        // 변형문제 생성 (시뮬레이션)
        function generateVariantProblem() {
            const patterns = [
                { content: "3, 4, 6, 10, 18, ?", answer: "34", difficulty: "상" },
                { content: "1, 1, 2, 3, 5, 8, ?", answer: "13", difficulty: "중" },
                { content: "2, 5, 10, 17, 26, ?", answer: "37", difficulty: "중" },
                { content: "1, 3, 7, 15, 31, ?", answer: "63", difficulty: "상" }
            ];
            return patterns[Math.floor(Math.random() * patterns.length)];
        }

        // 문제 블록 추가
        function addProblemBlock(gridId, problem) {
            const grid = document.getElementById(gridId);
            if (!grid) {
                console.error('Grid not found:', gridId);
                return;
            }
            
            const block = document.createElement('div');
            block.className = 'problem-block';
            
            // type에 따라 클래스 추가
            if (problem.type === 'modified') {
                block.classList.add('modified');
            } else {
                block.classList.add('similar');
            }
            
            block.setAttribute('data-id', problem.id);
            block.setAttribute('data-question', problem.question);
            block.setAttribute('data-solution', problem.solution);
            block.setAttribute('data-inputanswer', problem.inputanswer || '');
            block.setAttribute('data-type', problem.type || 'similar');
            
            // 문제 번호 표시 (짧게)
            block.innerHTML = `${problem.number}`;
            
            // 클릭 이벤트: 우측 칼럼에 상세 정보 표시
            block.addEventListener('click', function(e) {
                e.stopPropagation();
                // 이전 선택 제거
                document.querySelectorAll('.problem-block.selected').forEach(item => {
                    item.classList.remove('selected');
                });
                // 현재 선택
                this.classList.add('selected');
                selectedProblemBlock = this;
                // 우측 칼럼에 상세 정보 표시
                showProblemDetailInColumn(problem.id);
            });
            
            grid.appendChild(block);
        }

        // 우측 칼럼에 문제 상세 정보 표시
        async function showProblemDetailInColumn(problemId) {
            const column = document.getElementById('problemDetailColumn');
            if (!column) return;
            
            // 로딩 표시
            column.innerHTML = '<div style="text-align: center; padding: 40px; color: #95a5a6;">로딩 중...</div>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_problem');
                formData.append('id', problemId);
                
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const problem = await response.json();
                
                // 문제 상세 HTML 생성
                let choicesHtml = '';
                if (problem.inputanswer) {
                    const choices = typeof problem.inputanswer === 'string' ? JSON.parse(problem.inputanswer) : problem.inputanswer;
                    if (choices && choices.length > 0) {
                        choicesHtml = '<div id="problemChoicesDetail" class="textbook-choices-container">';
                        choices.forEach((choice, index) => {
                            // LaTeX 수식이 있으면 $로 감싸기
                            const wrappedChoice = wrapLatexInDollars(choice);
                            choicesHtml += `<div class="textbook-choice-item">${wrappedChoice}</div>`;
                        });
                        choicesHtml += '</div>';
                    }
                }
                
                // 이미지 HTML 생성
                let questionImageHtml = '';
                if (problem.qstnimgurl) {
                    questionImageHtml = `<div class="textbook-problem-image"><img src="${problem.qstnimgurl}" alt="문제 이미지"></div>`;
                }
                
                let solutionImageHtml = '';
                if (problem.solimgurl) {
                    solutionImageHtml = `<div class="textbook-solution-image"><img src="${problem.solimgurl}" alt="해설 이미지"></div>`;
                }
                
                const detailHTML = `
                    <div class="problem-detail-container">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="color: #2c3e50; margin: 0; font-size: 20px; font-weight: 600;">문제 상세</h3>
                            <button onclick="showProblemDetailModal(${problemId})" style="padding: 8px 16px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">📝 편집</button>
                        </div>
                        <div class="textbook-problem-box">
                            <div class="textbook-problem-label">문제</div>
                            <div id="problemQuestionDetail" class="textbook-problem-content">${problem.question}</div>
                            ${questionImageHtml}
                            ${choicesHtml}
                        </div>
                        <div class="textbook-solution-box">
                            <div class="textbook-solution-label">해설</div>
                            <div id="problemSolutionDetail" class="textbook-solution-content">${problem.solution}</div>
                            ${solutionImageHtml}
                        </div>
                    </div>
                `;
                
                column.innerHTML = detailHTML;
                
                // MathJax로 수식 렌더링 (문제, 선택지, 해설 모두 포함)
                // DOM이 완전히 렌더링될 때까지 약간의 지연
                await new Promise(resolve => setTimeout(resolve, 50));
                
                const questionElement = document.getElementById('problemQuestionDetail');
                const solutionElement = document.getElementById('problemSolutionDetail');
                const choicesContainer = document.getElementById('problemChoicesDetail');
                const choiceElements = column.querySelectorAll('.choice-item');
                
                const elementsToRender = [];
                if (questionElement) elementsToRender.push(questionElement);
                if (solutionElement) elementsToRender.push(solutionElement);
                // 선택지 컨테이너 전체를 렌더링 (개별 선택지가 아닌)
                if (choicesContainer) {
                    elementsToRender.push(choicesContainer);
                } else {
                    // 컨테이너가 없으면 개별 선택지 렌더링
                    choiceElements.forEach(el => elementsToRender.push(el));
                }
                
                if (elementsToRender.length > 0) {
                    await renderMath(elementsToRender);
                }
                
            } catch (e) {
                console.error('문제 정보를 가져오는 중 오류 발생:', e);
                column.innerHTML = '<div style="text-align: center; padding: 40px; color: #e74c3c;">문제 정보를 불러오는 중 오류가 발생했습니다.</div>';
            }
        }

        // 문제 상세 모달 표시 (기존 함수 유지)
        function showProblemDetailModal(problemId) {
            showProblemDetail(problemId);
        }   
  
        // 시험지 생성 (사용하지 않음 - 시험지 인쇄로 통합)
        /*
        function createExam() {
            const allSelected = document.querySelectorAll('#allProblems .problem-block.selected');
            const similarSelected = [];
            const variantSelected = [];
            
            // type에 따라 분류
            allSelected.forEach(block => {
                const type = block.getAttribute('data-type') || 'similar';
                if (type === 'similar') {
                    similarSelected.push(block);
                } else {
                    variantSelected.push(block);
                } 
            });
            
            if (similarSelected.length === 0 && variantSelected.length === 0) {
                alert('시험지를 출제할 문제를 선택해주세요. (Ctrl + 클릭)');
                return;
            } 
            
            // 선택된 문제들 가져오기
            const similarProblems = Array.from(similarSelected).map(block => ({
                content: block.getAttribute('data-content'),
                answer: block.getAttribute('data-answer')
            }));
            
            const variantProblems = Array.from(variantSelected).map(block => ({
                content: block.getAttribute('data-content'),
                answer: block.getAttribute('data-answer')
            }));
            
            // 시험지 HTML 생성
            const examHTML = `
                <div class="exam-header">
                    <div class="exam-title">수열의 규칙성 평가</div>
                    <div class="exam-info">
                        <div class="student-info">
                            <div>학년: <span>&nbsp;</span></div>
                            <div>반: <span>&nbsp;</span></div>
                            <div>이름: <span>&nbsp;</span></div>
                        </div>
                        <div>날짜: ${new Date().toLocaleDateString('ko-KR')}</div>
                    </div>
                </div>
                
                ${similarProblems.length > 0 ? `
                <div class="exam-section">
                    <div class="section-title">I. 유사문제 (각 10점)</div>
                    ${similarProblems.map((p, i) => `
                        <div class="exam-problem">
                            <div class="problem-number">${i + 1}. 다음 수열의 빈칸에 들어갈 수를 구하시오.</div>
                            <div class="problem-content">${p.content}</div>
                            <div class="answer-space">답:</div>
                        </div>
                    `).join('')}
                </div>
                ` : ''}
                
                ${variantProblems.length > 0 ? `
                <div class="exam-section">
                    <div class="section-title">${similarProblems.length > 0 ? 'II' : 'I'}. 변형문제 (각 15점)</div>
                    ${variantProblems.map((p, i) => `
                        <div class="exam-problem">
                            <div class="problem-number">${similarProblems.length + i + 1}. 다음 수열의 규칙을 찾아 뺈칸에 들어갈 수를 구하시오.</div>
                            <div class="problem-content">${p.content}</div>
                            <div class="answer-space">답:</div>
                        </div>
                    `).join('')}
                </div>
                ` : ''}
                
                <div style="margin-top: 50px; padding: 20px; background-color: #f0f0f0; border-radius: 8px;">
                    <strong>채점 기준</strong><br>
                    ${similarProblems.length > 0 ? `- 유사문제: 각 10점 (총 ${similarProblems.length * 10}점)<br>` : ''}
                    ${variantProblems.length > 0 ? `- 변형문제: 각 15점 (총 ${variantProblems.length * 15}점)<br>` : ''}
                    - 총점: ${similarProblems.length * 10 + variantProblems.length * 15}점
                </div>
            `;
            
            document.getElementById('examPaper').innerHTML = examHTML;
            document.getElementById('examModal').style.display = 'block';
        }
        */

        // 시험지 인쇄
        function printExam() {
            window.print();
        }
        
        // 선택된 문제 직접 인쇄
        async function printSelectedProblems() {
            const allSelected = document.querySelectorAll('.problem-block.selected');
            
            if (allSelected.length === 0) {
                alert('인쇄할 문제를 선택해주세요. (Ctrl + 클릭)');
                return;
            }
            
            // 선택된 문제들의 상세 정보 가져오기
            const selectedProblems = [];
            for (const block of allSelected) {
                const problemId = block.getAttribute('data-id');
                const formData = new FormData();
                formData.append('action', 'get_problem');
                formData.append('id', problemId);
                
                try {
                    const response = await fetch('patternbank_ajax.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    const problem = await response.json();
                    problem.type = block.getAttribute('data-type');
                    selectedProblems.push(problem);
                } catch (e) {
                    console.error('문제 정보 가져오기 실패:', e);
                }
            }
            
            // 시험지 생성
            const examHTML = generateExamHTML(selectedProblems);
            document.getElementById('examPaper').innerHTML = examHTML;
            document.getElementById('examModal').style.display = 'block';
            
            // MathJax 렌더링
            const examPaperEl = document.getElementById('examPaper');
            if (examPaperEl) {
                await renderMath(examPaperEl);
            }
            
            // 자동 인쇄
            setTimeout(() => {
                window.print();
            }, 500);
        }
        
        // 시험지 HTML 생성 함수
        function generateExamHTML(problems) {
            const similarProblems = problems.filter(p => p.type === 'similar');
            const variantProblems = problems.filter(p => p.type !== 'similar');
            let problemNumber = 1;
            
            return `
                <div class="textbook-exam-container">
                    ${problems.map((p) => {
                        let choicesHtml = '';
                        if (p.inputanswer) {
                            const choices = typeof p.inputanswer === 'string' ? JSON.parse(p.inputanswer) : p.inputanswer;
                            if (choices && choices.length > 0) {
                                choicesHtml = '<div class="textbook-choices-container">';
                                choices.forEach((choice, index) => {
                                    const wrappedChoice = wrapLatexInDollars(choice);
                                    choicesHtml += `<div class="textbook-choice-item">${wrappedChoice}</div>`;
                                });
                                choicesHtml += '</div>';
                            }
                        }
                        
                        let questionImageHtml = '';
                        if (p.qstnimgurl) {
                            questionImageHtml = `<div class="textbook-problem-image"><img src="${p.qstnimgurl}" alt="문제 이미지"></div>`;
                        }
                        
                        return `
                            <div class="textbook-exam-problem">
                                <div class="textbook-problem-header">
                                    <span class="textbook-problem-number">${problemNumber++}.</span>
                                    <div class="textbook-problem-body">
                                        <div class="textbook-problem-text">${p.question}</div>
                                        ${questionImageHtml}
                                        ${choicesHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        // 모달 표시
        function showModal(title, message) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('modal').style.display = 'block';
        }

        // 모달 닫기
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        // 시험지 모달 닫기
        function closeExamModal() {
            document.getElementById('examModal').style.display = 'none';
        }

        // 모달 외부 클릭 시 닫기
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            const examModal = document.getElementById('examModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            if (event.target == examModal) {
                examModal.style.display = 'none';
            }
        }

        // JSON 모달 닫기
        function closeJsonModal() {
            document.getElementById('jsonModal').style.display = 'none';
            window.isEditingExisting = false;
            window.editingProblemId = null;
        }
        
        // JSON으로 기존 문제 업데이트
        async function updateProblemFromJson() {
            const jsonInput = document.getElementById('jsonInput').value;
            
            try {
                const data = JSON.parse(jsonInput);
                const question = data.question || data["문제"] || data["문항"];
                const solution = data.solution || data["해설"];
                const choices = data.choices || data["선택지"];
                
                if (!question || !solution) {
                    alert('문제와 해설은 필수 항목입니다.');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'update_problem');
                formData.append('id', window.editingProblemId);
                formData.append('question', question);
                formData.append('solution', solution);
                if (choices) {
                    formData.append('choices', JSON.stringify(choices));
                }
                
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('문제가 JSON으로 성공적으로 업데이트되었습니다.');
                    closeJsonModal();
                    closeProblemDetailModal();
                    location.reload();
                } else {
                    alert('업데이트 중 오류: ' + (result.message || '알 수 없는 오류'));
                }
            } catch (e) {
                console.error('JSON 파싱 오류:', e);
                alert('JSON 형식이 올바르지 않습니다.');
            }
        }

        // 문제 상세 정보 모달 닫기
        function closeProblemDetailModal() {
            document.getElementById('problemDetailModal').style.display = 'none';
        }

        // JSON 문제 저장 (수정된 버전)
        window.saveJsonProblem = async function() {
            // 기존 문제 수정 모드인지 확인
            if (window.isEditingExisting && window.editingProblemId) {
                await updateProblemFromJson();
                return;
            }
            const jsonInput = document.getElementById('jsonInput').value;
            console.log('입력된 JSON:', jsonInput);
            
            try {
                let data;
                
                try {
                    data = JSON.parse(jsonInput);
                    console.log('파싱된 데이터:', data);
                } catch (e) {
                    console.error('JSON 파싱 오류 상세:', e);
                    alert('JSON 파싱 오류:\n' + e.message + '\n\n입력하신 내용을 확인해주세요.');
                    return;
                }
                
                const question = data.question || data["문제"] || data["문항"];
                const solution = data.solution || data["해설"];
                const choices = data.choices || data["선택지"];
                
                console.log('추출된 데이터:', {question, solution, choices});
                
                if (!question || !solution) {
                    alert('문제와 해설은 필수 항목입니다.');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'save_problem');
                formData.append('authorid', PHP_VARS.userid);
                formData.append('cntid', PHP_VARS.cntid);
                formData.append('cnttype', PHP_VARS.cnttype);
                const decodeHtmlEntities = (text) => {
                    const textArea = document.createElement('textarea');
                    textArea.innerHTML = text;
                    return textArea.value;
                };
                
                const convertDollarToLatex = (text) => {
                    text = text.replace(/\$([^$]+)\$/g, '\\($1\\)');
                    text = text.replace(/\$\$([^$]+)\$\$/g, '\\[$1\\]');
                    return text;
                };
                
                let decodedQuestion = decodeHtmlEntities(question);
                let decodedSolution = decodeHtmlEntities(solution);
                
                decodedQuestion = convertDollarToLatex(decodedQuestion);
                decodedSolution = convertDollarToLatex(decodedSolution);
                
                formData.append('question', decodedQuestion);
                formData.append('solution', decodedSolution);
                
                let decodedChoices = null;
                if (choices) {
                    decodedChoices = choices.map(choice => {
                        let decoded = decodeHtmlEntities(choice);
                        return convertDollarToLatex(decoded);
                    });
                    formData.append('choices', JSON.stringify(decodedChoices));
                    formData.append('inputanswer', JSON.stringify(decodedChoices));
                }
                
                formData.append('type', window.currentProblemType || 'similar');
                
                console.log('currentProblemType:', window.currentProblemType);
                console.log('FormData 내용:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ':', value);
                }
                
                try {
                    const response = await fetch('patternbank_ajax.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    console.log('Response status:', response.status);
                    const responseText = await response.text();
                    console.log('Response text:', responseText);
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('서버 응답 파싱 오류:', e); 
                        alert('서버 응답 오류: ' + responseText);
                        return;
                    }
                    
                    if (result.success) {
                        console.log('Server response:', result);
                        console.log('Type saved to server:', result.type_saved);
                        console.log('Type in database:', result.type_in_db);
                        
                        // 화면에 문제 추가
                        const problemType = window.currentProblemType || 'similar';
                        const gridId = problemType === 'similar' ? 'similarProblemsList' : 'modifiedProblemsList';
                        const grid = document.getElementById(gridId);
                        const problemCount = grid.children.length + 1;
                          
                        addProblemBlock(gridId, {
                            id: result.id,
                            number: problemCount,
                            question: decodedQuestion,
                            solution: decodedSolution,
                            inputanswer: decodedChoices ? JSON.stringify(decodedChoices) : '',
                            type: problemType
                        });
                        
                        closeJsonModal();
                        alert('문제가 성공적으로 추가되었습니다! (Type: ' + (window.currentProblemType || 'similar') + ')');
                        location.reload(); // 페이지 새로고침으로 전체 데이터 다시 로드
                    } else {
                        alert('문제 저장 중 오류: ' + (result.message || '알 수 없는 오류'));
                    }
                    
                } catch (e) {
                    console.error('네트워크 오류:', e);
                    alert('서버와의 통신 중 오류가 발생했습니다.');
                }
                
            } catch (e) {
                console.error('전체 오류:', e);
                alert('오류가 발생했습니다: ' + e.message);
            }
        }

        // 현재 편집 중인 문제 ID 저장
        let currentEditingProblemId = null;
        
        // 문제 상세 정보 표시
        async function showProblemDetail(problemId) {
            console.log('showProblemDetail called with id:', problemId);
            currentEditingProblemId = problemId;
            // 서버에서 문제 정보 가져오기
            const formData = new FormData();
            formData.append('action', 'get_problem');
            formData.append('id', problemId);
            
            try {
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const problem = await response.json();
                
                // 문제 표시 (수식 렌더링)
                document.getElementById('problemQuestion').innerHTML = problem.question;
                
                // 선택지 표시
                if (problem.inputanswer) {
                    let choicesHtml = '<div class="textbook-choices-container">';
                    const choices = typeof problem.inputanswer === 'string' ? JSON.parse(problem.inputanswer) : problem.inputanswer;
                    choices.forEach((choice, index) => {
                        // LaTeX 수식이 있으면 $로 감싸기
                        const wrappedChoice = wrapLatexInDollars(choice);
                        choicesHtml += `<div class="textbook-choice-item">${wrappedChoice}</div>`;
                    });
                    choicesHtml += '</div>';
                    document.getElementById('problemChoices').innerHTML = choicesHtml;
                } else {
                    document.getElementById('problemChoices').innerHTML = '';
                }
                
                // 해설 표시 (수식 렌더링)
                document.getElementById('problemSolution').innerHTML = problem.solution;
                
                // DOM이 완전히 렌더링될 때까지 약간의 지연
                await new Promise(resolve => setTimeout(resolve, 50));
                
                // MathJax로 수식 렌더링 (문제, 선택지, 해설 모두 포함)
                const questionEl = document.getElementById('problemQuestion');
                const choicesEl = document.getElementById('problemChoices');
                const solutionEl = document.getElementById('problemSolution');
                const elementsToRender = [];
                if (questionEl) elementsToRender.push(questionEl);
                if (choicesEl) elementsToRender.push(choicesEl);
                if (solutionEl) elementsToRender.push(solutionEl);
                
                if (elementsToRender.length > 0) {
                    await renderMath(elementsToRender);
                }
                
                // 이미지가 있으면 표시
                if (problem.qstnimgurl) {
                    document.getElementById('problemQuestionImage').innerHTML = `<img src="${problem.qstnimgurl}" alt="문제 이미지">`;
                }
                if (problem.solimgurl) {
                    document.getElementById('problemSolutionImage').innerHTML = `<img src="${problem.solimgurl}" alt="해설 이미지">`;
                }
                
                document.getElementById('problemDetailModal').style.display = 'block';
                
            } catch (e) {
                console.error('문제 정보를 가져오는 중 오류 발생:', e);
            }
        }

        // 문제 변경 사항 저장
        async function saveProblemChanges() {
            if (!currentEditingProblemId) {
                alert('편집 중인 문제 ID가 없습니다.');
                return;
            }
            
            console.log('Saving problem ID:', currentEditingProblemId);
            
            const question = document.getElementById('problemQuestion').innerText.trim();
            const solution = document.getElementById('problemSolution').innerText.trim();
            const choicesDiv = document.getElementById('problemChoices');
            
            // 선택지 처리
            let choices = null;
            if (choicesDiv.innerText.trim()) {
                choices = choicesDiv.innerText.split('\n').filter(line => line.trim());
            }
            
            console.log('Save data:', {
                id: currentEditingProblemId,
                question: question.substring(0, 50) + '...',
                solution: solution.substring(0, 50) + '...',
                choices: choices
            });
            
            const formData = new FormData();
            formData.append('action', 'update_problem');
            formData.append('id', currentEditingProblemId);
            formData.append('question', question);
            formData.append('solution', solution);
            if (choices && choices.length > 0) {
                formData.append('choices', JSON.stringify(choices));
            }
            
            try {
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('서버 응답 오류: ' + responseText.substring(0, 200));
                    return;
                }
                
                if (result.success) {
                    alert('문제가 성공적으로 수정되었습니다.');
                    closeProblemDetailModal();
                    location.reload();
                } else {
                    alert('수정 중 오류 발생: ' + (result.message || result.error || '알 수 없는 오류'));
                }
            } catch (e) {
                console.error('수정 중 오류:', e);
                alert('서버와의 통신 중 오류가 발생했습니다.');
            }
        }
        
        // JSON 편집기 표시
        function showJsonEditor() {
            if (!currentEditingProblemId) return;
            
            const question = document.getElementById('problemQuestion').innerText;
            const solution = document.getElementById('problemSolution').innerText;
            const choicesDiv = document.getElementById('problemChoices');
            
            let choices = null;
            if (choicesDiv.innerText.trim()) {
                choices = choicesDiv.innerText.split('\n').filter(line => line.trim());
            }
            
            const jsonData = {
                "문항": question,
                "선택지": choices,
                "해설": solution
            };
            
            document.getElementById('jsonInput').value = JSON.stringify(jsonData, null, 2);
            document.getElementById('jsonModal').style.display = 'block';
            window.isEditingExisting = true;
            window.editingProblemId = currentEditingProblemId;
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeExamModal();
                closeJsonModal();
                closeProblemDetailModal();
            }
        });
        
        // 유형 분석 편집 기능
        let analysisEditTimeout = null;
        let originalAnalysisText = '';
        
        function enableAnalysisEdit() {
            const analysisDiv = document.getElementById('analysisText');
            originalAnalysisText = analysisDiv.innerHTML;
            
            analysisDiv.contentEditable = true;
            analysisDiv.focus();
            
            // 텍스트 전체 선택
            const range = document.createRange();
            range.selectNodeContents(analysisDiv);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            
            // blur 이벤트로 저장
            analysisDiv.onblur = function() {
                saveAnalysis();
            };
            
            // Enter 키로 줄바꿈, Ctrl+Enter로 저장
            analysisDiv.onkeydown = function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    analysisDiv.blur();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    analysisDiv.innerHTML = originalAnalysisText;
                    analysisDiv.blur();
                }
            };
        }
        
        async function saveAnalysis() {
            const analysisDiv = document.getElementById('analysisText');
            analysisDiv.contentEditable = false;
            
            const newText = analysisDiv.innerHTML;
            if (newText === originalAnalysisText) {
                return; // 변경사항 없음
            }
            
            // 저장 중 표시
            let indicator = analysisDiv.querySelector('.analysis-save-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.className = 'analysis-save-indicator';
                analysisDiv.style.position = 'relative';
                analysisDiv.appendChild(indicator);
            }
            indicator.textContent = '저장 중...';
            indicator.style.display = 'block';
            
            const formData = new FormData();
            formData.append('action', 'save_analysis');
            formData.append('cntid', PHP_VARS.cntid);
            formData.append('analysis', newText);
            
            console.log('Saving analysis:', {
                cntid: PHP_VARS.cntid,
                textLength: newText.length
            });
            
            try {
                const response = await fetch('patternbank_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid server response');
                }
                
                if (result.success) {
                    indicator.textContent = '✓ 저장됨';
                    originalAnalysisText = newText; // 성공 시 원본 텍스트 업데이트
                    setTimeout(() => {
                        indicator.style.display = 'none';
                    }, 2000);
                } else {
                    indicator.style.display = 'none';
                    alert('저장 중 오류 발생: ' + (result.message || '알 수 없는 오류'));
                    analysisDiv.innerHTML = originalAnalysisText;
                }
            } catch (e) {
                console.error('저장 중 오류:', e);
                indicator.style.display = 'none';
                alert('서버와의 통신 중 오류가 발생했습니다.');
                analysisDiv.innerHTML = originalAnalysisText;
            }
        }

        // 해설지 인쇄 함수
        async function printSolutionSheet() {
            const allSelected = document.querySelectorAll('.problem-block.selected');
            
            if (allSelected.length === 0) {
                alert('해설지를 인쇄할 문제를 선택해주세요. (Ctrl + 클릭)');
                return;
            } 
            
            // 선택된 문제들의 상세 정보 가져오기  
            const selectedProblems = [];
            for (const block of allSelected) {
                const problemId = block.getAttribute('data-id');
                const formData = new FormData();
                formData.append('action', 'get_problem');
                formData.append('id', problemId);
                
                try {
                    const response = await fetch('patternbank_ajax.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    const problem = await response.json();
                    problem.type = block.getAttribute('data-type'); 
                    selectedProblems.push(problem);
                } catch (e) {
                    console.error('문제 정보 가져오기 실패:', e);
                }
            }
            
            // 해설지 HTML 생성
            const solutionHTML = generateSolutionHTML(selectedProblems);
            document.getElementById('examPaper').innerHTML = solutionHTML;
            document.getElementById('examModal').style.display = 'block';
            
            // MathJax 렌더링
            const examPaperEl = document.getElementById('examPaper');
            if (examPaperEl) {
                await renderMath(examPaperEl);
            }
            
            // 자동 인쇄
            setTimeout(() => {
                window.print();
            }, 500);
        }
        
        // 해설지 HTML 생성 함수
        function generateSolutionHTML(problems) {
            let problemNumber = 1;
            return `
                <div class="textbook-solution-container">
                    <h2 class="textbook-solution-title">해설지</h2>
                    ${problems.map((p) => {
                        let solutionImageHtml = '';
                        if (p.solimgurl) {
                            solutionImageHtml = `<div class="textbook-solution-image"><img src="${p.solimgurl}" alt="해설 이미지"></div>`;
                        }
                        
                        return `
                            <div class="textbook-solution-problem">
                                <div class="textbook-solution-header">
                                    <span class="textbook-solution-number">${problemNumber++}.</span>
                                    <div class="textbook-solution-body">
                                        ${solutionImageHtml}
                                        <div class="textbook-solution-text">${p.solution}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }
        
        // 콘솔에 입력하여 JSON 파싱 테스트
        const testJson = `{
          "문항": "x에 대한 삼차방정식 $x^3+(a+2)x^2+3ax+a^2=0$이 중근을 갖도록 하는 실수 $a$의 값을 모두 구하여라.",
          "선택지": [
            "① $a=0$",
            "② $a=1$",
            "③ $a=0$ 또는 $a=1$",
            "④ 해당 조건을 만족하는 실수 $a$는 존재하지 않는다",
            "⑤ 모든 실수 $a$"
          ],
          "해설": "함수를 $f(x)=x^3+(a+2)x^2+3ax+a^2$라 두면\\n$f(-a)=(-a)^3+(a+2)(-a)^2+3a(-a)+a^2=-a^3+(a+2)a^2-3a^2+a^2=0$이므로 $f(x)=(x+a)(x^2+2x+a)$로 인수분해된다.\\n삼차방정식 $f(x)=0$이 중근을 가지려면 다음 두 경우 가운데 하나가 성립해야 한다.\\n(i) $x=-a$가 이차방정식 $x^2+2x+a=0$의 근일 때\\n$(-a)^2+2(-a)+a=a^2-2a+a=a^2-a=a(a-1)=0$\\n따라서 $a=0$ 또는 $a=1$.\\n(ii) 이차방정식 $x^2+2x+a=0$이 중근을 가질 때\\n판별식 $D=2^2-4a=4-4a=0$에서 $a=1$.\\n(i), (ii)를 종합하면 중근을 갖도록 하는 실수 $a$는 $a=0$ 또는 $a=1$이다.\\n따라서 정답은 ③이다."
        }`;

        try {
            const parsed = JSON.parse(testJson);
            console.log('JSON 파싱 성공:', parsed);
        } catch (e) {
            console.error('JSON 파싱 실패:', e);
        }
    </script>
</body>
</html>