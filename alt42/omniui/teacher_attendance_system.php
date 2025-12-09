<?php
// Moodle 설정 파일 포함
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// 로그인 확인
require_login();

// 교사 권한 확인
$context = context_system::instance();
require_capability('moodle/course:viewparticipants', $context);

// 현재 교사 ID
$teacherid = $USER->id;

// 교사가 담당하는 학생 목록 가져오기
$students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.phone1, u.phone2,
           c.fullname as course_name, c.id as courseid
    FROM {user} u
    JOIN {user_enrolments} ue ON ue.userid = u.id
    JOIN {enrol} e ON e.id = ue.enrolid
    JOIN {course} c ON c.id = e.courseid
    JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
    JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ?
    WHERE ra.roleid IN (3,4,5) -- 교사 역할
    ORDER BY u.lastname, u.firstname
", array($teacherid));

// 오늘 날짜
$today = date('Y-m-d');
$timecreated = time();

// 실시간 알림 데이터 가져오기
$alerts = array();

// 정규수업 시간 체크하여 결석 알림 생성
foreach ($students as $student) {
    // 학생의 정규 스케줄 가져오기
    $schedule = $DB->get_record_sql("
        SELECT * FROM {abessi_schedule} 
        WHERE userid = ? AND pinned = 1 
        ORDER BY id DESC LIMIT 1
    ", array($student->id));
    
    if ($schedule) {
        // 오늘 수업이 있는지 체크
        $today_day = strtolower(date('l'));
        $schedule_data = json_decode($schedule->schedule_data, true);
        
        if (isset($schedule_data[$today_day]) && $schedule_data[$today_day]['has_class']) {
            $class_start = strtotime($today . ' ' . $schedule_data[$today_day]['start_time']);
            $current_time = time();
            
            // 수업 시작 15분 후 미접속시 결석 처리
            if ($current_time > $class_start + 900) {
                // 오늘 접속 기록 확인
                $attendance = $DB->get_record_sql("
                    SELECT MIN(timecreated) as first_access, MAX(timecreated) as last_access
                    FROM {abessi_missionlog}
                    WHERE userid = ? AND DATE(FROM_UNIXTIME(timecreated)) = ?
                ", array($student->id, $today));
                
                if (!$attendance->first_access || $attendance->first_access > $class_start + 900) {
                    $alerts[] = array(
                        'id' => uniqid(),
                        'type' => 'absence',
                        'priority' => 'urgent',
                        'student_id' => $student->id,
                        'student_name' => $student->firstname . ' ' . $student->lastname,
                        'message' => '정규수업 결석 (15분 경과)',
                        'timestamp' => $class_start + 900,
                        'class_info' => array(
                            'subject' => $student->course_name,
                            'scheduled_time' => $schedule_data[$today_day]['start_time'] . '-' . $schedule_data[$today_day]['end_time']
                        )
                    );
                }
            }
        }
    }
}

// 현재 접속 중인 학생들 체크
$current_sessions = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, 
           MIN(m.timecreated) as session_start,
           MAX(m.timecreated) as last_activity
    FROM {user} u
    JOIN {abessi_missionlog} m ON m.userid = u.id
    WHERE m.timecreated > ? AND u.id IN (SELECT id FROM ({$students}) s)
    GROUP BY u.id
    HAVING MAX(m.timecreated) > ?
", array(time() - 7200, time() - 300)); // 2시간 이내 시작, 5분 이내 활동

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>교사용 출결관리 시스템 - Mathking</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .animate-spin { animation: spin 1s linear infinite; }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="attendanceApp()" x-init="init()">
        <!-- 메인 컨테이너 -->
        <div class="min-h-screen bg-gray-50 p-4 md:p-6">
            <!-- 헤더 -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center justify-between space-y-4 md:space-y-0">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">출결관리 시스템</h1>
                        <p class="text-gray-600 mt-2"><?php echo $USER->firstname . ' ' . $USER->lastname; ?> 선생님의 담당 학생 관리</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="calendar" class="w-5 h-5 text-gray-500"></i>
                            <input type="date" x-model="selectedDate" 
                                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <button @click="refreshData()" :disabled="isLoading"
                                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50"
                                title="새로고침">
                            <i data-lucide="refresh-cw" :class="{'animate-spin': isLoading}" class="w-5 h-5"></i>
                        </button>
                        
                        <div class="relative">
                            <button @click="showNotificationPopup = !showNotificationPopup"
                                    class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="알림">
                                <i data-lucide="bell" :class="{'text-red-500': alerts.length > 0}" class="w-5 h-5"></i>
                                <span x-show="alerts.length > 0"
                                      class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse font-medium"
                                      x-text="alerts.length"></span>
                            </button>
                            
                            <!-- 알림 팝업 -->
                            <div x-show="showNotificationPopup" @click.away="showNotificationPopup = false"
                                 x-transition
                                 class="absolute top-full right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 max-h-96 overflow-y-auto">
                                <div class="p-4 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-900">실시간 알림</h3>
                                        <button @click="showNotificationPopup = false"
                                                class="text-gray-400 hover:text-gray-600 p-1 rounded transition-colors">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <template x-if="alerts.length === 0">
                                    <div class="p-6 text-center">
                                        <i data-lucide="check-circle" class="w-8 h-8 text-green-400 mx-auto mb-2"></i>
                                        <p class="text-sm font-medium text-gray-600">모든 상황이 정상입니다</p>
                                    </div>
                                </template>
                                
                                <template x-if="alerts.length > 0">
                                    <div class="divide-y divide-gray-200">
                                        <template x-for="alert in alerts" :key="alert.id">
                                            <div class="p-4 hover:bg-gray-50 transition-colors">
                                                <div class="flex items-start space-x-3">
                                                    <div :class="getAlertColor(alert.priority)" class="p-2 rounded-full flex-shrink-0">
                                                        <i :data-lucide="getAlertIconName(alert.type)" class="w-5 h-5"></i>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-medium text-gray-900 text-sm" x-text="alert.student_name"></div>
                                                        <div class="text-xs text-gray-600 mb-2" x-text="alert.message"></div>
                                                        <div class="text-xs text-gray-500 mb-3" x-text="formatTimeAgo(alert.timestamp)"></div>
                                                        
                                                        <div class="flex space-x-2">
                                                            <button @click="handleAlertAction(alert); showNotificationPopup = false"
                                                                    class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                                                처리하기
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 학생 목록 테이블 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">담당 학생 목록</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">학생 정보</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">보강 예정</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">보강 필요</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">총 휴강</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">상태</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">관리</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="student in students" :key="student.id">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <div class="h-12 w-12 rounded-full bg-blue-100 border-2 border-blue-300 flex items-center justify-center">
                                                    <i data-lucide="user" class="h-6 w-6 text-blue-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <button @click="viewStudentDetail(student)"
                                                        class="text-lg font-semibold text-blue-600 hover:text-blue-800 underline transition-colors"
                                                        x-text="student.name"></button>
                                                <div class="text-sm text-gray-600" x-text="student.course"></div>
                                                <div class="text-xs text-gray-500" x-text="student.email"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div x-show="!isEditing(student.id)" class="text-sm text-gray-900">
                                            <span class="font-semibold text-lg" x-text="student.scheduled_makeup_hours"></span>
                                            <span class="text-gray-500 ml-1">시간</span>
                                        </div>
                                        <input x-show="isEditing(student.id)"
                                               type="number" step="0.5"
                                               x-model="editData.scheduled_makeup_hours"
                                               class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-lg">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div x-show="!isEditing(student.id)" class="text-sm text-gray-900">
                                            <span :class="student.required_makeup_hours > 0 ? 'text-red-600' : 'text-gray-900'"
                                                  class="font-semibold text-lg" x-text="student.required_makeup_hours"></span>
                                            <span class="text-gray-500 ml-1">시간</span>
                                        </div>
                                        <input x-show="isEditing(student.id)"
                                               type="number" step="0.5"
                                               x-model="editData.required_makeup_hours"
                                               class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-lg">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div x-show="!isEditing(student.id)" class="text-sm text-gray-900">
                                            <span class="font-semibold text-lg" x-text="student.total_missed_hours"></span>
                                            <span class="text-gray-500 ml-1">시간</span>
                                        </div>
                                        <input x-show="isEditing(student.id)"
                                               type="number" step="0.5"
                                               x-model="editData.total_missed_hours"
                                               class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-lg">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusColor(student.status)"
                                              class="inline-flex px-3 py-1 text-xs font-semibold rounded-full border"
                                              x-text="student.status"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div x-show="!isEditing(student.id)" class="flex space-x-2">
                                            <button @click="editStudent(student)"
                                                    class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <button @click="markMakeupComplete(student)"
                                                    class="text-green-600 hover:text-green-900 text-xs px-3 py-1 border border-green-600 rounded-lg hover:bg-green-50 transition-colors font-medium">
                                                보강완료
                                            </button>
                                            <button @click="addAbsence(student)"
                                                    class="text-red-600 hover:text-red-900 text-xs px-3 py-1 border border-red-600 rounded-lg hover:bg-red-50 transition-colors font-medium">
                                                휴강추가
                                            </button>
                                        </div>
                                        <div x-show="isEditing(student.id)" class="flex space-x-2">
                                            <button @click="saveEdit(student)" :disabled="isLoading"
                                                    class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors">
                                                <i data-lucide="save" class="w-5 h-5"></i>
                                            </button>
                                            <button @click="cancelEdit()"
                                                    class="text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                                <i data-lucide="x" class="w-5 h-5"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 시스템 자동화 안내 -->
            <div class="mt-6 bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-6">
                <div class="flex">
                    <i data-lucide="book-open" class="w-6 h-6 text-blue-500 mt-0.5"></i>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-blue-800 mb-3">시스템 자동화 안내</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700">
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                    <span><strong>정규수업 15분 경과</strong> → 자동 결석 감지 → 교사 알림</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                    <span><strong>예정외 접속 감지</strong> → 실시간 알림 → 교사 승인 필요</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span><strong>수업시간 연장</strong> → 자동 감지 → 교사 확인 필요</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span><strong>보강수업 완료</strong> → 교사 승인 → 시간 차감</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span><strong>매일 자정</strong> → 일일 정산 → 자동 리포트</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                                    <span><strong>실시간 모니터링</strong> → 접속 상태 추적 → 최적화 제안</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 처리 모달 -->
        <div x-show="activeModal" x-transition
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-bold mb-4" x-text="activeModal?.title"></h3>
                
                <div x-show="activeModal?.type === 'absence'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">결석 사유</label>
                        <select x-model="modalData.reason" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            <option value="">사유 선택</option>
                            <option value="sick">질병</option>
                            <option value="personal">개인사정</option>
                            <option value="unauthorized">무단결석</option>
                            <option value="other">기타</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">보강 필요 시간</label>
                        <input type="number" step="0.5" x-model="modalData.makeup_hours"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div x-show="activeModal?.type === 'makeup'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">보강 완료 시간</label>
                        <input type="number" step="0.5" x-model="modalData.completed_hours"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div x-show="activeModal?.type === 'add_absence'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">휴강 시간</label>
                        <input type="number" step="0.5" x-model="modalData.absence_hours"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">사유</label>
                        <input type="text" x-model="modalData.reason"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div class="flex space-x-3 mt-6">
                    <button @click="activeModal = null"
                            class="flex-1 px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        취소
                    </button>
                    <button @click="processModal()" :disabled="isLoading"
                            class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span x-text="isLoading ? '처리 중...' : '확인'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 로딩 오버레이 -->
        <div x-show="isLoading" x-transition
             class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-40">
            <div class="bg-white rounded-xl p-6 shadow-2xl flex items-center space-x-4">
                <i data-lucide="refresh-cw" class="w-6 h-6 text-blue-600 animate-spin"></i>
                <span class="text-lg font-medium text-gray-900">처리 중...</span>
            </div>
        </div>
    </div>

    <script>
        function attendanceApp() {
            return {
                selectedDate: new Date().toISOString().split('T')[0],
                isLoading: false,
                showNotificationPopup: false,
                editingStudent: null,
                editData: {},
                activeModal: null,
                modalData: {},
                alerts: <?php echo json_encode(array_values($alerts)); ?>,
                students: [],

                init() {
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });
                    
                    this.loadStudents();
                    
                    // 5분마다 데이터 새로고침
                    setInterval(() => {
                        this.refreshData();
                    }, 300000);
                },

                async loadStudents() {
                    this.isLoading = true;
                    try {
                        const response = await fetch('ajax_attendance.php?action=get_students');
                        const data = await response.json();
                        this.students = data.students;
                    } catch (error) {
                        console.error('Failed to load students:', error);
                    } finally {
                        this.isLoading = false;
                        this.$nextTick(() => {
                            lucide.createIcons();
                        });
                    }
                },

                async refreshData() {
                    await this.loadStudents();
                    await this.loadAlerts();
                },

                async loadAlerts() {
                    try {
                        const response = await fetch('ajax_attendance.php?action=get_alerts');
                        const data = await response.json();
                        this.alerts = data.alerts;
                    } catch (error) {
                        console.error('Failed to load alerts:', error);
                    }
                },

                getStatusColor(status) {
                    switch(status) {
                        case "정상": return "text-green-600 bg-green-50 border-green-200";
                        case "보강 필요": return "text-yellow-600 bg-yellow-50 border-yellow-200";
                        case "수업 중": return "text-purple-600 bg-purple-50 border-purple-200";
                        case "예정외 접속": return "text-orange-600 bg-orange-50 border-orange-200";
                        default: return "text-gray-600 bg-gray-50 border-gray-200";
                    }
                },

                getAlertColor(priority) {
                    switch(priority) {
                        case 'urgent': return 'bg-red-100 border-red-300 text-red-800';
                        case 'normal': return 'bg-yellow-100 border-yellow-300 text-yellow-800';
                        default: return 'bg-gray-100 border-gray-300 text-gray-800';
                    }
                },

                getAlertIconName(type) {
                    switch(type) {
                        case 'absence': return 'alert-triangle';
                        case 'unscheduled_access': return 'zap';
                        case 'overtime': return 'timer';
                        default: return 'bell';
                    }
                },

                formatTimeAgo(timestamp) {
                    const diff = Math.floor((Date.now() / 1000 - timestamp) / 60);
                    if (diff < 1) return '방금 전';
                    if (diff < 60) return `${diff}분 전`;
                    const hours = Math.floor(diff / 60);
                    return `${hours}시간 전`;
                },

                viewStudentDetail(student) {
                    window.location.href = `/augmented_teacher/students/scaffolding.php?id=${student.id}`;
                },

                isEditing(studentId) {
                    return this.editingStudent === studentId;
                },

                editStudent(student) {
                    this.editingStudent = student.id;
                    this.editData = {
                        scheduled_makeup_hours: student.scheduled_makeup_hours,
                        required_makeup_hours: student.required_makeup_hours,
                        total_missed_hours: student.total_missed_hours
                    };
                },

                async saveEdit(student) {
                    this.isLoading = true;
                    try {
                        const response = await fetch('ajax_attendance.php?action=update_student', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                student_id: student.id,
                                ...this.editData
                            })
                        });
                        
                        if (response.ok) {
                            await this.loadStudents();
                            this.editingStudent = null;
                            this.editData = {};
                        }
                    } catch (error) {
                        console.error('Failed to save:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                cancelEdit() {
                    this.editingStudent = null;
                    this.editData = {};
                },

                markMakeupComplete(student) {
                    this.activeModal = {
                        type: 'makeup',
                        title: '보강 완료 처리',
                        student_id: student.id
                    };
                    this.modalData = {
                        completed_hours: 2
                    };
                },

                addAbsence(student) {
                    this.activeModal = {
                        type: 'add_absence',
                        title: '휴강 추가',
                        student_id: student.id
                    };
                    this.modalData = {
                        absence_hours: 2,
                        reason: ''
                    };
                },

                handleAlertAction(alert) {
                    if (alert.type === 'absence') {
                        this.activeModal = {
                            type: 'absence',
                            title: '결석 처리',
                            student_id: alert.student_id,
                            alert_id: alert.id
                        };
                        this.modalData = {
                            reason: '',
                            makeup_hours: 2
                        };
                    }
                },

                async processModal() {
                    this.isLoading = true;
                    try {
                        const response = await fetch('ajax_attendance.php?action=process_attendance', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                modal_type: this.activeModal.type,
                                student_id: this.activeModal.student_id,
                                alert_id: this.activeModal.alert_id,
                                data: this.modalData
                            })
                        });
                        
                        if (response.ok) {
                            await this.refreshData();
                            this.activeModal = null;
                            this.modalData = {};
                        }
                    } catch (error) {
                        console.error('Failed to process:', error);
                    } finally {
                        this.isLoading = false;
                    }
                }
            };
        }
    </script>
</body>
</html>