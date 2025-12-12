# Tasks: 화이트보드 이미지 표시 오류 수정

## Relevant Files

- `alt42/teachingsupport/student_inbox.php` - 화이트보드 iframe 로드 및 CSS 주입 대상
- `alt42/teachingsupport/teachingagent.php` - 이미지 편집 모달 및 크기 조정 함수
- `alt42/teachingsupport/save_interaction.php` - 이미지 저장 및 메타데이터 처리

### Notes

- 화이트보드 파일(`board_capture.php`)은 서버에만 존재하므로 직접 수정 불가
- iframe CSS 주입 방식으로 클라이언트 측에서 처리
- CORS 이슈는 같은 도메인이므로 발생하지 않을 것으로 예상

## Tasks

- [ ] 1.0 student_inbox.php - iframe 로드 후 이미지 크기 제한 CSS 주입

- [ ] 2.0 teachingagent.php - 이미지 편집 모달 크기 조정 결과 검증

- [ ] 3.0 이미지 로드 오류 처리 및 fallback 구현

- [ ] 4.0 동적 이미지 처리 (MutationObserver)

- [ ] 5.0 테스트 및 검증

---

**I have generated the high-level tasks based on the PRD. Ready to generate the sub-tasks? Respond with 'Go' to proceed.**

