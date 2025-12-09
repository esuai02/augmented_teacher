@echo off
echo [%date% %time%] 자동 백업 시작 >> backup.log

REM 프로젝트 디렉토리로 이동
cd /d "C:\1 Project\alt42"

REM Git 상태 확인
git status >> backup.log 2>&1

REM 변경사항이 있는지 확인
git diff --quiet
if %errorlevel% equ 0 (
    echo [%date% %time%] 변경사항 없음 >> backup.log
    goto :end
)

REM 파일 추가
echo [%date% %time%] 파일 추가 중... >> backup.log
git add . >> backup.log 2>&1

REM 커밋 생성
echo [%date% %time%] 커밋 생성 중... >> backup.log
git commit -m "Auto backup %date% %time%" >> backup.log 2>&1

REM GitHub에 푸시
echo [%date% %time%] GitHub에 푸시 중... >> backup.log
git push origin master >> backup.log 2>&1

if %errorlevel% equ 0 (
    echo [%date% %time%] 백업 성공! >> backup.log
) else (
    echo [%date% %time%] 백업 실패! >> backup.log
)

:end
echo [%date% %time%] 자동 백업 완료 >> backup.log
echo. >> backup.log 