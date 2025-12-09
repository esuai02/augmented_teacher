<?php
/**
 * 노드별 학생 질문 시스템 v4.0 - Simple Q&A Interface
 * 기존 풀이 단계 사용 + 질문/답변만 표시
 *
 * @author AI Learning System
 * @created 2025-01-26
 * @file books/drillingmath3.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$secret_key = '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';
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 ");
$role=$userrole->data;
require_login();

$contentsid=$_GET["cid"];
$studentid=$_GET["userid"];
$contentstype=$_GET["ctype"];
$nstep = isset($_GET["nstep"]) ? intval($_GET["nstep"]) : 1;
$section = isset($_GET["section"]) ? intval($_GET["section"]) : null;
$subtitle = isset($_GET["subtitle"]) ? $_GET["subtitle"] : '';
$wboardid = 'contentstype'.$contentstype.'_stepquiz_'.$contentsid.'_step'.$nstep.'_userid'.$studentid; 

// 기존 풀이 단계 및 질문 가져오기
$existingContent = $DB->get_record('abessi_tailoredcontents', array(
    'contentsid' => $contentsid,
    'contentstype' => $contentstype,
    'nstep' => $nstep
));

error_log(sprintf(
    '[drillingmath.php] File: %s, Line: %d, DB query result: %s',
    basename(__FILE__),
    __LINE__,
    $existingContent ? 'Found record id=' . $existingContent->id : 'No record found'
));

// 현재 단계의 표시 내용 결정
// 1순위: URL의 subtitle (현재 구간 자막) - 화면 표시용
// 2순위: DB의 qstn0 (전체 풀이 단계)
$thinkingContent = '';
$currentStepContent = '';  // 화면 표시용 (현재 단계만)

if (!empty($subtitle)) {
    // URL에서 전달된 현재 구간의 자막 사용 (최우선)
    $currentStepContent = $subtitle;
    error_log(sprintf(
        '[drillingmath.php] File: %s, Line: %d, Using subtitle for display (length: %d)',
        basename(__FILE__),
        __LINE__,
        strlen($subtitle)
    ));
} elseif ($existingContent && !empty($existingContent->qstn0)) {
    // URL subtitle이 없으면 DB qstn0 사용
    $currentStepContent = $existingContent->qstn0;
    error_log(sprintf(
        '[drillingmath.php] File: %s, Line: %d, Using DB qstn0 for display (length: %d)',
        basename(__FILE__),
        __LINE__,
        strlen($currentStepContent)
    ));
} else {
    // DB에도 없으면 기본 텍스트 가져오기
    error_log(sprintf(
        '[drillingmath.php] File: %s, Line: %d, No subtitle or qstn0, loading from source',
        basename(__FILE__),
        __LINE__
    ));

    if($contentstype==1) {
        $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'");
        if ($cnttext && !empty($cnttext->maintext)) {
            $currentStepContent = strip_tags($cnttext->maintext);
        }
    } elseif($contentstype==2) {
        $cnttext = $DB->get_record_sql("SELECT * FROM mdl_question where id='$contentsid'");
        if ($cnttext && !empty($cnttext->mathexpression)) {
            $currentStepContent = $cnttext->mathexpression;
        }
    }
}

// thinkingContent는 currentStepContent와 동일 (하위 호환성)
$thinkingContent = $currentStepContent;

// 전체 문맥을 위해 모든 단계의 qstn0 가져오기
$allSteps = $DB->get_records('abessi_tailoredcontents', array(
    'contentsid' => $contentsid,
    'contentstype' => $contentstype
), 'nstep ASC');

$fullContext = '';
$totalSteps = 0;
if ($allSteps && count($allSteps) > 0) {
    $stepTexts = array();
    foreach ($allSteps as $step) {
        if (!empty($step->qstn0)) {
            $stepTexts[] = "단계 " . $step->nstep . ": " . $step->qstn0;
            $totalSteps++;
        }
    }
    $fullContext = implode("\n\n", $stepTexts);
    error_log(sprintf(
        '[drillingmath.php] File: %s, Line: %d, Loaded %d total steps for context',
        basename(__FILE__),
        __LINE__,
        $totalSteps
    ));
}

error_log(sprintf(
    '[drillingmath3.php] File: %s, Line: %d, Final thinkingContent length: %d',
    basename(__FILE__),
    __LINE__,
    strlen($thinkingContent)
));

if($role!=='student') echo '';
else {
    echo '사용권한이 없습니다.';
    exit();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>질문과 답변</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        /* 대본 영역 완전히 숨김 */
        .thinking-content {
            display: none !important;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: space-between;
        }

        .current-section-toggle {
            cursor: pointer;
            color: #64748b;
            font-size: 14px;
            font-weight: normal;
            transition: color 0.2s ease;
            user-select: none;
            margin-left: 8px;
            text-decoration: underline;
        }

        .current-section-toggle:hover {
            color: #6366f1;
        }

        .current-section-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 0;
        }

        .current-section-content.expanded {
            max-height: 2000px;
            margin-top: 20px;
        }

        .current-section-text {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .questions-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        .whiteboard-section {
            background: white;
            border-radius: 16px;
            padding: 20px 30px 30px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .whiteboard-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }

        .whiteboard-header-icon {
            font-size: 20px;
        }

        .whiteboard-header-toggle {
            cursor: pointer;
            color: #64748b;
            font-size: 14px;
            font-weight: normal;
            transition: color 0.2s ease;
            user-select: none;
            margin-left: 8px;
            text-decoration: underline;
        }

        .whiteboard-header-toggle:hover {
            color: #6366f1;
        }

        .whiteboard-iframe-container {
            width: 100%;
            height: 600px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #f8fafc;
        }

        .whiteboard-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .section-header {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #fef3c7 0%, #fce7f3 100%);
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid #fde68a;
            box-shadow: 0 2px 4px rgba(251, 191, 36, 0.1);
        }

        .section-header .header-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-header .current-section-toggle {
            font-size: 12px;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .section-header .current-section-toggle:hover {
            color: #6366f1;
        }

        .refresh-button {
            border: none;
            background: rgba(255, 255, 255, 0.7);
            color: #6b7280;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .refresh-button:hover:not(:disabled) {
            background: #fff;
            color: #2563eb;
        }

        .refresh-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .question-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
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
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            background: transparent;
        }

        .question-number {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
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
            font-size: 14px;
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }

        .question-card.active .toggle-icon {
            transform: rotate(180deg);
        }

        .answer-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .question-card.active .answer-content {
            max-height: 500px;
        }

        .answer-text {
            padding: 0 20px 18px 60px;
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
            background: transparent;
            margin: 0;
            border-radius: 0;
            padding: 0 20px 18px 60px;
        }

        .loading {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #e2e8f0;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 640px) {
            body {
                padding: 12px;
            }

            .section, .questions-section {
                padding: 20px;
                border-radius: 12px;
            }

            .question-header {
                padding: 14px 16px;
            }

            .answer-text {
                padding: 0 16px 14px 48px;
            }

            .question-number {
                width: 24px;
                height: 24px;
                font-size: 12px;
            }
        }
    </style>

    <!-- MathJax for LaTeX rendering -->
    <script>
        MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true,
                tags: 'ams'
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre'],
                ignoreHtmlClass: 'tex2jax_ignore',
                processHtmlClass: 'tex2jax_process'
            },
            startup: {
                ready: () => {
                    console.log('[MathJax] Configuration loaded with $ delimiters enabled');
                    MathJax.startup.defaultReady();
                }
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>
    <div class="container">
        <!-- 질문 섹션 -->
        <div class="questions-section">
            <div class="section-header">
                💡 자주하는 질문들
                <div class="header-actions">
                    <?php if (!empty($currentStepContent)): ?>
                        <span class="current-section-toggle" onclick="toggleCurrentSection()">
                            (현재구간 보기)
                        </span>
                    <?php endif; ?>
                    <?php if ($role !== 'student'): ?>
                        <button id="regenerate-btn" class="refresh-button" type="button" onclick="regenerateQuestions()">
                            🔄 새로 생성
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($currentStepContent)): ?>
                <?php
                // LaTeX 수식 렌더링을 위해 htmlspecialchars 사용하지 않음
                // 대신 안전한 출력을 위해 script 태그만 제거
                $safeContent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $currentStepContent);
                ?>
                <div class="current-section-content" id="current-section-content">
                    <div class="current-section-text" id="current-section-text">
                        <?php echo $safeContent; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div id="questions-container" class="loading">
                <div class="loading-spinner"></div>
                <p>질문을 생성하고 있습니다...</p>
            </div>
        </div>

        <!-- 화이트보드 섹션 -->
        <div class="whiteboard-section">
            <div class="whiteboard-iframe-container">
                <iframe 
                    class="whiteboard-iframe" 
                    id="whiteboard-iframe"
                    src="../whiteboard/board_stepquiz.php?id=<?php echo htmlspecialchars($wboardid, ENT_QUOTES, 'UTF-8'); ?>" 
                    title="단계별 퀴즈 화이트보드"
                    allow="camera; microphone; fullscreen">
                </iframe>
            </div>
        </div>
    </div>

    <script>
        // 현재구간 보기 토글 함수
        function toggleCurrentSection() {
            const content = document.getElementById('current-section-content');
            if (content) {
                content.classList.toggle('expanded');
                
                // 펼쳐질 때 MathJax 렌더링
                if (content.classList.contains('expanded')) {
                    setTimeout(async () => {
                        const textElement = document.getElementById('current-section-text');
                        if (textElement && typeof MathJax !== 'undefined') {
                            try {
                                await MathJax.typesetPromise([textElement]);
                                console.log('[drillingmath.php] Current section MathJax rendered');
                            } catch (err) {
                                console.error('[drillingmath.php] MathJax rendering error (current section):', err);
                            }
                        }
                    }, 100);
                }
            }
        }

        // 페이지 로드 시 질문과 답변 생성
        let isGenerating = false;

        document.addEventListener('DOMContentLoaded', async function() {
            console.log('[drillingmath.php:DOMContentLoaded] Initializing...');

            // 대본 영역 완전히 제거 (혹시 남아있을 수 있는 경우 대비)
            const thinkingContentElements = document.querySelectorAll('.thinking-content');
            thinkingContentElements.forEach(el => {
                el.style.display = 'none';
                el.remove();
            });

            // MathJax 로드 대기
            if (typeof MathJax !== 'undefined') {
                await MathJax.startup.promise;
                console.log('[drillingmath.php] MathJax ready');
            }

            // 질문과 답변 로드
            await loadQuestions();

            // 질문/답변 MathJax 렌더링 (DOM 업데이트 후)
            setTimeout(async () => {
                const questionsContainer = document.getElementById('questions-container');
                if (questionsContainer && typeof MathJax !== 'undefined') {
                    try {
                        await MathJax.typesetPromise([questionsContainer]);
                        console.log('[drillingmath.php] Questions rendered');
                    } catch (err) {
                        console.error('[drillingmath.php] MathJax rendering error (questions):', err);
                    }
                }
            }, 300);
        });

        // 질문 로드
        async function loadQuestions(forceRegenerate = false) {
            const questionsContainer = document.getElementById('questions-container');
            // PHP에서 직접 전달받은 현재 단계 내용 사용
            const thinkingContent = <?php echo json_encode($currentStepContent, JSON_UNESCAPED_UNICODE); ?>;

            const contentsid = "<?php echo $contentsid; ?>";
            const contentstype = "<?php echo $contentstype; ?>";
            const nstep = <?php echo $nstep; ?>;
            const totalSteps = <?php echo $totalSteps; ?>;

            // fullContext를 JSON으로 안전하게 전달 (수식 보존)
            const fullContext = <?php echo json_encode($fullContext, JSON_UNESCAPED_UNICODE); ?>;

            console.log('[drillingmath.php:loadQuestions] File: drillingmath.php, Line: 396, Parameters:', {
                contentsid: contentsid,
                contentstype: contentstype,
                nstep: nstep,
                totalSteps: totalSteps,
                currentStepLength: thinkingContent.length,
                fullContextLength: fullContext ? fullContext.length : 0,
                urlParams: new URLSearchParams(window.location.search).toString()
            });

            if (!thinkingContent || thinkingContent.trim().length === 0) {
                questionsContainer.innerHTML = '<div style="color: #ef4444; text-align: center;">풀이 단계 내용이 없습니다.</div>';
                return;
            }

            // PHP에서 전달된 DB 캐시 Q&A 확인
            let cachedQAPairs = <?php
                // DB에 저장된 질문이 하나라도 있으면 사용
                $qaPairsFromDB = [];
                if ($existingContent) {
                    if (!empty($existingContent->qstn1)) {
                        $qaPairsFromDB[] = [
                            'question' => $existingContent->qstn1,
                            'answer' => $existingContent->ans1 ?? ''
                        ];
                    }
                    if (!empty($existingContent->qstn2)) {
                        $qaPairsFromDB[] = [
                            'question' => $existingContent->qstn2,
                            'answer' => $existingContent->ans2 ?? ''
                        ];
                    }
                    if (!empty($existingContent->qstn3)) {
                        $qaPairsFromDB[] = [
                            'question' => $existingContent->qstn3,
                            'answer' => $existingContent->ans3 ?? ''
                        ];
                    }
                }
                
                if (count($qaPairsFromDB) > 0) {
                    echo json_encode($qaPairsFromDB, JSON_UNESCAPED_UNICODE);
                    error_log(sprintf(
                        '[drillingmath.php] File: %s, Line: %d, Found %d cached Q&A pairs in DB',
                        basename(__FILE__),
                        __LINE__,
                        count($qaPairsFromDB)
                    ));
                } else {
                    echo 'null';
                    error_log(sprintf(
                        '[drillingmath.php] File: %s, Line: %d, No cached Q&A found, will generate new ones',
                        basename(__FILE__),
                        __LINE__
                    ));
                }
            ?>;

            if (!forceRegenerate && cachedQAPairs && cachedQAPairs.length > 0) {
                // DB 캐시된 Q&A 사용
                console.log('[drillingmath.php:loadQuestions] Using cached Q&A from DB:', cachedQAPairs.length, 'pairs');
                questionsContainer.classList.remove('loading');
                questionsContainer.innerHTML = '';

                window.qaPairs = cachedQAPairs;

                cachedQAPairs.forEach((qa, index) => {
                    const cardElement = createQuestionCard(index + 1, qa.question, 0, index, thinkingContent, qa.answer);
                    questionsContainer.appendChild(cardElement);
                });

                // MathJax 렌더링 (DOM 업데이트 완료 후)
                if (typeof MathJax !== 'undefined') {
                    setTimeout(async () => {
                        try {
                            await MathJax.typesetPromise([questionsContainer]);
                            console.log('[drillingmath.php] File: drillingmath.php, Line: 497, Cached Q&A MathJax rendered successfully');
                        } catch (err) {
                            console.error('[drillingmath.php] File: drillingmath.php, Line: 504, MathJax error (cached Q&A):', err);
                        }
                    }, 200);
                }
                return;
            }

            // DB에 없으면 AI로 생성
            try {
                console.log('[drillingmath.php:loadQuestions] No cached Q&A, generating with AI...', {
                    currentStep: nstep,
                    totalSteps: totalSteps
                });

                questionsContainer.classList.add('loading');
                questionsContainer.innerHTML = `
                    <div class="loading-spinner" style="margin: 0 auto 12px;"></div>
                    <p style="text-align:center;color:#94a3b8;">새로운 질문을 생성하고 있습니다...</p>
                `;

                const requestBody = {
                    nodeContent: thinkingContent,
                    nodeType: 'step',
                    fullContext: fullContext,  // 전체 단계 문맥
                    contentsid: contentsid,
                    contentstype: contentstype,
                    nstep: nstep,
                    totalSteps: totalSteps,
                    nodeIndex: 0,
                    forceRegenerate: Boolean(forceRegenerate)
                };

                console.log('[drillingmath.php:loadQuestions] Request body:', {
                    ...requestBody,
                    fullContext: requestBody.fullContext.substring(0, 200) + '...'
                });

                const response = await fetch('generate_questions_with_answers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });

                const data = await response.json();

                console.log('[drillingmath.php:loadQuestions] API Response:', data);

                if (data.success && data.qa_pairs && data.qa_pairs.length > 0) {
                    questionsContainer.classList.remove('loading');
                    questionsContainer.innerHTML = '';

                    window.qaPairs = data.qa_pairs;
                    cachedQAPairs = data.qa_pairs;

                    data.qa_pairs.forEach((qa, index) => {
                        const cardElement = createQuestionCard(index + 1, qa.question, 0, index, thinkingContent, qa.answer);
                        questionsContainer.appendChild(cardElement);
                    });

                    // AI 생성 Q&A MathJax 렌더링 (DOM 업데이트 완료 후)
                    if (typeof MathJax !== 'undefined') {
                        setTimeout(async () => {
                            try {
                                await MathJax.typesetPromise([questionsContainer]);
                                console.log('[drillingmath.php] File: drillingmath.php, Line: 557, AI-generated Q&A MathJax rendered successfully');
                            } catch (err) {
                                console.error('[drillingmath.php] File: drillingmath.php, Line: 564, MathJax error (AI Q&A):', err);
                            }
                        }, 200);
                    }
                } else {
                    const errorMsg = data.error || '알 수 없는 오류';
                    console.error('[drillingmath.php:loadQuestions] API Error:', errorMsg, data);
                    questionsContainer.innerHTML = `<div style="color: #ef4444; text-align: center;">질문 생성 실패: ${errorMsg}<br><small>콘솔을 확인하세요</small></div>`;
                }
            } catch (error) {
                console.error('[drillingmath3.php:loadQuestions] Exception:', error);
                console.error('[drillingmath3.php:loadQuestions] Stack:', error.stack);
                questionsContainer.innerHTML = `<div style="color: #ef4444; text-align: center;">질문 로딩 오류: ${error.message}</div>`;
            }
        }

        // LaTeX 수식 오류 자동 수정 함수
        function fixLatexErrors(text) {
            if (!text) return '';
            
            // 1. 역슬래시가 누락된 일반적인 LaTeX 명령어 복구
            // frac, sqrt, int, sum, lim, pi, theta, alpha, beta, gamma, etc.
            const latexCommands = [
                'frac', 'sqrt', 'int', 'sum', 'lim', 'pi', 'theta', 'alpha', 'beta', 'gamma', 'delta', 
                'times', 'div', 'pm', 'approx', 'neq', 'le', 'ge', 'infty', 'partial', 'nabla',
                'sin', 'cos', 'tan', 'log', 'ln', 'exp'
            ];
            
            let fixedText = text;
            
            latexCommands.forEach(cmd => {
                // 역슬래시 없이 사용된 명령어 찾기 (앞에 영문자가 없어야 함)
                // 예: " frac" -> " \frac", "(frac" -> "(\frac"
                const regex = new RegExp(`(?<![\\\\a-zA-Z])${cmd}(?![a-zA-Z])`, 'g');
                fixedText = fixedText.replace(regex, `\\${cmd}`);
            });
            
            // 2. 분수 표현 오류 복구 (예: a over b)
            fixedText = fixedText.replace(/(\w+)\s+over\s+(\w+)/g, '\\frac{$1}{$2}');
            
            // 3. 중괄호 누락 복구 시도 (간단한 경우만)
            // 예: \frac 1 2 -> \frac{1}{2} (조심스러운 접근 필요)
            
            return fixedText;
        }

        async function regenerateQuestions() {
            if (isGenerating) {
                return;
            }

            isGenerating = true;
            const regenerateBtn = document.getElementById('regenerate-btn');
            if (regenerateBtn) {
                regenerateBtn.disabled = true;
                regenerateBtn.textContent = '⏳ 생성 중...';
            }

            try {
                await loadQuestions(true);
            } catch (error) {
                console.error('[drillingmath.php] regenerate error:', error);
                alert('재생성 중 오류가 발생했습니다. 콘솔을 확인해주세요.');
            } finally {
                if (regenerateBtn) {
                    regenerateBtn.disabled = false;
                    regenerateBtn.textContent = '🔄 새로 생성';
                }
                isGenerating = false;
            }
        }

        // 질문 카드 생성 (답변 포함)
        function createQuestionCard(number, question, nodeIndex, questionIndex, nodeContent, answer) {
            const cardId = `question-card-${nodeIndex}-${questionIndex}`;

            // LaTeX 수식 보존을 위해 이스케이프 없이 사용
            // XSS 방지는 script 태그만 제거
            let safeQuestion = question ? question.replace(/<script\b[^>]*>(.*?)<\/script>/gis, '') : '';
            let safeAnswer = answer ? answer.replace(/<script\b[^>]*>(.*?)<\/script>/gis, '') : '';
            
            // 수식 오류 자동 수정 적용
            safeQuestion = fixLatexErrors(safeQuestion);
            safeAnswer = fixLatexErrors(safeAnswer);

            // DOM 요소 생성 ($ 기호 충돌 방지)
            const card = document.createElement('div');
            card.className = 'question-card';
            card.id = cardId;
            card.onclick = () => toggleQuestionCard(cardId, questionIndex);

            const header = document.createElement('div');
            header.className = 'question-header';

            const numberDiv = document.createElement('div');
            numberDiv.className = 'question-number';
            numberDiv.textContent = number;

            const questionText = document.createElement('div');
            questionText.className = 'question-text';
            questionText.innerHTML = safeQuestion;  // innerHTML으로 LaTeX 태그 보존

            const toggleIcon = document.createElement('div');
            toggleIcon.className = 'toggle-icon';
            toggleIcon.textContent = '▼';

            header.appendChild(numberDiv);
            header.appendChild(questionText);
            header.appendChild(toggleIcon);

            const answerContent = document.createElement('div');
            answerContent.className = 'answer-content';

            const answerText = document.createElement('div');
            answerText.className = 'answer-text';
            answerText.id = `answer-${cardId}`;
            answerText.setAttribute('data-loaded', 'true');
            answerText.innerHTML = safeAnswer;  // innerHTML으로 LaTeX 태그 보존

            answerContent.appendChild(answerText);
            card.appendChild(header);
            card.appendChild(answerContent);

            return card;
        }

        // 질문 카드 토글 (답변은 이미 로드됨)
        function toggleQuestionCard(cardId, questionIndex) {
            const card = document.getElementById(cardId);
            const answerDiv = document.getElementById(`answer-${cardId}`);

            // 다른 카드들 닫기 (아코디언)
            document.querySelectorAll('.question-card').forEach(c => {
                if (c.id !== cardId) {
                    c.classList.remove('active');
                }
            });

            // 현재 카드 토글
            card.classList.toggle('active');

            // 답변이 이미 있으면 MathJax 렌더링
            if (card.classList.contains('active') && answerDiv.dataset.loaded === 'true') {
                // MathJax 렌더링 (질문 텍스트와 답변 모두)
                if (typeof MathJax !== 'undefined') {
                    setTimeout(async () => {
                        try {
                            // 질문 텍스트와 답변 둘 다 렌더링
                            await MathJax.typesetPromise([card]);
                            console.log('[drillingmath.php] File: drillingmath.php, Line: 615, Question and Answer MathJax rendered for question', questionIndex);
                        } catch (err) {
                            console.error('[drillingmath.php] File: drillingmath.php, Line: 619, MathJax error (answer):', err);
                        }
                    }, 100);
                }
            }
        }
    </script>
</body>
</html>
