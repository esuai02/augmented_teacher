<?php
/**
 * HybridEventObserver - Moodle 이벤트 관측자
 * 
 * Moodle 이벤트를 감지하여 하이브리드 상태 시스템에 전달
 * 학습 활동, 퀴즈 제출, 페이지 조회 등의 이벤트 처리
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling\Observers
 * @version 1.0.0
 * @since 2025-12-06
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(dirname(__DIR__) . '/HybridDataBridge.php');

class HybridEventObserver
{
    /** @var string 현재 파일 경로 (에러 출력용) */
    private static $currentFile = __FILE__;

    /** @var array 이벤트 캐시 (동일 이벤트 중복 방지) */
    private static $eventCache = [];

    /** @var int 캐시 만료 시간 (초) */
    const CACHE_TTL = 5;

    // ============================================================
    // 퀴즈/문제 풀이 이벤트
    // ============================================================

    /**
     * 퀴즈 제출 완료 이벤트
     */
    public static function quiz_attempt_submitted(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            // 퀴즈 결과 조회
            global $DB;
            $attempt = $DB->get_record('quiz_attempts', ['id' => $data['objectid']]);
            
            if ($attempt) {
                $grade = floatval($attempt->sumgrades ?? 0);
                $maxGrade = floatval($attempt->sumofgrades ?? 1);
                $score = $maxGrade > 0 ? $grade / $maxGrade : 0;
                
                // 점수에 따라 이벤트 유형 결정
                if ($score >= 0.8) {
                    $activityType = 'problem_correct';
                } elseif ($score >= 0.5) {
                    $activityType = 'problem_wrong'; // 부분 정답
                } else {
                    $activityType = 'problem_wrong';
                }
                
                $bridge->processActivityEvent($activityType, [
                    'score' => $score,
                    'attempt_count' => $attempt->attempt ?? 1,
                ]);
            }
        });
    }

    /**
     * 문제 정답 이벤트
     */
    public static function question_answered_correctly(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('problem_correct', [
                'question_id' => $data['objectid'] ?? null,
                'time_taken' => $data['timecreated'] - ($data['other']['starttime'] ?? $data['timecreated']),
            ]);
        });
    }

    /**
     * 문제 오답 이벤트
     */
    public static function question_answered_incorrectly(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('problem_wrong', [
                'question_id' => $data['objectid'] ?? null,
                'attempt_count' => $data['other']['attempt'] ?? 1,
            ]);
        });
    }

    // ============================================================
    // 네비게이션 이벤트
    // ============================================================

    /**
     * 페이지 조회 이벤트
     */
    public static function course_module_viewed(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('page_view', [
                'module_id' => $data['contextinstanceid'] ?? null,
                'module_name' => $data['other']['modulename'] ?? 'unknown',
            ]);
        });
    }

    /**
     * 코스 조회 이벤트
     */
    public static function course_viewed(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('page_view', [
                'course_id' => $data['courseid'] ?? null,
            ]);
        });
    }

    // ============================================================
    // 힌트/도움 이벤트
    // ============================================================

    /**
     * 힌트 조회 이벤트
     */
    public static function hint_viewed(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('hint_used', [
                'hint_id' => $data['objectid'] ?? null,
            ]);
        });
    }

    /**
     * 해설 조회 이벤트
     */
    public static function solution_viewed(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('solution_viewed', [
                'problem_id' => $data['objectid'] ?? null,
            ]);
        });
    }

    // ============================================================
    // 세션 이벤트
    // ============================================================

    /**
     * 사용자 로그인 이벤트
     */
    public static function user_loggedin(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            // 세션 시작 시 초기화
            $bridge->initializeSession([
                'initial_focus' => 0.6, // 로그인 직후 약간 높은 집중도
            ]);
        });
    }

    /**
     * 사용자 로그아웃 이벤트
     */
    public static function user_loggedout(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('session_end', []);
            
            // 상태 저장
            $bridge->getStabilizer()->saveState();
        });
    }

    // ============================================================
    // 커스텀 이벤트 (Mathking 전용)
    // ============================================================

    /**
     * 빠른복습 완료 이벤트
     */
    public static function quick_review_completed(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $correctRate = floatval($data['other']['correct_rate'] ?? 0);
            
            if ($correctRate >= 0.8) {
                $bridge->processActivityEvent('problem_correct', ['score' => $correctRate]);
            } else {
                $bridge->processActivityEvent('problem_wrong', ['score' => $correctRate]);
            }
        });
    }

    /**
     * 개념 학습 완료 이벤트
     */
    public static function concept_study_completed(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            // 개념 학습은 집중 상태 유지
            $bridge->processActivityEvent('page_view', [
                'duration' => $data['other']['duration'] ?? 0,
            ]);
        });
    }

    /**
     * 문제 건너뛰기 이벤트
     */
    public static function problem_skipped(\core\event\base $event): void
    {
        self::processEvent($event, function($data) {
            $bridge = new HybridDataBridge($data['userid']);
            
            $bridge->processActivityEvent('problem_skip', [
                'problem_id' => $data['objectid'] ?? null,
                'reason' => $data['other']['reason'] ?? 'unknown',
            ]);
        });
    }

    // ============================================================
    // 유틸리티
    // ============================================================

    /**
     * 이벤트 처리 래퍼 (중복 방지, 에러 처리)
     */
    private static function processEvent(\core\event\base $event, callable $handler): void
    {
        try {
            $data = $event->get_data();
            $userId = $data['userid'] ?? 0;
            
            if (!$userId) {
                return;
            }
            
            // 중복 이벤트 체크
            $eventKey = md5(json_encode([
                'eventname' => $data['eventname'] ?? '',
                'userid' => $userId,
                'objectid' => $data['objectid'] ?? '',
            ]));
            
            $now = time();
            if (isset(self::$eventCache[$eventKey]) && 
                ($now - self::$eventCache[$eventKey]) < self::CACHE_TTL) {
                return; // 중복 이벤트 무시
            }
            
            self::$eventCache[$eventKey] = $now;
            
            // 캐시 정리 (오래된 항목 제거)
            foreach (self::$eventCache as $key => $time) {
                if ($now - $time > self::CACHE_TTL * 2) {
                    unset(self::$eventCache[$key]);
                }
            }
            
            // 핸들러 실행
            $handler($data);
            
        } catch (Exception $e) {
            error_log("[HybridEventObserver] Event processing error at " . self::$currentFile . ":" . $e->getLine() . " - " . $e->getMessage());
        }
    }

    /**
     * 이벤트 수동 트리거 (테스트/디버그용)
     */
    public static function triggerManualEvent(int $userId, string $eventType, array $eventData = []): array
    {
        try {
            $bridge = new HybridDataBridge($userId);
            return $bridge->processActivityEvent($eventType, $eventData);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}


