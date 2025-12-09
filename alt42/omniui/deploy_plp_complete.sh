#!/bin/bash

echo "🚀 PLP 완전 작동 버전 배포 시작..."

# 1. SQL 실행 (테이블 생성)
echo "📊 데이터베이스 테이블 생성..."
mysql -h 58.180.27.46 -u moodle -p'@MCtrigd7128' mathking < plp_create_tables.sql

if [ $? -eq 0 ]; then
    echo "✅ 테이블 생성 완료"
else
    echo "⚠️ 테이블이 이미 존재하거나 연결 오류"
fi

# 2. PHP 파일 복사
echo "📁 파일 복사..."
# 실제 웹 경로로 파일 복사 (sudo 권한 필요할 수 있음)
sudo cp plp_full_fixed.php /home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/

# 3. 권한 설정
echo "🔐 권한 설정..."
sudo chown www-data:www-data /home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/plp_full_fixed.php
sudo chmod 755 /home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/plp_full_fixed.php

echo "✅ 배포 완료!"
echo ""
echo "🌐 접속 URL:"
echo "   https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_full_fixed.php"
echo ""
echo "📝 테스트 순서:"
echo "   1. 위 URL로 접속"
echo "   2. Moodle 로그인"
echo "   3. 모든 기능 테스트:"
echo "      - 요약 작성 (30-60자)"
echo "      - 오답 태그 추가"
echo "      - 문제 체크"
echo "      - 연속 통과 업데이트"
echo "      - 실시간 통계 확인"