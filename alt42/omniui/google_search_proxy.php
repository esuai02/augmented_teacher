<?php
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

header('Content-Type: application/json; charset=utf-8');

// Google 검색 결과에서 첫 번째 링크 추출
function getFirstGoogleResult($query) {
    try {
        // DuckDuckGo 즉시 이동 기능 사용 (Google 대신)
        $duckUrl = "https://duckduckgo.com/?q=" . urlencode("!ducky " . $query);
        
        // Headers 설정
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n" .
                           "Accept: text/html,application/xhtml+xml\r\n",
                'follow_location' => 0,
                'timeout' => 5
            )
        );
        
        $context = stream_context_create($opts);
        
        // HEAD 요청으로 리다이렉트 위치 확인
        $headers = @get_headers($duckUrl, 1, $context);
        
        if ($headers && isset($headers['Location'])) {
            $location = is_array($headers['Location']) ? $headers['Location'][0] : $headers['Location'];
            if (filter_var($location, FILTER_VALIDATE_URL)) {
                return $location;
            }
        }
        
        // 백업: 학교 도메인 직접 추측
        if (strpos($query, '학교') !== false && strpos($query, '홈페이지') !== false) {
            // 학교명 추출
            $schoolName = str_replace(' 홈페이지', '', $query);
            
            // 일반적인 학교 홈페이지 패턴
            $patterns = array(
                '.hs.kr',  // 고등학교
                '.ms.kr',  // 중학교  
                '.es.kr',  // 초등학교
                '.sc.kr',  // 학교
                '.go.kr'   // 교육청
            );
            
            // 지역 코드
            $regions = array('sen', 'pen', 'dge', 'ice', 'gen', 'dje', 'use', 'sje', 
                           'goe', 'gwe', 'cbe', 'cne', 'jbe', 'jne', 'gbe', 'gne', 'jje');
            
            // 학교명 영문 변환 시도 (간단한 예시)
            $nameMap = array(
                '대전' => 'daejeon',
                '한밭' => 'hanbat',
                '충남' => 'chungnam',
                '대덕' => 'daedeok',
                '둔산' => 'dunsan',
                '서울' => 'seoul',
                '부산' => 'busan'
            );
            
            foreach ($nameMap as $korean => $english) {
                if (strpos($schoolName, $korean) !== false) {
                    foreach ($regions as $region) {
                        foreach ($patterns as $pattern) {
                            $testUrl = "http://{$english}.{$region}{$pattern}";
                            $headers = @get_headers($testUrl);
                            if ($headers && strpos($headers[0], '200') !== false) {
                                return $testUrl;
                            }
                        }
                    }
                }
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log('Search proxy error: ' . $e->getMessage());
        return null;
    }
}

// AJAX 요청 처리
if (isset($_GET['q'])) {
    $query = required_param('q', PARAM_TEXT);
    
    $firstUrl = getFirstGoogleResult($query);
    
    if ($firstUrl) {
        echo json_encode(array(
            'success' => true,
            'firstUrl' => $firstUrl,
            'message' => '검색 결과를 찾았습니다.'
        ), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array(
            'success' => false,
            'firstUrl' => null,
            'message' => '검색 결과를 찾을 수 없습니다.'
        ), JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>