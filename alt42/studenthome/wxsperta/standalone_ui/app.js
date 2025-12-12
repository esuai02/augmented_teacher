/* Standalone UI (Moodle UI와 분리)
 * - 인증/DB는 Moodle 세션 그대로 사용 (same-origin 쿠키)
 */

const state = {
  agents: [],
  filteredAgents: [],
  selectedAgent: null,
  category: 'all',
  user: null,
  history: [],
  isSending: false,
  suggestions: [],
  conversationId: '',
  approvals: [],
};

const el = (id) => document.getElementById(id);

function getParam(key, fallback = '') {
  try {
    const url = new URL(window.location.href);
    return url.searchParams.get(key) || fallback;
  } catch (e) {
    return fallback;
  }
}

async function fetchJson(url, options = {}) {
  const res = await fetch(url, {
    credentials: 'same-origin',
    ...options,
  });

  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch (e) {
    // Moodle 로그인 페이지로 리다이렉트/HTML이 온 경우
    return { success: false, error: '로그인이 필요해 - standalone_ui/app.js:fetchJson', raw: text };
  }
}

function setLoginNeededUI() {
  el('userBadge').textContent = '로그인이 필요해';
  el('userBadge').style.borderColor = 'rgba(239,68,68,0.6)';
  el('loginLink').style.display = 'inline';
}

async function loadUser() {
  const data = await fetchJson('../standalone_api.php?action=get_user_state');
  if (!data.success) {
    setLoginNeededUI();
    return;
  }
  state.user = data.user;
  el('userBadge').textContent = `사용자 #${state.user.id} · ${state.user.role || 'student'}`;
}

async function loadAgents() {
  const data = await fetchJson('../standalone_api.php?action=get_agents');
  if (!data.success) {
    setLoginNeededUI();
    return;
  }
  state.agents = data.agents || [];
  state.filteredAgents = [...state.agents];
  renderAgents();
}

function categoryLabel(category) {
  switch (category) {
    case 'future_design': return '항해 지도';
    case 'execution': return '미션 센터';
    case 'branding': return '나의 깃발';
    case 'knowledge_management': return '자원 창고';
    default: return category || '';
  }
}

function applyFilters() {
  const q = (el('searchInput').value || '').trim().toLowerCase();
  const cat = state.category;

  state.filteredAgents = state.agents.filter((a) => {
    const matchesCat = cat === 'all' ? true : a.category === cat;
    const matchesQ =
      !q ||
      (a.name || '').toLowerCase().includes(q) ||
      (a.description || '').toLowerCase().includes(q) ||
      (a.subtitle || '').toLowerCase().includes(q);
    return matchesCat && matchesQ;
  });
  renderAgents();
}

function renderAgents() {
  const grid = el('agentsGrid');
  grid.innerHTML = '';

  state.filteredAgents.forEach((a) => {
    const card = document.createElement('div');
    card.className = 'card' + (state.selectedAgent && state.selectedAgent.id === a.id ? ' active' : '');
    card.innerHTML = `
      <div class="card-top">
        <div class="card-icon">${a.icon || '🎯'}</div>
        <div>
          <div class="card-name">${a.name}</div>
          <div class="card-sub">${categoryLabel(a.category)} · #${a.number}</div>
        </div>
      </div>
      <div class="card-sub">${escapeHtml(shorten(a.subtitle || a.description || '', 80))}</div>
    `;
    card.addEventListener('click', () => selectAgent(a));
    grid.appendChild(card);
  });
}

function renderChat() {
  const chat = el('chatBox');
  chat.innerHTML = '';

  if (!state.selectedAgent) {
    chat.innerHTML = `<div class="chat-empty">에이전트를 선택하면 여기서 대화가 시작돼.</div>`;
    renderSuggestions([]);
    renderApprovalBox();
    return;
  }

  if (!state.history.length) {
    chat.innerHTML = `<div class="chat-empty">먼저 한 마디 해볼래?</div>`;
    renderSuggestions(state.suggestions || []);
    renderApprovalBox();
    return;
  }

  for (const m of state.history) {
    const row = document.createElement('div');
    row.className = `msg ${m.role === 'user' ? 'user' : 'agent'}`;
    const avatar = m.role === 'user' ? '' : `<div class="avatar">${state.selectedAgent.icon || '🤖'}</div>`;
    row.innerHTML = `
      ${avatar}
      <div class="bubble">${escapeHtml(m.content || '')}</div>
    `;
    chat.appendChild(row);
  }

  chat.scrollTop = chat.scrollHeight;
  renderSuggestions(state.suggestions || []);
  renderApprovalBox();
}

function renderSuggestions(list) {
  const box = el('suggestions');
  if (!box) return;
  box.innerHTML = '';

  const suggestions = Array.isArray(list) ? list : [];
  if (!suggestions.length || !state.selectedAgent) {
    box.style.display = 'none';
    return;
  }
  box.style.display = 'flex';

  suggestions.slice(0, 3).forEach((txt, idx) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'suggestion-btn' + (idx === 0 ? ' primary' : '');
    btn.textContent = txt;
    btn.disabled = state.isSending;
    btn.addEventListener('click', () => {
      if (state.isSending) return;
      el('chatInput').value = txt;
      sendMessage();
    });
    box.appendChild(btn);
  });
}

function layerFriendlyName(layer) {
  if (layer === 'worldView') return '나침반(왜)';
  if (layer === 'abstraction') return '한 문장 원리';
  return layer || '';
}

async function loadApprovals() {
  if (!state.selectedAgent) {
    state.approvals = [];
    return;
  }
  const agentKey = state.selectedAgent.id;
  const data = await fetchJson(`../standalone_api.php?action=get_pending_layer_approvals&agent_key=${encodeURIComponent(agentKey)}`);
  if (!data.success) {
    state.approvals = [];
    return;
  }
  state.approvals = data.approvals || [];
}

function renderApprovalBox() {
  const box = el('approvalBox');
  if (!box) return;

  if (!state.selectedAgent) {
    box.style.display = 'none';
    box.innerHTML = '';
    return;
  }

  const approvals = Array.isArray(state.approvals) ? state.approvals : [];
  const item =
    approvals.find((a) => a && a.agent_key === state.selectedAgent.id && a.conversation_id === state.conversationId) ||
    approvals.find((a) => a && a.agent_key === state.selectedAgent.id) ||
    approvals[0];

  if (!item) {
    box.style.display = 'none';
    box.innerHTML = '';
    return;
  }

  box.style.display = 'block';
  box.innerHTML = `
    <div class="approval-card">
      <div class="approval-title">
        <div>확인할 문장 · <strong>${escapeHtml(layerFriendlyName(item.layer))}</strong></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.55);">맞으면 ‘맞아’, 아니면 살짝 고쳐도 돼</div>
      </div>
      <textarea id="approvalText" class="approval-text">${escapeHtml(item.proposed_text || '')}</textarea>
      <div class="approval-actions">
        <button class="approval-btn ok" id="approvalYesBtn" type="button">맞아</button>
        <button class="approval-btn ok" id="approvalEditBtn" type="button">이대로(또는 수정해서) 저장</button>
        <button class="approval-btn no" id="approvalNoBtn" type="button">아니야</button>
        <button class="approval-btn later" id="approvalLaterBtn" type="button">나중에</button>
      </div>
    </div>
  `;

  const textEl = document.getElementById('approvalText');
  const yesBtn = document.getElementById('approvalYesBtn');
  const editBtn = document.getElementById('approvalEditBtn');
  const noBtn = document.getElementById('approvalNoBtn');
  const laterBtn = document.getElementById('approvalLaterBtn');

  const submit = async (decision, text) => {
    const form = new FormData();
    form.append('action', 'submit_layer_approval');
    form.append('approval_id', String(item.id));
    form.append('decision', decision);
    form.append('text', text || '');

    const res = await fetchJson('../standalone_api.php', { method: 'POST', body: form });
    if (!res.success) {
      state.history.push({ role: 'assistant', content: res.error || '오류가 났어. 잠깐만.' });
      renderChat();
      return;
    }
    await loadApprovals();
    renderChat();
  };

  yesBtn.addEventListener('click', () => submit('approved', ''));
  editBtn.addEventListener('click', () => submit('approved', (textEl && textEl.value) ? textEl.value.trim() : ''));
  noBtn.addEventListener('click', () => submit('rejected', ''));
  laterBtn.addEventListener('click', () => submit('skipped', ''));
}

async function selectAgent(agent) {
  state.selectedAgent = agent;
  state.history = [];
  state.isSending = false;
  state.suggestions = [];
  state.conversationId = '';
  state.approvals = [];
  el('chatSubtitle').textContent = `${agent.icon || '🤖'} ${agent.name} · ${categoryLabel(agent.category)}`;
  el('chatInput').disabled = false;
  el('sendBtn').disabled = false;
  renderAgents();
  renderChat();

  // 1) 대화 스레드 생성/재개
  const conv = await fetchJson(`../standalone_api.php?action=create_or_resume_conversation&agent_key=${encodeURIComponent(agent.id)}`);
  if (!conv.success) {
    setLoginNeededUI();
    state.history.push({ role: 'assistant', content: conv.error || '오류가 났어. 잠깐만.' });
    renderChat();
    return;
  }
  state.conversationId = conv.conversation_id || '';
  await loadApprovals();

  // 2) 기존 메시지 로드(있으면 재개)
  if (state.conversationId) {
    const prev = await fetchJson(`../standalone_api.php?action=get_conversation_messages&conversation_id=${encodeURIComponent(state.conversationId)}`);
    if (prev.success && Array.isArray(prev.messages) && prev.messages.length) {
      state.history = prev.messages.map((m) => ({ role: m.role, content: m.content }));
      renderChat();
      return;
    }
  }

  // 3) 없으면 초기 메시지
  const data = await fetchJson(`../agent_chat_api.php?action=get_initial&agent_id=${encodeURIComponent(agent.id)}`);
  if (!data.success) {
    setLoginNeededUI();
    state.history.push({ role: 'assistant', content: data.error || '오류가 났어. 잠깐만.' });
    renderChat();
    return;
  }
  state.history.push({ role: 'assistant', content: data.message });
  state.suggestions = data.suggestions || [];
  await loadApprovals();
  renderChat();
}

async function sendMessage() {
  if (!state.selectedAgent || state.isSending) return;

  const input = el('chatInput');
  const msg = (input.value || '').trim();
  if (!msg) return;

  state.isSending = true;
  input.value = '';
  state.history.push({ role: 'user', content: msg });
  state.suggestions = [];
  renderChat();

  // typing
  state.history.push({ role: 'assistant', content: '...' });
  renderChat();

  const historyForApi = state.history
    .filter((m) => m.content !== '...')
    .map((m) => ({ role: m.role, content: m.content }));

  const formData = new FormData();
  formData.append('action', 'send_message');
  formData.append('agent_id', state.selectedAgent.id);
  formData.append('message', msg);
  formData.append('history', JSON.stringify(historyForApi));
  if (state.conversationId) formData.append('conversation_id', state.conversationId);

  const data = await fetchJson('../agent_chat_api.php', { method: 'POST', body: formData });

  // remove typing
  state.history = state.history.filter((m) => m.content !== '...');

  if (!data.success) {
    if ((data.error || '').includes('로그인이 필요해')) setLoginNeededUI();
    state.history.push({ role: 'assistant', content: data.error || '오류가 났어. 다시 해볼래?' });
    state.isSending = false;
    renderChat();
    return;
  }

  state.history.push({ role: 'assistant', content: data.message });
  state.suggestions = data.suggestions || [];
  if (data.conversation_id) state.conversationId = data.conversation_id;
  await loadApprovals();
  state.isSending = false;
  renderChat();
}

function bindEvents() {
  el('searchInput').addEventListener('input', applyFilters);
  document.querySelectorAll('.chip').forEach((c) => {
    c.addEventListener('click', () => {
      document.querySelectorAll('.chip').forEach((x) => x.classList.remove('active'));
      c.classList.add('active');
      state.category = c.getAttribute('data-category') || 'all';
      applyFilters();
    });
  });

  el('sendBtn').addEventListener('click', sendMessage);
  el('chatInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') sendMessage();
  });
}

function shorten(s, n) {
  if (!s) return '';
  return s.length > n ? s.slice(0, n - 1) + '…' : s;
}

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

async function main() {
  bindEvents();
  await loadUser();
  await loadAgents();

  // URL로 바로 진입(글로벌/특정 에이전트)
  const agentId = getParam('agent_id', '');
  if (agentId) {
    const found = state.agents.find((a) => a.id === agentId);
    if (found) selectAgent(found);
  }
}

main();


