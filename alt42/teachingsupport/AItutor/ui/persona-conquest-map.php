<?php
/**
 * ✨ 9가지 경험 연속체 컷 전략
 * 페르소나 개선을 위한 창의적 전략 소개
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentId = $_GET['studentid'] ?? $USER->id;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✨ 9가지 경험 연속체 컷 전략</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3a 50%, #0a0a2a 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        .header {
            background: rgba(0, 0, 0, 0.4);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header h1 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #f59e0b, #ea580c, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .back-link:hover { text-decoration: underline; }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .intro {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(234,88,12,0.05));
            border-radius: 1.5rem;
            border: 1px solid rgba(245,158,11,0.2);
        }
        .intro h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #f59e0b;
        }
        .intro p {
            font-size: 1rem;
            color: #9ca3af;
            line-height: 1.8;
        }
        .strategies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .strategy-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .strategy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            border-color: var(--card-color);
        }
        .strategy-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
        }
        .strategy-number {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 36px;
            height: 36px;
            background: var(--card-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        .strategy-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .strategy-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #f3f4f6;
        }
        .strategy-subtitle {
            font-size: 0.875rem;
            color: var(--card-color);
            margin-bottom: 1rem;
        }
        .strategy-desc {
            font-size: 0.875rem;
            color: #9ca3af;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .strategy-path {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .path-node {
            background: rgba(255,255,255,0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .path-arrow {
            color: var(--card-color);
            font-weight: bold;
        }
        .strategy-target {
            background: rgba(255,255,255,0.05);
            padding: 0.75rem;
            border-radius: 0.5rem;
            border-left: 3px solid var(--card-color);
        }
        .target-label {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }
        .target-text {
            font-size: 0.875rem;
            color: #f3f4f6;
        }
        .strategy-effect {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.8125rem;
            color: #a5b4fc;
        }
        /* 카드 색상들 */
        .strategy-1 { --card-color: #667eea; }
        .strategy-2 { --card-color: #ec4899; }
        .strategy-3 { --card-color: #10b981; }
        .strategy-4 { --card-color: #f59e0b; }
        .strategy-5 { --card-color: #ef4444; }
        .strategy-6 { --card-color: #8b5cf6; }
        .strategy-7 { --card-color: #3b82f6; }
        .strategy-8 { --card-color: #14b8a6; }
        .strategy-9 { --card-color: #f97316; }
        
        .quick-guide {
            margin-top: 3rem;
            background: rgba(0,0,0,0.3);
            border-radius: 1.5rem;
            padding: 2rem;
        }
        .quick-guide h3 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: #f59e0b;
            text-align: center;
        }
        .guide-table {
            width: 100%;
            border-collapse: collapse;
        }
        .guide-table th, .guide-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .guide-table th {
            background: rgba(255,255,255,0.05);
            font-size: 0.75rem;
            color: #9ca3af;
            text-transform: uppercase;
        }
        .guide-table td {
            font-size: 0.875rem;
        }
        .guide-table tr:hover {
            background: rgba(255,255,255,0.05);
        }
        .tag {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            margin-right: 0.25rem;
        }
        .tag-high { background: rgba(239,68,68,0.2); color: #fca5a5; }
        .tag-medium { background: rgba(245,158,11,0.2); color: #fcd34d; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 200;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .modal.open { display: flex; }
        .modal-content {
            background: linear-gradient(145deg, #1a1a3a, #0a0a2a);
            border-radius: 1.5rem;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.25rem;
        }
        .modal-header {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .modal-icon { font-size: 4rem; margin-bottom: 0.75rem; }
        .modal-title { font-size: 1.5rem; font-weight: 700; }
        .modal-section {
            margin-bottom: 1.5rem;
        }
        .modal-section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #f59e0b;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-section-content {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.7;
        }
        .node-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .node-detail:last-child { border-bottom: none; }
        .node-id {
            background: var(--card-color, #667eea);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
            min-width: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="header">
        <div>
            <a href="math-persona-system.php" class="back-link">← 도감으로 돌아가기</a>
            <h1>✨ 9가지 경험 연속체 컷 전략</h1>
        </div>
    </header>

    <div class="container">
        <div class="intro">
            <h2>🧠 경험의 연속체를 끊어 변화를 만드는 전략</h2>
            <p>
                학습 중 자동으로 흐르는 "경험 연속체"를 적절히 분절하면<br>
                새로운 사고방식과 전략을 받아들일 수 있게 됩니다.<br>
                <strong>9가지 결이 다른 메커니즘</strong>으로 자아 흐름을 리셋해보세요.
            </p>
        </div>

        <div class="strategies-grid">
            <!-- 전략 1: 정체성 기반 컷 루프 -->
            <div class="strategy-card strategy-1" onclick="openModal(1)">
                <div class="strategy-number">1</div>
                <div class="strategy-icon">🪞</div>
                <div class="strategy-title">정체성 기반 컷 루프</div>
                <div class="strategy-subtitle">의식의 리셋 → 관점 이동 → 기억 재정렬</div>
                <div class="strategy-desc">
                    감정 루프부터 끊고, 사고·기억·논리를 순차적으로 리셋하여 
                    정체성 기반 습관을 완전히 초기화합니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">2</span><span class="path-arrow">→</span>
                    <span class="path-node">1</span><span class="path-arrow">→</span>
                    <span class="path-node">6</span><span class="path-arrow">→</span>
                    <span class="path-node">30</span><span class="path-arrow">→</span>
                    <span class="path-node">33</span><span class="path-arrow">→</span>
                    <span class="path-node">47</span><span class="path-arrow">→</span>
                    <span class="path-node">12</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">고집·실수 인정 어려움, 자기방식 고착</div>
                </div>
                <div class="strategy-effect">✨ 효과: 정체성 기반 습관 리셋 → 새로운 풀이 습관 수용</div>
            </div>

            <!-- 전략 2: 감각 전환 기반 컷 루프 -->
            <div class="strategy-card strategy-2" onclick="openModal(2)">
                <div class="strategy-number">2</div>
                <div class="strategy-icon">👁️</div>
                <div class="strategy-title">감각 전환 기반 컷 루프</div>
                <div class="strategy-subtitle">감각 모드 전환 → 시점 이동</div>
                <div class="strategy-desc">
                    감각을 바꾸면 자아 흐름이 자동으로 재정렬됩니다. 
                    시각·촉각 모드를 강제 전환하여 공간 지각을 재구성합니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">22</span><span class="path-arrow">→</span>
                    <span class="path-node">4</span><span class="path-arrow">→</span>
                    <span class="path-node">39</span><span class="path-arrow">→</span>
                    <span class="path-node">28</span><span class="path-arrow">→</span>
                    <span class="path-node">55</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">도형/그래프/공간 착시, 손이 먼저 가는 타입</div>
                </div>
                <div class="strategy-effect">✨ 효과: 머릿속 공간 좌표 재정렬</div>
            </div>

            <!-- 전략 3: 기억 구조 붕괴→재정립 루프 -->
            <div class="strategy-card strategy-3" onclick="openModal(3)">
                <div class="strategy-number">3</div>
                <div class="strategy-icon">🧩</div>
                <div class="strategy-title">기억 구조 붕괴→재정립</div>
                <div class="strategy-subtitle">기억 흔들기 → 연속체 분절</div>
                <div class="strategy-desc">
                    기억 혼선을 직접 드러내면 "연속된 나"가 흔들리고 
                    새로운 구조 수용성이 증가합니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">17</span><span class="path-arrow">→</span>
                    <span class="path-node">40</span><span class="path-arrow">→</span>
                    <span class="path-node">41</span><span class="path-arrow">→</span>
                    <span class="path-node">25</span><span class="path-arrow">→</span>
                    <span class="path-node">31</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">금방 잊고 연결 약함, 개념→예제 연결 부족</div>
                </div>
                <div class="strategy-effect">✨ 효과: 장기기억 고정 가속화</div>
            </div>

            <!-- 전략 4: 논리 구조 해체 루프 -->
            <div class="strategy-card strategy-4" onclick="openModal(4)">
                <div class="strategy-number">4</div>
                <div class="strategy-icon">🧱</div>
                <div class="strategy-title">논리 구조 해체 루프</div>
                <div class="strategy-subtitle">논리 흐름 끊기 → 다시 이해하기</div>
                <div class="strategy-desc">
                    자아의 인지 구조(논리 흐름)를 해체하고 다시 세우는 
                    메타구조 전략입니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">32</span><span class="path-arrow">→</span>
                    <span class="path-node">33</span><span class="path-arrow">→</span>
                    <span class="path-node">15</span><span class="path-arrow">→</span>
                    <span class="path-node">34</span><span class="path-arrow">→</span>
                    <span class="path-node">20</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">단계 점프, 조건 누락, 논리 중간 생략</div>
                </div>
                <div class="strategy-effect">✨ 효과: 논리적 자아 모델 안정화</div>
            </div>

            <!-- 전략 5: 정서·동기 Reset 루프 -->
            <div class="strategy-card strategy-5" onclick="openModal(5)">
                <div class="strategy-number">5</div>
                <div class="strategy-icon">❤️‍🔥</div>
                <div class="strategy-title">정서·동기 Reset 루프</div>
                <div class="strategy-subtitle">감정·동기 루틴 끊기 → 새로운 의지</div>
                <div class="strategy-desc">
                    정서-동기 시스템 자체를 재시동하여 
                    "나는 할 수 있다" 서사를 다시 씁니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">44</span><span class="path-arrow">→</span>
                    <span class="path-node">21</span><span class="path-arrow">→</span>
                    <span class="path-node">58</span><span class="path-arrow">→</span>
                    <span class="path-node">12</span><span class="path-arrow">→</span>
                    <span class="path-node">2</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">불안·과민·컨디션 영향 큼, 기분 따라 편차 심함</div>
                </div>
                <div class="strategy-effect">✨ 효과: 집중-동기 회복 속도 급상승</div>
            </div>

            <!-- 전략 6: 전략 다중성→단일화 루프 -->
            <div class="strategy-card strategy-6" onclick="openModal(6)">
                <div class="strategy-number">6</div>
                <div class="strategy-icon">🎯</div>
                <div class="strategy-title">전략 다중성→단일화</div>
                <div class="strategy-subtitle">병렬 전략 정리 → 단일 루틴 정렬</div>
                <div class="strategy-desc">
                    전략을 하나의 중심축으로 밀어넣어 
                    자아의 혼란 루프를 절단합니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">56</span><span class="path-arrow">→</span>
                    <span class="path-node">46</span><span class="path-arrow">→</span>
                    <span class="path-node">59</span><span class="path-arrow">→</span>
                    <span class="path-node">7</span><span class="path-arrow">→</span>
                    <span class="path-node">24</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">여러 풀이 병행·혼란, 방황하는 스타일</div>
                </div>
                <div class="strategy-effect">✨ 효과: 사고 흐름 안정 + 효율 급상승</div>
            </div>

            <!-- 전략 7: 시간 인식 왜곡→정상화 루프 -->
            <div class="strategy-card strategy-7" onclick="openModal(7)">
                <div class="strategy-number">7</div>
                <div class="strategy-icon">⏰</div>
                <div class="strategy-title">시간 인식 왜곡→정상화</div>
                <div class="strategy-subtitle">시간 흐름 조절 → 자기경험 재정렬</div>
                <div class="strategy-desc">
                    시간 흐름을 조절하면 주관적 경험 흐름도 재정렬되고 
                    자아의 자동성도 리셋됩니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">11</span><span class="path-arrow">→</span>
                    <span class="path-node">26</span><span class="path-arrow">→</span>
                    <span class="path-node">51</span><span class="path-arrow">→</span>
                    <span class="path-node">52</span><span class="path-arrow">→</span>
                    <span class="path-node">36</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">시간 압박·느긋함 문제, 시간 체감 왜곡</div>
                </div>
                <div class="strategy-effect">✨ 효과: 행동 속도와 사고 페이스 안정</div>
            </div>

            <!-- 전략 8: 사회적 시점 전환 기반 컷 루프 -->
            <div class="strategy-card strategy-8" onclick="openModal(8)">
                <div class="strategy-number">8</div>
                <div class="strategy-icon">👥</div>
                <div class="strategy-title">사회적 시점 전환 컷</div>
                <div class="strategy-subtitle">자기시점 → 타인의 눈으로 이동</div>
                <div class="strategy-desc">
                    타인의 시점을 순간 끌어오면 자아 흐름이 분리되고 
                    새로운 행동을 수용할 수 있게 됩니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">58</span><span class="path-arrow">→</span>
                    <span class="path-node">22</span><span class="path-arrow">→</span>
                    <span class="path-node">44</span><span class="path-arrow">→</span>
                    <span class="path-node">43</span><span class="path-arrow">→</span>
                    <span class="path-node">41</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">평가 민감, 눈치 영향 큼, 사회적 불안</div>
                </div>
                <div class="strategy-effect">✨ 효과: 심리적 루프 즉시 중단</div>
            </div>

            <!-- 전략 9: 선택 구조 재배치 기반 컷 루프 -->
            <div class="strategy-card strategy-9" onclick="openModal(9)">
                <div class="strategy-number">9</div>
                <div class="strategy-icon">🔀</div>
                <div class="strategy-title">선택 구조 재배치 컷</div>
                <div class="strategy-subtitle">선택지 재배열 → 연속체 절단</div>
                <div class="strategy-desc">
                    학생의 자아는 "지금 무엇을 선택할 수 있는가"로 구성됩니다. 
                    선택 구조를 재배열하면 기존 자아 흐름도 끊깁니다.
                </div>
                <div class="strategy-path">
                    <span class="path-node">46</span><span class="path-arrow">→</span>
                    <span class="path-node">56</span><span class="path-arrow">→</span>
                    <span class="path-node">7</span><span class="path-arrow">→</span>
                    <span class="path-node">10</span><span class="path-arrow">→</span>
                    <span class="path-node">47</span>
                </div>
                <div class="strategy-target">
                    <div class="target-label">👉 이런 학생에게 추천</div>
                    <div class="target-text">선택 과부하·엉뚱 선택, 선택 피로</div>
                </div>
                <div class="strategy-effect">✨ 효과: 새로운 전략 수용성 상승</div>
            </div>
        </div>

        <!-- 빠른 가이드 -->
        <div class="quick-guide">
            <h3>🧩 전략 선택 가이드</h3>
            <table class="guide-table">
                <thead>
                    <tr>
                        <th>전략</th>
                        <th>핵심 메커니즘</th>
                        <th>추천 대상</th>
                        <th>우선도</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1. 정체성 컷</td>
                        <td>감정→사고→기억→논리 순차 리셋</td>
                        <td>고집, 실수 인정 어려움</td>
                        <td><span class="tag tag-high">높음</span></td>
                    </tr>
                    <tr>
                        <td>2. 감각 전환</td>
                        <td>시각/촉각 모드 강제 전환</td>
                        <td>도형/그래프/공간 착시</td>
                        <td><span class="tag tag-medium">중간</span></td>
                    </tr>
                    <tr>
                        <td>3. 기억 재정립</td>
                        <td>기억 혼선 → 재구조화</td>
                        <td>금방 잊음, 연결 약함</td>
                        <td><span class="tag tag-medium">중간</span></td>
                    </tr>
                    <tr>
                        <td>4. 논리 해체</td>
                        <td>논리 구조 분해 → 재조립</td>
                        <td>단계 점프, 조건 누락</td>
                        <td><span class="tag tag-high">높음</span></td>
                    </tr>
                    <tr>
                        <td>5. 정서 Reset</td>
                        <td>정서-동기 시스템 재시동</td>
                        <td>불안/과민/컨디션 영향</td>
                        <td><span class="tag tag-high">높음</span></td>
                    </tr>
                    <tr>
                        <td>6. 전략 단일화</td>
                        <td>병렬 전략 → 단일 축 정렬</td>
                        <td>여러 풀이 병행, 혼란</td>
                        <td><span class="tag tag-medium">중간</span></td>
                    </tr>
                    <tr>
                        <td>7. 시간 정상화</td>
                        <td>시간 감각 리셋</td>
                        <td>시간 압박, 느긋함 문제</td>
                        <td><span class="tag tag-medium">중간</span></td>
                    </tr>
                    <tr>
                        <td>8. 사회적 시점</td>
                        <td>타인 시점으로 이동</td>
                        <td>평가 민감, 눈치 영향</td>
                        <td><span class="tag tag-medium">중간</span></td>
                    </tr>
                    <tr>
                        <td>9. 선택 재배치</td>
                        <td>선택 구조 재배열</td>
                        <td>선택 과부하, 피로</td>
                        <td><span class="tag tag-medium">중간</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 상세 모달 -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">×</button>
            <div class="modal-header">
                <div class="modal-icon" id="modalIcon">🪞</div>
                <div class="modal-title" id="modalTitle">전략 이름</div>
            </div>
            <div class="modal-section">
                <div class="modal-section-title">📋 경로 상세</div>
                <div class="modal-section-content" id="modalPath"></div>
            </div>
            <div class="modal-section">
                <div class="modal-section-title">🎯 핵심 메커니즘</div>
                <div class="modal-section-content" id="modalMechanism"></div>
            </div>
            <div class="modal-section">
                <div class="modal-section-title">👤 추천 대상</div>
                <div class="modal-section-content" id="modalTarget"></div>
            </div>
            <div class="modal-section">
                <div class="modal-section-title">✨ 기대 효과</div>
                <div class="modal-section-content" id="modalEffect"></div>
            </div>
        </div>
    </div>

    <script>
    const strategies = {
        1: {
            icon: '🪞',
            title: '정체성 기반 컷 루프',
            path: [
                {id:2, name:'3초 패배형', desc:'감정 루프 끊기 - 부정감정 시작 시 브레이크'},
                {id:1, name:'아이디어 해방형', desc:'사고 폭주 루프 끊기 - 5초 멈춤으로 절단'},
                {id:6, name:'작업기억 ⅔형', desc:'작업기억 루프 끊기 - 맥락 다시 보게 함'},
                {id:30, name:'메타인지 고갈형', desc:'메타인지 빈칸 자각 - 자아에 균열'},
                {id:33, name:'사다리 건너뛰기형', desc:'논리적 자아모델 붕괴 - 미싱스텝 감지'},
                {id:47, name:'반례 무시형', desc:'틀림 수용 - 자아 재구축 여지 확보'},
                {id:12, name:'시험 트라우마형', desc:'새로운 성공 경험 삽입 - 자아 재정렬'}
            ],
            mechanism: '자아는 "나는 이런 식으로 문제를 푼다"라는 내적 일관성으로 구성됩니다. 감정→사고→기억→논리를 순차적으로 끊어내면 정체성 기반 습관이 리셋되어 새로운 풀이 습관을 받아들일 수 있게 됩니다.',
            target: '자기방식이 고착되고, 틀림을 인정하기 어려워하는 학생 (자기 이미지 보호 강함, 실수 지적에 민감)',
            effect: '정체성 루프를 잠깐 끊어주면 새로운 풀이 습관을 받아들일 수 있음. 가장 "정체성 기반 습관"을 리셋하는 최적 흐름.'
        },
        2: {
            icon: '👁️',
            title: '감각 전환 기반 컷 루프',
            path: [
                {id:22, name:'감정 전염형', desc:'환경 감각 차단'},
                {id:4, name:'무의식 실수형', desc:'손의 자동성을 끊기'},
                {id:39, name:'시각화 회피형', desc:'시각 모드를 강제로 켜기'},
                {id:28, name:'공간·시각 혼선형', desc:'도형 Re-Sketch로 감각 재배열'},
                {id:55, name:'참조 프레임형', desc:'축 변환으로 공간 지각 재구성'}
            ],
            mechanism: '감각을 바꾸면 자아 흐름이 자동으로 재정렬됩니다. 시각·촉각 모드를 강제 전환하여 공간 지각 자체를 재구성하는 감각 기반 "시점 이동 전략"입니다.',
            target: '손이 먼저 가고, 감각적 혼란(도형·좌표·그래프) 자주 오는 학생 (감각·지각 착시형)',
            effect: '감각 모드를 바꿔주면 머릿속 공간 좌표가 다시 재정렬됨.'
        },
        3: {
            icon: '🧩',
            title: '기억 구조 붕괴→재정립 루프',
            path: [
                {id:17, name:'단기기억 증발형', desc:'기억 끊김 자각'},
                {id:40, name:'메모 불능형', desc:'외부기억 장치를 활성화'},
                {id:41, name:'지식-실행 단절형', desc:'"아는 것"과 "하는 것" 간 간극 인식'},
                {id:25, name:'단일 예시 착시형', desc:'예시-기억 분리'},
                {id:31, name:'개념-용어 혼동형', desc:'의미기억 재정렬'}
            ],
            mechanism: '기억 혼선을 직접 드러내면 "연속된 나"가 흔들리고 새로운 구조 수용성이 증가합니다. 기억을 흔들어 연속체를 분절시키는 방식입니다.',
            target: '아는 것 같은데 연결이 안 되고, 금방 잊어버리는 학생 (단기기억 유실, 개념→예제 연결 약함)',
            effect: '기억 구조를 일부러 흔들고 다시 조립하면 장기기억 고정이 빨라짐.'
        },
        4: {
            icon: '🧱',
            title: '논리 구조 해체 루프',
            path: [
                {id:32, name:'역추적 단절형', desc:'정반대 방향 추론으로 논리축 흔들기'},
                {id:33, name:'사다리 건너뛰기형', desc:'논증 공백을 강제 인식'},
                {id:15, name:'조건 회피형', desc:'조건 명시화 → 논리 흐름 재창조'},
                {id:34, name:'조건 재정렬형', desc:'조건 순서를 재배치'},
                {id:20, name:'불완전 개념 종결형', desc:'정의 원문으로 "논리 엔진" 재부팅'}
            ],
            mechanism: '논리 흐름이 끊기면 자아는 자동으로 "다시 이해하기" 상태가 됩니다. 자아의 인지 구조(논리 흐름)를 해체하고 다시 세우는 메타구조 전략입니다.',
            target: '논리 중간단계 생략, 조건 빠뜨리기, 단계 점프가 반복되는 학생 (논증 사다리 건너뛰는 스타일)',
            effect: '논리 구조를 해체했다가 다시 세우면 "논리적 자아 모델"이 안정됨.'
        },
        5: {
            icon: '❤️‍🔥',
            title: '정서·동기 Reset 루프',
            path: [
                {id:44, name:'감정보상형', desc:'과도한 보상을 잘라냄'},
                {id:21, name:'피로-오답형', desc:'체력 신호 인식'},
                {id:58, name:'피드백 과민형', desc:'방어적 자아 리셋'},
                {id:12, name:'시험 트라우마형', desc:'과거 기억 재서사화'},
                {id:2, name:'3초 패배형', desc:'새로운 감정 루프 시작'}
            ],
            mechanism: '정서-동기 시스템 자체를 재시동하여 "나는 할 수 있다" 서사가 다시 써지는 구조입니다. 감정·동기 루틴을 끊어서 새로운 의지를 생성합니다.',
            target: '기분·기운 따라 공부 편차가 매우 심한 학생 (불안, 과민, 피로 기반 성능 저하)',
            effect: '정서 루프만 리셋해도 집중-동기 회복 속도가 확 빨라짐.'
        },
        6: {
            icon: '🎯',
            title: '전략 다중성→단일화 루프',
            path: [
                {id:56, name:'전략 중복형', desc:'병렬 전략 중단'},
                {id:46, name:'전환비용형', desc:'문제 전환을 늦춤'},
                {id:59, name:'다중문제 과부하형', desc:'문제 세트 최소화'},
                {id:7, name:'반포기 창의형', desc:'단일 정석 라인으로 고정'},
                {id:24, name:'이론-연산형', desc:'정석 라인을 계산-증명 균형으로 다듬기'}
            ],
            mechanism: '너무 많은 전략을 한 번에 쓰는 학생의 경험 흐름을 줄여 단일 루틴으로 정렬합니다. 전략을 하나의 중심축으로 밀어넣어 자아의 혼란 루프를 절단합니다.',
            target: '풀이를 너무 많이 펼치고, 방황하는 스타일 (여러 방법 동시 전개, 전환 난무)',
            effect: '전략을 하나의 중심축으로 강제하면 사고 흐름이 안정되고 효율 급상승.'
        },
        7: {
            icon: '⏰',
            title: '시간 인식 왜곡→정상화 루프',
            path: [
                {id:11, name:'속도 압박형', desc:'압박감 컷'},
                {id:26, name:'시간 왜곡형', desc:'체감시간 재설정'},
                {id:51, name:'중간점검형', desc:'중간 멈춤 삽입'},
                {id:52, name:'검산 회피형', desc:'최종 멈춤 삽입'},
                {id:36, name:'근사치 타협형', desc:'시간이 만든 오차 인식'}
            ],
            mechanism: '시간 흐름을 다시 잡으면 "자기경험 흐름" 자체가 재정렬됩니다. 시간 흐름을 조절하면 주관적 경험 흐름도 재정렬되고 자아의 자동성도 리셋됩니다.',
            target: '시간만 보면 멘탈 나가거나, 반대로 너무 느긋해지는 학생 (시험 압박형/시간 왜곡형)',
            effect: '시간 감각을 리셋하면 행동 속도와 사고 페이스가 안정됨.'
        },
        8: {
            icon: '👥',
            title: '사회적 시점 전환 기반 컷 루프',
            path: [
                {id:58, name:'피드백 과민형', desc:'타인의 평가가 촉발점(방어적 자아 흔들림)'},
                {id:22, name:'감정 전염형', desc:'환경 정서가 "내 정서"가 아님을 자각'},
                {id:44, name:'감정보상형', desc:'성취에 대한 과잉 자기해석 제거'},
                {id:43, name:'인터럽트 리셋형', desc:'타인/환경으로 끊긴 흐름을 "재구축"'},
                {id:41, name:'지식-실행 단절형', desc:'"타인이 보는 나 vs 실제 나" 간 간극을 재정립'}
            ],
            mechanism: '자아는 원래 "내 시점"의 연속체로 흘러가는데, 타인의 시점을 순간 끌어오면 자아 흐름이 분리되고 새로운 행동을 수용할 수 있게 됩니다.',
            target: '타인의 시선·평가에 예민하고, 감정 스위치 크게 흔들리는 학생 (사회적 불안, 평가 민감형)',
            effect: '"나를 보는 타인의 시점"으로 이동시키면 심리적 루프가 즉시 중단됨.'
        },
        9: {
            icon: '🔀',
            title: '선택 구조 재배치 기반 컷 루프',
            path: [
                {id:46, name:'전환 비용형', desc:'선택지 전환 비용을 인식시켜 관성 끊기'},
                {id:56, name:'전략 중복형', desc:'여러 선택지 동시 활성화를 차단'},
                {id:7, name:'반포기 창의형', desc:'비효율적 선택에서 "정석 선택지"로 재배열'},
                {id:10, name:'불확실 강행형', desc:'선택의 확신도를 라벨링'},
                {id:47, name:'반례 무시형', desc:'선택의 정당성을 검증하며 재선택 구조 만들기'}
            ],
            mechanism: '학생의 자아는 "내가 지금 무엇을 선택할 수 있는가"로 구성됩니다. 선택 구조를 재배열하면 기존의 자아 흐름도 자동으로 끊겨 새로운 전략을 받아들일 준비가 됩니다.',
            target: '선택이 많으면 머리 터지고, 잘못된 선택지를 고집하는 학생 (선택 피로, 선택방향 고착)',
            effect: '선택지를 재배열하면 기존 자아 흐름을 끊고 새로운 전략 수용성이 높아짐.'
        }
    };

    function openModal(id) {
        const s = strategies[id];
        if (!s) return;
        
        document.getElementById('modalIcon').textContent = s.icon;
        document.getElementById('modalTitle').textContent = s.title;
        
        document.getElementById('modalPath').innerHTML = s.path.map((p, i) => `
            <div class="node-detail">
                <span class="node-id" style="--card-color:${getColor(id)}">${p.id}</span>
                <div>
                    <strong>${p.name}</strong><br>
                    <span style="color:#9ca3af;font-size:0.8125rem;">${p.desc}</span>
                </div>
            </div>
        `).join('');
        
        document.getElementById('modalMechanism').textContent = s.mechanism;
        document.getElementById('modalTarget').textContent = s.target;
        document.getElementById('modalEffect').textContent = s.effect;
        
        document.getElementById('detailModal').classList.add('open');
    }

    function closeModal() {
        document.getElementById('detailModal').classList.remove('open');
    }

    function getColor(id) {
        const colors = {
            1: '#667eea', 2: '#ec4899', 3: '#10b981',
            4: '#f59e0b', 5: '#ef4444', 6: '#8b5cf6',
            7: '#3b82f6', 8: '#14b8a6', 9: '#f97316'
        };
        return colors[id] || '#667eea';
    }

    document.getElementById('detailModal').addEventListener('click', (e) => {
        if (e.target.id === 'detailModal') closeModal();
    });
    </script>
</body>
</html>
