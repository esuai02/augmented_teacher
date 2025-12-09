<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주간 복습 설계 - 수학 학습 관리</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f9fafb;
            min-height: 100vh;
            padding: 24px;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-button {
            padding: 8px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nav-button:hover {
            background: #f3f4f6;
        }

        .current-week-button {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .current-week-button:hover {
            background: #2563eb;
        }

        /* Philosophy Banner */
        .philosophy-banner {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 8px;
        }

        .philosophy-banner-icon {
            color: #d97706;
            flex-shrink: 0;
        }

        .philosophy-content p:first-child {
            font-weight: 500;
            color: #92400e;
            margin-bottom: 4px;
        }

        .philosophy-content p:last-child {
            color: #b45309;
            font-size: 14px;
        }

        /* Main Layout */
        .main-layout {
            display: flex;
            gap: 24px;
        }

        .weekly-grid {
            flex: 1;
        }

        .week-columns {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }

        /* Week Card */
        .week-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .week-card.current-week {
            box-shadow: 0 0 0 2px #3b82f6;
        }

        .week-header {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .week-header.past {
            background: #f3f4f6;
        }

        .week-header.current {
            background: #dbeafe;
        }

        .week-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .week-dates {
            font-size: 14px;
            color: #6b7280;
        }

        .week-content {
            padding: 16px;
            min-height: 300px;
            position: relative;
        }

        .week-content.drag-over {
            background: #f3f4f6;
        }

        .empty-message {
            color: #9ca3af;
            font-size: 14px;
            text-align: center;
            margin-top: 32px;
        }

        /* Review Item */
        .review-item {
            padding: 12px;
            border-radius: 8px;
            border: 2px solid;
            margin-bottom: 8px;
            cursor: move;
            transition: all 0.2s;
        }

        .review-item:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .review-item.impact-high {
            border-color: #fca5a5;
            background: #fee2e2;
        }

        .review-item.impact-medium {
            border-color: #fde047;
            background: #fef9c3;
        }

        .review-item.impact-low {
            border-color: #86efac;
            background: #dcfce7;
        }

        .review-item.past {
            opacity: 0.75;
            cursor: not-allowed;
        }

        .item-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .item-title {
            font-weight: 500;
            font-size: 14px;
        }

        .difficulty-badge {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 9999px;
            margin-bottom: 8px;
            display: inline-block;
        }

        .difficulty-easy {
            background: #d1fae5;
            color: #065f46;
        }

        .difficulty-medium {
            background: #fef3c7;
            color: #78350f;
        }

        .difficulty-hard {
            background: #fee2e2;
            color: #7f1d1d;
        }

        .item-notes {
            font-size: 12px;
            color: #4b5563;
            font-style: italic;
        }

        .impact-warning {
            margin-top: 8px;
            font-size: 12px;
            color: #dc2626;
            font-weight: 500;
        }

        /* Sidebar */
        .sidebar {
            width: 320px;
        }

        .sidebar-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: 100%;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 16px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 600;
        }

        .add-button {
            padding: 6px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .add-button:hover {
            background: #e5e7eb;
        }

        .sidebar-content {
            padding: 16px;
            min-height: 600px;
        }

        /* Add Form */
        .add-form {
            margin-bottom: 16px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .add-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .add-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-primary {
            padding: 6px 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            padding: 6px 12px;
            background: #d1d5db;
            color: #374151;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: #9ca3af;
        }

        /* Legend */
        .legend {
            margin-top: 24px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 16px;
        }

        .legend h3 {
            font-weight: 600;
            margin-bottom: 12px;
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .legend-section h4 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        /* Icons */
        .icon {
            width: 20px;
            height: 20px;
            display: inline-block;
        }

        .icon-sm {
            width: 16px;
            height: 16px;
        }

        /* Responsive */
        @media (max-width: 1280px) {
            .week-columns {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .week-columns {
                grid-template-columns: 1fr;
            }
        }

        /* Hidden class */
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h1>주별 복습 관리 시스템</h1>
                </div>
                <div class="nav-buttons">
                    <button class="nav-button" onclick="navigateWeek(-1)">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button class="current-week-button" onclick="navigateToCurrentWeek()">현재 주</button>
                    <button class="nav-button" onclick="navigateWeek(1)">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="philosophy-banner">
                <svg class="icon philosophy-banner-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
                <div class="philosophy-content">
                    <p>핵심 철학</p>
                    <p>이번 주 학습 문제는 지난 주 복습 설계가 원인입니다. 체계적인 복습 계획으로 미래의 학습을 준비하세요.</p>
                </div>
            </div>
        </div>

        <div class="main-layout">
            <!-- Weekly Grid -->
            <div class="weekly-grid">
                <div class="week-columns" id="weekColumns">
                    <!-- Week cards will be generated here -->
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <div class="sidebar-title">
                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <span>미배치 복습 항목</span>
                        </div>
                        <button class="add-button" onclick="toggleAddForm()">
                            <svg class="icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="sidebar-content" ondrop="handleDrop(event, 'unscheduled')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                        <div id="addForm" class="add-form hidden">
                            <input type="text" id="newItemInput" class="add-input" placeholder="새 복습 항목 입력..." onkeypress="handleAddKeyPress(event)">
                            <div class="form-buttons">
                                <button class="btn-primary" onclick="addNewItem()">추가</button>
                                <button class="btn-secondary" onclick="cancelAddForm()">취소</button>
                            </div>
                        </div>
                        
                        <div id="unscheduledItems">
                            <!-- Unscheduled items will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <h3>범례</h3>
            <div class="legend-grid">
                <div class="legend-section">
                    <h4>상태</h4>
                    <div class="legend-item">
                        <svg class="icon-sm" fill="none" stroke="currentColor" style="color: #10b981;" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>완료</span>
                    </div>
                    <div class="legend-item">
                        <svg class="icon-sm" fill="none" stroke="currentColor" style="color: #eab308;" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>부분 완료</span>
                    </div>
                    <div class="legend-item">
                        <svg class="icon-sm" fill="none" stroke="currentColor" style="color: #3b82f6;" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>진행 중</span>
                    </div>
                    <div class="legend-item">
                        <svg class="icon-sm" fill="none" stroke="currentColor" style="color: #9ca3af;" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <span>미배치</span>
                    </div>
                </div>
                
                <div class="legend-section">
                    <h4>영향도</h4>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #f87171;"></div>
                        <span>높음</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #fbbf24;"></div>
                        <span>중간</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #34d399;"></div>
                        <span>낮음</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // State management
        let currentWeekOffset = 0;
        let draggedItem = null;
        let draggedFromWeek = null;
        let reviewItems = {
            '-1': [
                { id: 'r1', title: '집합과 명제', status: 'completed', impact: 'high', notes: '조건과 명제 구분 어려움으로 이번 주 문제 발생' },
                { id: 'r2', title: '함수의 정의와 그래프', status: 'partial', impact: 'medium', notes: '정의역과 치역 개념 복습 시간 부족' }
            ],
            '0': [
                { id: 'r3', title: '지수함수와 로그함수', status: 'pending', impact: 'high', difficulty: 'hard' },
                { id: 'r4', title: '삼각함수의 정의', status: 'pending', impact: 'medium', difficulty: 'medium' }
            ],
            '1': [
                { id: 'r5', title: '수열의 합', status: 'scheduled', impact: 'medium', difficulty: 'easy' },
                { id: 'r6', title: '지수·로그 방정식', status: 'scheduled', impact: 'high', difficulty: 'hard' }
            ],
            '2': [
                { id: 'r7', title: '등차수열과 등비수열', status: 'scheduled', impact: 'low', difficulty: 'medium' }
            ],
            'unscheduled': [
                { id: 'r8', title: '삼각함수의 그래프', status: 'unscheduled', impact: 'medium', difficulty: 'medium' },
                { id: 'r9', title: '삼각함수의 활용', status: 'unscheduled', impact: 'high', difficulty: 'hard' },
                { id: 'r10', title: '수열의 귀납적 정의', status: 'unscheduled', impact: 'medium', difficulty: 'hard' },
                { id: 'r11', title: '여러 가지 수열', status: 'unscheduled', impact: 'low', difficulty: 'easy' },
                { id: 'r12', title: '시그마 기호', status: 'unscheduled', impact: 'low', difficulty: 'medium' },
                { id: 'r13', title: '수학적 귀납법', status: 'unscheduled', impact: 'high', difficulty: 'hard' }
            ]
        };

        // Helper functions
        function getWeekDates(offset) {
            const today = new Date();
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay() + (offset * 7));
            
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6);
            
            return {
                start: weekStart.toLocaleDateString('ko-KR', { month: 'short', day: 'numeric' }),
                end: weekEnd.toLocaleDateString('ko-KR', { month: 'short', day: 'numeric' })
            };
        }

        function getWeekLabel(offset) {
            if (offset === -1) return '지난 주';
            if (offset === 0) return '이번 주';
            if (offset === 1) return '다음 주';
            return `${offset}주 후`;
        }

        function getStatusIcon(status) {
            const icons = {
                completed: '<svg class="icon-sm" fill="none" stroke="currentColor" style="color: #10b981;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                partial: '<svg class="icon-sm" fill="none" stroke="currentColor" style="color: #eab308;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                pending: '<svg class="icon-sm" fill="none" stroke="currentColor" style="color: #3b82f6;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                unscheduled: '<svg class="icon-sm" fill="none" stroke="currentColor" style="color: #9ca3af;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>',
                scheduled: '<svg class="icon-sm" fill="none" stroke="currentColor" style="color: #6b7280;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>'
            };
            return icons[status] || icons.scheduled;
        }

        function getDifficultyBadge(difficulty) {
            const labels = {
                easy: '쉬움',
                medium: '보통',
                hard: '어려움'
            };
            return `<span class="difficulty-badge difficulty-${difficulty}">${labels[difficulty]}</span>`;
        }

        // Drag and drop handlers
        function handleDragStart(event, item, weekOffset) {
            draggedItem = item;
            draggedFromWeek = weekOffset;
            event.dataTransfer.effectAllowed = 'move';
        }

        function handleDragOver(event) {
            event.preventDefault();
            event.currentTarget.classList.add('drag-over');
        }

        function handleDragLeave(event) {
            event.currentTarget.classList.remove('drag-over');
        }

        function handleDrop(event, targetWeekOffset) {
            event.preventDefault();
            event.currentTarget.classList.remove('drag-over');

            if (draggedItem && draggedFromWeek !== null && draggedFromWeek !== targetWeekOffset) {
                // Remove from source
                reviewItems[draggedFromWeek] = reviewItems[draggedFromWeek].filter(item => item.id !== draggedItem.id);
                
                // Update status based on target
                const updatedItem = { ...draggedItem };
                if (targetWeekOffset === 'unscheduled') {
                    updatedItem.status = 'unscheduled';
                } else if (typeof targetWeekOffset === 'number') {
                    updatedItem.status = targetWeekOffset === 0 ? 'pending' : 'scheduled';
                }
                
                // Add to target
                if (!reviewItems[targetWeekOffset]) {
                    reviewItems[targetWeekOffset] = [];
                }
                reviewItems[targetWeekOffset].push(updatedItem);
                
                // Re-render
                renderWeeks();
                renderUnscheduledItems();
            }
            
            draggedItem = null;
            draggedFromWeek = null;
        }

        // UI functions
        function navigateWeek(direction) {
            currentWeekOffset += direction;
            renderWeeks();
        }

        function navigateToCurrentWeek() {
            currentWeekOffset = 0;
            renderWeeks();
        }

        function toggleAddForm() {
            const addForm = document.getElementById('addForm');
            addForm.classList.toggle('hidden');
            if (!addForm.classList.contains('hidden')) {
                document.getElementById('newItemInput').focus();
            }
        }

        function cancelAddForm() {
            document.getElementById('addForm').classList.add('hidden');
            document.getElementById('newItemInput').value = '';
        }

        function handleAddKeyPress(event) {
            if (event.key === 'Enter') {
                addNewItem();
            }
        }

        function addNewItem() {
            const input = document.getElementById('newItemInput');
            const title = input.value.trim();
            
            if (title) {
                const newItem = {
                    id: `r${Date.now()}`,
                    title: title,
                    status: 'unscheduled',
                    impact: 'medium',
                    difficulty: 'medium'
                };
                
                reviewItems.unscheduled.push(newItem);
                renderUnscheduledItems();
                
                input.value = '';
                cancelAddForm();
            }
        }

        // Render functions
        function renderReviewItem(item, weekOffset) {
            const isPast = weekOffset < 0;
            const draggable = !isPast;
            
            return `
                <div class="review-item impact-${item.impact} ${isPast ? 'past' : ''}" 
                     draggable="${draggable}"
                     ondragstart="handleDragStart(event, ${JSON.stringify(item).replace(/"/g, '&quot;')}, ${typeof weekOffset === 'string' ? `'${weekOffset}'` : weekOffset})">
                    <div class="item-header">
                        ${getStatusIcon(item.status)}
                        <span class="item-title">${item.title}</span>
                    </div>
                    ${item.difficulty ? getDifficultyBadge(item.difficulty) : ''}
                    ${item.notes ? `<p class="item-notes">${item.notes}</p>` : ''}
                    ${isPast && item.impact === 'high' ? '<div class="impact-warning">⚠️ 이번 주 학습에 영향</div>' : ''}
                </div>
            `;
        }

        function renderWeeks() {
            const weekColumns = document.getElementById('weekColumns');
            const visibleWeeks = [-1, 0, 1, 2, 3].map(w => w + currentWeekOffset);
            
            weekColumns.innerHTML = visibleWeeks.map(weekOffset => {
                const weekDates = getWeekDates(weekOffset);
                const items = reviewItems[weekOffset] || [];
                const isCurrentWeek = weekOffset === 0;
                const isPastWeek = weekOffset < 0;
                
                return `
                    <div class="week-card ${isCurrentWeek ? 'current-week' : ''}">
                        <div class="week-header ${isPastWeek ? 'past' : isCurrentWeek ? 'current' : ''}">
                            <h3 class="week-title">${getWeekLabel(weekOffset)}</h3>
                            <p class="week-dates">${weekDates.start} - ${weekDates.end}</p>
                        </div>
                        <div class="week-content" 
                             ondrop="handleDrop(event, ${weekOffset})" 
                             ondragover="handleDragOver(event)"
                             ondragleave="handleDragLeave(event)">
                            ${items.length === 0 
                                ? '<p class="empty-message">복습 항목을 드래그하여 추가하세요</p>'
                                : items.map(item => renderReviewItem(item, weekOffset)).join('')
                            }
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderUnscheduledItems() {
            const container = document.getElementById('unscheduledItems');
            const items = reviewItems.unscheduled || [];
            
            container.innerHTML = items.map(item => renderReviewItem(item, 'unscheduled')).join('');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderWeeks();
            renderUnscheduledItems();
        });
    </script>
</body>
</html>