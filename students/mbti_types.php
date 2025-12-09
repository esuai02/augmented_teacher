<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid = $_GET["studentid"];
$type = $_GET["type"];
 
$type='present';$typeimg='https://mathking.kr/Contents/IMAGES/present.png';$mode='initial';$placeholder=' 결과 입력하기';
 
// Use parameterized queries for security
$username = $DB->get_record_sql(
    "SELECT * FROM {user} WHERE id = :id",
    array('id' => $studentid)
);
$studentname = $username->firstname.$username->lastname;
$timecreated = time();
$userrole = $DB->get_record_sql(
    "SELECT data AS role FROM {user_info_data} WHERE userid = :userid AND fieldid = :fieldid",
    array('userid' => $USER->id, 'fieldid' => '22')
); 
$role = $userrole->role;

 

$mbtilog1 = $DB->get_records_sql(
    "SELECT * FROM {abessi_mbtilog} WHERE userid = :userid AND type = :type ORDER BY id ASC LIMIT 100",
    array('userid' => $studentid, 'type' => 'present')
);
$result1 = json_decode(json_encode($mbtilog1), True);

$mbti1 = '';
unset($value1);  
foreach($result1 as $value1)
{
    $tcreated1=date("m월d일", $value1['timecreated']);   
    $mbti1.='<td><img src="https://mathking.kr/Contents/IMAGES/present.png" width=20> <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-'.$value1['mbti'].'"target="_blank"><b>'.strtoupper($value1['mbti']).'</b></a>('.$tcreated1.') &nbsp;&nbsp;&nbsp;&nbsp; </td>';	
}
 
$mbtilog2 = $DB->get_records_sql(
    "SELECT * FROM {abessi_mbtilog} WHERE userid = :userid AND type = :type ORDER BY id ASC LIMIT 100",
    array('userid' => $studentid, 'type' => 'initial')
);
$result2 = json_decode(json_encode($mbtilog2), True);

$mbti2 = '';
unset($value2);  
foreach($result2 as $value2)
{
    $tcreated2=date("m월d일", $value2['timecreated']);   
    $mbti2.='<td><img src="https://mathking.kr/Contents/IMAGES/baby.png" width=20> <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-'.$value2['mbti'].'"target="_blank"><b>'.strtoupper($value2['mbti']).'</b></a>('.$tcreated2.')  &nbsp;&nbsp;&nbsp;&nbsp;  </td>';	
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBTI 검사 - Math Learning Platform</title>
    
    <!-- External CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/css/ready.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #333;
        }

        /* 네비게이션 바 */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-button {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            color: white;
            text-decoration: none;
        }

        .user-info {
            font-family: monospace;
            color: #333;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* 메인 컨테이너 */
        .main-container {
            padding: 3rem;
            max-width: 1400px;
            margin: 2rem auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-align: center;
            color: #333;
        }

        /* 기존 스타일 개선 */
        table {
            margin: 1rem auto;
            text-align: center;
            background: transparent;
            color: #333;
        }
        
        table td {
            text-align: center;
            color: #333;
            padding: 0.5rem;
        }
        
        table a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        table a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        input[type="text"] {
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            background: white;
            color: #333;
            border-radius: 0.5rem;
            font-size: 1.1rem;
            margin: 0.5rem;
            transition: all 0.3s;
            width: 100%;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        input[type="text"]::placeholder {
            color: #999;
        }

        button {
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        hr {
            margin: 2.5rem 0;
            border: none;
            border-top: 2px solid #e0e0e0;
            opacity: 0.5;
        }
        
        /* MBTI 그리드 스타일 */
        .mbti-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 1rem;
            margin: 2rem 0;
        }
        
        .mbti-item {
            text-align: center;
            transition: transform 0.3s;
        }
        
        .mbti-item:hover {
            transform: scale(1.05);
        }
        
        .mbti-item img {
            width: 100%;
            max-width: 180px;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* 대칭적인 레이아웃 */
        .content-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .mbti-diagram {
            flex: 0 0 auto;
        }
        
        .mbti-diagram img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        /* 입력 섹션 스타일 */
        .input-section {
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-content: center;
            margin: 2rem 0;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 1rem;
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex: 1;
            max-width: 500px;
        }
        
        /* MBTI 로그 섹션 */
        .mbti-log {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 1rem;
            margin: 1rem 0;
        }
        
        .mbti-log table {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-header > div {
            flex: 1;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <div class="navbar">
        <div class="nav-links"> 
            <a href="/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=<?php echo $studentid; ?>" class="nav-button">
                <span>🏠</span> 홈
            </a>
 
            <a href="/moodle/local/augmented_teacher/alt42/studenthome/selectmode.php?userid=<?php echo $studentid; ?>" class="nav-button">
                <span>🎯</span> 학습모드
            </a>
 
        </div>
        <div class="user-info">
            <span>👤</span>
            <span><?php echo $studentname; ?></span>
        </div>
    </div>

    <!-- 메인 컨테이너 -->
    <div class="main-container">
        <!-- 정보 헤더 -->
        <div class="info-header">
            <div>
                <h3 style="margin: 0; color: #667eea;text-align: center;">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $studentid; ?>&tb=604800" style="color: inherit;">
                        <?php echo $studentname; ?>
                    </a>
                </h3>
            </div> 
            <div>
                <a href="https://www.16personalities.com/ko/%EB%AC%B4%EB%A3%8C-%EC%84%B1%EA%B2%A9-%EC%9C%A0%ED%98%95-%EA%B2%80%EC%82%AC" target="_blank" class="nav-button" style="padding: 0.5rem 1rem;">
                    MBTI 검사하기
                </a>
       
            </div>
        </div>
        
        <hr>

        <!-- MBTI 다이어그램과 그리드 섹션 -->
        <div class="content-section">
            <div class="mbti-diagram">
                <img src="https://mathking.kr/Contents/IMAGES/mbti-diagram.jpg" width="500">
            </div>
            <div class="mbti-grid">
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-istj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/istj.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-isfj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/isfj.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-infj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/infj.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-intj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/intj.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-istp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/istp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-isfp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/isfp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-infp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/infp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-intp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/intp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-estp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/estp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-esfp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/esfp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-enfp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/enfp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-entp" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/entp.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-estj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/estj.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-esfj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/esfj.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%85-enfj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/enfj.png">
                    </a>
                </div>
                <div class="mbti-item">
                    <a href="https://www.16personalities.com/ko/%EC%84%B1%EA%B2%A9%EC%9C%A0%ED%98%95-entj" target="_blank">
                        <img src="https://mathking.kr/Contents/IMAGES/entj.png">
                    </a>
                </div>
            </div>
        </div>
        
        <hr>
        
        <!-- 입력 섹션 -->
        <div class="input-section">
            <div class="input-group">
                <input type="text" id="squareInput" name="squareInput" placeholder="<?php echo $placeholder; ?>">
                <button onClick="Submitmbti('<?php echo $studentid; ?>','<?php echo $type; ?>',document.getElementById('squareInput').value)">제출</button>
            </div>
            
        </div>
        
        <hr>
        
        <!-- MBTI 로그 섹션 -->
        <div class="mbti-log">
            <table style="margin: 0 auto;">
                <tr><?php echo $mbti1; ?></tr>
            </table>
        </div>
         
        <hr>
    </div>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/pep/0.4.3/pep.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <script>
    function Submitmbti(Userid, Type, Mbti) {
        swal("MBTI 프로필이 업데이트 되었습니다 !", {buttons: false, timer: 2000});
        $.ajax({
            url: "checkflow.php",
            type: "POST",
            dataType: "json",
            data: {
                "eventid": '3',
                "userid": Userid,
                "mbti": Mbti,
                "type": Type,
            },
            success: function(data) {
                // Handle success
            }
        });
        setTimeout(function() {
            location.reload();
        }, 2000);
    }

    function SearchMbti(searchTerm) {
        // Implement search functionality if needed
        alert('검색 기능은 추가 구현이 필요합니다.');
    }
    </script>
</body>
</html>