<?php 
$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 미확인';

 
if($status==='flag')$imgstatus='<img src="https://mathking.kr/Contents/IMAGES/bookmark.png" width="15"> 책갈피';  
elseif($status==='bookmark')$imgstatus='<img src="https://mathking.kr/Contents/IMAGES/bookmark.png" width="15">  '; 
elseif($status==='givestar')$imgstatus='<img src="https://mathking.kr/Contents/IMAGES/staricon.jpg" width="15"> 별점'; 
elseif($status==='drilling')$imgstatus='<img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/drilling2.png" width="15"> 집중'; 
elseif($status==='review' && $value['teacher_check']==2)$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> OK';  
elseif($status==='attempt')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1622515447001.png" width="15"><span style="color: rgb(233, 33, 33);"> 시도</span>';
elseif($status==='easy')$imgstatus='<img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/easy.png" width="15"> 쉬운';
elseif($status==='boost')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1630677242001.png" width="15"> 성장';
elseif($status==='synapse')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1634440067.png" width="15"> 몰입';
elseif($status==='complete')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 완료';
elseif($status==='ask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603040404001.png" width="15"><span style="color: rgb(233, 33, 33);"> 요청</span>';
elseif($status==='exam')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1607157822001.png" width="15"><span style="color: rgb(33, 33, 233);"> 시작</span>';
elseif($status==='attempt')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1615010747001.png" width="15"><span style="color: rgb(33, 33, 233);"> 시도</span>';
elseif($status==='begin')$imgstatus='<img src="http://mathking.kr/Contents/IMAGES/prepare.png" width="15"><span style="color: rgb(33, 33, 233);"> 준비</span>';
elseif($status==='studentreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1607157896001.png" width="15"><span style="color: rgb(33, 33, 233);"> 응답</span>';
elseif($status==='askcorrection')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1620874600001.png" width="15"><span style="color: rgb(33, 33, 233);"> 첨삭</span>';
elseif($status==='ask' && strpos($encryption_id, "qrorkraknsrjsc")!=false)$imgstatus='<img src="http://mathking.kr/Contents/IMAGES/red.png" width="15"><span style="color: rgb(233, 33, 33);"> 긴급</span>';
elseif($status==='review')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> 예약';  
elseif($status==='reply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204129001.png" width="15"><span style="color: rgb(233, 33, 33);"> 답변</span>';  
elseif($status==='penreply')$imgstatus='<img style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654825291.png" width="18"><span style="color: rgb(233, 33, 33);"> 첨삭</span>';  
elseif($status==='solution')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186545001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$value['wbfeedback'].'&originalid='.$encryption_id.'" target="_blank"> <u>풀이</u></a></span>';   
elseif($status==='solutionask')$imgstatus='<img src="http://mathking.kr/Contents/IMAGES/green.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid='.$encryption_id.'" target="_blank"> <u>질문</u></a></span>';   
elseif($status==='solutionreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186950001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid='.$encryption_id.'" target="_blank"> <u>답변</u></a></span>';   
elseif($status==='steps')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609642998001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/cognitivesteps.php?id='.$encryption_id.'&recommend=1&nstep=1" target="_blank"> <u>단계</u></a></span>';   
elseif($status==='classroom')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1610682198001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$encryption_id.'&originalid='.$encryption_id.'" target="_blank"> 도움</a></span>';   
elseif($status==='stepbystep')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1611157733001.png" width="15"><span style="color: rgb(233, 33, 33);">  도제</span>';   
elseif($status==='submitstepbystep')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1611163161001.png" width="15"><span style="color: rgb(233, 33, 33);">  도제</span>';   
elseif($status==='retry')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1636664070.png" width="15"><span style="color: rgb(233, 33, 33);">  복습</span>';  
elseif($status==='present')$imgstatus='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=bessi'.$encryption_id.'&srcid='.$encryption_id.'"target="_blank"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617048169001.png" width="15"><span style="color: rgb(233, 33, 33);">  발표</a></span>';  
elseif($status==='analysis')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613371536001.png" width="15"><span style="color: rgb(233, 33, 33);">  분석</span>';   
elseif($status==='first')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613520283001.png" width="15"><span style="color: rgb(233, 33, 33);">  발상</span>';   
elseif($status==='how')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613517410001.png" width="15"><span style="color: rgb(233, 33, 33);">  비결</span>';   
elseif($status==='topics')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1622518606001.png" width="15"><span style="color: rgb(233, 33, 33);">  점검</span>';   
elseif($status==='begintopic')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1622518606001.png" width="15"><span style="color: rgb(233, 33, 33);">  개념</span>';   
elseif($status==='expand')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613517684001.png" width="15"><span style="color: rgb(233, 33, 33);">  확장</span>';   
elseif($status==='accelerate')$imgstatus='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1623079082001.png" width="15"><span style="color: rgb(233, 33, 33);">  가속</span>';   
elseif($status==='summary' || $status==='examplan'|| $status==='weekly')  $imgstatus='<img src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width="20"> 정리';
?>
