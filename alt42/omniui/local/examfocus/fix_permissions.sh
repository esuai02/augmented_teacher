#!/bin/bash
# ExamFocus 권한 수정 스크립트

echo "ExamFocus 플러그인 권한 수정 중..."

# 디렉토리 권한 설정 (755)
find /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/local/examfocus -type d -exec chmod 755 {} \;

# 파일 권한 설정 (644)
find /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/local/examfocus -type f -exec chmod 644 {} \;

# PHP 파일 실행 권한 (644도 충분, 웹서버가 읽기만 하면 됨)
find /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/local/examfocus -name "*.php" -exec chmod 644 {} \;

# 소유자 변경 (웹서버 사용자로)
# Ubuntu/Debian: www-data
# CentOS/RHEL: apache
# 시스템에 맞게 수정하세요
# chown -R www-data:www-data /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/local/examfocus

echo "권한 수정 완료!"
echo ""
echo "웹서버 사용자 확인 방법:"
echo "  ps aux | grep -E 'apache|httpd|nginx|php-fpm'"
echo ""
echo "수동으로 소유자 변경이 필요한 경우:"
echo "  sudo chown -R www-data:www-data /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/local/examfocus"
echo ""
echo "Apache 설정 확인:"
echo "  1. AllowOverride All 설정 확인"
echo "  2. .htaccess 파일 읽기 허용 확인"
echo "  3. Apache 재시작: sudo service apache2 restart"