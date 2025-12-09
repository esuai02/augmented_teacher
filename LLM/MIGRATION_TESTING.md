# TinyMCE to Summernote 마이그레이션 테스트 가이드

## 🚀 빠른 테스트 방법

### 1. Summernote 테스트 (새 에디터)
```
editprompt.php?cntid=[ID]&cnttype=[TYPE]&use_summernote=1
```

### 2. 듀얼 모드 테스트 (두 에디터 비교)
```
editprompt.php?cntid=[ID]&cnttype=[TYPE]&dual_mode=1
```

### 3. 디버그 모드 (성능 모니터링)
```
editprompt.php?cntid=[ID]&cnttype=[TYPE]&use_summernote=1&debug=1
```

### 4. 레거시 모드 (TinyMCE)
```
editprompt.php?cntid=[ID]&cnttype=[TYPE]
```

## ✅ 테스트 체크리스트

### 기본 기능 테스트
- [ ] 페이지가 에러 없이 로드됨
- [ ] 4개의 textarea가 모두 에디터로 변환됨
- [ ] 텍스트 입력이 정상 동작
- [ ] 저장 버튼(saveContent) 동작 확인

### Summernote 에디터 테스트
- [ ] 굵게(Bold) 기능 동작
- [ ] 기울임(Italic) 기능 동작
- [ ] 밑줄(Underline) 기능 동작
- [ ] 리스트(ul/ol) 생성 가능
- [ ] 링크 삽입 가능
- [ ] 이미지 삽입 가능
- [ ] 테이블 생성 가능
- [ ] 전체화면 모드 동작
- [ ] 코드뷰 모드 동작

### 콘텐츠 저장 테스트
- [ ] mytextarea0 (지시사항) 저장
- [ ] mytextarea1 (퀴즈) 저장
- [ ] mytextarea2 (본문/문제) 저장
- [ ] mytextarea3 (해설) 저장
- [ ] HTML 콘텐츠 보존 확인
- [ ] 특수문자 처리 확인

### 파일 업로드 테스트
- [ ] 오디오 업로드 버튼 동작
- [ ] 이미지 업로드 버튼 동작
- [ ] 업로드된 파일 처리 확인

### 마이그레이션 기능 테스트
- [ ] Migration Notice 표시 확인
- [ ] "Use Legacy Editor" 링크 동작
- [ ] "Enable Dual Mode" 링크 동작
- [ ] 에디터 간 전환 시 콘텐츠 유지

### 성능 테스트 (debug=1)
- [ ] Performance Metrics 표시
- [ ] Load Time 측정값 확인
- [ ] Memory Usage 표시
- [ ] 현재 에디터 타입 표시

## 🔍 트러블슈팅

### 문제: jQuery 충돌
**증상**: $ is not defined 에러
**해결**: 
- jQuery 3.6이 제대로 로드되었는지 확인
- jQuery migrate 플러그인이 로드되었는지 확인

### 문제: Summernote가 초기화되지 않음
**증상**: textarea가 일반 텍스트 영역으로 남아있음
**해결**:
- 브라우저 콘솔에서 에러 확인
- CDN 리소스가 로드되었는지 네트워크 탭 확인

### 문제: 저장 시 콘텐츠가 비어있음
**증상**: saveContent() 호출 시 빈 값
**해결**:
- getEditorContent() 함수가 올바른 에디터 타입을 감지하는지 확인
- migrationState.editors 객체 확인

### 문제: 스타일이 깨짐
**증상**: 에디터 레이아웃이 이상함
**해결**:
- Bootstrap 3.4.1이 로드되었는지 확인
- Summernote CSS가 로드되었는지 확인

## 📊 성능 비교 측정

### 측정 방법
1. 브라우저 개발자 도구 열기 (F12)
2. Network 탭에서 "Disable cache" 체크
3. 페이지 새로고침 후 측정

### 측정 항목
| 항목 | TinyMCE | Summernote | 개선율 |
|-----|---------|------------|--------|
| 페이지 로드 시간 | _____ms | _____ms | ____% |
| 에디터 초기화 시간 | _____ms | _____ms | ____% |
| 메모리 사용량 | _____KB | _____KB | ____% |
| 번들 크기 | _____KB | _____KB | ____% |

## 🚨 긴급 롤백 방법

### 방법 1: URL 파라미터
```
editprompt.php?cntid=[ID]&cnttype=[TYPE]&use_tinymce=1
```

### 방법 2: 백업 파일 복원
```bash
# 백업 파일 확인
ls migration_backups/

# 원본 파일로 복원
cp migration_backups/editprompt_20250907_175155_original.php editprompt.php
```

## 📝 테스트 완료 후 체크

- [ ] 모든 기능이 정상 동작함
- [ ] 성능이 개선됨 (또는 최소한 저하되지 않음)
- [ ] 사용자 경험이 개선됨
- [ ] 데이터 무결성이 유지됨
- [ ] 롤백 방법이 동작함

## 🎯 다음 단계

1. **Stage 1 (현재)**: 듀얼 모드 테스트
   - use_summernote=1로 개별 테스트
   - dual_mode=1로 비교 테스트

2. **Stage 2**: Summernote를 기본으로 설정
   - 마이그레이션 스크립트 실행
   - 사용자 피드백 수집

3. **Stage 3**: TinyMCE 비활성화
   - 완전 마이그레이션
   - 성능 최적화

4. **Stage 4**: 레거시 코드 제거
   - TinyMCE 완전 제거
   - 최종 최적화

---

테스트 날짜: 2025-09-07
테스터: _______________
결과: [ ] 통과 [ ] 실패 [ ] 부분 통과