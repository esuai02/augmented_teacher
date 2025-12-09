# Shining Stars 설치 가이드

## 📋 사전 요구사항

### 서버 환경
- **웹 서버**: Apache 2.4+ 또는 Nginx 1.18+
- **PHP**: 7.4 이상 (권장: 8.0+)
- **데이터베이스**: MySQL 5.7+ 또는 MariaDB 10.3+
- **SSL 인증서**: HTTPS 필수

### PHP 확장
```bash
# 필수 확장 확인
php -m | grep -E 'curl|json|mbstring|mysqli|openssl'
```

필요한 확장:
- curl
- json
- mbstring
- mysqli
- openssl

### 기타 도구
- Composer 2.0+
- Git
- Node.js 14+ (선택사항, 프론트엔드 빌드용)

## 🚀 설치 과정

### 1. 프로젝트 다운로드

```bash
# Git을 사용한 다운로드
git clone https://github.com/yourusername/shiningstars.git
cd shiningstars

# 또는 ZIP 파일 다운로드 후 압축 해제
wget https://github.com/yourusername/shiningstars/archive/main.zip
unzip main.zip
cd shiningstars-main
```

### 2. 의존성 설치

```bash
# Composer 의존성 설치
composer install --no-dev --optimize-autoloader

# 개발 환경에서는
composer install
```

### 3. 환경 설정

#### 3.1 환경 변수 파일 생성
```bash
cp .env.example .env
```

#### 3.2 .env 파일 편집
```bash
nano .env
```

```env
# 데이터베이스 설정
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=shiningstars
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Moodle 설정
MOODLE_URL=https://your-moodle-site.com
MOODLE_DB_PREFIX=mdl_

# OpenAI API 설정
OPENAI_API_KEY=your_openai_api_key
OPENAI_MODEL=gpt-4

# 애플리케이션 설정
APP_URL=https://your-domain.com/shiningstars
APP_DEBUG=false
APP_TIMEZONE=Asia/Seoul

# 세션 설정
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
```

### 4. 데이터베이스 설정

#### 4.1 데이터베이스 생성
```sql
CREATE DATABASE shiningstars CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shiningstars_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON shiningstars.* TO 'shiningstars_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 4.2 테이블 생성
```bash
mysql -u shiningstars_user -p shiningstars < sql/schema.sql
```

#### 4.3 초기 데이터 입력 (선택사항)
```bash
mysql -u shiningstars_user -p shiningstars < sql/seed.sql
```

### 5. 디렉토리 권한 설정

```bash
# 로그 디렉토리
mkdir -p logs
chmod 755 logs

# 데이터 디렉토리
mkdir -p data/prompts data/questions
chmod 755 data
chmod 755 data/prompts data/questions

# 설정 파일 보호
chmod 600 .env
chmod 644 config/*.php
```

### 6. 웹 서버 설정

#### Apache 설정 예시
```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/shiningstars
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    
    <Directory /var/www/shiningstars>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # 보안 헤더
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    
    ErrorLog ${APACHE_LOG_DIR}/shiningstars-error.log
    CustomLog ${APACHE_LOG_DIR}/shiningstars-access.log combined
</VirtualHost>
```

#### Nginx 설정 예시
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/shiningstars;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\. {
        deny all;
    }
    
    # 보안 헤더
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

### 7. Moodle 통합

#### 7.1 Moodle 관리자로 로그인
1. 사이트 관리 → 플러그인 → 활동 모듈 → 외부 도구
2. "사전 구성된 도구 관리" 클릭

#### 7.2 새 도구 추가
- **도구 이름**: 수학 성찰의 별자리
- **도구 URL**: `https://your-domain.com/shiningstars/index.php`
- **소비자 키**: `shiningstars`
- **공유 비밀**: 안전한 비밀번호 생성
- **기본 실행 컨테이너**: 새 창에서 열기

#### 7.3 config.php 업데이트
```php
// config/config.php에 추가
define('LTI_CONSUMER_KEY', 'shiningstars');
define('LTI_SHARED_SECRET', 'your_shared_secret');
```

### 8. 설치 확인

#### 8.1 시스템 체크
```bash
php check_installation.php
```

#### 8.2 웹 브라우저 테스트
1. `https://your-domain.com/shiningstars/test.php` 접속
2. 모든 항목이 "OK"로 표시되는지 확인

### 9. 보안 강화

#### 9.1 불필요한 파일 제거
```bash
rm -f test.php check_installation.php
rm -rf sql/seed.sql docs/
```

#### 9.2 파일 권한 최종 확인
```bash
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 .env
chmod 755 logs/
```

## 🔧 문제 해결

### 일반적인 문제

#### 1. 500 Internal Server Error
- PHP 에러 로그 확인: `tail -f logs/error.log`
- 파일 권한 확인
- PHP 확장 모듈 확인

#### 2. 데이터베이스 연결 오류
- .env 파일의 DB 설정 확인
- MySQL 서비스 상태 확인
- 방화벽 설정 확인

#### 3. OpenAI API 오류
- API 키 유효성 확인
- API 사용량 한도 확인
- 네트워크 연결 확인

### 로그 위치
- **애플리케이션 로그**: `logs/app.log`
- **에러 로그**: `logs/error.log`
- **AI 사용 로그**: `logs/ai_usage.log`

## 📞 지원

설치 중 문제가 발생하면:
1. [GitHub Issues](https://github.com/yourusername/shiningstars/issues) 확인
2. [설치 FAQ](FAQ.md) 참조
3. 지원 이메일: support@example.com