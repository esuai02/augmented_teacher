# ALT42 프로젝트

## 프로젝트 개요
ALT42는 교육 기술 플랫폼으로, 다양한 학습 도구와 시스템을 포함하고 있습니다.

## 프로젝트 구조
- `bloomstwosigma/` - 블룸스 투시그마 실험 시스템
- `contextconsolidation/` - 맥락 통합 시스템
- `healthyclassroom/` - 건강한 교실 업그레이드 시스템
- `hightutor/` - 하이 튜터 시스템
- `omniui/` - 통합 UI 시스템
- `parentalrapportsystem/` - 학부모 소통 시스템
- `patternbank/` - 패턴 뱅크 시스템
- `studenthome/` - 학생 홈 시스템
- `teacherhome/` - 교사 홈 시스템
- `teachingsupport/` - 교육 지원 시스템

## Git 백업 설정

### 초기 설정 (이미 완료됨)
```bash
git init
git remote add origin https://github.com/esuai02/alt42.git
git config user.name "esuai02"
git config user.email "esuai02@github.com"
```

### 백업 방법

#### 1. 자동 백업 (권장)
`backup.bat` 파일을 더블클릭하여 실행하거나, 명령 프롬프트에서:
```cmd
backup.bat
```

#### 2. 수동 백업
```bash
# 변경사항 확인
git status

# 파일 추가
git add .

# 커밋 생성
git commit -m "커밋 메시지"

# GitHub에 푸시
git push origin master
```

### 백업 파일 정보
- `.gitignore` - 백업에서 제외할 파일들 설정
- `backup.bat` - 자동 백업 스크립트

### 참고사항
- 정기적인 백업을 권장합니다
- 중요한 변경사항은 즉시 백업하세요
- 커밋 메시지는 명확하게 작성하세요

## 기여 방법
1. 변경사항을 작업합니다
2. `backup.bat`를 사용하여 백업합니다
3. GitHub에서 변경사항을 확인합니다

## 연락처
GitHub: https://github.com/esuai02
리포지토리: https://github.com/esuai02/alt42 # alt42_20250715
