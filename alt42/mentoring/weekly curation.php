<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 학년 그룹 정의 (신규생 추가)
$gradeGroups = [
    'elementary' => [
        'label' => '초등학교',
        'grades' => ['4학년', '5학년', '6학년']
    ],
    'middle' => [
        'label' => '중학교',
        'grades' => ['1학년', '2학년', '3학년']
    ],
    'high' => [
        'label' => '고등학교',
        'grades' => ['1학년', '2학년', '3학년']
    ],
    // 신규생 추가 (학년 없음)
    'newcomer' => [
        'label' => '신규생',
        'grades' => []
    ]
];

// 기존: 월 목록 / 주차 목록
$months = range(1, 12);
$weeks = range(1, 4);

// 신규생용 개월, 주차 목록
$newcomerMonths = [1, 2, 3]; // 1개월, 2개월, 3개월
$newcomerWeeks = range(1,4); // 1~4주차
// 초기 상태 설정
$selectedSchoolType = $_POST['schoolType'] ?? 'elementary';
$selectedGrade = $_POST['grade'] ?? (($selectedSchoolType === 'newcomer') ? '' : '4학년');
$selectedMonth = $_POST['month'] ?? '';
$selectedWeek = $_POST['week'] ?? '';
$action = $_POST['action'] ?? '';

// 내용 저장 및 게시 로직
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save' || $action === 'publish') {
        $contentTitle = $_POST['title'] ?? '';
        $introText = $_POST['introtext'] ?? '';
        $contentLink = $_POST['link'] ?? '';
        $contentStatus = ($action === 'publish') ? 'published' : 'draft';

        // 신규생인 경우 grade를 빈 문자열로 고정
        if ($selectedSchoolType === 'newcomer') {
            $selectedGrade = '';
        }

        $params = [
            'school_type' => $selectedSchoolType,
            'grade' => $selectedGrade,
            'month' => $selectedMonth,
            'week' => $selectedWeek
        ];
        $existingContent = $DB->get_record_select('alt42_weeklycuration', 
            'school_type = :school_type AND grade = :grade AND month = :month AND week = :week', $params);

        if ($existingContent) {
            // 업데이트
            $existingContent->title = $contentTitle;
            $existingContent->introtext = $introText;
            $existingContent->link = $contentLink;
            $existingContent->status = $contentStatus;
            $existingContent->updated_at = time();
            $DB->update_record('alt42_weeklycuration', $existingContent);
        } else {
            // 삽입
            $newContent = new stdClass();
            $newContent->school_type = $selectedSchoolType;
            $newContent->grade = $selectedGrade; // 신규생이면 '', 아니면 지정 학년
            $newContent->month = $selectedMonth;
            $newContent->week = $selectedWeek;
            $newContent->title = $contentTitle;
            $newContent->introtext = $introText;
            $newContent->link = $contentLink;
            $newContent->status = $contentStatus;
            $newContent->created_at = time();
            $newContent->updated_at = time();
            $DB->insert_record('alt42_weeklycuration', $newContent);
        }
    }
}

// 신규생의 경우 grade 조건 없이 조회
if ($selectedSchoolType === 'newcomer') {
    $params = ['school_type' => $selectedSchoolType];
    $existingContents = $DB->get_records_select('alt42_weeklycuration', 
        'school_type = :school_type AND grade = ""', $params);
} else {
    $params = [
        'school_type' => $selectedSchoolType,
        'grade' => $selectedGrade
    ];
    $existingContents = $DB->get_records_select('alt42_weeklycuration', 
        'school_type = :school_type AND grade = :grade', $params);
}


// 선택된 정보에 대한 기존 컨텐츠 불러오기
$contentTitle = '';
$introText = '';
$contentLink = '';
$contentStatus = 'draft';

if ($selectedMonth && $selectedWeek) {
    $params = [
        'school_type' => $selectedSchoolType,
        'grade' => $selectedGrade,
        'month' => $selectedMonth,
        'week' => $selectedWeek
    ];
    $existingContent = $DB->get_record_select('alt42_weeklycuration', 'school_type = :school_type AND grade = :grade AND month = :month AND week = :week', $params);
    if ($existingContent) {
        $contentTitle = $existingContent->title;
        $introText = $existingContent->introtext;
        $contentLink = $existingContent->link;
        $contentStatus = $existingContent->status;
    }
}

// 신규생이 아닌 경우에만 grade 별 content
if ($selectedSchoolType !== 'newcomer') {
    $params = [
        'school_type' => $selectedSchoolType,
        'grade' => $selectedGrade
    ];
    $existingContents = $DB->get_records_select('alt42_weeklycuration', 'school_type = :school_type AND grade = :grade', $params);
} else {
    // 신규생은 grade 없이 school_type만
    $params = [
        'school_type' => $selectedSchoolType
    ];
    $existingContents = $DB->get_records_select('alt42_weeklycuration', 'school_type = :school_type', $params);
}

// 최근 1개월 동안 업데이트된 컨텐츠 불러오기
$oneMonthAgo = time() - (30 * 24 * 60 * 60); // 30일 전
$recentContents = $DB->get_records_select('alt42_weeklycuration', 'updated_at >= :one_month_ago', ['one_month_ago' => $oneMonthAgo]);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Content Manager</title>
  <!-- Tailwind CSS 포함 -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/lucide-react@latest/dist/lucide-react.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
<div class="w-full max-w-6xl mx-auto p-4">
    <div class="mt-4 border rounded shadow-sm bg-white">
        <div class="p-0">
            <!-- Tabs -->
            <div>
                <!-- 상단 학교급 탭 -->
                <div class="border-b flex">
                    <?php foreach ($gradeGroups as $key => $group): ?>
                        <button
                            class="flex-1 h-12 transition-colors focus:outline-none <?php echo ($selectedSchoolType === $key) ? 'bg-white border-b-2 border-blue-500' : 'bg-gray-100'; ?>"
                            onclick="changeSchoolType('<?php echo $key; ?>')"
                        >
                            <?php echo $group['label']; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 각 학교급별 콘텐츠 -->
            <?php foreach ($gradeGroups as $key => $group): ?>
                <div class="px-4 py-6 <?php echo ($selectedSchoolType === $key) ? '' : 'hidden'; ?>" id="content-<?php echo $key; ?>">
                    <form method="post" id="contentForm" name="contentForm">
                        <!-- 숨겨진 필드 -->
                        <input type="hidden" name="schoolType" id="inputSchoolType" value="<?php echo htmlspecialchars($selectedSchoolType); ?>">
                        <input type="hidden" name="grade" id="inputGrade" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                        <input type="hidden" name="month" id="inputMonth" value="<?php echo htmlspecialchars($selectedMonth); ?>">
                        <input type="hidden" name="week" id="inputWeek" value="<?php echo htmlspecialchars($selectedWeek); ?>">
                        <input type="hidden" name="status" id="inputStatus" value="<?php echo htmlspecialchars($contentStatus); ?>">

                        <!-- 선택된 정보 표시 -->
                        <div class="mb-6 p-4 bg-slate-50 rounded-lg flex items-center">
                            <span class="font-semibold mr-2">현재:</span>
                            <span class="text-slate-600" id="currentSelection">
                                <?php 
                                if ($key !== 'newcomer') {
                                    echo $group['label'], ' ', $selectedGrade; 
                                    if ($selectedMonth) echo ' > ', $selectedMonth, '월';
                                    if ($selectedWeek) echo ' > ', $selectedWeek, '주차';
                                } else {
                                    // 신규생
                                    echo $group['label'];
                                    if ($selectedMonth) echo ' > ', $selectedMonth, '개월';
                                    if ($selectedWeek) echo ' > ', $selectedWeek, '주차';
                                }
                                ?>
                            </span>
                        </div>

                        <?php if ($key !== 'newcomer'): ?>
                            <!-- 기존 학년 선택 -->
                            <div class="flex gap-4 mb-8">
                                <?php foreach ($group['grades'] as $grade): ?>
                                    <button
                                        type="button"
                                        class="flex-1 px-4 py-2 rounded <?php echo ($selectedGrade === $grade) ? 'bg-blue-500 text-white' : 'bg-gray-100'; ?>"
                                        onclick="changeGrade('<?php echo $selectedSchoolType; ?>', '<?php echo $grade; ?>')"
                                    >
                                        <?php echo $grade; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- 월 선택 -->
                            <div class="grid grid-cols-6 gap-4 mb-8">
                                <?php foreach ($months as $month): ?>
                                    <button
                                        type="button"
                                        class="w-full py-2 rounded <?php echo ($selectedMonth == $month) ? 'bg-blue-500 text-white' : 'bg-gray-100'; ?>"
                                        onclick="changeMonth('<?php echo $selectedSchoolType; ?>', <?php echo $month; ?>)"
                                    >
                                        <?php echo $month; ?>월
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- 주차 선택 -->
                            <div class="grid grid-cols-4 gap-4 mb-8">
                                <?php foreach ($weeks as $week): ?>
                                    <button
                                        type="button"
                                        class="w-full py-2 rounded <?php echo ($selectedWeek == $week) ? 'bg-blue-500 text-white' : 'bg-gray-100'; ?>"
                                        onclick="changeWeek('<?php echo $selectedSchoolType; ?>', <?php echo $week; ?>)"
                                    >
                                        <?php echo $week; ?>주차
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- 신규생 탭: 개월 선택 -->
                            <div class="flex gap-4 mb-8">
                                <?php foreach ($newcomerMonths as $nMonth): ?>
                                    <button
                                        type="button"
                                        class="flex-1 px-4 py-2 rounded <?php echo ($selectedMonth == $nMonth) ? 'bg-blue-500 text-white' : 'bg-gray-100'; ?>"
                                        onclick="changeNewcomerMonth('<?php echo $selectedSchoolType; ?>', <?php echo $nMonth; ?>)"
                                    >
                                        <?php echo $nMonth; ?>개월
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- 신규생 주차 선택 -->
                            <?php if ($selectedMonth): ?>
                                <div class="grid grid-cols-4 gap-4 mb-8">
                                    <?php foreach ($newcomerWeeks as $week): ?>
                                        <button
                                            type="button"
                                            class="w-full py-2 rounded <?php echo ($selectedWeek == $week) ? 'bg-blue-500 text-white' : 'bg-gray-100'; ?>"
                                            onclick="changeNewcomerWeek('<?php echo $selectedSchoolType; ?>', <?php echo $week; ?>)"
                                        >
                                            <?php echo $week; ?>주차
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- 컨텐츠 제목 입력 -->
                        <div class="space-y-2 mb-4">
                            <label class="text-sm font-medium"><b>컨텐츠 제목</b></label>
                            <input
                                type="text"
                                name="title"
                                placeholder="컨텐츠 제목을 입력하세요"
                                value="<?php echo htmlspecialchars($contentTitle); ?>"
                                class="border border-gray-300 rounded w-full p-2"
                                id="contentTitle"
                            />
                        </div>

                        <!-- 소개 문구 입력 -->
                        <div class="space-y-2 mb-4">
                            <label class="text-sm font-medium"><b>소개 문구</b></label>
                            <textarea
                                name="introtext"
                                placeholder="소개 문구를 입력하세요"
                                class="border border-gray-300 rounded w-full p-2 resize-y"
                                style="min-height: 5em;"
                                rows="5"
                                id="introText"
                            ><?php echo htmlspecialchars($introText); ?></textarea>
                        </div>

                        <!-- 컨텐츠 링크 입력 -->
                        <div class="space-y-2 mb-8">
                            <label class="text-sm font-medium"><b>컨텐츠 링크</b> &nbsp;&nbsp;&nbsp;&nbsp;<a href="https://www.eduground.co.kr/" target="_blank">(주)에듀그라운드</a> | <a href="https://blog.naver.com/wonseong0712/223659053023" target="_blank">공부과학 블로그</a></label>
                            <input
                                type="url"
                                name="link"
                                placeholder="https://example.com/content"
                                value="<?php echo htmlspecialchars($contentLink); ?>"
                                class="border border-gray-300 rounded w-full p-2"
                                id="contentLink"
                            />
                        </div>

                        <!-- 버튼 그룹 -->
                        <div class="flex space-x-4">
                            <button
                                type="submit"
                                name="action"
                                value="save"
                                class="flex items-center space-x-2 border border-gray-300 px-4 py-2 rounded"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24"><path d="M19 21H5V3h10l4 4v14zM13 5h-4v2h4V5zm-4 14v-8h4v8h-4z"/></svg>
                                <span>준비</span>
                            </button>
                            <button
                                type="submit"
                                name="action"
                                value="publish"
                                class="flex items-center space-x-2 bg-blue-500 text-white px-4 py-2 rounded"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24"><path d="M2 21h19v-2H2v2zm19-9V3H3v9H1v2h2v7h18v-7h2v-2h-2zm-4-3H5V5h12v4z"/></svg>
                                <span>적용</span>
                            </button>
                        </div>

                        <!-- 상태 표시 -->
                        <?php if ($action === 'publish' && $contentStatus === 'published'): ?>
                            <div class="text-green-600 text-sm mt-4">
                                ✓ 컨텐츠가 성공적으로 적용되었습니다.
                            </div>
                        <?php elseif ($action === 'save' && $contentStatus === 'draft'): ?>
                            <div class="text-yellow-600 text-sm mt-4">
                                ✓ 컨텐츠가 임시 저장되었습니다.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>

            <!-- 최근 1개월 업데이트된 컨텐츠 표시 -->
            <?php if ($recentContents): ?>
                <div class="px-4 py-6">
                    <h2 class="text-lg font-semibold mb-4">최근 1개월 동안 업데이트된 컨텐츠</h2>
                    <table class="w-full table-auto border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border px-4 py-2">학교급</th>
                                <th class="border px-4 py-2">학년</th>
                                <th class="border px-4 py-2">월/개월</th>
                                <th class="border px-4 py-2">주차</th>
                                <th class="border px-4 py-2">제목</th>
                                <th class="border px-4 py-2">소개 문구</th>
                                <th class="border px-4 py-2">링크</th>
                                <th class="border px-4 py-2">상태</th>
                                <th class="border px-4 py-2">업데이트 시간</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentContents as $content): ?>
                                <tr>
                                    <td class="border px-4 py-2 text-center"><?php echo $gradeGroups[$content->school_type]['label']; ?></td>
                                    <td class="border px-4 py-2 text-center"><?php echo htmlspecialchars($content->grade); ?></td>
                                    <td class="border px-4 py-2 text-center"><?php echo htmlspecialchars($content->month); ?><?php echo ($content->school_type === 'newcomer')?'개월':'월'; ?></td>
                                    <td class="border px-4 py-2 text-center"><?php echo htmlspecialchars($content->week); ?>주차</td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($content->title); ?></td>
                                    <td class="border px-4 py-2"><?php echo nl2br(htmlspecialchars($content->introtext)); ?></td>
                                    <td class="border px-4 py-2">
                                        <a href="<?php echo htmlspecialchars($content->link); ?>" target="_blank" class="text-blue-500 underline">
                                            링크
                                        </a>
                                    </td>
                                    <td class="border px-4 py-2 text-center">
                                        <?php echo ($content->status === 'published') ? '적용됨' : '준비중'; ?>
                                    </td>
                                    <td class="border px-4 py-2 text-center">
                                        <?php echo date('Y-m-d H:i:s', $content->updated_at); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
  let selectedSchoolType = '<?php echo $selectedSchoolType; ?>';
  let selectedGrade = '<?php echo $selectedGrade; ?>';
  let selectedMonth = '<?php echo $selectedMonth; ?>';
  let selectedWeek = '<?php echo $selectedWeek; ?>';
  let contentStatus = '<?php echo $contentStatus; ?>';

  const gradeGroups = {
    'elementary': {
      label: '초등학교',
      grades: ['4학년', '5학년', '6학년']
    },
    'middle': {
      label: '중학교',
      grades: ['1학년', '2학년', '3학년']
    },
    'high': {
      label: '고등학교',
      grades: ['1학년', '2학년', '3학년']
    },
    'newcomer': {
      label: '신규생',
      grades: []
    }
  };

  function changeSchoolType(type) {
    selectedSchoolType = type;
    selectedGrade = (type === 'newcomer') ? '' : gradeGroups[type].grades[0];
    selectedMonth = '';
    selectedWeek = '';
    document.getElementById('inputSchoolType').value = selectedSchoolType;
    document.getElementById('inputGrade').value = selectedGrade;
    document.getElementById('inputMonth').value = '';
    document.getElementById('inputWeek').value = '';
    document.forms['contentForm'].submit();
  }

  function changeGrade(schoolType, grade) {
    selectedGrade = grade;
    selectedMonth = '';
    selectedWeek = '';
    document.getElementById('inputGrade').value = selectedGrade;
    document.getElementById('inputMonth').value = '';
    document.getElementById('inputWeek').value = '';
    document.forms['contentForm'].submit();
  }

  function changeMonth(schoolType, month) {
    selectedMonth = month;
    selectedWeek = '';
    document.getElementById('inputMonth').value = selectedMonth;
    document.getElementById('inputWeek').value = '';
    document.forms['contentForm'].submit();
  }

  // 신규생 전용 변경 함수
  function changeNewcomerMonth(schoolType, month) {
    selectedMonth = month;
    selectedWeek = '';
    document.getElementById('inputMonth').value = selectedMonth;
    document.getElementById('inputWeek').value = '';
    document.forms['contentForm'].submit();
  }

  function changeWeek(schoolType, week) {
    selectedWeek = week;
    document.getElementById('inputWeek').value = selectedWeek;
    document.forms['contentForm'].submit();
  }

  function changeNewcomerWeek(schoolType, week) {
    selectedWeek = week;
    document.getElementById('inputWeek').value = selectedWeek;
    document.forms['contentForm'].submit();
  }
</script>
</body>
</html>
