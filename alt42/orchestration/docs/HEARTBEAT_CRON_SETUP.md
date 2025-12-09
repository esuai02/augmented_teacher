# Heartbeat Scheduler Cron 설정 가이드

## 개요
Heartbeat Scheduler를 30분마다 자동 실행하도록 cron 작업을 설정합니다.

## Cron 작업 설정

### 1. Cron 작업 명령어

```bash
*/30 * * * * /usr/bin/php /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler/heartbeat.php >> /var/log/alt42/heartbeat_cron.log 2>&1
```

### 2. Cron 설정 방법

#### 방법 1: crontab 편집 (권장)

```bash
# crontab 편집
crontab -e

# 다음 라인 추가
*/30 * * * * /usr/bin/php /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler/heartbeat.php >> /var/log/alt42/heartbeat_cron.log 2>&1
```

#### 방법 2: cron 파일 직접 생성

```bash
# cron 디렉토리에 파일 생성
sudo nano /etc/cron.d/alt42-heartbeat

# 다음 내용 추가
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
*/30 * * * * www-data /usr/bin/php /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler/heartbeat.php >> /var/log/alt42/heartbeat_cron.log 2>&1
```

### 3. 로그 디렉토리 생성

```bash
# 로그 디렉토리 생성
sudo mkdir -p /var/log/alt42
sudo chown www-data:www-data /var/log/alt42
sudo chmod 755 /var/log/alt42
```

### 4. PHP 경로 확인

```bash
# PHP 경로 확인
which php
# 또는
/usr/bin/php -v
```

## Cron 스케줄 설명

```
*/30 * * * *
│   │ │ │ │
│   │ │ │ └─── 요일 (0-7, 0과 7은 일요일)
│   │ │ └───── 월 (1-12)
│   │ └─────── 일 (1-31)
│   └───────── 시 (0-23)
└───────────── 분 (0-59)

*/30 = 매 30분마다
```

**실행 시간 예시:**
- 00:00, 00:30
- 01:00, 01:30
- 02:00, 02:30
- ... (하루 48회 실행)

## 검증 및 테스트

### 1. Cron 작업 확인

```bash
# 현재 cron 작업 목록 확인
crontab -l

# 또는
sudo crontab -l -u www-data
```

### 2. 수동 실행 테스트

```bash
# 직접 실행하여 오류 확인
/usr/bin/php /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler/heartbeat.php
```

### 3. 로그 확인

```bash
# 실시간 로그 모니터링
tail -f /var/log/alt42/heartbeat_cron.log

# 최근 로그 확인
tail -n 100 /var/log/alt42/heartbeat_cron.log
```

### 4. Cron 실행 로그 확인

```bash
# 시스템 cron 로그 확인 (Ubuntu/Debian)
grep CRON /var/log/syslog | tail -20

# 시스템 cron 로그 확인 (CentOS/RHEL)
grep CRON /var/log/cron | tail -20
```

## 문제 해결

### Cron이 실행되지 않는 경우

1. **PHP 경로 확인**
   ```bash
   which php
   # 실제 경로로 수정
   ```

2. **파일 권한 확인**
   ```bash
   ls -la /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/api/scheduler/heartbeat.php
   # 실행 권한이 있어야 함
   ```

3. **Moodle config 경로 확인**
   - heartbeat.php에서 `config.php` 경로가 올바른지 확인

4. **로그 확인**
   ```bash
   # cron 실행 오류 확인
   grep -i error /var/log/alt42/heartbeat_cron.log
   ```

### Cron이 실행되지만 오류가 발생하는 경우

1. **직접 실행하여 오류 확인**
   ```bash
   cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration
   php api/scheduler/heartbeat.php
   ```

2. **PHP 오류 로그 확인**
   ```bash
   tail -f /var/log/php_errors.log
   ```

3. **데이터베이스 연결 확인**
   - Moodle config.php가 올바르게 로드되는지 확인

## 모니터링

### 1. 실행 빈도 확인

```sql
-- 최근 24시간 실행 내역
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS execution_time,
    students_processed,
    errors,
    duration_ms
FROM mdl_alt42_heartbeat_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;
```

### 2. 성능 모니터링

```sql
-- 평균 실행 시간 및 처리 학생 수
SELECT 
    DATE(created_at) AS date,
    COUNT(*) AS executions,
    AVG(students_processed) AS avg_students,
    AVG(duration_ms) AS avg_duration_ms,
    SUM(errors) AS total_errors
FROM mdl_alt42_heartbeat_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### 3. 알림 설정 (선택사항)

중요한 오류 발생 시 알림을 받으려면:

```bash
# cron 작업에 메일 알림 추가
*/30 * * * * /usr/bin/php /path/to/heartbeat.php >> /var/log/alt42/heartbeat_cron.log 2>&1 || echo "Heartbeat failed at $(date)" | mail -s "Heartbeat Error" admin@example.com
```

## 보안 고려사항

1. **파일 권한**
   - heartbeat.php는 웹에서 직접 접근 불가능하도록 설정
   - .htaccess 또는 웹 서버 설정으로 보호

2. **로그 로테이션**
   ```bash
   # logrotate 설정 추가
   sudo nano /etc/logrotate.d/alt42-heartbeat
   
   # 내용:
   /var/log/alt42/heartbeat_cron.log {
       daily
       rotate 30
       compress
       delaycompress
       missingok
       notifempty
   }
   ```

## 참고사항

- **실행 주기 조정**: 필요에 따라 `*/30`을 `*/15` (15분) 또는 `*/60` (1시간)으로 변경 가능
- **서버 부하**: 학생 수가 많을 경우 실행 주기를 조정 고려
- **백업**: cron 설정 변경 전 백업 권장

---

**작성일**: 2025-11-13  
**버전**: 1.0.0

