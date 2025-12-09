<?php
/////////////////////////////// code snippet ///////////////////////////////
// 에러 리포팅 억제 (데이터베이스 경고 메시지 숨김)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
@ini_set('display_errors', 0);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 이미지 가져오기 함수
function getNotebookImages($DB, $cntid, $studentid) {
    $images = array();
    if($cntid) {
        // cmid로 페이지 조회
        $cntpages = $DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cntid' ORDER BY pagenum ASC");
        if($cntpages) {
            foreach($cntpages as $page) {
                $contentsid = $page->id;
                $ctext = $page->pageicontent;
 
                if(!empty($ctext)) {
                    $htmlDom = new DOMDocument;
                    @$htmlDom->loadHTML($ctext);
                    $imageTags = $htmlDom->getElementsByTagName('img');

                    foreach($imageTags as $imageTag) {
                        $imgSrc = $imageTag->getAttribute('src');
                        if(empty($imgSrc)) continue;
                        
                        $imgSrc = str_replace(' ', '%20', $imgSrc);
                        
                        // 모든 이미지를 가져오되, MATRIX/MATH/imgur 이미지는 MathNote_exam으로 변경
                        if(strpos($imgSrc, 'MATRIX') !== false ||
                           strpos($imgSrc, 'MATH') !== false ||
                           strpos($imgSrc, 'imgur') !== false) {
                            // MathNote를 MathNote_exam으로 변경
                            $imgSrc = str_replace('MathNote', 'MathNote_exam', $imgSrc);
                        }
                        
                        // 유효한 이미지 URL인지 확인 (data: 제외)
                        if(strpos($imgSrc, 'data:') === 0) continue;
                        
                        $images[] = array(
                            'src' => $imgSrc,
                            'contentsid' => $contentsid,
                            'pagenum' => $page->pagenum
                        );
                    }
                }
            }
        }
    }
    return $images;
} 
 
$cid=$_GET["cid"]; 
$chnum=$_GET["nch"]; 

$studentid=$_GET["studentid"]; 
if($studentid==NULL)$studentid=$USER->id;
$timecreated=time(); 
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname = ($username && isset($username->firstname) && isset($username->lastname)) ? $username->firstname.$username->lastname : 'Unknown';
$lastchapter=$DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
if($cid==NULL && $lastchapter && isset($lastchapter->cid))$cid=$lastchapter->cid;

// 도메인 찾기
$domain = null;
for($ndm=120;$ndm<=136;$ndm++)
	{
	$dminfo= $DB->get_record_sql("SELECT * FROM mdl_abessi_domain WHERE domain='$ndm' ");
	for($ncid=1;$ncid<=20;$ncid++)
		{
		$cidstr='cid'.$ncid;
		if($cid==$dminfo->$cidstr)
			{
			$nchstr='nch'.$ncid;
			if($dminfo->$nchstr==$chnum)
				{
				$domain=$ndm;
				break 2;
				}
			}
		}
	}

$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
$subjectname=$curri->name;
$chapnum=$curri->nch;

// 대표유형이 있는 과목만 필터링하는 함수
function hasRepresentativeType($DB, $curriculumId) {
    $curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$curriculumId'");
    if (!$curri) return false;
    
    $chapnum = $curri->nch;
    
    // 각 단원을 확인하여 대표유형 노트가 있는지 체크
    for ($nch = 1; $nch <= $chapnum; $nch++) {
        $cntstr = 'cnt' . $nch;
        $checklistid = $curri->$cntstr;
        if (!$checklistid) continue;
        
        $chklist = $DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$checklistid' ORDER BY id DESC LIMIT 1");
        if (!$chklist || !isset($chklist->instance)) continue;
        
        $topics = $DB->get_records_sql("SELECT * FROM mdl_checklist_item where checklist='$chklist->instance' ORDER BY position ASC");
        if (!$topics) continue;
        
        foreach ($topics as $topic) {
            $displaytext = isset($topic->displaytext) ? $topic->displaytext : '';
            $linkurl = isset($topic->linkurl) ? $topic->linkurl : '';
            
            if (empty($linkurl)) continue;
            
            $displaytext_clean = str_replace(' ', '', $displaytext);
            
            // 대표유형 노트_중급 또는 대표유형 노트_심화가 있는지 확인
            if (strpos($displaytext, '대표유형 노트_중급') !== false || 
                strpos($displaytext, '노트_중급') !== false ||
                strpos($displaytext_clean, '대표유형노트_중급') !== false ||
                strpos($displaytext, '대표유형 노트_심화') !== false || 
                strpos($displaytext, '노트_심화') !== false ||
                strpos($displaytext_clean, '대표유형노트_심화') !== false) {
                return true; // 대표유형 노트가 있으면 true 반환
            }
        }
    }
    
    return false; // 대표유형 노트가 없으면 false 반환
}

// 모든 과목 목록 가져오기 (과목 드롭다운용)
$allCurriculums = $DB->get_records_sql("SELECT id, name FROM mdl_abessi_curriculum ORDER BY name ASC");
$subjectDropdown = '<select id="subjectDropdown" class="form-control" style="display: inline-block; width: auto; margin-left: 10px;" onchange="changeSubject(this.value)">';

// 대표유형이 있는 과목만 필터링
foreach($allCurriculums as $curriculum) {
    // 대표유형 노트가 있는지 확인
    if (hasRepresentativeType($DB, $curriculum->id)) {
        $selected = ($curriculum->id == $cid) ? 'selected' : '';
        $subjectDropdown .= '<option value="'.$curriculum->id.'" '.$selected.'>'.$curriculum->name.'</option>';
    }
}

$subjectDropdown .= '</select>';

// 드롭다운 메뉴 생성
$chapterDropdown = '<select id="chapterDropdown" class="form-control" style="display: inline-block; width: auto; margin-left: 10px;" onchange="changeChapter(this.value)">';
for($nch=1;$nch<=$chapnum;$nch++)
{
	$chname='ch'.$nch;
	$title=$curri->$chname;
	$qid='qid'.$nch;
	$qid=$curri->$qid;
	if($title==NULL)continue;
	$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$qid'  ");
	if($moduleid && isset($moduleid->instance)) {
		$attemptlog=$DB->get_record_sql("SELECT id,quiz,sumgrades,attempt,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
		if($attemptlog && isset($attemptlog->timefinish)) {
			$quiz=$DB->get_record_sql("SELECT id,sumgrades FROM mdl_quiz where id='$moduleid->instance'  ");
			if($quiz && isset($quiz->sumgrades) && $quiz->sumgrades > 0) {
				$quizgrade=round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
				$quizresult='';
				if($quizgrade!=NULL)$quizresult=' ('.$quizgrade.'점)';
			} else {
				$quizresult='';
			}
		} else {
			$quizresult='';
		}
	} else {
		$quizresult='';
	}
	
	 if($nch==$chnum)
		{ 
		$thischtitle=$curri->$chname;
		$cntstr='cnt'.$nch;
		$checklistid=$curri->$cntstr;
		$chapterDropdown .= '<option value="'.$nch.'" selected>'.$nch.'. '.$title.$quizresult.'</option>';
		}
	else {
		$chapterDropdown .= '<option value="'.$nch.'">'.$nch.'. '.$title.$quizresult.'</option>';
	}
	}
$chapterDropdown .= '</select>';	


$chklist=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$checklistid' ORDER BY id DESC LIMIT 1");
$topics=$DB->get_records_sql("SELECT * FROM mdl_checklist_item where checklist='$chklist->instance' ORDER BY position ASC   ");  //AND  title NOT LIKE '%Approach%' 
$result = json_decode(json_encode($topics), True);

// 중급 및 심화 노트 cntid 찾기
$middleCntid = null;
$advancedCntid = null;

foreach($result as $value) {
    $displaytext = isset($value['displaytext']) ? $value['displaytext'] : '';
    $linkurl = isset($value['linkurl']) ? $value['linkurl'] : '';
    
    if(empty($linkurl)) continue;
    
    $url_components = parse_url($linkurl);
    if(!isset($url_components['query'])) continue;
    
    parse_str($url_components['query'], $params);

    if(strpos($linkurl, 'icontent') !== false && isset($params['id'])) {
        $cntid = $params['id'];
        // displaytext 확인을 위해 다양한 패턴 체크 (공백 제거 후 비교)
        $displaytext_clean = str_replace(' ', '', $displaytext);
        if(strpos($displaytext, '대표유형 노트_중급') !== false || 
           strpos($displaytext, '노트_중급') !== false ||
           strpos($displaytext_clean, '대표유형노트_중급') !== false ||
           strpos($displaytext_clean, '노트_중급') !== false) {
            $middleCntid = $cntid;
        } elseif(strpos($displaytext, '대표유형 노트_심화') !== false || 
                 strpos($displaytext, '노트_심화') !== false ||
                 strpos($displaytext_clean, '대표유형노트_심화') !== false ||
                 strpos($displaytext_clean, '노트_심화') !== false) {
            $advancedCntid = $cntid;
        }
    }
}

// 중급 및 심화 이미지 가져오기
$middleImages = getNotebookImages($DB, $middleCntid, $studentid);
$advancedImages = getNotebookImages($DB, $advancedCntid, $studentid);

// 중급 이미지 HTML 생성
$middleImagesHTML = '';
if(!empty($middleImages)) {
    foreach($middleImages as $img) {
        $wboardid = 'jnrsorksqcrark'.$img['contentsid'].'_user'.$studentid;
        $imgSrcSafe = htmlspecialchars($img['src'], ENT_QUOTES, 'UTF-8');
        $iframeUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/patternbank.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$img['contentsid'].'&contentstype=1&mode=questiononly';
        $problemNum = $img['pagenum'];
        $middleImagesHTML .= '<div id="img-middle-'.$img['contentsid'].'-'.$img['pagenum'].'" class="notebook-image-item" style="cursor: pointer; border: 2px solid #007bff; padding: 5px; width: calc(50% - 4px); box-sizing: border-box; margin-bottom: 8px; position: relative; border-radius: 3px; background-color: #fff; overflow: hidden;"
                data-iframe-url="'.$iframeUrl.'"
                onclick="loadIframe(\''.$iframeUrl.'\', this)">
                <div style="font-weight: bold; margin-bottom: 3px; font-size: 14px; color: #333;">문제'.$problemNum.'</div>
                <img src="'.$imgSrcSafe.'" style="max-width: 100%; width: 100%; height: auto; display: block; background-color: #fff;"
                     data-iframe-url="'.$iframeUrl.'"
                     ondblclick="openImageInNewTab(\''.$iframeUrl.'\')"
                     onerror="var container = this.parentElement; this.style.display=\'none\'; var errorMsg = container.querySelector(\'.img-error\'); if(!errorMsg) { var p = document.createElement(\'p\'); p.className = \'img-error\'; p.style.color = \'red\'; p.style.fontSize = \'12px\'; p.textContent = \'이미지 로드 실패\'; container.appendChild(p); }"
                     onload="this.style.opacity=\'1\';">
            </div>';
    }
} else {
    // 디버깅 정보 추가
    if($middleCntid) {
        $middleImagesHTML = '<div style="padding: 20px; text-align: center; color: #666;">
            <p>중급 노트 이미지가 없습니다.</p>
            <p style="font-size: 12px; color: #999;">cntid: '.$middleCntid.'</p>
            <p style="font-size: 12px; color: #999;">파일: patternbank.php (line 256-257)</p>
        </div>';
    } else {
        $middleImagesHTML = '<div style="padding: 20px; text-align: center; color: #666;">
            <p>중급 노트를 찾을 수 없습니다.</p>
            <p style="font-size: 12px; color: #999;">checklist에서 "대표유형 노트_중급" 또는 "노트_중급" 항목을 확인해주세요.</p>
            <p style="font-size: 12px; color: #999;">파일: patternbank.php (line 234-253)</p>
        </div>';
    }
}

// 심화 이미지 HTML 생성
$advancedImagesHTML = '';
if(!empty($advancedImages)) {
    foreach($advancedImages as $img) {
        $wboardid = 'jnrsorksqcrark'.$img['contentsid'].'_user'.$studentid;
        $imgSrcSafe = htmlspecialchars($img['src'], ENT_QUOTES, 'UTF-8');
        $iframeUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/patternbank.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$img['contentsid'].'&contentstype=1&mode=questiononly';
        $problemNum = $img['pagenum'];
        $advancedImagesHTML .= '<div id="img-advanced-'.$img['contentsid'].'-'.$img['pagenum'].'" class="notebook-image-item" style="cursor: pointer; border: 2px solid #007bff; padding: 5px; width: calc(50% - 4px); box-sizing: border-box; margin-bottom: 8px; position: relative; border-radius: 3px; background-color: #fff; overflow: hidden;"
                data-iframe-url="'.$iframeUrl.'"
                onclick="loadIframe(\''.$iframeUrl.'\', this)">
                <div style="font-weight: bold; margin-bottom: 3px; font-size: 14px; color: #333;">문제'.$problemNum.'</div>
                <img src="'.$imgSrcSafe.'" style="max-width: 100%; width: 100%; height: auto; display: block; background-color: #fff;"
                     data-iframe-url="'.$iframeUrl.'"
                     ondblclick="openImageInNewTab(\''.$iframeUrl.'\')"
                     onerror="var container = this.parentElement; this.style.display=\'none\'; var errorMsg = container.querySelector(\'.img-error\'); if(!errorMsg) { var p = document.createElement(\'p\'); p.className = \'img-error\'; p.style.color = \'red\'; p.style.fontSize = \'12px\'; p.textContent = \'이미지 로드 실패\'; container.appendChild(p); }"
                     onload="this.style.opacity=\'1\';">
            </div>';
    }
} else {
    // 디버깅 정보 추가
    if($advancedCntid) {
        $advancedImagesHTML = '<div style="padding: 20px; text-align: center; color: #666;">
            <p>심화 노트 이미지가 없습니다.</p>
            <p style="font-size: 12px; color: #999;">cntid: '.$advancedCntid.'</p>
            <p style="font-size: 12px; color: #999;">파일: patternbank.php (line 256-257)</p>
        </div>';
    } else {
        $advancedImagesHTML = '<div style="padding: 20px; text-align: center; color: #666;">
            <p>심화 노트를 찾을 수 없습니다.</p>
            <p style="font-size: 12px; color: #999;">checklist에서 "대표유형 노트_심화" 또는 "노트_심화" 항목을 확인해주세요.</p>
            <p style="font-size: 12px; color: #999;">파일: patternbank.php (line 234-253)</p>
        </div>';
    }
}


echo '<!DOCTYPE html>
<html>
<head>
  <title>Bootstrap Example</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
  <style>
      * {
      -webkit-user-drag: none; /* Chrome, Safari */
      -moz-user-drag: none;    /* Firefox */
      -ms-user-drag: none;     /* Edge */
      user-drag: none;         /* Standard */
    }

  img {
	user-drag: none; /* for WebKit browsers including Chrome */
	user-select: none; /* for standard-compliant browsers */
	-webkit-user-drag: none; /* for Safari and Chrome */
	-webkit-user-select: none; /* for Safari */
	-moz-user-select: none; /* for Firefox */
	-ms-user-select: none; /* for Internet Explorer/Edge */
  }
  a {
	user-drag: none; /* for WebKit browsers including Chrome */
	user-select: none; /* for standard-compliant browsers */
	-webkit-user-drag: none; /* for Safari and Chrome */
	-webkit-user-select: none; /* for Safari */
	-moz-user-select: none; /* for Firefox */
	-ms-user-select: none; /* for Internet Explorer/Edge */
  }

html, body {
    width: 100vw !important;
    max-width: 100vw !important;
    overflow-x: hidden !important;
}

body {
    font-family: "Noto Sans KR", -apple-system, BlinkMacSystemFont, sans-serif;
    background-color: #f5f6fa;
    color: #2c3e50;
    line-height: 1.6;
}

.container {
    width: 95vw !important;
    max-width: 95vw !important;
    margin: 0 auto !important;
    padding: 10px !important;
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
}

.top-bar {
  width: 100%;
  padding: 10px 20px;
  background-color: #fff;
  border-bottom: 1px solid #dee2e6;
}

.content-wrapper {
  display: flex;
  width: 100%;
  height: calc(100vh - 100px);
}

.left-column{
  width: 40%;
  padding: 10px;
  overflow-y: auto;
  background-color: #fff;
}
.right-column {
  width: 60%;
  padding: 0px;
  border-left: 1px solid #dee2e6;
}
    
    /* 탭 내용이 사라지지 않도록 보장 */
    .tab-pane {
      min-height: 200px;
      background-color: #fff;
    }
    .tab-pane.fade:not(.show) {
      display: none !important;
    }
    .tab-pane.fade.show {
      display: block !important;
      opacity: 1 !important;
    }
    .tab-content {
      background-color: #fff !important;
    }
    
    /* 이미지 컨테이너 스타일 */
    .notebook-image-container {
      position: relative;
      min-height: 100px;
    }
    .notebook-image-container img {
      transition: opacity 0.3s ease;
      opacity: 0;
    }
    .notebook-image-container img.loaded {
      opacity: 1;
    }
    
    /* 이미지 아이템 스타일 */
    .notebook-image-item {
      transition: all 0.3s ease;
    }
    .notebook-image-item:hover {
      transform: scale(1.02);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .notebook-image-item.active {
      border-color: #28a745 !important;
      box-shadow: 0 0 10px rgba(40,167,69,0.5);
    }
    
    /* iframe 컨테이너 */
    #contentIframe {
      width: 100%;
      height: 100%;
      border: none;
    }
  </style>
 <script>
  // 드래그 이벤트 방지
  document.addEventListener("dragstart", function(e) {
    e.preventDefault();
  });
  // 텍스트 선택 이벤트 방지
  document.addEventListener("selectstart", function(e) {
    e.preventDefault();
  });
  
  // iframe 로드 함수
  function loadIframe(url, element) {
    var iframe = document.getElementById(\'contentIframe\');
    if(iframe) {
      iframe.src = url;
      // 활성 이미지 표시
      document.querySelectorAll(\'.notebook-image-item\').forEach(function(item) {
        item.classList.remove(\'active\');
      });
      if(element) {
        element.classList.add(\'active\');
      }
    }
  }
  
  // 이미지를 새 탭에서 iframe으로 열기
  function openImageInNewTab(iframeUrl) {
    if(iframeUrl) {
      // 직접 URL을 새 탭에서 열기 (주소창에 URL 표시)
      window.open(iframeUrl, \'_blank\');
    }
  }
  
  // 과목 변경 함수
  function changeSubject(newCid) {
    var studentid = \''.$studentid.'\';
    var params = [];
    params.push(\'cid=\' + newCid);
    params.push(\'nch=1\'); // 과목 변경 시 첫 번째 단원으로 이동
    params.push(\'studentid=\' + studentid);
    var url = \'https://mathking.kr/moodle/local/augmented_teacher/books/patternbank.php?\' + params.join(\'&\');
    window.location.href = url;
  }
  
  // 단원 변경 함수
  function changeChapter(nch) {
    var cid = \''.$cid.'\';
    var studentid = \''.$studentid.'\';
    var params = [];
    params.push(\'cid=\' + cid);
    params.push(\'nch=\' + nch);
    params.push(\'studentid=\' + studentid);
    // type=init을 제거하여 자동 복귀 방지
    var url = \'https://mathking.kr/moodle/local/augmented_teacher/books/patternbank.php?\' + params.join(\'&\');
    window.location.href = url;
  }
  
  // 탭 전환 시 이미지가 사라지지 않도록 보장
  document.addEventListener("DOMContentLoaded", function() {
    // URL 파라미터에서 cid와 nch 가져오기
    var urlParams = new URLSearchParams(window.location.search);
    var cid = urlParams.get(\'cid\');
    var nch = urlParams.get(\'nch\');
    
    // 과목 드롭다운 자동 선택
    if(cid) {
      var subjectDropdown = document.getElementById(\'subjectDropdown\');
      if(subjectDropdown) {
        subjectDropdown.value = cid;
      }
    }
    
    // 단원 드롭다운 자동 선택
    if(nch) {
      var chapterDropdown = document.getElementById(\'chapterDropdown\');
      if(chapterDropdown) {
        chapterDropdown.value = nch;
      }
    }
    
    // 모든 이미지에 로드 이벤트 리스너 추가
    var images = document.querySelectorAll(".tab-pane img");
    images.forEach(function(img) {
      if(img.complete) {
        img.classList.add("loaded");
        img.style.opacity = "1";
      } else {
        img.addEventListener("load", function() {
          this.classList.add("loaded");
          this.style.opacity = "1";
        });
        img.addEventListener("error", function() {
          // 에러 발생 시에도 컨테이너는 유지
          console.log("이미지 로드 실패:", this.src);
        });
      }
    });
    
    // Bootstrap 탭 이벤트 리스너
    $(\'#myTab button[data-toggle="tab"]\').on(\'shown.bs.tab\', function (e) {
      // 탭 전환 후 이미지 다시 확인
      var targetId = $(this).data(\'target\');
      var targetPane = $(targetId);
      var imgs = targetPane.find(\'img\');
      imgs.each(function() {
        if(this.complete && !this.classList.contains(\'loaded\')) {
          this.classList.add(\'loaded\');
          this.style.opacity = \'1\';
        }
      });
    });
  });
</script>

</head>
<body>
 
	<div class="container">
		<!-- 상단 바 -->
		<div class="top-bar">
			<div style="display: flex; align-items: center;">
				'.$subjectDropdown.'
				'.$chapterDropdown.'
			</div>
		</div>
		
		<!-- 콘텐츠 래퍼 -->
		<div class="content-wrapper">
			<!-- 좌측 칼럼: 이미지 목록 -->
			<div class="left-column">
				<!-- 탭 메뉴 -->
				<ul class="nav nav-tabs" id="myTab" role="tablist" style="margin-bottom: 20px;">
				  <li class="nav-item" role="presentation">
					<button class="nav-link active" id="middle-tab" data-toggle="tab" data-target="#middle" type="button" role="tab" aria-controls="middle" aria-selected="true">
					  <strong>대표유형 노트_중급</strong>
					</button>
				  </li>
				  <li class="nav-item" role="presentation">
					<button class="nav-link" id="advanced-tab" data-toggle="tab" data-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false">
					  <strong>대표유형 노트_심화</strong>
					</button>
				  </li>
				</ul>

				<!-- 탭 내용 -->
				<div class="tab-content" id="myTabContent" style="background-color: #fff; padding: 10px;">
				  <!-- 중급 탭 -->
				  <div class="tab-pane fade show active" id="middle" role="tabpanel" aria-labelledby="middle-tab">
					<div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 8px; align-items: flex-start;">
					  '.$middleImagesHTML.'
					</div>
				  </div>

				  <!-- 심화 탭 -->
				  <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
					<div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 8px; align-items: flex-start;">
					  '.$advancedImagesHTML.'
					</div>
				  </div>
				</div>
			</div>
			
			<!-- 우측 칼럼: iframe -->
			<div class="right-column">
				<iframe id="contentIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
			</div>
		</div>
	</div>

</body>
</html>
';
 

