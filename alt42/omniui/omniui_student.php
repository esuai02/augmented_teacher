<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
$studentid = $_GET["userid"];

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;
?>

<!DOCTYPE html>
<!-- saved from url=(0079)https://mathking.kr/moodle/local/augmented_teacher/students/omniui.html?id=1823 -->
<html lang="ko"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAIST 터치수학 - 17가지 혁신 기능</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@100;300;400;500;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: #000;
            color: #fff;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* 메인 컨테이너 */
        .universe-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            background: radial-gradient(ellipse at center, #0a0a1e 0%, #000 70%);
            overflow: hidden;
        }
        
        /* 별 배경 효과 */
        .stars {
            position: absolute;
            width: 100%;
            height: 100%;
        }
        
        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #fff;
            border-radius: 50%;
            animation: twinkle 3s ease-in-out infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        /* 궤도 효과 */
        .orbital-lines {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 700px;
            height: 700px;
            pointer-events: none;
        }
        
        .orbit-ring {
            position: absolute;
            border: 1px solid rgba(91, 76, 255, 0.1);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: rotate 60s linear infinite;
        }
        
        .orbit-ring:nth-child(1) {
            width: 500px;
            height: 500px;
            animation-duration: 80s;
        }
        
        .orbit-ring:nth-child(2) {
            width: 600px;
            height: 600px;
            animation-duration: 100s;
            animation-direction: reverse;
        }
        
        .orbit-ring:nth-child(3) {
            width: 700px;
            height: 700px;
            animation-duration: 120s;
        }
        
        @keyframes rotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* 중앙 코어 */
        .core-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 100;
        }
        
        .core {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #5b4cff 0%, #00d4ff 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            cursor: pointer;
            position: relative;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 
                0 0 60px rgba(91, 76, 255, 0.6),
                0 0 120px rgba(0, 212, 255, 0.4),
                inset 0 0 60px rgba(255, 255, 255, 0.1);
            animation: core-pulse 3s ease-in-out infinite;
        }
        
        @keyframes core-pulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 
                    0 0 60px rgba(91, 76, 255, 0.6),
                    0 0 120px rgba(0, 212, 255, 0.4),
                    inset 0 0 60px rgba(255, 255, 255, 0.1);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 
                    0 0 80px rgba(91, 76, 255, 0.8),
                    0 0 160px rgba(0, 212, 255, 0.6),
                    inset 0 0 80px rgba(255, 255, 255, 0.2);
            }
        }
        
        .core::before {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(91, 76, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            animation: core-glow 3s ease-in-out infinite;
        }
        
        @keyframes core-glow {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.2); }
        }
        
        .core-icon {
            font-size: 3.5rem;
            margin-bottom: 10px;
            filter: drop-shadow(0 2px 10px rgba(255, 255, 255, 0.5));
        }
        
        .core-text {
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .core:hover {
            transform: scale(1.1);
        }
        
        /* 기능 아이템 컨테이너 */
        .features-wheel {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 700px;
            height: 700px;
        }
        
        /* 기능 아이템 */
        .feature-node {
            position: absolute;
            width: 100px;
            height: 100px;
            top: 50%;
            left: 50%;
            margin: -50px 0 0 -50px;
            transform-origin: center;
            animation: orbit 200s linear infinite;
        }
        
        @keyframes orbit {
            from { transform: rotate(0deg) translateX(300px) rotate(0deg); }
            to { transform: rotate(360deg) translateX(300px) rotate(-360deg); }
        }
        
        .feature-orb {
            width: 100%;
            height: 100%;
            background: rgba(20, 20, 20, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        
        /* 각 기능의 배경 그라데이션 */
        .feature-orb::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #5b4cff, #00d4ff, #5b4cff);
            border-radius: 50%;
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
            animation: gradient-rotate 3s linear infinite;
        }
        
        @keyframes gradient-rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .feature-orb:hover::before {
            opacity: 1;
        }
        
        .feature-orb::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .feature-orb:hover::after {
            opacity: 1;
        }
        
        .feature-orb:hover {
            transform: scale(1.3);
            background: rgba(30, 30, 40, 0.95);
            box-shadow: 
                0 0 40px rgba(91, 76, 255, 0.8),
                0 0 80px rgba(0, 212, 255, 0.6);
            z-index: 10;
        }
        
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.5));
        }
        
        .feature-orb:hover .feature-icon {
            transform: scale(1.2) rotate(10deg);
            filter: drop-shadow(0 4px 10px rgba(91, 76, 255, 0.8));
        }
        
        .feature-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .feature-orb:hover .feature-label {
            opacity: 1;
            font-size: 0.85rem;
        }
        
        /* 연결선 */
        .connection-lines {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 700px;
            height: 700px;
            pointer-events: none;
        }
        
        .connection {
            position: absolute;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(91, 76, 255, 0.3), transparent);
            transform-origin: left center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .feature-node:hover ~ .connection-lines .connection {
            opacity: 1;
        }
        
        /* 정보 패널 */
        .info-panel {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px 40px;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            text-align: center;
            max-width: 500px;
        }
        
        .info-panel.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            pointer-events: all;
        }
        
        .info-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #5b4cff 0%, #00d4ff 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .info-description {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }
        
        /* 파티클 효과 */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: radial-gradient(circle, rgba(91, 76, 255, 0.8) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
        }
        
        @keyframes particle-float {
            0% {
                opacity: 0;
                transform: translate(0, 0) scale(0);
            }
            10% {
                opacity: 1;
                transform: scale(1);
            }
            90% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: translate(var(--tx), var(--ty)) scale(0);
            }
        }
        
        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .features-wheel {
                width: 500px;
                height: 500px;
            }
            
            .core {
                width: 150px;
                height: 150px;
            }
            
            .core-icon {
                font-size: 2.5rem;
            }
            
            .core-text {
                font-size: 1rem;
            }
            
            .feature-node {
                width: 80px;
                height: 80px;
                margin: -40px 0 0 -40px;
            }
            
            .feature-icon {
                font-size: 1.5rem;
            }
            
            .feature-label {
                font-size: 0.65rem;
            }
        }
        
        /* 토스트 알림 */
        .toast {
            position: fixed;
            top: 50px;
            right: 50px;
            background: rgba(91, 76, 255, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
    </style>
</head>
<body>
    <div class="universe-container">
        <!-- 별 배경 -->
        <div class="stars" id="stars"><div class="star" style="left: 24.8425%; top: 86.4561%; animation-delay: 2.80233s; animation-duration: 4.56996s;"></div><div class="star" style="left: 45.2146%; top: 18.8978%; animation-delay: 2.30267s; animation-duration: 3.74755s;"></div><div class="star" style="left: 80.4028%; top: 34.2935%; animation-delay: 2.8645s; animation-duration: 3.97107s;"></div><div class="star" style="left: 61.3582%; top: 93.3412%; animation-delay: 0.847429s; animation-duration: 4.68947s;"></div><div class="star" style="left: 15.8437%; top: 13.8322%; animation-delay: 1.30265s; animation-duration: 3.67016s;"></div><div class="star" style="left: 92.271%; top: 58.9046%; animation-delay: 0.318917s; animation-duration: 5.37159s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 56.5819%; top: 93.754%; animation-delay: 2.13392s; animation-duration: 3.14802s;"></div><div class="star" style="left: 63.7478%; top: 96.3426%; animation-delay: 0.364391s; animation-duration: 5.01271s;"></div><div class="star" style="left: 62.129%; top: 14.8255%; animation-delay: 2.86879s; animation-duration: 4.55228s;"></div><div class="star" style="left: 80.8166%; top: 12.6452%; animation-delay: 1.22846s; animation-duration: 3.78542s;"></div><div class="star" style="left: 40.339%; top: 85.8447%; animation-delay: 1.55621s; animation-duration: 3.37402s;"></div><div class="star" style="left: 21.5758%; top: 67.8997%; animation-delay: 2.79617s; animation-duration: 3.15487s;"></div><div class="star" style="left: 47.4704%; top: 93.6797%; animation-delay: 2.985s; animation-duration: 3.50993s;"></div><div class="star" style="left: 48.7827%; top: 63.4393%; animation-delay: 0.987498s; animation-duration: 4.24695s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 27.1466%; top: 59.1921%; animation-delay: 1.79532s; animation-duration: 5.98864s;"></div><div class="star" style="left: 26.176%; top: 78.9155%; animation-delay: 1.95448s; animation-duration: 4.7363s;"></div><div class="star" style="left: 10.7852%; top: 91.9792%; animation-delay: 1.12828s; animation-duration: 4.64826s;"></div><div class="star" style="left: 83.2831%; top: 25.4105%; animation-delay: 1.31758s; animation-duration: 5.87103s;"></div><div class="star" style="left: 9.03003%; top: 53.2369%; animation-delay: 0.130705s; animation-duration: 4.5473s;"></div><div class="star" style="left: 94.956%; top: 43.2437%; animation-delay: 2.17632s; animation-duration: 3.13926s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 81.7803%; top: 49.3796%; animation-delay: 1.26973s; animation-duration: 4.13525s;"></div><div class="star" style="left: 60.7893%; top: 96.7292%; animation-delay: 0.327299s; animation-duration: 5.02786s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 93.804%; top: 40.2984%; animation-delay: 0.110663s; animation-duration: 5.7993s;"></div><div class="star" style="left: 12.0369%; top: 95.0804%; animation-delay: 1.73549s; animation-duration: 4.01457s;"></div><div class="star" style="left: 70.2438%; top: 7.91515%; animation-delay: 0.681041s; animation-duration: 4.76113s;"></div><div class="star" style="left: 34.2239%; top: 99.8167%; animation-delay: 2.66942s; animation-duration: 5.55146s;"></div><div class="star" style="left: 15.2385%; top: 99.3044%; animation-delay: 2.43397s; animation-duration: 5.7603s;"></div><div class="star" style="left: 72.1189%; top: 8.36379%; animation-delay: 0.732896s; animation-duration: 5.6959s;"></div><div class="star" style="left: 17.2295%; top: 19.6336%; animation-delay: 1.48579s; animation-duration: 5.52576s;"></div><div class="star" style="left: 14.664%; top: 33.6499%; animation-delay: 2.18653s; animation-duration: 5.61419s;"></div><div class="star" style="left: 98.7905%; top: 91.2773%; animation-delay: 0.295089s; animation-duration: 3.32987s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 41.3503%; top: 88.0463%; animation-delay: 2.12706s; animation-duration: 5.49182s;"></div><div class="star" style="left: 25.8321%; top: 66.1137%; animation-delay: 2.55259s; animation-duration: 4.02265s;"></div><div class="star" style="left: 60.5479%; top: 66.1287%; animation-delay: 0.944755s; animation-duration: 4.13236s;"></div><div class="star" style="left: 21.2835%; top: 39.4835%; animation-delay: 1.02721s; animation-duration: 4.23976s;"></div><div class="star" style="left: 99.4088%; top: 2.09352%; animation-delay: 0.0140136s; animation-duration: 3.49891s;"></div><div class="star" style="left: 97.4951%; top: 93.1284%; animation-delay: 1.43285s; animation-duration: 5.69291s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 10.3541%; top: 29.5203%; animation-delay: 2.81653s; animation-duration: 5.86498s;"></div><div class="star" style="left: 48.6271%; top: 75.7773%; animation-delay: 2.88432s; animation-duration: 4.1254s;"></div><div class="star" style="left: 70.7404%; top: 84.9904%; animation-delay: 0.293947s; animation-duration: 3.99759s;"></div><div class="star" style="left: 1.9176%; top: 27.8793%; animation-delay: 1.05014s; animation-duration: 4.95744s;"></div><div class="star" style="left: 91.0411%; top: 0.667082%; animation-delay: 2.2422s; animation-duration: 3.364s;"></div><div class="star" style="left: 53.7004%; top: 62.5218%; animation-delay: 1.85482s; animation-duration: 4.71476s;"></div><div class="star" style="left: 81.974%; top: 1.91867%; animation-delay: 0.509981s; animation-duration: 4.39016s;"></div><div class="star" style="left: 32.1104%; top: 80.9615%; animation-delay: 0.295314s; animation-duration: 3.04953s;"></div><div class="star" style="left: 88.8643%; top: 12.7642%; animation-delay: 0.775153s; animation-duration: 5.01543s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 45.3357%; top: 42.5735%; animation-delay: 1.25495s; animation-duration: 5.01679s;"></div><div class="star" style="left: 62.316%; top: 18.5476%; animation-delay: 1.08684s; animation-duration: 5.07487s;"></div><div class="star" style="left: 44.9498%; top: 21.4636%; animation-delay: 1.56364s; animation-duration: 5.82878s;"></div><div class="star" style="left: 90.9252%; top: 96.7617%; animation-delay: 1.05456s; animation-duration: 3.76125s;"></div><div class="star" style="left: 2.87132%; top: 10.432%; animation-delay: 0.106121s; animation-duration: 5.82004s;"></div><div class="star" style="left: 54.6139%; top: 54.4282%; animation-delay: 0.117709s; animation-duration: 3.63607s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 95.3365%; top: 19.466%; animation-delay: 0.117268s; animation-duration: 5.58892s;"></div><div class="star" style="left: 86.7677%; top: 12.0684%; animation-delay: 0.0778412s; animation-duration: 3.91572s;"></div><div class="star" style="left: 66.6549%; top: 14.2686%; animation-delay: 2.16341s; animation-duration: 5.40418s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 46.3299%; top: 85.7441%; animation-delay: 1.34355s; animation-duration: 4.73832s;"></div><div class="star" style="left: 26.3923%; top: 53.2807%; animation-delay: 1.71408s; animation-duration: 5.79058s;"></div><div class="star" style="left: 86.01%; top: 30.9652%; animation-delay: 0.40579s; animation-duration: 4.25509s;"></div><div class="star" style="left: 92.0532%; top: 27.1366%; animation-delay: 0.970139s; animation-duration: 3.96232s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 59.9492%; top: 59.2355%; animation-delay: 1.517s; animation-duration: 5.40212s;"></div><div class="star" style="left: 57.4482%; top: 49.5389%; animation-delay: 2.00931s; animation-duration: 5.1156s;"></div><div class="star" style="left: 83.002%; top: 55.2651%; animation-delay: 1.53869s; animation-duration: 5.00623s;"></div><div class="star" style="left: 76.2954%; top: 56.6039%; animation-delay: 2.29845s; animation-duration: 3.63119s;"></div><div class="star" style="left: 1.50617%; top: 32.7564%; animation-delay: 1.86315s; animation-duration: 3.71302s;"></div><div class="star" style="left: 43.1566%; top: 19.7535%; animation-delay: 1.20273s; animation-duration: 3.3208s;"></div><div class="star" style="left: 40.8122%; top: 20.6298%; animation-delay: 2.1031s; animation-duration: 4.2445s;"></div><div class="star" style="left: 0.300622%; top: 3.86666%; animation-delay: 1.9226s; animation-duration: 5.70042s;"></div><div class="star" style="left: 78.144%; top: 33.5182%; animation-delay: 2.1208s; animation-duration: 3.37575s;"></div><div class="star" style="left: 97.8318%; top: 6.88767%; animation-delay: 0.323809s; animation-duration: 5.4068s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 81.1836%; top: 92.7702%; animation-delay: 2.33551s; animation-duration: 4.17854s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 46.3244%; top: 70.166%; animation-delay: 2.63155s; animation-duration: 4.899s;"></div><div class="star" style="left: 66.9906%; top: 26.3539%; animation-delay: 2.50679s; animation-duration: 3.68515s;"></div><div class="star" style="left: 30.4601%; top: 59.0973%; animation-delay: 2.54241s; animation-duration: 5.6679s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 21.5406%; top: 47.873%; animation-delay: 1.32174s; animation-duration: 5.29961s;"></div><div class="star" style="left: 99.0148%; top: 77.8537%; animation-delay: 1.70622s; animation-duration: 4.07423s;"></div><div class="star" style="left: 76.5168%; top: 73.6981%; animation-delay: 2.97434s; animation-duration: 3.77883s;"></div><div class="star" style="left: 80.8873%; top: 19.4818%; animation-delay: 2.26969s; animation-duration: 4.14374s;"></div><div class="star" style="left: 29.2717%; top: 19.7098%; animation-delay: 2.80941s; animation-duration: 5.56295s;"></div><div class="star" style="left: 89.2546%; top: 62.663%; animation-delay: 0.00142775s; animation-duration: 4.62659s;"></div><div class="star" style="left: 72.1282%; top: 37.3131%; animation-delay: 1.59056s; animation-duration: 4.4558s;"></div><div class="star" style="left: 91.5939%; top: 6.24803%; animation-delay: 0.523622s; animation-duration: 4.04785s;"></div><div class="star" style="left: 0.407806%; top: 70.5196%; animation-delay: 1.4842s; animation-duration: 3.9297s;"></div><div class="star" style="left: 22.0256%; top: 13.1855%; animation-delay: 1.77201s; animation-duration: 5.46967s;"></div><div class="star" style="left: 47.1931%; top: 17.5393%; animation-delay: 0.663944s; animation-duration: 3.10905s;"></div><div class="star" style="left: 12.0307%; top: 51.4497%; animation-delay: 1.85422s; animation-duration: 4.91885s;"></div><div class="star" style="left: 9.3848%; top: 97.85%; animation-delay: 0.425527s; animation-duration: 4.01741s;"></div><div class="star" style="left: 78.9073%; top: 85.3783%; animation-delay: 0.527254s; animation-duration: 5.28626s;"></div><div class="star" style="left: 51.4793%; top: 47.6366%; animation-delay: 2.47873s; animation-duration: 5.59896s;"></div><div class="star" style="left: 53.9552%; top: 1.47255%; animation-delay: 1.52531s; animation-duration: 3.54148s;"></div><div class="star" style="left: 42.0823%; top: 67.1816%; animation-delay: 1.95701s; animation-duration: 5.71382s;"></div><div class="star" style="left: 23.648%; top: 37.668%; animation-delay: 1.99915s; animation-duration: 3.64695s;"></div><div class="star" style="left: 47.045%; top: 71.4152%; animation-delay: 1.86891s; animation-duration: 3.81328s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 17.6223%; top: 58.6849%; animation-delay: 1.31631s; animation-duration: 4.28689s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 63.87%; top: 62.2121%; animation-delay: 2.73235s; animation-duration: 3.82701s;"></div><div class="star" style="left: 5.50317%; top: 62.8822%; animation-delay: 0.281071s; animation-duration: 5.75066s;"></div><div class="star" style="left: 41.5325%; top: 13.9277%; animation-delay: 0.295289s; animation-duration: 3.27505s;"></div><div class="star" style="left: 68.3922%; top: 72.6375%; animation-delay: 0.126797s; animation-duration: 4.46807s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 9.27024%; top: 0.537646%; animation-delay: 1.14717s; animation-duration: 3.53995s;"></div><div class="star" style="left: 85.821%; top: 79.2436%; animation-delay: 2.04231s; animation-duration: 5.38842s;"></div><div class="star" style="left: 68.5919%; top: 54.2132%; animation-delay: 1.23633s; animation-duration: 4.4355s;"></div><div class="star" style="left: 49.5252%; top: 51.28%; animation-delay: 2.21193s; animation-duration: 4.10807s;"></div><div class="star" style="left: 85.2397%; top: 75.4611%; animation-delay: 0.930545s; animation-duration: 4.24499s;"></div><div class="star" style="left: 32.762%; top: 50.0107%; animation-delay: 2.92653s; animation-duration: 5.53816s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 83.8032%; top: 12.7526%; animation-delay: 0.276625s; animation-duration: 3.51324s;"></div><div class="star" style="left: 89.1179%; top: 40.307%; animation-delay: 2.84517s; animation-duration: 3.69313s;"></div><div class="star" style="left: 11.0612%; top: 17.6716%; animation-delay: 1.05579s; animation-duration: 5.43666s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 34.8062%; top: 99.2964%; animation-delay: 1.2459s; animation-duration: 5.04589s;"></div><div class="star" style="left: 20.1119%; top: 58.9703%; animation-delay: 2.34849s; animation-duration: 3.81898s;"></div><div class="star" style="left: 23.8913%; top: 89.9794%; animation-delay: 1.55005s; animation-duration: 4.68237s;"></div><div class="star" style="left: 82.2532%; top: 27.6374%; animation-delay: 2.79689s; animation-duration: 5.42885s;"></div><div class="star" style="left: 4.17188%; top: 59.7813%; animation-delay: 0.598695s; animation-duration: 3.87555s;"></div><div class="star" style="left: 73.0797%; top: 97.7281%; animation-delay: 2.73178s; animation-duration: 3.69493s;"></div><div class="star" style="left: 92.3112%; top: 23.4479%; animation-delay: 0.468116s; animation-duration: 3.09451s;"></div><div class="star" style="left: 79.1396%; top: 25.3524%; animation-delay: 2.78611s; animation-duration: 4.40582s;"></div><div class="star" style="left: 50.6353%; top: 69.852%; animation-delay: 0.391526s; animation-duration: 5.32115s;"></div><div class="star" style="left: 58.1129%; top: 41.7566%; animation-delay: 0.834754s; animation-duration: 4.90869s;"></div><div class="star" style="left: 13.7892%; top: 4.12056%; animation-delay: 0.606506s; animation-duration: 4.78882s;"></div><div class="star" style="left: 49.793%; top: 62.2104%; animation-delay: 1.18842s; animation-duration: 5.36274s;"></div><div class="star" style="left: 65.5124%; top: 33.9543%; animation-delay: 1.85318s; animation-duration: 3.58607s;"></div><div class="star" style="left: 21.647%; top: 40.4374%; animation-delay: 1.28049s; animation-duration: 3.83175s;"></div><div class="star" style="left: 35.6012%; top: 28.6212%; animation-delay: 1.02512s; animation-duration: 3.1655s;"></div><div class="star" style="left: 21.3352%; top: 27.5033%; animation-delay: 1.06501s; animation-duration: 5.46883s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 95.6748%; top: 8.72285%; animation-delay: 0.125214s; animation-duration: 3.37267s;"></div><div class="star" style="left: 46.343%; top: 60.9828%; animation-delay: 1.51164s; animation-duration: 4.80699s;"></div><div class="star" style="left: 79.4839%; top: 99.3079%; animation-delay: 1.08089s; animation-duration: 4.98283s;"></div><div class="star" style="left: 35.2651%; top: 47.4666%; animation-delay: 0.875435s; animation-duration: 3.84708s;"></div><div class="star" style="left: 16.8779%; top: 15.1873%; animation-delay: 0.790697s; animation-duration: 4.31972s;"></div><div class="star" style="left: 14.1914%; top: 87.7558%; animation-delay: 1.90876s; animation-duration: 3.52387s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 33.9294%; top: 38.4062%; animation-delay: 2.94123s; animation-duration: 4.92416s;"></div><div class="star" style="left: 94.8837%; top: 37.1508%; animation-delay: 2.18436s; animation-duration: 4.69602s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 76.0975%; top: 6.30191%; animation-delay: 0.512813s; animation-duration: 4.74909s;"></div><div class="star" style="left: 88.2543%; top: 12.0577%; animation-delay: 2.56428s; animation-duration: 4.30948s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 21.2652%; top: 41.7851%; animation-delay: 0.476932s; animation-duration: 4.5393s;"></div><div class="star" style="left: 28.3196%; top: 14.3637%; animation-delay: 1.93085s; animation-duration: 3.20571s;"></div><div class="star" style="left: 40.0847%; top: 70.3059%; animation-delay: 0.431698s; animation-duration: 3.45194s;"></div><div class="star" style="left: 75.3193%; top: 69.3168%; animation-delay: 2.06523s; animation-duration: 3.74542s;"></div><div class="star" style="left: 86.8991%; top: 46.7478%; animation-delay: 1.32656s; animation-duration: 3.58626s;"></div><div class="star" style="left: 91.8895%; top: 43.1398%; animation-delay: 0.8817s; animation-duration: 3.49864s;"></div><div class="star" style="left: 90.9726%; top: 2.91432%; animation-delay: 2.22459s; animation-duration: 5.80183s;"></div><div class="star" style="left: 13.1342%; top: 12.134%; animation-delay: 2.2367s; animation-duration: 5.38345s;"></div><div class="star" style="left: 3.11419%; top: 75.4945%; animation-delay: 2.57685s; animation-duration: 4.37816s;"></div><div class="star" style="left: 6.86996%; top: 79.9541%; animation-delay: 1.12519s; animation-duration: 5.25152s;"></div><div class="star" style="left: 12.4478%; top: 0.855286%; animation-delay: 1.97821s; animation-duration: 5.89485s;"></div><div class="star" style="left: 5.29291%; top: 58.4448%; animation-delay: 0.990322s; animation-duration: 5.96468s;"></div><div class="star" style="left: 54.9691%; top: 11.7179%; animation-delay: 0.802586s; animation-duration: 4.84128s;"></div><div class="star" style="left: 66.4837%; top: 73.0044%; animation-delay: 2.17604s; animation-duration: 3.4126s;"></div><div class="star" style="left: 0.616564%; top: 13.3248%; animation-delay: 0.600029s; animation-duration: 3.79911s;"></div><div class="star" style="left: 45.1488%; top: 22.6666%; animation-delay: 1.15661s; animation-duration: 5.49048s;"></div><div class="star" style="left: 88.3864%; top: 36.1686%; animation-delay: 2.87871s; animation-duration: 5.54109s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 19.6254%; top: 88.5492%; animation-delay: 1.31028s; animation-duration: 5.01502s;"></div><div class="star" style="left: 17.1126%; top: 33.1327%; animation-delay: 0.392203s; animation-duration: 4.21367s;"></div><div class="star" style="left: 64.3195%; top: 44.5218%; animation-delay: 0.531755s; animation-duration: 4.87229s;"></div><div class="star" style="left: 42.8161%; top: 72.3638%; animation-delay: 1.7438s; animation-duration: 3.6199s;"></div><div class="star" style="left: 58.981%; top: 19.8377%; animation-delay: 0.855968s; animation-duration: 3.79295s;"></div><div class="star" style="left: 52.0519%; top: 43.247%; animation-delay: 1.75475s; animation-duration: 3.72767s;"></div><div class="star" style="left: 8.70753%; top: 63.5536%; animation-delay: 2.37585s; animation-duration: 5.48028s;"></div><div class="star" style="left: 37.3665%; top: 7.90775%; animation-delay: 1.71903s; animation-duration: 3.86583s;"></div><div class="star" style="left: 65.1936%; top: 72.259%; animation-delay: 1.45396s; animation-duration: 3.74984s;"></div><div class="star" style="left: 45.0927%; top: 1.02976%; animation-delay: 1.86975s; animation-duration: 4.64535s;"></div><div class="star" style="left: 77.4897%; top: 19.3201%; animation-delay: 0.153257s; animation-duration: 5.49378s;"></div><div class="star" style="left: 82.4376%; top: 82.155%; animation-delay: 1.95132s; animation-duration: 3.72905s;"></div><div class="star" style="left: 70.2151%; top: 14.3265%; animation-delay: 2.84153s; animation-duration: 3.28321s;"></div><div class="star" style="left: 93.2375%; top: 22.303%; animation-delay: 1.44677s; animation-duration: 4.71141s;"></div><div class="star" style="left: 55.6293%; top: 30.6644%; animation-delay: 0.0382243s; animation-duration: 3.17868s;"></div><div class="star" style="left: 86.8116%; top: 65.8881%; animation-delay: 0.484425s; animation-duration: 5.12437s;"></div><div class="star" style="left: 20.6015%; top: 0.502774%; animation-delay: 1.98072s; animation-duration: 5.39587s;"></div><div class="star" style="left: 89.4885%; top: 10.3966%; animation-delay: 2.63089s; animation-duration: 3.9716s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 2.31752%; top: 62.2955%; animation-delay: 2.05249s; animation-duration: 3.5115s;"></div><div class="star" style="left: 29.1974%; top: 79.0683%; animation-delay: 2.00723s; animation-duration: 3.26886s;"></div><div class="star" style="left: 73.9412%; top: 12.7997%; animation-delay: 2.46222s; animation-duration: 4.899s;"></div><div class="star" style="left: 88.1336%; top: 97.1321%; animation-delay: 1.90151s; animation-duration: 5.54394s;"></div><div class="star" style="left: 77.4504%; top: 91.4513%; animation-delay: 2.89617s; animation-duration: 5.99805s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 23.9366%; top: 10.0421%; animation-delay: 2.75343s; animation-duration: 3.04752s;"></div><div class="star" style="left: 35.5442%; top: 86.4556%; animation-delay: 1.50594s; animation-duration: 4.02916s;"></div><div class="star" style="left: 93.6262%; top: 50.453%; animation-delay: 1.5921s; animation-duration: 3.2334s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 58.6652%; top: 6.66747%; animation-delay: 0.563004s; animation-duration: 3.31482s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 72.5208%; top: 94.5901%; animation-delay: 0.164109s; animation-duration: 5.08712s;"></div><div class="star" style="left: 60.4497%; top: 58.5438%; animation-delay: 0.75473s; animation-duration: 5.27772s;"></div><div class="star" style="left: 82.2891%; top: 38.4331%; animation-delay: 2.99295s; animation-duration: 4.22436s;"></div><div class="star" style="left: 94.8662%; top: 73.4144%; animation-delay: 2.01253s; animation-duration: 5.35793s;"></div><div class="star" style="left: 23.2627%; top: 87.2616%; animation-delay: 1.98534s; animation-duration: 3.5254s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 32.8757%; top: 5.13578%; animation-delay: 1.65178s; animation-duration: 4.10655s;"></div><div class="star" style="left: 76.3177%; top: 73.2332%; animation-delay: 1.76434s; animation-duration: 5.93867s;"></div><div class="star" style="left: 88.5409%; top: 77.6761%; animation-delay: 0.544725s; animation-duration: 5.10472s;"></div><div class="star" style="left: 16.531%; top: 61.5684%; animation-delay: 2.48444s; animation-duration: 5.62934s;"></div><div class="star" style="left: 50.6037%; top: 61.1221%; animation-delay: 1.68637s; animation-duration: 5.70509s;"></div><div class="star" style="left: 3.47644%; top: 23.7109%; animation-delay: 0.125493s; animation-duration: 5.19413s;"></div><div class="star" style="left: 34.2117%; top: 1.83475%; animation-delay: 1.19617s; animation-duration: 5.61753s;"></div><div class="star" style="left: 58.1486%; top: 50.6092%; animation-delay: 1.90841s; animation-duration: 5.83185s;"></div><div class="star" style="left: 42.6245%; top: 1.45079%; animation-delay: 1.78569s; animation-duration: 5.6682s;"></div><div class="star" style="left: 58.0183%; top: 11.1322%; animation-delay: 0.810734s; animation-duration: 4.34805s;"></div><div class="star" style="left: 61.5361%; top: 46.3761%; animation-delay: 1.28176s; animation-duration: 5.18209s;"></div><div class="star" style="left: 25.1609%; top: 33.867%; animation-delay: 0.179979s; animation-duration: 4.57712s;"></div><div class="star" style="left: 81.2445%; top: 69.54%; animation-delay: 2.79785s; animation-duration: 4.06543s;"></div><div class="star" style="left: 63.601%; top: 92.7302%; animation-delay: 2.18664s; animation-duration: 3.73136s;"></div><div class="star" style="left: 90.1426%; top: 39.3069%; animation-delay: 2.64025s; animation-duration: 5.6058s;"></div><div class="star" style="left: 36.7371%; top: 8.82202%; animation-delay: 1.8689s; animation-duration: 4.72307s;"></div><div class="star" style="left: 28.6792%; top: 46.111%; animation-delay: 2.85299s; animation-duration: 4.07238s; width: 3px; height: 3px; box-shadow: rgba(255, 255, 255, 0.8) 0px 0px 10px;"></div><div class="star" style="left: 16.906%; top: 44.8469%; animation-delay: 0.882448s; animation-duration: 3.88966s;"></div><div class="star" style="left: 37.0172%; top: 71.654%; animation-delay: 2.64595s; animation-duration: 4.7863s;"></div></div>
        
        <!-- 궤도 효과 -->
        <div class="orbital-lines">
            <div class="orbit-ring"></div>
            <div class="orbit-ring"></div>
            <div class="orbit-ring"></div>
        </div>
        
        <!-- 중앙 코어 -->
        <div class="core-container" style="transform: translate(-50%, -50%) perspective(1000px) rotateX(-7.64962deg) rotateY(1.30208deg);">
            <div class="core" id="core">
                <span class="core-icon">🎯</span>
                <span class="core-text">KAIST<br>터치수학</span>
            </div>
        </div>
        
        <!-- 기능 휠 -->
        <div class="features-wheel" id="featuresWheel">
            <!-- 연결선 -->
            <div class="connection-lines" id="connectionLines"><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(0deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(21.2deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(42.4deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(63.6deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(84.8deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(106deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(127.2deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(148.4deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(169.6deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(190.8deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(212deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(233.2deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(254.4deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(275.6deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(296.8deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(318deg);"></div><div class="connection" style="width: 300px; left: 350px; top: 350px; transform: rotate(339.2deg);"></div></div>
            
            <!-- 17개 기능 노드 -->
            <div class="feature-node" style="animation-delay: 0s;">
                <div class="feature-orb" data-feature="guide" data-name="학습관리 페이지 구조 안내" data-desc="처음 방문하셨나요? 시스템의 전체 구조를 쉽게 이해할 수 있도록 단계별 가이드 투어를 제공합니다">
                    <span class="feature-icon">🧭</span>
                    <span class="feature-label">페이지 안내</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -11.76s;">
                <div class="feature-orb" data-feature="study-room" data-name="내공부방" data-desc="자녀의 개인 학습 공간에서 현재 진행 상황과 학습 통계를 한눈에 확인하세요">
                    <span class="feature-icon">📚</span>
                    <span class="feature-label">내공부방</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -23.53s;">
                <div class="feature-orb" data-feature="results" data-name="공부결과" data-desc="성적 변화를 그래프로 시각화하여 학습 성과를 직관적으로 파악할 수 있습니다">
                    <span class="feature-icon">📊</span>
                    <span class="feature-label">공부결과</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -35.29s;">
                <div class="feature-orb" data-feature="goals" data-name="목표설정" data-desc="자녀와 함께 구체적인 학습 목표를 설정하고 달성 과정을 추적하세요">
                    <span class="feature-icon">🎯</span>
                    <span class="feature-label">목표설정</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -47.06s;">
                <div class="feature-orb" data-feature="pomodoro" data-name="수학일기 (포모도로)" data-desc="25분 집중 학습 기법으로 효율적인 학습 습관을 만들어갑니다">
                    <span class="feature-icon">⏱️</span>
                    <span class="feature-label">수학일기</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -58.82s;">
                <div class="feature-orb" data-feature="parent-app" data-name="KTM 학부모 앱" data-desc="언제 어디서나 자녀의 학습 현황을 확인하고 선생님과 소통하세요">
                    <span class="feature-icon">📱</span>
                    <span class="feature-label">학부모 앱</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -70.59s;">
                <div class="feature-orb" data-feature="notes" data-name="기억노트" data-desc="중요한 개념과 틀린 문제를 체계적으로 관리하여 효과적인 복습이 가능합니다">
                    <span class="feature-icon">📝</span>
                    <span class="feature-label">기억노트</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -82.35s;">
                <div class="feature-orb" data-feature="anki" data-name="안키퀴즈" data-desc="과학적인 반복 학습 시스템으로 장기 기억력을 향상시킵니다">
                    <span class="feature-icon">🎴</span>
                    <span class="feature-label">안키퀴즈</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -94.12s;">
                <div class="feature-orb" data-feature="lectures" data-name="수학특강" data-desc="다양한 주제의 심화 강좌로 수학 실력을 한 단계 높여보세요">
                    <span class="feature-icon">🎓</span>
                    <span class="feature-label">수학특강</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -105.88s;">
                <div class="feature-orb" data-feature="search" data-name="개념검색" data-desc="모르는 수학 개념을 즉시 검색하고 이해할 수 있습니다">
                    <span class="feature-icon">🔍</span>
                    <span class="feature-label">개념검색</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -117.65s;">
                <div class="feature-orb" data-feature="quarterly" data-name="분기목표" data-desc="3개월 단위로 목표를 설정하고 체계적으로 관리합니다">
                    <span class="feature-icon">📈</span>
                    <span class="feature-label">분기목표</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -129.41s;">
                <div class="feature-orb" data-feature="mindset" data-name="성장마인드" data-desc="실패를 성장의 기회로 만드는 마인드셋을 함양합니다">
                    <span class="feature-icon">🌱</span>
                    <span class="feature-label">성장마인드</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -141.18s;">
                <div class="feature-orb" data-feature="challenge" data-name="오늘의 도전" data-desc="매일 새로운 문제에 도전하며 실력을 키워갑니다">
                    <span class="feature-icon">🏆</span>
                    <span class="feature-label">오늘의 도전</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -152.94s;">
                <div class="feature-orb" data-feature="schedule" data-name="시간표" data-desc="주간 학습 일정을 체계적으로 관리하고 계획합니다">
                    <span class="feature-icon">📅</span>
                    <span class="feature-label">시간표</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -164.71s;">
                <div class="feature-orb" data-feature="content" data-name="컨텐츠 페이지" data-desc="풍부한 학습 자료와 콘텐츠를 탐색하고 활용하세요">
                    <span class="feature-icon">📖</span>
                    <span class="feature-label">컨텐츠</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -176.47s;">
                <div class="feature-orb" data-feature="persona" data-name="페르소나 카드 시스템" data-desc="학습 성향을 캐릭터로 시각화하여 재미있게 성장합니다">
                    <span class="feature-icon">🎭</span>
                    <span class="feature-label">페르소나</span>
                </div>
            </div>
            
            <div class="feature-node" style="animation-delay: -188.24s;">
                <div class="feature-orb" data-feature="teacher" data-name="선생님 환경" data-desc="교사가 학생을 어떻게 관리하고 지도하는지 확인해보세요">
                    <span class="feature-icon">👩‍🏫</span>
                    <span class="feature-label">선생님 환경</span>
                </div>
            </div>
        </div>
        
        <!-- 정보 패널 -->
        <div class="info-panel" id="infoPanel">
            <h3 class="info-title" id="infoTitle">성장마인드</h3>
            <p class="info-description" id="infoDesc">실패를 성장의 기회로 만드는 마인드셋을 함양합니다</p>
        </div>
        
        <!-- 토스트 알림 -->
        <div class="toast" id="toast">클릭하여 자세히 알아보세요</div>
    </div>
    
    <script>
        // 별 생성
        function createStars() {
            const starsContainer = document.getElementById('stars');
            const starCount = 200;
            
            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 3 + 's';
                star.style.animationDuration = (3 + Math.random() * 3) + 's';
                
                // 일부 별은 더 크고 밝게
                if (Math.random() > 0.8) {
                    star.style.width = '3px';
                    star.style.height = '3px';
                    star.style.boxShadow = '0 0 10px rgba(255, 255, 255, 0.8)';
                }
                
                starsContainer.appendChild(star);
            }
        }
        
        // 파티클 생성
        function createParticle(x, y) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = x + 'px';
            particle.style.top = y + 'px';
            
            const angle = Math.random() * Math.PI * 2;
            const distance = 50 + Math.random() * 100;
            const tx = Math.cos(angle) * distance;
            const ty = Math.sin(angle) * distance;
            
            particle.style.setProperty('--tx', tx + 'px');
            particle.style.setProperty('--ty', ty + 'px');
            particle.style.animation = 'particle-float 1s ease-out';
            
            document.querySelector('.universe-container').appendChild(particle);
            
            setTimeout(() => particle.remove(), 1000);
        }
        
        // 연결선 생성
        function createConnectionLines() {
            const container = document.getElementById('connectionLines');
            const centerX = 350;
            const centerY = 350;
            
            for (let i = 0; i < 17; i++) {
                const line = document.createElement('div');
                line.className = 'connection';
                const angle = (i * 21.2) * Math.PI / 180;
                const length = 300;
                
                line.style.width = length + 'px';
                line.style.left = centerX + 'px';
                line.style.top = centerY + 'px';
                line.style.transform = `rotate(${i * 21.2}deg)`;
                
                container.appendChild(line);
            }
        }
        
        // 정보 패널 표시
        function showInfo(name, desc) {
            const panel = document.getElementById('infoPanel');
            const title = document.getElementById('infoTitle');
            const description = document.getElementById('infoDesc');
            
            title.textContent = name;
            description.textContent = desc;
            panel.classList.add('show');
        }
        
        function hideInfo() {
            document.getElementById('infoPanel').classList.remove('show');
        }
        
        // 토스트 알림
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // 이벤트 리스너
        document.querySelectorAll('.feature-orb').forEach(orb => {
            orb.addEventListener('mouseenter', function(e) {
                const name = this.getAttribute('data-name');
                const desc = this.getAttribute('data-desc');
                showInfo(name, desc);
                
                // 파티클 효과
                const rect = this.getBoundingClientRect();
                createParticle(
                    rect.left + rect.width / 2,
                    rect.top + rect.height / 2
                );
            });
            
            orb.addEventListener('mouseleave', function() {
                hideInfo();
            });
            
            orb.addEventListener('click', function() {
                const feature = this.getAttribute('data-feature');
                const name = this.getAttribute('data-name');
                showToast(`${name} 기능을 선택했습니다`);
                
                // 클릭 파티클 효과
                const rect = this.getBoundingClientRect();
                for (let i = 0; i < 10; i++) {
                    setTimeout(() => {
                        createParticle(
                            rect.left + rect.width / 2 + (Math.random() - 0.5) * 20,
                            rect.top + rect.height / 2 + (Math.random() - 0.5) * 20
                        );
                    }, i * 50);
                }
            });
        });
        
        // 중앙 코어 클릭
        document.getElementById('core').addEventListener('click', function() {
            showToast('17가지 혁신 기능을 탐험해보세요!');
            
            // 모든 기능 강조 애니메이션
            const orbs = document.querySelectorAll('.feature-orb');
            orbs.forEach((orb, index) => {
                setTimeout(() => {
                    orb.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        orb.style.transform = 'scale(1)';
                    }, 300);
                }, index * 50);
            });
        });
        
        // 마우스 이동 효과
        document.addEventListener('mousemove', (e) => {
            const core = document.querySelector('.core-container');
            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;
            
            core.style.transform = `translate(-50%, -50%) perspective(1000px) rotateX(${y}deg) rotateY(${x}deg)`;
        });
        
        // 초기화
        createStars();
        createConnectionLines();
        
        // 휠 회전 일시정지/재생
        let isPaused = false;
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                const nodes = document.querySelectorAll('.feature-node');
                if (isPaused) {
                    nodes.forEach(node => {
                        node.style.animationPlayState = 'running';
                    });
                } else {
                    nodes.forEach(node => {
                        node.style.animationPlayState = 'paused';
                    });
                }
                isPaused = !isPaused;
                showToast(isPaused ? '회전 일시정지' : '회전 재개');
            }
        });
    </script>

</body></html>