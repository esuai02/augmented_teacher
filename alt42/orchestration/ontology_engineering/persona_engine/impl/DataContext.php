<?php
/**
 * DataContext - 데이터 컨텍스트 구현
 *
 * Moodle DB와 연동하여 학생/교사 컨텍스트를 로드합니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 */

require_once(__DIR__ . '/../core/IDataContext.php');

class DataContext implements IDataContext {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 세션 캐시 */
    private $sessionCache = [];

    /**
     * 학생 컨텍스트 로드
     */
    public function loadStudentContext(int $userId, array $sessionData = []): array {
        global $DB;

        $context = [
            'userid' => $userId,
            'user_id' => $userId,
            'timestamp' => time()
        ];

        try {
            // 기본 사용자 정보
            $user = $DB->get_record('user', ['id' => $userId]);
            if ($user) {
                $context['username'] = $user->username ?? '';
                $context['firstname'] = $user->firstname ?? '';
                $context['lastname'] = $user->lastname ?? '';
                $context['email'] = $user->email ?? '';
            }

            // 확장 정보 (mdl_user_info_data)
            $infoData = $DB->get_records_sql(
                "SELECT f.shortname, d.data 
                 FROM {user_info_data} d 
                 JOIN {user_info_field} f ON f.id = d.fieldid 
                 WHERE d.userid = ?",
                [$userId]
            );

            foreach ($infoData as $info) {
                $context[$info->shortname] = $info->data;
            }

            // 역할 정보
            $roleData = $DB->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
                [$userId]
            );
            $context['role'] = $roleData ? $roleData->data : 'student';

            // 페르소나 세션 정보 로드
            $personaSession = $this->loadPersonaSession($userId);
            $context = array_merge($context, $personaSession);

            // 전달된 세션 데이터 병합
            $context = array_merge($context, $sessionData);

            // 세션 캐시 업데이트
            $this->sessionCache[$userId] = $context;

            return $context;

        } catch (Exception $e) {
            error_log("[DataContext ERROR] 학생 컨텍스트 로드 실패: {$e->getMessage()} [{$this->currentFile}:" . __LINE__ . "]");
            return array_merge($context, $sessionData);
        }
    }

    /**
     * 교사 컨텍스트 로드
     */
    public function loadTeacherContext(int $teacherId): array {
        global $DB;

        $context = [
            'userid' => $teacherId,
            'user_id' => $teacherId,
            'role' => 'teacher',
            'timestamp' => time()
        ];

        try {
            // 기본 사용자 정보
            $user = $DB->get_record('user', ['id' => $teacherId]);
            if ($user) {
                $context['username'] = $user->username ?? '';
                $context['firstname'] = $user->firstname ?? '';
                $context['lastname'] = $user->lastname ?? '';
                $context['email'] = $user->email ?? '';
            }

            // 담당 코스 정보
            $courses = $DB->get_records_sql(
                "SELECT DISTINCT c.id, c.fullname 
                 FROM {course} c 
                 JOIN {enrol} e ON e.courseid = c.id 
                 JOIN {user_enrolments} ue ON ue.enrolid = e.id 
                 WHERE ue.userid = ?",
                [$teacherId]
            );
            $context['courses'] = array_values($courses);

            return $context;

        } catch (Exception $e) {
            error_log("[DataContext ERROR] 교사 컨텍스트 로드 실패: {$e->getMessage()} [{$this->currentFile}:" . __LINE__ . "]");
            return $context;
        }
    }

    /**
     * 페르소나 세션 정보 로드
     */
    private function loadPersonaSession(int $userId): array {
        global $DB;

        $sessionData = [
            'current_persona' => null,
            'current_situation' => 'S1',
            'previous_persona' => null,
            'session_start' => time(),
            'interaction_count' => 0
        ];

        try {
            // mdl_at_persona_session 테이블에서 로드 (존재하는 경우)
            $session = $DB->get_record_sql(
                "SELECT * FROM {at_persona_session} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
                [$userId]
            );

            if ($session) {
                $sessionData['current_persona'] = $session->current_persona ?? null;
                $sessionData['current_situation'] = $session->current_situation ?? 'S1';
                $sessionData['previous_persona'] = $session->previous_persona ?? null;
                $sessionData['session_start'] = $session->created_at ?? time();
                $sessionData['interaction_count'] = $session->interaction_count ?? 0;
            }

        } catch (Exception $e) {
            // 테이블이 없으면 기본값 사용
            error_log("[DataContext] 페르소나 세션 테이블 없음 또는 오류: {$e->getMessage()}");
        }

        return $sessionData;
    }

    /**
     * 컨텍스트 데이터 업데이트
     */
    public function updateContext(int $userId, array $data): bool {
        global $DB;

        try {
            // 페르소나 세션 업데이트
            $existing = $DB->get_record('at_persona_session', ['user_id' => $userId]);

            $record = new stdClass();
            $record->user_id = $userId;
            $record->current_persona = $data['current_persona'] ?? null;
            $record->current_situation = $data['current_situation'] ?? 'S1';
            $record->previous_persona = $data['previous_persona'] ?? null;
            $record->interaction_count = ($data['interaction_count'] ?? 0) + 1;
            $record->updated_at = time();

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('at_persona_session', $record);
            } else {
                $record->created_at = time();
                $DB->insert_record('at_persona_session', $record);
            }

            // 캐시 업데이트
            if (isset($this->sessionCache[$userId])) {
                $this->sessionCache[$userId] = array_merge($this->sessionCache[$userId], $data);
            }

            return true;

        } catch (Exception $e) {
            error_log("[DataContext ERROR] 컨텍스트 업데이트 실패: {$e->getMessage()} [{$this->currentFile}:" . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 세션 데이터 저장
     */
    public function saveSessionData(int $userId, string $key, $value): bool {
        try {
            if (!isset($this->sessionCache[$userId])) {
                $this->sessionCache[$userId] = [];
            }
            $this->sessionCache[$userId][$key] = $value;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 세션 데이터 조회
     */
    public function getSessionData(int $userId, string $key, $default = null) {
        return $this->sessionCache[$userId][$key] ?? $default;
    }

    /**
     * 컨텍스트 필드 값 조회 (점 표기법 지원)
     */
    public function getContextValue(string $field, array $context) {
        $keys = explode('.', $field);
        $value = $context;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * 세션 캐시 클리어
     */
    public function clearCache(int $userId = null): void {
        if ($userId !== null) {
            unset($this->sessionCache[$userId]);
        } else {
            $this->sessionCache = [];
        }
    }
}

/*
 * 관련 DB 테이블:
 * - mdl_user (사용자 기본 정보)
 * - mdl_user_info_data (사용자 확장 정보)
 * - mdl_user_info_field (확장 필드 정의)
 * - mdl_at_persona_session (페르소나 세션) - 신규 생성 필요
 * - mdl_course (코스 정보)
 * - mdl_enrol / mdl_user_enrolments (등록 정보)
 */
