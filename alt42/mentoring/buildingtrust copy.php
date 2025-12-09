<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 사용자 역할 가져오기
$role = ''; // 초기화
if (is_siteadmin()) {
    $role = 'manager';
} else {
    $roles = get_user_roles(context_system::instance(), $USER->id);
    foreach ($roles as $r) {
        // 역할의 짧은 이름을 사용한다고 가정
        $role = $r->shortname;
        break;
    }
}
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
                <h2 class="text-2xl font-bold text-blue-600 mt-4">선생님 모드</h2>
                <div class="grid grid-cols-1 gap-6 mt-4 md:grid-cols-2">
                    <?php
                    $teacherConsultations = [
                        [
                            "title" => "학생의 학습 태도 변화",
                            "description" => "수업 중 집중력 저하나 산만한 행동이 지속될 때 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "과제 수행 문제",
                            "description" => "숙제 미제출이나 완성도가 낮은 경우 학부모님께 상담을 요청합니다."
                        ],
                        // 나머지 항목 추가
                        [
                            "title" => "성적 하락",
                            "description" => "평가 결과가 지속적으로 떨어질 때 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "결석 및 지각 빈번",
                            "description" => "수업에 자주 결석하거나 지각하는 경우 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "수업 참여도 저조",
                            "description" => "질문이나 토론에 소극적이거나 무관심한 태도를 보일 때 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "교우 관계 문제",
                            "description" => "다른 학생들과의 갈등이나 소외 현상이 나타날 때 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "학습 동기 저하",
                            "description" => "학습에 대한 의욕이나 흥미가 감소한 경우 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "학습 진도 부진",
                            "description" => "예상보다 학습 진도가 느리거나 목표에 미치지 못할 때 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "시험 대비 필요성",
                            "description" => "중요한 시험을 앞두고 학습 전략이나 계획에 대한 논의가 필요할 때 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "학습 습관 문제",
                            "description" => "집에서의 학습 습관이나 환경에 개선이 필요할 때 학부모님께 상담을 요청합니다."
                        ],
                        [
                            "title" => "특별한 상황 발생",
                            "description" => "가정 내 변화나 개인적인 사유로 학습에 영향을 미칠 수 있는 상황이 발생했을 때 학부모님께 상담을 요청합니다."
                        ],
                    ];
                    foreach ($teacherConsultations as $item):
                    ?>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <h3 class="text-lg md:text-xl font-semibold"><?= $item['title'] ?></h3>
                        <p class="text-gray-600 mt-2 text-base md:text-lg"><?= $item['description'] ?></p>
                        <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 w-full sm:w-auto text-base md:text-lg"
                            onclick="handleRequest('<?= htmlspecialchars($item['title']) ?>', 'parent')">
                            신청
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 학부모 모드 -->
            <div id="parent-mode" class="tab-content hidden">
                <h2 class="text-2xl font-bold text-blue-600 mt-4">학부모 모드</h2>
                <div class="grid grid-cols-1 gap-6 mt-4 md:grid-cols-2">
                    <?php
                    $parentConsultations = [
                        [
                            "title" => "학교 시험 결과 분석",
                            "description" => "자녀의 시험 성적에 대한 상세한 분석과 피드백을 요청하여 강점과 보완할 점을 파악합니다."
                        ],
                        [
                            "title" => "방학 계획 안내",
                            "description" => "방학 중 학습 및 활동 계획에 대한 조언을 구하여 자녀의 효율적인 시간 활용을 돕습니다."
                        ],
                        // 나머지 항목 추가
                        [
                            "title" => "고등학교 선택 상담",
                            "description" => "자녀의 적성과 성적을 고려한 고등학교 선택에 대한 조언을 받아 진로 결정에 도움을 받습니다."
                        ],
                        [
                            "title" => "학습 태도 평가",
                            "description" => "수업 중 자녀의 참여도와 집중력에 대한 평가를 요청하여 학습 습관 개선에 활용합니다."
                        ],
                        [
                            "title" => "교우 관계 파악",
                            "description" => "자녀의 친구 관계와 사회성 발달에 대한 정보를 얻어 원만한 대인 관계 형성을 지원합니다."
                        ],
                        [
                            "title" => "특별 지원 필요 여부",
                            "description" => "자녀가 학습이나 정서적으로 추가적인 지원이 필요한지에 대한 평가를 요청합니다."
                        ],
                        [
                            "title" => "학교 행사 및 프로그램 정보",
                            "description" => "학교에서 진행되는 행사나 특별 프로그램에 대한 정보를 요청하여 자녀의 참여를 독려합니다."
                        ],
                        [
                            "title" => "생활기록부 작성 내용 확인",
                            "description" => "자녀의 생활기록부에 기재될 내용에 대한 설명을 듣고, 필요한 경우 추가 정보를 제공합니다."
                        ],
                        [
                            "title" => "진로 및 적성 상담",
                            "description" => "자녀의 흥미와 능력을 고려한 진로 상담을 통해 미래 계획 수립에 도움을 받습니다."
                        ],
                        [
                            "title" => "학습 자료 및 참고서 추천",
                            "description" => "자녀의 학습 수준에 맞는 교재나 참고서를 추천받아 학습 효율을 높입니다."
                        ],
                        [
                            "title" => "학교 규정 및 정책 문의",
                            "description" => "학교의 규정이나 정책에 대한 궁금증을 해소하여 자녀의 학교 생활을 지원합니다."
                        ],
                        [
                            "title" => "자녀의 정서 상태 파악",
                            "description" => "학교에서의 자녀의 정서적 안녕과 관련된 정보를 얻어 가정에서의 지원 방안을 모색합니다."
                        ],
                    ];
                    foreach ($parentConsultations as $item):
                    ?>
                    <div class="p-4 bg-white rounded-lg shadow">
                        <h3 class="text-lg md:text-xl font-semibold"><?= $item['title'] ?></h3>
                        <p class="text-gray-600 mt-2 text-base md:text-lg"><?= $item['description'] ?></p>
                        <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 w-full sm:w-auto text-base md:text-lg"
                            onclick="handleRequest('<?= htmlspecialchars($item['title']) ?>', 'teacher')">
                            신청
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // 탭 기능 구현
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
            // 여기에서 상담 모드에 따른 추가 로직을 구현할 수 있습니다.
        }

        function handleRequest(title, recipient) {
            // 상담 모드 가져오기
            var mode = document.querySelector('input[name="consultationMode"]:checked').value;
            var modeText = mode === 'online' ? '온라인' : '대면';

            // 화면 전환 및 메시지 표시
            var contentDiv = document.getElementById('content');

            var message = '';
            if (mode === 'offline') {
                if (recipient === 'parent') {
                    // 선생님 모드에서 대면 상담 신청 시
                    message = '학부모와 상담일정을 잡은 후 답변드리겠습니다.';
                } else if (recipient === 'teacher') {
                    // 학부모 모드에서 대면 상담 신청 시
                    message = '상담일정을 확인 후 안내 메세지를 보내드리겠습니다.';
                }
            } else {
                // 온라인 상담 신청 시
                message = `${recipient === 'teacher' ? '선생님' : '학부모님'}에게 ${modeText} 상담 신청이 전달되었습니다. 답변이 있을 때까지 2일 간격으로 최대 1주일간 전달이 진행됩니다.`;
            }

            contentDiv.innerHTML = `
                <div class="p-6 bg-white rounded-lg shadow text-center">
                    <svg class="h-16 w-16 mx-auto text-green-500" fill="none" stroke="currentColor" stroke-width="2">
                        <use xlink:href="#check-circle"></use>
                    </svg>
                    <h2 class="text-2xl font-bold mt-4">신청이 완료되었습니다!</h2>
                    <p class="text-gray-600 mt-2 text-lg">${message}</p>
                    <button class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-base md:text-lg"
                        onclick="window.location.reload()">
                        돌아가기
                    </button>
                </div>
            `;

            // 실제로는 여기에서 신청 내용을 서버에 전달하는 로직이 필요합니다.
            console.log(`${title} 신청이 ${recipient}에게 전달되었습니다. 상담 모드: ${modeText}`);
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
