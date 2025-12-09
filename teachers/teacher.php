<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$teacherid' AND fieldid='22'"); 
$role = $userrole->role;

if ($userrole == 'teacher') {
    // 예시로만 사용 (실제 환경에서는 DB에서 가져오기)
    $username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid'");
    $tsymbol = substr($username->firstname, 0, 3);

    $mystudents = $DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname NOT LIKE '%$tsymbol%'");
    $result = json_decode(json_encode($mystudents), True);
}

include("navbar.php"); // 사이드바

$timecreated = time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teacherteacher','$timecreated')");

// -- 여기서부터 차트를 위한 샘플 데이터(하드코딩) -- //
$entropyData = [
    "systemEntropyOverTime" => [
        ["date" => "2024-01", "entropy" => 0.65],
        ["date" => "2024-02", "entropy" => 0.72],
        ["date" => "2024-03", "entropy" => 0.68],
        ["date" => "2024-04", "entropy" => 0.63]
    ],
    "projectEntropies" => [
        ["name" => "코어 LMS",        "entropy" => 0.63, "threshold" => 0.7 ],
        ["name" => "플러그인 시스템", "entropy" => 0.58, "threshold" => 0.65],
        ["name" => "모바일 앱",      "entropy" => 0.72, "threshold" => 0.7 ],
        ["name" => "사용자 인터페이스", "entropy" => 0.45, "threshold" => 0.6 ],
        ["name" => "시스템 연동",     "entropy" => 0.67, "threshold" => 0.7 ]
    ]
];
?>

<style>
  /* 간단한 스타일 예시 */
  .card-custom {
    background: #fff; 
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
  }
  .chart-title {
    font-size: 1.1rem;
    font-weight: bold;
    margin-bottom: 8px;
  }
</style>

<div class="main-panel">
    <div class="content" style="background-color: #f8f9fa;">
        <div class="container-fluid">

            <h2 style="margin-top: 20px; margin-bottom: 20px;">엔트로피 모니터링</h2>

            <!-- 경고 메시지 (표시 여부는 JS에서) -->
            <div id="entropyAlert" class="alert alert-warning d-none" role="alert">
                <strong>경고:</strong> 시스템 전체 엔트로피가 임계값을 초과했습니다. 즉시 개선 조치가 필요합니다.
            </div>

            <!-- 시스템 엔트로피 추이(Line Chart) & 프로젝트별 엔트로피(Bar Chart) -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card-custom">
                        <div class="chart-title">시스템 엔트로피 추이</div>
                        <canvas id="systemEntropyChart" style="width:100%; height:300px;"></canvas>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-custom">
                        <div class="chart-title">프로젝트별 엔트로피</div>
                        <canvas id="projectEntropyChart" style="width:100%; height:300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- 프로젝트별 상세 현황 (카드 형태) -->
            <div class="card-custom">
                <div class="chart-title">프로젝트 상세 현황</div>
                <div class="row" id="projectList">
                    <!-- JavaScript로 동적 생성 -->
                </div>
            </div>

        </div>
    </div>
</div>

<?php
include("quicksidebar.php");
?>

<!-- Scripts -->
<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const entropyData = <?php echo json_encode($entropyData, JSON_UNESCAPED_UNICODE); ?>;
    const systemEntropyOverTime = entropyData.systemEntropyOverTime;
    const projectEntropies = entropyData.projectEntropies;

    const entropyThreshold = 0.7;
    const lastEntropy = systemEntropyOverTime[systemEntropyOverTime.length - 1].entropy;

    // 경고 표시
    if (lastEntropy > entropyThreshold) {
        document.getElementById('entropyAlert').classList.remove('d-none');
    }

    // ------------------ Line Chart: 시스템 엔트로피 추이 ------------------ //
    const ctxLine = document.getElementById('systemEntropyChart').getContext('2d');
    const labelsLine = systemEntropyOverTime.map(item => item.date);
    const dataLine = systemEntropyOverTime.map(item => item.entropy);

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: labelsLine,
            datasets: [
                {
                    label: '엔트로피',
                    data: dataLine,
                    borderColor: '#6561f3',   // 라인 색
                    backgroundColor: 'rgba(101,97,243,0.1)', // 라인 하단 영역
                    pointBorderColor: '#6561f3',
                    pointBackgroundColor: '#fff',
                    pointRadius: 4,
                    tension: 0.1
                },
                {
                    label: '임계값',
                    data: Array(labelsLine.length).fill(entropyThreshold),
                    borderColor: '#ff6666',
                    backgroundColor: 'rgba(255,102,102,0.2)',
                    borderDash: [5, 5],      // 점선
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const val = context.parsed.y;
                    const label = context.dataset.label === '엔트로피' 
                      ? `엔트로피: ${val.toFixed(2)}`
                      : `임계값: ${val.toFixed(2)}`;
                    return label;
                  }
                }
              }
            },
            scales: {
                x: {
                    grid: {
                        drawOnChartArea: false,
                        color: "#cccccc55", // 옅은 점선
                        borderDash: [2, 2],
                    }
                },
                y: {
                    min: 0,
                    max: 1,
                    grid: {
                        color: "#cccccc55", // 옅은 점선
                        borderDash: [2, 2],
                    }
                }
            }
        }
    });

    // ------------------ Bar Chart: 프로젝트별 엔트로피 ------------------ //
    const ctxBar = document.getElementById('projectEntropyChart').getContext('2d');
    const labelsBar = projectEntropies.map(item => item.name);
    const dataEntropy = projectEntropies.map(item => item.entropy);
    const dataThreshold = projectEntropies.map(item => item.threshold);

    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: labelsBar,
            datasets: [
                {
                    label: '현재 엔트로피',
                    data: dataEntropy,
                    backgroundColor: '#6561f3'
                },
                {
                    label: '임계값',
                    data: dataThreshold,
                    backgroundColor: '#ff6666'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    grid: {
                        color: "#cccccc55",
                        borderDash: [2, 2],
                    }
                },
                y: {
                    min: 0,
                    max: 1,
                    grid: {
                        color: "#cccccc55",
                        borderDash: [2, 2],
                    }
                }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const val = context.parsed.y;
                    if (context.dataset.label === '현재 엔트로피') {
                        return `엔트로피: ${val.toFixed(2)}`;
                    } else {
                        return `임계값: ${val.toFixed(2)}`;
                    }
                  }
                }
              }
            }
        }
    });

    // ------------------ 프로젝트 상세 현황 (카드) ------------------ //
    const projectListContainer = document.getElementById('projectList');
    projectEntropies.forEach(project => {
        const { name, entropy, threshold } = project;

        const colDiv = document.createElement('div');
        colDiv.className = 'col-sm-6 col-md-4 col-lg-4 mb-3';

        const cardDiv = document.createElement('div');
        cardDiv.className = 'p-3 border rounded';

        // 테두리 컬러
        cardDiv.style.borderColor = (entropy > threshold) ? '#ff4444' : '#4CAF50';

        const title = document.createElement('h5');
        title.textContent = name;
        title.className = 'font-weight-bold';

        const row1 = document.createElement('div');
        row1.className = 'd-flex justify-content-between';
        row1.innerHTML = `
            <span>현재 엔트로피:</span>
            <span class="${(entropy > threshold) ? 'text-danger' : 'text-success'}">
                ${entropy.toFixed(2)}
            </span>
        `;

        const row2 = document.createElement('div');
        row2.className = 'd-flex justify-content-between';
        row2.innerHTML = `
            <span>임계값:</span>
            <span>${threshold.toFixed(2)}</span>
        `;

        const progressWrapper = document.createElement('div');
        progressWrapper.className = 'w-100 bg-light rounded mt-2';
        progressWrapper.style.height = '6px';

        const progressBar = document.createElement('div');
        progressBar.style.width = (entropy * 100) + '%';
        progressBar.style.height = '6px';
        progressBar.className = (entropy > threshold) ? 'bg-danger rounded' : 'bg-success rounded';

        progressWrapper.appendChild(progressBar);
        cardDiv.appendChild(title);
        cardDiv.appendChild(row1);
        cardDiv.appendChild(row2);
        cardDiv.appendChild(progressWrapper);
        colDiv.appendChild(cardDiv);
        projectListContainer.appendChild(colDiv);
    });
})();
</script>
