<?php
/**
 * DataContextInterface.php
 *
 * 에이전트 데이터 컨텍스트 인터페이스
 * 각 에이전트가 필요로 하는 데이터 수집 및 제공 표준 정의
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/interfaces/DataContextInterface.php
 */

defined('MOODLE_INTERNAL') || die();

interface DataContextInterface
{
    /**
     * 에이전트 번호 반환
     *
     * @return int 에이전트 번호 (1-21)
     */
    public function getAgentNumber(): int;

    /**
     * 사용자 기본 정보 조회
     *
     * @param int $userId 사용자 ID
     * @return array [
     *     'id'         => int,
     *     'firstname'  => string,
     *     'lastname'   => string,
     *     'email'      => string,
     *     'role'       => string,    // student, teacher 등
     *     'grade'      => string,    // 학년
     *     'created'    => int        // 계정 생성 시간
     * ]
     */
    public function getUserInfo(int $userId): array;

    /**
     * 사용자 학습 이력 조회
     *
     * @param int   $userId    사용자 ID
     * @param int   $limit     조회 제한 수
     * @param array $filters   필터 조건
     * @return array 학습 이력 배열
     */
    public function getLearningHistory(int $userId, int $limit = 100, array $filters = []): array;

    /**
     * 사용자 감정/상태 데이터 조회
     *
     * @param int      $userId 사용자 ID
     * @param int|null $since  이후 시간 (Unix timestamp)
     * @return array 감정/상태 데이터
     */
    public function getEmotionData(int $userId, ?int $since = null): array;

    /**
     * 사용자 성취도 데이터 조회
     *
     * @param int   $userId   사용자 ID
     * @param array $subjects 과목 필터 (선택적)
     * @return array 성취도 데이터
     */
    public function getAchievementData(int $userId, array $subjects = []): array;

    /**
     * 에이전트 전용 데이터 조회
     * 각 에이전트별로 특화된 데이터를 조회
     *
     * @param int    $userId   사용자 ID
     * @param string $dataType 데이터 유형
     * @param array  $params   추가 파라미터
     * @return array 에이전트 전용 데이터
     */
    public function getAgentSpecificData(int $userId, string $dataType, array $params = []): array;

    /**
     * 다른 에이전트의 분석 결과 조회
     *
     * @param int   $userId       사용자 ID
     * @param int   $sourceAgent  소스 에이전트 번호 (1-21)
     * @param array $dataTypes    요청할 데이터 유형들
     * @return array 다른 에이전트의 분석 결과
     */
    public function getCrossAgentData(int $userId, int $sourceAgent, array $dataTypes = []): array;

    /**
     * 컨텍스트 데이터 빌드
     * 페르소나 식별에 필요한 모든 컨텍스트 데이터를 수집하여 반환
     *
     * @param int   $userId  사용자 ID
     * @param array $options 옵션
     * @return array 통합된 컨텍스트 데이터
     */
    public function buildContext(int $userId, array $options = []): array;

    /**
     * 데이터 캐시 무효화
     *
     * @param int         $userId    사용자 ID (null이면 전체)
     * @param string|null $dataType  데이터 유형 (null이면 전체)
     * @return bool 성공 여부
     */
    public function invalidateCache(?int $userId = null, ?string $dataType = null): bool;

    /**
     * 에이전트 전용 데이터 저장
     *
     * @param int    $userId   사용자 ID
     * @param string $dataType 데이터 유형
     * @param array  $data     저장할 데이터
     * @return bool 저장 성공 여부
     */
    public function saveAgentData(int $userId, string $dataType, array $data): bool;

    /**
     * 데이터 통계 조회
     *
     * @param int   $userId    사용자 ID
     * @param array $metrics   요청할 메트릭들
     * @param array $timeRange 시간 범위 ['from' => int, 'to' => int]
     * @return array 통계 데이터
     */
    public function getStatistics(int $userId, array $metrics, array $timeRange = []): array;

    /**
     * 데이터 소스 상태 확인
     *
     * @return array [
     *     'available' => bool,
     *     'sources'   => array,   // 각 소스별 상태
     *     'lastSync'  => int      // 마지막 동기화 시간
     * ]
     */
    public function checkDataSources(): array;
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * DataContext가 접근하는 공통 테이블:
 *
 * 테이블명: mdl_user
 * ┌─────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field           │ Type             │ Description                        │
 * ├─────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id              │ BIGINT           │ Primary Key                        │
 * │ firstname       │ VARCHAR(100)     │ 이름                                │
 * │ lastname        │ VARCHAR(100)     │ 성                                  │
 * │ email           │ VARCHAR(100)     │ 이메일                              │
 * │ timecreated     │ INT              │ 계정 생성 시간                       │
 * └─────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * 테이블명: mdl_user_info_data
 * ┌─────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field           │ Type             │ Description                        │
 * ├─────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id              │ BIGINT           │ Primary Key                        │
 * │ userid          │ BIGINT           │ 사용자 ID                           │
 * │ fieldid         │ BIGINT           │ 필드 ID (22=role 등)                │
 * │ data            │ LONGTEXT         │ 데이터 값                           │
 * └─────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * 에이전트별 전용 테이블 패턴: mdl_at_agent{XX}_*
 * 예시:
 * - mdl_at_agent05_emotion_log (Agent05 감정 로그)
 * - mdl_at_agent08_calmness_sessions (Agent08 평온도 세션)
 * - mdl_at_agent09_learning_plans (Agent09 학습 계획)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
