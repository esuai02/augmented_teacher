/**
 * ActivityTracker - 학생 학습 활동 실시간 추적기
 *
 * 목적:
 * - 학습 인터페이스에서 발생하는 주요 이벤트를 백엔드 이벤트 수집 API로 전송
 * - 이후 Trigger Engine이 이벤트를 평가하여 AgentOrchestrator를 호출할 수 있게 함
 *
 * 제약:
 * - 기존 UI/로직에 영향 최소화 (기존 함수 래핑은 신중히)
 * - 네트워크 오류 시 재시도/버퍼링
 */

(function () {
  if (window.ActivityTracker) return;

  function nowMs() { return Date.now(); }
  function uid(prefix) {
    return (prefix || 'evt_') + nowMs() + '_' + Math.random().toString(36).slice(2, 10);
  }

  class ActivityTracker {
    constructor(opts) {
      this.studentId = (opts && opts.studentId) || window.STUDENT_ID || null;
      this.contentId = (opts && opts.contentId) || window.CONTENT_ID || null;
      this.analysisId = (opts && opts.analysisId) || window.ANALYSIS_ID || null;
      this.sessionId = uid('sess_');

      // endpoint (same origin)
      this.endpoint = (opts && opts.endpoint) ||
        '/moodle/local/augmented_teacher/alt42/orchestration/api/events/collect.php';

      this.buffer = [];
      this.flushTimer = null;
      this.flushIntervalMs = 2000;
      this.maxBuffer = 50;

      this.state = {
        lastActivityAt: nowMs(),
        idleReportedAt: 0,
        consecutiveWrongCount: 0,
        hintCount: 0,
        pauseStartAt: 0
      };

      this.init();
    }

    init() {
      this.wrapSaveInteraction();
      this.wrapRequestHint();
      this.trackSessionStart();
      this.installIdleCheck();
      this.installPauseSignalFromWhiteboard();
      this.startFlushLoop();
      window.addEventListener('beforeunload', () => {
        try { this.trackSessionEnd(); } catch (e) {}
        try { this.flush(true); } catch (e) {}
      });
    }

    getContext() {
      const ctx = {
        student_id: this.studentId,
        content_id: this.contentId,
        analysis_id: this.analysisId,
        session_id: this.sessionId,
        page_url: location.href
      };

      try {
        if (window.state && window.state.steps) {
          const cur = window.state.steps.find(s => s.status === 'current');
          if (cur) ctx.current_step = cur.id;
        }
        if (window.state && window.state.emotion) ctx.current_emotion = window.state.emotion.type;
        if (window.state) ctx.current_persona = window.state.currentPersonaType;
      } catch (e) {}

      return ctx;
    }

    defaultPriority(eventType) {
      // 1~10, 8+ 즉시 처리 권장
      const map = {
        session_start: 5,
        session_end: 5,
        hint_used: 5,
        idle_detected: 8,
        problem_pause: 7,
        problem_wrong: 8,
        problem_wrong_critical: 9
      };
      return map[eventType] || 5;
    }

    enqueue(eventType, data, priority) {
      const evt = {
        event_id: uid('evt_'),
        event_type: eventType,
        timestamp: nowMs(),
        priority: typeof priority === 'number' ? priority : this.defaultPriority(eventType),
        data: data || {},
        context: this.getContext()
      };

      this.buffer.push(evt);
      if (this.buffer.length > this.maxBuffer) this.buffer.shift();

      // High priority: flush asap
      if (evt.priority >= 8) this.flush();
    }

    flush(isBeacon) {
      if (!this.buffer.length) return;
      const payload = {
        student_id: this.studentId,
        content_id: this.contentId,
        analysis_id: this.analysisId,
        session_id: this.sessionId,
        events: this.buffer.splice(0, this.buffer.length)
      };

      // best-effort beacon on unload
      if (isBeacon && navigator.sendBeacon) {
        try {
          const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
          navigator.sendBeacon(this.endpoint, blob);
          return;
        } catch (e) {}
      }

      fetch(this.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      }).catch(() => {
        // 실패 시, events를 다시 buffer에 되돌리지 않음(중복 방지). 서버측에서 관측 이벤트가 더 안정적.
      });
    }

    startFlushLoop() {
      if (this.flushTimer) clearInterval(this.flushTimer);
      this.flushTimer = setInterval(() => this.flush(), this.flushIntervalMs);
    }

    markActivity() {
      this.state.lastActivityAt = nowMs();
      this.state.pauseStartAt = 0;
    }

    installIdleCheck() {
      const idleMs = 5 * 60 * 1000;
      setInterval(() => {
        const idleFor = nowMs() - this.state.lastActivityAt;
        if (idleFor >= idleMs) {
          // 5분마다 1회만
          if (nowMs() - this.state.idleReportedAt >= idleMs) {
            this.state.idleReportedAt = nowMs();
            this.enqueue('idle_detected', { idle_time_ms: idleFor }, 8);
          }
        }
      }, 30 * 1000);

      // 기본 활동 신호
      ['click', 'keydown', 'mousemove', 'touchstart'].forEach(ev =>
        document.addEventListener(ev, () => this.markActivity(), { passive: true })
      );
    }

    installPauseSignalFromWhiteboard() {
      // learning_interface.js 내부 whiteboard 메시지 기반 필기 이벤트에서 pause 감지를 하고 있어서,
      // 여기서는 최소한의 "필기 종료 후 30초 무활동"을 감지해 problem_pause 이벤트로 보냄.
      const pauseThresholdMs = 30 * 1000;
      setInterval(() => {
        // pauseStartAt가 있고 threshold 넘으면 전송
        if (this.state.pauseStartAt && (nowMs() - this.state.pauseStartAt >= pauseThresholdMs)) {
          const pauseMs = nowMs() - this.state.pauseStartAt;
          this.state.pauseStartAt = 0; // 중복 방지
          this.enqueue('problem_pause', { pause_duration_ms: pauseMs }, 7);
        }
      }, 1000);

      // whiteboard 관련 커스텀 이벤트가 있다면 활용 (없으면 무시)
      document.addEventListener('writing_stroke_end', () => {
        this.state.pauseStartAt = nowMs();
      });
    }

    wrapRequestHint() {
      // requestHintWithPersona가 호출되면 힌트 사용으로 간주
      const original = window.requestHintWithPersona;
      if (typeof original !== 'function') return;

      const self = this;
      window.requestHintWithPersona = function () {
        self.state.hintCount += 1;
        self.enqueue('hint_used', { hint_count: self.state.hintCount }, 5);
        return original.apply(this, arguments);
      };
    }

    wrapSaveInteraction() {
      const original = window.saveInteraction;
      if (typeof original !== 'function') return;

      const self = this;
      window.saveInteraction = function (type, data) {
        try {
          self.markActivity();

          // 최소: 오답 트리거(최우선)
          // 현재 UI에서 정오답이 확정적으로 들어오는 타입이 없을 수 있어, 아래 두 가지 경로를 지원:
          // 1) type === 'problem_wrong' 또는 data.is_correct === false
          // 2) type === 'writing_analysis' 결과가 "오답/실수"로 분류된 경우(서버 분석 결과 구조에 맞게 확장 가능)
          if (type === 'problem_wrong' || (data && data.is_correct === false)) {
            self.state.consecutiveWrongCount += 1;
            const pr = self.state.consecutiveWrongCount >= 3 ? 9 : 8;
            self.enqueue('problem_wrong', {
              consecutive_wrong_count: self.state.consecutiveWrongCount,
              source: 'saveInteraction',
              raw_type: type
            }, pr);
          } else if (type === 'problem_correct' || (data && data.is_correct === true)) {
            self.state.consecutiveWrongCount = 0;
          }
        } catch (e) {}

        return original.apply(this, arguments);
      };
    }

    trackSessionStart() {
      this.enqueue('session_start', { user_agent: navigator.userAgent }, 6);
    }

    trackSessionEnd() {
      this.enqueue('session_end', {}, 6);
    }
  }

  window.ActivityTracker = ActivityTracker;
  window.activityTracker = new ActivityTracker({
    studentId: window.STUDENT_ID,
    contentId: window.CONTENT_ID,
    analysisId: window.ANALYSIS_ID
  });
})();


