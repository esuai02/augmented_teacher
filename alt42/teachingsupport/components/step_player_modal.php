<?php
/**
 * Step-by-Step Audio Player Modal Component
 *
 * Reusable modal component for displaying TTS audio sections with step-by-step navigation.
 * Features circular navigation UI, auto-play functionality, and speed controls.
 *
 * @file step_player_modal.php
 * @location /alt42/teachingsupport/components/step_player_modal.php
 * @requires jQuery (for modal controls)
 * @requires step_player.js (business logic - Task 8)
 * @requires step_player_modal.css (styles - Task 7)
 *
 * Usage:
 * <?php include(__DIR__ . '/alt42/teachingsupport/components/step_player_modal.php'); ?>
 *
 * JavaScript API:
 * StepPlayer.open(messageid) - Opens modal with section data
 * StepPlayer.close() - Closes modal
 *
 * @version 1.0
 * @created 2025-11-22
 */

// Prevent direct access
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}
?>

<!-- Modal Backdrop -->
<div id="step-player-modal"
     class="step-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="step-modal-title"
     aria-describedby="step-modal-content"
     data-step-modal
     style="display: none;">

    <!-- Modal Content Card -->
    <div class="step-modal__card" role="document">

        <!-- Header Section -->
        <div class="step-modal__header">
            <!-- Modal Title -->
            <h2 id="step-modal-title" class="step-modal__title">
                듣기 학습
            </h2>

            <!-- Header Controls Group -->
            <div class="step-modal__header-controls">
                <!-- Step Indicator -->
                <span class="step-modal__indicator" aria-live="polite" aria-atomic="true">
                    <span class="step-modal__current" data-current-step>1</span>
                    <span aria-hidden="true"> / </span>
                    <span class="step-modal__total" data-total-steps>5</span>
                </span>

                <!-- Speed Control Dropdown -->
                <label class="step-modal__speed-label" for="step-modal-speed">
                    <span class="sr-only">재생 속도 선택</span>
                    <select id="step-modal-speed"
                            class="step-modal__speed"
                            data-speed-control
                            aria-label="재생 속도">
                        <option value="0.5">0.5x</option>
                        <option value="0.75">0.75x</option>
                        <option value="1">1x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="1.75" selected>1.75x</option>
                        <option value="2">2x</option>
                    </select>
                </label>

                <!-- Auto-play Toggle -->
                <label class="step-modal__auto-toggle">
                    <input type="checkbox"
                           id="step-modal-auto"
                           class="step-modal__auto-checkbox"
                           data-auto-play
                           checked
                           aria-describedby="auto-play-desc">
                    <span class="step-modal__auto-label">자동재생</span>
                </label>
                <span id="auto-play-desc" class="sr-only">
                    각 섹션이 끝나면 자동으로 다음 섹션을 재생합니다
                </span>
            </div>

            <!-- Close Button -->
            <button class="step-modal__close"
                    data-close-modal
                    aria-label="닫기"
                    title="닫기 (ESC)">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <!-- Audio Player Section -->
        <div class="step-modal__player">
            <!-- HTML5 Audio Element -->
            <audio id="step-audio-player"
                   class="step-modal__audio"
                   data-audio-element
                   preload="auto"
                   aria-label="오디오 플레이어">
                Your browser does not support the audio element.
            </audio>

            <!-- Play/Pause Button -->
            <div class="step-modal__player-controls">
                <button class="step-modal__play-btn"
                        data-play-pause
                        aria-label="재생"
                        aria-pressed="false"
                        title="재생/일시정지 (Space)">
                    <!-- Play Icon (visible by default) -->
                    <svg class="step-modal__play-icon"
                         width="48"
                         height="48"
                         viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path d="M8 5v14l11-7z" fill="currentColor"/>
                    </svg>
                    <!-- Pause Icon (hidden by default) -->
                    <svg class="step-modal__pause-icon"
                         width="48"
                         height="48"
                         viewBox="0 0 24 24"
                         aria-hidden="true"
                         style="display: none;">
                        <rect x="6" y="4" width="4" height="16" fill="currentColor"/>
                        <rect x="14" y="4" width="4" height="16" fill="currentColor"/>
                    </svg>
                </button>
            </div>

            <!-- Audio Progress Bar -->
            <div class="step-modal__progress-bar"
                 role="progressbar"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-valuenow="0"
                 aria-label="오디오 진행률">
                <div class="step-modal__progress-fill" data-progress-fill></div>
            </div>

            <!-- Time Display -->
            <div class="step-modal__time" aria-live="off">
                <span class="step-modal__time-current" data-time-current>0:00</span>
                <span aria-hidden="true"> / </span>
                <span class="step-modal__time-total" data-time-total>0:00</span>
            </div>
        </div>

        <!-- Text Display Area -->
        <div class="step-modal__content-wrapper">
            <div class="step-modal__content"
                 id="step-modal-content"
                 data-text-content
                 role="region"
                 aria-live="polite"
                 aria-atomic="true"
                 tabindex="0">
                <p class="step-modal__text">
                    섹션 텍스트가 여기에 표시됩니다.
                </p>
            </div>
        </div>

        <!-- Circular Navigation (Dots) -->
        <nav class="step-modal__nav-circles"
             aria-label="섹션 네비게이션"
             role="tablist">
            <div class="step-modal__circles" data-circle-nav>
                <!-- Dynamically populated by JavaScript -->
                <!-- Example structure:
                <button role="tab"
                        class="step-modal__circle step-modal__circle--active"
                        aria-label="섹션 1"
                        aria-selected="true"
                        data-step-index="0">
                    <span class="sr-only">섹션 1</span>
                </button>
                -->
            </div>
        </nav>

        <!-- Navigation Buttons -->
        <div class="step-modal__nav-buttons">
            <!-- Previous Button -->
            <button class="step-modal__prev step-modal__nav-btn"
                    data-prev-step
                    aria-label="이전 섹션으로 이동"
                    title="이전 (Left Arrow)"
                    disabled>
                <span class="step-modal__nav-icon" aria-hidden="true">◀</span>
                <span class="step-modal__nav-text">이전</span>
            </button>

            <!-- Next Button -->
            <button class="step-modal__next step-modal__nav-btn"
                    data-next-step
                    aria-label="다음 섹션으로 이동"
                    title="다음 (Right Arrow)">
                <span class="step-modal__nav-text">다음</span>
                <span class="step-modal__nav-icon" aria-hidden="true">▶</span>
            </button>
        </div>

        <!-- Footer Section -->
        <div class="step-modal__footer">
            <!-- Progress Text -->
            <p class="step-modal__progress-text" aria-live="polite">
                <span data-progress-text>
                    Step <span class="step-modal__current-footer" data-current-step-footer>1</span>
                    of <span class="step-modal__total-footer" data-total-steps-footer>5</span>
                </span>
            </p>

            <!-- Additional Controls (Optional) -->
            <div class="step-modal__footer-controls">
                <!-- Replay Current Section -->
                <button class="step-modal__replay-btn"
                        data-replay-section
                        aria-label="현재 섹션 다시 듣기"
                        title="다시 듣기">
                    <span aria-hidden="true">↻</span> 다시 듣기
                </button>

                <!-- Reset to Beginning -->
                <button class="step-modal__reset-btn"
                        data-reset-modal
                        aria-label="처음부터 다시 시작"
                        title="처음부터">
                    <span aria-hidden="true">⏮</span> 처음부터
                </button>
            </div>
        </div>

    </div><!-- .step-modal__card -->

</div><!-- #step-player-modal -->

<!-- Screen Reader Only Helper Text -->
<div class="sr-only" aria-live="polite" data-sr-announcements></div>

<!--
Modal Initialization:
This modal is controlled by external JavaScript (step_player.js - Task 8).
Do not include inline JavaScript here.

Integration Points:
1. teachingagent.php - Teacher TTS generation interface
2. student_inbox.php - Student "풀이보기" button

Data Flow:
1. User clicks trigger button with data-messageid attribute
2. JavaScript calls StepPlayer.open(messageid)
3. AJAX fetches section data from get_section_data.php
4. Modal populated and displayed
5. Audio playback controlled via step_player.js

Accessibility Features:
- Semantic HTML5 structure
- ARIA labels and roles
- Keyboard navigation (Tab, Space, Enter, Escape, Arrow keys)
- Screen reader announcements for step changes
- Focus management (trap focus in modal)
- High contrast support via CSS (Task 7)

File: /alt42/teachingsupport/components/step_player_modal.php
Error output location: Console + data-sr-announcements for SR users
Related DB: mdl_ktm_mathmessages (sections field)
Related Files:
- get_section_data.php (GET endpoint)
- tts_section_generator.php (POST endpoint)
- step_player_modal.css (Task 7)
- step_player.js (Task 8)
-->
