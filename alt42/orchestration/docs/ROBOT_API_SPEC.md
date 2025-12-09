# 로봇-스마트폰 API 명세서

**문서 버전**: 1.0  
**작성일**: 2025-01-27  
**Base URL**: `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/api/robot/`

---

## 인증

### Moodle 세션 쿠키

모든 API 요청은 Moodle 세션 쿠키를 포함해야 합니다.

```
Cookie: MoodleSession=YOUR_SESSION_ID
```

### API 토큰 (선택)

장기간 연결을 위한 API 토큰 사용 가능.

```
X-API-Token: YOUR_API_TOKEN
```

---

## 공통 응답 형식

### 성공 응답

```json
{
  "success": true,
  "data": { /* 응답 데이터 */ }
}
```

### 에러 응답

```json
{
  "success": false,
  "error": "에러 메시지",
  "location": "파일경로:라인번호",
  "code": "ERROR_CODE"
}
```

### HTTP 상태 코드

- `200 OK`: 성공
- `201 Created`: 리소스 생성 성공
- `400 Bad Request`: 잘못된 요청
- `401 Unauthorized`: 인증 실패
- `403 Forbidden`: 권한 없음
- `404 Not Found`: 리소스 없음
- `500 Internal Server Error`: 서버 오류

---

## API 엔드포인트

### 1. 로봇 등록

**엔드포인트**: `POST /api/robot/register`

**설명**: 로봇-스마트폰 쌍을 시스템에 등록합니다.

**요청 본문**:
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

**응답** (201 Created):
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

**에러 응답**:
- `400`: 필수 필드 누락 또는 잘못된 형식
- `403`: 학생 ID가 현재 사용자와 일치하지 않음
- `409`: 이미 등록된 robot_id 또는 device_id

---

### 2. 센서 데이터 전송

**엔드포인트**: `POST /api/robot/sensor-data`

**설명**: 스마트폰 센서 데이터를 서버로 전송합니다.

**요청 본문**:
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

**응답** (200 OK):
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

**에러 응답**:
- `400`: 필수 필드 누락 또는 잘못된 형식
- `403`: robot_id가 현재 사용자에게 할당되지 않음
- `500`: 센서 데이터 처리 실패

---

### 3. 개입 메시지 조회 (폴링)

**엔드포인트**: `GET /api/robot/intervention/pending`

**설명**: 대기 중인 개입 메시지를 조회합니다. (폴링 방식)

**쿼리 파라미터**:
- `robot_id` (필수): 로봇 ID
- `student_id` (필수): 학생 ID
- `limit` (선택): 조회할 개입 수 (기본값: 10, 최대: 50)

**요청 예시**:
```
GET /api/robot/intervention/pending?robot_id=robot-001&student_id=123&limit=10
```

**응답** (200 OK):
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
    ],
    "total_count": 1
  }
}
```

**에러 응답**:
- `400`: 필수 쿼리 파라미터 누락
- `403`: robot_id가 현재 사용자에게 할당되지 않음

---

### 4. 개입 실행 완료 보고

**엔드포인트**: `POST /api/robot/intervention/complete`

**설명**: 개입 메시지 실행 완료 및 결과를 보고합니다.

**요청 본문**:
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

**응답** (200 OK):
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

**에러 응답**:
- `400`: 필수 필드 누락 또는 잘못된 형식
- `404`: intervention_id를 찾을 수 없음
- `403`: robot_id가 현재 사용자에게 할당되지 않음

---

### 5. 로봇 상태 조회

**엔드포인트**: `GET /api/robot/status`

**설명**: 로봇의 현재 상태를 조회합니다.

**쿼리 파라미터**:
- `robot_id` (필수): 로봇 ID

**요청 예시**:
```
GET /api/robot/status?robot_id=robot-001
```

**응답** (200 OK):
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

**에러 응답**:
- `400`: robot_id 파라미터 누락
- `404`: robot_id를 찾을 수 없음
- `403`: robot_id가 현재 사용자에게 할당되지 않음

---

### 6. 로봇 상태 업데이트

**엔드포인트**: `PUT /api/robot/status`

**설명**: 로봇의 상태를 업데이트합니다.

**요청 본문**:
```json
{
  "robot_id": "robot-001",
  "status": "active",
  "battery_level": 85,
  "connection_status": "online"
}
```

**응답** (200 OK):
```json
{
  "success": true,
  "data": {
    "robot_id": "robot-001",
    "status": "active",
    "updated_at": "2025-01-27T10:30:00Z"
  }
}
```

---

## 데이터 타입 정의

### RobotInfo

```typescript
interface RobotInfo {
  robot_id: string;
  device_info: DeviceInfo;
  student_id: number;
  location?: LocationInfo;
  capabilities: Capabilities;
}

interface DeviceInfo {
  device_id: string;
  device_model: string;
  os_version: string;
  app_version: string;
}

interface LocationInfo {
  latitude: number;
  longitude: number;
  address?: string;
}

interface Capabilities {
  camera: boolean;
  microphone: boolean;
  tts: boolean;
  led_control: boolean;
  motor_control: boolean;
}
```

### SensorData

```typescript
interface SensorData {
  robot_id: string;
  student_id: number;
  timestamp: string; // ISO 8601
  sensor_data: {
    camera?: {
      face_detected: boolean;
      attention_score: number; // 0-1
      emotion?: string;
    };
    microphone?: {
      ambient_noise_level: number; // dB
      voice_detected: boolean;
    };
    motion?: {
      acceleration: [number, number, number];
      gyroscope: [number, number, number];
      device_orientation: string;
    };
    screen?: {
      brightness: number; // 0-100
      is_active: boolean;
      last_interaction: string; // ISO 8601
    };
  };
  session_context?: {
    session_id: string;
    activity_type: string;
    duration_seconds: number;
  };
}
```

### Intervention

```typescript
interface Intervention {
  intervention_id: string;
  type: "micro_break" | "encouragement" | "reminder" | "question";
  priority: "low" | "medium" | "high";
  message: {
    text: string;
    tts_text: string;
    display_duration: number; // seconds
  };
  robot_actions?: {
    led_pattern?: string;
    motor_action?: string;
    animation?: string;
  };
  created_at: string; // ISO 8601
  expires_at: string; // ISO 8601
}
```

---

## Rate Limiting

모든 API 엔드포인트는 Rate Limiting이 적용됩니다.

- **기본 제한**: 분당 60회 요청
- **센서 데이터 전송**: 분당 10회 요청
- **개입 메시지 조회**: 분당 30회 요청

**Rate Limit 초과 시 응답** (429 Too Many Requests):
```json
{
  "success": false,
  "error": "Rate limit exceeded",
  "retry_after": 60
}
```

---

## 버전 관리

API 버전은 URL 경로에 포함됩니다.

현재 버전: `v1`

예시:
- `POST /api/robot/v1/register`
- `GET /api/robot/v1/intervention/pending`

향후 버전 변경 시 하위 호환성을 유지합니다.

---

## 에러 코드

| 코드 | 설명 |
|------|------|
| `INVALID_REQUEST` | 잘못된 요청 형식 |
| `MISSING_FIELD` | 필수 필드 누락 |
| `UNAUTHORIZED` | 인증 실패 |
| `FORBIDDEN` | 권한 없음 |
| `NOT_FOUND` | 리소스 없음 |
| `DUPLICATE_RESOURCE` | 중복 리소스 |
| `PROCESSING_ERROR` | 처리 오류 |
| `RATE_LIMIT_EXCEEDED` | Rate Limit 초과 |

---

**문서 상태**: ✅ 명세 완료  
**최종 업데이트**: 2025-01-27

