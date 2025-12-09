<?php
// Moodle 환경 초기화
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

if (!isset($DB) || !$DB instanceof moodle_database) {
    die("Database object not properly initialized.");
}

// GET 파라미터
$studentid = $_GET["userid"] ?? $USER->id;
$tbegin = $_GET["tb"] ?? null;
$tend = $_GET["te"] ?? null;
$thiscontentsid = $_GET['contentsid'] ?? null;
$thiscontentstype = $_GET['contentstype'] ?? null; // 현재 컨텐츠 타입
$viewfilter = $_GET['viewfilter'] ?? 1; // 0=전체,1=개념,2=문항 / 기본 개념컨텐츠(1)

// 로그인 필요
require_login();

// 현재 시간과 12시간 전 계산
$timecreated = time();
$hoursago = $timecreated - 43200;

// 수학 용어 사전 예시(간략화 가능)
$mathTerms = [
    '수체계' => [
        '정수', '유리수', '무리수', '자연수', '실수', '복소수', '소수', '합성수',
        '양수', '음수', '짝수', '홀수', '유한소수', '순환소수', '무한소수', '분수',
        '비율', '비례', '최대공약수', '최소공배수', '약수', '배수', '소인수분해', '수직선',
        '음의정수', '유리근', '무리근', '절댓값', '몫', '나머지'
    ],
    '지수와 로그' => [
        '지수법칙', '로그법칙', '지수함수', '로그함수', '밑변환법칙', '자연로그', '상용로그',
        '로그의 성질', '지수의 성질', '로그미분', '지수방정식', '로그방정식', '지수적 증가',
        '지수적 감소', '대수적 증가', '대수적 감소', '지수와 로그의 관계', '지수와 등비수열',
        '로그와 등차수열', '역로그함수', '역지수함수', '지수치환', '로그치환', '반감기',
        '배가 시간', '지수그래프', '로그그래프', '변환법칙', '상수승', '비례관계'
    ],
    '수열' => [
        '등차수열', '등비수열', '피보나치수열', '산술수열', '기하수열', '수열의 합', '수열의 일반항',
        '등차중항', '등비중항', '반복수열', '계차수열', '등차수열의 합', '등비수열의 합',
        '수열의 극한', '수열의 성질', '수열의 재귀식', '수열의 귀납적 정의', '수열의 점화식',
        '수열과 함수', '수열의 그래프', '수열의 수렴', '수열의 발산', '급수', '무한급수',
        '등비급수', '조화수열', '계차수열', '이중수열', '무한수열', '유한수열'
    ],
    '식의 계산' => [
        '다항식', '인수분해', '항등식', '유리식', '무리식', '거듭제곱식', '항', '단항식',
        '다항방정식', '복소다항식', '다항식의 나눗셈', '다항식의 곱셈', '다항식의 인수',
        '최대공약다항식', '최소공배다항식', '동류항', '상수항', '계수', '차수', '근의 분리',
        '근의 성질', '인수정리', '나머지정리', '조립제법', '합동식', '축소식', '표준형',
        '다항식의 그래프', '근과 계수의 관계', '이차식', '삼차식'
    ],
    '방정식' => [
        '일차방정식', '이차방정식', '삼차방정식', '고차방정식', '복소근', '실근', '허근', '근의 판별식',
        '방정식의 성질', '동차방정식', '범위방정식', '치환방정식', '지수방정식', '로그방정식',
        '매개변수방정식', '삼각방정식', '연립방정식', '일차연립방정식', '이차연립방정식',
        '분수방정식', '무리방정식', '근과 계수의 관계', '대칭근', '불완전방정식', '방정식의 해',
        '부정방정식', '정수해', '소수해', '계수변환', '비례근'
    ],
    '부등식' => [
        '절대값부등식', '일차부등식', '이차부등식', '복합부등식', '연립부등식', '치환부등식', '범위부등식',
        '지수부등식', '로그부등식', '삼각부등식', '비례부등식', '평균부등식', '코시부등식', '상관부등식',
        '절댓값과 부등식', '부등식의 해', '부등식의 영역', '부등식의 그래프', '부등식의 계산',
        '부등식의 성질', '역삼각부등식', '사잇값정리', '함수의 부등식', '최댓값', '최솟값',
        '증감부등식', '단조부등식', '계산부등식', '지수적부등식', '산술적부등식', '대수적부등식'
    ],
    '함수' => [
        '정의역', '치역', '역함수', '항등함수', '상수함수', '다항함수', '유리함수', '무리함수', '초월함수',
        '지수함수', '로그함수', '삼각함수', '주기함수', '분할함수', '함수의 그래프', '함수의 극한',
        '함수의 연속성', '함수의 미분', '함수의 적분', '함수의 극대', '함수의 극소', '함수의 성질',
        '함수의 변환', '함수의 증가', '함수의 감소', '함수의 최대', '함수의 최소', '함수의 평행이동',
        '함수의 대칭', '함수의 주기'
    ],
    '미분' => [
        '도함수', '접선', '미분계수', '미분법', '고차미분', '함수의 기울기', '연속과 미분',
        '평균값정리', '롤의 정리', '테일러 전개', '맥클로린 전개', '미분가능성', '미분불가능점',
        '함수의 증가', '함수의 감소', '함수의 극대', '함수의 극소', '함수의 변곡점', '도함수의 그래프',
        '미분과 급수', '미분적분학의 기본정리', '미분과 최적화', '속도와 가속도', '곡선의 접선방정식',
        '곡률', '함수의 근사', '부분미분', '편미분', '쌍곡선함수의 미분', '암시적 미분', '연쇄법칙'
    ],
    '적분' => [
        '정적분', '부정적분', '적분법', '적분의 기본정리', '치환적분', '부분적분', '적분과 넓이',
        '적분과 부피', '적분과 길이', '적분과 면적', '적분의 성질', '함수의 적분', '적분과 극한',
        '수치적분', '급수와 적분', '적분과 평균값정리', '적분과 확률', '적분과 기하학', '미분방정식',
        '정적분의 근사', '특정구간의 적분', '복합적분', '적분의 연산', '적분의 대칭성', '다중적분',
        '적분의 응용', '적분과 경계값', '매개변수 적분', '적분과 변환', '적분 그래프'
    ],
    '평면도형' => [
        '삼각형', '사각형', '원', '타원', '정사각형', '직사각형', '마름모', '평행사변형', '사다리꼴',
        '정삼각형', '직각삼각형', '둔각삼각형', '예각삼각형', '원의 중심', '원의 반지름', '원의 둘레',
        '원의 면적', '타원의 초점', '타원의 반장축', '타원의 면적', '다각형', '정다각형', '내각',
        '외각', '내심', '외심', '수심', '중심각', '대각선', '변의 길이', '평면좌표'
    ],
    '공간좌표' => [
        '공간벡터', '좌표평면', '좌표축', '3차원 공간', '공간의 방향', '점의 좌표', '선분의 중점',
        '공간의 거리', '평면의 방정식', '선분의 방정식', '공간 내의 직선', '공간의 평행',
        '공간의 수직', '평행선', '직선의 교점', '평면의 교점', '공간의 회전', '공간의 대칭',
        '구의 방정식', '원뿔의 방정식', '원통의 방정식', '입체도형', '좌표변환', '공간의 기하학',
        '3차원 도형', '입체의 부피', '좌표공간의 표기법', '공간과 벡터', '벡터의 연산', '공간에서의 비례'
    ],
    '벡터' => [
        '내적', '외적', '벡터의 성질', '벡터의 연산', '벡터의 길이', '단위벡터', '벡터의 합성',
        '벡터의 분해', '벡터의 방향', '벡터의 투영', '벡터의 성분', '벡터의 평행', '벡터의 수직',
        '벡터의 회전', '벡터와 점', '벡터와 선분', '벡터와 평면', '평면벡터', '공간벡터',
        '벡터와 좌표', '벡터 방정식', '벡터와 거리', '벡터의 기하학적 의미', '벡터와 속도',
        '벡터와 가속도', '벡터의 내적 성질', '벡터의 외적 성질', '벡터와 곡선', '벡터의 단위변환'
    ],
    '경우의 수와 확률' => [
        '순열', '조합', '확률', '기대값', '이항정리', '이항분포', '조건부확률', '독립사건',
        '종속사건', '확률분포', '평균값', '분산', '표준편차', '확률변수', '기댓값 계산',
        '확률공간', '샘플링', '확률밀도함수', '확률질량함수', '확률과 그래프', '확률모델',
        '복합사건', '순열과 반복', '조합과 중복', '확률의 합', '확률의 곱', '확률의 성질',
        '확률의 극한', '표본공간', '확률추정', '랜덤변수'
    ],
    '통계' => [
        '표본', '모평균', '분산', '표준편차', '중앙값', '최빈값', '자료의 분포', '자료의 정리',
        '도수분포표', '상관관계', '회귀분석', '확률분포', '정규분포', '이항분포', '포아송분포',
        '추정치', '신뢰구간', '가설검정', '오차범위', '산포도', '박스플롯', '히스토그램', '평균',
        '표준화값', '자료의 중심경향', '자료의 퍼짐정도', '상관계수', '추정과 예측', '분석결과의 시각화'
    ]
];

// 한글 단어 추출
function extractKoreanWords($text) {
    $text = preg_replace("/[^가-힣a-zA-Z\s]/u", " ", $text);
    $words = preg_split('/\s+/', trim($text));
    $words = array_filter($words, function($w) {
        return preg_match("/[가-힣]/u", $w);
    });
    return array_values($words);
}

// 수학 용어 식별
function extractMathTerms($words, $mathTerms) {
    $allMathWords = [];
    foreach($mathTerms as $category => $terms) {
        $allMathWords = array_merge($allMathWords, $terms);
    }
    $allMathWords = array_unique($allMathWords);
    return array_intersect($words, $allMathWords);
}

// 컨텐츠 정보 가져오기
function getContentInfo($contentstype, $contentsid, $DB) {
    $title = '제목없음';
    $message = '';
    $imgSrc = '<img src="https://via.placeholder.com/250x150?text=No+Image" style="width:100%;height:auto;">';

    if ($contentstype == 1) {
        $record = $DB->get_record_sql("SELECT title, maintext, pageicontent FROM mdl_icontent_pages WHERE id = ?", [$contentsid]);
        if ($record) {
            $title = $record->title ?? '제목없음';
            $message = $record->maintext ?? '';
        }
        $ctext = $record->pageicontent ?? '';
        $htmlDom = new DOMDocument;
        @$htmlDom->loadHTML($ctext);
        $imageTags = $htmlDom->getElementsByTagName('img');
        foreach($imageTags as $imageTag) {
            $src = $imageTag->getAttribute('src');
            $src = str_replace(' ', '%20', $src);
            if(strpos($src, 'MATRIX')!== false || strpos($src, 'MATH')!== false || strpos($src, 'imgur')!== false) {
                $imgSrc='<img loading="lazy" src="'.$src.'" style="width:100%;height:auto;">';
                break;
            }
        }

    } elseif ($contentstype == 2) {
        $record = $DB->get_record_sql("SELECT name, questiontext, mathexpression, ans1 FROM mdl_question WHERE id = ?", [$contentsid]);
        if ($record) {
            $title = $record->name ?? '제목없음';
            $message = ($record->mathexpression ?? '') . ($record->ans1 ?? '');
        }
        $htmlDom = new DOMDocument;
        @$htmlDom->loadHTML($record->questiontext);
        $imageTags = $htmlDom->getElementsByTagName('img');
        foreach($imageTags as $imageTag) {
            $src = $imageTag->getAttribute('src');
            $src = str_replace(' ', '%20', $src);
            if(strpos($src, 'MATRIX/MATH')!== false || strpos($src, 'HintIMG')!== false) {
                $imgSrc='<img loading="lazy" src="'.$src.'" style="width:100%;height:auto;">';
                break;
            }
        }

    }

    return ['title' => $title, 'message' => $message, 'imgsrc' => $imgSrc];
}

$currentContent = getContentInfo($thiscontentstype, $thiscontentsid, $DB);
$thiscntinfo = $currentContent['message'] ?? '';
$contentstitle = $currentContent['title'] ?? '제목없음';

// 최근 필기(핸드라이팅) 기록
$handwriting = $DB->get_records_sql(
    "SELECT * FROM mdl_abessi_messages WHERE userid = ? AND active = 1 ORDER BY timemodified DESC LIMIT 300",
    [$studentid]
);
$result = json_decode(json_encode($handwriting), true);

// 필터링 함수 (viewfilter 사용)
function filterContentsByType($allContents, $viewfilter) {
    if ($viewfilter == 1) {
        // 개념컨텐츠(1)
        return array_filter($allContents, function($c){
            return $c['contentstype'] == 1;
        });
    } elseif ($viewfilter == 2) {
        // 문항(2)
        return array_filter($allContents, function($c){
            return $c['contentstype'] == 2;
        });
    } else {
        // 전체보기(0)
        return $allContents;
    }
}

function buildDocumentList($currentContent, $allContents, $DB) {
    $documents = [];
    $currentWords = extractKoreanWords($currentContent['message']);
    $documents[] = [
        'words' => $currentWords,
        'contentstype' => null,
        'contentsid' => null,
        'wboardid' => null,
        'timemodified' => time(), // 현재 컨텐츠의 시간 추가
        'title' => $currentContent['title'],
        'message' => $currentContent['message'],
        'imgsrc' => $currentContent['imgsrc']
    ];

    foreach($allContents as $content) {
        $ctype = $content['contentstype'] ?? '';
        $cid = $content['contentsid'] ?? '';
        $wboardid = $content['wboardid'] ?? '';
        $timemodified = $content['timemodified'] ?? 0; // timemodified 값 가져오기
        $info = getContentInfo($ctype, $cid, $DB);
        $words = extractKoreanWords($info['message']);
        $documents[] = [
            'words' => $words,
            'contentstype' => $ctype,
            'contentsid' => $cid,
            'wboardid' => $wboardid,
            'timemodified' => $timemodified, // 추가
            'title' => $info['title'],
            'message' => $info['message'],
            'imgsrc' => $info['imgsrc']
        ];
    }

    return $documents;
}

function computeTFIDF($documents) {
    $docCount = count($documents);
    $df = [];
    $docTermFreqs = [];

    foreach($documents as $di => $doc) {
        $freq = array_count_values($doc['words']);
        $docTermFreqs[$di] = $freq;
        foreach($freq as $term => $count) {
            if(!isset($df[$term])) {
                $df[$term] = 0;
            }
            $df[$term] += 1;
        }
    }

    $tfidf = [];
    foreach($docTermFreqs as $di => $freqs) {
        $maxFreq = max($freqs) ?: 1;
        $tfidfVector = [];
        foreach($freqs as $term => $count) {
            $tf = $count / $maxFreq;
            $idf = log(($docCount+1)/($df[$term]+1)) + 1;
            $tfidfVector[$term] = $tf * $idf;
        }
        $tfidf[$di] = $tfidfVector;
    }

    return $tfidf;
}

function getTopKeywords($tfidfForCurrentDoc, $topN=10) {
    arsort($tfidfForCurrentDoc);
    return array_slice($tfidfForCurrentDoc, 0, $topN, true);
}

function calculateWeightedSimilarity($currentVector, $otherVector, $topKeywords, $currentWords, $otherWords, $mathTerms) {
    $allTerms = array_unique(array_merge(array_keys($currentVector), array_keys($otherVector)));

    $currentMathTerms = extractMathTerms($currentWords, $mathTerms);
    $otherMathTerms = extractMathTerms($otherWords, $mathTerms);

    $dot = 0; $mag1 = 0; $mag2 = 0;
    foreach($allTerms as $t) {
        $w1 = $currentVector[$t] ?? 0;
        $w2 = $otherVector[$t] ?? 0;

        if(isset($topKeywords[$t])) {
            $w1 *= 1.2;
            $w2 *= 1.2;
        }

        if(in_array($t, $currentMathTerms) || in_array($t, $otherMathTerms)) {
            $w1 *= 1.5;
            $w2 *= 1.5;
        }

        $dot += $w1*$w2;
        $mag1 += $w1*$w1;
        $mag2 += $w2*$w2;
    }

    $mag1 = sqrt($mag1);
    $mag2 = sqrt($mag2);
    if($mag1==0 || $mag2==0) return 0;
    return $dot/($mag1*$mag2);
}


function formatElapsedTime($timemodified) {
    $elapsed = time() - $timemodified;
    if ($elapsed < 60) {
        return $elapsed . '초 전';
    } elseif ($elapsed < 3600) {
        return floor($elapsed / 60) . '분 전';
    } elseif ($elapsed < 86400) {
        return floor($elapsed / 3600) . '시간 전';
    } else {
        return floor($elapsed / 86400) . '일 전';
    }
}


function findSimilarContents($currentContent, $allContents, $DB, $mathTerms, $thiscontentsid, $thiscontentstype, $limit = 6) {
    $documents = buildDocumentList($currentContent, $allContents, $DB);
    $tfidf = computeTFIDF($documents);

    $currentVector = $tfidf[0];
    $topKeywords = getTopKeywords($currentVector, 10);
    $currentWords = $documents[0]['words'];

    $similarities = [];
    for($i=1; $i<count($documents); $i++) {
        $sim = calculateWeightedSimilarity(
            $currentVector,
            $tfidf[$i],
            $topKeywords,
            $currentWords,
            $documents[$i]['words'],
            $mathTerms
        );

        $similarities[] = [
            'contentstype' => $documents[$i]['contentstype'],
            'contentsid' => $documents[$i]['contentsid'],
            'similarity' => $sim,
            'contentstitle' => $documents[$i]['title'],
            'contentmessage' => $documents[$i]['message'],
            'wboardid' => $documents[$i]['wboardid'],
            'imgsrc' => $documents[$i]['imgsrc'],
            'timemodified' => $documents[$i]['timemodified']
        ];
    }

    // 자기 자신 컨텐츠 제거
    $similarities = array_filter($similarities, function($c) use ($thiscontentsid, $thiscontentstype) {
        return !($c['contentsid'] == $thiscontentsid && $c['contentstype'] == $thiscontentstype);
    });

    usort($similarities, function($a, $b){
        return $b['similarity'] <=> $a['similarity'];
    });

    return array_slice($similarities, 0, $limit);
}

// 필터 적용
$filteredResult = filterContentsByType($result, $viewfilter);
$similarContents = findSimilarContents($currentContent, $filteredResult, $DB, $mathTerms, $thiscontentsid, $thiscontentstype, 6);

// 70% 이상인 컨텐츠 추출
$highSim = [];
$lowSim = [];
foreach($similarContents as $c) {
    if($c['similarity'] >= 0.7) {
        $highSim[] = $c;
    } else {
        $lowSim[] = $c;
    }
}
// 낮은 유사도에서 상위 3개만
$lowSim = array_slice($lowSim, 0, 3);
?>
<!DOCTYPE html>
<html>
<head>
    <title>컨텐츠 유사도 분석</title>
    <style>
        .content-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .filter-buttons {
            margin-bottom: 20px;
        }
        .filter-buttons a {
            margin-right: 10px;
            text-decoration: none;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            color: #333;
            background-color: #f7f7f7;
        }
        .filter-buttons a:hover {
            background-color: #e7e7e7;
        }

        .current-content {
            background-color: #f5f5f5;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
            text-align: center;
        }
        .row-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .row-container.one-row {
            justify-content: flex-start;
        }
        .row-container.two-row {
            justify-content: flex-start;
        }

        .similar-content-card {
            position: relative;
            background-color: #fff;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: visible;
            width: 250px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .similar-content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .similar-content-card.highlight {
            border-color: #ff9800;
            background-color: #fff3e0;
        }

        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 0.9em;
            color: #555;
        }

        .similarity-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #007bff;
            color: #fff;
            border-radius: 3px;
            font-size: 0.9em;
        }

        .tooltip-content {
            display: none;
            position: absolute;
            top: -10px;
            right: -10px;
            background: #fff;
            border: 1px solid #ccc;
            z-index: 99999;
            padding: 10px;
            border-radius: 5px;
            width: 200px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: left;
        }
        .tooltip-content {
            overflow-y: auto; /* 긴 텍스트도 스크롤 가능 */
            max-height: 200px; 
        }
        .similar-content-card:hover .tooltip-content {
            display: block;
        }

        .card-content {
            flex-grow: 1;
        }
        .card-footer {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="content-container">
        <div class="filter-buttons">
            <a href="?contentsid=<?php echo urlencode($thiscontentsid); ?>&contentstype=<?php echo urlencode($thiscontentstype); ?>&viewfilter=0&userid=<?php echo urlencode($studentid); ?>">전체보기</a>
            <a href="?contentsid=<?php echo urlencode($thiscontentsid); ?>&contentstype=<?php echo urlencode($thiscontentstype); ?>&viewfilter=1&userid=<?php echo urlencode($studentid); ?>">개념컨텐츠</a>
            <a href="?contentsid=<?php echo urlencode($thiscontentsid); ?>&contentstype=<?php echo urlencode($thiscontentstype); ?>&viewfilter=2&userid=<?php echo urlencode($studentid); ?>">문제풀이</a>

        </div>

        <div class="current-content">
            <h2>현재 컨텐츠: <?php echo htmlspecialchars($contentstitle); ?></h2>
            <?php echo $currentContent['imgsrc']; ?>
        </div>

        <?php if(!empty($highSim)): ?>
        <h3>유사도 높은 컨텐츠 (70% 이상)</h3>
        <div class="row-container one-row">
        <?php foreach($highSim as $content): 
            $similarityPercent = round($content['similarity'] * 100, 1);
            $elapsedTime = isset($content['timemodified']) ? formatElapsedTime($content['timemodified']) : '시간 정보 없음';
        ?>
                <div class="similar-content-card highlight" onclick="location.href='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=<?php echo urlencode($content['wboardid']); ?>'">
                    <div class="card-content">
                        <h4><?php echo htmlspecialchars($content['contentstitle']); ?></h4>
                        <?php echo $content['imgsrc']; ?>
                    </div>
                    <div class="card-footer">
                        <div class="info-bar">
                            <span class="similarity-badge">유사도: <?php echo $similarityPercent; ?>%</span>
                            <span class="time-elapsed"><?php echo htmlspecialchars($elapsedTime); ?></span>
                        </div>
                    </div>
                    <div class="tooltip-content">
                        <?php echo htmlspecialchars($content['contentmessage']); ?>
                    </div>
                </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($lowSim)): ?>
        <h3>다른 유사 컨텐츠</h3>
        <div class="row-container two-row">
            <?php foreach($lowSim as $content):
                $similarityPercent = round($content['similarity'] * 100, 1);
                $elapsedTime = formatElapsedTime($content['timemodified']); 
            ?>
                <div class="similar-content-card" onclick="location.href='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=<?php echo urlencode($content['wboardid']); ?>'">
                    <div class="card-content">
                        <h4><?php echo htmlspecialchars($content['contentstitle']); ?></h4>
                        <?php echo $content['imgsrc']; ?>
                    </div>
                    <div class="card-footer">
                        <div class="info-bar">
                            <span class="similarity-badge">유사도: <?php echo $similarityPercent; ?>%</span>
                            <span class="time-elapsed"><?php echo htmlspecialchars($elapsedTime); ?></span>
                        </div>
                    </div>
                    <div class="tooltip-content">
                        <?php echo htmlspecialchars($content['contentmessage']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
