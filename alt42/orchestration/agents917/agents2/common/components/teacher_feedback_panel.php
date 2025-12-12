<?php
/**
 * Teacher Feedback Panel Component
 *
 * 교사 피드백 패널 재사용 컴포넌트
 * - 수학일기 데이터 표시 (mdl_abessi_todayplans)
 * - 종합 피드백 통합
 * - 기간별 필터링 (오늘, 일주일, 2주일, 3주일, 4주일, 3개월)
 *
 * @file teacher_feedback_panel.php:1
 */

// 세션 시작 확인
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Moodle 글로벌 변수 확인
global $USER, $studentid;

// studentid가 설정되지 않았으면 세션이나 기본값 사용
if (!isset($studentid)) {
    $studentid = $_SESSION['user_id'] ?? $USER->id ?? 2;
}
?>

<div id="teacher-feedback-panel" class="teacher-feedback-content">

    <!-- 버튼 그룹 및 기간 선택 -->
    <div class="period-selector" style="margin-bottom: 30px;">
        <!-- 상단 버튼 그룹 -->
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px;">
            <button id="loadMathDiary"
                    class="action-button"
                    style="padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                           color: white; border: none; border-radius: 10px; cursor: pointer;
                           font-size: 16px; font-weight: 600; transition: transform 0.3s;">
                🔍 최근 교사 기록 (수학일기)
            </button>

            <button id="comprehensiveFeedback"
                    class="action-button"
                    style="padding: 12px 30px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                           color: white; border: none; border-radius: 10px; cursor: pointer;
                           font-size: 16px; font-weight: 600; transition: transform 0.3s;">
                📊 종합 피드백
            </button>
        </div>

        <!-- 기간 선택 버튼 그룹 -->
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <button class="period-btn active" data-period="today"
                    style="padding: 10px 20px; border: 2px solid #f97316; background: #f97316;
                           color: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                오늘
            </button>
            <button class="period-btn" data-period="week"
                    style="padding: 10px 20px; border: 2px solid #f97316; background: white;
                           color: #f97316; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                일주일
            </button>
            <button class="period-btn" data-period="2weeks"
                    style="padding: 10px 20px; border: 2px solid #f97316; background: white;
                           color: #f97316; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                2주일
            </button>
            <button class="period-btn" data-period="3weeks"
                    style="padding: 10px 20px; border: 2px solid #f97316; background: white;
                           color: #f97316; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                3주일
            </button>
            <button class="period-btn" data-period="4weeks"
                    style="padding: 10px 20px; border: 2px solid #f97316; background: white;
                           color: #f97316; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                4주일
            </button>
            <button class="period-btn" data-period="3months"
                    style="padding: 10px 20px; border: 2px solid #f97316; background: white;
                           color: #f97316; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                3개월
            </button>
        </div>
    </div>

    <!-- 로딩 표시 -->
    <div id="feedback-loading" style="display: none; text-align: center; padding: 40px;">
        <div style="display: inline-block; width: 50px; height: 50px; border: 5px solid #f3f3f3;
                    border-top: 5px solid #f97316; border-radius: 50%; animation: spin 1s linear infinite;">
        </div>
        <p style="margin-top: 20px; color: #666;">데이터를 불러오는 중...</p>
    </div>

    <!-- 수학일기 표시 영역 -->
    <div id="math-diary-display" style="display: none;">
        <!-- 요약 정보 -->
        <div class="diary-summary"
             style="background: linear-gradient(135deg, #f6f9fc 0%, #e9f3ff 100%);
                    padding: 20px; border-radius: 12px; margin-bottom: 30px;">
            <h3 style="color: #1e293b; margin-bottom: 15px;">📊 교사 피드백 요약</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                <div style="text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #f97316;" id="total-diary-count">0</div>
                    <div style="color: #666; font-size: 14px;">수학일기</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #10b981;" id="total-plans-count">0</div>
                    <div style="color: #666; font-size: 14px;">학습 계획</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #6366f1;" id="total-duration">0</div>
                    <div style="color: #666; font-size: 14px;">학습시간(분)</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #8b5cf6;" id="total-notes-count">0</div>
                    <div style="color: #666; font-size: 14px;">메모장</div>
                </div>
            </div>
        </div>

        <!-- 탭 네비게이션 -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0;">
            <button class="tab-btn active" data-tab="diary"
                    style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid #f97316;
                           color: #f97316; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                📚 수학일기 & 피드백
            </button>
            <button class="tab-btn" data-tab="notes"
                    style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid transparent;
                           color: #64748b; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                📝 메모장 전달 내용
            </button>
        </div>

        <!-- 수학일기 탭 -->
        <div id="diary-tab" class="tab-content" style="display: block;">
            <div id="diary-list" style="max-height: 600px; overflow-y: auto;">
                <!-- 일기 아이템들이 여기 동적으로 추가됨 -->
            </div>
        </div>

        <!-- 메모장 탭 -->
        <div id="notes-tab" class="tab-content" style="display: none;">
            <div id="notes-list" style="max-height: 600px; overflow-y: auto;">
                <!-- 메모 아이템들이 여기 동적으로 추가됨 -->
            </div>
        </div>
    </div>

    <!-- 종합 피드백 표시 영역 -->
    <div id="comprehensive-display" style="display: none;">
        <div id="comprehensive-content">
            <!-- 종합 피드백 내용이 여기 동적으로 추가됨 -->
        </div>
    </div>

    <!-- 빈 상태 메시지 -->
    <div id="empty-feedback" style="display: none; text-align: center; padding: 60px 20px;">
        <div style="font-size: 80px; margin-bottom: 20px;">📭</div>
        <h3 style="color: #666; margin-bottom: 10px;">선택한 기간에 데이터가 없습니다</h3>
        <p style="color: #999;">다른 기간을 선택하거나 수학일기를 작성해보세요.</p>
    </div>

</div>

<style>
/* 애니메이션 */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 버튼 호버 효과 */
.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.period-btn:hover {
    transform: translateY(-2px);
}

.period-btn.active {
    background: #f97316 !important;
    color: white !important;
}

/* 일기 항목 스타일 */
.diary-entry {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.diary-entry:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.plan-item {
    background: #f8fafc;
    padding: 12px;
    border-left: 4px solid #f97316;
    margin-bottom: 10px;
    border-radius: 6px;
}

.plan-item:last-child {
    margin-bottom: 0;
}

.sticky-note:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.tab-btn:hover {
    color: #f97316;
}
</style>

<script>
/**
 * 교사 피드백 패널 JavaScript
 * @file teacher_feedback_panel.php:179
 */

(function() {
    'use strict';

    // 현재 선택된 기간
    let currentPeriod = 'today';
    const studentId = <?php echo $studentid; ?>;

    // 초기화
    document.addEventListener('DOMContentLoaded', function() {
        initEventListeners();
    });

    /**
     * 이벤트 리스너 초기화
     */
    function initEventListeners() {
        // 기간 선택 버튼
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // 활성 상태 변경
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // 기간 업데이트
                currentPeriod = this.dataset.period;
                console.log('[teacher_feedback_panel.php:209] Period changed:', currentPeriod);
            });
        });

        // 탭 전환 버튼
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.dataset.tab;

                // 탭 버튼 활성 상태 변경
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active');
                    b.style.borderBottom = '3px solid transparent';
                    b.style.color = '#64748b';
                });
                this.classList.add('active');
                this.style.borderBottom = '3px solid #f97316';
                this.style.color = '#f97316';

                // 탭 컨텐츠 전환
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                document.getElementById(tabName + '-tab').style.display = 'block';

                console.log('[teacher_feedback_panel.php:236] Tab switched:', tabName);
            });
        });

        // 수학일기 불러오기 버튼
        document.getElementById('loadMathDiary').addEventListener('click', function() {
            loadMathDiary(currentPeriod);
        });

        // 종합 피드백 버튼
        document.getElementById('comprehensiveFeedback').addEventListener('click', function() {
            loadComprehensiveFeedback(currentPeriod);
        });
    }

    /**
     * 수학일기 데이터 로드
     * @param {string} period 기간
     */
    async function loadMathDiary(period) {
        console.log('[teacher_feedback_panel.php:230] Loading math diary for period:', period);

        // 로딩 표시
        showLoading();

        try {
            const response = await fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/common/api/get_math_diary.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'getMathDiary',
                    period: period,
                    user_id: studentId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} [teacher_feedback_panel.php:249]`);
            }

            const data = await response.json();
            console.log('[teacher_feedback_panel.php:253] Math diary data:', data);

            if (data.success) {
                displayMathDiary(data);
            } else {
                console.error('[teacher_feedback_panel.php:258] API error:', data.error);
                showEmptyState();
            }

        } catch (error) {
            console.error('[teacher_feedback_panel.php:263] Fetch error:', error);
            showEmptyState();
        }
    }

    /**
     * 수학일기 데이터 표시
     * @param {object} data API 응답 데이터
     */
    function displayMathDiary(data) {
        const diaryEntries = data.diary_entries || [];
        const stickyNotes = data.sticky_notes || [];

        if (diaryEntries.length === 0 && stickyNotes.length === 0) {
            showEmptyState();
            return;
        }

        // 요약 정보 계산
        let totalPlans = 0;
        let totalDuration = 0;

        diaryEntries.forEach(entry => {
            totalPlans += entry.plans.length;
            entry.plans.forEach(plan => {
                totalDuration += plan.duration;
            });
        });

        // 요약 정보 표시
        document.getElementById('total-diary-count').textContent = diaryEntries.length;
        document.getElementById('total-plans-count').textContent = totalPlans;
        document.getElementById('total-duration').textContent = totalDuration;
        document.getElementById('total-notes-count').textContent = stickyNotes.length;

        // 1. 수학일기 리스트 렌더링
        const diaryList = document.getElementById('diary-list');
        diaryList.innerHTML = '';

        if (diaryEntries.length > 0) {
            diaryEntries.forEach(entry => {
                const entryHtml = createDiaryEntryHtml(entry);
                diaryList.insertAdjacentHTML('beforeend', entryHtml);
            });
        } else {
            diaryList.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">선택한 기간에 수학일기가 없습니다.</div>';
        }

        // 2. 메모장 리스트 렌더링
        const notesList = document.getElementById('notes-list');
        notesList.innerHTML = '';

        if (stickyNotes.length > 0) {
            stickyNotes.forEach(note => {
                const noteHtml = createStickyNoteHtml(note);
                notesList.insertAdjacentHTML('beforeend', noteHtml);
            });
        } else {
            notesList.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">선택한 기간에 메모장 내용이 없습니다.</div>';
        }

        // 표시 영역 전환
        hideLoading();
        document.getElementById('math-diary-display').style.display = 'block';
        document.getElementById('comprehensive-display').style.display = 'none';
        document.getElementById('empty-feedback').style.display = 'none';
    }

    /**
     * 일기 항목 HTML 생성 (fback 필드 포함)
     * @param {object} entry 일기 항목
     * @returns {string} HTML 문자열
     */
    function createDiaryEntryHtml(entry) {
        const plansHtml = entry.plans.map(plan => `
            <div class="plan-item">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <strong style="color: #1e293b;">${plan.index}. ${escapeHtml(plan.plan)}</strong>
                    <span style="color: #f97316; font-weight: 600;">${plan.duration}분</span>
                </div>
                ${plan.status ? `<div style="font-size: 12px; color: #666; margin-bottom: 4px;">만족도: ${escapeHtml(plan.status)}</div>` : ''}
                ${plan.url ? `<div style="font-size: 12px; color: #3b82f6; margin-bottom: 4px;">
                    <a href="${escapeHtml(plan.url)}" target="_blank" style="text-decoration: none;">🔗 링크</a>
                </div>` : ''}
                ${plan.feedback ? `<div style="background: #fef3c7; padding: 8px; border-radius: 6px; margin-top: 8px; border-left: 3px solid #f59e0b;">
                    <div style="font-size: 11px; color: #92400e; font-weight: 600; margin-bottom: 4px;">💬 교사/AI 피드백</div>
                    <div style="font-size: 13px; color: #78350f; line-height: 1.5;">${escapeHtml(plan.feedback)}</div>
                </div>` : ''}
            </div>
        `).join('');

        return `
            <div class="diary-entry">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="color: #1e293b; margin: 0;">📅 ${entry.date}</h4>
                    <span style="color: #666; font-size: 14px;">${entry.time}</span>
                </div>
                <div>
                    ${plansHtml}
                </div>
            </div>
        `;
    }

    /**
     * 메모장 항목 HTML 생성
     * @param {object} note 메모 항목
     * @returns {string} HTML 문자열
     */
    function createStickyNoteHtml(note) {
        // 타입별 색상
        const typeColors = {
            'timescaffolding': '#8b5cf6',
            'chapter': '#3b82f6',
            'edittoday': '#10b981',
            'mystudy': '#f59e0b',
            'today': '#ef4444'
        };

        const bgColor = typeColors[note.type] || '#64748b';

        return `
            <div class="sticky-note" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px;
                                          box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid ${bgColor};
                                          transition: transform 0.3s, box-shadow 0.3s;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="background: ${bgColor}; color: white; padding: 4px 12px; border-radius: 12px;
                                     font-size: 12px; font-weight: 600;">
                            ${escapeHtml(note.type_label)}
                        </span>
                        <span style="color: #1e293b; font-weight: 600;">ID: ${note.id}</span>
                    </div>
                    <span style="color: #666; font-size: 14px;">${note.date} ${note.time}</span>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; line-height: 1.8; color: #1e293b;">
                    ${escapeHtml(note.content)}
                </div>
            </div>
        `;
    }

    /**
     * 종합 피드백 로드
     * @param {string} period 기간
     */
    async function loadComprehensiveFeedback(period) {
        console.log('[teacher_feedback_panel.php:365] Loading comprehensive feedback for period:', period);

        showLoading();

        try {
            const response = await fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/common/api/comprehensive_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'getComprehensiveFeedback',
                    period: period,
                    user_id: studentId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} [teacher_feedback_panel.php:383]`);
            }

            const data = await response.json();
            console.log('[teacher_feedback_panel.php:387] Comprehensive feedback data:', data);

            if (data.success) {
                displayComprehensiveFeedback(data);
            } else {
                console.error('[teacher_feedback_panel.php:392] API error:', data.error);
                showEmptyState();
            }

        } catch (error) {
            console.error('[teacher_feedback_panel.php:397] Fetch error:', error);
            showEmptyState();
        }
    }

    /**
     * 종합 피드백 표시
     * @param {object} data API 응답 데이터
     */
    function displayComprehensiveFeedback(data) {
        const content = document.getElementById('comprehensive-content');
        content.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="color: #1e293b; margin-bottom: 20px;">📊 종합 피드백 리포트</h3>
                <div style="white-space: pre-wrap; line-height: 1.8; color: #334155;">
                    ${escapeHtml(data.report || '종합 피드백 데이터를 생성하는 중입니다...')}
                </div>
            </div>
        `;

        hideLoading();
        document.getElementById('math-diary-display').style.display = 'none';
        document.getElementById('comprehensive-display').style.display = 'block';
        document.getElementById('empty-feedback').style.display = 'none';
    }

    /**
     * 로딩 표시
     */
    function showLoading() {
        document.getElementById('feedback-loading').style.display = 'block';
        document.getElementById('math-diary-display').style.display = 'none';
        document.getElementById('comprehensive-display').style.display = 'none';
        document.getElementById('empty-feedback').style.display = 'none';
    }

    /**
     * 로딩 숨기기
     */
    function hideLoading() {
        document.getElementById('feedback-loading').style.display = 'none';
    }

    /**
     * 빈 상태 표시
     */
    function showEmptyState() {
        hideLoading();
        document.getElementById('math-diary-display').style.display = 'none';
        document.getElementById('comprehensive-display').style.display = 'none';
        document.getElementById('empty-feedback').style.display = 'block';
    }

    /**
     * HTML 이스케이프
     * @param {string} text 원본 텍스트
     * @returns {string} 이스케이프된 텍스트
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
</script>

<!--
Database Tables:
- mdl_abessi_todayplans: 수학일기 데이터 (포모도로 세션)
  Fields: userid (int), timecreated (int),
          plan1-16 (text) - 학습 계획 내용,
          due1-16 (int) - 소요 시간(분),
          url1-16 (text) - 관련 URL,
          status01-16 (text) - 만족도,
          fback01-16 (text) - 교사/AI 피드백

- mdl_abessi_stickynotes: 메모장 전달 내용
  Fields: id (int), userid (int), type (varchar), content (text),
          created_at (varchar/int), updated_at (varchar/int)
  Types: timescaffolding (포모도로), chapter (컨텐츠 페이지),
         edittoday (목표설정), mystudy (내공부방), today (공부결과)
-->
