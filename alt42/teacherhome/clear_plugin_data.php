<?php
/**
 * 플러그인 데이터 초기화 스크립트
 * 하드코딩된 데이터를 모두 제거하고 깨끗한 상태로 만듭니다.
 */

require_once __DIR__ . '/plugin_db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <title>플러그인 데이터 초기화</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #721c24;
            background: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .button:hover {
            background: #0056b3;
        }
        .danger-button {
            background: #dc3545;
        }
        .danger-button:hover {
            background: #c82333;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>플러그인 데이터 초기화</h1>";
    
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        // 실제 초기화 수행
        echo "<h2>초기화 진행 중...</h2>";
        
        // 1. 현재 데이터 백업
        try {
            $backupSql = "CREATE TABLE IF NOT EXISTS mdl_alt42DB_card_plugin_settings_backup_" . date('YmdHis') . " AS 
                         SELECT * FROM mdl_alt42DB_card_plugin_settings";
            $pdo->exec($backupSql);
            echo "<div class='success'>✓ 백업 테이블 생성 완료</div>";
        } catch (PDOException $e) {
            echo "<div class='warning'>백업 테이블 생성 건너뜀: " . $e->getMessage() . "</div>";
        }
        
        // 2. 하드코딩된 온보딩 카드 제거
        try {
            // agent_type이 'onboarding_item'인 모든 레코드 삭제
            $deleteSql = "DELETE FROM mdl_alt42DB_card_plugin_settings 
                         WHERE agent_type = 'onboarding_item'";
            $stmt = $pdo->prepare($deleteSql);
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            echo "<div class='success'>✓ 온보딩 카드 {$deletedCount}개 삭제 완료</div>";
            
            // 메뉴 구조와 일치하는 하드코딩된 카드들 삭제
            $hardcodedTitles = [
                // 수학교실 카드들
                '맞춤형 학습', '개인진도', '학년군 학습', '내용영역 학습',
                '수행평가', '진단평가', '형성평가', '총괄평가',
                'AI 문제추천', 'AI 오답관리', 'AI 학습분석', 'AI 성취예측',
                '실시간 모니터링', '학습 히트맵', '성장 트래킹', '성취도 분석',
                
                // 학생관리 카드들
                '담임상담', '학부모상담', '심리상담', '진로상담',
                '개인건강', '체력관리', '영양관리', '생활습관',
                '진로탐색', '직업체험', '포트폴리오', '대입준비',
                '동아리', '봉사활동', '학생회', '체험학습',
                
                // 학급운영 카드들
                '학급규칙', '역할분담', '좌석배치', '청소구역',
                '학급회의', '건의사항', '투표하기', '공지사항',
                '포상제도', '벌점관리', '칭찬스티커', '학급화폐',
                '학급신문', '게시판', '사진첩', '타임캡슐',
                
                // 행정업무 카드들
                '출결관리', '생활기록부', '성적처리', '학적관리',
                '기안문서', '공문처리', '보고서', '계획서',
                '연간계획', '주간계획', '시간표', '행사관리',
                '예산관리', '물품구매', '시설관리', '급식관리',
                
                // 소통채널 카드들
                '담임일지', '관찰일지', '상담일지', '특이사항',
                '학급소식', '가정통신', '상담예약', '설문조사',
                '교사모임', '연수공유', '수업연구', '멘토링',
                '학생게시판', '건의함', '칭찬합시다', 'Q&A',
                
                // 바이럴 마케팅 카드들
                '키워드분석', 'SEO최적화', '콘텐츠기획', '포스팅전략',
                '쇼츠제작', '썸네일제작', '알고리즘분석', '채널분석',
                '콘텐츠기획', '해시태그', '바이럴전략', '인플루언서'
            ];
            
            $deleteSql2 = "DELETE FROM mdl_alt42DB_card_plugin_settings 
                          WHERE plugin_name IN (" . implode(',', array_fill(0, count($hardcodedTitles), '?')) . ")";
            $stmt2 = $pdo->prepare($deleteSql2);
            $stmt2->execute($hardcodedTitles);
            $deletedCount2 = $stmt2->rowCount();
            echo "<div class='success'>✓ 하드코딩된 카드 {$deletedCount2}개 추가 삭제 완료</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>삭제 중 오류 발생: " . $e->getMessage() . "</div>";
        }
        
        // 3. 남은 데이터 확인
        try {
            $countSql = "SELECT COUNT(*) as total FROM mdl_alt42DB_card_plugin_settings";
            $stmt = $pdo->query($countSql);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "<div class='success'>✓ 남은 플러그인 카드 수: {$count}개</div>";
            
            // 남은 카드 목록 표시
            if ($count > 0) {
                echo "<h3>남은 카드 목록:</h3><pre>";
                $listSql = "SELECT id, user_id, category, card_title, plugin_id, plugin_name 
                           FROM mdl_alt42DB_card_plugin_settings 
                           ORDER BY category, card_title, display_order";
                $stmt = $pdo->query($listSql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "ID: {$row['id']} | 사용자: {$row['user_id']} | ";
                    echo "카테고리: {$row['category']} | 탭: {$row['card_title']} | ";
                    echo "플러그인: {$row['plugin_id']} | 이름: {$row['plugin_name']}\n";
                }
                echo "</pre>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>데이터 확인 중 오류: " . $e->getMessage() . "</div>";
        }
        
        echo "<h2>✅ 초기화 완료</h2>";
        echo "<p>하드코딩된 온보딩 카드들이 모두 제거되었습니다.</p>";
        echo "<p>이제 좌측 메뉴를 클릭해도 자동으로 카드가 생성되지 않습니다.</p>";
        
    } else {
        // 확인 페이지 표시
        echo "<div class='warning'>
            <strong>⚠️ 주의:</strong> 이 작업은 다음을 수행합니다:
            <ul>
                <li>모든 하드코딩된 온보딩 카드 삭제</li>
                <li>메뉴 구조와 일치하는 기본 카드들 삭제</li>
                <li>사용자가 직접 추가한 카드는 유지</li>
                <li>백업 테이블 자동 생성</li>
            </ul>
        </div>";
        
        // 현재 상태 표시
        try {
            $countSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN agent_type = 'onboarding_item' THEN 1 ELSE 0 END) as onboarding_count
                FROM mdl_alt42DB_card_plugin_settings";
            $stmt = $pdo->query($countSql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>현재 데이터베이스 상태:</h3>";
            echo "<pre>";
            echo "전체 카드 수: {$stats['total']}개\n";
            echo "온보딩 카드 수: {$stats['onboarding_count']}개\n";
            echo "</pre>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>현재 상태 확인 실패: " . $e->getMessage() . "</div>";
        }
        
        echo "<form method='post' style='margin-top: 20px;'>
            <input type='hidden' name='confirm' value='yes'>
            <button type='submit' class='button danger-button' 
                    onclick='return confirm(\"정말로 하드코딩된 데이터를 모두 삭제하시겠습니까?\");'>
                초기화 실행
            </button>
            <a href='index.php' class='button'>취소</a>
        </form>";
    }
    
    echo "</div>
</body>
</html>";
    
} catch (Exception $e) {
    echo "<div class='error'>오류 발생: " . $e->getMessage() . "</div>";
}
?>