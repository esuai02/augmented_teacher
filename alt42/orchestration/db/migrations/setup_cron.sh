#!/bin/bash
# Heartbeat Scheduler Cron 설정 스크립트
# 
# 사용법: sudo bash setup_cron.sh
# 
# 주의: 이 스크립트는 서버에서 직접 실행해야 합니다.

set -e

echo "=== Heartbeat Scheduler Cron 설정 ==="
echo ""

# 변수 설정
PHP_PATH="/usr/bin/php"
HEARTBEAT_SCRIPT="/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler/heartbeat.php"
LOG_DIR="/var/log/alt42"
LOG_FILE="${LOG_DIR}/heartbeat_cron.log"
CRON_USER="www-data"

# PHP 경로 확인
if [ ! -f "$PHP_PATH" ]; then
    echo "⚠ PHP 경로를 찾을 수 없습니다: $PHP_PATH"
    echo "PHP 경로를 확인하세요:"
    which php
    read -p "PHP 경로를 입력하세요 (기본값: /usr/bin/php): " PHP_PATH
    PHP_PATH=${PHP_PATH:-/usr/bin/php}
fi

# Heartbeat 스크립트 확인
if [ ! -f "$HEARTBEAT_SCRIPT" ]; then
    echo "✗ Heartbeat 스크립트를 찾을 수 없습니다: $HEARTBEAT_SCRIPT"
    exit 1
fi

echo "✓ PHP 경로: $PHP_PATH"
echo "✓ Heartbeat 스크립트: $HEARTBEAT_SCRIPT"
echo ""

# 로그 디렉토리 생성
echo "1. 로그 디렉토리 생성 중..."
if [ ! -d "$LOG_DIR" ]; then
    sudo mkdir -p "$LOG_DIR"
    sudo chown $CRON_USER:$CRON_USER "$LOG_DIR"
    sudo chmod 755 "$LOG_DIR"
    echo "  ✓ 로그 디렉토리 생성: $LOG_DIR"
else
    echo "  ✓ 로그 디렉토리 이미 존재: $LOG_DIR"
fi

# Cron 작업 생성
echo ""
echo "2. Cron 작업 설정 중..."

CRON_LINE="*/30 * * * * $PHP_PATH $HEARTBEAT_SCRIPT >> $LOG_FILE 2>&1"

# 기존 cron 작업 확인
if sudo crontab -u $CRON_USER -l 2>/dev/null | grep -q "heartbeat.php"; then
    echo "  ⚠ 기존 cron 작업이 발견되었습니다."
    read -p "  기존 작업을 교체하시겠습니까? (y/n): " REPLACE
    if [ "$REPLACE" = "y" ]; then
        # 기존 작업 제거
        sudo crontab -u $CRON_USER -l 2>/dev/null | grep -v "heartbeat.php" | sudo crontab -u $CRON_USER -
        echo "  ✓ 기존 작업 제거됨"
    else
        echo "  ⚠ 기존 작업을 유지합니다."
        exit 0
    fi
fi

# 새 cron 작업 추가
(sudo crontab -u $CRON_USER -l 2>/dev/null; echo "$CRON_LINE") | sudo crontab -u $CRON_USER -

echo "  ✓ Cron 작업 추가됨"
echo ""

# Cron 작업 확인
echo "3. 설정된 Cron 작업 확인:"
sudo crontab -u $CRON_USER -l | grep heartbeat
echo ""

# 테스트 실행
echo "4. 테스트 실행 중..."
if $PHP_PATH $HEARTBEAT_SCRIPT > /tmp/heartbeat_test.log 2>&1; then
    echo "  ✓ 테스트 실행 성공"
    echo "  결과:"
    tail -5 /tmp/heartbeat_test.log
else
    echo "  ✗ 테스트 실행 실패"
    echo "  오류 내용:"
    cat /tmp/heartbeat_test.log
    exit 1
fi

echo ""
echo "=== 설정 완료 ==="
echo ""
echo "다음 명령어로 모니터링하세요:"
echo "  tail -f $LOG_FILE"
echo ""
echo "Cron 작업 확인:"
echo "  sudo crontab -u $CRON_USER -l"
echo ""

