<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidation Home - 수학 수업 관리</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            max-width: 1200px;
            width: 100%;
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            color: #666;
            font-size: 18px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .menu-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .menu-section:hover::before {
            transform: scaleX(1);
        }

        .menu-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .menu-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .menu-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }

        .exam-prep .menu-icon {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        }

        .concept-review .menu-icon {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
        }

        .learning-analysis .menu-icon {
            background: linear-gradient(135deg, #6c5ce7, #574b90);
        }

        .menu-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .menu-items {
            list-style: none;
        }

        .menu-item {
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-item:hover {
            background: white;
            border-color: #667eea;
            transform: translateX(5px);
        }

        .menu-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .menu-item-text {
            font-size: 16px;
            font-weight: 500;
        }

        .menu-item-arrow {
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
        }

        .menu-item:hover .menu-item-arrow {
            opacity: 1;
            transform: translateX(0);
        }

        .add-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 28px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-button:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 6px 30px rgba(102, 126, 234, 0.6);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #333;
            color: white;
            padding: 16px 32px;
            border-radius: 8px;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 2000;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Consolidation Home</h1>
            <p>수학 수업 상황별 학습 관리 시스템</p>
        </div>

        <div class="menu-grid">
            <!-- 시험대비 섹션 -->
            <div class="menu-section exam-prep">
                <div class="menu-header">
                    <div class="menu-icon">📝</div>
                    <h2 class="menu-title">시험대비</h2>
                </div>
                <ul class="menu-items" id="exam-prep-items">
                    <li class="menu-item" onclick="selectItem(this, '학교기출 분석')">
                        <span class="menu-item-text">학교기출 분석*</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                    <li class="menu-item" onclick="selectItem(this, '응시전략 분석')">
                        <span class="menu-item-text">응시전략 분석</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                    <li class="menu-item" onclick="selectItem(this, '최종 기억인출')">
                        <span class="menu-item-text">최종 기억인출</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                </ul>
            </div>

            <!-- 복습전략 섹션 -->
            <div class="menu-section concept-review">
                <div class="menu-header">
                    <div class="menu-icon">🔄</div>
                    <h2 class="menu-title">복습전략</h2>
                </div>
                <ul class="menu-items" id="concept-review-items">
                    <li class="menu-item" onclick="selectItem(this, '주간 복습설계')">
                        <span class="menu-item-text">주간 복습설계*</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                    <li class="menu-item" onclick="selectItem(this, '분기 복습설계')">
                        <span class="menu-item-text">분기 복습설계</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                    <li class="menu-item" onclick="selectItem(this, '상황 복습설계')">
                        <span class="menu-item-text">상황 복습설계</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                </ul>
            </div>

            <!-- 학습분석 섹션 -->
            <div class="menu-section learning-analysis">
                <div class="menu-header">
                    <div class="menu-icon">📊</div>
                    <h2 class="menu-title">학습분석</h2>
                </div>
                <ul class="menu-items" id="learning-analysis-items">
                    <li class="menu-item" onclick="selectItem(this, '메타인지 취약지점 분석')">
                        <span class="menu-item-text">메타인지 취약지점 분석</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                    <li class="menu-item" onclick="selectItem(this, '커리큘럼 취약지점 분석')">
                        <span class="menu-item-text">커리큘럼 취약지점 분석</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                    <li class="menu-item" onclick="selectItem(this, '수학일기 취약지점 분석')">
                        <span class="menu-item-text">수학일기 취약지점 분석*</span>
                        <span class="menu-item-arrow">→</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 추가 버튼 -->
    <button class="add-button" onclick="openModal()">+</button>

    <!-- 모달 -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2>새로운 기능 추가</h2>
            <form id="addForm">
                <div class="form-group">
                    <label for="category">카테고리 선택</label>
                    <select id="category" required>
                        <option value="">카테고리를 선택하세요</option>
                        <option value="exam-prep">시험대비</option>
                        <option value="concept-review">복습전략</option>
                        <option value="learning-analysis">학습분석</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="itemName">기능 이름</label>
                    <input type="text" id="itemName" placeholder="새로운 기능 이름을 입력하세요" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary">추가하기</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">취소</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 토스트 메시지 -->
    <div class="toast" id="toast"></div>

    <script>
        // 아이템 선택 함수
        function selectItem(element, itemName) {
            // 이전 선택 해제
            const previousActive = element.parentElement.querySelector('.active');
            if (previousActive) {
                previousActive.classList.remove('active');
            }
            
            // 현재 아이템 선택
            element.classList.add('active');
            
            // 페이지 이동 처리
            let targetPage = '';
            switch(itemName) {
                case '학교기출 분석':
                    targetPage = 'exam_analysis.php';
                    break;
                case '수학일기 취약지점 분석':
                    targetPage = 'mathnote_analysis.html';
                    break;
                case '최종 기억인출':
                    targetPage = 'optimized_retrieval.php';
                    break;
                case '주간 복습설계':
                    targetPage = 'review_priority.php';
                    break;
                default:
                    // 학생에게 전달 (실제 구현에서는 API 호출)
                    showToast(`"${itemName}" 설정이 학생에게 전달되었습니다.`);
                    console.log('Selected:', itemName);
                    return;
            }
            
            // 페이지로 이동
            if (targetPage) {
                window.location.href = targetPage;
            }
        }

        // 모달 열기
        function openModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        // 모달 닫기
        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
            document.getElementById('addForm').reset();
        }

        // 토스트 메시지 표시
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // 폼 제출 처리
        document.getElementById('addForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const category = document.getElementById('category').value;
            const itemName = document.getElementById('itemName').value;
            
            if (!category || !itemName) {
                showToast('모든 필드를 입력해주세요.');
                return;
            }
            
            // 새 아이템 추가
            const itemsList = document.getElementById(category + '-items');
            const newItem = document.createElement('li');
            newItem.className = 'menu-item';
            newItem.onclick = function() { selectItem(this, itemName); };
            newItem.innerHTML = `
                <span class="menu-item-text">${itemName}</span>
                <span class="menu-item-arrow">→</span>
            `;
            
            itemsList.appendChild(newItem);
            
            // 애니메이션 효과
            setTimeout(() => {
                newItem.style.animation = 'modalSlideIn 0.3s ease';
            }, 10);
            
            showToast(`"${itemName}" 기능이 추가되었습니다.`);
            closeModal();
        });

        // 모달 외부 클릭시 닫기
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>