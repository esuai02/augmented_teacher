<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;
require_login();
$studentid=$_GET["userid"];

$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  ");
$role=$userrole->data;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTS 실험 추적 시스템</title>
    <link rel="stylesheet" href="prove_BTS.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>BTS 실험 추적 시스템</h1>
            <div class="user-info">
                <span>사용자: <?php echo $USER->firstname . ' ' . $USER->lastname; ?></span>
                <span>역할: <?php echo $role; ?></span>
            </div>
        </header>

        <nav class="navigation">
            <button class="nav-tab active" data-tab="design">실험 설계</button>
            <button class="nav-tab" data-tab="groups">그룹 배정</button>
            <button class="nav-tab" data-tab="tracking">데이터 추적</button>
            <button class="nav-tab" data-tab="intervention">개입 로그</button>
            <button class="nav-tab" data-tab="survey">설문 관리</button>
            <button class="nav-tab" data-tab="analysis">분석</button>
        </nav>

        <main class="main-content">
            <!-- 실험 설계 탭 -->
            <div id="design-tab" class="tab-content active">
                <div class="card">
                    <h3>실험 기본 설정</h3>
                    <form id="experiment-config-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="experiment-name">실험명</label>
                                <input type="text" id="experiment-name" placeholder="예: 메타인지 피드백 효과 검증">
                            </div>
                            <div class="form-group">
                                <label for="start-date">시작일</label>
                                <input type="date" id="start-date">
                            </div>
                            <div class="form-group">
                                <label for="duration">실험 기간 (주)</label>
                                <input type="number" id="duration" min="1" max="24" value="8">
                            </div>
                            <div class="form-group">
                                <label for="sessions-per-week">주당 세션 수</label>
                                <input type="number" id="sessions-per-week" min="1" max="7" value="2">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="intervention-type">개입 방법</label>
                            <select id="intervention-type">
                                <option value="meta">메타인지 피드백</option>
                                <option value="learning">학습인지 피드백</option>
                                <option value="combined">결합형 피드백</option>
                                <option value="control">통제그룹</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>측정 지표</label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" value="accuracy" checked> 정답률</label>
                                <label><input type="checkbox" value="responseTime" checked> 응답시간</label>
                                <label><input type="checkbox" value="questionFreq" checked> 질문빈도</label>
                                <label><input type="checkbox" value="blankRate"> 빈칸응답률</label>
                                <label><input type="checkbox" value="metacognitiveCheck"> 자기점검횟수</label>
                                <label><input type="checkbox" value="persistence"> 지속시간</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">실험 설정 저장</button>
                    </form>
                </div>
            </div>

            <!-- 그룹 배정 탭 -->
            <div id="groups-tab" class="tab-content">
                <div class="card">
                    <h3>학생 선택 및 그룹 배정</h3>
                    <div class="group-assignment">
                        <div class="students-pool">
                            <h4>학생 목록</h4>
                            <div class="student-search">
                                <input type="text" id="student-search" placeholder="학생 검색...">
                                <button id="load-students" class="btn btn-secondary">학생 불러오기</button>
                            </div>
                            <div id="students-list" class="students-list"></div>
                        </div>
                        <div class="groups-container">
                            <div class="group-section">
                                <h4>실험 그룹</h4>
                                <div id="experiment-group" class="group-list"></div>
                            </div>
                            <div class="group-section">
                                <h4>통제 그룹</h4>
                                <div id="control-group" class="group-list"></div>
                            </div>
                        </div>
                    </div>
                    <div class="group-actions">
                        <button id="random-assignment" class="btn btn-primary">무작위 배정</button>
                        <button id="clear-groups" class="btn btn-secondary">그룹 초기화</button>
                    </div>
                </div>
            </div>

            <!-- 데이터 추적 탭 -->
            <div id="tracking-tab" class="tab-content">
                <div class="card">
                    <h3>실험 진행 상태</h3>
                    <div class="experiment-status">
                        <div class="status-item">
                            <span class="status-label">상태:</span>
                            <span id="experiment-status" class="status-value">준비중</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">참가자:</span>
                            <span id="participant-count" class="status-value">0명</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">진행도:</span>
                            <span id="progress-percentage" class="status-value">0%</span>
                        </div>
                    </div>
                    <div class="experiment-controls">
                        <button id="start-experiment" class="btn btn-success">실험 시작</button>
                        <button id="pause-experiment" class="btn btn-warning" disabled>일시정지</button>
                        <button id="stop-experiment" class="btn btn-danger" disabled>실험 중지</button>
                    </div>
                </div>
                <div class="card">
                    <h3>실시간 데이터 모니터링</h3>
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <h4>정답률</h4>
                            <div class="metric-value" id="avg-accuracy">0%</div>
                        </div>
                        <div class="metric-card">
                            <h4>평균 응답시간</h4>
                            <div class="metric-value" id="avg-response-time">0초</div>
                        </div>
                        <div class="metric-card">
                            <h4>질문 빈도</h4>
                            <div class="metric-value" id="avg-question-freq">0회</div>
                        </div>
                        <div class="metric-card">
                            <h4>세션 완료율</h4>
                            <div class="metric-value" id="completion-rate">0%</div>
                        </div>
                    </div>
                    <div class="data-table">
                        <table id="tracking-data-table">
                            <thead>
                                <tr>
                                    <th>학생명</th>
                                    <th>그룹</th>
                                    <th>세션 수</th>
                                    <th>최근 정답률</th>
                                    <th>평균 응답시간</th>
                                    <th>진행률</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 개입 로그 탭 -->
            <div id="intervention-tab" class="tab-content">
                <div class="card">
                    <h3>개입 기록</h3>
                    <div class="intervention-controls">
                        <button id="add-intervention" class="btn btn-primary">개입 추가</button>
                        <button id="export-logs" class="btn btn-secondary">로그 내보내기</button>
                    </div>
                    <div class="intervention-log" id="intervention-log"></div>
                </div>
            </div>

            <!-- 설문 관리 탭 -->
            <div id="survey-tab" class="tab-content">
                <div class="card">
                    <h3>설문 관리</h3>
                    <div class="survey-types">
                        <div class="survey-type">
                            <h4>사전 설문</h4>
                            <p>실험 시작 전 기초선 측정</p>
                            <button class="btn btn-primary" data-survey="pre">설문 시작</button>
                        </div>
                        <div class="survey-type">
                            <h4>메타인지 인식 설문 (MAI)</h4>
                            <p>메타인지 역량 측정</p>
                            <button class="btn btn-primary" data-survey="metacognitive">설문 시작</button>
                        </div>
                        <div class="survey-type">
                            <h4>학습 동기 설문 (MSLQ)</h4>
                            <p>학습 동기 및 전략 평가</p>
                            <button class="btn btn-primary" data-survey="motivation">설문 시작</button>
                        </div>
                        <div class="survey-type">
                            <h4>피드백 만족도</h4>
                            <p>제공된 피드백의 효과성 평가</p>
                            <button class="btn btn-primary" data-survey="feedback">설문 시작</button>
                        </div>
                        <div class="survey-type">
                            <h4>사후 설문</h4>
                            <p>실험 종료 후 전반적 평가</p>
                            <button class="btn btn-primary" data-survey="post">설문 시작</button>
                        </div>
                    </div>
                    <div class="survey-responses">
                        <h4>설문 응답 현황</h4>
                        <div id="survey-response-table"></div>
                    </div>
                </div>
            </div>

            <!-- 분석 탭 -->
            <div id="analysis-tab" class="tab-content">
                <div class="card">
                    <h3>데이터 분석</h3>
                    <div class="analysis-controls">
                        <button id="generate-report" class="btn btn-primary">분석 리포트 생성</button>
                        <button id="download-data" class="btn btn-secondary">데이터 다운로드</button>
                    </div>
                    <div class="analysis-results" id="analysis-results">
                        <div class="chart-container">
                            <canvas id="performance-chart"></canvas>
                        </div>
                        <div class="statistics-summary">
                            <h4>통계 요약</h4>
                            <div id="statistics-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- 모달 -->
        <div id="survey-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="survey-title"></h3>
                <div id="survey-questions"></div>
                <div class="modal-actions">
                    <button id="submit-survey" class="btn btn-primary">제출</button>
                    <button id="cancel-survey" class="btn btn-secondary">취소</button>
                </div>
            </div>
        </div>

        <div id="intervention-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>개입 추가</h3>
                <form id="intervention-form">
                    <div class="form-group">
                        <label for="intervention-student">학생 선택</label>
                        <select id="intervention-student"></select>
                    </div>
                    <div class="form-group">
                        <label for="intervention-type-select">개입 유형</label>
                        <select id="intervention-type-select">
                            <option value="feedback">피드백</option>
                            <option value="guidance">가이던스</option>
                            <option value="encouragement">격려</option>
                            <option value="correction">수정</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="intervention-message">메시지</label>
                        <textarea id="intervention-message" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="intervention-details">상세 내용</label>
                        <textarea id="intervention-details" rows="5"></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">저장</button>
                        <button type="button" class="btn btn-secondary" id="cancel-intervention">취소</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="prove_BTS.js"></script>
</body>
</html>