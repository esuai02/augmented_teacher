<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$cntid=$_GET["cntid"];  

// 폼 제출 처리
if ($_POST && isset($_POST['save_gameurl'])) {
    $gameurl = $_POST['gameurl'];
    
    // 기존 레코드가 있는지 확인
    $existing = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE cmid = ?", array($cntid));
    
    if ($existing && isset($existing->id)) {
        // 기존 레코드 업데이트 - SQL을 직접 사용
        $sql = "UPDATE mdl_icontent_pages SET gameurl = ?, timemodified = ? WHERE id = ?";
        $params = array($gameurl, time(), $existing->id);
        $DB->execute($sql, $params);
        $message = "Game URL이 성공적으로 업데이트되었습니다.";
    } else {
        // 새 레코드 삽입
        $insertdata = new stdClass();
        $insertdata->cmid = $cntid;
        $insertdata->gameurl = $gameurl;
        $insertdata->timecreated = time();
        $insertdata->timemodified = time();
        
        $DB->insert_record('mdl_icontent_pages', $insertdata);
        $message = "Game URL이 성공적으로 저장되었습니다.";
    }
    
    // 페이지 새로고침을 위해 데이터 다시 조회
    $checkgameurl = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE cmid = ?", array($cntid));
}

$checkgameurl=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE cmid ='$cntid' "); // 전

// 현재 gameurl 값 확인
$current_gameurl = '';
$gameurl_status = '';

if ($checkgameurl && !empty($checkgameurl->gameurl)) {
    $current_gameurl = $checkgameurl->gameurl;
    $gameurl_status = 'Game URL 정보가 있습니다.';
} else {
    $gameurl_status = 'Game URL 정보가 비어있습니다.';
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game URL 관리</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }
        .status {
            padding: 10px;
            margin: 15px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        .status.empty {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.exists {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="text"]:focus {
            border-color: #007cba;
            outline: none;
        }
        .btn {
            background-color: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #005a87;
        }
        .message {
            padding: 15px;
            margin: 15px 0;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        .info {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border-left: 4px solid #007cba;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Game URL 관리</h1>
        
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Content ID:</strong> <?php echo htmlspecialchars($cntid); ?>
        </div>
        
        <div class="status <?php echo empty($current_gameurl) ? 'empty' : 'exists'; ?>">
            <?php echo htmlspecialchars($gameurl_status); ?>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="gameurl">Game URL:</label>
                <input 
                    type="text" 
                    id="gameurl" 
                    name="gameurl" 
                    value="<?php echo htmlspecialchars($current_gameurl); ?>"
                    placeholder="게임 URL을 입력하세요 (예: https://example.com/game)"
                >
            </div>
            
            <button type="submit" name="save_gameurl" class="btn">저장</button>
        </form>
        
        <?php if (!empty($current_gameurl)): ?>
            <div class="info">
                <strong>현재 저장된 URL:</strong><br>
                <a href="<?php echo htmlspecialchars($current_gameurl); ?>" target="_blank">
                    <?php echo htmlspecialchars($current_gameurl); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
