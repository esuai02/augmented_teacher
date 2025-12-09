<?php
/**************************************************** 
 * analysis_daily.php 
 *
 * 화이트보드의 필기 stroke 데이터를 DB에서 읽고,
 * 획 간 시간 간격을 분석해 시각화. 
 *
 * 수정사항:
 *  1) 탭(Tab) -> 아코디언(Accordion) 변경
 *  2) 차트 유형: 모두 bar(막대)로 통일 (직사각형 강조)
 *  3) 클릭한 막대만 다른 색(빨간색)으로 표시
 *  4) 화이트보드별 풀이 길이(최대 획 수)도 그래프로 표현
 *  5) 모든 차트 높이 최대치를 화면의 50%(50vh)로 고정, maintainAspectRatio: false
 *  6) 아코디언 자동접기 금지 (다른 패널 열려 있어도 유지)
 *  7) 풀이 길이 그래프 바 클릭 시 새 탭으로 화이트보드 페이지 열기
 *  8) 추가: 화이트보드별 평균 획간 시간(avgGap) 계산 → 풀이 길이 그래프 툴팁에 표시
 *  9) 추가: 히스토그램 라벨 "시간에 따른 획간 시간차"로 명시
 ****************************************************/

// Moodle 설정 파일
include_once("/home/moodle/public_html/moodle/config.php");
include_once("/home/moodle/public_html/moodle/configwhiteboard.php");

// DB 연결
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

global $DB, $USER;

// GET 파라미터로 studentid(또는 userid)를 받되, 없으면 현재 유저 ID로 대체
$ndays = $_GET["ndays"];
if($ndays == NULL) $ndays = 30;

$studentid = $_GET["userid"];
if ($studentid == NULL) {
    $studentid = $USER->id;
}

// 현재 타임스탬프
$timecreated = time();

// Moodle DB에서 사용자 정보 조회
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid'");
$stdname  = $thisuser->firstname . $thisuser->lastname;

// 최근 $ndays일 이내 기간
$monthsago3 = $timecreated - 86400*$ndays; 

// 현재 유저의 role(사용자 정의 필드 fieldid=22)
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->role;

// 쿼리: $ndays일 이내 boarddb 레코드 전부(특정 유저 기준)
$sql = "SELECT * FROM boarddb 
        WHERE authorid='$studentid' 
        AND timecreated > '$monthsago3'
        ORDER BY id ASC";
$rs = mysqli_query($conn, $sql);

$tstroke_prev = 0;           // 이전 획의 타임스탬프
$dailyData = array();        // 일자별 -> 화이트보드별 -> 획 간격
$distributionData = array(); // 화이트보드별 -> 0~9초 구간 히스토그램
$whiteboardStrokes = array();// 화이트보드별 generate_id 최대값(풀이 길이)

// 추가: 화이트보드별 전체 획간시간 합, 개수
$wbGapTotals = array();
$wbGapCounts = array();

// 레코드 반복
while($info = mysqli_fetch_array($rs)) {
    $currentTime = $info['timecreated'];
    $shapedata   = $info['shape_data']; 
    $wboardid    = $info['encryption_id'];
    $generateid  = $info['generate_id'];

    $strokegap = $currentTime - $tstroke_prev;
    $tstroke_prev = $currentTime;

    // 화이트보드별 최대 획 수 체크
    if (!isset($whiteboardStrokes[$wboardid])) {
        $whiteboardStrokes[$wboardid] = 0;
    }
    if ($generateid > $whiteboardStrokes[$wboardid]) {
        $whiteboardStrokes[$wboardid] = $generateid;
    }

    // 12시간(43200초) 미만인 간격만 분석
    if($strokegap >= 0 && $strokegap < 43200) {
        // 화이트보드별 전체 획간시간도 누적
        if (!isset($wbGapTotals[$wboardid])) {
            $wbGapTotals[$wboardid] = 0;
            $wbGapCounts[$wboardid] = 0;
        }
        $wbGapTotals[$wboardid] += $strokegap;
        $wbGapCounts[$wboardid]++;

        // 일자키
        $dayKey = date("Y-m-d", $currentTime);
        if(!isset($dailyData[$dayKey])) {
            $dailyData[$dayKey] = array();
        }
        if(!isset($dailyData[$dayKey][$wboardid])) {
            $dailyData[$dayKey][$wboardid] = array();
        }

        // 10초 미만만 “집중 간격”으로 취급하여 저장
        if($strokegap < 10) {
            $dailyData[$dayKey][$wboardid][] = $strokegap;

            // 히스토그램 (0~9초)
            if(!isset($distributionData[$wboardid])) {
                $distributionData[$wboardid] = array_fill(0, 10, 0);
            }
            $binIndex = (int)floor($strokegap);
            if($binIndex >= 0 && $binIndex < 10) {
                $distributionData[$wboardid][$binIndex]++;
            }
        }
    }
}
mysqli_close($conn);

// 화이트보드별 평균 획간 시간(전체)
$whiteboardAvgGaps = array();
foreach($wbGapTotals as $wbid => $totalGap) {
    $count = $wbGapCounts[$wbid];
    if ($count > 0) {
        // 소수점 2자리로 반올림
        $whiteboardAvgGaps[$wbid] = round($totalGap / $count, 2);
    } else {
        $whiteboardAvgGaps[$wbid] = 0;
    }
}

// 일자별 평균 계산 구조화
$analysisDaily = array();
foreach($dailyData as $dayKey => $wbArray) {
    $dayTotalGap = 0;
    $dayCount    = 0;
    $whiteboardList = array();

    foreach($wbArray as $wbId => $gaps) {
        $wbSum  = array_sum($gaps);
        $wbCnt  = count($gaps);
        $wbAvg  = ($wbCnt > 0) ? ($wbSum / $wbCnt) : 0;

        $dayTotalGap += $wbSum;
        $dayCount    += $wbCnt;

        $whiteboardList[] = array(
            "id"     => $wbId,
            "avgGap" => $wbAvg
        );
    }

    $dailyAvgGap = ($dayCount > 0) ? ($dayTotalGap / $dayCount) : 0;

    $analysisDaily[] = array(
        "date"        => $dayKey,
        "avgGap"      => $dailyAvgGap,
        "whiteboards" => $whiteboardList
    );
}

// 분석 결과를 하나의 배열로 정리
$analysisResult = array(
    "dailyData"              => $analysisDaily,
    "whiteboardDistribution" => $distributionData,
    "whiteboardStrokes"      => $whiteboardStrokes,
    "whiteboardAvgGaps"      => $whiteboardAvgGaps  // ★ 추가된 부분
);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Stroke Time Analysis - Accordion Version</title>

    <!-- TinyMCE / MathJax -->
    <script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script type="text/x-mathjax-config">
    MathJax.Hub.Config({
      tex2jax: {
        inlineMath:[ ["$","$"], ["\\[","\\]"] ]
      }
    });
    </script>
    <script type="text/javascript" async
      src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML">
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- jQuery / Bootstrap / Chart.js -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    .table-wrapper {
        position: relative;
        height: 100%; 
        overflow: auto;
    }
    .table-wrapper thead {
        position: sticky;
        top: 0;
        background-color: #BCD5FF;
        z-index: 1;
    }
    /* 모든 차트의 최대 높이를 화면의 50%로 고정 */
    canvas {
        width: 80%;
        max-height: 50vh; /* 화면 높이의 50% */
    }
    </style>
</head>
<body>

<?php
echo "<h2>학생({$stdname})의 화이트보드 획 분석</h2>";
echo "<p>최근 {$ndays}일 간 자료를 이용해 획 간격을 기반으로 집중도(기민성)을 추정합니다.</p>";
?>

<!-- PHP 변수를 JS에서도 쓰기 위해 전달 -->
<script>
const phpStudentID = "<?php echo $studentid; ?>";
</script>

<!-- 분석 데이터 자바스크립트 변수로 전달 -->
<script>
var analysisData = <?php echo json_encode($analysisResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;
console.log("analysisData", analysisData);

/**
 * 모든 차트의 y축 '최대값'을 일별 평균 중 가장 큰 값으로 통일.
 * (빈 데이터 방지용 기본값 10)
 */
(function(){
    const dailyData = analysisData.dailyData;
    if(!dailyData || dailyData.length === 0) {
        window.globalYMax = 10; 
        return;
    }
    const avgGaps = dailyData.map(item => item.avgGap);
    const dailyMax = Math.max(...avgGaps);
    window.globalYMax = (dailyMax < 1) ? 1 : dailyMax;
})();

/** 
 * 막대 그래프의 "기본색" 배열 생성 + 클릭된 막대 인덱스만 'red'.
 */
function createBarColors(count, defaultColor, selectedIndex=null) {
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push(i === selectedIndex ? 'red' : defaultColor);
    }
    return colors;
}
</script>

<!-- 아코디언 구조 (Bootstrap) -->
<div class="panel-group" id="chartAccordion" role="tablist" aria-multiselectable="true">
    <!-- (1) 일별 평균 패널 -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingDaily">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse" 
                   href="#collapseDaily" aria-expanded="true" aria-controls="collapseDaily">
                   1) 일별 평균 차트
                </a>
            </h4>
        </div>
        <div id="collapseDaily" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingDaily">
            <div class="panel-body">
                <canvas id="dailyAvgChart"></canvas>
            </div>
        </div>
    </div>

    <!-- (2) 화이트보드 상세 패널 -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingDetail">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse"
                   href="#collapseDetail" aria-expanded="false" aria-controls="collapseDetail">
                   2) 화이트보드 상세 차트
                </a>
            </h4>
        </div>
        <div id="collapseDetail" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingDetail">
            <div class="panel-body">
                <canvas id="whiteboardDetailChart"></canvas>
            </div>
        </div>
    </div>

    <!-- (3) 히스토그램 패널 -->
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingHistogram">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse"
                   href="#collapseHistogram" aria-expanded="false" aria-controls="collapseHistogram">
                   3) 히스토그램 차트
                </a>
            </h4>
        </div>
        <div id="collapseHistogram" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingHistogram">
            <div class="panel-body">
                <canvas id="histogramChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 화이트보드별 풀이 길이(최대 획 수) 테이블 & 그래프 -->
<div style="margin-top:40px;">
    <h3>화이트보드별 풀이 길이(최대 획 수)</h3>
    <div class="table-wrapper" style="max-width:700px;">
      <table style="display:none;" border="1" cellpadding="5" cellspacing="0">
          <thead>
              <tr style="background-color:#BCD5FF;">
                  <th>화이트보드 ID</th>
                  <th>generate_id 최대값</th>
              </tr>
          </thead>
          <tbody id="strokeTableBody"><!-- JS에서 채움 --></tbody>
      </table>
    </div>
    <canvas id="solveLengthChart" style="margin-top:20px;"></canvas>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    /** ============ (1) 일별 평균 차트 ============ **/
    const dailyData = analysisData.dailyData;
    const dailyCtx = document.getElementById('dailyAvgChart').getContext('2d');
    const dailyLabels = dailyData.map(item => item.date);
    const dailyValues = dailyData.map(item => item.avgGap);

    let dailySelectedIndex = null; // 클릭한 막대 index
    const dailyChart = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: '일별 평균 획 간격(초)',
                data: dailyValues,
                backgroundColor: createBarColors(dailyValues.length, 'rgba(0,0,255,0.3)', null)
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: window.globalYMax
                }
            },
            onClick: (evt, array) => {
                if (array.length > 0) {
                    // 클릭한 막대 색상 업데이트
                    dailySelectedIndex = array[0].index;
                    dailyChart.data.datasets[0].backgroundColor = 
                        createBarColors(dailyLabels.length, 'rgba(0,0,255,0.3)', dailySelectedIndex);
                    dailyChart.update();

                    // (아코디언) 현재 패널은 그대로 두고, 상세 패널 펼치기
                    $('#collapseDetail').collapse('show');

                    // 클릭한 일자에 해당하는 화이트보드 목록
                    const clickedDate = dailyLabels[dailySelectedIndex];
                    const whiteboards = dailyData[dailySelectedIndex].whiteboards;
                    showWhiteboardDetail(clickedDate, whiteboards);
                }
            }
        }
    });

    /** ============ (2) 화이트보드 상세 차트 ============ **/
    let wbChart = null;
    let wbSelectedIndex = null;
    function showWhiteboardDetail(clickedDate, whiteboards) {
        const ctx2 = document.getElementById('whiteboardDetailChart').getContext('2d');
        if (wbChart) wbChart.destroy();

        wbSelectedIndex = null;

        const wbLabels = whiteboards.map(wb => wb.id);
        const wbValues = whiteboards.map(wb => wb.avgGap);

        wbChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: wbLabels,
                datasets: [{
                    label: clickedDate + ' 화이트보드별 평균 간격(초)',
                    data: wbValues,
                    backgroundColor: createBarColors(wbValues.length, 'orange')
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: window.globalYMax
                    }
                },
                onClick: (evt, array) => {
                    if(array.length > 0) {
                        wbSelectedIndex = array[0].index;
                        // 클릭 막대만 'red'
                        wbChart.data.datasets[0].backgroundColor = 
                            createBarColors(wbLabels.length, 'orange', wbSelectedIndex);
                        wbChart.update();

                        // 히스토그램 패널 펼치기
                        $('#collapseHistogram').collapse('show');

                        const wbId = wbLabels[wbSelectedIndex];
                        showHistogram(wbId);
                    }
                }
            }
        });
    }
    window.showWhiteboardDetail = showWhiteboardDetail; 

    /** ============ (3) 히스토그램 차트 ============ **/
    let histChart = null;
    let histSelectedIndex = null;
    function showHistogram(whiteboardId) {
        const distribution = analysisData.whiteboardDistribution[whiteboardId];
        if(!distribution) {
            alert("해당 화이트보드의 분포 데이터가 없습니다.");
            return;
        }
        const ctx3 = document.getElementById('histogramChart').getContext('2d');
        if(histChart) histChart.destroy();

        histSelectedIndex = null;

        // 0~9초 레이블
        const binLabels = [];
        for(let i=0; i<10; i++){
            binLabels.push(`${i}~${i+1}초`);
        }

        histChart = new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: binLabels,
                datasets: [{
                    // 라벨 수정: "시간에 따른 획간 시간차 분포"
                    label: `화이트보드(${whiteboardId}) 시간에 따른 획간 시간차 분포`,
                    data: distribution,
                    backgroundColor: createBarColors(distribution.length, 'green')
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: window.globalYMax
                    }
                },
                onClick: (evt, array) => {
                    if(array.length > 0) {
                        histSelectedIndex = array[0].index;
                        histChart.data.datasets[0].backgroundColor = 
                            createBarColors(distribution.length, 'green', histSelectedIndex);
                        histChart.update();
                        // 필요 시 추가 액션
                    }
                }
            }
        });
    }
    window.showHistogram = showHistogram;

    /** ============ (4) 화이트보드별 풀이 길이(최대 획 수) 테이블 & 그래프 ============ **/
    const wbStrokesData  = analysisData.whiteboardStrokes; 
    const wbStrokeLabels = Object.keys(wbStrokesData); 
    const wbStrokeValues = Object.values(wbStrokesData);

    // 테이블 채우기 (display:none)
    const strokeTbody = document.getElementById('strokeTableBody');
    wbStrokeLabels.forEach((boardId, idx) => {
        const row = document.createElement('tr');
        const cellId = document.createElement('td');
        const cellVal = document.createElement('td');
        cellId.textContent = boardId;
        cellVal.textContent = wbStrokeValues[idx];
        row.appendChild(cellId);
        row.appendChild(cellVal);
        strokeTbody.appendChild(row);
    });

    // 추가: 화이트보드별 평균 획간 시간
    var wbAvgGaps = analysisData.whiteboardAvgGaps;

    // 풀이 길이(최대 획 수) 그래프
    let solveSelectedIndex = null;
    const solveCtx = document.getElementById('solveLengthChart').getContext('2d');
    const solveLengthChart = new Chart(solveCtx, {
        type: 'bar',
        data: {
            labels: wbStrokeLabels,
            datasets: [{
                label: '화이트보드별 풀이 길이(최대 획 수)',
                data: wbStrokeValues,
                backgroundColor: createBarColors(wbStrokeValues.length, 'purple')
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: window.globalYMax
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            // 예: "화이트보드별 풀이 길이(최대 획 수): 326획 / 평균간격 3.45초"
                            let baseLabel = context.dataset.label || '';
                            let strokes = context.parsed.y;   // 막대 높이(=풀이 길이)
                            let wbId = context.label;         // x축(화이트보드ID)
                            let avgGap = (wbAvgGaps[wbId] !== undefined) 
                                         ? wbAvgGaps[wbId] : 0;
                            return baseLabel
                                + " = " + strokes + "획"
                                + " / 평균간격 " + avgGap + "초";
                        }
                    }
                }
            },
            onClick: (evt, array) => {
                if(array.length>0){
                    solveSelectedIndex = array[0].index;
                    // 클릭 막대 색 변경
                    solveLengthChart.data.datasets[0].backgroundColor =
                        createBarColors(wbStrokeValues.length, 'purple', solveSelectedIndex);
                    solveLengthChart.update();

                    // 해당 화이트보드 페이지 새 탭으로 열기
                    const wbId = wbStrokeLabels[solveSelectedIndex];
                    const url = 
                      "https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid="
                      + phpStudentID
                      + "&mode=2&wboardid="
                      + encodeURIComponent(wbId);
                    window.open(url, "_blank");
                }
            }
        }
    });
});
</script>
</body>
</html>
