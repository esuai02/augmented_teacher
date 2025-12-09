<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 사용자 정보 파라미터
$studentid = isset($_GET["userid"]) ? intval($_GET["userid"]) : 0;

// 사용자 역할 가져오기 (예: 필요 시 권한 확인용)
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ?", array($USER->id));
$role = isset($userrole->role) ? $userrole->role : '';

// 세션 시작
session_start();

// 초기화 처리
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $_SESSION['images'] = array();
    $_SESSION['mistakes'] = array(); // 2단계: 단순실수 문항
    $_SESSION['no_time_limit'] = array(); // 3단계: 시간제한 없이 푼 문제
    $_SESSION['not_solved'] = array(); // 4단계: 시간이 있어도 못 푼 문제
    $_SESSION['scores'] = array('actual_score'=>'', 'additional2'=>'', 'additional3'=>''); // 5단계: 점수입력
    $_SESSION['total_comment'] = '';
}

// 세션 기본값 설정
if(!isset($_SESSION['images'])) $_SESSION['images'] = array();
if(!isset($_SESSION['mistakes'])) $_SESSION['mistakes'] = array();
if(!isset($_SESSION['no_time_limit'])) $_SESSION['no_time_limit'] = array();
if(!isset($_SESSION['not_solved'])) $_SESSION['not_solved'] = array();
if(!isset($_SESSION['scores'])) $_SESSION['scores'] = array('actual_score'=>'', 'additional2'=>'', 'additional3'=>'');
if(!isset($_SESSION['total_comment'])) $_SESSION['total_comment'] = '';

// 단계 파라미터
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
if($step < 1) $step = 1;
if($step > 6) $step = 6;

// 이미지 업로드 처리 (1단계에서만)
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['exam_image'])) {
    // $_FILES['exam_image']가 배열 형태이므로 loop로 처리
    foreach ($_FILES['exam_image']['tmp_name'] as $idx => $tmp_name) {
        if ($_FILES['exam_image']['error'][$idx] === UPLOAD_ERR_OK) {
            $name = basename($_FILES['exam_image']['name'][$idx]);
            $upload_dir = __DIR__ . '/uploads/';
            
            // 업로드 디렉토리 없으면 생성
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $target = $upload_dir . uniqid('exam_', true) . '_' . $name;
            if (move_uploaded_file($tmp_name, $target)) {
                $_SESSION['images'][] = 'uploads/' . basename($target);
            }
        }
    }
}
// 페이지 이동 처리용 함수
function nav_button($current_step, $target_step, $label, $disabled=false) {
    $disabled_class = $disabled ? 'disabled' : '';
    $url = "?step=$target_step&userid=" . intval($_GET['userid']);
    return '<a href="'.$url.'" class="nav-btn '.$disabled_class.'">'.$label.'</a>';
}


// 2,3,4단계: 문항 선택 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2 && isset($_POST['mistakes'])) {
        $_SESSION['mistakes'] = $_POST['mistakes'];
        header("Location: ?step=3&userid=$studentid");
        exit;
    }
    if ($step === 3 && isset($_POST['no_time_limit'])) {
        $_SESSION['no_time_limit'] = $_POST['no_time_limit'];
        header("Location: ?step=4&userid=$studentid");
        exit;
    }
    if ($step === 4 && isset($_POST['not_solved'])) {
        $_SESSION['not_solved'] = $_POST['not_solved'];
        header("Location: ?step=5&userid=$studentid");
        exit;
    }
    if ($step === 5) {
        $_SESSION['scores']['actual_score'] = isset($_POST['actual_score']) ? $_POST['actual_score'] : '';
        $_SESSION['scores']['additional2'] = isset($_POST['additional2']) ? $_POST['additional2'] : '';
        $_SESSION['scores']['additional3'] = isset($_POST['additional3']) ? $_POST['additional3'] : '';
        header("Location: ?step=6&userid=$studentid");
        exit;
    }
    // 6단계에서 총평 및 DB 저장 처리
    if ($step === 6 && isset($_POST['total_comment'])) {
        $_SESSION['total_comment'] = $_POST['total_comment'];
    
        $time = time();
    
        // attempts 테이블에 시도 정보 저장
        $attempt = new stdClass();
        $attempt->userid = $studentid; 
        $attempt->status = 1; // 완료 상태
        $attempt->actual_score = intval($_SESSION['scores']['actual_score']);
        $attempt->additional2 = intval($_SESSION['scores']['additional2']);
        $attempt->additional3 = intval($_SESSION['scores']['additional3']);
        $attempt->total_comment = $_SESSION['total_comment'];
        $attempt->timecreated = $time;
        $attempt->timemodified = $time;
    
        $attemptid = $DB->insert_record('alt42_attempts', $attempt);
    
        // pages 테이블에 이미지 정보 저장
        $page_number = 1;
        foreach ($_SESSION['images'] as $img) {
            $page = new stdClass();
            $page->attemptid = $attemptid;
            $page->page_number = $page_number++;
            $page->image_url = $img;
            $page->timecreated = $time;
            $page->timemodified = $time;
            $DB->insert_record('alt42_pages', $page);
        }
    
        // questions 테이블에 문항 정보 저장
        foreach ($_SESSION['mistakes'] as $qnum) {
            $q = new stdClass();
            $q->attemptid = $attemptid;
            $q->question_number = intval($qnum);
            $q->question_type = 'mistake';
            $q->timecreated = $time;
            $DB->insert_record('alt42_questions', $q);
        }
    
        foreach ($_SESSION['no_time_limit'] as $qnum) {
            $q = new stdClass();
            $q->attemptid = $attemptid;
            $q->question_number = intval($qnum);
            $q->question_type = 'no_time_limit';
            $q->timecreated = $time;
            $DB->insert_record('alt42_questions', $q);
        }
    
        foreach ($_SESSION['not_solved'] as $qnum) {
            $q = new stdClass();
            $q->attemptid = $attemptid;
            $q->question_number = intval($qnum);
            $q->question_type = 'not_solved';
            $q->timecreated = $time;
            $DB->insert_record('alt42_questions', $q);
        }
        
        // DB 저장 후 안내 메시지 출력
        echo "<div style='background:#d4edda; padding:15px; border-radius:5px; color:#155724; margin:20px 0; font-size:1.1em;'>
        선생님에게 제출되었습니다.
        </div>";

        $mistakes = $_SESSION['mistakes'];
        $no_time_limit = $_SESSION['no_time_limit'];
        $not_solved = $_SESSION['not_solved'];
        $scores = $_SESSION['scores'];

        // 결과 리포트 표시 (textarea 제거, 폼 제거)
        echo "<div class='summary-box'>";
        echo "<h3>최종 결과 리포트</h3>";
        echo "<p><strong>단순 실수로 틀린 문항:</strong> ".(empty($mistakes) ? '없음' : implode(', ', $mistakes))."</p>";
        echo "<p><strong>시간 제한 없이 푼 문항:</strong> ".(empty($no_time_limit) ? '없음' : implode(', ', $no_time_limit))."</p>";
        echo "<p><strong>시간이 있어도 못 푼 문항:</strong> ".(empty($not_solved) ? '없음' : implode(', ', $not_solved))."</p>";
        echo "<p><strong>점수:</strong> 실제 점수: ".$scores['actual_score']."점 / 단순실수 추가: ".$scores['additional2']."점 / 시간추가: ".$scores['additional3']."점</p>";
        echo "<p><strong>총평:</strong> ".htmlspecialchars($_SESSION['total_comment'], ENT_QUOTES)."</p>";
        echo "</div>";

        // 확인, 이전으로 버튼 추가
        echo "<div style='margin-top:20px; display:flex; gap:10px;'>";
        echo "<a href='?step=6&userid=$studentid' class='nav-btn'>확인</a>";
        echo "<a href='?step=5&userid=$studentid' class='nav-btn'>이전으로</a>";
        echo "</div>";

        // 세션 초기화
        $_SESSION['images'] = array();
        $_SESSION['mistakes'] = array();
        $_SESSION['no_time_limit'] = array();
        $_SESSION['not_solved'] = array();
        $_SESSION['scores'] = array('actual_score'=>'', 'additional2'=>'', 'additional3'=>'');
        $_SESSION['total_comment'] = '';
        exit;
    }
}

// 현재 단계에 따른 컨텐츠 출력
function render_step_content($step) {
    $totalPages = count($_SESSION['images']);
    $studentid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

    switch($step) {
        case 1:
            // 시험지 업로드 화면
            echo '<h2>1. 시험지 업로드</h2>';
            echo '<p>각 페이지를 촬영하여 업로드해주세요.</p>';
            echo '<form method="post" enctype="multipart/form-data" class="form-upload">
            <label class="upload-button">
                페이지 촬영하기
                <input type="file" name="exam_image[]" accept="image/*" capture="environment" multiple style="display:none;" onchange="this.form.submit()"/>
            </label>
            </form>';

            // **여기서부터 썸네일 갤러리 형태로 이미지 표시**
            if ($totalPages > 0) {
                echo '<div class="thumbnail-gallery">';
                foreach ($_SESSION['images'] as $index => $img) {
                    echo '<div class="thumbnail">';
                    echo '<img src="'.htmlspecialchars($img, ENT_QUOTES, 'UTF-8').'" alt="시험지 페이지 '.($index+1).'" onclick="openModal(this.src)"/>';
                    echo '<div class="thumb-caption">'.($index+1).'페이지</div>';
                    echo '</div>';
                }
                echo '</div>';

                echo '<div class="page-info" style="text-align:center; margin-top:10px;">총 '.$totalPages.'페이지가 업로드되었습니다.</div>';
            } else {
                echo '<div class="no-images">아직 업로드된 페이지가 없습니다.<br>위 버튼을 클릭하여 시험지를 촬영해주세요.</div>';
            }

            // 이미지 확대용 모달
            echo '
            <div id="imageModal" class="modal" onclick="closeModal()">
              <span class="close" onclick="closeModal()">&times;</span>
              <img class="modal-content" id="modalImage">
            </div>
            ';
            break;

        case 2:
            // 단순 실수 문항 선택
            echo '<h2>2. 단순 실수로 틀린 문항 선택</h2>';
            echo '<p>아래에서 단순 실수로 틀린 문항을 선택해주세요. (중복 선택 가능)</p>';
            echo '<form method="post">';
            echo '<div class="question-grid">';
            for ($i=1; $i<=30; $i++) {
                $checked = in_array($i, $_SESSION['mistakes']) ? 'checked' : '';
                echo '<label><input type="checkbox" name="mistakes[]" value="'.$i.'" '.$checked.'> '.$i.'번</label>';
            }
            echo '</div>';
            echo '<button type="submit" class="next-btn">저장하기</button>';
            echo '</form>';
            break;

        case 3:
            echo '<h2>3. 시간제한 없이 푼 문제 선택</h2>';
            echo '<p>실제 시험 시간 안이 아니라, 추가 시간을 받았다면 그 시간에 푼 문항을 선택해주세요.</p>';
            echo '<form method="post">';
            echo '<div class="question-grid">';
            for ($i=1; $i<=30; $i++) {
                $checked = in_array($i, $_SESSION['no_time_limit']) ? 'checked' : '';
                echo '<label><input type="checkbox" name="no_time_limit[]" value="'.$i.'" '.$checked.'> '.$i.'번</label>';
            }
            echo '</div>';
            echo '<button type="submit" class="next-btn">저장하기</button>';
            echo '</form>';
            break;

        case 4:
            echo '<h2>4. 시간이 있어도 못 푼 문제 선택</h2>';
            echo '<p>시험 시간 중에도 해결하지 못한 문항을 선택해주세요.</p>';
            echo '<form method="post">';
            echo '<div class="question-grid">';
            for ($i=1; $i<=30; $i++) {
                $checked = in_array($i, $_SESSION['not_solved']) ? 'checked' : '';
                echo '<label><input type="checkbox" name="not_solved[]" value="'.$i.'" '.$checked.'> '.$i.'번</label>';
            }
            echo '</div>';
            echo '<button type="submit" class="next-btn">저장하기</button>';
            echo '</form>';
            break;

        case 5:
            echo '<h2>5. 점수 입력</h2>';
            echo '<p>시험 점수와 추가 점수를 입력해주세요.</p>';
            echo '<form method="post" class="score-form">';
            echo '<label>실제 점수: <input type="number" name="actual_score" value="'.htmlspecialchars($_SESSION['scores']['actual_score'], ENT_QUOTES).'" placeholder="예: 80"></label>';
            echo '<label>단순 실수(2번) 추가 점수: <input type="number" name="additional2" value="'.htmlspecialchars($_SESSION['scores']['additional2'], ENT_QUOTES).'" placeholder="예: 5"></label>';
            echo '<label>시간 제한 없이 푼 문제(3번) 추가 점수: <input type="number" name="additional3" value="'.htmlspecialchars($_SESSION['scores']['additional3'], ENT_QUOTES).'" placeholder="예: 10"></label>';
            echo '<button type="submit" class="next-btn">저장하기</button>';
            echo '</form>';
            break;

        case 6:
            $mistakes = $_SESSION['mistakes'];
            $no_time_limit = $_SESSION['no_time_limit'];
            $not_solved = $_SESSION['not_solved'];
            $scores = $_SESSION['scores'];
        
            echo '<h2>6. 요약 및 총평</h2>';
        
            echo '<div class="summary-box">';
            echo '<h3>요약</h3>';
            echo '<p><strong>단순 실수로 틀린 문항:</strong> '.(empty($mistakes) ? '없음' : implode(', ', $mistakes)).'</p>';
            echo '<p><strong>시간 제한 없이 푼 문항:</strong> '.(empty($no_time_limit) ? '없음' : implode(', ', $no_time_limit)).'</p>';
            echo '<p><strong>시간이 있어도 못 푼 문항:</strong> '.(empty($not_solved) ? '없음' : implode(', ', $not_solved)).'</p>';
            echo '<p><strong>점수:</strong> 실제 점수: '.$scores['actual_score'].'점 / 단순실수 추가: '.$scores['additional2'].'점 / 시간추가: '.$scores['additional3'].'점</p>';
        
                // 총평 입력 부분
            echo '<h3>총평 입력</h3>';
            echo '<form method="post">';
            echo '<textarea name="total_comment" style="width:100%; height:100px;" placeholder="총평을 입력하세요...">';
            // 여기서 이전에 저장된 $_SESSION['total_comment'] 값을 기본 텍스트로 출력
            echo htmlspecialchars($_SESSION['total_comment'], ENT_QUOTES);
            echo '</textarea><br>';

            echo '<button type="submit" class="next-btn">제출하기</button>';
            echo '</form>';
            echo '</div>';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>시험 결과 분석</title>
<style>
  body { font-family: sans-serif; margin:0; background: #f8f9fa; }
  header {
    background: #343a40; color:#fff; padding:15px; display:flex; justify-content:space-between; align-items:center;
  }
  header .title { font-size:1.2em; }
  header a.reset-btn {
    color:#fff; text-decoration:none; background:#dc3545; padding:8px 12px; border-radius:5px; 
  }
  header a.reset-btn:hover { background:#c82333; }
  
  .container { max-width:600px; margin:20px auto; background:#fff; padding:20px; border-radius:10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
  h2 { margin-top:0; }
  p { margin-bottom:15px; color:#333; }
  .form-upload, .score-form { display:flex; flex-direction:column; gap:10px; }
  
  .upload-button {
    display:inline-block; background:#007bff; color:#fff; padding:10px; border-radius:5px; text-align:center; cursor:pointer;
  }
  .upload-button:hover { background:#0056b3; }

  .no-images {
    text-align:center; color:#888; border:2px dashed #ccc; padding:20px; border-radius:10px; margin:15px 0;
  }

  a.nav-btn {
    text-decoration:none; background:#007bff; color:#fff; padding:8px 12px; border-radius:5px; 
  }
  a.nav-btn.disabled { opacity:0.5; pointer-events:none; }

  .question-grid {
    display:grid; grid-template-columns: repeat(5, 1fr); gap:10px;
    margin-bottom:15px;
  }
  .question-grid label {
    background:#f8f9fa; border:1px solid #ccc; padding:10px; border-radius:5px; text-align:center; cursor:pointer; font-size:0.9em;
  }
  .question-grid input[type="checkbox"] {
    margin-right:5px;
  }

  .next-btn {
    background:#28a745; color:#fff; padding:10px; border:none; border-radius:5px; cursor:pointer;
    font-size:1em;
    width:100%;
    display:block;
    text-align:center;
    margin:20px 0;
  }
  .next-btn:hover { background:#218838; }

  .score-form label {
    display:flex; flex-direction:column; gap:5px;
    margin-bottom:10px;
  }
  
  .summary-box {
    background:#e9ecef; padding:20px; border-radius:5px;
  }
  .summary-box h3 { margin-top:0; }
  textarea { border:1px solid #ccc; border-radius:5px; padding:10px; width:100%; }

  .step-navigation {
    display:flex; justify-content:space-between; margin-top:20px;
  }
  .step-navigation a {
    text-decoration:none; background:#17a2b8; color:#fff; padding:10px; border-radius:5px; 
  }
  .step-navigation a:hover { background:#138496; }

  .disabled-step { background:#6c757d !important; pointer-events:none; }

  /* 썸네일 갤러리 스타일 */
  .thumbnail-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
  }
  .thumbnail {
    width: calc(33.333% - 10px);
    box-sizing: border-box;
    position: relative;
    cursor: pointer;
  }
  .thumbnail img {
    width: 100%;
    height: auto;
    border-radius:5px;
    border:1px solid #ccc;
    transition: transform 0.2s;
  }
  .thumbnail img:hover {
    transform: scale(1.05);
  }
  .thumb-caption {
    text-align:center; 
    font-size:0.9em; 
    color:#555; 
    margin-top:5px;
  }

  /* 모달 스타일 */
  .modal {
    display: none; 
    position: fixed; 
    z-index: 9999; 
    padding-top: 50px; 
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto; 
    background-color: rgba(0,0,0,0.7);
  }

  .modal-content {
    margin: auto; 
    display: block; 
    max-width: 90%;
    max-height: 80vh; 
    border-radius:5px;
  }

  .close {
    position: absolute; 
    top: 20px; 
    right: 30px; 
    color: #fff; 
    font-size: 40px; 
    font-weight: bold; 
    cursor: pointer;
    font-family: Arial, sans-serif;
  }

  .close:hover,
  .close:focus {
    color: #bbb;
    text-decoration: none;
    cursor: pointer;
  }
  
</style>
</head>
<body>
<header>
  <div class="title">시험 결과 분석</div>
  <a href="?reset=1" class="reset-btn">초기화</a>
</header>
<div class="container">

<?php
render_step_content($step);

// 하단 단계 이동 버튼 (이전 단계 / 다음 단계)
echo '<div class="step-navigation">';
if ($step > 1) {
    echo nav_button($step, $step-1, '이전 단계');
} else {
    echo '<a class="nav-btn disabled-step">이전 단계</a>';
}

if ($step < 6) {
    echo nav_button($step, $step+1, '다음 단계');
} else {
    echo '<a class="nav-btn disabled-step">다음 단계</a>';
}
echo '</div>';
?>

</div>

<script>
function openModal(src) {
  var modal = document.getElementById("imageModal");
  var modalImg = document.getElementById("modalImage");
  modal.style.display = "block";
  modalImg.src = src;
}

function closeModal() {
  var modal = document.getElementById("imageModal");
  modal.style.display = "none";
}
</script>
</body>
</html>
