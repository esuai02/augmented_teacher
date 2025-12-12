<?php
/**
 * Agent 06: 선생님 피드백 패널 (Step 6 전용)
 *
 * 파일 위치: /alt42/orchestration/agents/agent06_teacher_feedback/ui/teacher_feedback_panel.php
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/ui/teacher_feedback_panel.php
 *
 * 기능:
 * - 선생님 피드백 입력 및 조회
 * - 기간별 피드백 필터링
 * - 학생별 피드백 관리
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 ID 확인
$studentid = $_GET['userid'] ?? $USER->id;
?>

<div id="agent06-panel" class="agent-panel">
    <style>
        .agent06-container {
            max-width: 100%;
            padding: 0;
        }

        .agent06-header {
            margin-bottom: 20px;
        }

        .agent06-header h2 {
            color: #1e293b;
            font-size: 22px;
            margin-bottom: 8px;
        }

        .agent06-header p {
            color: #64748b;
            font-size: 14px;
        }

        .agent06-toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .agent06-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .agent06-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .agent06-btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .agent06-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .agent06-period-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .agent06-period-section h4 {
            color: #475569;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .agent06-period-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .agent06-period-btn {
            padding: 8px 16px;
            border: 2px solid #e11d48;
            background: white;
            color: #e11d48;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }

        .agent06-period-btn.active {
            background: #e11d48;
            color: white;
        }

        .agent06-period-btn:hover:not(.active) {
            background: #fff1f2;
        }

        .agent06-summary {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .agent06-summary h4 {
            color: #991b1b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .agent06-summary-text {
            color: #7f1d1d;
            font-size: 13px;
        }

        .agent06-feedback-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .agent06-feedback-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s;
        }

        .agent06-feedback-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .agent06-feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .agent06-teacher-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }

        .agent06-timestamp {
            font-size: 11px;
            color: #94a3b8;
        }

        .agent06-feedback-text {
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        .agent06-new-feedback {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
        }

        .agent06-new-feedback h4 {
            color: #475569;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .agent06-textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            margin-bottom: 10px;
        }

        .agent06-textarea:focus {
            outline: none;
            border-color: #e11d48;
        }

        .agent06-loading {
            text-align: center;
            padding: 40px;
        }

        .agent06-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #e11d48;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .agent06-empty {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .agent06-empty-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>

    <div class="agent06-container">
        <!-- 헤더 -->
        <div class="agent06-header">
            <h2>👨‍🏫 선생님 피드백</h2>
            <p>학생에 대한 관찰, 개선사항, 칭찬 등을 기록하고 조회합니다.</p>
        </div>

        <!-- 툴바 -->
        <div class="agent06-toolbar">
            <button class="agent06-btn agent06-btn-primary" onclick="agent06.loadFeedback()">
                🔍 피드백 불러오기
            </button>
            <button class="agent06-btn agent06-btn-success" onclick="agent06.scrollToNew()">
                ✍️ 새 피드백 작성
            </button>
        </div>

        <!-- 기간 선택 -->
        <div class="agent06-period-section">
            <h4>📅 조회 기간</h4>
            <div class="agent06-period-btns">
                <button class="agent06-period-btn active" data-period="today">오늘</button>
                <button class="agent06-period-btn" data-period="week">1주일</button>
                <button class="agent06-period-btn" data-period="2weeks">2주</button>
                <button class="agent06-period-btn" data-period="month">1개월</button>
                <button class="agent06-period-btn" data-period="3months">3개월</button>
            </div>
        </div>

        <!-- 로딩 -->
        <div id="agent06-loading" class="agent06-loading" style="display: none;">
            <div class="agent06-spinner"></div>
            <p style="color: #64748b;">피드백을 불러오는 중...</p>
        </div>

        <!-- 요약 -->
        <div id="agent06-summary" class="agent06-summary" style="display: none;">
            <h4>📊 피드백 요약</h4>
            <div id="agent06-summary-text" class="agent06-summary-text">
                <!-- 동적 생성 -->
            </div>
        </div>

        <!-- 피드백 목록 -->
        <div id="agent06-feedback-list" class="agent06-feedback-list">
            <div class="agent06-empty">
                <div class="agent06-empty-icon">📝</div>
                <p>위 버튼을 클릭하여 피드백을 불러오세요.</p>
            </div>
        </div>

        <!-- 새 피드백 작성 -->
        <div class="agent06-new-feedback" id="agent06-new-section">
            <h4>✍️ 새 피드백 작성</h4>
            <textarea id="agent06-new-feedback"
                      class="agent06-textarea"
                      placeholder="학생에 대한 관찰 내용, 개선사항, 칭찬, 주의사항 등을 입력하세요...&#10;&#10;예시:&#10;- 오늘 수학 문제 풀이에 집중력이 높았음&#10;- 오답노트 작성이 성실하지만, 원인 분석이 부족&#10;- 질문을 적극적으로 하는 모습이 인상적">
            </textarea>
            <button class="agent06-btn agent06-btn-success" onclick="agent06.saveFeedback()" style="width: 100%;">
                💾 피드백 저장
            </button>
        </div>
    </div>
</div>

<script>
// Agent 06 전역 객체
window.agent06 = window.agent06 || {
    currentPeriod: 'today',
    userId: <?php echo json_encode($studentid); ?>,

    // 피드백 불러오기
    loadFeedback: async function() {
        console.log('[Agent06] Loading feedback, period:', this.currentPeriod, 'userId:', this.userId);

        const loading = document.getElementById('agent06-loading');
        const summary = document.getElementById('agent06-summary');
        const feedbackList = document.getElementById('agent06-feedback-list');

        if (loading) loading.style.display = 'block';
        if (summary) summary.style.display = 'none';

        try {
            // API 경로 수정 (orchestration_hs2 참조)
            const apiUrl = '/moodle/local/augmented_teacher/alt42/orchestration_hs2/api/teacher_feedback_api.php';
            const params = new URLSearchParams({
                action: 'get_feedback',
                user_id: this.userId,
                period: this.currentPeriod
            });

            const response = await fetch(`${apiUrl}?${params}`);
            const data = await response.json();

            if (loading) loading.style.display = 'none';

            if (data.success && data.feedbacks && data.feedbacks.length > 0) {
                this.displayFeedbacks(data.feedbacks);
                this.updateSummary(data.feedbacks.length);
                if (summary) summary.style.display = 'block';
            } else {
                if (feedbackList) {
                    feedbackList.innerHTML = `
                        <div class="agent06-empty">
                            <div class="agent06-empty-icon">📭</div>
                            <p>선택한 기간에 피드백이 없습니다.</p>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('[Agent06] Load error:', error);
            if (loading) loading.style.display = 'none';

            if (feedbackList) {
                feedbackList.innerHTML = `
                    <div class="agent06-empty">
                        <div class="agent06-empty-icon">❌</div>
                        <p style="color: #ef4444;">피드백 불러오기 실패</p>
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">File: teacher_feedback_panel.php, Error: ${error.message}</p>
                    </div>
                `;
            }
        }
    },

    // 피드백 표시
    displayFeedbacks: function(feedbacks) {
        const feedbackList = document.getElementById('agent06-feedback-list');
        if (!feedbackList) return;

        feedbackList.innerHTML = '';

        feedbacks.forEach(fb => {
            const card = document.createElement('div');
            card.className = 'agent06-feedback-card';
            card.innerHTML = `
                <div class="agent06-feedback-header">
                    <span class="agent06-teacher-name">${this.escapeHtml(fb.teacher_name || '교사')}</span>
                    <span class="agent06-timestamp">${this.escapeHtml(fb.created_at || '')}</span>
                </div>
                <div class="agent06-feedback-text">${this.escapeHtml(fb.feedback_text || '').replace(/\n/g, '<br>')}</div>
            `;
            feedbackList.appendChild(card);
        });
    },

    // 요약 업데이트
    updateSummary: function(count) {
        const summaryText = document.getElementById('agent06-summary-text');
        if (!summaryText) return;

        const periodText = {
            'today': '오늘',
            'week': '최근 1주일',
            '2weeks': '최근 2주',
            'month': '최근 1개월',
            '3months': '최근 3개월'
        }[this.currentPeriod] || this.currentPeriod;

        summaryText.innerHTML = `${periodText} 동안 <strong>${count}개</strong>의 피드백이 있습니다.`;
    },

    // 피드백 저장
    saveFeedback: async function() {
        const textarea = document.getElementById('agent06-new-feedback');
        if (!textarea) return;

        const text = textarea.value.trim();
        if (!text) {
            alert('피드백 내용을 입력해주세요.');
            return;
        }

        try {
            const apiUrl = '/moodle/local/augmented_teacher/alt42/orchestration_hs2/api/teacher_feedback_api.php';

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_feedback',
                    user_id: this.userId,
                    feedback_text: text
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('✅ 피드백이 저장되었습니다.');
                textarea.value = '';
                this.loadFeedback(); // 목록 새로고침
            } else {
                alert('❌ 피드백 저장 실패: ' + (data.message || '알 수 없는 오류'));
            }
        } catch (error) {
            console.error('[Agent06] Save error:', error);
            alert('❌ 피드백 저장 중 오류 발생\nFile: teacher_feedback_panel.php\nError: ' + error.message);
        }
    },

    // 새 피드백 섹션으로 스크롤
    scrollToNew: function() {
        const newSection = document.getElementById('agent06-new-section');
        if (newSection) {
            newSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            const textarea = document.getElementById('agent06-new-feedback');
            if (textarea) textarea.focus();
        }
    },

    // HTML 이스케이프
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// 기간 선택 버튼 이벤트
document.addEventListener('DOMContentLoaded', function() {
    const periodBtns = document.querySelectorAll('.agent06-period-btn');
    periodBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            periodBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            agent06.currentPeriod = this.dataset.period;
            console.log('[Agent06] Period changed:', agent06.currentPeriod);
        });
    });

    console.log('[Agent06] Teacher Feedback Panel Initialized for user:', agent06.userId);
});
</script>
