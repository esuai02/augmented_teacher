<?php
/**
 * WXSPERTA Neuron View (Vanilla)
 * - React 없이 SVG+JS로 W.X.S.P.E.R.T.A 네트워크 UI 구현
 * - 각 노드 클릭 시 해당 레이어의 최신 "현재값 + 근거"를 표시
 */
include_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/ai_agents/cards_data.php");
global $DB, $USER, $CFG;
require_login();

$agent_key = isset($_GET['agent_key']) ? (string)$_GET['agent_key'] : 'global';
$agent_key = trim($agent_key) !== '' ? trim($agent_key) : 'global';

// 카드 목록(선택 드롭다운용)
$agents = array_map(function($card) {
    return [
        'id' => $card['id'],
        'number' => $card['number'],
        'name' => $card['name'],
        'icon' => $card['icon'] ?? '🎯',
        'category' => $card['category'] ?? ''
    ];
}, $cards_data);

// 최신 레이어 값 로드 (있으면)
$layers = ['worldView','context','structure','process','execution','reflection','transfer','abstraction'];
$latest = [];
try {
    // 테이블 존재 확인 (prefix 고려)
    $tables = $DB->get_tables();
    $prefix = (isset($CFG) && isset($CFG->prefix) && $CFG->prefix) ? $CFG->prefix : 'mdl_';
    $tableOk = in_array('wxsperta_conversation_layers', $tables) || in_array($prefix . 'wxsperta_conversation_layers', $tables);

    if ($tableOk) {
        $rows = $DB->get_records_sql("
            SELECT layer, layer_content, extracted_from, created_at
            FROM {wxsperta_conversation_layers}
            WHERE user_id = ? AND agent_key = ?
            ORDER BY created_at DESC
        ", [$USER->id, $agent_key]);

        foreach ($rows as $r) {
            $layer = (string)$r->layer;
            if (!in_array($layer, $layers, true)) continue;
            if (isset($latest[$layer])) continue; // 최신 1개만
            $latest[$layer] = [
                'content' => (string)$r->layer_content,
                'evidence' => (string)$r->extracted_from,
                'created_at' => (int)$r->created_at
            ];
        }
    }
} catch (Exception $e) {
    // 무시 (UI는 뜨고, 데이터만 비어있게)
}

$payload = [
    'user_id' => (int)$USER->id,
    'agent_key' => $agent_key,
    'agents' => array_values($agents),
    'latest' => $latest
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>W.X.S.P.E.R.T.A - Neuron</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg0:#0a0a0f;
      --bg1:#1a1a2e;
      --bg2:#0f0f1a;
      --text:#e0e0e0;
      --muted:#888;
      --line:#333;
    }
    *{box-sizing:border-box;}
    html,body{height:100%;}
    body{
      margin:0;
      font-family:"Noto Sans KR",-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;
      background:linear-gradient(135deg,var(--bg0) 0%, var(--bg1) 50%, var(--bg2) 100%);
      color:var(--text);
      overflow:hidden;
    }
    .wrap{height:100%; display:flex; overflow:hidden;}
    .left{
      flex: 1 1 auto;
      transition: flex 0.5s cubic-bezier(0.4,0,0.2,1);
      position:relative;
      padding: 1.5rem 1.5rem 1rem;
      min-width: 0;
    }
    .right{
      flex: 0 0 0%;
      transition: flex 0.5s cubic-bezier(0.4,0,0.2,1);
      background: linear-gradient(180deg, rgba(20,20,30,0.95) 0%, rgba(10,10,15,0.98) 100%);
      border-left: 0;
      overflow:hidden;
      display:flex;
      flex-direction:column;
      min-width: 0;
    }
    .right.open{flex: 0 0 45%; border-left: 1px solid var(--line);}
    .left.shrink{flex: 0 0 55%;}

    .header{
      text-align:center;
      margin-bottom: 0.75rem;
    }
    .title{
      font-size: 2.1rem;
      font-weight: 200;
      letter-spacing: 0.35em;
      margin: 0 0 0.25rem 0;
      background: linear-gradient(90deg, #FF6B6B, #4ECDC4, #45B7D1, #96CEB4, #FFEAA7, #DDA0DD, #F8B500, #E056FD);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .subtitle{
      font-size:0.8rem;
      color: var(--muted);
      letter-spacing: 0.18em;
      margin:0;
    }

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap: 0.75rem;
      margin: 0.75rem 0 0.25rem;
      padding: 0 0.25rem;
    }
    .select{
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      color: var(--text);
      padding: 0.55rem 0.7rem;
      border-radius: 10px;
      outline: none;
      max-width: 340px;
    }
    .hint{
      font-size: 0.75rem;
      color: #777;
      white-space: nowrap;
      overflow:hidden;
      text-overflow: ellipsis;
    }

    .svg-wrap{
      width:100%;
      height: calc(100vh - 180px);
      max-height: 620px;
      display:flex;
      justify-content:center;
      align-items:center;
    }
    svg{width:100%; height:100%;}

    .legend{
      display:flex;
      justify-content:center;
      gap: 1.5rem;
      font-size: 0.75rem;
      color:#666;
      margin-top: 0.5rem;
      flex-wrap:wrap;
    }
    .legend span{white-space:nowrap;}

    .panel{
      position:relative;
      height:100%;
      overflow-y:auto;
      padding: 1.25rem 1.5rem;
    }
    .close{
      position:absolute;
      top: 0.75rem;
      right: 0.75rem;
      background:none;
      border:none;
      color:#666;
      font-size: 1.6rem;
      cursor:pointer;
      padding: 0.25rem 0.5rem;
      line-height: 1;
    }
    .node-head{
      border-bottom: 2px solid #444;
      padding-bottom: 1rem;
      margin-bottom: 1rem;
      display:flex;
      align-items:center;
      gap: 0.9rem;
    }
    .node-icon{
      font-size: 2.6rem;
      line-height: 1;
      filter: drop-shadow(0 0 18px rgba(255,255,255,0.08));
    }
    .node-title{
      margin:0;
      font-size: 1.6rem;
      font-weight: 300;
      letter-spacing: 0.02em;
    }
    .node-sub{
      margin: 0.25rem 0 0 0;
      font-size: 0.82rem;
      color: #888;
      letter-spacing: 0.08em;
    }
    .card{
      background: rgba(255,255,255,0.03);
      border-radius: 12px;
      padding: 1.1rem 1.1rem;
      border: 1px solid rgba(255,255,255,0.05);
      margin-bottom: 1rem;
    }
    .card h3{
      font-size: 0.75rem;
      letter-spacing: 0.15em;
      margin: 0 0 0.65rem 0;
      font-weight: 600;
    }
    .card p{
      margin:0;
      font-size: 0.95rem;
      line-height: 1.75;
      color:#ccc;
      white-space: pre-wrap;
    }
    .card.kpi{
      background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, transparent 100%);
    }
    .badge{
      display:inline-flex;
      align-items:center;
      gap: 0.5rem;
      font-size: 0.72rem;
      color:#aaa;
      border: 1px solid rgba(255,255,255,0.08);
      padding: 0.35rem 0.55rem;
      border-radius: 999px;
      background: rgba(0,0,0,0.25);
      margin-bottom: 0.75rem;
    }
    .badge strong{color:#fff; font-weight:600;}
    .small{
      font-size: 0.78rem;
      color:#9a9a9a;
      line-height:1.6;
      white-space: pre-wrap;
      word-break: break-word;
    }

    @media (max-width: 1100px){
      .right.open{flex-basis: 52%;}
      .left.shrink{flex-basis: 48%;}
    }
    @media (max-width: 860px){
      body{overflow:auto;}
      .wrap{flex-direction:column; overflow:auto;}
      .left, .right, .right.open{flex: 0 0 auto;}
      .svg-wrap{height: 420px;}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div id="leftPane" class="left">
      <div class="header">
        <h1 class="title">W.X.S.P.E.R.T.A</h1>
        <p class="subtitle">상태+흐름으로 성장하는 “나의 지도”</p>
      </div>

      <div class="topbar">
        <select id="agentSelect" class="select" title="에이전트 선택"></select>
        <div id="hint" class="hint"></div>
      </div>

      <div class="svg-wrap">
        <svg id="neuronSvg" viewBox="0 0 100 100" aria-label="WXSPERTA neuron network">
          <defs id="defs"></defs>
          <g id="connGroup"></g>
          <g id="nodeGroup"></g>
        </svg>
      </div>

      <div class="legend">
        <span>━━ 순차 흐름</span>
        <span>╴╴ 피드백 루프</span>
        <span>┄┄ 리소스 요청</span>
      </div>
    </div>

    <div id="rightPane" class="right">
      <div id="panel" class="panel"></div>
    </div>
  </div>

  <script>
    const DATA = <?php echo json_encode($payload, JSON_UNESCAPED_UNICODE); ?>;

    // WXS PERTA 레이어 매핑
    const layerKeyMap = {
      W: 'worldView',
      X: 'context',
      S: 'structure',
      P: 'process',
      E: 'execution',
      R: 'reflection',
      T: 'transfer',
      A: 'abstraction'
    };

    const neuronData = {
      W: { name: "나침반(왜)", subtitle: "방향", color: "#FF6B6B", glow: "rgba(255, 107, 107, 0.6)", description: "너한테 중요한 기준/가치/의미(왜 하는지)", will: "방향이 흔들릴 때 ‘왜’를 다시 잡아줘", function: "대화의 방향을 잡아주는 기준점", icon: "◉" },
      X: { name: "지금 상황", subtitle: "현재", color: "#4ECDC4", glow: "rgba(78, 205, 196, 0.6)", description: "지금 조건/환경/시간/에너지/막힘", will: "지금 상황을 한 줄로 정리해서 길을 열어줘", function: "현실 조건을 깔끔하게 붙잡는 역할", icon: "◈" },
      S: { name: "핵심 정리", subtitle: "정리", color: "#45B7D1", glow: "rgba(69, 183, 209, 0.6)", description: "중요한 걸 2~3개로 묶기(지도 만들기)", will: "복잡한 걸 ‘핵심만’ 남기게 도와줘", function: "생각을 구조로 바꿔주는 역할", icon: "◇" },
      P: { name: "순서(방법)", subtitle: "단계", color: "#96CEB4", glow: "rgba(150, 206, 180, 0.6)", description: "어떻게 해볼지 순서 만들기", will: "너한테 맞는 ‘다음 순서’를 만들어줘", function: "무리 없는 절차를 잡아주는 역할", icon: "◆" },
      E: { name: "한 칸 실천", subtitle: "실행", color: "#FFEAA7", glow: "rgba(255, 234, 167, 0.6)", description: "지금 바로 할 수 있는 한 걸음", will: "5분짜리라도 ‘진짜로’ 움직이게 해줘", function: "실제로 해보게 만드는 역할", icon: "▣" },
      R: { name: "되돌아보기", subtitle: "깨달음", color: "#DDA0DD", glow: "rgba(221, 160, 221, 0.6)", description: "해보고 난 뒤의 느낌/배움/수정", will: "억지 말고, 배운 걸 자연스럽게 남기게 해줘", function: "경험을 배움으로 바꾸는 역할", icon: "◎" },
      T: { name: "확장/공유", subtitle: "퍼뜨리기", color: "#F8B500", glow: "rgba(248, 181, 0, 0.6)", description: "다른 과목/상황에도 써먹기", will: "한 번 된 걸 다른 데도 이어지게 해줘", function: "적용 범위를 넓히는 역할", icon: "◐" },
      A: { name: "한 문장 원리", subtitle: "요약", color: "#E056FD", glow: "rgba(224, 86, 253, 0.6)", description: "핵심을 한 문장으로 뽑기(내 룰 만들기)", will: "다음에도 쓸 수 있게 ‘내 룰’로 남겨줘", function: "본질을 뽑아 재사용 가능하게 만드는 역할", icon: "✧" },
    };

    const connections = [
      { from: 'W', to: 'X', type: 'primary' },
      { from: 'X', to: 'S', type: 'primary' },
      { from: 'S', to: 'P', type: 'primary' },
      { from: 'P', to: 'E', type: 'primary' },
      { from: 'E', to: 'R', type: 'primary' },
      { from: 'R', to: 'T', type: 'primary' },
      { from: 'T', to: 'A', type: 'primary' },
      { from: 'A', to: 'W', type: 'feedback' },
      { from: 'R', to: 'S', type: 'feedback' },
      { from: 'R', to: 'P', type: 'feedback' },
      { from: 'R', to: 'E', type: 'feedback' },
      { from: 'E', to: 'S', type: 'request' },
    ];

    const nodePositions = {
      W: { x: 50, y: 15 },
      X: { x: 85, y: 25 },
      S: { x: 90, y: 50 },
      P: { x: 75, y: 75 },
      E: { x: 50, y: 85 },
      R: { x: 25, y: 75 },
      T: { x: 10, y: 50 },
      A: { x: 15, y: 25 },
    };

    const svg = document.getElementById('neuronSvg');
    const defs = document.getElementById('defs');
    const connGroup = document.getElementById('connGroup');
    const nodeGroup = document.getElementById('nodeGroup');
    const rightPane = document.getElementById('rightPane');
    const leftPane = document.getElementById('leftPane');
    const panel = document.getElementById('panel');
    const select = document.getElementById('agentSelect');
    const hint = document.getElementById('hint');

    let selectedNode = null;
    let activeConnections = new Set();
    let pulseNode = null;

    function esc(s){
      return String(s ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#039;');
    }

    function formatTime(ts){
      if (!ts) return '';
      const d = new Date(ts * 1000);
      return d.toLocaleString('ko-KR', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit' });
    }

    function stableControl(from, to){
      // 랜덤 대신 pair 해시로 안정적인 곡률 생성
      const a = nodePositions[from], b = nodePositions[to];
      const midX = (a.x + b.x) / 2;
      const midY = (a.y + b.y) / 2;
      const seed = (from.charCodeAt(0) * 31 + to.charCodeAt(0) * 17) % 11; // 0..10
      const offset = (seed - 5) * 0.6; // -3..+3
      return { cx: midX + offset, cy: midY - offset };
    }

    function getConnectionPath(from, to){
      const start = nodePositions[from];
      const end = nodePositions[to];
      const c = stableControl(from, to);
      return `M ${start.x} ${start.y} Q ${c.cx} ${c.cy} ${end.x} ${end.y}`;
    }

    function buildDefs(){
      defs.innerHTML = '';
      for (const [key, data] of Object.entries(neuronData)){
        const rg = document.createElementNS('http://www.w3.org/2000/svg','radialGradient');
        rg.setAttribute('id', `gradient-${key}`);
        const s0 = document.createElementNS('http://www.w3.org/2000/svg','stop');
        s0.setAttribute('offset','0%');
        s0.setAttribute('stop-color', data.color);
        s0.setAttribute('stop-opacity','0.9');
        const s1 = document.createElementNS('http://www.w3.org/2000/svg','stop');
        s1.setAttribute('offset','100%');
        s1.setAttribute('stop-color', data.color);
        s1.setAttribute('stop-opacity','0.3');
        rg.appendChild(s0); rg.appendChild(s1);
        defs.appendChild(rg);
      }

      const filter = document.createElementNS('http://www.w3.org/2000/svg','filter');
      filter.setAttribute('id','glow');
      const blur = document.createElementNS('http://www.w3.org/2000/svg','feGaussianBlur');
      blur.setAttribute('stdDeviation','0.5');
      blur.setAttribute('result','coloredBlur');
      const merge = document.createElementNS('http://www.w3.org/2000/svg','feMerge');
      const mn1 = document.createElementNS('http://www.w3.org/2000/svg','feMergeNode');
      mn1.setAttribute('in','coloredBlur');
      const mn2 = document.createElementNS('http://www.w3.org/2000/svg','feMergeNode');
      mn2.setAttribute('in','SourceGraphic');
      merge.appendChild(mn1); merge.appendChild(mn2);
      filter.appendChild(blur); filter.appendChild(merge);
      defs.appendChild(filter);
    }

    function renderConnections(){
      connGroup.innerHTML = '';
      connections.forEach((conn, idx) => {
        const isActive = activeConnections.has(`${conn.from}-${conn.to}`) || activeConnections.has(`${conn.to}-${conn.from}`);
        const fromData = neuronData[conn.from];

        const g = document.createElementNS('http://www.w3.org/2000/svg','g');
        const path = document.createElementNS('http://www.w3.org/2000/svg','path');
        path.setAttribute('d', getConnectionPath(conn.from, conn.to));
        path.setAttribute('fill','none');
        path.setAttribute('stroke', isActive ? fromData.color : '#333');
        path.setAttribute('stroke-width', isActive ? '0.4' : '0.2');
        path.setAttribute('opacity', isActive ? '1' : '0.4');
        if (conn.type === 'feedback') path.setAttribute('stroke-dasharray','1,1');
        if (conn.type === 'request') path.setAttribute('stroke-dasharray','0.5,0.5');
        g.appendChild(path);

        if (isActive) {
          const dot = document.createElementNS('http://www.w3.org/2000/svg','circle');
          dot.setAttribute('r','0.8');
          dot.setAttribute('fill', fromData.color);
          const motion = document.createElementNS('http://www.w3.org/2000/svg','animateMotion');
          motion.setAttribute('dur','1.5s');
          motion.setAttribute('repeatCount','indefinite');
          motion.setAttribute('path', getConnectionPath(conn.from, conn.to));
          dot.appendChild(motion);
          g.appendChild(dot);
        }

        connGroup.appendChild(g);
      });
    }

    function renderNodes(){
      nodeGroup.innerHTML = '';
      for (const [key, pos] of Object.entries(nodePositions)){
        const data = neuronData[key];
        const isSelected = selectedNode === key;
        const isPulsing = pulseNode === key;

        const g = document.createElementNS('http://www.w3.org/2000/svg','g');
        g.setAttribute('transform', `translate(${pos.x}, ${pos.y})`);
        g.style.cursor = 'pointer';
        g.addEventListener('click', () => handleNodeClick(key));

        // glow ring
        const outer = document.createElementNS('http://www.w3.org/2000/svg','circle');
        outer.setAttribute('r', isSelected ? '8' : (isPulsing ? '7' : '5'));
        outer.setAttribute('fill','none');
        outer.setAttribute('stroke', data.color);
        outer.setAttribute('stroke-width','0.3');
        outer.setAttribute('opacity', isSelected ? '0.8' : (isPulsing ? '0.6' : '0.3'));
        outer.style.filter = 'url(#glow)';
        g.appendChild(outer);

        // pulse ring (SMIL)
        if (isSelected || isPulsing){
          const pr = document.createElementNS('http://www.w3.org/2000/svg','circle');
          pr.setAttribute('r','6');
          pr.setAttribute('fill','none');
          pr.setAttribute('stroke', data.color);
          pr.setAttribute('stroke-width','0.2');
          pr.setAttribute('opacity','0');
          const a1 = document.createElementNS('http://www.w3.org/2000/svg','animate');
          a1.setAttribute('attributeName','r');
          a1.setAttribute('from','5');
          a1.setAttribute('to','12');
          a1.setAttribute('dur','1s');
          a1.setAttribute('repeatCount','indefinite');
          const a2 = document.createElementNS('http://www.w3.org/2000/svg','animate');
          a2.setAttribute('attributeName','opacity');
          a2.setAttribute('from','0.6');
          a2.setAttribute('to','0');
          a2.setAttribute('dur','1s');
          a2.setAttribute('repeatCount','indefinite');
          pr.appendChild(a1); pr.appendChild(a2);
          g.appendChild(pr);
        }

        // main node
        const main = document.createElementNS('http://www.w3.org/2000/svg','circle');
        main.setAttribute('r', isSelected ? '5' : '4');
        main.setAttribute('fill', `url(#gradient-${key})`);
        main.setAttribute('stroke', data.color);
        main.setAttribute('stroke-width','0.3');
        if (isSelected) main.style.filter = 'url(#glow)';
        g.appendChild(main);

        // letter
        const t = document.createElementNS('http://www.w3.org/2000/svg','text');
        t.setAttribute('text-anchor','middle');
        t.setAttribute('dominant-baseline','central');
        t.setAttribute('fill','#fff');
        t.setAttribute('font-size','2.5');
        t.setAttribute('font-weight','bold');
        t.textContent = key;
        g.appendChild(t);

        // name
        const tn = document.createElementNS('http://www.w3.org/2000/svg','text');
        tn.setAttribute('y','8');
        tn.setAttribute('text-anchor','middle');
        tn.setAttribute('fill', data.color);
        tn.setAttribute('font-size','1.8');
        tn.setAttribute('opacity','0.9');
        tn.textContent = data.name;
        g.appendChild(tn);

        nodeGroup.appendChild(g);
      }
    }

    function handleNodeClick(nodeKey){
      selectedNode = nodeKey;
      activeConnections = new Set(connections
        .filter(c => c.from === nodeKey || c.to === nodeKey)
        .map(c => `${c.from}-${c.to}`));
      rightPane.classList.add('open');
      leftPane.classList.add('shrink');
      render();
      renderPanel();
    }

    function closePanel(){
      selectedNode = null;
      activeConnections = new Set();
      rightPane.classList.remove('open');
      leftPane.classList.remove('shrink');
      panel.innerHTML = '';
      render();
    }

    function renderPanel(){
      if (!selectedNode) return;
      const d = neuronData[selectedNode];
      const layerKey = layerKeyMap[selectedNode];
      const latest = DATA.latest && DATA.latest[layerKey] ? DATA.latest[layerKey] : null;
      const current = latest && latest.content ? latest.content : '아직 데이터가 부족해. 대화를 더 쌓아보자.';
      const evidence = latest && latest.evidence ? latest.evidence : '근거 없음';
      const ts = latest && latest.created_at ? latest.created_at : null;

      panel.innerHTML = `
        <button class="close" title="닫기">×</button>
        <div class="badge"><strong>내 성장 조각</strong> ${esc(DATA.agent_key)} · <strong>레이어</strong> ${esc(layerKey)} ${ts ? `· ${esc(formatTime(ts))}` : ''}</div>
        <div class="node-head" style="border-bottom-color:${esc(d.color)};">
          <div class="node-icon" style="color:${esc(d.color)}; text-shadow: 0 0 20px ${esc(d.glow)};">${esc(d.icon)}</div>
          <div>
            <h2 class="node-title" style="color:${esc(d.color)};">${esc(selectedNode)} · ${esc(d.name)}</h2>
            <p class="node-sub">${esc(d.subtitle)}</p>
          </div>
        </div>

        <div class="card kpi" style="border-color:${esc(d.color)}30; background: linear-gradient(135deg, ${esc(d.color)}10 0%, transparent 100%);">
          <h3 style="color:${esc(d.color)};">◆ 현재값 (State)</h3>
          <p>${esc(current)}</p>
        </div>

        <div class="card">
          <h3 style="color:${esc(d.color)};">◇ 의지 (Will)</h3>
          <p>${esc(d.will)}</p>
        </div>

        <div class="card">
          <h3 style="color:${esc(d.color)};">○ 시스템 역할</h3>
          <p>${esc(d.function)}</p>
        </div>

        <div class="card">
          <h3 style="color:#888;">◎ 최근 근거 (Evidence)</h3>
          <div class="small">${esc(evidence)}</div>
        </div>

        <div class="card" style="background: rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.06);">
          <h3 style="color:#888;">◎ 연결 네트워크</h3>
          <div class="small">${renderConnList(selectedNode)}</div>
        </div>
      `;
      panel.querySelector('.close').addEventListener('click', closePanel);
    }

    function renderConnList(nodeKey){
      const list = connections.filter(c => c.from === nodeKey || c.to === nodeKey);
      if (!list.length) return '연결 없음';
      return list.map(conn => {
        const isOutgoing = conn.from === nodeKey;
        const other = isOutgoing ? conn.to : conn.from;
        const typeLabel = conn.type === 'primary' ? '→' : conn.type === 'feedback' ? '↺' : '⇠';
        const typeName = conn.type === 'primary' ? '순차' : conn.type === 'feedback' ? '피드백' : '요청';
        return `${typeLabel} ${other} (${neuronData[other].name}) · ${typeName}`;
      }).join('\n');
    }

    function render(){
      renderConnections();
      renderNodes();
    }

    function initSelect(){
      // 옵션 채우기(번호 순)
      const ordered = [...DATA.agents].sort((a,b) => (a.number||999)-(b.number||999));
      select.innerHTML = '';
      ordered.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = `${a.icon || '🎯'} ${a.name} (${a.number})`;
        if (a.id === DATA.agent_key) opt.selected = true;
        select.appendChild(opt);
      });
      const found = ordered.find(a => a.id === DATA.agent_key);
      hint.textContent = found ? `현재 내 성장 조각: ${found.icon || '🎯'} ${found.name}` : `현재 내 성장 조각: ${DATA.agent_key}`;

      select.addEventListener('change', () => {
        const k = select.value;
        const url = new URL(location.href);
        url.searchParams.set('agent_key', k);
        location.href = url.toString();
      });
    }

    function startPulse(){
      setInterval(() => {
        const keys = Object.keys(neuronData);
        const k = keys[Math.floor(Math.random()*keys.length)];
        pulseNode = k;
        renderNodes();
        setTimeout(() => { pulseNode = null; renderNodes(); }, 800);
      }, 2000);
    }

    buildDefs();
    initSelect();
    render();
    startPulse();
  </script>
</body>
</html>


