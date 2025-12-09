# timescaffolding42.php 구조 분석 문서

## 1. 파일 정보
- **대상 파일**: timescaffolding42.php
- **백업 파일**: timescaffolding42_backup.php (생성 완료)
- **파일 크기**: 약 42,456 tokens

## 2. HTML 구조 분석

### 2.1 주요 컨테이너 클래스
- `.modern-header`: 메인 헤더 및 네비게이션 영역
- `.sticky-notes-container`: 메모장 영역 전체 컨테이너
- `.sticky-notes-header`: 메모장 헤더 (제목, 버튼들)
- `.sticky-notes-area`: 메모 표시 영역
- `.sticky-notes-footer`: 메모장 하단 버튼들
- `.section-header`: 일반 섹션 헤더 클래스

### 2.2 헤더 구조 (lines 361-411)
```html
<div class="modern-header">
  <div class="nav-top">
    <div class="content-container"> 
      <div class="nav-controls">
        <div class="header-nav">
          <!-- 네비게이션 링크들 -->
        </div>   
      </div>
    </div>
  </div>
  <div class="status-section">
    <!-- 상태 체크박스들 -->
  </div>
</div>
```

### 2.3 메인 컨텐츠 구조 (lines 412-505)
- **활동일지 영역**: 테이블 기반 활동 추적 정보
- **메모장 영역**: sticky-notes-container 내부
  - teacher-notes-section: 선생님 메모
  - student-notes-section: 학생 메모

## 3. JavaScript 함수 분석

### 3.1 핵심 함수들
- `ShowMessage(Alerttext)`: 알림 메시지 표시
- `ChangeCheckBox(Eventid, Userid, Goalid, Checkvalue)`: 체크박스 상태 변경
- `Resttime(Eventid, Userid, Goalid, Checkvalue)`: 휴식 시간 관리
- `checkMonitoringStatus()`: 모니터링 상태 확인
- `loadNotes()`: 메모 데이터 로드
- `addNewNote()`: 새 메모 추가
- `deleteAllNotes()`: 모든 메모 삭제

### 3.2 AJAX 패턴
- jQuery 기반 $.ajax() 호출 패턴 사용
- 기존 10개 이상의 AJAX 엔드포인트 존재
- 성공/실패 콜백 구조 일관성 유지

## 4. 데이터베이스 테이블 분석

### 4.1 주요 테이블들
- `mdl_abessi_tracking`: 학습 활동 추적
- `mdl_abessi_messages`: 메시지 데이터
- `mdl_abessi_today`: 일일/주간 목표
- `mdl_abessi_chapterlog`: 챕터 로그
- `mdl_abessi_progress`: 진도 추적
- `mdl_abessi_missionlog`: 미션 로그
- `mdl_abessi_mathtalk`: 녹음 관련

### 4.2 쿼리 패턴
- 모든 쿼리는 `$DB->get_record_sql()` 또는 `$DB->get_records_sql()` 사용
- userid 기반 필터링
- 시간 범위 기반 조회 (aweekago, halfdayago 등)

## 5. CSS 클래스 체계

### 5.1 네이밍 패턴
- `.modern-`: 헤더 관련 스타일
- `.sticky-notes-`: 메모장 관련 스타일  
- `.section-`: 일반 섹션 관련 스타일
- `.nav-`: 네비게이션 관련 스타일
- `.status-`: 상태 관련 스타일

### 5.2 기존 jQuery UI 버전
- jQuery 1.12.4
- jQuery UI 1.8.18
- 탭 시스템 구현에 활용 가능

## 6. 리팩터링 고려사항

### 6.1 보존해야 할 요소들
- modern-header 전체 구조 및 네비게이션
- 모든 JavaScript 함수 시그니처
- 데이터베이스 쿼리 로직
- AJAX 엔드포인트 구조
- CSS 클래스명 및 스타일

### 6.2 탭 시스템 전환 계획
1. **활동일지 탭**: 기존 활동 추적 테이블 영역
2. **메모장 탭**: 기존 sticky-notes-container 영역
3. **학습분석 탭**: 신규 AI 분석 시스템

### 6.3 신규 구현 요소
- OpenAI API 연동 함수
- Synergetic API 호출 시스템
- 분석 결과 캐싱 테이블
- AI 분석 UI 컴포넌트

## 7. 다음 단계 준비사항
- jQuery UI tabs 호환 HTML 구조 설계
- 기존 CSS와 조화로운 탭 스타일 개발
- behavior-preserving 원칙 하에 점진적 리팩터링 진행