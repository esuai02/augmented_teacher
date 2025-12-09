/**
 * Agent 15: 문제 재정의 & 개선방안 - JavaScript 기능
 *
 * orchestration_hs2의 Step 15 기능을 agent15로 이식
 * "문제 재정의 가져오기" 버튼 클릭 시 데이터 수집 및 GPT API 호출
 *
 * File: alt42/orchestration/agents/agent15_problem_redefinition/ui/problem_redefinition_functions.js
 * Error: line number in errors
 */

/**
 * 문제 재정의 데이터 가져오기
 * orchestration_hs2의 fetchProblemRedefinition() 함수를 이식
 */
async function agent15FetchProblemRedefinition() {
    const userId = window.currentUserId || 2;
    const loadingEl = document.getElementById('agent15-loading');
    const statusEl = document.getElementById('agent15-status');
    const textArea = document.getElementById('agent15-problem-redefinition-text');
    const fetchBtn = document.getElementById('agent15-fetch-problem-redefinition-btn');

    console.log('agent15FetchProblemRedefinition 시작... (userId:', userId, ')');

    // UI 상태 업데이트
    loadingEl.style.display = 'flex';
    fetchBtn.disabled = true;
    statusEl.style.display = 'none';

    try {
        // Step 1: 각 Step의 데이터 수집
        console.log('📊 Agent 15: 데이터 수집 시작...');

        // Agent 15 API 경로 사용
        const response = await fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/api/collect_workflow_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ userId: userId })
        });

        if (!response.ok) {
            throw new Error('데이터 수집 실패 (file: problem_redefinition_functions.js, line: 48)');
        }

        const collectedData = await response.json();
        console.log('✅ 데이터 수집 완료:', collectedData);

        // Step 2: GPT API로 문제 재정의 생성
        statusEl.textContent = 'GPT API로 분석 중...';
        statusEl.className = '';
        statusEl.classList.add('info');
        statusEl.style.display = 'block';

        const gptResponse = await fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/api/problem_redefinition_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userId: userId,
                data: collectedData,
                guidanceMode: window.selectedGuidanceMode || null
            })
        });

        if (!gptResponse.ok) {
            throw new Error('GPT 분석 실패 (file: problem_redefinition_functions.js, line: 73)');
        }

        const gptResult = await gptResponse.json();
        console.log('✅ GPT 분석 완료');

        // Step 3: 결과를 텍스트 영역에 표시
        const redefinitionContent = gptResult.redefinition || '문제 재정의 내용을 생성할 수 없습니다.';

        console.log('📝 문제 재정의 내용 설정 중...');
        console.log('내용:', redefinitionContent.substring(0, 100) + '...');

        if (textArea) {
            textArea.value = redefinitionContent;
            console.log('✅ agent15-problem-redefinition-text에 설정 완료');
        } else {
            console.log('❌ agent15-problem-redefinition-text 찾을 수 없음');
        }

        // 성공 메시지 표시
        statusEl.textContent = '✅ 문제 재정의가 성공적으로 생성되었습니다.';
        statusEl.className = '';
        statusEl.classList.add('success');

    } catch (error) {
        console.error('Agent 15 오류 (file: problem_redefinition_functions.js):', error);

        // 오류 메시지 표시
        statusEl.textContent = `❌ 오류: ${error.message}`;
        statusEl.className = '';
        statusEl.classList.add('error');
        statusEl.style.display = 'block';

        // 대체 텍스트 제공
        if (!textArea.value) {
            textArea.value = `[데이터 수집 오류]\n\n현재 데이터를 불러올 수 없습니다.\n수동으로 문제 재정의 내용을 작성해주세요.\n\n오류: ${error.message}\n파일: problem_redefinition_functions.js`;
        }

    } finally {
        // UI 상태 복원
        loadingEl.style.display = 'none';
        fetchBtn.disabled = false;
    }
}

/**
 * 문제 재정의 저장
 * orchestration_hs2의 saveProblemRedefinition() 함수를 이식
 */
async function agent15SaveProblemRedefinition() {
    const userId = window.currentUserId || 2;
    const textArea = document.getElementById('agent15-problem-redefinition-text');
    const statusEl = document.getElementById('agent15-status');
    const saveBtn = document.getElementById('agent15-save-problem-redefinition-btn');

    console.log('agent15SaveProblemRedefinition 시작... (userId:', userId, ')');

    if (!textArea || !textArea.value.trim()) {
        statusEl.textContent = '⚠️ 저장할 내용이 없습니다. (file: problem_redefinition_functions.js, line: 137)';
        statusEl.className = '';
        statusEl.classList.add('error');
        statusEl.style.display = 'block';
        return;
    }

    saveBtn.disabled = true;

    try {
        // 로컬 스토리지에 저장 (orchestration_hs2와 동일한 방식)
        const saveData = {
            userId: userId,
            content: textArea.value,
            timestamp: new Date().toISOString()
        };

        localStorage.setItem(`agent15_redefinition_${userId}`, JSON.stringify(saveData));

        console.log('✅ 로컬 스토리지 저장 완료');

        // 성공 메시지
        statusEl.textContent = '✅ 문제 재정의가 저장되었습니다.';
        statusEl.className = '';
        statusEl.classList.add('success');
        statusEl.style.display = 'block';

    } catch (error) {
        console.error('저장 오류 (file: problem_redefinition_functions.js):', error);
        statusEl.textContent = `❌ 저장 실패: ${error.message}`;
        statusEl.className = '';
        statusEl.classList.add('error');
        statusEl.style.display = 'block';

    } finally {
        saveBtn.disabled = false;

        // 3초 후 메시지 숨기기
        setTimeout(() => {
            statusEl.style.display = 'none';
        }, 3000);
    }
}

/**
 * 저장된 문제 재정의 내용 불러오기
 */
function agent15LoadSavedRedefinition() {
    const userId = window.currentUserId || 2;
    const savedData = localStorage.getItem(`agent15_redefinition_${userId}`);

    if (savedData) {
        try {
            const parsed = JSON.parse(savedData);
            const textArea = document.getElementById('agent15-problem-redefinition-text');

            if (textArea && parsed.content) {
                textArea.value = parsed.content;
                console.log('Agent 15: 저장된 내용 로드 완료 (file: problem_redefinition_functions.js, line: 196)');
            }
        } catch (e) {
            console.error('Agent 15: 저장된 내용 로드 실패 (file: problem_redefinition_functions.js):', e);
        }
    }
}

/**
 * Agent 15 초기화
 */
function initializeAgent15ProblemRedefinition() {
    console.log('Agent 15: 문제 재정의 패널 초기화 시작...');

    // 저장된 내용 불러오기
    agent15LoadSavedRedefinition();

    console.log('Agent 15: 초기화 완료 (file: problem_redefinition_functions.js, line: 212)');
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    console.log('Agent 15: DOMContentLoaded 이벤트 감지');

    // Agent 15 패널이 있는 경우 초기화
    const agent15Panel = document.getElementById('agent15-problem-redefinition-panel');
    if (agent15Panel) {
        initializeAgent15ProblemRedefinition();
    }
});

console.log('✅ Agent 15: problem_redefinition_functions.js 로드 완료');
