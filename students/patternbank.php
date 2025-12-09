<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pattern Bank Interface</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f5f6fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
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

        /* 상단 섹션 */
        .top-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
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
        }

        /* 하단 섹션 */
        .bottom-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* 문제 블록 스타일 */
        .problem-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 8px;
            margin-bottom: 15px;
            min-height: 100px;
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
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
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
            width: 100%;
            padding: 12px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-button:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .add-button:active {
            transform: translateY(0);
        }

        .add-button.similar {
            background-color: #27ae60;
        }

        .add-button.similar:hover {
            background-color: #229954;
        }

        .add-button.variant {
            background-color: #e74c3c;
        }

        .add-button.variant:hover {
            background-color: #c0392b;
        }

        /* 로딩 애니메이션 */
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
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
            padding: 30mm 25mm;
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
            margin-bottom: 40px;
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
            margin-bottom: 30px;
            padding-left: 20px;
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

        /* 인쇄 스타일 */
        @media print {
            body * {
                visibility: hidden;
            }
            .exam-content,
            .exam-content * {
                visibility: visible;
            }
            .exam-content {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
                box-shadow: none;
            }
            .print-button,
            .close {
                display: none !important;
            }
        }

        /* 반응형 디자인 */
        @media (max-width: 1200px) {
            .problem-grid {
                grid-template-columns: repeat(8, 1fr);
            }
        }

        @media (max-width: 768px) {
            .top-section,
            .bottom-section {
                grid-template-columns: 1fr;
            }
            .problem-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            .header-section {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>Pattern Bank 인터페이스</h1>
            <button class="exam-button" onclick="createExam()">
                📝 시험지 출제
            </button>
        </div>
        
        <!-- 상단 섹션 -->
        <div class="top-section">
            <!-- 대표유형 -->
            <div class="card">
                <h2 class="card-header">📋 대표유형</h2>
                <div class="representative-type">
                    <div class="type-title">수열의 규칙성 찾기</div>
                    <div class="type-content">
                        주어진 수열에서 일정한 규칙을 찾아 다음 항을 구하는 문제입니다.<br>
                        예시: 2, 4, 8, 16, ? (답: 32)<br>
                        규칙: 각 항이 이전 항의 2배가 되는 등비수열
                    </div>
                </div>
            </div>
            
            <!-- 유형 분석글 -->
            <div class="card">
                <h2 class="card-header">📊 유형 분석</h2>
                <div class="analysis-text">
                    수열의 규칙성 문제는 대수적 사고력을 평가하는 핵심 문제 유형입니다. 
                    이 유형은 주로 등차수열, 등비수열, 피보나치 수열 등의 기본 패턴을 변형하여 출제됩니다.
                    <br><br>
                    학생들은 첫 번째로 인접한 항들 간의 차이나 비율을 계산하여 규칙을 찾아야 합니다. 
                    복잡한 문제의 경우, 두 가지 이상의 규칙이 복합적으로 적용되거나, 
                    홀수 번째와 짝수 번째 항이 서로 다른 규칙을 따르는 경우도 있습니다.
                </div>
            </div>
        </div>
        
        <!-- 하단 섹션 -->
        <div class="bottom-section">
            <!-- 유사문제 -->
            <div class="card">
                <h2 class="card-header">🔄 유사문제</h2>
                <div class="problem-grid" id="similarProblems">
                    <div class="problem-block" data-id="1" data-content="3, 6, 12, 24, ?" data-answer="48">
                        문제 1
                        <div class="tooltip">
                            문제: 3, 6, 12, 24, ?<br>
                            답: 48<br>
                            난이도: 하
                        </div>
                    </div>
                    <div class="problem-block" data-id="2" data-content="5, 10, 20, 40, ?" data-answer="80">
                        문제 2
                        <div class="tooltip">
                            문제: 5, 10, 20, 40, ?<br>
                            답: 80<br>
                            난이도: 하
                        </div>
                    </div>
                    <div class="problem-block" data-id="3" data-content="1, 2, 4, 8, ?" data-answer="16">
                        문제 3
                        <div class="tooltip">
                            문제: 1, 2, 4, 8, ?<br>
                            답: 16<br>
                            난이도: 하
                        </div>
                    </div>
                </div>
                <button class="add-button similar" onclick="addSimilarProblem()">
                    <span>➕ 유사문제 추가</span>
                    <div class="loading" id="similarLoading"></div>
                </button>
            </div>
            
            <!-- 변형문제 -->
            <div class="card">
                <h2 class="card-header">🔀 변형문제</h2>
                <div class="problem-grid" id="variantProblems">
                    <div class="problem-block" data-id="4" data-content="2, 3, 5, 9, 17, ?" data-answer="33">
                        문제 1
                        <div class="tooltip">
                            문제: 2, 3, 5, 9, 17, ?<br>
                            답: 33<br>
                            난이도: 중
                        </div>
                    </div>
                    <div class="problem-block" data-id="5" data-content="1, 4, 9, 16, 25, ?" data-answer="36">
                        문제 2
                        <div class="tooltip">
                            문제: 1, 4, 9, 16, 25, ?<br>
                            답: 36<br>
                            난이도: 중
                        </div>
                    </div>
                    <div class="problem-block" data-id="6" data-content="2, 6, 12, 20, 30, ?" data-answer="42">
                        문제 3
                        <div class="tooltip">
                            문제: 2, 6, 12, 20, 30, ?<br>
                            답: 42<br>
                            난이도: 중
                        </div>
                    </div>
                </div>
                <button class="add-button variant" onclick="addVariantProblem()">
                    <span>➕ 변형문제 추가</span>
                    <div class="loading" id="variantLoading"></div>
                </button>
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
        // 원본 문제 정보
        const originalProblem = {
            type: "수열의 규칙성 찾기",
            pattern: "등비수열",
            example: "2, 4, 8, 16, ?",
            answer: "32",
            difficulty: "중급"
        };

        // 문제 선택 기능
        document.querySelectorAll('.problem-block').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
        });

        // 유사문제 추가
        async function addSimilarProblem() {
            const button = event.target.closest('button');
            const loading = document.getElementById('similarLoading');
            const buttonText = button.querySelector('span');
            
            // 로딩 상태 표시
            buttonText.style.display = 'none';
            loading.style.display = 'block';
            
            // API 호출 시뮬레이션
            const apiData = {
                originalProblem: originalProblem,
                type: 'similar',
                requestTime: new Date().toISOString()
            };
            
            console.log('API 요청 데이터:', apiData);
            
            // 실제 API 호출 대신 시뮬레이션
            setTimeout(() => {
                // 새 문제 생성 (시뮬레이션)
                const problemData = generateSimilarProblem();
                const grid = document.getElementById('similarProblems');
                const problemCount = grid.children.length + 1;
                
                const newProblem = {
                    id: Date.now(),
                    number: problemCount,
                    ...problemData
                };
                
                // DOM에 추가
                addProblemBlock('similarProblems', newProblem);
                
                // 로딩 상태 해제
                buttonText.style.display = 'inline';
                loading.style.display = 'none';
                
                // 성공 메시지
                showModal('유사문제 추가 완료', `새로운 유사문제가 추가되었습니다.\n문제: ${newProblem.content}`);
            }, 1500);
        }

        // 변형문제 추가
        async function addVariantProblem() {
            const button = event.target.closest('button');
            const loading = document.getElementById('variantLoading');
            const buttonText = button.querySelector('span');
            
            // 로딩 상태 표시
            buttonText.style.display = 'none';
            loading.style.display = 'block';
            
            // API 호출 시뮬레이션
            const apiData = {
                originalProblem: originalProblem,
                type: 'variant',
                requestTime: new Date().toISOString()
            };
            
            console.log('API 요청 데이터:', apiData);
            
            // 실제 API 호출 대신 시뮬레이션
            setTimeout(() => {
                // 새 문제 생성 (시뮬레이션)
                const problemData = generateVariantProblem();
                const grid = document.getElementById('variantProblems');
                const problemCount = grid.children.length + 1;
                
                const newProblem = {
                    id: Date.now(),
                    number: problemCount,
                    ...problemData
                };
                
                // DOM에 추가
                addProblemBlock('variantProblems', newProblem);
                
                // 로딩 상태 해제
                buttonText.style.display = 'inline';
                loading.style.display = 'none';
                
                // 성공 메시지
                showModal('변형문제 추가 완료', `새로운 변형문제가 추가되었습니다.\n문제: ${newProblem.content}`);
            }, 1500);
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
            const block = document.createElement('div');
            block.className = 'problem-block';
            block.setAttribute('data-id', problem.id);
            block.setAttribute('data-content', problem.content);
            block.setAttribute('data-answer', problem.answer);
            block.innerHTML = `
                문제 ${problem.number}
                <div class="tooltip">
                    문제: ${problem.content}<br>
                    답: ${problem.answer}<br>
                    난이도: ${problem.difficulty}
                </div>
            `;
            
            // 클릭 이벤트 추가
            block.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
            
            grid.appendChild(block);
        }

        // 시험지 생성
        function createExam() {
            const similarSelected = document.querySelectorAll('#similarProblems .problem-block.selected');
            const variantSelected = document.querySelectorAll('#variantProblems .problem-block.selected');
            
            if (similarSelected.length < 3 || variantSelected.length < 3) {
                alert('유사문제와 변형문제를 각각 3개씩 선택해주세요.');
                return;
            }
            
            // 선택된 문제들 가져오기
            const similarProblems = Array.from(similarSelected).slice(0, 3).map(block => ({
                content: block.getAttribute('data-content'),
                answer: block.getAttribute('data-answer')
            }));
            
            const variantProblems = Array.from(variantSelected).slice(0, 3).map(block => ({
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
                
                <div class="exam-section">
                    <div class="section-title">II. 변형문제 (각 15점)</div>
                    ${variantProblems.map((p, i) => `
                        <div class="exam-problem">
                            <div class="problem-number">${i + 4}. 다음 수열의 규칙을 찾아 빈칸에 들어갈 수를 구하시오.</div>
                            <div class="problem-content">${p.content}</div>
                            <div class="answer-space">답:</div>
                        </div>
                    `).join('')}
                </div>
                
                <div style="margin-top: 50px; padding: 20px; background-color: #f0f0f0; border-radius: 8px;">
                    <strong>채점 기준</strong><br>
                    - 유사문제: 각 10점 (총 30점)<br>
                    - 변형문제: 각 15점 (총 45점)<br>
                    - 총점: 75점
                </div>
            `;
            
            document.getElementById('examPaper').innerHTML = examHTML;
            document.getElementById('examModal').style.display = 'block';
        }

        // 시험지 인쇄
        function printExam() {
            window.print();
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

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeExamModal();
            }
        });
    </script>
</body>
</html>