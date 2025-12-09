<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar_note.php");
$cmid=required_param('cntid', PARAM_INT); 
$instance= $DB->get_record_sql("SELECT * FROM mdl_course_modules WHERE id='$cmid' "); 
$checklistid=$instance->instance;
$checklist= $DB->get_record_sql("SELECT * FROM mdl_checklist WHERE id='$checklistid' ");  
$listname= $checklist->name;
$contents= $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist='$checklistid' "); //단원 정보 가져오기

$result = json_decode(json_encode($contents), True);
 
echo '<section id="how-to"><div class="panel-group" id="superaccordion">'.$listname;

unset($value);
foreach($result as $value) // 소주제
	{  
	$chaptertitle=$value['displaytext']; //주제명
 	$cmid2=str_replace('https://mathking.kr/moodle/mod/icontent/view.php?id=', '', $value['linkurl']); 
 
echo ' 
        <!-- Accordion -->
          <div class="panel">
          <div class="panel-heading parent">
              <a class="accordion-toggle" data-toggle="collapse" data-parent="#superaccordion" href="#collapse'.$cmid2.'" aria-expanded="false">
              <h4><span style="color:white">'.$chaptertitle.'</span></h4>
              </a>
          </div>
 <div id="collapse'.$cmid2.'" class="panel-collapse collapse">
    '; 
	$contents3= $DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cmid2' ORDER BY pagenum");   // (icontent 부분에서 정보가지고 오기)
	$result3 = json_decode(json_encode($contents3), True);
	$nstep=0;
	$items='';
	unset($value3);
	foreach($result3 as $value3) // 소주제 반복생성
 		{
			$cnttitle=$value3['title']; // 주제내 목차명
			$pageid=$value3['id'];
			$cmid=$value3['cmid'];
			$wboardid='pageid'.$pageid.'jnrsorksqcrark'.$studentid;
			$message= $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' "); 

			if($message->wboardid==NULL) $items.='<li class="list-group-item"><table><tr><td><h5>'.$cnttitle.'</h5></td><td>&nbsp;</td><td></td><td><input type="checkbox"  onclick="changecheckbox2(9,\''.$studentid.'\',\''.$wboardid.'\',\''.$pageid.'\', this.checked)"/></td></tr></table></li>';
			else $items.='<li class="list-group-item"><table><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'" target="_blank"><h5>'.$cnttitle.'</h5></a></td><td>&nbsp;</td><td></td></tr></table></li>';
	 	}
		
	echo ' 	  <div class="panel-body">
		   <div class="panel-group" id="accordion'.$cmid2.'">
		    <div class="panel"> 
 	                    <div class="panel-body">
	                      <div class="inside-body">
	                        <ol class="list-group vertical-steps">
			'.$items.'
	                        </ol>
	                      </div>
	                    </div>
	                </div>
	              </div>
	            </div>

	      ';
 
echo '</div></div> ';  // 이부분 지우면 하나씩 나타남
 
	}  

echo '</div></section>';
if($cmid==83048) echo '  
 <br><br>
<table style="width: 100%;">
    <caption></caption>
    <thead>
        <tr>
            <th width=30% scope="col" style="text-align: left;">중등수학 1 - 1</th>
            <th width=5% scope="col" style="text-align: left;"></th>
            <th width=30% scope="col" style="text-align: left;">중등수학 2 - 1</th>
            <th width=5% scope="col" style="text-align: left;"></th>
            <th width=30% scope="col" style="text-align: left;">중등수학 3 - 1</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82922">1. 소인수분해</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82920">2. 최대공약수와 최소공배수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82919">3. 정수와 유리수</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82918">4. 유리식의 계산</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82917">5. 문자와 식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82910">6. 일차방정식의 풀이</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82921">7. 일차방정식의 활용</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82914">8. 함수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82913">9. 함수의 그래프와 활용</a></td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82936">1. 유리수와 순환소수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82935">2. 단항식의 계산</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82934">3. 다항식의 계산(1)</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82933">4. 일차부등식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82932">5. 연립일차부등식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82931">6. 부등식의 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82930">7. 연립일차방정식의 풀이</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82929">8. 연립일차방정식의 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82928">9. 일차함수와 그 그래프(1)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82927">10. 일차함수와 그 그래프(2)</a><br>
                        <a
                            target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82926">11. 일차함수와 일차방정식의 관계</a><br> <br>
            </td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82962">1. 제곱근의 뜻과 성질</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82961">2. 무리수와 실수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82960">3. 근호를 포함한 식의 계산 (1)</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82959">4. 근호를 포함한 식의 계산 (2)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82958">5. 다항식의 곱셈</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82957">6. 인수분해</a><br><a target="_blank"
                        href="https://mathking.kr/moodle/mod/url/view.php?id=82956">7. 이차방정식의 풀이</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82955">8. 이차방정식의 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82954">9. 이차함수의 그래프(1)</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82953">10. 이차함수의 그래프(2)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82952">11. 이차함수의 활용</a></td>
        </tr>
        <tr>
            <td style="text-align: left;"><b>중등수학 1 - 2</b></td>
            <td></td>
            <td style="text-align: left;"><b>중등수학 2 - 2</b><br></td>
            <td></td>
            <td style="text-align: left;"><b>중등수학 3 - 2</b></td>
        </tr>
        <tr>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82916">1. 자료의 정리</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82915">2. 자료의 분석</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82945">3. 기본도형</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82944">4. 위치 관계</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82943">5. 평행선</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82942">6. 작도와 합동</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82941">7. 다각형</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82940">8. 원과 부채꼴</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82939">9. 다면체</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82938">10. 회전체</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82937">11. 입체도형의 부피와 겉넓이</a></td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82972">1. 삼각형의 성질(1)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82971">2. 삼각형의 성질(2)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82970">3. 평행사변형</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82969">4. 여러 가지 사각형</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82968">5. 도형의 닮음</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82967">6. 평행선 사이의 선분의 길이의 비</a><br><a target="_blank"
                        href="https://mathking.kr/moodle/mod/url/view.php?id=82966">7. 닮음의 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82965">8. 피타고라스 정리</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82964">9. 경우의 수</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82963">10. 확률</a>
            </td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82951">1. 대푯값과 산포도</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82950">2. 피타고라스 정리</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82949">3. 피타고라스 정리와 도형</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82948">4. 피타고라스 정리의 평면도형에의 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82947">5. 피타고라스 정리의 입체도형에의 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82946">6. 삼각비</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82997">7. 삼각비의 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82996">8. 원과 직선</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82995">9. 원주각</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82994">10. 원주각의 활용</a></td>
        </tr>
    </tbody>
</table>
<hr>
<p><br>
</p>
<table style="width: 100%;">
    <caption></caption>
    <thead>
        <tr>
            <th width=30% scope="col" style="text-align: left;">고등수학 상</th>
            <th width=5% scope="col" style="text-align: left;"></th>
            <th width=30% scope="col" style="text-align: left;">고등수학 하</th>
            <th width=5% scope="col" style="text-align: left;"></th>
            <th width=30% scope="col" style="text-align: left;">수학 1</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82993">1. 다항식의 연산</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82991">2. 나머지정리와 인수분해</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82990">3. 복소수</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82989">4. 이차방정식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82988">5. 이차방정식과 이차함수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82987">6. 고차방정식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82986">7. 연립방정식</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82985">8. 부등식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82984">9. 이차부등식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82983">10. 평면좌표</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82982">11. 직선의 방정식</a><br>
                        <a
                            target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82981">12. 원의 방정식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82980">13. 도형의 이동</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82992">14. 부등식의 영역</a><br> <br></td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82979">1. 집합의 뜻과 표현</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82978">2. 집합의 연산</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82977">3. 명제</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82976">4. 절대부등식</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82975">5. 함수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82974">6. 유리식과 유리함수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82973">7. 무리식과 무리함수</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83013">8. 순열</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83012">9. 조합</a></td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83011">1. 지수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83010">2. 로그</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83009">3. 지수함수</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83008">4. 로그함수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83007">5. 삼각함수</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83006">6. 삼각함수의 그래프</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83005">7. 삼각함수의 활용</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83004">8. 등차수열</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83003">9. 등비수열</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83002">10. 수열의 합</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83001">11. 수학적 귀납법</a></td>
        </tr>
        <tr>
            <td><b>수학2</b></td>
            <td></td>
            <td><b>미분과 적분</b></td>
            <td></td>
            <td><b>확률과 통계</b></td>
        </tr>
        <tr>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83000">1. 함수의 극한</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82999">2. 함수의 연속</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=82998">3. 미분계수와 도함수</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83035">4. 도함수의 활용 (1)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83034">5. 도함수의 활용 (2)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83033">6. 도함수의 활용 (3)</a><br><a target="_blank"
                        href="https://mathking.kr/moodle/mod/url/view.php?id=83032">7. 부정적분</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83031">8. 정적분</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83030">9. 정적분의 활용</a></td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83029"></a><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83020">1. 수열의 극한</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83019">2. 급수</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83018">3. 지수함수와 로그함수의 미분</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83017">4. 삼각함수의 미분</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83016">5. 여러 가지 미분법</a><br><a target="_blank"
                        href="https://mathking.kr/moodle/mod/url/view.php?id=83015">6. 도함수의 활용 (1)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83014">7. 도함수의 활용 (2)</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83046">8. 여러 가지 적분법</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83045">9. 정적분</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83044">10. 정적분의 활용</a><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83021"></a><br> <br></td>
            <td></td>
            <td valign="top"><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83029">1. 순열</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83028">2. 여러 가지 순열</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83027">3. 조합</a><br>
                <a
                    target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83026">4. 이항정리와 분할</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83025">5. 확률의 뜻과 활용</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83024">6. 조건부 확률</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83023">7. 확률분포</a><br>
                    <a
                        target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83022">8. 정규분포</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83021">9. 통계적 추정</a></td>
        </tr>
        <tr>
            <td><b>&nbsp;기하</b></td>
            <td><b>&nbsp;</b></td>
            <td><b>&nbsp;</b></td>
            <td><b>&nbsp;</b></td>
            <td><b>&nbsp;</b></td>
        </tr>
        <tr>
            <td valign="top"><b>&nbsp;<a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83043">1. 이차곡선</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83042">2. 평면 곡선의 접선</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83041">3. 벡터의 연산</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83040">4. 평면벡터와 평면 운동</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83039">5. 공간도형</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83038">6. 공간좌표</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83037">7. 공간벡터</a><br><a target="_blank" href="https://mathking.kr/moodle/mod/url/view.php?id=83036">8. 도형의 방정식</a></b></td>
            <td><b>&nbsp;</b></td>
            <td><b>&nbsp;</b></td>
            <td><b>&nbsp;</b></td>
            <td><b>&nbsp;</b></td>
        </tr>
    </tbody>
</table>
<b><br><br></b>
<p></p>
<p></p>
<p><br></p><br><br><br><br>&nbsp;<br><br><br><span style="font-size: 14.44px;">&nbsp;</span><br><br>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p>
<p></p><br>
<p></p>';
echo ' </div> </div></div>
<style>
 body{
  padding:20px;
  background-color: #fff;
}

/*Vertical Steps*/
.inside-body{
  padding:25px;
}
.list-group.vertical-steps .list-group-item{
  border:none;
  border-left:3px solid #5cadff;
  box-sizing:border-box;
  border-radius:0;
  counter-increment: step-counter;
  padding-left:20px;
  padding-right:0px;
  padding-bottom:20px;
  padding-top:0px;
}
.list-group.vertical-steps .list-group-item.active{
  background-color:transparent;
  color:#18191a;
}
.list-group.vertical-steps .list-group-item:last-child{
  border-left:3px solid transparent;
  padding-bottom:0;
}
.list-group.vertical-steps .list-group-item::before {
  border-radius: 50%;
  background-color:#5cadff;
  color:#fff;
  content: counter(step-counter);
  display:inline-block;
  float:left;
  height:25px;
  line-height:25px;
  margin-left:-35px;
  text-align:center;
  width:25px;
}
.list-group.vertical-steps .list-group-item span,
.list-group.vertical-steps .list-group-item a{
  display:block;
  overflow:hidden;
  padding-top:2px;
}
/* End of Vertical Step */

#how-to .panel-group .panel{
  border-radius:0px;
  border: 0px;
}
#how-to .panel-group{
  margin:0px;
}
#how-to .panel-heading{
  padding:0px !important;
  border-radius: 0px;
}
#how-to .parent a{
  display: block;
  text-decoration: none;
  padding:25px;
}
#how-to .child a{
  display: block;
  text-decoration: none;
  padding:25px;
}
#how-to .parent{
  background-color: #29accc !important;  /* bar 의 채우기 색 */
}
#how-to .child{
  background-color: #6dcbf7 !important;  /* 2단계 박스 채우기 색 */
}
#how-to .panel-body{
  border: none;
}
#how-to .panel-body{
  padding:0px;
}
#how-to .panel-group .panel+.panel{
  margin:0px;
}
#how-to .panel-group .parent{
  border-bottom: 1px solid #fff;
}
#how-to .panel-group .child{
  border-bottom: 1px solid #fff;
}
#superaccordion{
  box-shadow:0 2px 4px 0 rgba(11,0,0,0.16),0 2px 10px 0 rgba(11,0,0,0.12)!important;
}
.panel-heading a:after {
  content: "";
  position: relative;
  top: 1px;
  right:10px;
  display: inline-block;
  font-style: normal;
  font-weight:500;
  font-size:10pt;
  line-height: 1;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  float: right;
  transition: transform .25s linear;
  -webkit-transition: -webkit-transform .25s linear;
  color:#333;
}
.panel-heading a[aria-expanded="true"]:after {
  content: "\2212";
  -webkit-transform: rotate(180deg);
  transform: rotate(180deg);
}
.panel-heading a[aria-expanded="false"]:after {
  content: "\002b";
  -webkit-transform: rotate(90deg);
  transform: rotate(90deg);
}
.parent a:after{
  content: "";
  position: relative;
  top: -15px;
  right:10px;
  display: inline-block;
  line-height: 0;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  float: right;
  transition: transform .25s linear;
  -webkit-transition: -webkit-transform .25s linear;
  color:#333;
}

</style>
 
';
 
echo '
<script>
$(".card-header").parent(".card").hover(
			function() {
				$(this).children(".collapse").collapse("show");
			}, function() {
				$(this).children(".collapse").collapse("hide");
			}
		);
</script>
 
<script>https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js</script>
 
<script>https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js</script>

  	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>

	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>


	<script>    
		function changecheckbox(Eventid,Userid,Wboardid,Checkvalue){
 
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		       url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "wboardid":Wboardid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });	
		}
 
		function changecheckbox2(Eventid,Userid,Wboardid,Pageid,Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		       url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "wboardid":Wboardid,
			 "pageid":Pageid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });	
		 window.open("https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+Wboardid);
		} 
	</script>
</body>
';

?>