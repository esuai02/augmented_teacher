<?php
/**
 * 노드별 학생 질문 시스템 v3.0 - Modern Card-Based Design
 *
 * @author AI Learning System
 * @created 2025-01-26
 * @file books/drillingmath2.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$secret_key = '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';
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 ");
$role=$userrole->data;
require_login();
$contentsid=$_GET["cid"];
$contentstype=$_GET["ctype"];
$nstep=$_GET["nstep"];
$type=$_GET["type"];
// 구간 정보 받기
$section = isset($_GET["section"]) ? intval($_GET["section"]) : null;
$subtitle = isset($_GET["subtitle"]) ? $_GET["subtitle"] : '';
$timecreated=time();

$thiscnt=$DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1 ");
$inputtext=$thiscnt->outputtext;

// 구간 자막이 전달된 경우 자막 텍스트로 대체
if(!empty($subtitle)) {
    $inputtext = $subtitle;
    error_log(sprintf(
        '[drillingmath2.php] File: %s, Line: %d, Section: %d, Subtitle received: %s',
        basename(__FILE__),
        __LINE__,
        $section,
        substr($subtitle, 0, 100)
    ));
}

if($role!=='student') echo '';
else {
    echo '사용권한이 없습니다.';
    exit();
}

if($type==NULL)$type='conversation';
$thiscnt=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE '$type' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' AND gid LIKE '71280'  ORDER BY id DESC LIMIT 1 ");
if($thiscnt->id==NULL) {
    $newrecord = new stdClass();
    $newrecord->type = $type;
    $newrecord->contentsid = $contentsid;
    $newrecord->contentstype = $contentstype;
    $newrecord->gid ='71280';
    $newrecord->timemodified = $timecreated;
    $newrecord->timecreated = $timecreated;
    $DB->insert_record('abrainalignment_gptresults', $newrecord);
}

$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype' AND url IS NOT NULL ORDER BY id DESC LIMIT 1 ");

// 컨텐츠 정보 가져오기
$maintext = '';

if($contentstype==1) {
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid' ORDER BY id DESC LIMIT 1");
    $maintext = $cnttext->maintext;
} elseif($contentstype==2) {
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_question where id='$contentsid' ORDER BY id DESC LIMIT 1");
    $maintext = $cnttext->mathexpression;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>멈추어 생각하기<?php if($section !== null) echo ' - 구간 '.($section + 1); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            width: 100%;
        }

        .problem-step {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
        }

        .step-title {
            font-size: 14px;
            font-weight: 600;
            color: #6366f1;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .math-expression {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            line-height: 1.8;
            color: #1e293b;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #6366f1;
            margin-bottom: 8px;
        }

        .math-expression:last-child {
            margin-bottom: 0;
        }

        .questions-section {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .section-header {
            font-size: 16px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header::before {
            content: "💡";
            font-size: 20px;
        }

        .question-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .question-card:hover {
            border-color: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }

        .question-card.active {
            border-color: #6366f1;
            background: #eef2ff;
        }

        .question-header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .question-number {
            background: #6366f1;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .question-text {
            flex: 1;
            font-size: 15px;
            font-weight: 500;
            color: #334155;
        }

        .toggle-icon {
            color: #94a3b8;
            transition: transform 0.3s ease;
            font-size: 20px;
            flex-shrink: 0;
        }

        .question-card.active .toggle-icon {
            transform: rotate(180deg);
            color: #6366f1;
        }

        .answer-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, padding 0.4s ease;
            padding: 0 20px;
        }

        .question-card.active .answer-content {
            max-height: 500px;
            padding: 0 20px 20px 20px;
        }

        .answer-text {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 3px solid #6366f1;
            font-size: 14px;
            line-height: 1.7;
            color: #475569;
        }

        .answer-text strong {
            color: #1e293b;
            font-weight: 600;
        }

        .example {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 6px;
            margin-top: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #334155;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #94a3b8;
        }

        @media (max-width: 640px) {
            .problem-step, .questions-section {
                padding: 24px;
            }

            .math-expression {
                font-size: 16px;
                padding: 16px;
            }

            .question-text {
                font-size: 14px;
            }
        }

        .pulse-hint {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .regenerate-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #6366f1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .regenerate-button:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
    </style>

    <!-- MathJax for LaTeX rendering -->
    <script>
        MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)']],
                displayMath: [['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>
    <?php if($role !== 'student'): ?>
    <button class="regenerate-button" onclick="regenerateFullContent()">🔄 전체 다시 생성</button>
    <?php endif; ?>

    <div class="container" id="main-container">
        <div class="problem-step">
            <div class="step-title">풀이 단계</div>
            <div id="flowchart-display" class="loading">
                사고 흐름도를 생성하고 있습니다...
            </div>
        </div>

        <div class="questions-section">
            <div class="section-header">
                이 부분에서 궁금할 수 있는 질문들
            </div>
            <div id="questions-container" class="loading">
                질문을 생성하고 있습니다...
            </div>
        </div>
    </div>

    <script>
        // 페이지 로드 시 사고 흐름도 생성
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('[drillingmath2.php:DOMContentLoaded] File: <?php echo basename(__FILE__); ?>, Line: DOMContentLoaded');
            await generateDetailedThinking();
        });

        // 사고 흐름도 자동 생성 (DB 확인 후 없으면 생성)
        async function generateDetailedThinking() {
            const flowchartDisplay = document.getElementById('flowchart-display');
            const subtitle = `<?php echo addslashes($subtitle); ?>`;
            const maintext = `<?php echo addslashes(strip_tags($maintext)); ?>`;
            const contentsid = "<?php echo $contentsid; ?>";
            const contentstype = "<?php echo $contentstype; ?>";
            const nstep = <?php echo isset($nstep) && $nstep > 0 ? $nstep : ($section !== null ? $section + 1 : 1); ?>;

            const fullContext = subtitle || maintext;

            console.log('[drillingmath2.php:generateDetailedThinking] Checking existing content...');

            try {
                // 1단계: DB에서 기존 컨텐츠 확인
                const checkResponse = await fetch('check_existing_content.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        contentsid: contentsid,
                        contentstype: contentstype,
                        nstep: nstep
                    })
                });

                const checkData = await checkResponse.json();

                if (checkData.exists && checkData.thinking) {
                    console.log('[drillingmath2.php:generateDetailedThinking] Using existing thinking from DB');
                    renderFlowchart(checkData.thinking);
                    setTimeout(() => loadAllQuestions(checkData.thinking), 500);
                    return;
                }

                // 2단계: 새로 생성
                console.log('[drillingmath2.php:generateDetailedThinking] Generating new thinking...');

                const response = await fetch('generate_detailed_thinking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        subtitle: subtitle,
                        maintext: maintext,
                        contentsid: contentsid,
                        contentstype: contentstype,
                        nstep: nstep
                    })
                });

                const data = await response.json();

                if (data.success && data.thinking) {
                    console.log('[drillingmath2.php:generateDetailedThinking] Thinking generated successfully');
                    renderFlowchart(data.thinking);
                    setTimeout(() => loadAllQuestions(data.thinking), 500);
                } else {
                    const errorMsg = data.error || '알 수 없는 오류';
                    console.error('[drillingmath2.php:generateDetailedThinking] API Error:', errorMsg);
                    flowchartDisplay.innerHTML = `<div style="color: #ef4444;">사고 흐름도 생성 실패: ${errorMsg}</div>`;
                }

            } catch (error) {
                console.error('[drillingmath2.php:generateDetailedThinking] Exception:', error);
                console.error('[drillingmath2.php:generateDetailedThinking] Stack:', error.stack);
                flowchartDisplay.innerHTML = `<div style="color: #ef4444;">사고 흐름도 로딩 오류: ${error.message}</div>`;
            }
        }

        // 플로우차트 렌더링
        function renderFlowchart(thinkingText) {
            const flowchartDisplay = document.getElementById('flowchart-display');
            flowchartDisplay.classList.remove('loading');

            const lines = thinkingText.split('\n').map(line => line.trim()).filter(line => line);
            let flowchartHTML = '';
            let nodeIndex = 0;

            // 전역 변수로 노드 정보 저장
            window.flowchartNodes = [];

            lines.forEach((line) => {
                if (line) {
                    const mathExpressionHTML = `<div class="math-expression" data-node-index="${nodeIndex}" data-node-content="${line.replace(/"/g, '&quot;')}">${line}</div>`;
                    flowchartHTML += mathExpressionHTML;

                    window.flowchartNodes.push({
                        index: nodeIndex,
                        content: line,
                        type: getNodeType(line)
                    });

                    nodeIndex++;
                }
            });

            flowchartDisplay.innerHTML = flowchartHTML;

            // MathJax 렌더링
            if (typeof MathJax !== 'undefined') {
                MathJax.typesetPromise([flowchartDisplay]).catch(err => console.error('MathJax error:', err));
            }
        }

        // 노드 타입 결정
        function getNodeType(line) {
            if (line.startsWith('∴')) return 'conclusion';
            if (line.includes('∵')) return 'premise';
            return 'step';
        }

        // 모든 노드의 질문 자동 로드
        async function loadAllQuestions(thinkingText) {
            const questionsContainer = document.getElementById('questions-container');
            questionsContainer.classList.remove('loading');
            questionsContainer.innerHTML = '';

            if (!window.flowchartNodes || window.flowchartNodes.length === 0) {
                questionsContainer.innerHTML = '<div style="color: #94a3b8;">질문을 생성할 노드가 없습니다.</div>';
                return;
            }

            const subtitle = `<?php echo addslashes($subtitle); ?>`;
            const maintext = `<?php echo addslashes(strip_tags($maintext)); ?>`;
            const contentsid = "<?php echo $contentsid; ?>";
            const contentstype = "<?php echo $contentstype; ?>";
            const nstep = <?php echo isset($nstep) && $nstep > 0 ? $nstep : ($section !== null ? $section + 1 : 1); ?>;
            const fullContext = thinkingText;

            let questionNumber = 1;

            for (const node of window.flowchartNodes) {
                try {
                    const response = await fetch('generate_node_questions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            nodeContent: node.content,
                            nodeType: node.type,
                            fullContext: fullContext,
                            contentsid: contentsid,
                            contentstype: contentstype,
                            nstep: nstep,
                            nodeIndex: node.index
                        })
                    });

                    const data = await response.json();

                    if (data.success && data.questions && data.questions.length > 0) {
                        data.questions.forEach((question, qIndex) => {
                            const cardHTML = createQuestionCard(
                                questionNumber,
                                question,
                                node.index,
                                qIndex,
                                node.content,
                                node.type,
                                questionNumber === 1
                            );
                            questionsContainer.innerHTML += cardHTML;
                            questionNumber++;
                        });
                    }
                } catch (error) {
                    console.error(`[drillingmath2.php:loadAllQuestions] Error loading questions for node ${node.index}:`, error);
                }
            }

            console.log(`[drillingmath2.php:loadAllQuestions] Loaded ${questionNumber - 1} questions total`);
        }

        // 질문 카드 HTML 생성
        function createQuestionCard(number, question, nodeIndex, questionIndex, nodeContent, nodeType, isFirstCard = false) {
            const cardId = `question-card-${nodeIndex}-${questionIndex}`;
            const pulseClass = isFirstCard ? ' pulse-hint' : '';

            return `
                <div class="question-card${pulseClass}" id="${cardId}" data-node-index="${nodeIndex}" data-question-index="${questionIndex}">
                    <div class="question-header" onclick="toggleQuestionCard('${cardId}', ${nodeIndex}, ${questionIndex}, '${question.replace(/'/g, "\\'")}', '${nodeContent.replace(/'/g, "\\'")}', '${nodeType}')">
                        <span class="question-number">${number}</span>
                        <span class="question-text">${question}</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="answer-content">
                        <div class="answer-text" id="answer-${nodeIndex}-${questionIndex}">
                            <!-- Answer will be loaded here -->
                        </div>
                    </div>
                </div>
            `;
        }

        // 질문 카드 토글 (accordion)
        async function toggleQuestionCard(cardId, nodeIndex, questionIndex, question, nodeContent, nodeType) {
            const card = document.getElementById(cardId);
            const answerDiv = document.getElementById(`answer-${nodeIndex}-${questionIndex}`);
            const isActive = card.classList.contains('active');

            // Accordion: 다른 카드들 모두 닫기
            document.querySelectorAll('.question-card.active').forEach(c => {
                if (c.id !== cardId) c.classList.remove('active');
            });

            // Pulse hint 제거 (첫 클릭 시)
            card.classList.remove('pulse-hint');

            // 현재 카드 토글
            if (isActive) {
                card.classList.remove('active');
                return;
            }

            card.classList.add('active');

            // 답변이 이미 로드되어 있으면 skip
            if (answerDiv.dataset.loaded === 'true') {
                // MathJax 렌더링
                if (typeof MathJax !== 'undefined') {
                    MathJax.typesetPromise([answerDiv]).catch(err => console.error('MathJax error:', err));
                }
                return;
            }

            // 답변 로딩
            answerDiv.classList.add('loading');
            answerDiv.innerHTML = '답변 생성 중...';

            const subtitle = `<?php echo addslashes($subtitle); ?>`;
            const maintext = `<?php echo addslashes(strip_tags($maintext)); ?>`;
            const contentsid = "<?php echo $contentsid; ?>";
            const contentstype = "<?php echo $contentstype; ?>";
            const nstep = <?php echo isset($nstep) && $nstep > 0 ? $nstep : ($section !== null ? $section + 1 : 1); ?>;
            const fullContext = document.getElementById('flowchart-display').innerText || '';

            try {
                const response = await fetch('generate_node_answer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        question: question,
                        nodeContent: nodeContent,
                        fullContext: fullContext,
                        contentsid: contentsid,
                        contentstype: contentstype,
                        nstep: nstep,
                        nodeIndex: nodeIndex,
                        questionIndex: questionIndex
                    })
                });

                const data = await response.json();

                answerDiv.classList.remove('loading');

                if (data.success && data.answer) {
                    answerDiv.innerHTML = data.answer;
                    answerDiv.dataset.loaded = 'true';

                    // MathJax 렌더링
                    if (typeof MathJax !== 'undefined') {
                        MathJax.typesetPromise([answerDiv]).catch(err => console.error('MathJax error:', err));
                    }
                } else {
                    answerDiv.innerHTML = '<span style="color: #ef4444;">답변 생성 실패</span>';
                }
            } catch (error) {
                console.error(`[drillingmath2.php:toggleQuestionCard] Error loading answer:`, error);
                answerDiv.classList.remove('loading');
                answerDiv.innerHTML = '<span style="color: #ef4444;">답변 로딩 오류</span>';
            }
        }

        // 전체 내용 다시 생성
        async function regenerateFullContent() {
            if (!confirm('전체 내용을 다시 생성하시겠습니까? 기존 내용이 대체됩니다.')) {
                return;
            }

            const flowchartDisplay = document.getElementById('flowchart-display');
            const questionsContainer = document.getElementById('questions-container');
            const subtitle = `<?php echo addslashes($subtitle); ?>`;
            const maintext = `<?php echo addslashes(strip_tags($maintext)); ?>`;
            const contentsid = "<?php echo $contentsid; ?>";
            const contentstype = "<?php echo $contentstype; ?>";
            const nstep = <?php echo isset($nstep) && $nstep > 0 ? $nstep : ($section !== null ? $section + 1 : 1); ?>;

            console.log(`[drillingmath2.php:regenerateFullContent] Starting full regeneration`);

            flowchartDisplay.className = 'loading';
            flowchartDisplay.innerHTML = '전체 내용을 다시 생성하고 있습니다...';
            questionsContainer.className = 'loading';
            questionsContainer.innerHTML = '질문을 생성하고 있습니다...';

            try {
                const fullContext = subtitle || maintext;

                const response = await fetch('generate_detailed_thinking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        context: fullContext,
                        subtitle: subtitle,
                        contentsid: contentsid,
                        contentstype: contentstype,
                        nstep: nstep,
                        forceRegenerate: true
                    })
                });

                const data = await response.json();

                if (data.success && data.thinking) {
                    console.log('[drillingmath2.php:regenerateFullContent] Regeneration successful');
                    renderFlowchart(data.thinking);
                    setTimeout(() => loadAllQuestions(data.thinking), 500);
                } else {
                    const errorMsg = data.error || '알 수 없는 오류';
                    console.error('[drillingmath2.php:regenerateFullContent] API Error:', errorMsg);
                    flowchartDisplay.className = '';
                    flowchartDisplay.innerHTML = `<div style="color: #ef4444;">재생성 실패: ${errorMsg}</div>`;
                    questionsContainer.className = '';
                    questionsContainer.innerHTML = '';
                }
            } catch (error) {
                console.error('[drillingmath2.php:regenerateFullContent] Exception:', error);
                console.error('[drillingmath2.php:regenerateFullContent] Stack:', error.stack);
                flowchartDisplay.className = '';
                flowchartDisplay.innerHTML = `<div style="color: #ef4444;">재생성 오류: ${error.message}</div>`;
                questionsContainer.className = '';
                questionsContainer.innerHTML = '';
            }
        }
    </script>
</body>
</html>
