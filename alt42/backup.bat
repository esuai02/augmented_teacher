@echo off
echo Git 백업을 시작합니다...

REM 현재 변경사항 확인
echo 현재 변경사항:
git status

REM 사용자 확인
echo.
set /p confirm=백업을 계속하시겠습니까? (y/n): 

if /i "%confirm%"=="y" (
    echo 파일 추가 중...
    git add .
    
    REM 커밋 메시지 입력
    set /p message=커밋 메시지를 입력하세요: 
    
    if "%message%"=="" (
        set message=Auto backup %date% %time%
    )
    
    echo 커밋 생성 중...
    git commit -m "%message%"
    
    echo GitHub에 푸시 중...
    git push origin master
    
    echo 백업 완료!
) else (
    echo 백업이 취소되었습니다.
)

pause 