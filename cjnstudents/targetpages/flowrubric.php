<?php

// 메타인지는 나에 대한 이해도와 관련이 있다. 나는 목표, 순서, ... , 효율과 관련하여 어떤 선택들을 하고 있으며 어떤 방식으로 변화하고 있는가 혹은 정체되어 있는가에 대한 것이다. 
//나아가 어떤 경로로 초지능학습자가 될 수 있는지를 찾아가는 것이 효과적인지에 대한 이해에 관한 것이다.
//데드라인과 목표설정을 성공할 수 있도록 촉진. 8 flows로 명확한 로드맵 제시...
//몰입유형별 효과 제시
include("flowexpressions.php");
if($type==='목표') // <img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/inspectdata.png width=15>
	{ 
 
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow1/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow1/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow1/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow1/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow1/c5.html';

	$ntype=1;$typetext='목표 메타인지 실행내용을 입력하고 공부를 시작합니다 (<span style="color:blue;">몰입지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/목표%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp;  </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></td></tr></table>';
	$setcolor1='black';  // Checkflow(Itemid,Ntype,Userid,Text, Checkvalue)     
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/red.png" width=60>';           
	}
elseif($type==='순서')
	{ 
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow2/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow2/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow2/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow2/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow2/c5.html';

	$ntype=2;$typetext='순서 메타인지 실행내용을 입력하고 공부를 시작합니다(<span style="color:blue;">선택지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/순서%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></td></tr></table>';
	$setcolor2='black';
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/orange.png" width=60>'; 
	}
elseif($type==='기억') // 기억은 나지 않지만 풀면 풀린다. 몇 % 풀리는가를 중심으로 스토리텔링한다.
	{ 
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow3/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow3/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow3/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow3/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow3/c5.html';

	$ntype=3;$typetext='기억 메타인지 실행내용을 입력하고 공부를 시작합니다(<span style="color:blue;">성찰지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/기억%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></td></tr></table>';
	$setcolor3='black';
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/yellow.png" width=60>'; 
	}
elseif($type==='몰입') // 문항 > 유형 > 소주제 > 주제 > 단원 > 영역의 구조에 대한 마인드맵이 효과 ! mindmap - 메타인지 도구
	{
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow4/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow4/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow4/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow4/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow4/c5.html';

	$ntype=4;$typetext='몰입 메타인지 실행내용을 입력하고 공부를 시작합니다(<span style="color:blue;">연결지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/몰입%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></td></tr></table>';
	$setcolor4='black';
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/white.png" width=60>'; 
	}
elseif($type==='발상')
	{ 
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow5/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow5/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow5/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow5/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow5/c5.html';

	$ntype=5;$typetext='발상 메타인지 실행내용을 입력하고 공부를 시작합니다(<span style="color:blue;">출력지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/발상%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></td></tr></table>';
	$setcolor5='black';
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/green.png" width=60>'; 
	}
elseif($type==='해석')
	{
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow6/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow6/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow6/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow6/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow6/c5.html';

	$ntype=6;$typetext='해석 메타인지 실행내용을 입력하고 공부를 시작합니다(<span style="color:blue;">분석지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/해석%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></td></tr></table>';
	$setcolor6='black';
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/blue.png" width=60>'; 
	}

elseif($type==='숙달')
	{ 
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow7/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow7/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow7/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow7/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow7/c5.html';

	$ntype=7;$typetext='숙달 메타인지 실행내용을 입력하고 공부를 시작합니다(<span style="color:blue;">입력지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/숙달%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></td></tr></table>';
	$setcolor7='black';
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/black.png" width=60>'; 
	}
elseif($type==='효율')
	{ 
	$helpurl1='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow8/c1.html';
	$helpurl2='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow8/c2.html';
	$helpurl3='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow8/c3.html';
	$helpurl4='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow8/c4.html';
	$helpurl5='https://mathking.kr/moodle/local/augmented_teacher/twinery/flowitems/flow8/c5.html';

	$ntype=8;$typetext='효율 메타인지 실행내용을 입력하고 공부를 시작합니다(<span style="color:blue;">가속지능</span>)';$typeurl='https://mathking.kr/moodle/local/augmented_teacher/twinery/효율%20메타인지.html';
	$rubric='<table align=center>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk1.' onClick="Checkflow(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\', this.checked)"/>1. '.$flowitem1.'<a href="'.$helpurl1.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(1,\''.$type.'\',\''.$studentid.'\',\''.$flowitem1.'\')"/>제출</button>'.$dataurl1.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk2.' onClick="Checkflow(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\', this.checked)"/>2. '.$flowitem2.'<a href="'.$helpurl2.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(2,\''.$type.'\',\''.$studentid.'\',\''.$flowitem2.'\')"/>제출</button>'.$dataurl2.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk3.' onClick="Checkflow(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\', this.checked)"/>3. '.$flowitem3.'<a href="'.$helpurl3.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(3,\''.$type.'\',\''.$studentid.'\',\''.$flowitem3.'\')"/>제출</button>'.$dataurl3.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk4.' onClick="Checkflow(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\', this.checked)"/>4. '.$flowitem4.'<a href="'.$helpurl4.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(4,\''.$type.'\',\''.$studentid.'\',\''.$flowitem4.'\')"/>제출</button>'.$dataurl4.'&nbsp; </td></tr><tr><td align=center><br></td></tr>
	<tr><td valign=top><input type=checkbox style="margin-top:5px;" '.$chk5.' onClick="Checkflow(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\', this.checked)"/>5. '.$flowitem5.'<a href="'.$helpurl5.'"target="_blank"><img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/help.png width=15></a> <button onClick="flowstamp(5,\''.$type.'\',\''.$studentid.'\',\''.$flowitem5.'\')"/>제출</button>'.$dataurl5.'&nbsp; </td></tr><tr><td align=center><br></table>';
	$setcolor8='black';
	$hat='<img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/bluem.png" width=60>'; 
	}
?>
