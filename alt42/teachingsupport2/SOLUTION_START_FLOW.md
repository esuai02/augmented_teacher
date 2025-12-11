# 풀이 시작하기 버튼 클릭 시 실행 순서 명세서

## 개요
`teachingagent.php`에서 "풀이 시작하기" 버튼(`startTutoringBtn`) 클릭 시 실행되는 전체 프로세스의 상세 명세서입니다.

## 실행 순서

### 1. 버튼 클릭 이벤트 핸들러 (라인 1276)
- **위치**: `startTutoringBtn.addEventListener('click', async () => {...})`
- **검증 단계**:
  - `uploadedFile` 존재 여부 확인 (라인 1277)
  - `problemType.value` 선택 여부 확인 (라인 1279-1282)
- **버튼 비활성화**: `startTutoringBtn.disabled = true` (라인 1284)

### 2. 선생님 모드 프로세스 (라인 1306-1358)

#### 2-1. UI 초기화
- **위치**: 라인 1308-1309
- **작업**:
  - `solutionLoading.classList.add('active')` - 로딩 표시 활성화
  - `solutionContent.textContent = ''` - 해설 영역 초기화

#### 2-2. 1단계: 문제 분석 (`analyzeProblem()`)
- **위치**: 라인 1320-1322
- **상태 메시지**: "1/5 문제 분석 중..."
- **함수**: `analyzeProblem()` (라인 1363-1513)

**세부 실행 순서**:
1. **상호작용 레코드 생성/확인** (라인 1364-1448)
   - `currentInteractionId` 존재 여부 확인
   - 없으면 새로운 상호작용 레코드 생성 (`save_interaction.php` 호출)
   - 있으면 기존 레코드에 수정 프롬프트 업데이트

2. **FormData 준비** (라인 1450-1474)
   - 이미지 파일 (`uploadedFile`)
   - 문제 유형 (`problemType.value`)
   - 선생님 ID, 학생 ID
   - `interactionId` (있는 경우)
   - 추가 프롬프트 또는 수정 프롬프트

3. **문제 분석 API 호출** (라인 1477-1480)
   - 엔드포인트: `analyze_problem.php`
   - 메서드: POST
   - FormData 전송

4. **해설 저장** (라인 1484-1502)
   - 응답에서 `solution`과 `imageUrl` 추출
   - `currentSolution`, `currentImageUrl` 전역 변수에 저장
   - `save_interaction.php`로 해설 업데이트

5. **해설 표시** (라인 1504-1509)
   - `formatMathContent()`로 HTML 포맷팅
   - `solutionContent.innerHTML`에 삽입
   - MathJax로 수식 렌더링

#### 2-3. 2단계: 나레이션 생성 (`generateNarration()`)
- **위치**: 라인 1324-1326
- **상태 메시지**: "2/5 설명 나레이션 생성 중..."
- **함수**: `generateNarration()` (라인 1516-1567)

**세부 실행 순서**:
1. **검증** (라인 1517-1518)
   - `currentSolution` 존재 확인
   - `currentInteractionId` 존재 확인

2. **나레이션 생성 API 호출** (라인 1521-1531)
   - 엔드포인트: `generate_dialog_narration.php`
   - 메서드: POST
   - 파라미터:
     - `interactionId`
     - `solution`
     - `generateTTS: 'true'`

3. **나레이션 저장 및 표시** (라인 1535-1542)
   - `currentNarration` 전역 변수에 저장
   - `narrationText.textContent`에 표시
   - `narrationContent.style.display = 'block'`로 표시

4. **Step-by-step TTS 모달 트리거** (라인 1544-1563)
   - `sectionFiles`가 있으면 Step Player 모달 자동 열기
   - `window.currentStepAudioData`에 섹션 데이터 저장
   - `StepPlayer.open(currentInteractionId)` 호출

#### 2-4. 3단계: 음성 생성 (`generateTTS()`)
- **위치**: 라인 1328-1330
- **상태 메시지**: "3/5 음성 생성 중..."
- **함수**: `generateTTS()` (라인 1570-1619)

**세부 실행 순서**:
1. **검증** (라인 1571-1572)
   - `currentNarration` 존재 확인
   - `currentInteractionId` 존재 확인

2. **TTS 생성 API 호출** (라인 1574-1583)
   - 엔드포인트: `generate_tts.php`
   - 메서드: POST
   - 파라미터:
     - `text`: `currentNarration`
     - `voice`: 'nova' (여성 목소리)

3. **오디오 URL 저장** (라인 1587-1606)
   - `currentAudioUrl` 전역 변수에 저장
   - `save_interaction.php`로 오디오 URL 업데이트

4. **오디오 플레이어 준비** (라인 1608-1615)
   - Audio 객체 생성
   - `audioElement.src` 설정
   - 재생 버튼 표시 (`playAudioBtn.style.display = 'inline-flex'`)

#### 2-5. 4단계: 메시지 발송 (`sendMessage()`)
- **위치**: 라인 1332-1334
- **상태 메시지**: "4/5 학생에게 메시지 발송 중..."
- **함수**: `sendMessage()` (라인 1622-1702)

**세부 실행 순서**:
1. **검증** (라인 1623-1626)
   - `currentInteractionId` 존재 확인

2. **자동 메시지 생성** (라인 1629-1635)
   - 문제 유형, 해설 완료 시간, 음성 설명 포함 메시지 생성

3. **메시지 전송 API 호출** (라인 1638-1651)
   - 엔드포인트: `send_message.php`
   - 메서드: POST
   - 파라미터:
     - `studentId`
     - `teacherId`
     - `interactionId`
     - `message`
     - `solutionText`
     - `audioUrl`

4. **상태 업데이트** (라인 1660-1701)
   - `save_interaction.php`로 상태를 'completed'로 업데이트
   - DOM에서 처리한 항목 제거
   - 새로운 요청 목록 새로고침 (`loadNewRequests()`)

#### 2-6. 5단계: 완료
- **위치**: 라인 1336-1349
- **상태 메시지**: "5/5 완료 중..." → "✅ 하이튜터링 완료!"
- **작업**:
  - 추가 프롬프트 입력창 초기화
  - 완료 알림 표시

#### 2-7. 에러 처리 및 정리
- **위치**: 라인 1351-1358
- **작업**:
  - 에러 발생 시 콘솔 로그 및 알림 표시
  - `solutionLoading.classList.remove('active')` - 로딩 표시 제거
  - `startTutoringBtn.disabled = false` - 버튼 재활성화

## 관련 파일

### PHP 엔드포인트
1. `save_interaction.php` - 상호작용 레코드 생성/업데이트
2. `analyze_problem.php` - 문제 분석 및 해설 생성
3. `generate_dialog_narration.php` - 나레이션 생성
4. `generate_tts.php` - 음성 생성
5. `send_message.php` - 학생에게 메시지 전송
6. `get_new_requests.php` - 새로운 풀이요청 목록 조회

### 전역 변수
- `uploadedFile`: 업로드된 이미지 파일
- `currentSolution`: 생성된 해설 텍스트
- `currentNarration`: 생성된 나레이션 텍스트
- `currentAudioUrl`: 생성된 오디오 파일 URL
- `currentInteractionId`: 현재 상호작용 ID
- `currentImageUrl`: 문제 이미지 URL

## 주의사항
- 모든 단계는 순차적으로 실행되며, 이전 단계가 완료되어야 다음 단계로 진행됩니다.
- 각 단계에서 에러가 발생하면 전체 프로세스가 중단되고 에러 메시지가 표시됩니다.
- `currentInteractionId`가 없으면 1단계에서 자동으로 생성됩니다.
- Step-by-step TTS가 생성되면 자동으로 모달이 열립니다.

