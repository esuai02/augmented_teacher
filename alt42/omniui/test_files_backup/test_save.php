<?php
// 저장 테스트 페이지
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
?>
<!DOCTYPE html>
<html>
<head>
    <title>저장 테스트</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>저장 기능 테스트</h2>
    
    <h3>1. Form POST 테스트</h3>
    <form method="POST" action="save_exam_data_alt42t.php">
        <input type="hidden" name="userid" value="<?php echo $USER->id; ?>">
        <input type="hidden" name="section" value="0">
        <input type="text" name="school" value="테스트고등학교" placeholder="학교">
        <input type="text" name="grade" value="고등학교 2학년" placeholder="학년">
        <input type="text" name="examType" value="1학기 중간고사" placeholder="시험종류">
        <button type="submit">POST 전송</button>
    </form>
    
    <h3>2. AJAX JSON 테스트</h3>
    <button onclick="testAjax()">AJAX 테스트</button>
    <button onclick="testAjaxWithJQuery()">jQuery AJAX 테스트</button>
    
    <h3>3. Fetch API 테스트</h3>
    <button onclick="testFetch()">Fetch 테스트</button>
    
    <h3>결과:</h3>
    <div id="result" style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
        결과가 여기에 표시됩니다...
    </div>
    
    <script>
    function testAjax() {
        const data = {
            userid: <?php echo $USER->id; ?>,
            section: 0,
            school: "AJAX테스트고등학교",
            grade: "고등학교 2학년",
            examType: "1학기 중간고사"
        };
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_exam_data_alt42t.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                document.getElementById('result').innerHTML = 
                    'Status: ' + xhr.status + '<br>' +
                    'Response: <pre>' + xhr.responseText + '</pre>';
            }
        };
        xhr.send(JSON.stringify(data));
    }
    
    function testAjaxWithJQuery() {
        const data = {
            userid: <?php echo $USER->id; ?>,
            section: 0,
            school: "jQuery테스트고등학교",
            grade: "고등학교 2학년",
            examType: "1학기 중간고사"
        };
        
        $.ajax({
            url: 'save_exam_data_alt42t.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                document.getElementById('result').innerHTML = 
                    'Success: <pre>' + JSON.stringify(response, null, 2) + '</pre>';
            },
            error: function(xhr, status, error) {
                document.getElementById('result').innerHTML = 
                    'Error: ' + error + '<br>' +
                    'Status: ' + status + '<br>' +
                    'Response: <pre>' + xhr.responseText + '</pre>';
            }
        });
    }
    
    function testFetch() {
        const data = {
            userid: <?php echo $USER->id; ?>,
            section: 0,
            school: "Fetch테스트고등학교",
            grade: "고등학교 2학년",
            examType: "1학기 중간고사"
        };
        
        console.log('Sending data:', data);
        
        fetch('save_exam_data_alt42t.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('Response headers:', response.headers);
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            document.getElementById('result').innerHTML = 
                'Response: <pre>' + text + '</pre>';
            
            try {
                const json = JSON.parse(text);
                document.getElementById('result').innerHTML += 
                    '<br>Parsed: <pre>' + JSON.stringify(json, null, 2) + '</pre>';
            } catch (e) {
                document.getElementById('result').innerHTML += 
                    '<br>JSON Parse Error: ' + e.message;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('result').innerHTML = 
                'Fetch Error: ' + error.message;
        });
    }
    </script>
    
    <h3>4. 서버 정보</h3>
    <pre>
    User ID: <?php echo $USER->id; ?>
    Username: <?php echo $USER->username; ?>
    Session ID: <?php echo session_id(); ?>
    </pre>
</body>
</html>