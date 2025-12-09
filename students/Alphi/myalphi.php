<?php 
header('Content-Type: text/html; charset=utf-8');
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

require_login();

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphi 성장시키기</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #1a1a2e;
        }
        .svg-container {
            width: 100%;
            max-width: 1000px;
            margin: auto;
        }
        svg {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="svg-container">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900">
          <!-- Definitions -->
          <defs>
            <linearGradient id="bg-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#1a1a2e" />
              <stop offset="100%" stop-color="#16213e" />
            </linearGradient>
            <filter id="glow" height="300%" width="300%" x="-75%" y="-75%">
              <feGaussianBlur stdDeviation="3" result="glow"/>
              <feMerge>
                <feMergeNode in="glow"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>
            <linearGradient id="connection-line" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#4cc9f0" />
              <stop offset="100%" stop-color="#e94560" />
            </linearGradient>
            
            <!-- Pulsing animation for post-graduation icon -->
            <radialGradient id="pulse-gradient" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
              <stop offset="0%" stop-color="#e94560" stop-opacity="0.7">
                <animate attributeName="stop-opacity" values="0.7;0.3;0.7" dur="3s" repeatCount="indefinite" />
              </stop>
              <stop offset="100%" stop-color="#e94560" stop-opacity="0">
                <animate attributeName="stop-opacity" values="0;0.3;0" dur="3s" repeatCount="indefinite" />
              </stop>
            </radialGradient>
            
            <!-- Moving dot along the path -->
            <circle id="moving-dot" r="6" fill="#ffffff" filter="url(#glow)">
              <animate attributeName="opacity" values="0;1;0" dur="5s" repeatCount="indefinite" />
            </circle>
          </defs>
          
          <!-- Main background -->
          <rect width="1000" height="600" fill="url(#bg-gradient)" />
          
          <!-- Mobile App Design - Outside the main frame -->
          <g transform="translate(500, 650)">
            <!-- Phone Frame with improved design -->
            <rect x="-60" y="0" width="120" height="240" rx="20" fill="#1a1a2e" stroke="#4cc9f0" stroke-width="3">
              <animate attributeName="stroke-opacity" values="0.5;1;0.5" dur="3s" repeatCount="indefinite" />
            </rect>
            
            <!-- Screen Content with gradient -->
            <defs>
              <linearGradient id="screen-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#0f3460" />
                <stop offset="100%" stop-color="#16213e" />
              </linearGradient>
            </defs>
            <rect x="-55" y="5" width="110" height="230" rx="15" fill="url(#screen-gradient)" />
            
            <!-- App Icon with improved design -->
            <circle cx="0" cy="40" r="30" fill="#e94560" filter="url(#glow)">
              <animate attributeName="r" values="30;32;30" dur="4s" repeatCount="indefinite" />
            </circle>
            <text x="0" y="48" font-family="Arial, sans-serif" font-size="24" fill="#fff" text-anchor="middle" font-weight="bold">A</text>
            
            <!-- App Title with improved typography -->
            <text x="0" y="100" font-family="Arial, sans-serif" font-size="18" fill="#fff" text-anchor="middle" font-weight="bold">Alphi</text>
            <text x="0" y="120" font-family="Arial, sans-serif" font-size="12" fill="#4cc9f0" text-anchor="middle">수학 학습 파트너</text>
            
            <!-- Download Button with improved design -->
            <rect x="-40" y="180" width="80" height="35" rx="17" fill="#4cc9f0">
              <animate attributeName="opacity" values="0.7;1;0.7" dur="3s" repeatCount="indefinite" />
            </rect>
            <text x="0" y="202" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle" font-weight="bold">다운로드</text>
          </g>
          
          <!-- Title -->
          <text x="500" y="60" font-family="Arial, sans-serif" font-size="36" fill="#e94560" text-anchor="middle" font-weight="bold">Alphi 성장시키기</text>
          <text x="500" y="100" font-family="Arial, sans-serif" font-size="24" fill="#fff" text-anchor="middle">나만의 수학 반려로봇 키우기</text>
          
          <!-- Timeline path -->
          <path d="M100,300 C250,200 350,400 500,300 C650,200 750,400 900,300" stroke="url(#connection-line)" stroke-width="5" fill="none" stroke-dasharray="10,5" />
          
          <!-- Moving dot animation along the path -->
          <use href="#moving-dot">
            <animateMotion dur="10s" repeatCount="indefinite" rotate="auto">
              <mpath href="#timeline-path"/>
            </animateMotion>
          </use>
          <path id="timeline-path" d="M100,300 C250,200 350,400 500,300 C650,200 750,400 900,300" stroke="none" fill="none" />
          
          <!-- Stage 1: Elementary School -->
          <circle cx="100" cy="300" r="80" fill="#0f3460" stroke="#4cc9f0" stroke-width="3">
            <animate attributeName="r" values="80;82;80" dur="5s" repeatCount="indefinite" />
          </circle>
          <text x="100" y="240" font-family="Arial, sans-serif" font-size="18" fill="#fff" text-anchor="middle">초등학교</text>
          
          <!-- Basic Alphi face - simple, cute -->
          <circle cx="100" cy="290" r="20" fill="#4cc9f0" filter="url(#glow)" />
          <circle cx="100" cy="290" r="15" fill="#0f3460" />
          
          <!-- Blinking eyes animation for elementary Alphi -->
          <circle cx="85" cy="280" r="8" fill="#4cc9f0" filter="url(#glow)">
            <animate attributeName="ry" values="8;1;8" dur="5s" begin="2s" repeatCount="indefinite" />
          </circle>
          <circle cx="115" cy="280" r="8" fill="#4cc9f0" filter="url(#glow)">
            <animate attributeName="ry" values="8;1;8" dur="5s" begin="2s" repeatCount="indefinite" />
          </circle>
          
          <!-- Mouth with smile animation -->
          <path d="M85,305 Q100,315 115,305" stroke="#4cc9f0" stroke-width="3" fill="none">
            <animate attributeName="d" values="M85,305 Q100,315 115,305;M85,305 Q100,320 115,305;M85,305 Q100,315 115,305" dur="7s" repeatCount="indefinite" />
          </path>
          
          <!-- Elementary skills -->
          <text x="100" y="340" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">기초 연산</text>
          <text x="100" y="360" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">도형의 기초</text>
          
          <!-- Stage 2: Middle School -->
          <circle cx="500" cy="300" r="80" fill="#0f3460" stroke="#4cc9f0" stroke-width="3">
            <animate attributeName="r" values="80;83;80" dur="6s" begin="1s" repeatCount="indefinite" />
          </circle>
          <text x="500" y="240" font-family="Arial, sans-serif" font-size="18" fill="#fff" text-anchor="middle">중학교</text>
          
          <!-- Evolved Alphi face - more detailed -->
          <circle cx="500" cy="290" r="25" fill="#4cc9f0" filter="url(#glow)" opacity="0.8">
            <animate attributeName="opacity" values="0.8;0.9;0.8" dur="4s" repeatCount="indefinite" />
          </circle>
          <circle cx="500" cy="290" r="20" fill="#0f3460" />
          <ellipse cx="485" cy="280" rx="10" ry="8" fill="#4cc9f0" filter="url(#glow)" />
          <ellipse cx="515" cy="280" rx="10" ry="8" fill="#4cc9f0" filter="url(#glow)" />
          
          <!-- Moving eyeballs for middle school Alphi -->
          <circle cx="485" cy="280" r="3" fill="#0f3460">
            <animate attributeName="cx" values="485;487;485;483;485" dur="8s" repeatCount="indefinite" />
          </circle>
          <circle cx="515" cy="280" r="3" fill="#0f3460">
            <animate attributeName="cx" values="515;517;515;513;515" dur="8s" repeatCount="indefinite" />
          </circle>
          
          <path d="M480,305 Q500,320 520,305" stroke="#4cc9f0" stroke-width="3" fill="none">
            <animate attributeName="d" values="M480,305 Q500,320 520,305;M480,305 Q500,325 520,305;M480,305 Q500,320 520,305" dur="6s" repeatCount="indefinite" />
          </path>
          
          <!-- Middle school skills -->
          <text x="500" y="340" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">대수학 기초</text>
          <text x="500" y="360" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">기하학 & 통계</text>
          
          <!-- Stage 3: High School -->
          <circle cx="900" cy="300" r="80" fill="#0f3460" stroke="#e94560" stroke-width="3">
            <animate attributeName="r" values="80;84;80" dur="7s" begin="2s" repeatCount="indefinite" />
          </circle>
          <text x="900" y="240" font-family="Arial, sans-serif" font-size="18" fill="#fff" text-anchor="middle">고등학교</text>
          
          <!-- Advanced Alphi - complex, personalized -->
          <circle cx="900" cy="290" r="30" fill="#e94560" filter="url(#glow)" opacity="0.7">
            <animate attributeName="opacity" values="0.7;0.9;0.7" dur="5s" repeatCount="indefinite" />
          </circle>
          <circle cx="900" cy="290" r="25" fill="#0f3460" />
          
          <!-- High school Alphi eyes with complex movement -->
          <ellipse cx="880" cy="280" rx="12" ry="10" fill="#4cc9f0" filter="url(#glow)" />
          <ellipse cx="920" cy="280" rx="12" ry="10" fill="#4cc9f0" filter="url(#glow)" />
          
          <circle cx="880" cy="280" r="4" fill="#0f3460">
            <animate attributeName="cx" values="880;883;880;878;880" dur="9s" repeatCount="indefinite" />
            <animate attributeName="cy" values="280;282;280;279;280" dur="7s" repeatCount="indefinite" />
          </circle>
          <circle cx="920" cy="280" r="4" fill="#0f3460">
            <animate attributeName="cx" values="920;923;920;918;920" dur="9s" repeatCount="indefinite" />
            <animate attributeName="cy" values="280;282;280;279;280" dur="7s" repeatCount="indefinite" />
          </circle>
          
          <path d="M875,305 Q900,325 925,305" stroke="#4cc9f0" stroke-width="4" fill="none">
            <animate attributeName="d" values="M875,305 Q900,325 925,305;M875,305 Q900,330 925,305;M875,305 Q900,325 925,305" dur="5s" repeatCount="indefinite" />
          </path>
          
          <!-- Digital circuits pattern in background with pulsing animation -->
          <path d="M870,290 L880,290 L880,300 L890,300 L890,280 L900,280" stroke="#e94560" stroke-width="1" fill="none">
            <animate attributeName="stroke-width" values="1;2;1" dur="4s" repeatCount="indefinite" />
          </path>
          <path d="M910,290 L920,290 L920,300 L930,300 L930,280 L940,280" stroke="#e94560" stroke-width="1" fill="none">
            <animate attributeName="stroke-width" values="1;2;1" dur="4s" begin="1s" repeatCount="indefinite" />
          </path>
          
          <!-- High school skills -->
          <text x="900" y="340" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">고급 수학</text>
          <text x="900" y="360" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">미적분 & 삼각함수</text>
          
          <!-- Memory accumulation visualization -->
          <text x="500" y="430" font-family="Arial, sans-serif" font-size="24" fill="#e94560" text-anchor="middle">기억 성장</text>
          
          <!-- Memory bars container -->
          <rect x="200" y="470" width="600" height="25" rx="12" fill="#1a1a2e" stroke="#4cc9f0" stroke-width="1" />
          
          <!-- Elementary memories (30%) with filling animation -->
          <rect x="200" y="470" width="0" height="25" rx="12" fill="#4cc9f0" opacity="0.6">
            <animate attributeName="width" from="0" to="180" dur="3s" fill="freeze" />
          </rect>
          <text x="290" y="487" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">초등 학습 기억</text>
          
          <!-- Middle school memories (30%) with filling animation -->
          <rect x="380" y="470" width="0" height="25" rx="0" fill="#4cc9f0" opacity="0.8">
            <animate attributeName="width" from="0" to="180" dur="3s" begin="1s" fill="freeze" />
          </rect>
          <text x="470" y="487" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">중학교 학습 기억</text>
          
          <!-- High school memories (40%) with filling animation -->
          <rect x="560" y="470" width="0" height="25" rx="0" fill="#e94560" opacity="0.9">
            <animate attributeName="width" from="0" to="240" dur="3s" begin="2s" fill="freeze" />
          </rect>
          <text x="680" y="487" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">고등학교 학습 기억</text>
          
          <!-- Growth info boxes with fade-in effect -->
          
          <!-- Box 1: Mirror Neuron Learning -->
          <rect x="125" y="500" width="220" height="70" rx="10" fill="rgba(15, 52, 96, 0.7)" stroke="#4cc9f0" stroke-width="2">
            <animate attributeName="opacity" from="0" to="1" dur="1s" fill="freeze" />
          </rect>
          <text x="235" y="525" font-family="Arial, sans-serif" font-size="16" fill="#4cc9f0" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="0.5s" fill="freeze" />
            거울뉴런 학습
          </text>
          <text x="235" y="550" font-family="Arial, sans-serif" font-size="12" fill="#fff" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="0.7s" fill="freeze" />
            당신의 문제 해결 방식을
          </text>
          <text x="235" y="565" font-family="Arial, sans-serif" font-size="12" fill="#fff" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="0.9s" fill="freeze" />
            관찰하며 학습합니다
          </text>
          
          <!-- Box 2: Emotional Memory -->
          <rect x="390" y="500" width="220" height="70" rx="10" fill="rgba(15, 52, 96, 0.7)" stroke="#4cc9f0" stroke-width="2">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="1s" fill="freeze" />
          </rect>
          <text x="500" y="525" font-family="Arial, sans-serif" font-size="16" fill="#4cc9f0" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="1.5s" fill="freeze" />
            감정 기억
          </text>
          <text x="500" y="550" font-family="Arial, sans-serif" font-size="12" fill="#fff" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="1.7s" fill="freeze" />
            학습 과정에서 어떤 것이 당신을
          </text>
          <text x="500" y="565" font-family="Arial, sans-serif" font-size="12" fill="#fff" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="1.9s" fill="freeze" />
            좌절시키거나 동기부여했는지 기억
          </text>
          
          <!-- Box 3: 2nd Brain -->
          <rect x="655" y="500" width="220" height="70" rx="10" fill="rgba(15, 52, 96, 0.7)" stroke="#e94560" stroke-width="2">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="2s" fill="freeze" />
          </rect>
          <text x="765" y="525" font-family="Arial, sans-serif" font-size="16" fill="#e94560" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="2.5s" fill="freeze" />
            보조지능 반려AI 동작
          </text>
          <text x="765" y="550" font-family="Arial, sans-serif" font-size="12" fill="#fff" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="2.7s" fill="freeze" />
            졸업 후: 당신만의 개인화된
          </text>
          <text x="765" y="565" font-family="Arial, sans-serif" font-size="12" fill="#fff" text-anchor="middle">
            <animate attributeName="opacity" from="0" to="1" dur="1s" begin="2.9s" fill="freeze" />
            평생 학습 파트너가 됩니다
          </text>
          
          <!-- Post-graduation small icon with pulsing glow -->
          <circle cx="900" cy="160" r="45" fill="url(#pulse-gradient)" stroke="none" />
          <circle cx="900" cy="160" r="40" fill="#0f3460" stroke="#e94560" stroke-width="2">
            <animate attributeName="r" values="40;42;40" dur="4s" repeatCount="indefinite" />
          </circle>
          <text x="900" y="145" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">졸업 후</text>
          <text x="900" y="165" font-family="Arial, sans-serif" font-size="14" fill="#fff" text-anchor="middle">평생 반려자</text>
          
          <!-- Post-graduation Alphi - most advanced with complex animation -->
          <circle cx="900" cy="180" r="15" fill="#e94560" filter="url(#glow)">
            <animate attributeName="opacity" values="1;0.8;1" dur="3s" repeatCount="indefinite" />
          </circle>
          
          <!-- Advanced blinking eyes -->
          <ellipse cx="890" cy="175" rx="5" ry="4" fill="#4cc9f0" filter="url(#glow)">
            <animate attributeName="ry" values="4;1;4" dur="7s" repeatCount="indefinite" />
          </ellipse>
          <ellipse cx="910" cy="175" rx="5" ry="4" fill="#4cc9f0" filter="url(#glow)">
            <animate attributeName="ry" values="4;1;4" dur="7s" repeatCount="indefinite" />
          </ellipse>
          
          <!-- Animating smile -->
          <path d="M890,185 Q900,190 910,185" stroke="#4cc9f0" stroke-width="2" fill="none">
            <animate attributeName="d" values="M890,185 Q900,190 910,185;M890,185 Q900,195 910,185;M890,185 Q900,190 910,185" dur="5s" repeatCount="indefinite" />
          </path>
        </svg>
    </div>
</body>
</html>