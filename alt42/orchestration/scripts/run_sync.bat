@echo off
REM ============================================
REM 문서-시스템 동기화 실행 스크립트
REM ============================================

echo.
echo ========================================
echo   문서-시스템 자동 동기화 도구
echo ========================================
echo.

REM Python 확인
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python이 설치되어 있지 않습니다.
    exit /b 1
)

cd /d "%~dp0"

echo [1/3] 동기화 상태 검사 중...
echo ----------------------------------------
python check_doc_sync.py

if errorlevel 2 (
    echo [ERROR] 검사 중 오류 발생
    exit /b 2
)

if errorlevel 1 (
    echo.
    echo [!] 동기화 이슈가 발견되었습니다.
    echo.
    set /p confirm="동기화를 수행하시겠습니까? (y/n): "
    if /i "%confirm%"=="y" (
        echo.
        echo [2/3] 문서 동기화 수행 중...
        echo ----------------------------------------
        python sync_docs.py
        
        echo.
        echo [3/3] 에이전트 목록 생성 중...
        echo ----------------------------------------
        python sync_docs.py --generate-list
        
        echo.
        echo ========================================
        echo   동기화 완료!
        echo ========================================
    ) else (
        echo 동기화가 취소되었습니다.
    )
) else (
    echo.
    echo [OK] 모든 문서가 동기화되어 있습니다!
)

echo.
pause

