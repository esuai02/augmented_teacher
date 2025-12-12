<?php
/**
 * init.php
 *
 * 이벤트 시스템 초기화 헬퍼
 * - 다른 페이지에서 include하여 EventBus 인스턴스와 TriggerRuleEngine을 쉽게 사용할 수 있게 함
 *
 * @package ALT42\Events
 * @version 1.0.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/event_bus.php');
require_once(__DIR__ . '/trigger_engine.php');

class EventSystem {
    /** @var int */
    private $studentId;
    /** @var \ALT42\Events\EventBus */
    private $bus;
    /** @var TriggerRuleEngine */
    private $triggerEngine;

    public function __construct(int $studentId) {
        $this->studentId = $studentId;
        $this->bus = \ALT42\Events\EventBus::getInstance();
        $this->triggerEngine = new TriggerRuleEngine($studentId);
    }

    public function getBus(): \ALT42\Events\EventBus {
        return $this->bus;
    }

    public function getTriggerEngine(): TriggerRuleEngine {
        return $this->triggerEngine;
    }

    public function getStudentId(): int {
        return $this->studentId;
    }
}


