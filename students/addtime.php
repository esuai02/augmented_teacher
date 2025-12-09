<?php
/**
 * 퀴즈 시간 추가 팝업
 *
 * 사용법: addtime.php?attempt={attemptid}
 * 예시: addtime.php?attempt=1042472
 *
 * @file addtime.php
 * @path /local/augmented_teacher/students/addtime.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// URL에서 attemptid 가져오기
$attemptid = required_param('attempt', PARAM_INT);

// 퀴즈 시도 정보 확인
$attemptinfo = $DB->get_record('quiz_attempts', array('id' => $attemptid));
if (!$attemptinfo) {
    die("Error: Invalid attempt ID - $attemptid (File: " . __FILE__ . ", Line: " . __LINE__ . ")");
}

// 현재 퀴즈 정보
$quiz = $DB->get_record('quiz', array('id' => $attemptinfo->quiz));
$quizname = $quiz ? $quiz->name : 'Unknown Quiz';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>퀴즈 시간 추가</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            padding: 15px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        .time-btn {
            padding: 12px 8px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .time-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .time-btn:active {
            transform: translateY(0);
        }
        .btn-minus { background: #dc3545; color: white; }
        .btn-plus { background: #28a745; color: white; }
        .input-row {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        .custom-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
        }
        .custom-input:focus {
            outline: none;
            border-color: #007bff;
        }
        .submit-btn {
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
        .submit-btn:hover { background: #0056b3; }
        .cancel-btn {
            width: 100%;
            padding: 10px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .cancel-btn:hover { background: #545b62; }
        .status {
            text-align: center;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            display: none;
        }
        .status.success { display: block; background: #d4edda; color: #155724; }
        .status.error { display: block; background: #f8d7da; color: #721c24; }
        
        /* 피드백 애니메이션 스타일 */
        .feedback-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            animation: fadeIn 0.4s ease-in forwards;
            backdrop-filter: blur(4px);
        }
        .feedback-box {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 35px 45px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.1);
            max-width: 550px;
            text-align: center;
            transform: scale(0.7) translateY(30px) rotate(-2deg);
            animation: slideUpBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .feedback-text {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.7;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
            letter-spacing: -0.3px;
        }
        @keyframes fadeIn {
            from { 
                opacity: 0;
                backdrop-filter: blur(0px);
            }
            to { 
                opacity: 1;
                backdrop-filter: blur(4px);
            }
        }
        @keyframes slideUpBounce {
            0% {
                transform: scale(0.7) translateY(30px) rotate(-2deg);
                opacity: 0;
            }
            60% {
                transform: scale(1.05) translateY(-5px) rotate(1deg);
                opacity: 1;
            }
            80% {
                transform: scale(0.98) translateY(2px) rotate(-0.5deg);
            }
            100% {
                transform: scale(1) translateY(0) rotate(0deg);
                opacity: 1;
            }
        }
        @keyframes fadeOut {
            from { 
                opacity: 1;
                backdrop-filter: blur(4px);
            }
            to { 
                opacity: 0;
                backdrop-filter: blur(0px);
            }
        }
    </style>
</head>
<body>

<div class="title">퀴즈 시간 변경</div>

<div class="btn-grid">
    <button class="time-btn btn-minus" onclick="updateTime(-30)">-30</button>
    <button class="time-btn btn-minus" onclick="updateTime(-20)">-20</button>
    <button class="time-btn btn-minus" onclick="updateTime(-10)">-10</button>
    <button class="time-btn btn-minus" onclick="updateTime(-5)">-5</button>
    <button class="time-btn btn-plus" onclick="updateTime(5)">+5</button>
    <button class="time-btn btn-plus" onclick="updateTime(10)">+10</button>
    <button class="time-btn btn-plus" onclick="updateTime(20)">+20</button>
    <button class="time-btn btn-plus" onclick="updateTime(30)">+30</button>
</div>

<div class="input-row">
    <input type="number" class="custom-input" id="customTime" placeholder="직접 입력 (분)">
    <button class="submit-btn" onclick="updateCustomTime()">적용</button>
</div>

<button class="cancel-btn" onclick="window.close()">닫기</button>

<div class="status" id="statusMsg"></div>

<script>
var attemptId = <?php echo $attemptid; ?>;

// 피드백 메시지 배열 (구간별 60개씩)
var feedbackMessages = {
    // ① 마이너스 시간 선택 - 자신감 + 집중력 + 과감함
    negative: [
        "오, 시간 줄여서 바로 승부 보겠다는 거네? 그럼 멘탈은 눌러두고 가라. 😏",
        "과감하긴 한데… 너무 날뛰면 실수 난다. 천천히. 🧘",
        "시간 깎았다고 생각도 깎으면 안 된다? 대가가 커. 😶‍🌫️",
        "좋아, 스피드전 가자. 단, 허둥 모드 금지. 🏎️💨",
        "오? 줄였어? 자신감은 오케이. 조급함은 NO. 🙅‍♂️",
        "뇌가 '이건 된다'고 말하고 있네. 급한 건 너지 뇌가 아니다. 😌",
        "시간 줄이면 집중력은 자동 상승… 근데 조급 모드도 자동 켜진다. 꺼라. 🔥🧊",
        "속도 올리는 건 괜찮아. 근데 사고는 절대 스킵 금지. 🎯",
        "과감하네? 그럼 정확성은 더 과감하게 챙겨라. 📌",
        "시계는 줄었지만 사고는 줄이면 안 돼. ✂️🚫",
        "오케이, 압축 플레이 들어가는구나. 대신 마음은 널찍하게. 🧠",
        "줄임 선택은 멋있다. 근데 판단은 절대 가볍게 가지 마. ⚖️",
        "시간 아꼈지? 그럼 실수로 잃지 마라. 🔄",
        "빠르게 가는 건 괜찮아. '급하게' 가는 게 문제지. 😮‍💨",
        "시간 줄였으니까 집중은 두 배로 조여라. 🪢",
        "위험한 선택일 수도 있지만… 그만큼 집중하면 된다. 🔥",
        "줄인 만큼 책임감도 올라간다. 멘탈 수평 유지. 🌡️",
        "뇌에 부스터 달았네. 과열은 막아라. 🧠⚡",
        "줄여놓고 헤매면 더 민망하다. 분명하게 가라. 👀",
        "속도전 좋다. 대신 정교하게. 수술하듯. 🔪",
        "시간 단축 = 판단력 테스트. 준비됐지? 😏",
        "멋있게 줄여놓고 실수하면 웃기다. 정신 잡자. 😤",
        "빠른 흐름 좋아. 하지만 각도는 틀어지면 안 된다. 📐",
        "압축 플레이의 핵심은 '침착함'. 기억하자. 📌",
        "시간 줄이면서 영혼까지 줄이지 마라. 🙃",
        "줄였다고 답도 줄어드는 건 아니다. 머리 써! 🧠",
        "단축 선택은 용기다. 그 뒤는 기술이다. 🎛️",
        "좋아, 깔끔하게 치고 빠질 생각이지? 그럼 더 정확하게. 🥷",
        "줄였으면 리듬도 탄탄하게 유지해야 한다. 🎵",
        "시간은 줄었고, 오차는 더 허용되지 않는다. 🤏",
        "단축은 '집중력 올인'이 전제다. 빼먹지 마라. 🎯",
        "속도전 할 때 더 조용히 생각해야 한다. 🧘‍♂️",
        "줄였네? 오케이. 그럼 사고는 좁고 깊게. 🔍",
        "시간 줄인 건 멋있다. 실수 줄이는 건 더 멋있다. ✨",
        "단축한 만큼 여유는 없다. 그 대신 자신감 있다면 괜찮다. 😉",
        "줄였다고 꼭 급할 필요는 없다. 천천히 골라잡아라. 🖐️",
        "빠르게 간다고 뇌도 빠를 거라 착각하면 안 된다. 체크해라. 🔎",
        "줄인 만큼 단계를 건너뛰고 싶겠지? 참아라. 🙅",
        "단축 구간은 마음이 흔들리기 제일 쉽다. 고정! 📌",
        "오케이, 짧은 전쟁으로 끝내려는구나. 그럼 집중력은 장전 완료해야지. 🔫",
        "줄인 순간부터는 '정확성'이 왕이다. 👑",
        "속도 올리기 = 긴장 올리기. 둘 다 관리해야 한다. ⚡🧘",
        "시간 줄인 사람 대부분 여기서 실수하더라… 너는 예외 만들어라. 😏",
        "단축은 힘이 아니라 기술 싸움이다. 🎮",
        "오, 공격적이네? 그럼 마음은 수비적으로. 🛡️",
        "줄였으면 멘탈도 심플하게. 복잡해지면 망한다. 🌀",
        "지금 이 흐름 그대로 유지하면 된다. 흔들리지 마라. 📶",
        "시간 줄인 건 용기. 실수 줄이는 건 능력. 둘 다 보여줘. 💡",
        "급해지는 순간 바로 틀린다. 그 찰나 조심. ⚠️",
        "단축은 선택했다. 이제 집중은 의무다. 🧠",
        "오케이, 튀는 템포 좋다. 다만 사고는 점잖게. 😌",
        "줄였다고 스스로 쫓기지 마라. 네가 시간의 주인이다. ⏳",
        "짧을수록 차분해야 한다. 반비례다. 📉🧘",
        "줄인 시간 속에서 더 넓게 보려는 건 욕심이다. 좁혀라. 👁️",
        "단축 구간은 판단력과 집중력 둘 다 뽑아먹는다. 체력 관리. 🧃",
        "결정은 빠르게, 검토는 침착하게. 🧠🔁",
        "오케이, 리듬은 빨라졌고… 그럼 실수는 더 빨리 온다. 조심. 🚧",
        "우당탕 거리면 끝난다. 살살, 조용하게 밀어. 🐾",
        "시간은 줄였고… 그럼 핑계도 줄어든다. 😏",
        "단축은 용기, 마무리는 침착함. 둘 다 합쳐야 맛이 난다. 🥣✨"
    ],
    // ② 10분 이하 (0~10분) - 침착 + 자신감 + 페이스 유지
    short: [
        "했다면 그만큼 더 차분해야 한다. 🤲",
        "긴장해서 넣었어도 괜찮아. 다만 허둥대지 마라. 🫠",
        "짧게 넣는 건 타협이 아니다. 리셋 과정이다. 🔄",
        "시간 조금 늘렸네? 그럼 판단은 조금 더 단단해야지. 💎",
        "10분 이하는 '조율'이야. 감정 조절부터 다시. 🎛️",
        "좋아, 아주 살짝 늘렸어. 그럼 눈은 더 예리하게. 👁️‍🗨️",
        "작은 시간도 큰 의미가 있다. 침착하게. 🧘",
        "이 정도면 충분해. 네 페이스 믿어. 🙂",
        "오케이, 조급할 필요 없어. 천천히 확실하게. 🐢",
        "괜찮아, 네 속도로 가자. 리듬 유지가 핵심이야. 🎵",
        "너무 조급해하지 말고, 차분하게 이어가자. 💆",
        "좋아, 가볍게 정리하고 다시 집중하자. ✨",
        "충분해! 네 판단 나쁘지 않아. 👍",
        "그래, 그 정도면 현명한 선택이야. 🧠",
        "오케이, 과하지 않고 좋아. 그대로 가자. ✅",
        "천천히 한 번 더 생각해보면 훨씬 잘 풀려. 💡",
        "네 템포 괜찮다. 흔들리지 마. 🎯",
        "좋아, 작은 시간도 의미 있어. 잘하고 있어. 💎",
        "네 감각 좋다. 침착하게만 가면 돼. 🎪",
        "성급하게 몰아치지 말고, 지금처럼만! ⚡",
        "괜찮아, 충분히 할 수 있어. 💪",
        "오케이, 페이스 컨트롤 좋아! 🎮",
        "천천히 해도 괜찮아. 정확도가 더 중요해. 🎯",
        "좋아, 네 리듬 지키는 게 제일 중요해. 🎶",
        "이 정도면 충분해. 걱정하지 마. 😊",
        "너무 급하게만 안 하면 다 돼. 🌊",
        "좋아, 지금 흐름 깔끔해. ✨",
        "가볍게 호흡하고 다시 집중하자. 🫁",
        "네 템포가 딱 맞아. 유지하자. 🎵",
        "잘 판단했어. 무리 안 하는 게 좋아. 🧠",
        "좋아, 자신감 있게 가자. 💪",
        "침착함이 답이야. 네가 잘 알지? 🎯",
        "충분히 가능해. 흔들리지 말자. 💎",
        "오케이, 천천히 생각하면 더 잘 보여. 👁️",
        "괜찮아, 이 정도면 완전 적당해. ✅",
        "네 페이스가 정답이야. 🎯",
        "너무 서두르지 않는 게 오히려 좋아. 🐢",
        "좋아, 지금 템포 지켜! 🎵",
        "천천히. 그게 제일 강하다. 💪",
        "잘 하고 있어. 걱정하지 마. 😊",
        "네가 결정한 시간, 믿어도 돼. 🤝",
        "오케이, 차분하게 마무리해보자. 🧘",
        "좋아, 지금 흐름 그대로! 🌊",
        "안정적인 선택이다. 좋아. ✅",
        "침착한 선택은 항상 맞아. 🎯",
        "네 페이스가 제일 효율적이야. ⚡",
        "이런 선택 좋아. 다급함 없이! ✨",
        "차분하게 하니까 더 잘한다. 🧠",
        "너무 성급할 필요 없어. 너는 할 수 있어. 💪",
        "좋다, 리듬 좋다. 유지하자. 🎶",
        "네 템포가 지금 딱 적당해. 🎵",
        "오케이, 마음 급하게 먹지 마. 🧘",
        "괜찮아, 충분해. 잘하고 있어. 😊",
        "침착하게만 하면 돼. 🎯",
        "지금 선택 아주 좋아. ✅",
        "서두름 없이 가면 훨씬 잘 돼. 🐢",
        "좋은 판단! 과하지 않아서 더 좋아. 🧠",
        "이렇게 안정적으로 가는 게 정답이지. ✅",
        "네 리듬이 가장 효율적이야. 🎵",
        "좋아, 차분함 유지하자. 🧘",
        "흐름 괜찮다. 계속 가자. 🌊",
        "천천히 보고, 정확히 가자. 👁️",
        "조급할 이유 전혀 없어. 😊",
        "좋아, 잘 잡아간다! 💪",
        "네 템포를 믿어. 가장 안전해. 🛡️",
        "오케이, 딱 좋아. 지금 그대로만 해! ✨"
    ],
    // ③ 10분 초과 ~ 30분 미만 - 안정적 페이스 유지 + 흐름 조절
    medium: [
        "오… 꽤 늘렸네? 그럼 멘탈은 반대로 조여라. 🪢",
        "시간 넉넉하게 잡았구나. 흐름만 놓치지 말자. 🌊",
        "여유는 늘었는데 집중이 줄면 안 된다? 그건 기본이다. 😏",
        "중간 정도 늘렸네. 이 구간이 가장 쉽게 늘어진다는 거 알지? 😶‍🌫️",
        "오케이, 넉넉해 보이는데 방심만 금지. 👀",
        "시간은 늘었지만 뇌는 더 분명해야 한다. 🔦",
        "여유를 주면 생각도 풀어지기 쉬워. 조심해라. 🌬️",
        "중간 추가는 양날의 검이다. 집중만 유지해라. 🔪",
        "흐름 유지가 진짜 문제다. 시간 자체는 부차적. 🌐",
        "오케이, 늘어난 만큼 리듬 관리가 더 중요해진다. 🥁",
        "이 정도 추가는 '집중력 테스트 코스'다. 준비됐지? 😏",
        "적당히 늘린 게 오히려 위험할 때 많다. 센스 발휘해라. 🧠",
        "중간 구간의 핵심: 침착 + 흐름 보호 + 과한 여유 금지. 🎛️",
        "시간은 늘어났지만 책임도 늘어났다. 😌",
        "방심신호 켜지기 쉬운 구간이다. 끄자. 🚫",
        "오케이, 시간은 늘었어도 판단은 더 좁혀야 한다. 🎯",
        "이 구간에서 가장 중요한 건 '리듬 유지'다. 🌀",
        "줘버린 여유를 뇌가 다 써버리게 두면 안 된다. ✋",
        "오케이, 늘어났네. 그럼 속도가 아니라 방향을 봐라. 🧭",
        "중간 추가는 흔들리는 마음의 증거일 때도 있다. 정리부터. 🧹",
        "여유는 늘었는데 집중은 줄어드는 건 진짜 최악이다. 방지해. 📉",
        "흐름이 살짝 풀릴 조짐이 보인다. 바로 잡아라. 🛠️",
        "시간이 늘어나면 사고도 퍼진다. 좁혀라. 🧠➡️🎯",
        "중간 구간에서는 '속도 욕심'이 제일 위험하다. 조심. ⚠️",
        "오케이, 가성비 좋게 쓰려면 침착함이 필수다. 💸",
        "이 구간은 흔들림 빈도가 올라가는 구간이다. 대비해라. 📡",
        "중간 추가는 선택이지 해결책이 아니다. 🧩",
        "시간 늘린 만큼 사고 범위도 다이어트해라. 🥗",
        "질질 끌기 좋은 구간이다. 정신 챙겨. 😤",
        "오케이, 생각을 다시 정렬해라. 시간이 아니라 사고가 문제다. 🔄",
        "시간이 늘면 리듬 관리 난이도가 올라간다. 기억해라. 🧗",
        "이 구간 문제는 '늘어진 집중력'이다. 팍 조여라. 🪢",
        "중간 정도 늘렸네. 그럼 정확성에 더 투자해라. 🎯",
        "오케이, 흐름 깨지기 싱크홀 구간이다. 조심. 🕳️",
        "여유가 늘어난 만큼 멍 때릴 확률도 늘었다. 차단해. 🧱",
        "시간이 넉넉하면 생각이 늘어지는 건 본능이다. 이겨라. 🧠🔥",
        "괴상하게 늘어지기 쉬운 타이밍이다. 잡아라. 🫳",
        "중간 추가는 리듬 깨기 딱 좋은 트리거다. 하드락 걸어라. 🔒",
        "이 구간은 '생각이 과하게 널어지는 구간'이다. 선을 좁혀. 📏",
        "오케이, 멘탈이 널널해지기 직전이다. 브레이크 살짝. 🧘‍♂️",
        "시간 늘어났다고 문제도 쉬워지는 건 아니다. 착각 금지. ❌",
        "이 타이밍은 페이스 재정비가 핵심이다. 🧩",
        "중간 추가였으면 '속도 조절'부터 다시. 🎚️",
        "여유라는 함정에 빠지기 쉬운 타이밍이다. 의식해라. ⚓",
        "오케이, 늘어난 만큼 사고도 산만해질 수 있다. 한 점에 집중. 🔘",
        "시간이 늘면 관성이 생긴다. 그 관성 조심해. 🌀",
        "흐름이 부드럽게 이어질 때만 이 구간이 안전하다. 🌙",
        "중간 추가 = 페이스 리셋. 복구부터. 🔧",
        "오케이, 꾸물꾸물해질 조짐 보인다. 끊어라. ✂️",
        "늘어난 시간이 너를 잡아먹지 않게 해라. 🍽️",
        "중간 구간은 방심 구간이다. 여기서 실수난다. 🤦‍♂️",
        "생각 과잉이 가장 쉽게 생기는 구간이다. 줄여라. 📉",
        "오케이, 늘어졌지? 그럼 다시 직각으로 세워라. 📐",
        "중간 추가는 '관리'가 필요하다. 그냥 두면 흐트러진다. 🪢",
        "여유는 늘었지만, 확실한 근거로만 움직여라. 🧠",
        "시간이 늘어난 만큼 선택도 느슨해진다. 단단히! 🧱",
        "오케이, 속도조절 실패하기 쉬운 구간이다. 리듬 고정. 🥁",
        "중간 추가는 집중력이 '틈새'를 만든다. 메꿔라. 🧩",
        "늘린 시간만큼 사고를 날씬하게 유지하자. 🏃‍♂️",
        "이 구간은 네가 기세를 잃기 쉬운 구간이다. 텐션 유지해라. 🔥"
    ],
    // ④ 30분 이상 - 느슨해짐 경계 + 집중 유지 + 우직하게 잡아주기
    long: [
        "오… 30분 넘겼네? 이건 '유혹 구간'이다. 정신 끈 꽉 잡아. 🪢",
        "여기서 느슨해지면 바로 무너진다. 호흡 다시 잡자. 🧘‍♂️",
        "시간 많아 보이지? 착각이다. 집중은 줄어들려고 한다. 👀",
        "30분 이상은 '느려짐 버프'가 붙는다. 차단해라. 🚫📉",
        "여유를 준 만큼 멘탈은 단단히 조여라. 🧱",
        "오케이, 길게 가네. 이러면 흐름 금방 풀린다. 잠가. 🔒",
        "시간은 길어졌지만 사고는 길어지면 안 된다. ✂️",
        "여유 구간=방심 구간. 지금이 그 타이밍이다. ⚠️",
        "30분+는 마음이 풀리기 딱 좋은 함정이다. 🕳️",
        "오, 크게 늘렸네? 그럼 뇌는 더 예리해야 한다. 🔪",
        "너 지금 '늘어짐 위험'을 샀다. 조여라. 🧠💢",
        "긴 시간은 생각을 퍼지게 만든다. 좁혀. 📏",
        "집중력은 시간 많다고 늘어나지 않는다. 반대로 줄어든다. 🫥",
        "여유는 플러스지만 방심은 마이너스다. 둘을 헷갈리지 마라. ➕➖",
        "30분 넘기면 사고가 여기저기 튀기 시작한다. 붙잡아. 🪤",
        "오케이, 길게 잡았으면 움직임은 더 단단히. 🤜",
        "엉덩이가 먼저 편해지려고 한다. 허리 펴라. 🧍‍♂️",
        "긴 시간은 오히려 뇌 속 잡음만 키운다. 정리해라. 🧹",
        "지금부터는 '생각 낮잠'이 가장 큰 적이다. 😪🚫",
        "30분은 루즈해지는 데 충분한 시간이다. 경계해라. ⚡",
        "마음이 풀리는 소리 들린다... 야, 잡아! 😮‍💨",
        "시간 늘었지만, 페이스는 줄이면 안 돼. 💫",
        "오케이, 이건 진짜 흐름 관리 싸움이다. 🌀",
        "시간 긴 만큼 선택은 날카롭게. 🔘",
        "늘어진 생각, 퍼진 판단… 둘 다 위험하다. 🫠",
        "너무 편안해지면 바로 틀린다. 긴장 유지. 😤",
        "시간은 여유일 뿐, 해답은 여유에서 안 나와. 🙃",
        "30분 이상은 '안일함 버프'를 몰래 준다. 없애라. 🔥",
        "이 구간은 부주의가 가장 잘 피어난다. 뽑아버려. 🌱✂️",
        "급하지 않은 척하다가 놓친다. 집중 빡. 👁️",
        "긴 여유는 판단을 흐리게 한다. 맑게 해라. 💧",
        "30분? 오케이. 대신 생각은 10분짜리처럼. 🧠⚙️",
        "머릿속이 늘어지는 순간 바로 잡아라. 🪢",
        "여유로 방심하면 결과는 바로 불편해진다. 😬",
        "긴 시간일수록 템포는 짧게 잡는 게 정답이다. ✂️",
        "여유가 많아지면 사고는 느려진다. 역행해라. 🏃‍♂️💨",
        "오케이, 이건 진짜 멘탈 싸움이다. 💥",
        "30분 넘기면 뇌가 '쉬어도 되나?'라고 묻는다. 대답은 '아니'다. 🚫",
        "느슨함이 스멀스멀 온다. 바로 문 닫아라. 🚪💥",
        "긴 시간만큼 집중력 증발 속도도 빠르다. 잡아. 🧪",
        "오케이, 여유와 긴장은 같이 가야 한다. 둘 중 하나만 택하면 진다. ⚖️",
        "30분 이상은 방심 구간의 시작이다. 끝까지 버텨. 🧗",
        "생각이 사방으로 퍼질 구간이다. 경계선 쳐라. 🚧",
        "여유는 편안함을 부르고 편안함은 실수를 부른다. 순서다. 😶",
        "너 지금 느슨해질 확률 70% 찍고 있다. 조여라. 📉",
        "30분 이상의 선택은 자신감보다 관리가 더 중요하다. 🪄",
        "이 타이밍은 뇌가 멈칫하기 좋다. 멈추기 전에 잡아라. ⚡",
        "노는 마음과 일하는 마음이 섞이기 시작하는 구간이다. 바꿔라. 🔄",
        "긴 시간은 절대 강점이 아니다. 무기에도, 독에도 된다. 🧪",
        "오케이, 흐트러지지 않는 사람이 이긴다. 그냥 그거다. 🧠",
        "시간은 많아도 집중은 적어지는 게 공식이다. 깨라. 📐",
        "30분+는 템포를 죽인다. 직접 지켜라. ⚰️",
        "여유 구간은 생각이 풀리는 구간이다. 철조망 둘러라. 🪖",
        "편안함이 습격하려는 순간이다. 문단속해라. 🔒",
        "긴 시간 쓴다고 해결되는 게 아니다. 뇌가 결정한다. 👁️‍🗨️",
        "30분대는 진짜 정신이 흐리멍덩해지기 쉬운 타이밍이다. 선명하게. 🔆",
        "늘어짐 감지됨. 페이스 복구하자. 🔧",
        "여유는 늘었고, 위험도도 늘었다. 균형 유지. ⚖️",
        "오케이, 이건 절대 느긋하게 가면 안 된다. 집중! 😤",
        "30분 이상 추가는 '루즈 모드' 자동 발동이다. 즉시 해제하라. 📴🔥"
    ]
};

// 팝업을 항상 위에 유지
window.onload = function() {
    window.focus();
};

// 부모 창 클릭 시에도 팝업 유지를 위한 포커스 인터벌
var focusInterval = setInterval(function() {
    if (!document.hasFocus()) {
        window.focus();
    }
}, 500);

// 창이 닫힐 때 인터벌 정리
window.onbeforeunload = function() {
    clearInterval(focusInterval);
};

function updateTime(minutes) {
    sendRequest(minutes);
}

function updateCustomTime() {
    var val = document.getElementById('customTime').value;
    if (!val || isNaN(val)) {
        showStatus('숫자를 입력해주세요.', 'error');
        return;
    }
    sendRequest(parseInt(val));
}

// 피드백 메시지 선택 함수
function getFeedbackMessage(minutes) {
    var messages;
    
    // 마이너스 시간 선택 시
    if (minutes < 0) {
        messages = feedbackMessages.negative;
    }
    // 시간 구간 판단
    else if (minutes <= 10) {
        messages = feedbackMessages.short;
    } else if (minutes < 30) {
        messages = feedbackMessages.medium;
    } else {
        // 30분 이상 (30분 포함)
        messages = feedbackMessages.long;
    }
    
    // 현재 시간(초)을 60으로 나눈 나머지로 선택 (0이면 60번 선택)
    var now = new Date();
    var seconds = now.getSeconds();
    var index = seconds % 60;
    if (index === 0) {
        index = 60;
    }
    // 배열 인덱스는 0부터 시작하므로 -1
    return messages[index - 1];
}

// 피드백 애니메이션 표시 함수
function showFeedbackAnimation(message) {
    // 기존 피드백 제거
    var existing = document.querySelector('.feedback-overlay');
    if (existing) {
        existing.remove();
    }
    
    // 피드백 오버레이 생성
    var overlay = document.createElement('div');
    overlay.className = 'feedback-overlay';
    
    var box = document.createElement('div');
    box.className = 'feedback-box';
    
    var text = document.createElement('p');
    text.className = 'feedback-text';
    text.textContent = message;
    
    box.appendChild(text);
    overlay.appendChild(box);
    document.body.appendChild(overlay);
    
    // 3초 후 페이드아웃 및 제거 (bounce 애니메이션 고려)
    setTimeout(function() {
        overlay.style.animation = 'fadeOut 0.4s ease-out forwards';
        setTimeout(function() {
            overlay.remove();
        }, 400);
    }, 3000);
}

function sendRequest(minutes) {
    showStatus('처리 중...', 'success');

    $.ajax({
        url: "check.php",
        type: "POST",
        data: {
            "eventid": '301',
            "inputtext": minutes,
            "attemptid": attemptId
        },
        success: function(data) {
            // 피드백 메시지 가져오기
            var feedbackMsg = getFeedbackMessage(minutes);
            
            // 피드백 애니메이션 표시
            showFeedbackAnimation(feedbackMsg);
            
            // 피드백 표시 후 팝업 닫기 및 새로고침
            setTimeout(function() {
                if (window.opener && !window.opener.closed) {
                    window.opener.location.reload();
                }
                window.close();
            }, 4400); // 애니메이션 시간 고려 (3초 표시 + 0.4초 페이드아웃 + 1초 추가)
        },
        error: function(xhr, status, error) {
            showStatus('오류 발생: ' + error, 'error');
            console.error("Error:", error, "File: addtime.php, Function: sendRequest, Line: " + (arguments.callee.caller ? arguments.callee.caller.line : 'unknown'));
        }
    });
}

function showStatus(msg, type) {
    var el = document.getElementById('statusMsg');
    el.textContent = msg;
    el.className = 'status ' + type;
}
</script>

</body>
</html>
<?php
/**
 * 관련 DB 테이블 및 필드:
 *
 * 테이블: mdl_quiz_attempts
 * 필드:
 *   - id (int): 시도 ID
 *   - quiz (int): 퀴즈 ID
 *   - userid (int): 사용자 ID
 *   - attempt (int): 시도 번호
 *   - state (varchar): 상태 (inprogress, finished, abandoned)
 *   - timestart (bigint): 시작 시간 (Unix timestamp)
 *   - timefinish (bigint): 종료 시간
 *   - timemodified (bigint): 수정 시간
 *   - addtime (int): 추가된 시간 (분)
 *   - timeadded (bigint): 시간 추가된 시각
 *   - modified (varchar): 수정 유형
 */
?>
