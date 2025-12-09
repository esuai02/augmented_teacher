# AI 페르소나 매칭 시스템

선생님과 학생의 페르소나를 AI가 매칭하여 메시지를 자동으로 변환하는 교육 시스템입니다.

## 🎯 시스템 개요

이 시스템은 선생님의 교육 철학과 학생의 학습 스타일을 매칭하여, OpenAI API를 통해 메시지를 학생에게 맞는 어조와 방식으로 자동 변환합니다.

### 예시
- **원본 메시지**: "이번 시험 제대로 공부 안하면 큰일날!"
- **변환된 메시지**: "수학시험 일정과 위험을 체크해 볼까요?"

## 📁 파일 구조

```
studenthome/
├── selectmode.php                      # 메인 페르소나 선택 인터페이스
├── chat.php                           # 실시간 채팅 인터페이스
├── create_persona_modes_table.sql     # 데이터베이스 테이블 생성 스크립트
└── README_PERSONA_MATCHING.md         # 이 파일
```

## 🚀 설치 및 설정

### 1. 데이터베이스 테이블 생성
```sql
-- create_persona_modes_table.sql 파일을 실행
mysql -u username -p database_name < create_persona_modes_table.sql
```

### 2. OpenAI API 키 설정
`selectmode.php` 파일에서 다음 라인을 수정:
```php
$api_key = 'YOUR_OPENAI_API_KEY'; // 실제 API 키로 변경
```

**보안을 위해 환경 변수 사용 권장:**
```php
$api_key = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
```

### 3. 파일 권한 설정
```bash
chmod 644 selectmode.php chat.php
```

## 📋 사용법

### 1. 페르소나 모드 선택
1. `selectmode.php?userid=[학생ID]` 접속
2. 우상단 "선생님용" 버튼 클릭
3. 선생님 모드와 학생 모드를 각각 선택
4. "모드 설정 저장" 버튼 클릭

### 2. 메시지 변환 데모
1. 모드 저장 후 "메시지 변환 데모" 버튼 클릭
2. 선생님 메시지 입력
3. "메시지 변환하기" 버튼으로 변환 결과 확인

### 3. 실시간 채팅
1. "실시간 채팅 시작" 버튼 클릭
2. 메시지 입력 시 자동으로 학생 스타일에 맞게 변환


## 🎨 페르소나 모드 종류

### 선생님 모드
- **커리큘럼 중심**: 체계적이고 계획적인 어조
- **시험대비 중심**: 긴장감 있고 동기부여적인 어조  
- **맞춤학습 중심**: 친근하고 격려하는 어조
- **단기미션 중심**: 게임처럼 도전적이고 즉각적인 어조
- **자기성찰 중심**: 사려깊고 질문을 유도하는 어조
- **자기주도 중심**: 자율성을 존중하는 제안형 어조

### 학생 모드
- **커리큘럼 중심**: 상위권, 목표 대학 있는 유형
- **시험대비 중심**: 시험에 집중하는 유형, 동기부여 타입
- **맞춤학습 중심**: 기초 부족, 스스로 학습이 익숙하지 않은 학생
- **단기미션 중심**: 집중력 낮고 루틴이 없는 학생
- **자기성찰 중심**: 고민은 많고 생각은 깊은데 실행은 없는 학생
- **자기주도 중심**: 자율성 높은 중·상위권, "나만의 공부법" 선호자

## 🗃️ 데이터베이스 구조

### mdl_persona_modes 테이블
```sql
- id: 고유 식별자
- teacher_id: 선생님 사용자 ID
- student_id: 학생 사용자 ID  
- teacher_mode: 선생님 교육 모드
- student_mode: 학생 학습 모드
- created_at: 생성 시간
- updated_at: 수정 시간
```

### mdl_message_transformations 테이블
```sql
- id: 고유 식별자
- teacher_id: 선생님 사용자 ID
- student_id: 학생 사용자 ID
- original_message: 원본 메시지
- transformed_message: 변환된 메시지
- teacher_mode: 선생님 모드
- student_mode: 학생 모드
- transformation_time: 변환 시간
```

### mdl_chat_messages 테이블
```sql
- id: 고유 식별자
- room_id: 채팅방 ID
- sender_id: 발신자 ID
- receiver_id: 수신자 ID
- message_type: 메시지 타입 (original/transformed)
- message_content: 메시지 내용
- sent_at: 전송 시간
- read_at: 읽은 시간
```

## 🔧 API 엔드포인트

### selectmode.php
- `POST action=save_modes`: 페르소나 모드 저장
- `POST action=transform_message`: 메시지 변환

### chat.php  
- `POST action=send_message`: 채팅 메시지 전송 및 변환
- `GET action=get_messages`: 채팅 메시지 조회

## 🎯 주요 기능

### 1. 자동 메시지 변환
- OpenAI GPT-4 기반 메시지 변환
- 선생님과 학생 페르소나에 따른 맞춤형 변환
- 원본 메시지의 핵심 내용 유지

### 2. 실시간 채팅
- 메시지 전송 시 자동 변환
- 원본과 변환된 메시지 모두 저장
- 변환 상태 실시간 표시

### 3. 페르소나 관리
- 선생님-학생 쌍별 모드 설정
- 언제든 모드 변경 가능
- 설정 이력 추적

## 🔒 보안 고려사항

1. **API 키 보안**: OpenAI API 키를 환경 변수로 관리
2. **입력 검증**: 모든 사용자 입력에 대한 검증 및 이스케이프
3. **권한 확인**: 선생님-학생 관계 검증
4. **SQL 인젝션 방지**: Prepared Statement 사용

## 🚨 문제 해결

### 자주 발생하는 문제

1. **API 키 오류**
   - OpenAI API 키가 올바른지 확인
   - API 크레딧이 충분한지 확인

2. **데이터베이스 연결 오류**  
   - Moodle config.php 경로 확인
   - 테이블이 생성되었는지 확인

3. **메시지 변환 실패**
   - API 키 설정 확인
   - 네트워크 연결 상태 확인
   - OpenAI API 서비스 상태 확인

## 📈 확장 가능성

1. **다국어 지원**: 메시지 번역 기능 추가
2. **음성 메시지**: TTS/STT 연동
3. **감정 분석**: 메시지 감정 상태 분석
4. **학습 분석**: 대화 패턴 분석 및 인사이트 제공
5. **모바일 앱**: React Native 기반 모바일 인터페이스

## 🤝 기여하기

버그 리포트나 기능 제안은 이슈를 통해 제출해주세요.

## 📄 라이센스

이 프로젝트는 교육 목적으로 개발되었습니다.