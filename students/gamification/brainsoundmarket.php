<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
require_login();
$studentid=$_GET["userid"]; 
if($studentid==NULL) $studentid=$USER->id;

$timecreated=time(); 

$username= $DB->get_record_sql("SELECT id,hideinput,lastname, firstname,timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1 ");

$studentname=$username->firstname.$username->lastname;

// 코인 잔액 조회
$coin_record = $DB->get_record_sql("SELECT quantity FROM mdl_block_stash_user_items WHERE userid='$studentid' AND itemid='495' ");
$coin_balance = $coin_record ? (int)$coin_record->quantity : 0;

// MBTI 정보 조회
$mbtiType = 'INTJ'; // 기본값
try {
    $mbtiLog = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_mbtilog WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
        [$studentid]
    );
    if ($mbtiLog && !empty($mbtiLog->mbti)) {
        $mbtiType = strtoupper($mbtiLog->mbti);
    }
} catch (Exception $e) {
    error_log("MBTI fetch error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
}

// 하이페리아 정보 (예시 - 실제 DB 구조에 맞게 수정 필요)
$hyperia = '하이페리아'; // 기본값
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎵 Mathking Brain Sound 상점</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Pretendard', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        /* 온보딩 단계 */
        .onboarding-container {
            background: white;
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .onboarding-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .onboarding-steps::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .step-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            font-weight: bold;
            margin: 0 auto 10px;
            transition: all 0.3s ease;
        }
        
        .step.active .step-circle {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: scale(1.1);
        }
        
        .step.completed .step-circle {
            background: #43e97b;
            color: white;
        }
        
        .step-title {
            font-size: 0.9em;
            color: #666;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .step.active .step-title {
            color: #667eea;
            font-weight: bold;
        }
        
        .step:hover .step-circle {
            transform: scale(1.1);
        }
        
        .step:hover .step-title {
            color: #667eea;
        }
        
        /* 단계별 콘텐츠 */
        .step-content {
            display: none;
            background: #f6f7f9;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .step-content.active {
            display: block;
            animation: fadeInUp 0.5s ease;
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .step-icon {
            font-size: 3em;
        }
        
        .step-info h3 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 5px;
        }
        
        .step-info p {
            color: #666;
            font-size: 0.95em;
        }
        
        /* 옵션 그리드 */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .option-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .option-card.selected {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }
        
        .option-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .option-name {
            font-weight: 600;
            font-size: 0.95em;
        }
        
        .execute-btn {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1em;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .execute-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(67, 233, 123, 0.3);
        }
        
        .execute-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* 프로필 정보 */
        .profile-info {
            background: linear-gradient(135deg, #f6f7f9, #ffffff);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .profile-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .profile-label {
            font-weight: 600;
            color: #666;
            min-width: 100px;
        }
        
        .profile-value {
            color: #333;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-badge.completed {
            background: #43e97b;
            color: white;
        }
        
        .status-badge.processing {
            background: #667eea;
            color: white;
        }
        
        /* 음악 플레이리스트 카드 */
        .playlist-section {
            background: white;
            border-radius: 25px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 2em;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .playlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .playlist-card {
            background: linear-gradient(135deg, #f6f7f9 0%, #ffffff 100%);
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            padding: 25px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .playlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .playlist-card.owned {
            border-color: #43e97b;
            background: linear-gradient(135deg, #e8f5e9, #ffffff);
        }
        
        .playlist-icon {
            font-size: 3em;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .playlist-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .playlist-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
            text-align: center;
            min-height: 40px;
        }
        
        .playlist-price {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 1.3em;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 15px;
        }
        
        .purchase-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .purchase-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .purchase-btn:disabled {
            background: #43e97b;
            cursor: not-allowed;
        }
        
        .purchase-btn.owned {
            background: #43e97b;
        }
        
        .owned-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #43e97b;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        /* 코인 잔액 표시 */
        .coin-balance-header {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            padding: 15px 25px;
            border-radius: 20px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        /* 세부 목록 영역 */
        .detail-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px dashed #e0e0e0;
        }
        
        .detail-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .detail-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .detail-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .detail-card.selected {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }
        
        .detail-icon {
            font-size: 2em;
            margin-bottom: 8px;
        }
        
        .detail-name {
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .show-playlist-btn {
            background: linear-gradient(135deg, #ff6b9d, #c44569);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .show-playlist-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 107, 157, 0.3);
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .onboarding-steps {
                flex-direction: column;
                gap: 20px;
            }
            
            .onboarding-steps::before {
                display: none;
            }
            
            .options-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .playlist-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <h1>🎵 Mathking Brain Sound 상점</h1>
            <p style="font-size: 1.1em; opacity: 0.95;">학습 상황에 맞는 음악을 선택하세요</p>
        </div>
        
        <!-- 코인 잔액 -->
        <div class="coin-balance-header">
            <span>🪙</span>
            <span>내 코인: <?php echo number_format($coin_balance); ?></span>
        </div>
        
        <!-- 온보딩 프로세스 -->
        <div class="onboarding-container">
            <div class="onboarding-steps">
                <div class="step active" data-step="1" onclick="goToStep(1)" style="cursor: pointer;">
                    <div class="step-circle">👤</div>
                    <div class="step-title">온보딩</div>
                </div>
                <div class="step" data-step="2" onclick="goToStep(2)" style="cursor: pointer;">
                    <div class="step-circle">📅</div>
                    <div class="step-title">시험일정</div>
                </div>
                <div class="step" data-step="3" onclick="goToStep(3)" style="cursor: pointer;">
                    <div class="step-circle">🎯</div>
                    <div class="step-title">목표분석</div>
                </div>
                <div class="step" data-step="4" onclick="goToStep(4)" style="cursor: pointer;">
                    <div class="step-circle">📚</div>
                    <div class="step-title">문제활동</div>
                </div>
                <div class="step" data-step="5" onclick="goToStep(5)" style="cursor: pointer;">
                    <div class="step-circle">😊</div>
                    <div class="step-title">학습감정</div>
                </div>
            </div>
            
            <!-- 단계 1: 온보딩 -->
            <div class="step-content active" id="step1">
                <div class="step-header">
                    <div class="step-icon">👤</div>
                    <div class="step-info">
                        <h3>온보딩</h3>
                        <p>학생 프로필 정보가 로드되었습니다</p>
                    </div>
                </div>
                
                <div class="profile-info">
                    <div class="profile-item">
                        <span class="profile-label">이름:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($studentname); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">MBTI:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($mbtiType); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">처리 과정:</span>
                        <span class="status-badge completed">학생 프로필 정보가 로드되었습니다</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">인사이트:</span>
                        <span class="status-badge completed">기존 학습 패턴 파악 완료</span>
                    </div>
                </div>
                
                <button class="execute-btn" onclick="nextStep(2)">다음 단계</button>
            </div>
            
            <!-- 단계 2: 시험일정 식별 -->
            <div class="step-content" id="step2">
                <div class="step-header">
                    <div class="step-icon">📅</div>
                    <div class="step-info">
                        <h3>시험일정 식별</h3>
                        <p>일상정보 수집, 분석 영역입니다</p>
                    </div>
                </div>
                
                <div class="options-grid" id="examScheduleOptions">
                    <div class="option-card" data-value="vacation" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">🏖️</div>
                        <div class="option-name">방학</div>
                    </div>
                    <div class="option-card" data-value="d-2months" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">📅</div>
                        <div class="option-name">D-2개월</div>
                    </div>
                    <div class="option-card" data-value="d-1month" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">📆</div>
                        <div class="option-name">D-1개월</div>
                    </div>
                    <div class="option-card" data-value="d-2weeks" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">⏰</div>
                        <div class="option-name">D-2주</div>
                    </div>
                    <div class="option-card" data-value="d-1week" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">🚨</div>
                        <div class="option-name">D-1주</div>
                    </div>
                    <div class="option-card" data-value="d-3days" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">🔥</div>
                        <div class="option-name">D-3일</div>
                    </div>
                    <div class="option-card" data-value="d-1day" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">💯</div>
                        <div class="option-name">D-1일</div>
                    </div>
                    <div class="option-card" data-value="no-exam" onclick="selectOption(this, 'examSchedule')">
                        <div class="option-icon">📖</div>
                        <div class="option-name">시험없음</div>
                    </div>
                </div>
                
                <p style="text-align: center; color: #666; margin-top: 15px;">
                    상황을 선택하면 맞춤형 학습 전략 가이드가 표시됩니다.
                </p>
                
                <!-- 세부 시험일정 목록 -->
                <div class="detail-section" id="examDetailSection" style="display: none;">
                    <div class="detail-title">시험일정 세부 정보</div>
                    <div class="detail-grid" id="examDetailGrid"></div>
                    <button class="show-playlist-btn" onclick="showDetailPlaylists('examSchedule')" id="examPlaylistBtn" style="display: none;">
                        선택한 시험일정의 음악 보기
                    </button>
                </div>
                
                <button class="execute-btn" onclick="nextStep(3)" id="examScheduleBtn" disabled>실행</button>
            </div>
            
            <!-- 단계 3: 목표 및 계획 분석 -->
            <div class="step-content" id="step3">
                <div class="step-header">
                    <div class="step-icon">🎯</div>
                    <div class="step-info">
                        <h3>목표 및 계획 분석</h3>
                        <p>분기목표, 주간목표, 오늘목표 분석</p>
                    </div>
                </div>
                
                <div class="options-grid" id="goalOptions">
                    <div class="option-card" data-value="quarter" onclick="selectOption(this, 'goal')">
                        <div class="option-icon">📊</div>
                        <div class="option-name">분기목표</div>
                    </div>
                    <div class="option-card" data-value="weekly" onclick="selectOption(this, 'goal')">
                        <div class="option-icon">📅</div>
                        <div class="option-name">주간목표</div>
                    </div>
                    <div class="option-card" data-value="daily" onclick="selectOption(this, 'goal')">
                        <div class="option-icon">📆</div>
                        <div class="option-name">오늘목표</div>
                    </div>
                    <div class="option-card" data-value="class-prep" onclick="selectOption(this, 'goal')">
                        <div class="option-icon">📚</div>
                        <div class="option-name">수업준비</div>
                    </div>
                    <div class="option-card" data-value="pomodoro" onclick="selectOption(this, 'goal')">
                        <div class="option-icon">⏱️</div>
                        <div class="option-name">포모도르</div>
                    </div>
                    <div class="option-card" data-value="home-check" onclick="selectOption(this, 'goal')">
                        <div class="option-icon">🏠</div>
                        <div class="option-name">귀가검사</div>
                    </div>
                </div>
                
                <!-- 세부 목표 목록 -->
                <div class="detail-section" id="goalDetailSection" style="display: none;">
                    <div class="detail-title">세부 목표 선택</div>
                    <div class="detail-grid" id="goalDetailGrid"></div>
                    <button class="show-playlist-btn" onclick="showDetailPlaylists('goal')" id="goalPlaylistBtn" style="display: none;">
                        선택한 세부 목표의 음악 보기
                    </button>
                </div>
                
                <button class="execute-btn" onclick="nextStep(4)" id="goalBtn" disabled>실행</button>
            </div>
            
            <!-- 단계 4: 문제활동 식별 -->
            <div class="step-content" id="step4">
                <div class="step-header">
                    <div class="step-icon">📚</div>
                    <div class="step-info">
                        <h3>문제활동 식별</h3>
                        <p>학습 활동을 선택하면 해당 활동에서의 감정 상태를 분석합니다</p>
                    </div>
                </div>
                
                <div class="options-grid" id="activityOptions">
                    <div class="option-card" data-value="concept-understanding" onclick="selectOption(this, 'activity')">
                        <div class="option-icon">📖</div>
                        <div class="option-name">개념이해</div>
                    </div>
                    <div class="option-card" data-value="type-learning" onclick="selectOption(this, 'activity')">
                        <div class="option-icon">🎯</div>
                        <div class="option-name">유형학습</div>
                    </div>
                    <div class="option-card" data-value="problem-solving" onclick="selectOption(this, 'activity')">
                        <div class="option-icon">✏️</div>
                        <div class="option-name">문제풀이</div>
                    </div>
                    <div class="option-card" data-value="error-notes" onclick="selectOption(this, 'activity')">
                        <div class="option-icon">📝</div>
                        <div class="option-name">오답노트</div>
                    </div>
                    <div class="option-card" data-value="qa" onclick="selectOption(this, 'activity')">
                        <div class="option-icon">💬</div>
                        <div class="option-name">질의응답</div>
                    </div>
                    <div class="option-card" data-value="review" onclick="selectOption(this, 'activity')">
                        <div class="option-icon">🔄</div>
                        <div class="option-name">복습활동</div>
                    </div>
                </div>
                
                <p style="text-align: center; color: #666; margin-top: 15px;">
                    현재 '<span id="selectedActivity">-</span>' 활동이 선택되었습니다. 세부 활동을 선택해주세요.
                </p>
                
                <!-- 세부 활동 목록 -->
                <div class="detail-section" id="activityDetailSection" style="display: none;">
                    <div class="detail-title">세부 활동 선택</div>
                    <div class="detail-grid" id="activityDetailGrid"></div>
                    <button class="show-playlist-btn" onclick="showDetailPlaylists('activity')" id="activityPlaylistBtn" style="display: none;">
                        선택한 세부 활동의 음악 보기
                    </button>
                </div>
                
                <button class="execute-btn" onclick="nextStep(5)" id="activityBtn" disabled>실행</button>
            </div>
            
            <!-- 단계 5: 학습감정 분석 -->
            <div class="step-content" id="step5">
                <div class="step-header">
                    <div class="step-icon">😊</div>
                    <div class="step-info">
                        <h3>학습감정 분석</h3>
                        <p>학습 활동에서의 감정 상태를 세밀하게 분석합니다</p>
                    </div>
                </div>
                
                <div class="profile-info">
                    <div class="profile-item">
                        <span class="profile-label">선택된 활동:</span>
                        <span class="profile-value" id="finalActivity">-</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">시험일정:</span>
                        <span class="profile-value" id="finalExamSchedule">-</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">목표:</span>
                        <span class="profile-value" id="finalGoal">-</span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">상태:</span>
                        <span class="status-badge completed">분석 완료</span>
                    </div>
                </div>
                
                <button class="execute-btn" onclick="showPlaylists()">음악 플레이리스트 보기</button>
            </div>
        </div>
        
        <!-- 음악 플레이리스트 섹션 -->
        <div class="playlist-section" id="playlistSection" style="display: none;">
            <h2 class="section-title">
                <span>🎵</span>
                <span>맞춤형 음악 플레이리스트</span>
            </h2>
            
            <div class="playlist-grid" id="playlistGrid">
                <!-- 플레이리스트 카드들이 동적으로 생성됩니다 -->
            </div>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        let selectedData = {
            examSchedule: null,
            goal: null,
            activity: null,
            examScheduleDetail: null,
            goalDetail: null,
            activityDetail: null
        };
        let coinBalance = <?php echo $coin_balance; ?>;
        
        // 플레이리스트 데이터 (세부 항목별)
        const playlists = {
            // 개념이해 세부 항목별
            'concept-reading': [
                {id: 101, title: '개념정독 집중 음악', description: '개념을 정독할 때 집중력을 높이는 음악', icon: '📖', price: 45},
                {id: 102, title: '조용한 학습 환경음', description: '조용한 환경에서 개념을 읽을 때', icon: '🔇', price: 35},
                {id: 103, title: '알파파 음악', description: '뇌파 동기화를 통한 집중력 향상', icon: '🧠', price: 60}
            ],
            'concept-understanding-detail': [
                {id: 104, title: '개념이해 몰입 음악', description: '개념을 이해할 때 깊이 몰입하게 해주는 음악', icon: '💡', price: 50},
                {id: 105, title: '사고력 향상 음악', description: '논리적 사고를 돕는 음악', icon: '🤔', price: 55},
                {id: 106, title: '명상 음악', description: '명상을 통한 개념 이해', icon: '🧘', price: 40}
            ],
            'concept-check': [
                {id: 107, title: '개념체크 집중 음악', description: '개념을 체크할 때 집중력을 높이는 음악', icon: '✓', price: 45},
                {id: 108, title: '기억력 강화 음악', description: '개념 기억을 강화하는 음악', icon: '🧠', price: 50}
            ],
            'example-quiz': [
                {id: 109, title: '예제퀴즈 학습 음악', description: '예제를 풀 때 도움이 되는 음악', icon: '📝', price: 40},
                {id: 110, title: '문제 해결 음악', description: '문제 해결 능력을 높이는 음악', icon: '💪', price: 45}
            ],
            'representative-type': [
                {id: 111, title: '대표유형 연습 음악', description: '대표유형 문제를 풀 때', icon: '🎯', price: 50},
                {id: 112, title: '패턴 인식 음악', description: '문제 패턴을 인식하는데 도움', icon: '🔍', price: 55}
            ],
            'topic-test': [
                {id: 113, title: '주제별테스트 집중 음악', description: '주제별 테스트에 집중할 때', icon: '📊', price: 50},
                {id: 114, title: '테스트 준비 음악', description: '테스트 전 집중력 향상', icon: '📈', price: 45}
            ],
            'unit-test': [
                {id: 115, title: '단원별테스트 음악', description: '단원별 테스트에 최적화된 음악', icon: '📚', price: 55},
                {id: 116, title: '종합 이해 음악', description: '단원 전체를 이해하는데 도움', icon: '🌐', price: 60}
            ],
            'explanation-listen': [
                {id: 117, title: '설명듣기 집중 음악', description: '설명을 들을 때 집중력을 높이는 음악', icon: '🔊', price: 40},
                {id: 118, title: '청각 학습 음악', description: '청각 학습에 최적화된 음악', icon: '👂', price: 45}
            ],
            // 시험일정별
            'vacation-study': [
                {id: 201, title: '방학 학습 플레이리스트', description: '방학 기간 학습에 최적화', icon: '🏖️', price: 90},
                {id: 202, title: '여유로운 학습 음악', description: '여유롭게 학습할 때', icon: '🌴', price: 70}
            ],
            'd-2months': [
                {id: 203, title: 'D-2개월 학습 음악', description: '시험 2개월 전 학습 음악', icon: '📅', price: 100},
                {id: 204, title: '장기 계획 음악', description: '장기 학습 계획에 도움', icon: '📊', price: 85}
            ],
            'd-1week': [
                {id: 205, title: 'D-1주 집중 음악', description: '시험 1주 전 집중력 향상', icon: '🚨', price: 150},
                {id: 206, title: '실전 대비 음악', description: '실전을 대비한 집중 음악', icon: '💪', price: 140}
            ],
            // 목표별
            'focus-session': [
                {id: 301, title: '포모도르 집중 세션 음악', description: '25분 집중 세션용 음악', icon: '⏱️', price: 70},
                {id: 302, title: '타이머 음악', description: '시간 관리에 도움', icon: '⏰', price: 60}
            ],
            'break-session': [
                {id: 303, title: '휴식 세션 음악', description: '5분 휴식용 음악', icon: '☕', price: 30},
                {id: 304, title: '릴랙스 음악', description: '긴장 완화 음악', icon: '🌿', price: 35}
            ],
            // 일반 플레이리스트
            'general': [
                {id: 401, title: '집중력 향상 음악', description: '일반적인 집중력 향상 음악', icon: '🎵', price: 50},
                {id: 402, title: '학습 몰입 음악', description: '학습에 몰입하게 해주는 음악', icon: '📚', price: 55},
                {id: 403, title: '기억력 강화 음악', description: '기억력을 강화하는 음악', icon: '🧠', price: 60},
                {id: 404, title: '스트레스 완화 음악', description: '학습 스트레스를 완화하는 음악', icon: '🌊', price: 45}
            ]
        };
        
        // 세부 항목별 플레이리스트 표시
        function showDetailPlaylists(type) {
            const detailId = selectedData[type + 'Detail'];
            if (!detailId) {
                alert('세부 항목을 선택해주세요.');
                return;
            }
            
            let playlistArray = [];
            
            // 세부 항목에 맞는 플레이리스트 찾기
            if (playlists[detailId]) {
                playlistArray = playlists[detailId];
            } else {
                // 매칭되는 플레이리스트가 없으면 일반 플레이리스트 표시
                playlistArray = playlists['general'] || [];
            }
            
            // 플레이리스트 그리드 생성
            const grid = document.getElementById('playlistGrid');
            grid.innerHTML = '';
            
            if (playlistArray.length === 0) {
                grid.innerHTML = '<p style="text-align: center; color: #666; grid-column: 1/-1;">해당 항목에 대한 음악 플레이리스트가 없습니다.</p>';
            } else {
                playlistArray.forEach(playlist => {
                    const card = document.createElement('div');
                    card.className = 'playlist-card';
                    card.innerHTML = `
                        <div class="playlist-icon">${playlist.icon}</div>
                        <div class="playlist-title">${playlist.title}</div>
                        <div class="playlist-description">${playlist.description}</div>
                        <div class="playlist-price">
                            <span>🪙</span>
                            <span>${playlist.price}</span>
                        </div>
                        <button class="purchase-btn" onclick="purchasePlaylist(${playlist.id}, ${playlist.price})">
                            구매하기
                        </button>
                    `;
                    grid.appendChild(card);
                });
            }
            
            // 플레이리스트 섹션 표시
            document.getElementById('playlistSection').style.display = 'block';
            document.getElementById('playlistSection').scrollIntoView({ behavior: 'smooth' });
        }
        
        // 특정 단계로 이동 (클릭으로 바로 이동)
        function goToStep(step) {
            // 이전 단계들을 완료 표시 (현재 단계 이전까지)
            for (let i = 1; i < step; i++) {
                const stepElement = document.querySelector(`.step[data-step="${i}"]`);
                if (stepElement && !stepElement.classList.contains('completed')) {
                    stepElement.classList.add('completed');
                }
                stepElement.classList.remove('active');
            }
            
            // 현재 단계부터는 완료 표시 제거하고 활성화
            for (let i = step; i <= 5; i++) {
                const stepElement = document.querySelector(`.step[data-step="${i}"]`);
                if (stepElement) {
                    stepElement.classList.remove('completed');
                    if (i === step) {
                        stepElement.classList.add('active');
                    } else {
                        stepElement.classList.remove('active');
                    }
                }
            }
            
            // 현재 단계 업데이트
            currentStep = step;
            
            // 콘텐츠 전환
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`step${currentStep}`).classList.add('active');
            
            // 스크롤을 해당 단계로 이동
            document.getElementById(`step${currentStep}`).scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // 다음 단계로 이동 (순차적 진행)
        function nextStep(step) {
            // 현재 단계 완료 표시
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('completed');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
            
            // 다음 단계 활성화
            currentStep = step;
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
            
            // 콘텐츠 전환
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`step${currentStep}`).classList.add('active');
            
            // 스크롤을 해당 단계로 이동
            document.getElementById(`step${currentStep}`).scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // 세부 목록 데이터
        const detailLists = {
            'activity': {
                'concept-understanding': [
                    {id: 'concept-reading', name: '개념정독', icon: '📖'},
                    {id: 'concept-understanding-detail', name: '개념이해', icon: '💡'},
                    {id: 'concept-check', name: '개념체크', icon: '✓'},
                    {id: 'example-quiz', name: '예제퀴즈', icon: '📝'},
                    {id: 'representative-type', name: '대표유형', icon: '🎯'},
                    {id: 'topic-test', name: '주제별테스트', icon: '📊'},
                    {id: 'unit-test', name: '단원별테스트', icon: '📚'},
                    {id: 'explanation-listen', name: '설명듣기', icon: '🔊'}
                ],
                'type-learning': [
                    {id: 'basic-type', name: '기본 유형', icon: '📘'},
                    {id: 'applied-type', name: '응용 유형', icon: '📗'},
                    {id: 'advanced-type', name: '심화 유형', icon: '📙'},
                    {id: 'new-type', name: '신유형', icon: '🆕'}
                ],
                'problem-solving': [
                    {id: 'past-exam', name: '기출문제 풀이', icon: '📋'},
                    {id: 'mock-exam', name: '모의고사 풀이', icon: '📝'},
                    {id: 'unit-problem', name: '단원별 문제', icon: '📚'},
                    {id: 'comprehensive', name: '종합 문제', icon: '📊'}
                ],
                'error-notes': [
                    {id: 'error-analysis', name: '오답 원인 분석', icon: '🔍'},
                    {id: 'similar-problem', name: '유사 문제 연습', icon: '🔄'},
                    {id: 'concept-review', name: '개념 재정리', icon: '📖'},
                    {id: 'mistake-checklist', name: '실수 방지 체크리스트', icon: '✓'}
                ],
                'qa': [
                    {id: 'concept-question', name: '개념 질문', icon: '❓'},
                    {id: 'problem-question', name: '문제 풀이 질문', icon: '💬'},
                    {id: 'learning-method', name: '학습 방법 상담', icon: '💡'},
                    {id: 'career-counseling', name: '진로 상담', icon: '🎓'}
                ],
                'review': [
                    {id: 'quick-review', name: '빠른복습', icon: '⚡'},
                    {id: 'deep-review', name: '심화복습', icon: '🔍'},
                    {id: 'spaced-review', name: '간격복습', icon: '📅'},
                    {id: 'comprehensive-review', name: '종합복습', icon: '📊'}
                ]
            },
            'goal': {
                'quarter': [
                    {id: 'quarter-plan', name: '분기 계획 수립', icon: '📅'},
                    {id: 'quarter-progress', name: '분기 진행 상황', icon: '📊'},
                    {id: 'quarter-review', name: '분기 회고', icon: '🔍'}
                ],
                'weekly': [
                    {id: 'weekly-plan', name: '주간 계획', icon: '📅'},
                    {id: 'weekly-progress', name: '주간 진행', icon: '📊'},
                    {id: 'weekly-review', name: '주간 회고', icon: '🔍'}
                ],
                'daily': [
                    {id: 'daily-plan', name: '오늘 계획', icon: '📅'},
                    {id: 'daily-progress', name: '오늘 진행', icon: '📊'},
                    {id: 'daily-review', name: '오늘 회고', icon: '🔍'}
                ],
                'class-prep': [
                    {id: 'preview', name: '예습', icon: '👀'},
                    {id: 'review-class', name: '복습', icon: '🔄'},
                    {id: 'note-taking', name: '필기', icon: '✏️'}
                ],
                'pomodoro': [
                    {id: 'focus-session', name: '집중 세션', icon: '🎯'},
                    {id: 'break-session', name: '휴식 세션', icon: '☕'},
                    {id: 'long-break', name: '긴 휴식', icon: '🌴'}
                ],
                'home-check': [
                    {id: 'homework-check', name: '숙제 확인', icon: '📝'},
                    {id: 'review-check', name: '복습 확인', icon: '🔍'},
                    {id: 'prep-check', name: '예습 확인', icon: '👀'}
                ]
            },
            'examSchedule': {
                'vacation': [
                    {id: 'vacation-study', name: '방학 학습', icon: '📚'},
                    {id: 'vacation-prep', name: '시험대비', icon: '🎯'},
                    {id: 'vacation-advance', name: '개념선행', icon: '🚀'},
                    {id: 'vacation-review', name: '복습 & 심화', icon: '🔍'}
                ],
                'd-2months': [
                    {id: 'd2m-concept', name: '개념공부', icon: '📖'},
                    {id: 'd2m-type', name: '유형연습', icon: '🎯'},
                    {id: 'd2m-advanced', name: '심화학습', icon: '🚀'},
                    {id: 'd2m-past', name: '기출문제 풀이', icon: '📋'}
                ],
                'd-1month': [
                    {id: 'd1m-diagnosis', name: '진단 및 재조정', icon: '🔍'},
                    {id: 'd1m-strategy', name: '전략 수립', icon: '📊'},
                    {id: 'd1m-practice', name: '연습 강화', icon: '💪'}
                ],
                'd-2weeks': [
                    {id: 'd2w-final', name: '마무리 전략', icon: '🎯'},
                    {id: 'd2w-optimization', name: '최적화 전략', icon: '⚡'},
                    {id: 'd2w-guide', name: '가이드 활용', icon: '📖'}
                ],
                'd-1week': [
                    {id: 'd1w-strategy', name: '맞춤전략 선택', icon: '🎯'},
                    {id: 'd1w-practice', name: '실전 연습', icon: '💪'},
                    {id: 'd1w-application', name: '적용도 향상', icon: '📈'}
                ],
                'd-3days': [
                    {id: 'd3d-diagnosis', name: '실전 준비 진단', icon: '🔍'},
                    {id: 'd3d-weakness', name: '취약지점 보충', icon: '💪'},
                    {id: 'd3d-practice', name: '반복 실전 연습', icon: '🔄'}
                ],
                'd-1day': [
                    {id: 'd1d-activation', name: '작업기억 활성화', icon: '⚡'},
                    {id: 'd1d-speed', name: 'Speed 서술평가', icon: '📝'},
                    {id: 'd1d-past', name: '기출문제 풀이', icon: '📋'},
                    {id: 'd1d-warmup', name: '워밍업', icon: '🔥'}
                ],
                'no-exam': [
                    {id: 'no-exam-study', name: '일상 학습', icon: '📚'},
                    {id: 'no-exam-review', name: '복습', icon: '🔄'},
                    {id: 'no-exam-advance', name: '선행 학습', icon: '🚀'}
                ]
            }
        };
        
        // 옵션 선택
        function selectOption(card, type) {
            // 같은 타입의 다른 카드 선택 해제
            const parent = card.closest('.options-grid');
            parent.querySelectorAll('.option-card').forEach(c => {
                c.classList.remove('selected');
            });
            
            // 선택된 카드 활성화
            card.classList.add('selected');
            selectedData[type] = card.dataset.value;
            
            // 실행 버튼 활성화
            const btnId = type === 'examSchedule' ? 'examScheduleBtn' : 
                          type === 'goal' ? 'goalBtn' : 'activityBtn';
            document.getElementById(btnId).disabled = false;
            
            // 활동 선택 시 텍스트 업데이트 및 세부 목록 표시
            if (type === 'activity') {
                const activityNames = {
                    'concept-understanding': '개념이해',
                    'type-learning': '유형학습',
                    'problem-solving': '문제풀이',
                    'error-notes': '오답노트',
                    'qa': '질의응답',
                    'review': '복습활동'
                };
                document.getElementById('selectedActivity').textContent = 
                    activityNames[selectedData.activity] || '-';
                
                // 세부 활동 목록 표시
                showDetailList('activity', selectedData.activity);
            } else if (type === 'goal') {
                // 세부 목표 목록 표시
                showDetailList('goal', selectedData.goal);
            } else if (type === 'examSchedule') {
                // 세부 시험일정 목록 표시
                showDetailList('examSchedule', selectedData.examSchedule);
            }
        }
        
        // 세부 목록 표시
        function showDetailList(type, selectedValue) {
            const sectionId = type === 'activity' ? 'activityDetailSection' :
                              type === 'goal' ? 'goalDetailSection' : 'examDetailSection';
            const gridId = type === 'activity' ? 'activityDetailGrid' :
                          type === 'goal' ? 'goalDetailGrid' : 'examDetailGrid';
            const btnId = type === 'activity' ? 'activityPlaylistBtn' :
                         type === 'goal' ? 'goalPlaylistBtn' : 'examPlaylistBtn';
            
            const section = document.getElementById(sectionId);
            const grid = document.getElementById(gridId);
            const btn = document.getElementById(btnId);
            
            if (detailLists[type] && detailLists[type][selectedValue]) {
                grid.innerHTML = '';
                detailLists[type][selectedValue].forEach(item => {
                    const card = document.createElement('div');
                    card.className = 'detail-card';
                    card.dataset.detailId = item.id;
                    card.onclick = function() {
                        selectDetailItem(this, type, item.id);
                    };
                    card.innerHTML = `
                        <div class="detail-icon">${item.icon}</div>
                        <div class="detail-name">${item.name}</div>
                    `;
                    grid.appendChild(card);
                });
                section.style.display = 'block';
                btn.style.display = 'none';
                selectedData[type + 'Detail'] = null;
            } else {
                section.style.display = 'none';
            }
        }
        
        // 세부 항목 선택
        function selectDetailItem(card, type, detailId) {
            const parent = card.closest('.detail-grid');
            parent.querySelectorAll('.detail-card').forEach(c => {
                c.classList.remove('selected');
            });
            card.classList.add('selected');
            selectedData[type + 'Detail'] = detailId;
            
            const btnId = type === 'activity' ? 'activityPlaylistBtn' :
                         type === 'goal' ? 'goalPlaylistBtn' : 'examPlaylistBtn';
            document.getElementById(btnId).style.display = 'block';
        }
        
        // 플레이리스트 표시 (최종 단계)
        function showPlaylists() {
            // 최종 선택 정보 업데이트
            const examScheduleNames = {
                'vacation': '🏖️ 방학',
                'd-2months': '📅 D-2개월',
                'd-1month': '📆 D-1개월',
                'd-2weeks': '⏰ D-2주',
                'd-1week': '🚨 D-1주',
                'd-3days': '🔥 D-3일',
                'd-1day': '💯 D-1일',
                'no-exam': '📖 시험없음'
            };
            
            const activityNames = {
                'concept-understanding': '개념이해',
                'type-learning': '유형학습',
                'problem-solving': '문제풀이',
                'error-notes': '오답노트',
                'qa': '질의응답',
                'review': '복습활동'
            };
            
            const goalNames = {
                'quarter': '분기목표',
                'weekly': '주간목표',
                'daily': '오늘목표',
                'class-prep': '수업준비',
                'pomodoro': '포모도르',
                'home-check': '귀가검사'
            };
            
            document.getElementById('finalActivity').textContent = 
                activityNames[selectedData.activity] || '-';
            document.getElementById('finalExamSchedule').textContent = 
                examScheduleNames[selectedData.examSchedule] || '-';
            document.getElementById('finalGoal').textContent = 
                goalNames[selectedData.goal] || '-';
            
            // 선택된 세부 항목에 맞는 플레이리스트 표시
            let playlistArray = [];
            
            // 활동 세부 항목이 있으면 해당 플레이리스트 표시
            if (selectedData.activityDetail && playlists[selectedData.activityDetail]) {
                playlistArray = playlists[selectedData.activityDetail];
            }
            // 목표 세부 항목이 있으면 해당 플레이리스트 표시
            else if (selectedData.goalDetail && playlists[selectedData.goalDetail]) {
                playlistArray = playlists[selectedData.goalDetail];
            }
            // 시험일정 세부 항목이 있으면 해당 플레이리스트 표시
            else if (selectedData.examScheduleDetail && playlists[selectedData.examScheduleDetail]) {
                playlistArray = playlists[selectedData.examScheduleDetail];
            }
            // 일반 플레이리스트 표시
            else {
                playlistArray = playlists['general'] || [];
            }
            
            // 플레이리스트 그리드 생성
            const grid = document.getElementById('playlistGrid');
            grid.innerHTML = '';
            
            if (playlistArray.length === 0) {
                grid.innerHTML = '<p style="text-align: center; color: #666; grid-column: 1/-1;">해당 항목에 대한 음악 플레이리스트가 없습니다.</p>';
            } else {
                playlistArray.forEach(playlist => {
                    const card = document.createElement('div');
                    card.className = 'playlist-card';
                    card.innerHTML = `
                        <div class="playlist-icon">${playlist.icon}</div>
                        <div class="playlist-title">${playlist.title}</div>
                        <div class="playlist-description">${playlist.description}</div>
                        <div class="playlist-price">
                            <span>🪙</span>
                            <span>${playlist.price}</span>
                        </div>
                        <button class="purchase-btn" onclick="purchasePlaylist(${playlist.id}, ${playlist.price})">
                            구매하기
                        </button>
                    `;
                    grid.appendChild(card);
                });
            }
            
            // 플레이리스트 섹션 표시
            document.getElementById('playlistSection').style.display = 'block';
            document.getElementById('playlistSection').scrollIntoView({ behavior: 'smooth' });
        }
        
        // 플레이리스트 구매
        function purchasePlaylist(playlistId, price) {
            if (coinBalance < price) {
                alert(`코인이 부족합니다! 필요 코인: ${price}, 현재 코인: ${coinBalance}`);
                return;
            }
            
            if (confirm(`이 플레이리스트를 ${price} 코인에 구매하시겠습니까?`)) {
                // AJAX로 서버에 구매 요청
                fetch('brainsoundmarket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=purchase&playlist_id=${playlistId}&price=${price}&userid=<?php echo $studentid; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        coinBalance -= price;
                        document.querySelector('.coin-balance-header span:last-child').textContent = 
                            `내 코인: ${coinBalance.toLocaleString()}`;
                        
                        // 구매 완료 표시
                        const btn = event.target;
                        btn.disabled = true;
                        btn.textContent = '구매 완료';
                        btn.classList.add('owned');
                        btn.parentElement.classList.add('owned');
                        
                        // 소유 배지 추가
                        const badge = document.createElement('div');
                        badge.className = 'owned-badge';
                        badge.textContent = '소유함';
                        btn.parentElement.appendChild(badge);
                        
                        alert('구매가 완료되었습니다! 🎉');
                    } else {
                        alert('구매 중 오류가 발생했습니다: ' + (data.message || '알 수 없는 오류'));
                    }
                })
                .catch(error => {
                    console.error('구매 오류:', error);
                    alert('구매 중 오류가 발생했습니다. [File: brainsoundmarket.php, Line: purchase function]');
                });
            }
        }
    </script>
    
    <?php
    // 구매 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purchase') {
        $playlist_id = intval($_POST['playlist_id']);
        $price = intval($_POST['price']);
        $userid = intval($_POST['userid']);
        
        try {
            // 코인 차감
            $current_coin = $DB->get_record_sql(
                "SELECT quantity FROM mdl_block_stash_user_items WHERE userid = ? AND itemid = 495",
                [$userid]
            );
            
            if (!$current_coin || $current_coin->quantity < $price) {
                echo json_encode(['success' => false, 'message' => '코인이 부족합니다']);
                exit;
            }
            
            $new_balance = $current_coin->quantity - $price;
            $DB->execute(
                "UPDATE mdl_block_stash_user_items SET quantity = ? WHERE userid = ? AND itemid = 495",
                [$new_balance, $userid]
            );
            
            // 구매 기록 저장 (필요시 별도 테이블 생성)
            // $DB->insert_record('brainsound_purchases', [
            //     'userid' => $userid,
            //     'playlist_id' => $playlist_id,
            //     'price' => $price,
            //     'timecreated' => time()
            // ]);
            
            echo json_encode(['success' => true, 'message' => '구매 완료']);
            exit;
        } catch (Exception $e) {
            error_log("Purchase error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            echo json_encode(['success' => false, 'message' => '구매 처리 중 오류가 발생했습니다']);
            exit;
        }
    }
    ?>
</body>
</html>

