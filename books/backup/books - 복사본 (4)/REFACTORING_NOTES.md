# Chapter42.php UI 리팩터링 노트

## 백업 정보
- 원본 파일: `chapter42.php`
- 백업 파일: `chapter42_backup_20250817.php`
- 백업 일시: 2025-08-17

## 디렉토리 구조
```
/books/
├── chapter42.php (메인 파일)
├── chapter42_backup_20250817.php (백업)
├── components/ (컴포넌트 디렉토리)
├── assets/
│   ├── css/ (스타일시트)
│   └── js/ (JavaScript 파일)
```

## 의존성 버전 정보

### 현재 사용 중인 버전 (기존)
- **Bootstrap**: 4.6.2
- **jQuery**: 3.6.1 (slim 버전)
- **Popper.js**: 1.16.1
- **SweetAlert2**: 11.x
- **jQuery UI**: 1.8.18 (legacy)
- **Font Awesome**: 5.15.4

### 업그레이드 대상 버전
- **Bootstrap**: 5.3.3 (최신 안정 버전)
  - CDN: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css`
  - JS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js`
- **jQuery**: 3.6.1 유지 (기존 함수 호환성)
- **SweetAlert2**: 11.x 유지 (호환성 확인됨)

## Bootstrap 4 → 5 마이그레이션 체크리스트

### Data 속성 변경
- [x] `data-toggle` → `data-bs-toggle`
- [x] `data-dismiss` → `data-bs-dismiss`
- [x] `data-parent` → `data-bs-parent`
- [x] `data-target` → `data-bs-target`
- [x] `data-placement` → `data-bs-placement`

### 클래스명 변경
- [x] `ml-*`, `mr-*` → `ms-*`, `me-*`
- [x] `pl-*`, `pr-*` → `ps-*`, `pe-*`
- [x] `close` → `btn-close`
- [x] `badge-*` → `bg-*`
- [x] `font-weight-*` → `fw-*`

### jQuery 의존성
- jQuery는 Bootstrap 5에서 필수가 아니지만, 기존 커스텀 함수들을 위해 유지
- 점진적으로 vanilla JavaScript로 마이그레이션 예정

## 기존 JavaScript 함수 목록 (보존 필수)
1. `CheckProgress(Eventid, Userid, Itemid, Checkvalue)`
2. `ImmersiveSession(Eventid, Userid, Cid, Domainid, Chapterid, Topicid)`
3. `ChangeCheckBox(Eventid, Userid, Contentsid, Wboardid, Noteurl)`
4. `openPersonaPopup(cntid, studentid)`
5. `setGoal(Inputtext)`
6. `addReview(Inputtext)`
7. `dragChatbox(Cntid)`
8. `checkMonitoringStatus()`
9. `openCallbackModal()`
10. `saveCallbackGeneral(timeMinutes, content)`
11. `completeCallback(callbackId)`
12. `extendCallback(callbackId, additionalMinutes)`

## AJAX 엔드포인트 (보존 필수)
- `check_status.php`
- `../students/check.php`
- `../cjnstudents/check_status.php`
- `../api/callback_api.php`

## 데이터베이스 테이블 (참조)
- `mdl_user`
- `mdl_abessi_chapterlog`
- `mdl_abessi_indicators`
- `mdl_abessi_today`
- `mdl_abessi_domain`
- `mdl_abessi_curriculum`
- `mdl_abessi_gptultratalk`
- `mdl_abessi_messages`
- `mdl_abessi_messages_rating`
- `mdl_checklist_item`
- `mdl_checklist_check`
- `mdl_quiz_attempts`
- `mdl_quiz`
- `mdl_course_modules`
- `mdl_icontent_pages`
- `mdl_user_info_data`

## 롤백 절차
1. 백업 파일 복원: `cp chapter42_backup_20250817.php chapter42.php`
2. 생성된 디렉토리 제거: `rm -rf components/ assets/`
3. 추가 생성 파일 제거

## 테스트 체크리스트
- [ ] 모든 PHP 변수 정상 전달
- [ ] 데이터베이스 쿼리 정상 동작
- [ ] JavaScript 함수 호출 정상
- [ ] AJAX 통신 정상
- [ ] 세션 관리 정상
- [ ] URL 파라미터 처리 정상
- [ ] 모든 모달 정상 표시
- [ ] Collapse 기능 정상
- [ ] 체크박스 상태 저장
- [ ] 진행률 표시 정상