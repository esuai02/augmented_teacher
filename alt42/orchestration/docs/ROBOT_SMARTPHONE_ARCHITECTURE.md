# 로봇-스마트폰-서버 통신 아키텍처 설계 문서

**문서 버전**: 1.0  
**작성일**: 2025-01-27  
**최종 업데이트**: 2025-01-27  
**상태**: 설계 완료 - 구현 준비

---

## 📋 목차

1. [개요](#개요)
2. [시스템 아키텍처](#시스템-아키텍처)
3. [통신 프로토콜 및 API 설계](#통신-프로토콜-및-api-설계)
4. [데이터 모델 및 흐름](#데이터-모델-및-흐름)
5. [보안 고려사항](#보안-고려사항)
6. [로드맵](#로드맵)
7. [기술 스택 및 구현 가이드](#기술-스택-및-구현-가이드)
8. [테스트 전략](#테스트-전략)
9. [배포 및 운영](#배포-및-운영)

## 📚 관련 문서

- [API 명세서](ROBOT_API_SPEC.md) - 상세 API 엔드포인트 명세
- [구현 로드맵](ROBOT_IMPLEMENTATION_ROADMAP.md) - 단계별 구현 계획
- [데이터베이스 스키마](../database/migrations/003_robot_tables.sql) - 데이터베이스 테이블 정의

---

## 개요

### 목적

본 문서는 Mathking 학습 개입 시스템(`alt42/orchestration`)이 스마트폰을 머리로 한 로봇과 연동하기 위한 아키텍처 설계를 정의합니다. 스마트폰 앱이 Mathking 서버와 통신하며 로봇의 머리 역할을 수행하는 구조를 안정적으로 설계합니다.

### 핵심 가정

1. **로봇 구조**: 단순 로봇 모양의 거치대에 스마트폰을 거치하는 형태
2. **스마트폰 역할**: 로봇의 머리 역할 (화면, 음성, 카메라, 센서 활용)
3. **로봇 동작**: 기본적인 동작만 고려 (고개 움직임, LED 표시 등)
4. **서버 통신**: 기존 Mathking 서버(`mathking.kr`)와 RESTful API 통신
5. **학습 개입**: 기존 MVP 시스템의 Sensing → Decision → Execution 파이프라인 활용

### 범위

- ✅ **포함**: 스마트폰 앱 ↔ 서버 통신, 데이터 동기화, 개입 전달
- ✅ **포함**: 로봇 기본 동작 제어 (간단한 제스처, LED)
- ⚠️ **제외**: 복잡한 로봇 동작 (이동, 복잡한 제스처 등)
- ⚠️ **제외**: 로봇 하드웨어 제어 세부사항 (다른 영역)

---

## 시스템 아키텍처

### 전체 구조도

```
┌─────────────────────────────────────────────────────────────┐
│                    Mathking 서버 (mathking.kr)              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Orchestration System (alt42/orchestration)          │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐          │  │
│  │  │ Sensing │→ │ Decision │→ │Execution │          │  │
│  │  └──────────┘  └──────────┘  └──────────┘          │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  API Gateway (RESTful API)                           │  │
│  │  - /api/robot/status                                 │  │
│  │  - /api/robot/intervention                           │  │
│  │  - /api/robot/sensor-data                            │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Database (MySQL 5.7)                                 │  │
│  │  - mdl_mvp_* (기존 테이블)                            │  │
│  │  - mdl_robot_* (신규 테이블)                           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTPS/REST API
┌─────────────────────────────────────────────────────────────┐
│              스마트폰 앱 (로봇 머리 역할)                    │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  App Core                                             │  │
│  │  - 통신 모듈 (HTTP Client)                           │  │
│  │  - 센서 수집 모듈                                     │  │
│  │  - UI 렌더링 모듈                                     │  │
│  │  - 음성/TTS 모듈                                      │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Robot Control Module                                 │  │
│  │  - 로봇 동작 제어 (BLE/Serial)                       │  │
│  │  - LED 제어                                           │  │
│  │  - 모터 제어 (고개 움직임)                            │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            ↕ BLE/Serial
┌─────────────────────────────────────────────────────────────┐
│                    로봇 본체 (거치대)                        │
│  - 모터 (고개 움직임)                                       │
│  - LED (표정/상태 표시)                                     │
│  - BLE/Serial 통신 모듈                                     │
└─────────────────────────────────────────────────────────────┘
```

### 계층별 역할

#### 1. 서버 계층 (Mathking Server)

**역할**:
- 학습 데이터 분석 및 개입 결정
- 스마트폰 앱과의 통신 관리
- 로봇 상태 및 센서 데이터 수집
- 개입 메시지 전달 및 실행 추적

**주요 컴포넌트**:
- `mvp_system/orchestrator.php`: 파이프라인 오케스트레이션
- `api/robot/*.php`: 로봇 전용 API 엔드포인트
- `database/robot_*.sql`: 로봇 관련 데이터베이스 스키마

#### 2. 스마트폰 앱 계층

**역할**:
- 서버와의 실시간 통신
- 학생 상태 센서 데이터 수집 (카메라, 마이크, 가속도계 등)
- 개입 메시지 수신 및 표시 (화면, 음성)
- 로봇 본체 제어 (BLE/Serial 통신)

**주요 모듈**:
- **통신 모듈**: HTTP/REST API 클라이언트
- **센서 모듈**: 카메라, 마이크, 가속도계, 자이로스코프
- **UI 모듈**: 화면 표시, 애니메이션
- **음성 모듈**: TTS, 음성 인식
- **로봇 제어 모듈**: BLE/Serial 통신으로 로봇 본체 제어

#### 3. 로봇 본체 계층

**역할**:
- 스마트폰 거치대 역할
- 기본 동작 수행 (고개 움직임, LED 표시)
- BLE/Serial을 통한 스마트폰과 통신

**주요 구성요소**:
- 모터 (서보 모터 또는 스테퍼 모터)
- LED 어레이 (표정/상태 표시)
- BLE/Serial 통신 모듈
- 전원 관리 시스템

---

## 통신 프로토콜 및 API 설계

### API 기본 구조

**Base URL**: `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/api/robot/`

**인증 방식**:
- Moodle 세션 쿠키 (`MoodleSession`)
- 또는 API 토큰 (`X-API-Token` 헤더)

**응답 형식**: JSON (UTF-8)

**에러 처리**: 모든 에러 응답에 파일 경로와 라인 번호 포함

### API 엔드포인트

#### 1. 로봇 상태 등록/업데이트

**엔드포인트**: `POST /api/robot/register`

**목적**: 로봇-스마트폰 쌍을 시스템에 등록하거나 상태 업데이트

**요청**:
```json
{
  "robot_id": "robot-001",
  "device_info": {
    "device_id": "android-abc123",
    "device_model": "Samsung Galaxy S23",
    "os_version": "Android 14",
    "app_version": "1.0.0"
  },
  "student_id": 123,
  "location": {
    "latitude": 37.5665,
    "longitude": 126.9780,
    "address": "서울시 강남구"
  },
  "capabilities": {
    "camera": true,
    "microphone": true,
    "tts": true,
    "led_control": true,
    "motor_control": true
  }
}
```

**응답**:
```json
{
  "success": true,
  "data": {
    "robot_id": "robot-001",
    "registration_id": "reg-abc123",
    "status": "active",
    "last_sync": "2025-01-27T10:30:00Z"
  }
}
```

#### 2. 센서 데이터 전송

**엔드포인트**: `POST /api/robot/sensor-data`

**목적**: 스마트폰 센서 데이터를 서버로 전송 (학습 상태 분석용)

**요청**:
```json
{
  "robot_id": "robot-001",
  "student_id": 123,
  "timestamp": "2025-01-27T10:30:00Z",
  "sensor_data": {
    "camera": {
      "face_detected": true,
      "attention_score": 0.85,
      "emotion": "focused"
    },
    "microphone": {
      "ambient_noise_level": 35.5,
      "voice_detected": false
    },
    "motion": {
      "acceleration": [0.1, 0.2, 9.8],
      "gyroscope": [0.0, 0.0, 0.0],
      "device_orientation": "portrait"
    },
    "screen": {
      "brightness": 80,
      "is_active": true,
      "last_interaction": "2025-01-27T10:29:45Z"
    }
  },
  "session_context": {
    "session_id": "session-xyz789",
    "activity_type": "problem_solving",
    "duration_seconds": 600
  }
}
```

**응답**:
```json
{
  "success": true,
  "data": {
    "sensor_data_id": "sensor-abc123",
    "processed": true,
    "metrics": {
      "calm_score": 75.5,
      "focus_score": 82.0,
      "recommendation": "안정, 학습 지속 가능"
    }
  }
}
```

#### 3. 개입 메시지 수신 (Polling)

**엔드포인트**: `GET /api/robot/intervention/pending`

**목적**: 대기 중인 개입 메시지 조회 (폴링 방식)

**요청**:
```
GET /api/robot/intervention/pending?robot_id=robot-001&student_id=123
```

**응답**:
```json
{
  "success": true,
  "data": {
    "interventions": [
      {
        "intervention_id": "int-xyz789",
        "type": "micro_break",
        "priority": "high",
        "message": {
          "text": "잠깐 휴식을 취해볼까요? 3분간 심호흡을 해보세요.",
          "tts_text": "잠깐 휴식을 취해볼까요? 삼분간 심호흡을 해보세요.",
          "display_duration": 180
        },
        "robot_actions": {
          "led_pattern": "breathing",
          "motor_action": "nod",
          "animation": "calm_breathing"
        },
        "created_at": "2025-01-27T10:30:00Z",
        "expires_at": "2025-01-27T10:33:00Z"
      }
    ]
  }
}
```

#### 4. 개입 메시지 수신 (WebSocket - 향후)

**엔드포인트**: `WS /api/robot/intervention/stream`

**목적**: 실시간 개입 메시지 수신 (WebSocket 스트리밍)

**연결**:
```
WS wss://mathking.kr/.../api/robot/intervention/stream?robot_id=robot-001&token=xxx
```

**메시지 형식**:
```json
{
  "type": "intervention",
  "intervention_id": "int-xyz789",
  "data": { /* 개입 데이터 */ }
}
```

#### 5. 개입 실행 완료 보고

**엔드포인트**: `POST /api/robot/intervention/complete`

**목적**: 개입 메시지 실행 완료 및 결과 보고

**요청**:
```json
{
  "intervention_id": "int-xyz789",
  "robot_id": "robot-001",
  "student_id": 123,
  "status": "completed",
  "execution_data": {
    "started_at": "2025-01-27T10:30:05Z",
    "completed_at": "2025-01-27T10:33:00Z",
    "user_response": "completed",
    "effectiveness_score": 0.85
  }
}
```

**응답**:
```json
{
  "success": true,
  "data": {
    "intervention_id": "int-xyz789",
    "status": "completed",
    "recorded_at": "2025-01-27T10:33:01Z"
  }
}
```

#### 6. 로봇 상태 조회

**엔드포인트**: `GET /api/robot/status`

**목적**: 로봇 현재 상태 조회

**요청**:
```
GET /api/robot/status?robot_id=robot-001
```

**응답**:
```json
{
  "success": true,
  "data": {
    "robot_id": "robot-001",
    "status": "active",
    "student_id": 123,
    "last_sensor_update": "2025-01-27T10:29:45Z",
    "last_intervention": "2025-01-27T10:30:00Z",
    "battery_level": 85,
    "connection_status": "online"
  }
}
```

### 통신 흐름도

#### 시나리오 1: 정기 센서 데이터 전송

```
[스마트폰 앱]                    [서버]
     |                              |
     |-- POST /sensor-data -------->|
     |   (센서 데이터)              |
     |                              |-- Sensing Layer 실행
     |                              |-- Decision Layer 실행
     |                              |
     |<-- 200 OK -------------------|
     |   (처리 완료)                |
```

#### 시나리오 2: 개입 메시지 수신 및 실행

```
[서버]                            [스마트폰 앱]
     |                              |
     |-- Decision: 개입 필요 ------>|
     |                              |
     |                              |-- GET /intervention/pending
     |<-- 요청 ----------------------|
     |                              |
     |-- 200 OK (개입 데이터) ------>|
     |                              |
     |                              |-- 화면 표시
     |                              |-- TTS 재생
     |                              |-- 로봇 동작 제어
     |                              |
     |<-- POST /intervention/complete|
     |   (실행 완료 보고)            |
     |                              |
     |-- 200 OK -------------------->|
```

---

## 데이터 모델 및 흐름

### 데이터베이스 스키마

#### 1. 로봇 등록 테이블

```sql
CREATE TABLE IF NOT EXISTS mdl_robot_registration (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 고유 ID',
    device_id VARCHAR(100) NOT NULL COMMENT '스마트폰 기기 ID',
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    device_info TEXT DEFAULT NULL COMMENT '기기 정보 (JSON)',
    location_info TEXT DEFAULT NULL COMMENT '위치 정보 (JSON)',
    capabilities TEXT DEFAULT NULL COMMENT '기능 정보 (JSON)',
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_sync_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY unique_robot_id (robot_id),
    UNIQUE KEY unique_device_id (device_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇 등록 정보';
```

#### 2. 센서 데이터 테이블

```sql
CREATE TABLE IF NOT EXISTS mdl_robot_sensor_data (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 ID',
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    sensor_data TEXT NOT NULL COMMENT '센서 데이터 (JSON)',
    processed_metrics TEXT DEFAULT NULL COMMENT '처리된 메트릭 (JSON)',
    session_id VARCHAR(100) DEFAULT NULL COMMENT '세션 ID',
    timestamp DATETIME NOT NULL COMMENT '측정 시각',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_robot_student (robot_id, student_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇 센서 데이터';
```

#### 3. 로봇 개입 실행 테이블

```sql
CREATE TABLE IF NOT EXISTS mdl_robot_intervention_execution (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    intervention_id VARCHAR(100) NOT NULL COMMENT '개입 ID',
    robot_id VARCHAR(100) NOT NULL COMMENT '로봇 ID',
    student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID',
    intervention_type VARCHAR(50) NOT NULL COMMENT '개입 유형',
    message_data TEXT NOT NULL COMMENT '메시지 데이터 (JSON)',
    robot_actions TEXT DEFAULT NULL COMMENT '로봇 동작 (JSON)',
    status ENUM('pending', 'sent', 'delivered', 'executing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    sent_at DATETIME DEFAULT NULL COMMENT '전송 시각',
    delivered_at DATETIME DEFAULT NULL COMMENT '수신 시각',
    executed_at DATETIME DEFAULT NULL COMMENT '실행 시각',
    completed_at DATETIME DEFAULT NULL COMMENT '완료 시각',
    execution_result TEXT DEFAULT NULL COMMENT '실행 결과 (JSON)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY unique_intervention_id (intervention_id),
    INDEX idx_robot_student (robot_id, student_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='로봇 개입 실행';
```

### 데이터 흐름

#### 1. 센서 데이터 수집 → 분석 → 개입 결정

```
[스마트폰 센서]
    ↓
[센서 데이터 수집]
    ↓
[POST /api/robot/sensor-data]
    ↓
[서버: Sensing Layer]
    ↓
[서버: Decision Layer]
    ↓
[개입 필요 여부 판단]
    ↓
[개입 필요 시 → mdl_robot_intervention_execution 테이블에 저장]
```

#### 2. 개입 메시지 전달 → 실행 → 완료 보고

```
[서버: 개입 메시지 생성]
    ↓
[mdl_robot_intervention_execution 테이블 저장]
    ↓
[스마트폰: GET /api/robot/intervention/pending]
    ↓
[스마트폰: 메시지 수신 및 표시]
    ↓
[로봇: 동작 실행 (LED, 모터)]
    ↓
[스마트폰: POST /api/robot/intervention/complete]
    ↓
[서버: 실행 결과 기록]
```

---

## 보안 고려사항

### 1. 인증 및 권한

**Moodle 세션 기반 인증**:
- 기존 Moodle 인증 시스템 활용
- `require_login()` 함수로 사용자 인증 확인
- 학생은 자신의 로봇만 접근 가능

**API 토큰 인증 (선택)**:
- 장기간 연결을 위한 API 토큰 발급
- 토큰 만료 시간 설정 (예: 30일)
- 토큰 갱신 메커니즘

### 2. 데이터 암호화

**전송 계층 보안**:
- 모든 통신은 HTTPS 사용 (TLS 1.2 이상)
- 인증서 검증 필수

**데이터 암호화**:
- 민감한 센서 데이터는 서버 저장 시 암호화
- 위치 정보는 해시화 또는 익명화

### 3. 개인정보 보호

**수집 데이터 최소화**:
- 학습에 필요한 최소한의 센서 데이터만 수집
- 얼굴 인식 데이터는 로컬 처리 후 메타데이터만 전송

**데이터 보관 기간**:
- 센서 데이터: 90일 보관 후 자동 삭제
- 개입 실행 기록: 1년 보관

### 4. 접근 제어

**로봇-학생 매핑**:
- 한 로봇은 한 학생에게만 할당
- 로봇 ID와 학생 ID 매핑 검증

**API 접근 제한**:
- IP 화이트리스트 (선택)
- Rate Limiting (분당 요청 수 제한)

---

## 로드맵

### Phase 1: 기본 통신 인프라 (1-2개월)

**목표**: 서버-스마트폰 앱 기본 통신 구축

**작업 항목**:
1. ✅ API 엔드포인트 구현 (`api/robot/*.php`)
2. ✅ 데이터베이스 스키마 생성 (`database/robot_*.sql`)
3. ✅ 로봇 등록 및 상태 관리 기능
4. ✅ 센서 데이터 수신 및 저장 기능
5. ✅ 기본 개입 메시지 전달 기능 (폴링 방식)

**산출물**:
- API 문서
- 데이터베이스 마이그레이션 스크립트
- 단위 테스트 코드

### Phase 2: 스마트폰 앱 개발 (2-3개월)

**목표**: 기본 스마트폰 앱 개발 및 로봇 제어 기능 구현

**작업 항목**:
1. ✅ 앱 기본 구조 설계
2. ✅ 서버 통신 모듈 구현
3. ✅ 센서 데이터 수집 모듈 구현
4. ✅ UI 렌더링 모듈 구현
5. ✅ TTS 모듈 구현
6. ✅ 로봇 제어 모듈 구현 (BLE/Serial)

**산출물**:
- 스마트폰 앱 (Android/iOS)
- 로봇 제어 라이브러리
- 사용자 매뉴얼

### Phase 3: 통합 및 테스트 (1개월)

**목표**: 서버-앱-로봇 통합 테스트 및 안정화

**작업 항목**:
1. ✅ End-to-End 통합 테스트
2. ✅ 성능 테스트 및 최적화
3. ✅ 보안 검증
4. ✅ 사용자 테스트 (파일럿)

**산출물**:
- 통합 테스트 리포트
- 성능 벤치마크 리포트
- 보안 검증 리포트

### Phase 4: 실시간 통신 및 고급 기능 (2-3개월)

**목표**: WebSocket 기반 실시간 통신 및 고급 기능 추가

**작업 항목**:
1. ⏳ WebSocket 서버 구현
2. ⏳ 실시간 개입 메시지 스트리밍
3. ⏳ 양방향 통신 (앱 → 서버 실시간 피드백)
4. ⏳ 오프라인 모드 지원
5. ⏳ 데이터 동기화 메커니즘

**산출물**:
- WebSocket 서버 구현
- 오프라인 모드 구현
- 동기화 메커니즘 문서

### Phase 5: 확장 및 최적화 (지속적)

**목표**: 시스템 확장 및 성능 최적화

**작업 항목**:
1. ⏳ 다중 로봇 지원 확장
2. ⏳ 로봇 그룹 관리 기능
3. ⏳ 고급 센서 데이터 분석
4. ⏳ 머신러닝 기반 개입 최적화

---

## 기술 스택 및 구현 가이드

### 서버 측 (기존 시스템 확장)

**기술 스택**:
- **언어**: PHP 7.1.9
- **프레임워크**: Moodle 3.7
- **데이터베이스**: MySQL 5.7
- **API**: RESTful API (향후 WebSocket)

**구현 가이드**:

#### 1. API 엔드포인트 생성

**파일 위치**: `alt42/orchestration/api/robot/`

**예시**: `register.php`
```php
<?php
// File: api/robot/register.php (Line 1)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'location' => __FILE__ . ':' . __LINE__
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // 검증 로직
    // 데이터베이스 저장 로직
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'location' => __FILE__ . ':' . __LINE__
    ]);
}
?>
```

#### 2. 데이터베이스 마이그레이션

**파일 위치**: `alt42/orchestration/database/migrations/`

**예시**: `003_robot_tables.sql`
```sql
-- File: database/migrations/003_robot_tables.sql
-- 로봇 관련 테이블 생성

CREATE TABLE IF NOT EXISTS mdl_robot_registration (
    -- 테이블 정의 (위 스키마 참조)
);
```

### 스마트폰 앱 측

**기술 스택**:
- **플랫폼**: Android (Kotlin/Java), iOS (Swift)
- **통신**: Retrofit (Android), URLSession (iOS)
- **BLE**: Android BLE API, Core Bluetooth (iOS)
- **TTS**: Android TTS, AVSpeechSynthesizer (iOS)

**구현 가이드**:

#### 1. 통신 모듈 (Android 예시)

```kotlin
// RobotApiClient.kt
class RobotApiClient {
    private val baseUrl = "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/api/robot/"
    
    suspend fun registerRobot(robotInfo: RobotInfo): ApiResponse<RobotRegistration> {
        // Retrofit을 사용한 API 호출
    }
    
    suspend fun sendSensorData(sensorData: SensorData): ApiResponse<SensorDataResponse> {
        // 센서 데이터 전송
    }
    
    suspend fun getPendingInterventions(robotId: String): ApiResponse<List<Intervention>> {
        // 대기 중인 개입 메시지 조회
    }
}
```

#### 2. 로봇 제어 모듈 (BLE 예시)

```kotlin
// RobotController.kt
class RobotController(private val context: Context) {
    private var bluetoothGatt: BluetoothGatt? = null
    
    fun connectToRobot(deviceAddress: String) {
        // BLE 연결
    }
    
    fun controlLED(pattern: LEDPattern) {
        // LED 제어 명령 전송
    }
    
    fun controlMotor(action: MotorAction) {
        // 모터 제어 명령 전송
    }
}
```

### 로봇 본체 측

**기술 스택**:
- **마이크로컨트롤러**: ESP32 또는 Arduino
- **통신**: BLE 또는 Serial (USB)
- **모터 제어**: 서보 모터 또는 스테퍼 모터
- **LED 제어**: WS2812B 또는 일반 LED

**구현 가이드**:

#### 1. BLE 통신 모듈 (Arduino 예시)

```cpp
// robot_ble.ino
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>

BLEServer* pServer = NULL;
BLECharacteristic* pCharacteristic = NULL;

void setup() {
    BLEDevice::init("MathkingRobot");
    pServer = BLEDevice::createServer();
    
    BLEService *pService = pServer->createService(SERVICE_UUID);
    pCharacteristic = pService->createCharacteristic(
        CHARACTERISTIC_UUID,
        BLECharacteristic::PROPERTY_READ | BLECharacteristic::PROPERTY_WRITE
    );
    
    pService->start();
    pServer->getAdvertising()->start();
}

void loop() {
    // 명령 수신 및 처리
    String command = pCharacteristic->getValue();
    executeCommand(command);
}
```

---

## 테스트 전략

### 1. 단위 테스트

**서버 측**:
- API 엔드포인트 단위 테스트
- 데이터베이스 쿼리 테스트
- 비즈니스 로직 테스트

**스마트폰 앱 측**:
- 통신 모듈 테스트
- 센서 데이터 수집 모듈 테스트
- 로봇 제어 모듈 테스트

### 2. 통합 테스트

**서버-앱 통합**:
- API 통신 테스트
- 데이터 동기화 테스트
- 개입 메시지 전달 테스트

**앱-로봇 통합**:
- BLE/Serial 통신 테스트
- 로봇 동작 제어 테스트
- LED 제어 테스트

### 3. End-to-End 테스트

**시나리오**:
1. 로봇 등록 → 센서 데이터 전송 → 개입 메시지 수신 → 실행 → 완료 보고
2. 오프라인 모드 → 온라인 복구 → 데이터 동기화
3. 다중 로봇 동시 통신

### 4. 성능 테스트

**지표**:
- API 응답 시간 (목표: < 500ms)
- 센서 데이터 처리 시간 (목표: < 200ms)
- 개입 메시지 전달 시간 (목표: < 1초)

---

## 배포 및 운영

### 배포 체크리스트

#### 서버 측

- [ ] 데이터베이스 마이그레이션 실행
- [ ] API 엔드포인트 배포
- [ ] SSL 인증서 설정
- [ ] 로깅 설정
- [ ] 모니터링 설정

#### 스마트폰 앱 측

- [ ] 앱 빌드 및 서명
- [ ] 앱스토어 제출 (Google Play, App Store)
- [ ] 버전 관리 설정
- [ ] 크래시 리포팅 설정

#### 로봇 본체 측

- [ ] 펌웨어 업데이트
- [ ] BLE/Serial 통신 테스트
- [ ] 하드웨어 검증

### 모니터링

**서버 측 모니터링**:
- API 응답 시간
- 에러 발생률
- 데이터베이스 성능
- 로봇 연결 상태

**앱 측 모니터링**:
- 앱 크래시율
- API 통신 성공률
- 센서 데이터 수집률
- 로봇 제어 성공률

### 운영 가이드

**일상 운영**:
- 로봇 상태 모니터링
- 센서 데이터 품질 확인
- 개입 메시지 전달 확인

**문제 해결**:
- 로봇 연결 문제: BLE/Serial 재연결
- API 통신 문제: 네트워크 상태 확인
- 데이터 동기화 문제: 수동 동기화 트리거

---

## 부록

### A. API 명세서 (상세)

✅ [API 명세서](ROBOT_API_SPEC.md) - 상세 API 엔드포인트 명세

### B. 구현 로드맵

✅ [구현 로드맵](ROBOT_IMPLEMENTATION_ROADMAP.md) - 단계별 구현 계획 및 작업 항목

### C. 데이터베이스 스키마

✅ [데이터베이스 마이그레이션 스크립트](../database/migrations/003_robot_tables.sql) - 데이터베이스 테이블 정의

### D. 하드웨어 명세서

⏳ 하드웨어 명세서 (향후 작성 예정)

### D. 용어집

- **로봇**: 스마트폰을 거치한 로봇 형태의 거치대
- **스마트폰 앱**: 로봇의 머리 역할을 하는 모바일 애플리케이션
- **개입 (Intervention)**: 학습 상태에 따른 AI 기반 개입 메시지
- **센서 데이터**: 스마트폰의 카메라, 마이크, 가속도계 등에서 수집된 데이터

---

**문서 상태**: ✅ 설계 완료  
**다음 단계**: Phase 1 구현 시작  
**담당자**: 개발팀  
**문의**: 프로젝트 관리자

