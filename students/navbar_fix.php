					</style>
					<div class="swipe-indicator">
						<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" accesskey="s" class="nav-button '.$currentpage3.'">
							<div class="indicator-dot active"></div>
							<span>내 공부방</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" accesskey="o" class="nav-button '.$currentpage4.'">
							<div class="indicator-dot"></div>
							<span>공부결과</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$studentid.'" class="nav-button '.$currentpage2.'">
							<div class="indicator-dot"></div>
							<span>목표설정</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$studentid.'" class="nav-button">
							<div class="indicator-dot"></div>
							<span>수학일기</span>
						</a>
						<a href="timeline.php?id='.$studentid.'&tb=604800" accesskey="l" class="nav-button">
							<div class="indicator-dot"></div>
							<span>타임라인</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_memo.php?id='.$usernote.'&studentid='.$studentid.'" target="_blank" class="nav-button">
							<div class="indicator-dot"></div>
							<span>기억노트</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn=math&sbjt=m11&studentid='.$studentid.'&nch=1" target="_blank" class="nav-button">
							<div class="indicator-dot"></div>
							<span>안키퀴즈</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/books/domaindrilling.php?domain=120&studentid='.$studentid.'" target="_blank" class="nav-button">
							<div class="indicator-dot"></div>
							<span>수학특강</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/students/searchmynote.php?id='.$studentid.'" target="_blank" class="nav-button">
							<div class="indicator-dot"></div>
							<span>개념검색</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.'" class="nav-button '.$currentpage1.'">
							<div class="indicator-dot"></div>
							<span>분기목표</span>
						</a>
						<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=12" accesskey="." class="nav-button '.$currentpage5.'">
							<div class="indicator-dot"></div>
							<span>시간표</span>
						</a>
						<a href="'.$nexturl.'" class="next-button">
							<span>NEXT</span>
						</a>
					</div>

$flowlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog where userid='$studentid'   ORDER BY id DESC LIMIT 1"); 
$totalflow=$flowlog->flow1+$flowlog->flow2+$flowlog->flow3+$flowlog->flow4+$flowlog->flow5+$flowlog->flow6+$flowlog->flow7+$flowlog->flow8;
if($totalflow==0 || $totalflow==NULL)$totalflow=1;
if($usersex==='여')$cjnimg='<img loading="lazy" style="max-width:68%;" src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/woman/'.$totalflow.'.png">';
else $cjnimg='<img loading="lazy" style="max-width:68%;" src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/man/'.$totalflow.'.png">';
echo ' 