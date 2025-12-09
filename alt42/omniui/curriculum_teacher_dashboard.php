<?php
/**
 * 커리큘럼 오케스트레이터 - 교사 대시보드
 * 시험 D-30 자동 커리큘럼 생성 및 관리
 */

session_start();
require_once __DIR__ . '/curriculum_orchestrator_config.php';
require_once __DIR__ . '/curriculum_planner_service.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$isTeacher = isTeacherRole($userId);

if (!$isTeacher) {
    die('교사 권한이 필요합니다.');
}

// 학생 목록 조회
function getStudents() {
    $pdo = getCurriculumDB();
    $stmt = $pdo->prepare("
        SELECT u.id, u.firstname, u.lastname, u.email, u.phone1,
               uid.data as role
        FROM mdl_user u
        LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
        WHERE u.deleted = 0 AND (uid.data = 'student' OR uid.data IS NULL)
        ORDER BY u.lastname, u.firstname
        LIMIT 100
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// 코스 목록 조회
function getCourses() {
    $pdo = getCurriculumDB();
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.fullname, c.shortname
        FROM mdl_course c
        WHERE c.visible = 1 AND c.id > 1
        ORDER BY c.fullname
        LIMIT 50
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// 활성 계획 조회
function getActivePlans($teacherId) {
    $pdo = getCurriculumDB();
    $stmt = $pdo->prepare("
        SELECT p.*, u.firstname, u.lastname,
               COUNT(DISTINCT i.id) as total_items,
               SUM(CASE WHEN i.status = 'completed' THEN 1 ELSE 0 END) as completed_items
        FROM mdl_curriculum_plan p
        LEFT JOIN mdl_user u ON p.userid = u.id
        LEFT JOIN mdl_curriculum_items i ON p.id = i.planid
        WHERE p.created_by = ? AND p.status IN ('published', 'active')
        GROUP BY p.id
        ORDER BY p.exam_date ASC
        LIMIT 20
    ");
    $stmt->execute([$teacherId]);
    return $stmt->fetchAll();
}

// KPI 조회
function getKPIData($teacherId) {
    $pdo = getCurriculumDB();
    
    // 전체 통계
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as total_plans,
            COUNT(DISTINCT p.userid) as total_students,
            AVG(k.completion_rate) as avg_completion,
            AVG(k.review_resolution_rate) as avg_review_resolution,
            AVG(k.daily_achievement_rate) as avg_daily_achievement
        FROM mdl_curriculum_plan p
        LEFT JOIN mdl_curriculum_kpi k ON p.id = k.planid
        WHERE p.created_by = ? AND k.metric_date = CURDATE()
    ");
    $stmt->execute([$teacherId]);
    
    return $stmt->fetch();
}

$students = getStudents();
$courses = getCourses();
$activePlans = getActivePlans($userId);
$kpiData = getKPIData($userId);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>커리큘럼 오케스트레이터 - 교사 대시보드</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --success-color: #5cb85c;
            --warning-color: #f0ad4e;
            --danger-color: #d9534f;
            --dark-bg: #2c3e50;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Noto Sans KR', sans-serif;
        }
        
        .dashboard-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .kpi-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .kpi-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        
        .plan-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .plan-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        
        .progress-ring-circle {
            transition: stroke-dashoffset 0.5s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        .btn-create-plan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-create-plan:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary-color);
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 14px;
            top: 15px;
            width: 2px;
            height: calc(100% + 10px);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .ratio-slider {
            margin: 20px 0;
        }
        
        .ratio-display {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .ratio-box {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .ratio-lead {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .ratio-review {
            background: #fff3e0;
            color: #f57c00;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .alert-badge {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- 헤더 -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-calendar-check"></i> 커리큘럼 오케스트레이터
                    </h1>
                    <p class="text-muted mb-0">시험 D-30 자동 커리큘럼 편성 시스템</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-create-plan" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                        <i class="bi bi-plus-circle"></i> 새 커리큘럼 생성
                    </button>
                </div>
            </div>
        </div>
        
        <!-- KPI 카드 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="kpi-card position-relative">
                    <i class="bi bi-graph-up kpi-icon"></i>
                    <div class="kpi-label">계획 완료율</div>
                    <div class="kpi-value text-success">
                        <?php echo number_format($kpiData['avg_completion'] ?? 0, 1); ?>%
                    </div>
                    <small class="text-muted">전체 평균</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card position-relative">
                    <i class="bi bi-check2-circle kpi-icon"></i>
                    <div class="kpi-label">오답 해결률</div>
                    <div class="kpi-value text-warning">
                        <?php echo number_format($kpiData['avg_review_resolution'] ?? 0, 1); ?>%
                    </div>
                    <small class="text-muted">복습 효과</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card position-relative">
                    <i class="bi bi-calendar-check kpi-icon"></i>
                    <div class="kpi-label">일일 이행률</div>
                    <div class="kpi-value text-info">
                        <?php echo number_format($kpiData['avg_daily_achievement'] ?? 0, 1); ?>%
                    </div>
                    <small class="text-muted">오늘 기준</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card position-relative">
                    <i class="bi bi-people kpi-icon"></i>
                    <div class="kpi-label">활성 학생</div>
                    <div class="kpi-value text-primary">
                        <?php echo $kpiData['total_students'] ?? 0; ?>명
                    </div>
                    <small class="text-muted"><?php echo $kpiData['total_plans'] ?? 0; ?>개 계획</small>
                </div>
            </div>
        </div>
        
        <!-- 활성 계획 목록 -->
        <div class="form-section">
            <h3 class="mb-4">
                <i class="bi bi-list-check"></i> 활성 커리큘럼
                <?php if (count($activePlans) > 0): ?>
                    <span class="badge bg-danger alert-badge"><?php echo count($activePlans); ?></span>
                <?php endif; ?>
            </h3>
            
            <?php if (empty($activePlans)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">활성화된 커리큘럼이 없습니다.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                        첫 커리큘럼 만들기
                    </button>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($activePlans as $plan): 
                        $daysLeft = (strtotime($plan['exam_date']) - time()) / 86400;
                        $completionRate = $plan['total_items'] > 0 ? 
                            ($plan['completed_items'] / $plan['total_items']) * 100 : 0;
                    ?>
                    <div class="col-md-6">
                        <div class="plan-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($plan['firstname'] . ' ' . $plan['lastname']); ?>
                                    </h5>
                                    <p class="mb-2 text-muted">
                                        <i class="bi bi-calendar"></i> 
                                        <?php echo $plan['exam_type']; ?> - 
                                        <?php echo date('m월 d일', strtotime($plan['exam_date'])); ?>
                                    </p>
                                    <div class="d-flex gap-3">
                                        <span class="badge bg-primary">
                                            D-<?php echo max(0, floor($daysLeft)); ?>
                                        </span>
                                        <span class="badge bg-secondary">
                                            선행 <?php echo $plan['ratio_lead']; ?>% : 
                                            복습 <?php echo $plan['ratio_review']; ?>%
                                        </span>
                                        <span class="badge bg-info">
                                            일일 <?php echo $plan['daily_minutes']; ?>분
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <svg class="progress-ring" viewBox="0 0 80 80">
                                        <circle cx="40" cy="40" r="35" fill="none" stroke="#e0e0e0" stroke-width="5"/>
                                        <circle class="progress-ring-circle" cx="40" cy="40" r="35" fill="none" 
                                                stroke="#4a90e2" stroke-width="5"
                                                stroke-dasharray="<?php echo 220 * $completionRate / 100; ?> 220"/>
                                    </svg>
                                    <div class="mt-2">
                                        <strong><?php echo number_format($completionRate, 1); ?>%</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="viewPlanDetail(<?php echo $plan['id']; ?>)">
                                    <i class="bi bi-eye"></i> 상세보기
                                </button>
                                <button class="btn btn-sm btn-outline-success" 
                                        onclick="viewProgress(<?php echo $plan['id']; ?>)">
                                    <i class="bi bi-graph-up"></i> 진행상황
                                </button>
                                <button class="btn btn-sm btn-outline-warning" 
                                        onclick="editPlan(<?php echo $plan['id']; ?>)">
                                    <i class="bi bi-pencil"></i> 수정
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 커리큘럼 생성 모달 -->
    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> 새 커리큘럼 생성
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createPlanForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">학생 선택</label>
                                <select class="form-select" id="studentSelect" required>
                                    <option value="">학생을 선택하세요</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">과정 선택</label>
                                <select class="form-select" id="courseSelect" required>
                                    <option value="">과정을 선택하세요</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['fullname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">시험 종류</label>
                                <select class="form-select" id="examType" required>
                                    <option value="중간고사">중간고사</option>
                                    <option value="기말고사">기말고사</option>
                                    <option value="모의고사">모의고사</option>
                                    <option value="수능">수능</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">시험일</label>
                                <input type="date" class="form-control" id="examDate" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">일일 학습시간(분)</label>
                                <input type="number" class="form-control" id="dailyMinutes" 
                                       value="120" min="30" max="480" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">학습 비율 설정</label>
                            <div class="ratio-slider">
                                <input type="range" class="form-range" id="ratioSlider" 
                                       min="30" max="90" value="70" step="5">
                                <div class="ratio-display">
                                    <div class="ratio-box ratio-lead">
                                        선행학습: <span id="leadRatio">70</span>%
                                    </div>
                                    <div class="ratio-box ratio-review">
                                        복습: <span id="reviewRatio">30</span>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">시험 이름 (선택)</label>
                            <input type="text" class="form-control" id="examName" 
                                   placeholder="예: 2024년 1학기 중간고사">
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>자동 편성 안내</strong><br>
                            • 선행학습: 개념, 예제, 기본문제 위주<br>
                            • 복습: 오답노트, 최근 틀린 문제 우선<br>
                            • 매일 자동으로 학습 분량이 배정됩니다
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="createPlan()">
                        <i class="bi bi-check-circle"></i> 커리큘럼 생성
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 상세보기 모달 -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">커리큘럼 상세</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    <!-- 동적 로드 -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 비율 슬라이더 업데이트
        document.getElementById('ratioSlider').addEventListener('input', function() {
            const leadRatio = this.value;
            const reviewRatio = 100 - leadRatio;
            document.getElementById('leadRatio').textContent = leadRatio;
            document.getElementById('reviewRatio').textContent = reviewRatio;
        });
        
        // 커리큘럼 생성
        function createPlan() {
            const formData = {
                userid: document.getElementById('studentSelect').value,
                courseid: document.getElementById('courseSelect').value,
                exam_type: document.getElementById('examType').value,
                exam_date: document.getElementById('examDate').value,
                exam_name: document.getElementById('examName').value,
                daily_minutes: document.getElementById('dailyMinutes').value,
                ratio_lead: document.getElementById('ratioSlider').value,
                ratio_review: 100 - document.getElementById('ratioSlider').value
            };
            
            // 유효성 검사
            if (!formData.userid || !formData.courseid || !formData.exam_date) {
                alert('필수 정보를 모두 입력해주세요.');
                return;
            }
            
            // 로딩 표시
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 생성 중...';
            btn.disabled = true;
            
            $.ajax({
                url: 'curriculum_api.php',
                method: 'POST',
                data: {
                    action: 'create_plan',
                    ...formData
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('커리큘럼이 성공적으로 생성되었습니다!');
                        
                        // 미리보기로 이동
                        viewPlanDetail(response.data.plan_id);
                        
                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('createPlanModal')).hide();
                        
                        // 페이지 새로고침
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('오류: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('서버 오류가 발생했습니다: ' + error);
                },
                complete: function() {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }
        
        // 상세보기
        function viewPlanDetail(planId) {
            $.ajax({
                url: 'curriculum_api.php',
                method: 'GET',
                data: {
                    action: 'preview_plan',
                    plan_id: planId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showDetailModal(response.data);
                    } else {
                        alert('오류: ' + response.message);
                    }
                }
            });
        }
        
        // 상세 모달 표시
        function showDetailModal(data) {
            let html = `
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4>${data.plan.exam_type} - ${data.plan.exam_date}</h4>
                            <p class="text-muted">
                                총 ${data.plan.total_days}일 | 
                                선행 ${data.plan.ratio_lead}% : 복습 ${data.plan.ratio_review}% | 
                                일일 ${data.plan.daily_minutes}분
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5>${data.stats.total_items}</h5>
                                    <small>전체 항목</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5>${data.stats.concept_items}</h5>
                                    <small>개념 항목</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5>${data.stats.review_items}</h5>
                                    <small>복습 항목</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5>${Math.round(data.stats.total_minutes / 60)}시간</h5>
                                    <small>총 학습시간</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">일별 계획</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>개념학습</th>
                                    <th>복습</th>
                                    <th>총 시간</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            data.daily_items.forEach(item => {
                const totalMinutes = (item.concept_minutes || 0) + (item.review_minutes || 0);
                html += `
                    <tr>
                        <td>D-${data.plan.total_days - item.day + 1}</td>
                        <td>${item.concept_count}개 (${item.concept_minutes}분)</td>
                        <td>${item.review_count}개 (${item.review_minutes}분)</td>
                        <td>${totalMinutes}분</td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <button class="btn btn-success" onclick="publishPlan(${data.plan.id})">
                            <i class="bi bi-send"></i> 학생에게 배포
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('detailModalBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }
        
        // 계획 배포
        function publishPlan(planId) {
            if (!confirm('이 커리큘럼을 학생에게 배포하시겠습니까?')) {
                return;
            }
            
            $.ajax({
                url: 'curriculum_api.php',
                method: 'POST',
                data: {
                    action: 'publish_plan',
                    plan_id: planId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('커리큘럼이 성공적으로 배포되었습니다!');
                        location.reload();
                    } else {
                        alert('오류: ' + response.message);
                    }
                }
            });
        }
        
        // 진행상황 보기
        function viewProgress(planId) {
            window.open('curriculum_progress.php?plan_id=' + planId, '_blank');
        }
        
        // 계획 수정
        function editPlan(planId) {
            alert('수정 기능은 준비 중입니다.');
        }
    </script>
</body>
</html>