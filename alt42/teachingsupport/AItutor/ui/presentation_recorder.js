/**
 * 학생 발표하기(단독 발표) 모듈
 * - MediaRecorder로 음성 수집 (서버에 음성 파일 저장 금지)
 * - Whisper STT 변환 → 발표 텍스트 저장 → 텍스트 기반 페르소나 분석 → 선택 → quantum_modeling 이동
 *
 * 의존:
 * - window.ANALYSIS_ID, window.CONTENT_ID, window.TTS_CONFIG(containsType), window.STUDENT_ID
 * - learning_interface.php의 #presentationBtn/#presentationControls/#faqBubble
 */

(function() {
  'use strict';

  function $(id) { return document.getElementById(id); }

  function pad2(n) { return String(n).padStart(2, '0'); }
  function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${pad2(m)}:${pad2(s)}`;
  }

  function pickOne(arr) {
    if (!arr || !arr.length) return '';
    return arr[Math.floor(Math.random() * arr.length)];
  }

  // 중앙 얼굴 아이콘 말풍선(FAQ bubble) 재활용
  function showCenterBubble(text, label) {
    const bubble = $('faqBubble');
    const labelEl = $('faqBubbleLabel');
    const textEl = $('faqBubbleText');
    if (!bubble || !textEl) {
      // fallback
      if (typeof showFeedback === 'function') showFeedback(text);
      return;
    }
    if (labelEl) labelEl.textContent = label || '🎤 발표';
    textEl.textContent = text;
    bubble.classList.remove('hidden');
    bubble.style.animation = 'bubbleFadeIn 0.2s ease';
  }

  function hideCenterBubble() {
    const bubble = $('faqBubble');
    if (!bubble) return;
    bubble.style.animation = 'bubbleFadeIn 0.2s ease reverse';
    setTimeout(() => bubble.classList.add('hidden'), 200);
  }

  const MSG = {
    start: [
      '좋아! 지금부터 네가 선생님이야. 이 문제를 어떻게 풀었는지 설명해줘.',
      '발표 시작! 천천히, 네 말로 풀이 흐름을 설명해줘.',
      '자, 시작하자. 먼저 문제를 어떻게 읽었는지부터 말해볼래?'
    ],
    pause: [
      '잠깐 멈춰도 괜찮아. 생각 정리되면 다시 시작하자.',
      '오케이, 일시정지. 준비되면 이어서 설명해줘.',
      '숨 고르고 다시 가자. 재개 버튼 누르면 이어서!'
    ],
    resume: [
      '좋아, 이어서 설명해줘!',
      '재개! 어디까지 했는지부터 다시 잡아보자.',
      '오케이, 계속! 다음 단계로 넘어가볼까?'
    ],
    finish: [
      '발표 고마워! 이제 네 설명을 분석해볼게. 잠깐만 기다려줘.',
      '좋았어. 이제 자막으로 정리하고 취약한 부분을 찾아볼게.',
      '수고했어! 분석하고 인지맵으로 연결해줄게.'
    ],
    analyzing: [
      '분석 중... 잠깐만!',
      '자막 만들고 있어. 조금만 기다려줘.'
    ]
  };

  const PresentationRecorder = {
    state: {
      isActive: false,
      isPaused: false,
      startTs: 0,
      elapsedSec: 0,
      timerId: null,
      stream: null,
      recorder: null,
      chunks: [],
      mimeType: 'audio/webm',
      presentationId: null,
      analysis: null,
      selectedPersonaIds: [],
    },

    apiBase: '/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api',

    toggle() {
      if (!this.state.isActive) return this.start();
      // active이면 finish로 유도
      return this.finish();
    },

    async start() {
      try {
        const btnText = $('presentationBtnText');
        if (btnText) btnText.textContent = '발표중...';

        // UI
        var controls = $('presentationControls');
        if (controls) controls.classList.remove('hidden');

        showCenterBubble(pickOne(MSG.start), '📣 발표 시작');

        // 마이크 권한
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        this.state.stream = stream;

        // recorder
        const options = {};
        if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(this.state.mimeType)) {
          options.mimeType = this.state.mimeType;
        }
        const recorder = new MediaRecorder(stream, options);
        this.state.recorder = recorder;
        this.state.chunks = [];
        this.state.isActive = true;
        this.state.isPaused = false;
        this.state.startTs = Date.now();
        this.state.elapsedSec = 0;
        this._startTimer();

        recorder.ondataavailable = (e) => {
          if (e.data && e.data.size > 0) this.state.chunks.push(e.data);
        };

        recorder.onstop = async () => {
          try {
            await this._handleStop();
          } catch (err) {
            console.error('[PresentationRecorder] stop handler error:', err);
            showCenterBubble('오류가 발생했어. 다시 시도해줘.', '⚠️ 발표');
          }
        };

        recorder.start(250);
      } catch (err) {
        console.error('[PresentationRecorder] start error:', err);
        showCenterBubble('마이크 권한이 필요해. 브라우저 설정에서 허용해줘.', '⚠️ 발표');
        this._resetUi();
      }
    },

    pauseOrResume() {
      if (!this.state.isActive || !this.state.recorder) return;
      const pauseBtn = $('presentationPauseBtn');
      if (!this.state.isPaused) {
        try {
          this.state.recorder.pause();
          this.state.isPaused = true;
          this._stopTimer();
          if (pauseBtn) pauseBtn.textContent = '▶';
          showCenterBubble(pickOne(MSG.pause), '⏸ 일시정지');
        } catch (e) {
          console.error('[PresentationRecorder] pause error:', e);
        }
      } else {
        try {
          this.state.recorder.resume();
          this.state.isPaused = false;
          this._startTimer();
          if (pauseBtn) pauseBtn.textContent = '⏸';
          showCenterBubble(pickOne(MSG.resume), '▶ 재개');
        } catch (e) {
          console.error('[PresentationRecorder] resume error:', e);
        }
      }
    },

    finish() {
      if (!this.state.isActive || !this.state.recorder) return;
      showCenterBubble(pickOne(MSG.finish), '✅ 마무리');
      this._stopTimer();
      try {
        this.state.recorder.stop();
      } catch (e) {
        console.error('[PresentationRecorder] stop error:', e);
      }
      // stream close
      if (this.state.stream) {
        this.state.stream.getTracks().forEach(t => t.stop());
      }
    },

    async _handleStop() {
      const blob = new Blob(this.state.chunks, { type: this.state.mimeType });
      const durationSeconds = this.state.elapsedSec;

      // 1) create record
      const contentId = window.CONTENT_ID;
      var cfg = window.TTS_CONFIG || {};
      var contentsType = (cfg.contentsType != null ? cfg.contentsType : (cfg.contentstype != null ? cfg.contentstype : (cfg.contents_type != null ? cfg.contents_type : '')));
      const analysisId = window.ANALYSIS_ID;
      if (!contentsType) contentsType = 'unknown';
      const createRes = await this._postJson(`${this.apiBase}/save_presentation.php`, {
        action: 'create',
        analysis_id: analysisId,
        contentsid: contentId,
        contentstype: String(contentsType || ''),
        duration_seconds: durationSeconds
      });

      if (!createRes || !createRes.success || !createRes.presentation_id) {
        throw new Error((createRes && createRes.error) ? createRes.error : '발표 레코드 생성 실패');
      }
      this.state.presentationId = createRes.presentation_id;

      // 2) audio -> dataURL
      showCenterBubble(pickOne(MSG.analyzing), '🧠 STT');
      const dataUrl = await this._blobToDataURL(blob);

      // 3) STT
      const sttRes = await this._postJson(`${this.apiBase}/transcribe_presentation.php`, {
        audio_data: dataUrl
      });
      if (!sttRes || !sttRes.success || !sttRes.text) {
        throw new Error((sttRes && sttRes.error) ? sttRes.error : 'STT 실패');
      }

      const text = String(sttRes.text);

      // 4) save text
      await this._postJson(`${this.apiBase}/save_presentation.php`, {
        presentation_id: this.state.presentationId,
        presentation_text: text,
        duration_seconds: durationSeconds
      });

      // 5) analyze personas
      showCenterBubble('페르소나 분석 중... 잠깐만!', '📊 분석');
      const anaRes = await this._postJson(`${this.apiBase}/analyze_presentation.php`, {
        presentation_text: text
      });
      if (!anaRes || !anaRes.success || !anaRes.data) {
        throw new Error((anaRes && anaRes.error) ? anaRes.error : '페르소나 분석 실패');
      }
      this.state.analysis = anaRes.data;

      // 6) persist analysis
      const weakIds = Array.isArray(anaRes.data.weak_personas) ? anaRes.data.weak_personas.map(w => w.id) : [];
      await this._postJson(`${this.apiBase}/save_presentation.php`, {
        presentation_id: this.state.presentationId,
        analysis_json: anaRes.data,
        weak_personas: weakIds
      });

      // 7) show selection modal
      this._openPersonaModal();
      hideCenterBubble();
    },

    _openPersonaModal() {
      const overlay = $('presentationPersonaOverlay');
      const modal = $('presentationPersonaModal');
      const summary = $('presentationPersonaSummary');
      const list = $('presentationPersonaList');

      if (summary) summary.textContent = (this.state.analysis && (this.state.analysis.summary || this.state.analysis.coach_message)) || '분석 완료!';
      if (list) list.innerHTML = '';

      const weak = (this.state.analysis && Array.isArray(this.state.analysis.weak_personas)) ? this.state.analysis.weak_personas : [];
      if (list) {
        if (!weak.length) {
          list.innerHTML = '<div class="presentation-persona-empty">취약 페르소나를 찾지 못했어. 그래도 인지맵으로 가볼까?</div>';
        } else {
          weak.forEach((wp) => {
            const id = wp.id;
            const name = wp.name ? wp.name : `페르소나 ${id}`;
            const reason = wp.reason || '';
            const btn = document.createElement('button');
            btn.className = 'presentation-persona-item';
            btn.innerHTML = `<div class="p-name">${name}</div><div class="p-reason">${reason}</div>`;
            btn.onclick = () => this.selectPersona(id);
            list.appendChild(btn);
          });
        }
      }

      if (overlay) overlay.classList.remove('hidden');
      if (modal) modal.classList.remove('hidden');
    },

    async selectPersona(personaId) {
      // single select (1개만)
      this.state.selectedPersonaIds = [personaId];

      // 저장
      try {
        await this._postJson(`${this.apiBase}/save_presentation.php`, {
          presentation_id: this.state.presentationId,
          selected_persona_ids: this.state.selectedPersonaIds
        });
      } catch (e) {
        console.warn('[PresentationRecorder] selectPersona save failed:', e);
      }

      // placeholder audio 재생 (임시 링크)
      // NOTE: 실제 페르소나별 파일 링크는 추후 연결 예정
      const placeholderUrl = '/moodle/local/augmented_teacher/teachers/alarm1.mp3';
      const audio = new Audio(placeholderUrl);
      audio.play().catch(() => {});
    },

    closePersonaModal() {
      var o = $('presentationPersonaOverlay');
      var m = $('presentationPersonaModal');
      if (o) o.classList.add('hidden');
      if (m) m.classList.add('hidden');
    },

    goToQuantum() {
      if (!this.state.presentationId) return;
      const analysisId = window.ANALYSIS_ID;
      const studentId = window.STUDENT_ID;
      const url = `/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/ui/quantum_modeling.php?id=${encodeURIComponent(analysisId)}&studentid=${encodeURIComponent(studentId)}&presentation_id=${encodeURIComponent(this.state.presentationId)}&autoplay_voice_map=1`;
      window.location.href = url;
    },

    _startTimer() {
      const timerEl = $('presentationTimer');
      if (this.state.timerId) clearInterval(this.state.timerId);
      this.state.timerId = setInterval(() => {
        this.state.elapsedSec += 1;
        if (timerEl) timerEl.textContent = formatTime(this.state.elapsedSec);
      }, 1000);
    },

    _stopTimer() {
      if (this.state.timerId) {
        clearInterval(this.state.timerId);
        this.state.timerId = null;
      }
    },

    _resetUi() {
      this._stopTimer();
      var controls = $('presentationControls');
      if (controls) controls.classList.add('hidden');
      const btnText = $('presentationBtnText');
      if (btnText) btnText.textContent = '발표하기';
      const pauseBtn = $('presentationPauseBtn');
      if (pauseBtn) pauseBtn.textContent = '⏸';
      const timerEl = $('presentationTimer');
      if (timerEl) timerEl.textContent = '00:00';

      this.state.isActive = false;
      this.state.isPaused = false;
      this.state.stream = null;
      this.state.recorder = null;
      this.state.chunks = [];
      this.state.elapsedSec = 0;
      this.state.presentationId = null;
      this.state.analysis = null;
      this.state.selectedPersonaIds = [];
    },

    async _postJson(url, body) {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json().catch(() => null);
      return data;
    },

    _blobToDataURL(blob) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      });
    }
  };

  window.PresentationRecorder = PresentationRecorder;
})();


