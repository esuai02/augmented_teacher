<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학생 검색 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .search-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            align-self: flex-end;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn:active {
            transform: translateY(0);
        }

        .results-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .results-count {
            color: #666;
            font-size: 1.1em;
        }

        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .student-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .student-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .student-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .student-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-item {
            display: flex;
            align-items: center;
            color: #666;
        }

        .info-label {
            font-weight: 500;
            margin-right: 8px;
            min-width: 50px;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-results-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 학생 검색 시스템</h1>
            <p>이름, 학교, 학년으로 학생 정보를 검색하세요</p>
        </div>

        <div class="search-container">
            <form class="search-form" id="searchForm">
                <div class="form-group">
                    <label for="searchType">검색 유형</label>
                    <select class="form-control" id="searchType" name="searchType">
                        <option value="all">전체 검색</option>
                        <option value="name">이름으로 검색</option>
                        <option value="school">학교로 검색</option>
                        <option value="grade">학년으로 검색</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="searchQuery">검색어</label>
                    <input type="text" class="form-control" id="searchQuery" name="query" 
                           placeholder="검색어를 입력하세요">
                </div>
                <button type="submit" class="btn">검색</button>
            </form>
        </div>

        <div class="results-container">
            <div class="results-header">
                <h2>검색 결과</h2>
                <span class="results-count" id="resultsCount"></span>
            </div>
            <div id="resultsArea">
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <p>검색을 시작하려면 위에서 검색 조건을 입력하세요</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('searchForm');
            const resultsArea = document.getElementById('resultsArea');
            const resultsCount = document.getElementById('resultsCount');
            
            // 페이지 로드 시 전체 학생 목록 로드
            loadAllStudents();
            
            // 폼 제출 이벤트
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
            
            // 검색 수행
            function performSearch() {
                const searchType = document.getElementById('searchType').value;
                const searchQuery = document.getElementById('searchQuery').value;
                
                // 로딩 표시
                showLoading();
                
                // AJAX 요청
                fetch(`search.php?searchType=${searchType}&query=${encodeURIComponent(searchQuery)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayResults(data.data);
                            resultsCount.textContent = data.message;
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        showError('검색 중 오류가 발생했습니다: ' + error);
                    });
            }
            
            // 전체 학생 목록 로드
            function loadAllStudents() {
                showLoading();
                
                fetch('search.php?searchType=all&query=')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayResults(data.data);
                            resultsCount.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading students:', error);
                    });
            }
            
            // 결과 표시
            function displayResults(students) {
                if (students.length === 0) {
                    resultsArea.innerHTML = `
                        <div class="no-results">
                            <div class="no-results-icon">😔</div>
                            <p>검색 결과가 없습니다</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '<div class="student-grid">';
                students.forEach(student => {
                    html += `
                        <div class="student-card">
                            <div class="student-name">${escapeHtml(student.name)}</div>
                            <div class="student-info">
                                <div class="info-item">
                                    <span class="info-label">학교:</span>
                                    <span>${escapeHtml(student.school)}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">학년:</span>
                                    <span>${escapeHtml(student.grade)}학년</span>
                                </div>
                                ${student.class ? `
                                <div class="info-item">
                                    <span class="info-label">반:</span>
                                    <span>${escapeHtml(student.class)}반</span>
                                </div>
                                ` : ''}
                                ${student.student_id ? `
                                <div class="info-item">
                                    <span class="info-label">학번:</span>
                                    <span>${escapeHtml(student.student_id)}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultsArea.innerHTML = html;
            }
            
            // 로딩 표시
            function showLoading() {
                resultsArea.innerHTML = `
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>검색 중...</p>
                    </div>
                `;
            }
            
            // 오류 표시
            function showError(message) {
                resultsArea.innerHTML = `
                    <div class="no-results">
                        <div class="no-results-icon">❌</div>
                        <p>${escapeHtml(message)}</p>
                    </div>
                `;
            }
            
            // HTML 이스케이프
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text ? text.toString().replace(/[&<>"']/g, m => map[m]) : '';
            }
        });
    </script>
</body>
</html>