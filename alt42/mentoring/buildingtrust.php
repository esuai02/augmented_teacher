<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid=$_GET["userid"]; 
// 사용자 역할 가져오기
// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ?", array($USER->id));
$role = isset($userrole->role) ? $userrole->role : '';
//if($role==='teacher' || $role==='manager')$studentid=$userid;
//else $studentid=$USER->id;

$role = '';
if (is_siteadmin()) {
    $role = 'manager';
} else {
    $roles = get_user_roles(context_system::instance(), $USER->id);
    foreach ($roles as $r) {
        $role = $r->shortname;
        break;
    }
}

$can_add_consultation_type = false;
if ($role === 'manager' || $role === 'teacher') {
    $can_add_consultation_type = true;
}

// 상대방의 userid를 가져오는 함수 (예시 구현)
function get_recipient_id($userid, $recipientType) {
    global $DB;
    // 실제로는 사용자 간의 관계를 고려하여 상대방의 ID를 찾아야 합니다.
    // 여기서는 단순히 테스트를 위해 임의의 사용자 ID를 반환합니다.
    if ($recipientType === 'teacher') {
        // 예: 선생님 ID 반환 (실제 로직 필요)
        return 2;
    } else if ($recipientType === 'parent') {
        // 예: 학부모 ID 반환 (실제 로직 필요)
        return 3;
    }
    return 0;
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_consultation_type') {
        require_sesskey(); // CSRF 방지

        // 새로운 상담 유형 추가 처리
        $usertype = required_param('usertype', PARAM_TEXT);
        $title = required_param('title', PARAM_TEXT);
        $description = required_param('description', PARAM_TEXT);

        // 새로운 레코드 생성
        $newrecord = new stdClass();
        $newrecord->userid = $USER->id;
        $newrecord->mode = ''; // 필요에 따라 설정
        $newrecord->usertype = $usertype;
        $newrecord->type = ''; // 필요에 따라 설정
        $newrecord->title = $title;
        $newrecord->text = $description;
        $newrecord->gptlink = $gptlink; // gptlink 필드 설정
        $newrecord->hide = 1; // hide=1이면 상담 유형
        $newrecord->targetgrade = ''; // 필요에 따라 설정
        $newrecord->targettype = ''; // 필요에 따라 설정
        $newrecord->timecreated = time(); // UNIX 타임스탬프로 저장

        $DB->insert_record('alt42_consulting', $newrecord, false);

        // 페이지 새로고침
        echo '<script>window.location.reload();</script>';
        exit();
    } else if ($_POST['action'] == 'request_consultation') {
        require_sesskey(); // CSRF 방지

        // 상담 요청 처리
        $consultationid = required_param('consultationid', PARAM_INT);
        $mode = required_param('mode', PARAM_ALPHA);
        $recipientType = required_param('recipient', PARAM_ALPHA);

        // 상담 유형 정보 가져오기
        $consultation = $DB->get_record('alt42_consulting', array('id' => $consultationid));

        if ($consultation) {
           

            // 새로운 상담 요청 생성
            $newrequest = new stdClass();
            $newrequest->userid = $USER->id;
            $newrequest->studentid = $studentid;
            $newrequest->consultationid = $consultationid;
            $newrequest->mode = $mode;
            $newrequest->status = 'pending';
            $newrequest->timecreated = time(); // UNIX 타임스탬프로 저장

            $DB->insert_record('alt42_consulting_requests', $newrequest, false);

            // 응답 메시지 생성
            $message = '';
            if ($mode === 'offline') {
                if ($recipientType === 'parent') {
                    $message = '학부모와 상담일정을 잡은 후 답변드리겠습니다.';
                } else if ($recipientType === 'teacher') {
                    $message = '상담일정을 확인 후 안내 메세지를 보내드리겠습니다.';
                }
            } else {
                $message = ($recipientType === 'teacher' ? '선생님' : '학부모님') . '에게 온라인 상담 신청이 전달되었습니다. 답변이 있을 때까지 2일 간격으로 최대 1주일간 전달이 진행됩니다.';
            }

            // 응답 HTML 생성
            echo '
            <div class="p-6 bg-white rounded-lg shadow text-center">
                <svg class="h-16 w-16 mx-auto text-green-500" fill="none" stroke="currentColor" stroke-width="2">
                    <use xlink:href="#check-circle"></use>
                </svg>
                <h2 class="text-2xl font-bold mt-4">신청이 완료되었습니다!</h2>
                <p class="text-gray-600 mt-2 text-lg">' . $message . '</p>
                <button class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-base md:text-lg"
                    onclick="window.location.reload()">
                    돌아가기
                </button>
            </div>
            ';
            exit();
        } else {
            echo '유효하지 않은 상담 유형입니다.';
            exit();
        }
    }
}


if ($_GET['action'] === 'get_consultation_type') {
    $id = required_param('id', PARAM_INT);
    $record = $DB->get_record('alt42_consulting', array('id' => $id));
    if ($record) {
        echo json_encode($record);
    } else {
        echo json_encode([]);
    }
    exit();
}

if ($_POST['action'] == 'edit_consultation_type') {
    require_sesskey();
    $consultationid = required_param('consultationid', PARAM_INT);
    $usertype = required_param('usertype', PARAM_TEXT);
    $title = required_param('title', PARAM_TEXT);
    $description = required_param('description', PARAM_TEXT);
    $gptlink = required_param('gptlink', PARAM_TEXT);

    $updatedrecord = $DB->get_record('alt42_consulting', array('id' => $consultationid));
    if ($updatedrecord) {
        $updatedrecord->usertype = $usertype;
        $updatedrecord->title = $title;
        $updatedrecord->text = $description;
        $updatedrecord->gptlink = $gptlink;
        $DB->update_record('alt42_consulting', $updatedrecord);
    }

    echo '<script>window.location.reload();</script>';
    exit();
}

// 상담 유형 가져오기
$sql_compare_usertype = $DB->sql_compare_text('usertype');
$sql_compare_param = $DB->sql_compare_text(':usertype');

$teacher_select = "hide = :hide AND $sql_compare_usertype = $sql_compare_param";
$parent_select = "hide = :hide AND $sql_compare_usertype = $sql_compare_param";

$params_teacher = array('hide' => 1, 'usertype' => 'teacher');
$params_parent = array('hide' => 1, 'usertype' => 'parent');

$teacherConsultations = $DB->get_records_select('alt42_consulting', $teacher_select, $params_teacher);
$parentConsultations = $DB->get_records_select('alt42_consulting', $parent_select, $params_parent);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>상담 신청</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto p-4 space-y-6">
        <!-- 새로운 상담 유형 추가 버튼 -->
        <?php if ($can_add_consultation_type): ?>
        <div class="flex justify-end">
            <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                onclick="showAddConsultationTypeForm()">
                새로운 상담 유형 추가
            </button>
        </div>
        <?php endif; ?>

        <!-- 탭 -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex overflow-x-auto no-scrollbar" aria-label="Tabs">
                <?php if ($role === 'teacher' || $role === 'manager'): ?>
                    <a href="#teacher-mode" class="tab-link text-gray-500 hover:text-gray-700 whitespace-nowrap py-4 px-3 border-b-2 font-medium text-base">
                        선생님 모드 
                    </a>  
                    <a href="#parent-mode" class="tab-link text-gray-500 hover:text-gray-700 whitespace-nowrap py-4 px-3 border-b-2 font-medium text-base">
                        학부모 모드
                    </a>
                <?php else: ?>
                    <a href="#parent-mode" class="tab-link text-gray-500 hover:text-gray-700 whitespace-nowrap py-4 px-3 border-b-2 font-medium text-base">
                        학부모 모드
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- 상담 모드 선택 -->
        <div class="flex items-center space-x-4 mt-4">
            <label class="flex items-center text-base">
                <input type="radio" name="consultationMode" value="online" checked onclick="changeConsultationMode('online')">
                <span class="ml-2">온라인 상담</span>
            </label>
            <label class="flex items-center text-base">
                <input type="radio" name="consultationMode" value="offline" onclick="changeConsultationMode('offline')">
                <span class="ml-2">대면 상담</span>
            </label>
        </div>

        <!-- 컨텐츠 영역 -->
        <div id="content">
            <!-- 선생님 모드 -->
            <?php if ($role === 'teacher' || $role === 'manager'): ?>
            <div id="teacher-mode" class="tab-content hidden">
                <h2 class="text-2xl font-bold text-blue-600 mt-4">선생님 모드  <a href="https://chatgpt.com/share/e/6761726c-7440-8008-9333-1a0eff9ca559"target="_blank">(학부모 페르소나 생성기)</a></h2>
                <div class="grid grid-cols-1 gap-6 mt-4 md:grid-cols-2">
                <?php foreach ($teacherConsultations as $item): ?>
                <div class="p-4 bg-white rounded-lg shadow">
                    <h3 class="text-lg md:text-xl font-semibold"><?= format_string($item->title) ?></h3>
                    <p class="text-gray-600 mt-2 text-base md:text-lg"><?= format_text($item->text) ?></p>
                    <div class="flex space-x-2 mt-4">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-base md:text-lg"
                            onclick="handleRequest('<?= htmlspecialchars($item->title, ENT_QUOTES) ?>', 'parent', <?= $item->id ?>)">
                            신청
                        </button>
                        
                        <!-- gptlink 도구메뉴 버튼 (gptlink 존재 시) -->
                        <?php if (!empty($item->gptlink)): ?>
                        <button class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 text-base md:text-lg"
                            onclick="openGptLink('<?= htmlspecialchars($item->gptlink, ENT_QUOTES) ?>')">
                            도구
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($can_add_consultation_type): ?>
                        <button class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-base md:text-lg"
                            onclick="editConsultationType(<?= $item->id ?>)">
                            편집
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 학부모 모드 -->
            <div id="parent-mode" class="tab-content hidden">
                <h2 class="text-2xl font-bold text-blue-600 mt-4">학부모 모드</h2>
                <div class="grid grid-cols-1 gap-6 mt-4 md:grid-cols-2">
                    <?php foreach ($parentConsultations as $item): ?>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <h3 class="text-lg md:text-xl font-semibold"><?= format_string($item->title) ?></h3>
                        <p class="text-gray-600 mt-2 text-base md:text-lg"><?= format_text($item->text) ?></p>
                        <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 w-full sm:w-auto text-base md:text-lg"
                            onclick="handleRequest('<?= htmlspecialchars($item->title, ENT_QUOTES) ?>', 'teacher', <?= $item->id ?>)">
                            신청
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Consultation Type Form -->
    <?php if ($can_add_consultation_type): ?>
    <div id="add-consultation-type-form" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">새로운 상담 유형 추가</h2>
            <form method="post" action="" onsubmit="return submitConsultationTypeForm(this);">
                <input type="hidden" name="action" value="add_consultation_type">
                <input type="hidden" name="sesskey" value="<?= sesskey() ?>">
                <div class="mb-4">
                    <label class="block text-gray-700">상담 모드</label>
                    <select name="usertype" class="w-full mt-2 p-2 border rounded">
                        <option value="teacher">선생님 모드</option>
                        <option value="parent">학부모 모드</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">제목</label>
                    <input type="text" name="title" class="w-full mt-2 p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">설명</label>
                    <textarea name="description" class="w-full mt-2 p-2 border rounded" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">GPT Link</label>
                    <input type="text" name="gptlink" class="w-full mt-2 p-2 border rounded" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 mr-2"
                        onclick="hideAddConsultationTypeForm()">취소</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">추가</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- JavaScript -->
    <script>
        function openGptLink(gptlink) {
            window.open(gptlink, '_blank');
        }
        // 탭 기능 구현
        function editConsultationType(id) {
                // AJAX 요청으로 해당 상담유형 데이터 가져오기
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '?action=get_consultation_type&id=' + id, true);
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);

                        // 폼 표시
                        showAddConsultationTypeForm();

                        // action을 edit_consultation_type으로 변경
                        document.querySelector('input[name="action"]').value = 'edit_consultation_type';
                        // 해당 상담유형 ID를 hidden 필드로 추가
                        let hiddenIdInput = document.querySelector('input[name="consultationid"]');
                        if (!hiddenIdInput) {
                            hiddenIdInput = document.createElement('input');
                            hiddenIdInput.type = 'hidden';
                            hiddenIdInput.name = 'consultationid';
                            document.querySelector('#add-consultation-type-form form').appendChild(hiddenIdInput);
                        }
                        hiddenIdInput.value = data.id;

                        // 폼에 값 할당
                        document.querySelector('select[name="usertype"]').value = data.usertype;
                        document.querySelector('input[name="title"]').value = data.title;
                        document.querySelector('textarea[name="description"]').value = data.text;
                        // gptlink 필드도 존재한다면 값 할당
                        document.querySelector('input[name="gptlink"]').value = data.gptlink;
                    }
                };
                xhr.send();
            }


        document.addEventListener('DOMContentLoaded', function() {
            var tabs = document.querySelectorAll('.tab-link');
            var contents = document.querySelectorAll('.tab-content');

            function hideAllContents() {
                contents.forEach(function(content) {
                    content.classList.add('hidden');
                });
                tabs.forEach(function(tab) {
                    tab.classList.remove('border-blue-500', 'text-blue-600');
                });
            }

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = document.querySelector(this.getAttribute('href'));
                    hideAllContents();
                    target.classList.remove('hidden');
                    this.classList.add('border-blue-500', 'text-blue-600');
                });
            });

            // 초기 활성 탭 설정
            tabs[0].click();
        });

        function changeConsultationMode(mode) {
            console.log("Selected consultation mode:", mode);
        }

        function handleRequest(title, recipient, consultationId) {
            var mode = document.querySelector('input[name="consultationMode"]:checked').value;

            var xhr = new XMLHttpRequest();
            var params = `action=request_consultation&consultationid=${consultationId}&mode=${mode}&recipient=${recipient}&sesskey=<?= sesskey() ?>`;
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var contentDiv = document.getElementById('content');
                    contentDiv.innerHTML = xhr.responseText;
                } else {
                    alert('오류가 발생했습니다. 다시 시도해주세요.');
                }
            };
            xhr.send(params);
        }

        function showAddConsultationTypeForm() {
            document.getElementById('add-consultation-type-form').classList.remove('hidden');
        }

        function hideAddConsultationTypeForm() {
            document.getElementById('add-consultation-type-form').classList.add('hidden');
        }

        function submitConsultationTypeForm(form) {
            var formData = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    window.location.reload();
                } else {
                    alert('오류가 발생했습니다. 다시 시도해주세요.');
                }
            };
            xhr.send(formData);
            return false;
        }
    </script>

    <!-- SVG Symbols for Icons -->
    <svg style="display: none;">
        <symbol id="check-circle" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9 12l2 2 4-4"></path>
        </symbol>
    </svg>
</body>
</html>
