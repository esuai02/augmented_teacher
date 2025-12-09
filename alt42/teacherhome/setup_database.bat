@echo off
echo.
echo ========================================
echo ALT42 플러그인 설정 데이터베이스 설정
echo ========================================
echo.
echo mdl_alt42DB_ 접두어를 사용하는 플러그인 설정 테이블들을 생성합니다.
echo.

REM 설정 파일 확인
if not exist "create_alt42_plugin_tables.sql" (
    echo [ERROR] SQL 파일을 찾을 수 없습니다: create_alt42_plugin_tables.sql
    echo.
    pause
    exit /b 1
)

if not exist "execute_database_setup.php" (
    echo [ERROR] PHP 실행 파일을 찾을 수 없습니다: execute_database_setup.php
    echo.
    pause
    exit /b 1
)

echo [INFO] 필요한 파일들이 확인되었습니다.
echo.

REM 데이터베이스 연결 정보 안내
echo ========================================
echo 데이터베이스 연결 정보를 확인하세요
echo ========================================
echo.
echo execute_database_setup.php 파일에서 다음 정보를 수정해야 합니다:
echo.
echo $host = 'localhost';
echo $dbname = 'your_database_name';
echo $username = 'your_username';
echo $password = 'your_password';
echo.
echo 수정 후 계속 진행하려면 아무 키나 누르세요...
pause > nul
echo.

REM 방법 선택
echo ========================================
echo 실행 방법을 선택하세요
echo ========================================
echo.
echo 1. 웹 브라우저에서 실행 (권장)
echo 2. 명령행에서 실행
echo 3. 종료
echo.
set /p choice=선택 (1-3): 

if "%choice%"=="1" (
    echo.
    echo [INFO] 웹 브라우저에서 실행합니다...
    echo.
    echo 다음 URL을 브라우저에서 열어주세요:
    echo http://localhost/alt42/teacherhome/execute_database_setup.php
    echo.
    echo 또는 웹 서버의 주소에 맞게 수정해주세요.
    echo.
    start http://localhost/alt42/teacherhome/execute_database_setup.php
    echo.
    echo 브라우저가 열리지 않으면 위 URL을 복사해서 직접 열어주세요.
    goto :end
)

if "%choice%"=="2" (
    echo.
    echo [INFO] 명령행에서 실행합니다...
    echo.
    php execute_database_setup.php
    goto :end
)

if "%choice%"=="3" (
    echo.
    echo [INFO] 종료합니다.
    goto :end
)

echo.
echo [ERROR] 잘못된 선택입니다.
goto :end

:end
echo.
echo ========================================
echo 완료
echo ========================================
echo.
echo 다음 단계:
echo 1. plugin_settings_api.php 파일의 데이터베이스 연결 정보를 수정하세요
echo 2. teacherhome/index.html에 플러그인 설정 스크립트를 추가하세요
echo 3. plugin_settings_demo.html에서 시스템을 테스트하세요
echo.
pause 