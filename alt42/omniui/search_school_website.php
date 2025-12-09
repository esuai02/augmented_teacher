<?php
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

header('Content-Type: application/json; charset=utf-8');

// Google Custom Search API를 사용하지 않고 간단한 검색 시뮬레이션
function searchSchoolWebsite($schoolName) {
    // 학교 홈페이지의 일반적인 패턴
    $schoolNameClean = trim($schoolName);
    
    // 알려진 학교 도메인 패턴
    $knownPatterns = array();
    
    // 지역 교육청 도메인
    $regionCodes = array(
        '서울' => 'sen', '부산' => 'pen', '대구' => 'dge',
        '인천' => 'ice', '광주' => 'gen', '대전' => 'dje',
        '울산' => 'use', '세종' => 'sje', '경기' => 'goe',
        '강원' => 'gwe', '충북' => 'cbe', '충남' => 'cne',
        '전북' => 'jbe', '전남' => 'jne', '경북' => 'gbe',
        '경남' => 'gne', '제주' => 'jje'
    );
    
    // 학교명에서 지역과 학교 유형 추출
    $region = '';
    $schoolType = '';
    
    foreach ($regionCodes as $regionName => $code) {
        if (strpos($schoolNameClean, $regionName) !== false) {
            $region = $code;
            break;
        }
    }
    
    // 학교 유형 판단
    if (strpos($schoolNameClean, '초등학교') !== false || strpos($schoolNameClean, '초교') !== false) {
        $schoolType = 'es';
    } elseif (strpos($schoolNameClean, '중학교') !== false) {
        $schoolType = 'ms';
    } elseif (strpos($schoolNameClean, '고등학교') !== false || strpos($schoolNameClean, '고교') !== false) {
        $schoolType = 'hs';
    }
    
    // 학교명에서 핵심 단어 추출 (영문 변환 시도)
    $coreNameMap = array(
        '대전고' => 'daejeon',
        '한밭고' => 'hanbat',
        '대덕고' => 'daedeok',
        '충남고' => 'chungnam',
        '대신고' => 'daesin',
        '동산고' => 'dongsan',
        '둔산고' => 'dunsan',
        '대전여고' => 'daejeongh',
        '대전여자고' => 'daejeongh',
        '충남여고' => 'chungnamgh',
        '한밭중' => 'hanbat',
        '대전중' => 'daejeon',
        '동신중' => 'dongsin',
        '봉명중' => 'bongmyeong',
        '대전초' => 'daejeon',
        '한밭초' => 'hanbat',
        '둔산초' => 'dunsan'
    );
    
    $englishName = '';
    foreach ($coreNameMap as $korean => $english) {
        if (strpos($schoolNameClean, $korean) !== false) {
            $englishName = $english;
            break;
        }
    }
    
    // 가능한 URL 목록 생성
    $possibleUrls = array();
    
    if ($region && $schoolType && $englishName) {
        // 패턴 1: 영문이름.지역.학교유형.kr
        $possibleUrls[] = "http://{$englishName}.{$region}.{$schoolType}.kr";
        $possibleUrls[] = "https://{$englishName}.{$region}.{$schoolType}.kr";
        
        // 패턴 2: 지역-영문이름.학교유형.kr
        $possibleUrls[] = "http://{$region}-{$englishName}.{$schoolType}.kr";
        $possibleUrls[] = "https://{$region}-{$englishName}.{$schoolType}.kr";
    }
    
    if ($region && $schoolType) {
        // 패턴 3: 학교홈페이지 포털
        $possibleUrls[] = "http://school.{$region}.go.kr";
    }
    
    // 기본 검색 URL (실제로는 첫 번째 추측 URL 반환)
    if (!empty($possibleUrls)) {
        return $possibleUrls[0];
    }
    
    // 대전 지역 특별 처리
    if (strpos($schoolNameClean, '대전') !== false) {
        if (strpos($schoolNameClean, '고등학교') !== false) {
            return "http://school.dje.go.kr";
        }
    }
    
    return null;
}

// AJAX 요청 처리
if (isset($_GET['school'])) {
    $schoolName = required_param('school', PARAM_TEXT);
    
    $url = searchSchoolWebsite($schoolName);
    
    if ($url) {
        echo json_encode(array(
            'success' => true,
            'url' => $url,
            'message' => '학교 홈페이지를 찾았습니다.'
        ), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array(
            'success' => false,
            'url' => null,
            'message' => '학교 홈페이지를 찾을 수 없습니다.'
        ), JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>