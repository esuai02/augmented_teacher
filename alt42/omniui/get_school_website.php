<?php
require_once('/home/moodle/public_html/moodle/config.php');
global $DB;
require_login();

header('Content-Type: application/json; charset=utf-8');

// 기본 학교 도메인 패턴으로 URL 추측
function guessSchoolWebsite($schoolName) {
    // 학교명에서 핵심 단어 추출
    $schoolName = str_replace(' ', '', $schoolName);
    
    // 학교 유형별 일반적인 패턴
    $patterns = array();
    
    // 지역별 교육청 도메인
    $eduOffices = array(
        '서울' => 'sen',
        '부산' => 'pen',
        '대구' => 'dge',
        '인천' => 'ice',
        '광주' => 'gen',
        '대전' => 'dje',
        '울산' => 'use',
        '세종' => 'sje',
        '경기' => 'goe',
        '강원' => 'gwe',
        '충북' => 'cbe',
        '충남' => 'cne',
        '전북' => 'jbe',
        '전남' => 'jne',
        '경북' => 'gbe', 
        '경남' => 'gne',
        '제주' => 'jje'
    );
    
    // 지역 찾기
    foreach ($eduOffices as $region => $code) {
        if (strpos($schoolName, $region) !== false) {
            // 고등학교
            if (strpos($schoolName, '고등') !== false || strpos($schoolName, '고교') !== false) {
                $patterns[] = "http://{$code}.hs.kr";
                $patterns[] = "https://{$code}.hs.kr";
            }
            // 중학교
            else if (strpos($schoolName, '중학') !== false) {
                $patterns[] = "http://{$code}.ms.kr";
                $patterns[] = "https://{$code}.ms.kr";
            }
            // 초등학교
            else if (strpos($schoolName, '초등') !== false || strpos($schoolName, '초교') !== false) {
                $patterns[] = "http://{$code}.es.kr";
                $patterns[] = "https://{$code}.es.kr";
            }
            break;
        }
    }
    
    return $patterns;
}

// 학교명으로 홈페이지 URL 찾기
function getSchoolWebsite($schoolName) {
    global $DB;
    
    // 먼저 데이터베이스에서 학교 홈페이지 정보가 있는지 확인
    // (향후 학교 홈페이지 정보를 저장하는 테이블을 만들 수 있음)
    
    // 일반적인 학교 홈페이지 패턴
    $patterns = array(
        'hs' => '.hs.kr', // 고등학교
        'ms' => '.ms.kr', // 중학교
        'es' => '.es.kr', // 초등학교
    );
    
    // 학교명 정리 (공백 제거, 소문자 변환)
    $cleanName = str_replace(' ', '', $schoolName);
    $cleanName = mb_strtolower($cleanName, 'UTF-8');
    
    // 학교 유형 판단
    $schoolType = '';
    if (strpos($schoolName, '고등학교') !== false || strpos($schoolName, '고교') !== false) {
        $schoolType = 'hs';
    } else if (strpos($schoolName, '중학교') !== false) {
        $schoolType = 'ms';
    } else if (strpos($schoolName, '초등학교') !== false || strpos($schoolName, '초교') !== false) {
        $schoolType = 'es';
    }
    
    // 학교명에서 영문 추출 시도
    $englishName = '';
    
    // 지역별 교육청 도메인 패턴
    $eduDomains = array(
        '서울' => 'sen',
        '부산' => 'pen', 
        '대구' => 'dge',
        '인천' => 'ice',
        '광주' => 'gen',
        '대전' => 'dje',
        '울산' => 'use',
        '세종' => 'sje',
        '경기' => 'goe',
        '강원' => 'gwe',
        '충북' => 'cbe',
        '충남' => 'cne',
        '전북' => 'jbe',
        '전남' => 'jne',
        '경북' => 'gbe',
        '경남' => 'gne',
        '제주' => 'jje'
    );
    
    // 지역 찾기
    $region = '';
    foreach ($eduDomains as $key => $code) {
        if (strpos($schoolName, $key) !== false) {
            $region = $code;
            break;
        }
    }
    
    // 몇 가지 일반적인 학교 홈페이지 URL 추측
    $possibleUrls = array();
    
    // 학교명이 특정 패턴을 가진 경우
    if ($region && $schoolType) {
        // 지역 교육청 기반 URL은 더 복잡한 패턴을 가지므로
        // 실제로는 검색을 권장
        $possibleUrls[] = "https://school.{$region}.go.kr";
    }
    
    // 간단한 검증 (실제 URL 확인은 하지 않음)
    if (!empty($possibleUrls)) {
        return array(
            'success' => true,
            'url' => $possibleUrls[0],
            'message' => '학교 홈페이지 URL (추정)'
        );
    }
    
    // 추측된 URL 패턴 가져오기
    $guessedUrls = guessSchoolWebsite($schoolName);
    if (!empty($guessedUrls)) {
        // 첫 번째 추측 URL 반환 (실제 크롤링은 클라이언트에서 처리)
        return array(
            'success' => true,
            'url' => $guessedUrls[0],
            'guessedUrls' => $guessedUrls,
            'message' => '학교 홈페이지 URL 추측'
        );
    }
    
    // URL을 찾을 수 없는 경우
    return array(
        'success' => false,
        'url' => null,
        'message' => '학교 홈페이지를 찾을 수 없습니다. 검색을 시도합니다.'
    );
}

// AJAX 요청 처리
if (isset($_GET['school'])) {
    $schoolName = required_param('school', PARAM_TEXT);
    $result = getSchoolWebsite($schoolName);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
?>