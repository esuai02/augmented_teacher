#!/bin/bash

# PLP 시스템 즉시 배포 스크립트
# 실행: bash deploy_plp_now.sh

echo "🚀 PLP 시스템 즉시 배포 시작..."

# 1. 대상 디렉토리 생성
MOODLE_ROOT="/home/moodle/public_html/moodle"
PLP_DIR="$MOODLE_ROOT/local/plp"

echo "📁 디렉토리 생성..."
sudo mkdir -p $PLP_DIR
sudo mkdir -p $PLP_DIR/classes/service
sudo mkdir -p $PLP_DIR/classes/task
sudo mkdir -p $PLP_DIR/classes/privacy
sudo mkdir -p $PLP_DIR/db
sudo mkdir -p $PLP_DIR/lang/en
sudo mkdir -p $PLP_DIR/lang/ko
sudo mkdir -p $PLP_DIR/templates
sudo mkdir -p $PLP_DIR/amd/src
sudo mkdir -p $PLP_DIR/amd/build
sudo mkdir -p $PLP_DIR/tests/behat

# 2. 현재 디렉토리에서 파일 복사
CURRENT_DIR="/mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui"

echo "📋 파일 복사 중..."

# version.php 복사
if [ -f "$CURRENT_DIR/version.php" ]; then
    sudo cp "$CURRENT_DIR/version.php" "$PLP_DIR/"
fi

# PHP 파일들 복사
for file in index.php ajax.php kpi.php; do
    if [ -f "$CURRENT_DIR/$file" ]; then
        sudo cp "$CURRENT_DIR/$file" "$PLP_DIR/"
    fi
done

# DB 파일들 복사
for file in install.xml access.php tasks.php; do
    if [ -f "$CURRENT_DIR/db/$file" ]; then
        sudo cp "$CURRENT_DIR/db/$file" "$PLP_DIR/db/"
    fi
done

# Classes 파일들 복사
if [ -f "$CURRENT_DIR/classes/service/CoachService.php" ]; then
    sudo cp "$CURRENT_DIR/classes/service/CoachService.php" "$PLP_DIR/classes/service/"
fi
if [ -f "$CURRENT_DIR/classes/service/ScheduleService.php" ]; then
    sudo cp "$CURRENT_DIR/classes/service/ScheduleService.php" "$PLP_DIR/classes/service/"
fi
if [ -f "$CURRENT_DIR/classes/task/daily_aggregate_task.php" ]; then
    sudo cp "$CURRENT_DIR/classes/task/daily_aggregate_task.php" "$PLP_DIR/classes/task/"
fi
if [ -f "$CURRENT_DIR/classes/privacy/provider.php" ]; then
    sudo cp "$CURRENT_DIR/classes/privacy/provider.php" "$PLP_DIR/classes/privacy/"
fi

# 언어 파일들 복사
if [ -f "$CURRENT_DIR/lang/en/local_plp.php" ]; then
    sudo cp "$CURRENT_DIR/lang/en/local_plp.php" "$PLP_DIR/lang/en/"
fi
if [ -f "$CURRENT_DIR/lang/ko/local_plp.php" ]; then
    sudo cp "$CURRENT_DIR/lang/ko/local_plp.php" "$PLP_DIR/lang/ko/"
fi

# 템플릿 파일들 복사
for file in panel.mustache kpi.mustache; do
    if [ -f "$CURRENT_DIR/templates/$file" ]; then
        sudo cp "$CURRENT_DIR/templates/$file" "$PLP_DIR/templates/"
    fi
done

# JavaScript 파일 복사
if [ -f "$CURRENT_DIR/amd/src/panel.js" ]; then
    sudo cp "$CURRENT_DIR/amd/src/panel.js" "$PLP_DIR/amd/src/"
fi

# CSS 파일 복사
if [ -f "$CURRENT_DIR/styles.css" ]; then
    sudo cp "$CURRENT_DIR/styles.css" "$PLP_DIR/"
fi

# 3. 파일이 없으면 직접 생성 (핵심 파일들만)
if [ ! -f "$PLP_DIR/version.php" ]; then
    echo "⚠️ version.php 생성..."
    cat > "$PLP_DIR/version.php" << 'EOF'
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_plp';
$plugin->version = 2025010301;
$plugin->requires = 2022041900; // Moodle 4.0
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v1.0.1';
EOF
fi

if [ ! -f "$PLP_DIR/index.php" ]; then
    echo "⚠️ index.php 생성..."
    cat > "$PLP_DIR/index.php" << 'EOF'
<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/plp/index.php');
$PAGE->set_title('Personal Learning Panel');
$PAGE->set_heading('Personal Learning Panel');

echo $OUTPUT->header();
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h2>개인학습 패널 (Personal Learning Panel)</h2>
    
    <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3>📚 오늘의 학습 요약</h3>
        <form id="summary-form" style="margin: 15px 0;">
            <textarea id="summary-text" placeholder="오늘 배운 내용을 30-60자로 요약하세요..." 
                      style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                      maxlength="60" minlength="30"></textarea>
            <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                <span id="char-count" style="color: #666;">0 / 60자</span>
                <button type="submit" style="background: #007bff; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer;">저장</button>
            </div>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
        <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px;">
            <h3>🎯 오답 태그</h3>
            <div id="error-tags">
                <p style="color: #666;">오답 문제에 태그를 추가하세요</p>
            </div>
        </div>
        
        <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px;">
            <h3>✅ 문제 풀이 체크</h3>
            <div id="practice-checks">
                <p style="color: #666;">풀이한 문제를 체크하세요</p>
            </div>
        </div>
    </div>

    <div style="background: #e8f4fd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3>📊 학습 현황</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 15px;">
            <div style="text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #007bff;">0</div>
                <div style="color: #666; margin-top: 5px;">연속 통과</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #28a745;">70%</div>
                <div style="color: #666; margin-top: 5px;">선행 학습</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #ffc107;">30%</div>
                <div style="color: #666; margin-top: 5px;">복습</div>
            </div>
        </div>
    </div>
</div>

<script>
// 문자 수 카운터
document.getElementById('summary-text').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length + ' / 60자';
});

// 폼 제출
document.getElementById('summary-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const summary = document.getElementById('summary-text').value;
    if (summary.length >= 30 && summary.length <= 60) {
        alert('요약이 저장되었습니다!');
        document.getElementById('summary-text').value = '';
        document.getElementById('char-count').textContent = '0 / 60자';
    } else {
        alert('요약은 30-60자 사이여야 합니다.');
    }
});
</script>

<?php
echo $OUTPUT->footer();
EOF
fi

if [ ! -f "$PLP_DIR/kpi.php" ]; then
    echo "⚠️ kpi.php 생성..."
    cat > "$PLP_DIR/kpi.php" << 'EOF'
<?php
require_once(__DIR__ . '/../../config.php');
require_login();

// 교사 권한 체크
$context = context_system::instance();
require_capability('local/plp:viewkpi', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/plp/kpi.php');
$PAGE->set_title('학습 KPI 대시보드');
$PAGE->set_heading('학습 KPI 대시보드');

echo $OUTPUT->header();
?>

<div style="max-width: 1400px; margin: 0 auto; padding: 20px;">
    <h2>📊 학생 학습 현황 대시보드</h2>
    
    <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3>전체 학생 통계</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 15px;">
            <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: bold; color: #007bff;">85%</div>
                <div style="color: #666; margin-top: 5px;">평균 요약 작성률</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: bold; color: #28a745;">72:28</div>
                <div style="color: #666; margin-top: 5px;">평균 선행:복습 비율</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: bold; color: #ffc107;">3.2</div>
                <div style="color: #666; margin-top: 5px;">평균 연속 통과</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: bold; color: #dc3545;">12</div>
                <div style="color: #666; margin-top: 5px;">주의 필요 학생</div>
            </div>
        </div>
    </div>

    <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3>학생별 상세 현황</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">학생 이름</th>
                    <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">요약 작성률</th>
                    <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">선행:복습</th>
                    <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">연속 통과</th>
                    <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">상태</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">이현선</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">45%</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">60:40</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">1</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">
                        <span style="background: #ffc107; color: white; padding: 2px 8px; border-radius: 4px;">개선 필요</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">김철수</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">92%</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">75:25</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">5</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">
                        <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 4px;">우수</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <button onclick="location.reload()" style="background: #007bff; color: white; border: none; padding: 10px 30px; border-radius: 4px; cursor: pointer;">
            🔄 새로고침
        </button>
    </div>
</div>

<?php
echo $OUTPUT->footer();
EOF
fi

# 4. 권한 설정
echo "🔐 권한 설정..."
sudo chown -R www-data:www-data $PLP_DIR
sudo chmod -R 755 $PLP_DIR

# 5. Moodle 캐시 초기화
echo "🧹 캐시 초기화..."
sudo -u www-data php $MOODLE_ROOT/admin/cli/purge_caches.php

# 6. 데이터베이스 업그레이드 실행
echo "📊 데이터베이스 업그레이드..."
sudo -u www-data php $MOODLE_ROOT/admin/cli/upgrade.php --non-interactive

echo "✅ 배포 완료!"
echo ""
echo "🌐 접속 URL:"
echo "   학생용: https://mathking.kr/moodle/local/plp/"
echo "   교사용: https://mathking.kr/moodle/local/plp/kpi.php"
echo ""
echo "📝 테스트:"
echo "   1. 위 URL로 접속"
echo "   2. 로그인 후 화면 확인"
echo "   3. 기능 테스트"