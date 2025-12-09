<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>미래 일기 작성 도구</title>
    <link rel="stylesheet" href="../../../styles/main.css">
    <style>
        .diary-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .diary-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        .prompts {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .prompts h4 {
            margin-bottom: 0.5rem;
            color: #3498db;
        }
        .prompts ul {
            margin-left: 1.5rem;
            color: #7f8c8d;
        }
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        .saved-diaries {
            margin-top: 2rem;
        }
        .diary-entry {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .diary-entry h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .diary-date {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>미래 일기 작성</h1>
            <p>5년 후의 나의 하루를 상상하며 일기를 작성해보세요</p>
        </header>

        <nav class="category-nav">
            <a href="../index.php" class="nav-item">← 시간 수정체로</a>
        </nav>

        <div class="diary-container">
            <div class="diary-form">
                <div class="prompts">
                    <h4>작성 가이드</h4>
                    <ul>
                        <li>5년 후 오늘, 당신은 어디에서 무엇을 하고 있나요?</li>
                        <li>어떤 일을 하며, 어떤 사람들과 함께하고 있나요?</li>
                        <li>하루 일과 중 가장 만족스러운 순간은 무엇인가요?</li>
                        <li>어떤 목표를 달성했고, 다음 목표는 무엇인가요?</li>
                        <li>현재의 자신에게 해주고 싶은 조언은 무엇인가요?</li>
                    </ul>
                </div>

                <form id="futureDiaryForm">
                    <div class="form-group">
                        <label for="futureDate">미래 날짜</label>
                        <input type="date" id="futureDate" name="futureDate" required>
                    </div>

                    <div class="form-group">
                        <label for="diaryTitle">일기 제목</label>
                        <input type="text" id="diaryTitle" name="diaryTitle" 
                               placeholder="예: 꿈꾸던 회사에서의 첫 프로젝트 성공" required>
                    </div>

                    <div class="form-group">
                        <label for="morningRoutine">아침 일과</label>
                        <textarea id="morningRoutine" name="morningRoutine" 
                                  placeholder="미래의 나는 어떻게 하루를 시작하나요?"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="workLife">일과 커리어</label>
                        <textarea id="workLife" name="workLife" 
                                  placeholder="어떤 일을 하고 있으며, 어떤 성취를 이루었나요?"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="relationships">인간관계</label>
                        <textarea id="relationships" name="relationships" 
                                  placeholder="어떤 사람들과 함께하고, 어떤 관계를 맺고 있나요?"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="achievements">달성한 목표들</label>
                        <textarea id="achievements" name="achievements" 
                                  placeholder="지난 5년간 이룬 성취들을 돌아보세요"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="adviceToPresent">현재의 나에게 주는 조언</label>
                        <textarea id="adviceToPresent" name="adviceToPresent" 
                                  placeholder="미래의 관점에서 현재의 자신에게 해주고 싶은 말은?"></textarea>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="saveDraft()">임시 저장</button>
                        <button type="submit" class="btn btn-primary">일기 저장</button>
                    </div>
                </form>
            </div>

            <div class="saved-diaries" id="savedDiaries">
                <h2>저장된 미래 일기</h2>
                <div id="diaryList"></div>
            </div>
        </div>
    </div>

    <script>
        // 미래 날짜 자동 설정 (5년 후)
        document.addEventListener('DOMContentLoaded', function() {
            const futureDateInput = document.getElementById('futureDate');
            const today = new Date();
            const futureDate = new Date(today.setFullYear(today.getFullYear() + 5));
            futureDateInput.value = futureDate.toISOString().split('T')[0];
            
            loadSavedDiaries();
        });

        // 폼 제출 처리
        document.getElementById('futureDiaryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveDiary();
        });

        // 일기 저장
        function saveDiary() {
            const formData = {
                id: Date.now(),
                createdDate: new Date().toISOString(),
                futureDate: document.getElementById('futureDate').value,
                title: document.getElementById('diaryTitle').value,
                morningRoutine: document.getElementById('morningRoutine').value,
                workLife: document.getElementById('workLife').value,
                relationships: document.getElementById('relationships').value,
                achievements: document.getElementById('achievements').value,
                adviceToPresent: document.getElementById('adviceToPresent').value
            };

            // 로컬 스토리지에 저장
            let diaries = JSON.parse(localStorage.getItem('futureDiaries') || '[]');
            diaries.unshift(formData);
            localStorage.setItem('futureDiaries', JSON.stringify(diaries));

            // 폼 초기화
            document.getElementById('futureDiaryForm').reset();
            
            // 미래 날짜 재설정
            const futureDateInput = document.getElementById('futureDate');
            const today = new Date();
            const futureDate = new Date(today.setFullYear(today.getFullYear() + 5));
            futureDateInput.value = futureDate.toISOString().split('T')[0];

            // 저장된 일기 목록 새로고침
            loadSavedDiaries();
            
            alert('미래 일기가 저장되었습니다!');
        }

        // 임시 저장
        function saveDraft() {
            const formElements = document.getElementById('futureDiaryForm').elements;
            const draft = {};
            
            for (let element of formElements) {
                if (element.name) {
                    draft[element.name] = element.value;
                }
            }
            
            localStorage.setItem('futureDiaryDraft', JSON.stringify(draft));
            alert('임시 저장되었습니다!');
        }

        // 저장된 일기 불러오기
        function loadSavedDiaries() {
            const diaries = JSON.parse(localStorage.getItem('futureDiaries') || '[]');
            const diaryList = document.getElementById('diaryList');
            
            if (diaries.length === 0) {
                diaryList.innerHTML = '<p style="text-align: center; color: #7f8c8d;">아직 작성된 미래 일기가 없습니다.</p>';
                return;
            }

            diaryList.innerHTML = diaries.map(diary => `
                <div class="diary-entry">
                    <h3>${diary.title}</h3>
                    <div class="diary-date">
                        작성일: ${new Date(diary.createdDate).toLocaleDateString('ko-KR')} | 
                        미래 날짜: ${new Date(diary.futureDate).toLocaleDateString('ko-KR')}
                    </div>
                    <div class="diary-content">
                        ${diary.adviceToPresent ? `<p><strong>현재의 나에게:</strong> ${diary.adviceToPresent}</p>` : ''}
                    </div>
                    <a href="#" onclick="viewDiary(${diary.id}); return false;" class="card-link">전체 보기</a>
                </div>
            `).join('');
        }

        // 일기 상세 보기
        function viewDiary(id) {
            const diaries = JSON.parse(localStorage.getItem('futureDiaries') || '[]');
            const diary = diaries.find(d => d.id === id);
            
            if (diary) {
                // 새 창에서 일기 표시 (실제 구현 시 모달이나 별도 페이지로 표시)
                const content = `
                    <h2>${diary.title}</h2>
                    <p><strong>미래 날짜:</strong> ${new Date(diary.futureDate).toLocaleDateString('ko-KR')}</p>
                    <p><strong>아침 일과:</strong> ${diary.morningRoutine}</p>
                    <p><strong>일과 커리어:</strong> ${diary.workLife}</p>
                    <p><strong>인간관계:</strong> ${diary.relationships}</p>
                    <p><strong>달성한 목표들:</strong> ${diary.achievements}</p>
                    <p><strong>현재의 나에게:</strong> ${diary.adviceToPresent}</p>
                `;
                
                // 임시로 alert로 표시 (실제로는 모달이나 별도 뷰 구현 필요)
                alert(content.replace(/<[^>]*>/g, ''));
            }
        }
    </script>
</body>
</html>