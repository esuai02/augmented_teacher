# 상담관리 모듈 (Consultation Module)

## 개요
상담관리 모듈은 학생 및 학부모 상담을 체계적으로 관리하고 기록하는 기능을 제공합니다.

## 모듈 구조
- **동적 데이터베이스 기반**: 모든 상담 카테고리와 항목이 데이터베이스에서 동적으로 로드됩니다.
- **플러그인 시스템 통합**: 각 상담 항목은 플러그인으로 구성 가능합니다.

## 주요 탭
1. **신규학생**: 신규 학생 상담 및 레벨 테스트
   - 초등학생, 중학생, 예비고, 고1, 고2, 고3
2. **정기상담**: 재원생 정기 상담 및 학습 점검
3. **시험관련**: 시험 관련 특별 상담 및 관리
4. **상황맞춤 상담**: 특별한 상황에 맞춘 맞춤형 상담
5. **사례청취 및 스킬업**: 상담 사례 분석과 전문성 향상
6. **학부모 페르소나**: 학부모 유형별 맞춤 대응 전략
7. **학생 페르소나**: 학생 유형별 맞춤 학습 전략

## 데이터베이스 설정
```sql
-- consultation 모듈 데이터를 추가하려면:
mysql -u username -p database_name < insert_consultation_module_data.sql
```

## 사용 방법
```javascript
// 상담 모듈 데이터 가져오기
const consultationData = await window.consultationModule.getData();

// 신규 학생 상담 시작
const steps = window.consultationModule.startNewStudentConsultation('고3');
```

## 마이그레이션 완료
- ✅ 하드코딩된 데이터를 데이터베이스로 이전
- ✅ 동적 모듈 로더 시스템 통합
- ✅ 폴백 메커니즘 구현