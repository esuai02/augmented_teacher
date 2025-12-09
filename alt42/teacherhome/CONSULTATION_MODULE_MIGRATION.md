# Consultation Module Migration Summary

## 문제점
사용자가 "신규학생" 상담 부분에 플러그인이 하드코딩되어 있다고 보고함. 스크린샷에서 "고3" 카드가 여전히 플러그인으로 표시되고 있었음.

## 원인
상담관리(consultation) 모듈만 하드코딩된 데이터를 사용하고 있었음. 다른 모듈들(quarterly, weekly, daily 등)은 이미 데이터베이스 기반으로 전환되었지만, consultation은 누락되었음.

## 해결 방법

### 1. 동적 모듈 생성
- **파일**: `consultation/consultation.js`
- 다른 모듈들과 동일한 구조로 동적 모듈 생성
- 상담 관련 커스텀 메서드 포함

### 2. 데이터베이스 스키마 추가
- **파일**: `insert_consultation_module_data.sql`
- consultation 카테고리와 7개 탭 추가
- 각 탭별 상담 항목 데이터 추가

### 3. 스크립트 업데이트
- `script.js`: consultationModule 사용하도록 수정
- `index.html`, `index.php`: consultation 모듈 스크립트 추가
- 기존 getConsultationData()는 폴백용으로 유지

### 4. 문서화
- `consultation/README.md`: 모듈 사용 가이드
- 이 문서: 마이그레이션 요약

## 적용 방법
1. 데이터베이스에 consultation 데이터 추가:
   ```bash
   mysql -u username -p database_name < insert_consultation_module_data.sql
   ```

2. 웹 페이지 새로고침

3. 상담관리 메뉴가 데이터베이스에서 로드되는지 확인

## 검증
- 상담관리 메뉴 클릭
- 각 탭(신규학생, 정기상담 등) 확인
- 카드들이 올바른 설명과 함께 표시되는지 확인

## 완료된 작업
✅ consultation 모듈 동적화
✅ 데이터베이스 스키마 생성
✅ 하드코딩된 데이터 제거
✅ 모든 상담 카테고리 데이터베이스로 이전
✅ 문서화 완료