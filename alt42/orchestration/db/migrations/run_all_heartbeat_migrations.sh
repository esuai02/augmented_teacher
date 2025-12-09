#!/bin/bash
# Heartbeat 마이그레이션 실행 스크립트
# 서버에서 실행: bash run_all_heartbeat_migrations.sh

echo "=========================================="
echo "Heartbeat Scheduler 마이그레이션 실행"
echo "=========================================="
echo ""

# 현재 디렉토리 확인
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "작업 디렉토리: $(pwd)"
echo ""

# PHP 버전 확인
echo "1. PHP 버전 확인..."
php --version
if [ $? -ne 0 ]; then
    echo "❌ PHP가 설치되어 있지 않습니다."
    exit 1
fi
echo ""

# 마이그레이션 004 실행 (기본 테이블 생성)
echo "2. 마이그레이션 004 실행 (기본 테이블 생성)..."
php run_004_migration.php
MIGRATION_004_STATUS=$?

if [ $MIGRATION_004_STATUS -ne 0 ]; then
    echo "⚠ 마이그레이션 004에 일부 오류가 있을 수 있습니다. 계속 진행합니다..."
fi
echo ""

# 마이그레이션 005 실행
echo "3. 마이그레이션 005 실행 (Heartbeat 테이블 생성)..."
php run_005_migration.php
MIGRATION_005_STATUS=$?

if [ $MIGRATION_005_STATUS -ne 0 ]; then
    echo "❌ 마이그레이션 005 실패"
    exit 1
fi
echo ""

# 마이그레이션 006 실행
echo "4. 마이그레이션 006 실행 (Heartbeat 뷰 생성)..."
php run_006_migration.php
MIGRATION_006_STATUS=$?

if [ $MIGRATION_006_STATUS -ne 0 ]; then
    echo "❌ 마이그레이션 006 실패"
    exit 1
fi
echo ""

# 테스트 실행
echo "5. Heartbeat 테스트 실행..."
cd ../../api/scheduler
php test_heartbeat.php
TEST_STATUS=$?

if [ $TEST_STATUS -ne 0 ]; then
    echo "⚠ 테스트에 일부 문제가 있을 수 있습니다. 로그를 확인하세요."
else
    echo "✅ 모든 테스트 통과"
fi
echo ""

echo "=========================================="
echo "마이그레이션 완료"
echo "=========================================="

