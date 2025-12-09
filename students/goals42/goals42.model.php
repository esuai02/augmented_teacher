<?php
/**
 * Goals42 Data Model
 *
 * Handles all database operations for student goals
 * File: /mnt/c/1 Project/augmented_teacher/students/goals42/goals42.model.php
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

class Goals42Model {
    private $DB;
    private $USER;

    public function __construct($DB, $USER) {
        $this->DB = $DB;
        $this->USER = $USER;
    }

    /**
     * Get student information
     *
     * @param int $userid User ID
     * @return object Student record
     * @throws Exception on database error (Line: __LINE__)
     */
    public function getStudentInfo($userid) {
        try {
            $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email,
                           s.grade, s.school, s.parent_phone
                    FROM {user} u
                    LEFT JOIN {local_students} s ON u.id = s.userid
                    WHERE u.id = ?";

            $student = $this->DB->get_record_sql($sql, [$userid]);

            if (!$student) {
                throw new Exception("Student not found: userid={$userid} (File: " . __FILE__ . ", Line: " . __LINE__ . ")");
            }

            return $student;
        } catch (Exception $e) {
            error_log("Goals42Model::getStudentInfo Error - " . $e->getMessage());
            throw new Exception("Database error in getStudentInfo (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }

    /**
     * Get quarter goals for student
     *
     * @param int $userid User ID
     * @param int $year Year
     * @param int $quarter Quarter (1-4)
     * @return array Quarter goals records
     * @throws Exception on database error (Line: __LINE__)
     */
    public function getQuarterGoals($userid, $year = null, $quarter = null) {
        try {
            if ($year === null) {
                $year = date('Y');
            }
            if ($quarter === null) {
                $quarter = ceil(date('n') / 3);
            }

            $sql = "SELECT * FROM {local_quarter_goals}
                    WHERE userid = ? AND year = ? AND quarter = ?
                    ORDER BY created_at DESC";

            return $this->DB->get_records_sql($sql, [$userid, $year, $quarter]);
        } catch (Exception $e) {
            error_log("Goals42Model::getQuarterGoals Error - " . $e->getMessage());
            throw new Exception("Database error in getQuarterGoals (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }

    /**
     * Save quarter goal
     *
     * @param object $data Goal data
     * @return int Inserted/Updated record ID
     * @throws Exception on database error (Line: __LINE__)
     */
    public function saveQuarterGoal($data) {
        try {
            $data->modified_at = time();

            if (isset($data->id) && $data->id > 0) {
                // Update existing record
                $this->DB->update_record('local_quarter_goals', $data);
                return $data->id;
            } else {
                // Insert new record
                $data->created_at = time();
                return $this->DB->insert_record('local_quarter_goals', $data);
            }
        } catch (Exception $e) {
            error_log("Goals42Model::saveQuarterGoal Error - " . $e->getMessage());
            throw new Exception("Database error in saveQuarterGoal (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }

    /**
     * Get weekly plans for student
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param int $weeknum Week number
     * @return array Weekly plan records
     * @throws Exception on database error (Line: __LINE__)
     */
    public function getWeeklyPlans($userid, $courseid, $weeknum = null) {
        try {
            if ($weeknum === null) {
                $weeknum = date('W');
            }

            $sql = "SELECT * FROM {local_weekly_plans}
                    WHERE userid = ? AND courseid = ? AND week_number = ?
                    ORDER BY created_at DESC";

            return $this->DB->get_records_sql($sql, [$userid, $courseid, $weeknum]);
        } catch (Exception $e) {
            error_log("Goals42Model::getWeeklyPlans Error - " . $e->getMessage());
            throw new Exception("Database error in getWeeklyPlans (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }

    /**
     * Get daily goals for student
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param string $date Date (Y-m-d format)
     * @return array Daily goal records
     * @throws Exception on database error (Line: __LINE__)
     */
    public function getDailyGoals($userid, $courseid, $date = null) {
        try {
            if ($date === null) {
                $date = date('Y-m-d');
            }

            $sql = "SELECT * FROM {local_daily_goals}
                    WHERE userid = ? AND courseid = ? AND goal_date = ?
                    ORDER BY created_at DESC";

            return $this->DB->get_records_sql($sql, [$userid, $courseid, $date]);
        } catch (Exception $e) {
            error_log("Goals42Model::getDailyGoals Error - " . $e->getMessage());
            throw new Exception("Database error in getDailyGoals (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }

    /**
     * Get math diary entries
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param string $date Date (Y-m-d format)
     * @return array Math diary records
     * @throws Exception on database error (Line: __LINE__)
     */
    public function getMathDiary($userid, $courseid, $date = null) {
        try {
            if ($date === null) {
                $date = date('Y-m-d');
            }

            $sql = "SELECT * FROM {local_math_diary}
                    WHERE userid = ? AND courseid = ? AND diary_date = ?
                    ORDER BY created_at DESC";

            return $this->DB->get_records_sql($sql, [$userid, $courseid, $date]);
        } catch (Exception $e) {
            error_log("Goals42Model::getMathDiary Error - " . $e->getMessage());
            throw new Exception("Database error in getMathDiary (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }

    /**
     * Delete quarter goal
     *
     * @param int $id Goal ID
     * @param int $userid User ID (for security check)
     * @return bool Success status
     * @throws Exception on database error (Line: __LINE__)
     */
    public function deleteQuarterGoal($id, $userid) {
        try {
            // Security check: verify ownership
            $record = $this->DB->get_record('local_quarter_goals', ['id' => $id]);
            if (!$record || $record->userid != $userid) {
                throw new Exception("Unauthorized delete attempt: goal_id={$id}, userid={$userid} (File: " . __FILE__ . ", Line: " . __LINE__ . ")");
            }

            return $this->DB->delete_records('local_quarter_goals', ['id' => $id]);
        } catch (Exception $e) {
            error_log("Goals42Model::deleteQuarterGoal Error - " . $e->getMessage());
            throw new Exception("Database error in deleteQuarterGoal (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }

    /**
     * Get course information
     *
     * @param int $courseid Course ID
     * @return object Course record
     * @throws Exception on database error (Line: __LINE__)
     */
    public function getCourseInfo($courseid) {
        try {
            $course = $this->DB->get_record('course', ['id' => $courseid]);

            if (!$course) {
                throw new Exception("Course not found: courseid={$courseid} (File: " . __FILE__ . ", Line: " . __LINE__ . ")");
            }

            return $course;
        } catch (Exception $e) {
            error_log("Goals42Model::getCourseInfo Error - " . $e->getMessage());
            throw new Exception("Database error in getCourseInfo (File: " . __FILE__ . ", Line: " . __LINE__ . "): " . $e->getMessage());
        }
    }
}
