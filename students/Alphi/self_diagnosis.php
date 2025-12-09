<?php 
header('Content-Type: text/html; charset=utf-8');

// 오류 보고 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);
$studentid=$_GET["id"]; 
try {
    // Moodle 설정 파일 포함
    if (!file_exists("/home/moodle/public_html/moodle/config.php")) {
        throw new Exception("Moodle 설정 파일을 찾을 수 없습니다.");
    }
    
    include_once("/home/moodle/public_html/moodle/config.php"); 
    global $DB, $USER; 

    $username= $DB->get_record_sql("SELECT id,hideinput,lastname, firstname,timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1 ");
    $studentname=$username->firstname.$username->lastname;

    // 로그인 확인
    if (!isset($USER) || !isset($USER->id)) {
        throw new Exception("사용자 로그인이 필요합니다.");
    }

    // SQL 인젝션 방지를 위한 파라미터 바인딩
    $params = array('userid' => $USER->id, 'fieldid' => 22);
    $userrole = $DB->get_record_sql(
        "SELECT data AS role FROM mdl_user_info_data WHERE userid = :userid AND fieldid = :fieldid",
        $params
    );

    if (!$userrole) {
        throw new Exception("사용자 역할 정보를 찾을 수 없습니다.");
    }

    $role = $userrole->role;

} catch (Exception $e) {
    die("오류가 발생했습니다: " . $e->getMessage());
}

// 현재 날짜 설정
$today = date('Y년 n월 j일');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>성장형 마인드셋 자가진단</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- 아이콘용 폰트어썸 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fa;
        }
        .header-icon {
            font-size: 1.5rem;
            color: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="container mx-auto max-w-4xl py-8 px-4">
        <!-- 상단 헤더 -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <a href="growthmindset.php?id=<?php echo $studentid; ?>" class="text-indigo-600 hover:text-indigo-800">
                    <i class="fas fa-arrow-left header-icon"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-800">성장형 마인드셋 자가진단</h1>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-gray-600"><?php echo $today; ?></span>
                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <span class="font-semibold text-indigo-700"><?php echo $username->lastname; ?></span>
                </div>
            </div>
        </div>
        
        <!-- 자가진단 앱 컨테이너 -->
        <div id="mindset-assessment-app" class="my-8"></div>
        
        <!-- 하단 정보 -->
        <div class="mt-12 bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">성장형 마인드셋이란?</h2>
            <p class="text-gray-700 mb-4">
                성장형 마인드셋(Growth Mindset)은 심리학자 캐럴 드웩(Carol Dweck)이 주창한 개념으로, 자신의 능력과 지능이 학습과 노력을 통해 발전할 수 있다고 믿는 마인드셋입니다. 
                반면, 고정형 마인드셋(Fixed Mindset)은 자신의 능력과 지능이 타고난 것이며 변하지 않는다고 믿는 마인드셋입니다.
            </p>
            <p class="text-gray-700 mb-4">
                성장형 마인드셋을 가진 사람들은 실패를 성장의 기회로 받아들이고, 도전을 즐기며, 지속적인 학습과 개선을 추구합니다. 
                이는 학업적 성취뿐만 아니라 삶의 다양한 영역에서 성공과 행복에 긍정적인 영향을 줄 수 있습니다.
            </p>
            <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
                <a href="https://www.youtube.com/watch?v=n6Pbjyly908" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center">
                    <i class="fas fa-video mr-2"></i>
                    <span>성장형 마인드셋 영상 보기</span>
                </a>
                <a href="growthmindset.php?id=<?php echo $studentid; ?>" class="text-indigo-600 hover:text-indigo-800 flex items-center">
                    <span>성장형 마인드셋 프로그램으로 돌아가기</span>
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- 자가진단 스크립트 -->
    <script src="scripts/mindset_assessment.js"></script>
</body>
</html> 