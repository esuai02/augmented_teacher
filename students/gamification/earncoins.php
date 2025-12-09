<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
require_login();
$studentid=$_GET["userid"]; 
if($studentid==NULL) $studentid=$USER->id;
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;
   
$timecreated=time(); 
   
$username= $DB->get_record_sql("SELECT id,hideinput,lastname, firstname,timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1 ");

$studentname=$username->firstname.$username->lastname;

// 코인 잔액 조회 (mdl_block_stash_user_items 테이블에서 itemid=495인 quantity 값)
$coin_record = $DB->get_record_sql("SELECT quantity FROM mdl_block_stash_user_items WHERE userid='$studentid' AND itemid='495' ");
$coin_balance = $coin_record ? (int)$coin_record->quantity : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏦 카이스트 터치수학 코인 환전소</title>
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
            width: 80%;
            max-width: 100%;
            margin: 0 auto;
        }
        
        /* 헤더 */
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease;
        }
        
        .header h1 {
            font-size: 3em;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .coin-icon {
            display: inline-block;
            animation: rotate 2s linear infinite;
        }
        
        .coin-icon img {
            width: 1em;
            height: 1em;
            vertical-align: middle;
        }
        
        .coin-img {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
        }
        
        .coin-img-small {
            width: 0.8em;
            height: 0.8em;
        }
        
        .coin-img-medium {
            width: 1.2em;
            height: 1.2em;
        }
        
        .coin-img-large {
            width: 1.5em;
            height: 1.5em;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* 학생 정보 카드 */
        .student-info {
            background: white;
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 0.8s ease;
        }
        
        .student-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
        }
        
        .student-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .student-name {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .student-level {
            color: #667eea;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .coin-balance {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            padding: 20px 30px;
            border-radius: 20px;
            color: white;
            text-align: center;
            min-width: 200px;
            animation: pulse 2s ease infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .balance-label {
            font-size: 0.9em;
            margin-bottom: 5px;
            opacity: 0.95;
        }
        
        .balance-amount {
            font-size: 2.5em;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        /* 환전 가능 항목 */
        .exchange-section {
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
        
        .exchange-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .exchange-card {
            background: linear-gradient(135deg, #f6f7f9 0%, #ffffff 100%);
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            padding: 25px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }
        
        .exchange-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .exchange-card.ready {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            border-color: #43e97b;
            animation: glow 2s ease infinite;
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(67, 233, 123, 0.3); }
            50% { box-shadow: 0 0 30px rgba(67, 233, 123, 0.5); }
        }
        
        .exchange-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .exchange-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .exchange-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
        }
        
        .exchange-badge {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .exchange-stats {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        
        .exchange-progress {
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .exchange-reward {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
        }
        
        .reward-amount {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.3em;
            font-weight: bold;
            color: #FFD700;
        }
        
        .exchange-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }
        
        .exchange-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .exchange-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* 교환 히스토리 */
        .history-section {
            background: white;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th {
            background: #f6f7f9;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .history-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .history-table tr:hover {
            background: #f6f7f9;
        }
        
        .transaction-type {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .transaction-type.exchange {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .transaction-type.reward {
            background: #fff3e0;
            color: #ff9800;
        }
        
        /* 리워드 샵 프리뷰 */
        .reward-shop {
            background: white;
            border-radius: 25px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .reward-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .reward-item {
            background: #f6f7f9;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .reward-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .reward-item.shop-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            display: block;
        }
        
        .reward-item.shop-link:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .reward-item.shop-link .reward-name {
            color: white;
        }
        
        .reward-item.shop-link.premium {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            position: relative;
        }
        
        .reward-item.shop-link.premium:hover {
            background: linear-gradient(135deg, #FFA500 0%, #FFD700 100%);
        }
        
        .reward-item.shop-link.stationery {
            background: linear-gradient(135deg, #ff6b9d 0%, #c44569 100%);
        }
        
        .reward-item.shop-link.stationery:hover {
            background: linear-gradient(135deg, #c44569 0%, #ff6b9d 100%);
        }
        
        .reward-item.shop-link.premium .reward-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .reward-emoji {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .reward-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        .reward-icon {
            font-size: 4em;
            margin-bottom: 10px;
            display: block;
        }
        
        .reward-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .reward-price {
            color: #FFD700;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .coming-soon {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ff4757;
            color: white;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 0.7em;
            font-weight: bold;
        }
        
        /* 성공 애니메이션 */
        .success-animation {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            z-index: 1000;
            display: none;
            text-align: center;
        }
        
        .success-animation.show {
            display: block;
            animation: bounceIn 0.5s ease;
        }
        
        @keyframes bounceIn {
            0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0; }
            50% { transform: translate(-50%, -50%) scale(1.05); }
            70% { transform: translate(-50%, -50%) scale(0.9); }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }
        
        .success-icon {
            font-size: 4em;
            margin-bottom: 20px;
            animation: spin 1s ease;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .success-message {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .success-coins {
            font-size: 2em;
            color: #FFD700;
            font-weight: bold;
        }
        
        /* 코인 떨어지는 애니메이션 */
        .coin-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 999;
        }
        
        .falling-coin {
            position: absolute;
            font-size: 2em;
            animation: fall 2s linear;
        }
        
        @keyframes fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        /* 랭킹 및 히스토리 컨테이너 */
        .ranking-history-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        /* 랭킹 섹션 */
        .ranking-section {
            background: white;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .ranking-list {
            display: grid;
            gap: 15px;
        }
        
        .ranking-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: #f6f7f9;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .ranking-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .ranking-item.top1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
        }
        
        .ranking-item.top2 {
            background: linear-gradient(135deg, #C0C0C0, #B8B8B8);
            color: white;
        }
        
        .ranking-item.top3 {
            background: linear-gradient(135deg, #CD7F32, #B87333);
            color: white;
        }
        
        .ranking-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ranking-number {
            font-size: 1.5em;
            font-weight: bold;
            width: 40px;
            text-align: center;
        }
        
        .ranking-name {
            font-weight: 600;
        }
        
        .ranking-coins {
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* 반응형 */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .student-header {
                flex-direction: column;
                text-align: center;
            }
            
            .coin-balance {
                width: 100%;
            }
            
            .exchange-grid {
                grid-template-columns: 1fr;
            }
            
            .reward-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
            
            .ranking-history-container {
                grid-template-columns: 1fr;
            }
            
            .history-table {
                font-size: 0.9em;
            }
            
            .history-table th,
            .history-table td {
                padding: 10px 5px;
            }
        }
        
        /* 애니메이션 */
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
        
        .badge-new {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff4757;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: bold;
            animation: pulse 1s ease infinite;
        }
        
        /* 툴팁 */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: rgba(0,0,0,0.9);
            color: #fff;
            text-align: center;
            border-radius: 10px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.9em;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* 보물상자 이미지 */
        .treasure-box-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: fadeInDown 0.8s ease;
            text-align: center;
        }
        
        .treasure-box-link a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
        }
        
        .treasure-box-link img {
            width: 144px;
            max-width: 144px;
            height: auto;
            transition: transform 0.3s ease;
            display: block;
        }
        
        .treasure-box-link:hover img {
            transform: scale(1.15);
        }
        
        .treasure-box-label {
            margin-top: 8px;
            font-size: 0.9em;
            color: #667eea;
            font-weight: 600;
            text-align: center;
        }
        
        .coin-balance {
            justify-self: end;
        }
        
        @media (max-width: 768px) {
            .student-header {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .treasure-box-link {
                order: 2;
            }
            
            .coin-balance {
                justify-self: center;
                order: 3;
            }
            
            .student-profile {
                order: 1;
                justify-self: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <h1>
                <span class="coin-icon">🪙</span>
                <span>터치수학 코인 환전소</span>
                <span class="coin-icon">💰</span>
            </h1>
            <p style="font-size: 1.2em; opacity: 0.95;">열심히 공부한 만큼 보상받자!</p>
        </div>
        
        <!-- 학생 정보 카드 -->
        <div class="student-info">
            <div class="student-header">
                <div class="student-profile">
                    <div class="avatar">😊</div>
                    <div>
                        <div class="student-name"><?php echo $studentname; ?></div>
                        <div class="student-level">
                            <span>🏆</span> Level 15 수학 마스터
                        </div>
                    </div>
                </div>
                
                <!-- 보물상자 링크 -->
                <div class="treasure-box-link">
                    <a href="https://mathking.kr/moodle/course/view.php?id=88" target="_blank">
                        <img src="https://mathking.kr/Contents/Moodle/visual%20art2/treasurebox.gif" alt="보물상자">
                        <div class="treasure-box-label">몬스터 구입처</div>
                    </a>
                </div>
                
                <div class="coin-balance">
                    <div class="balance-label">내 코인 잔액</div>
                    <div class="balance-amount">
                        <span>🪙</span>
                        <span id="coinBalance"><?php echo number_format($coin_balance); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 환전 가능 항목 -->
        <div class="exchange-section">
            <h2 class="section-title">
                <span>💱</span>
                <span>환전 가능한 학습 데이터</span>
            </h2>
            
            <div class="exchange-grid">
                <!-- 포모도로 카드 -->
                <div class="exchange-card ready" onclick="exchangeCoins('pomodoro', 12, 120)">
                    <div class="badge-new">환전 가능!</div>
                    <div class="exchange-header">
                        <div>
                            <div class="exchange-icon">🍅</div>
                            <div class="exchange-title">포모도로 달성</div>
                        </div>
                        <div class="exchange-badge">연속 12회</div>
                    </div>
                    
                    <div class="exchange-stats">
                        <div class="stat-item">
                            <div class="stat-value">12</div>
                            <div class="stat-label">완료</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">180분</div>
                            <div class="stat-label">집중시간</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">95%</div>
                            <div class="stat-label">집중도</div>
                        </div>
                    </div>
                    
                    <div class="exchange-progress">
                        <div class="progress-fill" style="width: 100%;"></div>
                    </div>
                    
                    <div class="exchange-reward">
                        <div class="reward-amount">
                            <span>🪙</span>
                            <span>+120</span>
                        </div>
                        <button class="exchange-btn">환전하기</button>
                    </div>
                </div>
                
                <!-- 오답노트 카드 -->
                <div class="exchange-card ready" onclick="exchangeCoins('error_note', 24, 240)">
                    <div class="badge-new">환전 가능!</div>
                    <div class="exchange-header">
                        <div>
                            <div class="exchange-icon">📝</div>
                            <div class="exchange-title">오답노트 우수</div>
                        </div>
                        <div class="exchange-badge">연속 24일</div>
                    </div>
                    
                    <div class="exchange-stats">
                        <div class="stat-item">
                            <div class="stat-value">156</div>
                            <div class="stat-label">문제 분석</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">89%</div>
                            <div class="stat-label">재정답률</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">A+</div>
                            <div class="stat-label">품질</div>
                        </div>
                    </div>
                    
                    <div class="exchange-progress">
                        <div class="progress-fill" style="width: 100%;"></div>
                    </div>
                    
                    <div class="exchange-reward">
                        <div class="reward-amount">
                            <span>🪙</span>
                            <span>+240</span>
                        </div>
                        <button class="exchange-btn">환전하기</button>
                    </div>
                </div>
                
                <!-- 목표 달성 카드 -->
                <div class="exchange-card ready" onclick="exchangeCoins('goal', 20, 200)">
                    <div class="badge-new">환전 가능!</div>
                    <div class="exchange-header">
                        <div>
                            <div class="exchange-icon">🎯</div>
                            <div class="exchange-title">목표 몰입도</div>
                        </div>
                        <div class="exchange-badge">연속 20일</div>
                    </div>
                    
                    <div class="exchange-stats">
                        <div class="stat-item">
                            <div class="stat-value">100%</div>
                            <div class="stat-label">달성률</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">20</div>
                            <div class="stat-label">연속 일수</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">⭐⭐⭐</div>
                            <div class="stat-label">평가</div>
                        </div>
                    </div>
                    
                    <div class="exchange-progress">
                        <div class="progress-fill" style="width: 100%;"></div>
                    </div>
                    
                    <div class="exchange-reward">
                        <div class="reward-amount">
                            <span>🪙</span>
                            <span>+200</span>
                        </div>
                        <button class="exchange-btn">환전하기</button>
                    </div>
                </div>
                
                <!-- 점수 우수 카드 -->
                <div class="exchange-card ready" onclick="exchangeCoins('score', 12, 180)">
                    <div class="badge-new">환전 가능!</div>
                    <div class="exchange-header">
                        <div>
                            <div class="exchange-icon">💯</div>
                            <div class="exchange-title">점수 우수</div>
                        </div>
                        <div class="exchange-badge">연속 12일</div>
                    </div>
                    
                    <div class="exchange-stats">
                        <div class="stat-item">
                            <div class="stat-value">94.5</div>
                            <div class="stat-label">평균 점수</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">12</div>
                            <div class="stat-label">연속 일수</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">S급</div>
                            <div class="stat-label">등급</div>
                        </div>
                    </div>
                    
                    <div class="exchange-progress">
                        <div class="progress-fill" style="width: 100%;"></div>
                    </div>
                    
                    <div class="exchange-reward">
                        <div class="reward-amount">
                            <span>🪙</span>
                            <span>+180</span>
                        </div>
                        <button class="exchange-btn">환전하기</button>
                    </div>
                </div>
                
                <!-- 출석 카드 -->
                <div class="exchange-card" onclick="showNotReady()">
                    <div class="exchange-header">
                        <div>
                            <div class="exchange-icon">⏰</div>
                            <div class="exchange-title">지각 안하기</div>
                        </div>
                        <div class="exchange-badge">8/10회</div>
                    </div>
                    
                    <div class="exchange-stats">
                        <div class="stat-item">
                            <div class="stat-value">8</div>
                            <div class="stat-label">출석</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">2</div>
                            <div class="stat-label">남음</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">80%</div>
                            <div class="stat-label">달성률</div>
                        </div>
                    </div>
                    
                    <div class="exchange-progress">
                        <div class="progress-fill" style="width: 80%;"></div>
                    </div>
                    
                    <div class="exchange-reward">
                        <div class="reward-amount">
                            <span style="opacity: 0.5;">🪙</span>
                            <span style="opacity: 0.5;">+100</span>
                        </div>
                        <button class="exchange-btn" disabled>2회 더 필요</button>
                    </div>
                </div>
                
                <!-- 자기설명 활동 카드 -->
                <div class="exchange-card" onclick="showNotReady()">
                    <div class="exchange-header">
                        <div>
                            <div class="exchange-icon">🏅</div>
                            <div class="exchange-title">자기설명 활동</div>
                        </div>
                        <div class="exchange-badge">3/5 완료</div>
                    </div>
                    
                    <div class="exchange-stats">
                        <div class="stat-item">
                            <div class="stat-value">3</div>
                            <div class="stat-label">완료</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">2</div>
                            <div class="stat-label">남음</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">60%</div>
                            <div class="stat-label">달성률</div>
                        </div>
                    </div>
                    
                    <div class="exchange-progress">
                        <div class="progress-fill" style="width: 60%;"></div>
                    </div>
                    
                    <div class="exchange-reward">
                        <div class="reward-amount">
                            <span style="opacity: 0.5;">🪙</span>
                            <span style="opacity: 0.5;">+150</span>
                        </div>
                        <button class="exchange-btn" disabled>2개 더 필요</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 리워드 샵 프리뷰 -->
        <div class="reward-shop">
            <h2 class="section-title">
                <span>🎁</span>
                <span>코인으로 구매 가능한 리워드</span>
            </h2>
            
            <div class="reward-grid">
                <div class="reward-item" onclick="purchaseReward('피카추', 5)">
                    <div class="reward-icon">⚡</div>
                    <div class="reward-name">피카추</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>5</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('이상해씨', 5)">
                    <div class="reward-icon">🌱</div>
                    <div class="reward-name">이상해씨</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>5</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('이브이', 200)">
                    <div class="reward-icon">🦊</div>
                    <div class="reward-name">이브이</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>200</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('고라파덕', 5)">
                    <div class="reward-icon">🦆</div>
                    <div class="reward-name">고라파덕</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>5</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('글레이시아', 200)">
                    <div class="reward-icon">❄️</div>
                    <div class="reward-name">글레이시아</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>200</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('리피아', 200)">
                    <div class="reward-icon">🍃</div>
                    <div class="reward-name">리피아</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>200</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('샤미드', 200)">
                    <div class="reward-icon">💧</div>
                    <div class="reward-name">샤미드</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>200</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('메타몽', 75)">
                    <div class="reward-icon">🟣</div>
                    <div class="reward-name">메타몽</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>75</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('후딘', 75)">
                    <div class="reward-icon">🧠</div>
                    <div class="reward-name">후딘</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>75</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('데가라스', 25)">
                    <div class="reward-icon">🪨</div>
                    <div class="reward-name">데가라스</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>25</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('나무돌이', 25)">
                    <div class="reward-icon">🌳</div>
                    <div class="reward-name">나무돌이</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>25</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('라이코', 450)">
                    <div class="reward-icon">⚡</div>
                    <div class="reward-name">라이코</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>450</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('홍수몬', 225)">
                    <div class="reward-icon">🌊</div>
                    <div class="reward-name">홍수몬</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>225</span>
                    </div>
                </div>
                
                <div class="reward-item" onclick="purchaseReward('마그마', 75)">
                    <div class="reward-icon">🔥</div>
                    <div class="reward-name">마그마</div>
                    <div class="reward-price">
                        <span>🪙</span>
                        <span>75</span>
                    </div>
                </div>
                
                <a href="https://mathking.kr/moodle/course/view.php?id=88&section=2" target="_blank" class="reward-item shop-link">
                    <div class="reward-icon">👹</div>
                    <div class="reward-name">몬스터 상점</div>
                </a>
                
                <a href="https://mathking.kr/moodle/course/view.php?id=88&section=1" target="_blank" class="reward-item shop-link premium">
                    <div class="reward-icon">💎</div>
                    <div class="reward-name">고급몬스터 상점</div>
                </a>
                
                <a href="brainsoundmarket.php?userid=<?php echo $studentid; ?>" class="reward-item shop-link stationery">
                    <div class="reward-icon">🎵</div>
                    <div class="reward-name">Mathking Brain Sound 상점</div>
                </a>
            </div>
        </div>
        
        <!-- 랭킹 및 히스토리 컨테이너 -->
        <div class="ranking-history-container">
            <!-- 코인 랭킹 -->
            <div class="ranking-section">
                <h2 class="section-title">
                    <span>🏆</span>
                    <span>이번 주 랭킹</span>
                </h2>
                
                <div class="ranking-list">
                    <div class="ranking-item top1">
                        <div class="ranking-left">
                            <div class="ranking-number">🥇</div>
                            <div class="ranking-name">이지원</div>
                        </div>
                        <div class="ranking-coins">
                            <span>🪙</span>
                            <span>2,850</span>
                        </div>
                    </div>
                    
                    <div class="ranking-item top2">
                        <div class="ranking-left">
                            <div class="ranking-number">🥈</div>
                            <div class="ranking-name">박민준</div>
                        </div>
                        <div class="ranking-coins">
                            <span>🪙</span>
                            <span>2,420</span>
                        </div>
                    </div>
                    
                    <div class="ranking-item top3">
                        <div class="ranking-left">
                            <div class="ranking-number">🥉</div>
                            <div class="ranking-name">최서연</div>
                        </div>
                        <div class="ranking-coins">
                            <span>🪙</span>
                            <span>2,180</span>
                        </div>
                    </div>
                    
                    <div class="ranking-item">
                        <div class="ranking-left">
                            <div class="ranking-number">4</div>
                            <div class="ranking-name">정하늘</div>
                        </div>
                        <div class="ranking-coins">
                            <span>🪙</span>
                            <span>1,950</span>
                        </div>
                    </div>
                    
                    <div class="ranking-item">
                        <div class="ranking-left">
                            <div class="ranking-number">5</div>
                            <div class="ranking-name">김수학 (나)</div>
                        </div>
                        <div class="ranking-coins">
                            <span>🪙</span>
                            <span>1,250</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 교환 히스토리 -->
            <div class="history-section">
                <h2 class="section-title">
                    <span>📜</span>
                    <span>최근 거래 내역</span>
                </h2>
                
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>날짜</th>
                            <th>항목</th>
                            <th>타입</th>
                            <th>코인</th>
                            <th>잔액</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <tr>
                            <td>2025.01.08</td>
                            <td>오답노트 우수 (7일)</td>
                            <td><span class="transaction-type exchange">환전</span></td>
                            <td style="color: #27ae60; font-weight: bold;">+70 🪙</td>
                            <td>1,250 🪙</td>
                        </tr>
                        <tr>
                            <td>2025.01.06</td>
                            <td>파이리 구매</td>
                            <td><span class="transaction-type reward">구매</span></td>
                            <td style="color: #e74c3c; font-weight: bold;">-450 🪙</td>
                            <td>1,180 🪙</td>
                        </tr>
                        <tr>
                            <td>2025.01.05</td>
                            <td>포모도로 달성 (10회)</td>
                            <td><span class="transaction-type exchange">환전</span></td>
                            <td style="color: #27ae60; font-weight: bold;">+100 🪙</td>
                            <td>1,630 🪙</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 성공 애니메이션 -->
    <div class="success-animation" id="successAnimation">
        <div class="success-icon">🎉</div>
        <div class="success-message">환전 성공!</div>
        <div class="success-coins" id="successCoins">+0 🪙</div>
    </div>
    
    <!-- 코인 떨어지는 효과 -->
    <div class="coin-rain" id="coinRain"></div>
    
    <script>
        let currentBalance = <?php echo $coin_balance; ?>;
        
        // 환전 기능
        function exchangeCoins(type, days, coins) {
            // 코인 추가
            currentBalance += coins;
            document.getElementById('coinBalance').textContent = currentBalance.toLocaleString();
            
            // 성공 애니메이션 표시
            const successAnim = document.getElementById('successAnimation');
            document.getElementById('successCoins').textContent = `+${coins} 🪙`;
            successAnim.classList.add('show');
            
            // 코인 떨어지는 효과
            createCoinRain();
            
            // 히스토리 추가
            addHistory(type, days, coins);
            
            // 카드 비활성화
            event.currentTarget.classList.remove('ready');
            event.currentTarget.querySelector('.exchange-btn').disabled = true;
            event.currentTarget.querySelector('.exchange-btn').textContent = '환전 완료';
            
            // 뱃지 제거
            const badge = event.currentTarget.querySelector('.badge-new');
            if (badge) badge.remove();
            
            // 3초 후 애니메이션 숨기기
            setTimeout(() => {
                successAnim.classList.remove('show');
            }, 3000);
        }
        
        // 코인 떨어지는 효과
        function createCoinRain() {
            const rainContainer = document.getElementById('coinRain');
            rainContainer.innerHTML = '';
            
            for (let i = 0; i < 20; i++) {
                setTimeout(() => {
                    const coin = document.createElement('div');
                    coin.className = 'falling-coin';
                    coin.textContent = '🪙';
                    coin.style.left = Math.random() * 100 + '%';
                    coin.style.animationDelay = Math.random() * 0.5 + 's';
                    rainContainer.appendChild(coin);
                    
                    setTimeout(() => coin.remove(), 2000);
                }, i * 100);
            }
        }
        
        // 히스토리 추가
        function addHistory(type, days, coins) {
            const historyBody = document.getElementById('historyBody');
            const newRow = document.createElement('tr');
            const date = new Date().toLocaleDateString('ko-KR').replace(/\. /g, '.').replace('.', '');
            
            const typeNames = {
                'pomodoro': '포모도로 달성',
                'error_note': '오답노트 우수',
                'goal': '목표 몰입도',
                'score': '점수 우수'
            };
            
            newRow.innerHTML = `
                <td>${date}</td>
                <td>${typeNames[type]} (${days}회/일)</td>
                <td><span class="transaction-type exchange">환전</span></td>
                <td style="color: #27ae60; font-weight: bold;">+${coins} 🪙</td>
                <td>${currentBalance.toLocaleString()} 🪙</td>
            `;
            
            newRow.style.background = '#fffacd';
            historyBody.insertBefore(newRow, historyBody.firstChild);
            
            setTimeout(() => {
                newRow.style.transition = 'background 1s ease';
                newRow.style.background = 'transparent';
            }, 100);
        }
        
        // 리워드 구매
        function purchaseReward(name, price) {
            if (currentBalance >= price) {
                if (confirm(`${name}을(를) ${price} 코인에 구매하시겠습니까?`)) {
                    currentBalance -= price;
                    document.getElementById('coinBalance').textContent = currentBalance.toLocaleString();
                    
                    // 구매 성공 애니메이션
                    const successAnim = document.getElementById('successAnimation');
                    successAnim.querySelector('.success-message').textContent = '구매 성공!';
                    successAnim.querySelector('.success-icon').textContent = '🎁';
                    document.getElementById('successCoins').textContent = `${name} 획득!`;
                    successAnim.classList.add('show');
                    
                    // 히스토리에 구매 내역 추가
                    const historyBody = document.getElementById('historyBody');
                    const newRow = document.createElement('tr');
                    const date = new Date().toLocaleDateString('ko-KR').replace(/\. /g, '.').replace('.', '');
                    
                    newRow.innerHTML = `
                        <td>${date}</td>
                        <td>${name} 구매</td>
                        <td><span class="transaction-type reward">구매</span></td>
                        <td style="color: #e74c3c; font-weight: bold;">-${price} 🪙</td>
                        <td>${currentBalance.toLocaleString()} 🪙</td>
                    `;
                    
                    historyBody.insertBefore(newRow, historyBody.firstChild);
                    
                    setTimeout(() => {
                        successAnim.classList.remove('show');
                        // 원래 텍스트로 복원
                        successAnim.querySelector('.success-message').textContent = '환전 성공!';
                        successAnim.querySelector('.success-icon').textContent = '🎉';
                    }, 3000);
                }
            } else {
                alert(`코인이 부족합니다! 필요 코인: ${price}, 현재 코인: ${currentBalance}`);
            }
        }
        
        // 아직 준비되지 않은 항목 클릭
        function showNotReady() {
            alert('조건을 충족하면 환전할 수 있습니다! 조금만 더 힘내세요! 💪');
        }
        
        // 페이지 로드 시 애니메이션
        window.addEventListener('load', function() {
            // 카드 순차 등장
            const cards = document.querySelectorAll('.exchange-card, .reward-item, .ranking-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
            
            // 환전 가능한 카드 강조
            const readyCards = document.querySelectorAll('.exchange-card.ready');
            readyCards.forEach(card => {
                setInterval(() => {
                    card.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        card.style.transform = 'scale(1)';
                    }, 500);
                }, 3000);
            });
        });
        
        // 실시간 시간 업데이트 (선택사항)
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('ko-KR');
            // 여기에 시간 표시 로직 추가 가능
        }
        
        setInterval(updateTime, 1000);
    </script>
</body>
</html>