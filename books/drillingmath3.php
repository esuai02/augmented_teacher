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
global $DB, $USER, $CFG;

// API 키를 $CFG에서 가져오기
$secret_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
if (empty($secret_key)) {
    error_log('[drillingmath3.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Error: API 키가 설정되지 않았습니다.');
}
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 ");
$role=$userrole->data;
require_login();

$contentsid=$_GET["cid"];
$contentstype=$_GET["ctype"];
$nstep = isset($_GET["nstep"]) ? intval($_GET["nstep"]) : 1;
$section = isset($_GET["section"]) ? intval($_GET["section"]) : null;
$subtitle = isset($_GET["subtitle"]) ? $_GET["subtitle"] : '';

// 기존 풀이 단계 가져오기
$existingContent = $DB->get_record('abessi_tailoredcontents', array(
    'contentsid' => $contentsid,
    'contentstype' => $contentstype,
    'nstep' => $nstep
));

$thinkingContent = '';
if ($existingContent && !empty($existingContent->qstn0)) {
    $thinkingContent = $existingContent->qstn0;
    error_log(sprintf(
        '[drillingmath3.php] File: %s, Line: %d, Loaded from DB qstn0: %s',
        basename(__FILE__),
        __LINE__,
        substr($thinkingContent, 0, 100)
    ));
} else {
    error_log(sprintf(
        '[drillingmath3.php] File: %s, Line: %d, No qstn0 found, loading from source',
        basename(__FILE__),
        __LINE__
    ));

    // DB에 없으면 기본 텍스트 가져오기
    if($contentstype==1) {
        $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'");
        if ($cnttext && !empty($cnttext->maintext)) {
            $thinkingContent = strip_tags($cnttext->maintext);
        }
    } elseif($contentstype==2) {
        $cnttext = $DB->get_record_sql("SELECT * FROM mdl_question where id='$contentsid'");
        if ($cnttext && !empty($cnttext->mathexpression)) {
            $thinkingContent = $cnttext->mathexpression;
        }
    }

    // subtitle이 있으면 우선 사용
    if(!empty($subtitle)) {
        $thinkingContent = $subtitle;
        error_log(sprintf(
            '[drillingmath3.php] File: %s, Line: %d, Using subtitle: %s',
            basename(__FILE__),
            __LINE__,
            substr($subtitle, 0, 100)
        ));
    }
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

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .thinking-content {
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
        }

        .section-header {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            background: white;
            margin: 0 12px 12px 12px;
            border-radius: 8px;
            padding: 16px;
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
    <div class="container">
        <!-- 기존 풀이 단계 표시 -->
        <div class="section">
            <div class="section-title">📝 풀이 단계</div>
            <div class="thinking-content" id="thinking-content">
                <?php
                if (!empty($thinkingContent)) {
                    echo htmlspecialchars($thinkingContent);
                } else {
                    echo '<div style="color: #94a3b8; text-align: center;">풀이 단계 내용이 없습니다. DB에 qstn0 값을 먼저 생성해주세요.</div>';
                }
                ?>
            </div>
        </div>

        <!-- 질문 섹션 -->
        <div class="questions-section">
            <div class="section-header">
                💡 자주하는 질문들
            </div>
            <div id="questions-container" class="loading">
                <div class="loading-spinner"></div>
                <p>질문을 생성하고 있습니다...</p>
            </div>
        </div>
    </div>

    <script>
        // 페이지 로드 시 질문과 답변 생성
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('[drillingmath3.php:DOMContentLoaded] Loading questions and answers');

            // 풀이 단계 MathJax 렌더링
            if (typeof MathJax !== 'undefined') {
                const thinkingContent = document.getElementById('thinking-content');
                MathJax.typesetPromise([thinkingContent]).catch(err => console.error('MathJax error:', err));
            }

            // 질문과 답변 로드
            await loadQuestions();

            // 질문/답변 MathJax 렌더링 (500ms 후)
            setTimeout(() => {
                if (typeof MathJax !== 'undefined') {
                    const questionsContainer = document.getElementById('questions-container');
                    MathJax.typesetPromise([questionsContainer]).catch(err => console.error('MathJax error:', err));
                }
            }, 500);
        });

        // 질문 로드
        async function loadQuestions() {
            const questionsContainer = document.getElementById('questions-container');
            const thinkingContent = document.getElementById('thinking-content').innerText;

            const contentsid = "<?php echo $contentsid; ?>";
            const contentstype = "<?php echo $contentstype; ?>";
            const nstep = <?php echo $nstep; ?>;

            console.log('[drillingmath3.php:loadQuestions] Parameters:', {
                contentsid,
                contentstype,
                nstep,
                thinkingContentLength: thinkingContent.length,
                thinkingContentPreview: thinkingContent.substring(0, 100)
            });

            if (!thinkingContent || thinkingContent.trim().length === 0) {
                questionsContainer.innerHTML = '<div style="color: #ef4444; text-align: center;">풀이 단계 내용이 없습니다.</div>';
                return;
            }

            // PHP에서 전달된 DB 캐시 Q&A 확인
            const cachedQAPairs = <?php
                if ($existingContent && !empty($existingContent->qstn1)) {
                    // DB에 Q&A가 있으면 JavaScript로 전달
                    $qaPairsFromDB = [];
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
                    echo json_encode($qaPairsFromDB, JSON_UNESCAPED_UNICODE);
                } else {
                    echo 'null';
                }
            ?>;

            if (cachedQAPairs && cachedQAPairs.length > 0) {
                // DB 캐시된 Q&A 사용
                console.log('[drillingmath3.php:loadQuestions] Using cached Q&A from DB:', cachedQAPairs);
                questionsContainer.classList.remove('loading');
                questionsContainer.innerHTML = '';

                window.qaPairs = cachedQAPairs;

                cachedQAPairs.forEach((qa, index) => {
                    const cardHTML = createQuestionCard(index + 1, qa.question, 0, index, thinkingContent, qa.answer);
                    questionsContainer.insertAdjacentHTML('beforeend', cardHTML);
                });

                // MathJax 렌더링
                if (typeof MathJax !== 'undefined') {
                    setTimeout(() => {
                        MathJax.typesetPromise([questionsContainer]).catch(err => console.error('MathJax error:', err));
                    }, 100);
                }
                return;
            }

            // DB에 없으면 AI로 생성
            try {
                console.log('[drillingmath3.php:loadQuestions] No cached Q&A, generating with AI...');

                const requestBody = {
                    nodeContent: thinkingContent,
                    nodeType: 'step',
                    fullContext: thinkingContent,
                    contentsid: contentsid,
                    contentstype: contentstype,
                    nstep: nstep,
                    nodeIndex: 0
                };

                console.log('[drillingmath3.php:loadQuestions] Request body:', requestBody);

                const response = await fetch('generate_questions_with_answers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });

                const data = await response.json();

                console.log('[drillingmath3.php:loadQuestions] API Response:', data);

                if (data.success && data.qa_pairs && data.qa_pairs.length > 0) {
                    questionsContainer.classList.remove('loading');
                    questionsContainer.innerHTML = '';

                    window.qaPairs = data.qa_pairs;

                    data.qa_pairs.forEach((qa, index) => {
                        const cardHTML = createQuestionCard(index + 1, qa.question, 0, index, thinkingContent, qa.answer);
                        questionsContainer.insertAdjacentHTML('beforeend', cardHTML);
                    });
                } else {
                    const errorMsg = data.error || '알 수 없는 오류';
                    console.error('[drillingmath3.php:loadQuestions] API Error:', errorMsg, data);
                    questionsContainer.innerHTML = `<div style="color: #ef4444; text-align: center;">질문 생성 실패: ${errorMsg}<br><small>콘솔을 확인하세요</small></div>`;
                }
            } catch (error) {
                console.error('[drillingmath3.php:loadQuestions] Exception:', error);
                console.error('[drillingmath3.php:loadQuestions] Stack:', error.stack);
                questionsContainer.innerHTML = `<div style="color: #ef4444; text-align: center;">질문 로딩 오류: ${error.message}</div>`;
            }
        }

        // 질문 카드 생성 (답변 포함)
        function createQuestionCard(number, question, nodeIndex, questionIndex, nodeContent, answer) {
            const cardId = `question-card-${nodeIndex}-${questionIndex}`;
            const escapedAnswer = answer ? answer.replace(/`/g, '\\`').replace(/\$/g, '\\$') : '';

            return `
                <div class="question-card" id="${cardId}" onclick="toggleQuestionCard('${cardId}', ${questionIndex})">
                    <div class="question-header">
                        <div class="question-number">${number}</div>
                        <div class="question-text">${question}</div>
                        <div class="toggle-icon">▼</div>
                    </div>
                    <div class="answer-content">
                        <div class="answer-text" id="answer-${cardId}" data-loaded="true">${answer || ''}</div>
                    </div>
                </div>
            `;
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
                // MathJax 렌더링
                if (typeof MathJax !== 'undefined') {
                    setTimeout(() => {
                        MathJax.typesetPromise([answerDiv]).catch(err => console.error('MathJax error:', err));
                    }, 100);
                }
            }
        }
    </script>
</body>
</html>
