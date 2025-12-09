<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
require_login();
global $DB, $USER;

// URL에서 userid 파라미터 가져오기
$userid = required_param('userid', PARAM_INT);

// 최대 선택 가능한 항목 수
$maxSelection = 3;

// 선택된 항목 처리
$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selectedItems'])) {
    // CSRF 방지
    require_sesskey();

    $selectedItems = $_POST['selectedItems'];

    // 선택된 항목 수 검증
    if (count($selectedItems) !== $maxSelection) {
        // 오류 처리 (필요 시 사용자에게 메시지 표시)
        die('정확히 ' . $maxSelection . '개의 항목을 선택해야 합니다.');
    }

    // 현재 시간
    $timeNow = time();

    // 데이터베이스에 저장할 객체 생성
    $feedbackRecord = new stdClass();
    $feedbackRecord->userid = $userid;
    $feedbackRecord->topic1 = $selectedItems[0];
    $feedbackRecord->topic2 = $selectedItems[1];
    $feedbackRecord->topic3 = $selectedItems[2];
    $feedbackRecord->timecreated = $timeNow;
    $feedbackRecord->timemodified = $timeNow;

    // 기존 레코드가 있는지 확인
    $existingRecord = $DB->get_record('alt42_homefeedback', ['userid' => $userid]);
/*
    if ($existingRecord) {
        // 기존 레코드 업데이트
        $feedbackRecord->id = $existingRecord->id;
        $DB->update_record('alt42_homefeedback', $feedbackRecord);
    } else {
        // 새로운 레코드 삽입
        $DB->insert_record('alt42_homefeedback', $feedbackRecord);
    }
    */
    $DB->insert_record('alt42_homefeedback', $feedbackRecord);
    $update_success = true;
}

// 피드백 항목 목록과 선택된 항목 불러오기
$allFeedbackItems = [
    "자녀의 이번주 학습 컨디션",
    "자녀의 최근 공부 분위기",
    "자녀의 수면상태",
    "학교생활이 원활한 편인가요?",
    "다른 과목 공부가 원활한 편인가요?",
    "자녀가 학습을 즐겁게 받아들이는 편인가요?",
    "자녀가 자신의 학습 성과에 대해 만족하는 모습이 보였나요?",
    "자녀는 규칙적으로 운동을 하나요?",
    "자녀의 건강상태는 양호한가요?",
    "자녀가 식사를 규칙적으로 하고 영양을 고려한 식단을 유지하나요?"
];

// 기존에 저장된 선택된 항목 불러오기
$selectedItems = [];

$existingRecord = $DB->get_record('alt42_homefeedback', ['userid' => $userid]);
if ($existingRecord) {
    $selectedItems = [
        $existingRecord->topic1,
        $existingRecord->topic2,
        $existingRecord->topic3
    ];
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>피드백 항목 선택</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- sesskey를 JavaScript로 전달 -->
    <script>
        var sesskey = '<?= sesskey(); ?>';
    </script>
</head>
<body class="bg-white">
    <div class="max-w-3xl mx-auto p-4 space-y-6">
        <!-- 카드 컴포넌트 -->
        <div class="border rounded-lg shadow">
            <div class="border-b p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-blue-600 flex items-center gap-2">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor">
                            <use xlink:href="#heart"></use>
                        </svg>
                        피드백 항목 선택
                    </h2>
                </div>
                <p class="text-sm text-gray-600 mt-1">
                    원하시는 피드백 항목을 3개 선택해주세요.
                </p>
            </div>
            <div class="p-4">
                <form method="post" action="">
                    <input type="hidden" name="sesskey" value="<?= sesskey(); ?>">
                    <div class="pr-4">

                        <div class="space-y-4" id="feedbackList">
                            <?php
                            foreach ($allFeedbackItems as $index => $item):
                                $isChecked = in_array($item, $selectedItems);
                            ?>
                            <div class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:bg-gray-50">
                                <input
                                    type="checkbox"
                                    id="item-<?= $index ?>"
                                    name="selectedItems[]"
                                    value="<?= htmlspecialchars($item) ?>"
                                    class="form-checkbox h-5 w-5 text-blue-600"
                                    <?= $isChecked ? 'checked' : '' ?>
                                    onclick="handleItemToggle(this)"
                                    data-item="<?= htmlspecialchars($item) ?>"
                                >
                                <label for="item-<?= $index ?>" class="text-gray-700 cursor-pointer flex-grow">
                                    <?= $item ?>
                                </label>
                                <span class="text-sm text-blue-600 <?= $isChecked ? '' : 'hidden' ?>" id="selected-<?= $index ?>">
                                    선택됨
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <button
                            type="button"
                            class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50"
                            onclick="window.location.href='/'"
                        >
                            취소
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                            id="saveButton"
                        >
                            저장하기 (<?= count($selectedItems) ?>/3)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        var selectedItems = <?= json_encode($selectedItems) ?>;
        var maxSelection = 3;

        function handleItemToggle(checkbox) {
            var item = checkbox.getAttribute('data-item');
            var index = checkbox.id.split('-')[1];
            var selectedLabel = document.getElementById('selected-' + index);

            if (checkbox.checked) {
                if (selectedItems.length < maxSelection) {
                    selectedItems.push(item);
                    selectedLabel.classList.remove('hidden');
                } else {
                    alert('최대 ' + maxSelection + '개까지 선택할 수 있습니다.');
                    checkbox.checked = false;
                }
            } else {
                selectedItems = selectedItems.filter(function(i) { return i !== item; });
                selectedLabel.classList.add('hidden');
            }
            updateSaveButton();
        }

        function updateSaveButton() {
            var saveButton = document.getElementById('saveButton');
            saveButton.innerText = '저장하기 (' + selectedItems.length + '/3)';
            saveButton.disabled = selectedItems.length !== maxSelection;
        }

        // 초기 로딩 시 저장 버튼 상태 업데이트
        document.addEventListener('DOMContentLoaded', function() {
            updateSaveButton();
        });

        <?php if ($update_success): ?>
        // 저장 성공 시 SweetAlert 표시
        Swal.fire({
            icon: 'success',
            title: '업데이트 되었습니다.',
            showConfirmButton: false,
            timer: 1500
        });
        window.location.href = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly%20letter.php?userid=<?= $userid ?>';
        <?php endif; ?>
    </script>

    <!-- SVG Symbols for Icons -->
    <svg style="display: none;">
        <symbol id="heart" viewBox="0 0 24 24">
            <!-- Heart icon paths -->
            <path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1 7.8 7.8 7.8-7.8 1-1a5.5 5.5 0 000-7.8z"></path>
        </symbol>
    </svg>
</body>
</html>
