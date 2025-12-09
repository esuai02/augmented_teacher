<?php  // Welcome 페이지
// 조건문으로 메뉴조절. 선생님이 페이지별로 선택하는 메뉴 자동생성
$visualart='<img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/welcome.png width=80%>';
$pageintro='<table align=center><tr><td align=center>'.$visualart.'</td></tr></table>';

if($step==NULL)
    {
    $pagewelcome='Welcome. 안녕하세요, 환영합니다. Mathking은 개인 맞춤형 수학공부를 도와드립니다. 현재 학년과 개념진도 수준 및 복습이 필요한 부분 및 시험대비 상황 등을 고려하여 공부환경을 세팅하게 됩니다. 지금부터 아이디 생성부터 강좌 세팅까지 순서대로 안내해 드리겠습니다. 안내에 따라 초기설정을 진행해 주시기 바랍니다.';
    $showpage= '<table with=100% align=center><tr><td>Welcome 페이지 홈</td></tr><tr><td>공부를 시작할 때 효과적인 플러그인들을 추가하여 학습의 흐름을 원활하게 할 수 있습니다.</td></tr><tr><td>학습을 촉진시킬 감정엔진을 만들어보세요</td></tr>
<tr><td>플러그인을 추가해 주세요 (+)</td></tr></table>'; // 기본 컨텐츠
    }
elseif($step==1)
    {
    $pagewelcome='먼저 아이디 생성을 도와 드리겠습니다. 우측 화면에서 아이디, 비밀번호를 입력하신 후 생성하기 버튼을 눌려주세요.';
    $showpage= '<body>
    <form id="create-user-form" method="post" action="">
    <label>성</label>
    <input type="text" name="firstname" required><br><br>
    <label>이름</label>
    <input type="text" name="lastname" required><br><br>
    <label>아이디</label>
    <input type="text" name="username" required><br><br>
    <button type="button" class="submit-button2" onclick="createUser();">생성하기</button>
  </form></body>
  '; 
    echo '
    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
    
    <link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
    
   
    <script>
    function createUser() 
        {
        const form = document.getElementById("create-user-form");
        const firstname = form.elements.firstname.value;
        const lastname = form.elements.lastname.value;
        const username = form.elements.username.value;
       
         
        alert(lastname);
       
        $.ajax({
            url: "./pages/database.php",
            type: "POST",
            dataType: "json",
            data : {
            "eventid":\'1\',
            "username":username,
            "firstname":firstname,
            "lastname":lastname,              
                },
                success:function(data){
                    var resulttext=data.result;
                    var mode=data.mode;
                    alert(resulttext);
                    if(mode==1)window.location.href = "https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?type=firstvisit&step=2&username="+username;
                    }
            });
        }
  </script>';
  
    }
elseif($step==2)
    {
    $username=$_GET["username"];
    $pagewelcome='첫 번째 로그인을 환영합니다 ! 생성된 아이디는 '.$username.'입니다. 초기 비밀번호는 ktm입니다. 우측 화면에서 먼저 로그인을 한 다음 NEXT 버튼을 누르고 비밀번호를 변경해 주세요.';
    $showpage= '<table width=100% height=100% align=center><tr height=90%><td height=90%><iframe src="https://mathking.kr/moodle/login/index.php" width=80% height=600></iframe></td></tr></table>'; // 기본 컨텐츠
    }
elseif($step==3)
    {
    $pagewelcome='우측 화면에 초기 비밀번호 ktm을 입력하고 새로운 비밀번호를 입력한 다음 변경사항 저장을 클릭해 주세요. 다음으로 NEXT 버튼을 누르고 수학공부 코스를 선택해 주세요.';
    $showpage= '<table width=100% height=100% align=center><tr height=90%><td height=90%><iframe src="https://mathking.kr/moodle/login/change_password.php?id=1&userid='.$userid.'" width=80% height=800></iframe></td></tr></table>'; // 기본 컨텐츠
    }
elseif($step==4)
    {
    $pagewelcome='이제 마지막으로 공부를 시작할 강좌를 선택할 순서입니다.  ';
    $showpage= '<table width=100% align=center><tr><td>Welcome 페이지 홈</td></tr><tr><td>공부를 시작할 때 효과적인 플러그인들을 추가하여 학습의 흐름을 원활하게 할 수 있습니다.</td></tr><tr><td>학습을 촉진시킬 감정엔진을 만들어보세요</td></tr>
    <tr><td>플러그인을 추가해 주세요 (+)</td></tr></table>'; // 기본 컨텐츠
    $buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?userid='.$USER->id.'&type=mycourses&mode=firstcourse"><button class="submit-button2">NEXT</button></a></td>';
    $DB->execute("UPDATE {user_info_data} SET data='student' WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 ");
    $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data,dataformat) VALUES('$USER->id','22','student','0')");	
    } 
$step=$step+1;
if($step==NULL)$step=1;
if($userid==NULL)$studentid=$USER->id;
// 조건문으로 선생님별로 선택
//$buttons.= '<td><button class="submit-button" id="updateButton1" onclick="">퀴즈결과</button></td>';
//$buttons.= '<td><button class="submit-button" id="updateButton2" onclick="">오답노트</button></td>';
if($step==2 ||$step==5)$buttons.= ''; 
else $buttons.= '<td><a href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/chatbot.php?type=firstvisit&step='.$step.'"><button class="submit-button2">NEXT</button></a></td>';
$buttons='<tr>'.$buttons.'</tr>';
?> 