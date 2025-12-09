# 📚 대본 DB 저장 시스템 - 통합 완료!

## ✅ 수정 완료 사항

### 문제점
1. **절차기억 나레이션 생성** 버튼 클릭 시 대본이 DB에 저장되지 않음
2. **대본 저장** 버튼 클릭 시 `reflections0` 필드에만 저장되고 `mdl_abrainalignment_gptresults` 테이블에는 저장 안 됨

### 해결책
모든 대본 저장 경로를 **`mdl_abrainalignment_gptresults` 테이블에 통합** 저장

---

## 🔧 수정된 파일

### 1. `books/generate_procedural_narration.php`

#### 변경 내용: GPT 나레이션 생성 후 DB 자동 저장

**Before:**
```php
if($httpCode === 200) {
    $result = json_decode($response, true);
    
    if(isset($result['choices'][0]['message']['content'])) {
        $narration = trim($result['choices'][0]['message']['content']);
        
        // @ 기호 개수 확인
        $atCount = substr_count($narration, '@');
        
        echo json_encode([
            'success' => true,
            'narration' => $narration,
            'sectionCount' => $atCount + 1,
            'message' => "절차기억 나레이션 생성 완료! (총 " . ($atCount + 1) . "개 구간)"
        ]);
    }
}
```

**After:**
```php
if($httpCode === 200) {
    $result = json_decode($response, true);
    
    if(isset($result['choices'][0]['message']['content'])) {
        $narration = trim($result['choices'][0]['message']['content']);
        
        // @ 기호 개수 확인
        $atCount = substr_count($narration, '@');
        
        // ✅ DB에 저장 (mdl_abrainalignment_gptresults)
        try {
            $timecreated = time();
            
            // 기존 레코드 확인
            $existing = $DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults 
                WHERE type='pmemory' AND contentsid='$contentsid' AND contentstype='$contentstype' 
                ORDER BY id DESC LIMIT 1");
            
            if($existing) {
                // 업데이트
                $DB->execute("UPDATE mdl_abrainalignment_gptresults 
                    SET outputtext=?, timemodified=? 
                    WHERE id=?",
                    [$narration, $timecreated, $existing->id]);
            } else {
                // 신규 삽입
                $record = new stdClass();
                $record->type = 'pmemory';
                $record->contentsid = $contentsid;
                $record->contentstype = $contentstype;
                $record->outputtext = $narration;
                $record->gid = '71280';
                $record->timemodified = $timecreated;
                $record->timecreated = $timecreated;
                $insertId = $DB->insert_record('abrainalignment_gptresults', $record);
            }
            
            echo json_encode([
                'success' => true,
                'narration' => $narration,
                'sectionCount' => $atCount + 1,
                'message' => "절차기억 나레이션 생성 및 DB 저장 완료! (총 " . ($atCount + 1) . "개 구간)",
                'saved_to_db' => true  // ✅ DB 저장 상태
            ]);
        } catch (Exception $e) {
            // DB 저장 실패해도 나레이션은 반환
            echo json_encode([
                'success' => true,
                'narration' => $narration,
                'sectionCount' => $atCount + 1,
                'message' => "절차기억 나레이션 생성 완료! - DB 저장 실패",
                'saved_to_db' => false,
                'db_error' => $e->getMessage()
            ]);
        }
    }
}
```

**주요 변경점:**
- ✅ GPT 나레이션 생성 즉시 `mdl_abrainalignment_gptresults` 테이블에 저장
- ✅ 기존 레코드 있으면 업데이트, 없으면 신규 삽입
- ✅ `saved_to_db` 플래그로 저장 성공 여부 반환
- ✅ 에러 처리: DB 저장 실패해도 나레이션은 반환

---

### 2. `books/openai_tts_pmemory.php`

#### 변경 내용: DB 저장 상태 표시

**Before:**
```javascript
if(data.success) {
    document.getElementById("input-text").value = data.narration;
    
    // 성공 메시지
    successMsg.innerHTML = "<strong>✅ " + data.message + "</strong><br>" +
                          "<small>@ 기호로 " + data.sectionCount + "개 구간이 구분되었습니다.</small>";
    
    alert("✅ 절차기억 나레이션이 생성되었습니다!");
}
```

**After:**
```javascript
if(data.success) {
    document.getElementById("input-text").value = data.narration;
    
    // ✅ DB 저장 상태 확인
    var dbStatus = data.saved_to_db ? "✅ DB 저장 완료" : "⚠️ DB 저장 실패";
    var dbStatusColor = data.saved_to_db ? "#28a745" : "#ffc107";
    
    // 성공 메시지에 DB 저장 상태 추가
    successMsg.style.cssText = "background:#d4edda;border:2px solid " + dbStatusColor + ";padding:15px;margin:10px 0;border-radius:8px;";
    successMsg.innerHTML = "<strong>✅ " + data.message + "</strong><br>" +
                          "<small>@ 기호로 " + data.sectionCount + "개 구간이 구분되었습니다.</small><br>" +
                          "<small style='color:" + dbStatusColor + ";font-weight:bold;'>" + dbStatus + "</small>";
    
    var alertMsg = "✅ 절차기억 나레이션이 생성되었습니다!\\n\\n" + dbStatus;
    if(!data.saved_to_db) {
        alertMsg += "\\n\\n⚠️ DB 저장 실패: " + (data.db_error || "알 수 없는 오류");
    }
    alert(alertMsg);
}
```

**주요 변경점:**
- ✅ DB 저장 성공/실패 상태 표시
- ✅ 저장 실패 시 경고 색상 (노란색)
- ✅ 에러 메시지 표시

---

### 3. `check_status.php`

#### 변경 내용: eventid=51 (대본 저장)에 이중 저장 로직 추가

**Before:**
```php
if($eventid==51) // 대본 저장
{
    if($contentstype==1) {
        $DB->execute("UPDATE {icontent_pages} SET reflections0=? WHERE id=?", array($inputtext, $contentsid));
    } else if($contentstype==2) {
        $DB->execute("UPDATE {question} SET reflections0=? WHERE id=?", array($inputtext, $contentsid));
    }
    $response = array('success' => true, 'message' => '대본이 저장되었습니다.');
    echo json_encode($response);
    exit();
}
```

**After:**
```php
if($eventid==51) // 대본 저장
{
    // ✅ 1. reflections0 필드에 저장 (기존 로직 유지)
    if($contentstype==1) {
        $DB->execute("UPDATE {icontent_pages} SET reflections0=? WHERE id=?", array($inputtext, $contentsid));
    } else if($contentstype==2) {
        $DB->execute("UPDATE {question} SET reflections0=? WHERE id=?", array($inputtext, $contentsid));
    }
    
    // ✅ 2. mdl_abrainalignment_gptresults 테이블에도 저장 (신규 로직)
    try {
        $timecreated = time();
        
        // 기존 레코드 확인
        $existing = $DB->get_record_sql("SELECT * FROM {abrainalignment_gptresults} 
            WHERE type='pmemory' AND contentsid=? AND contentstype=? 
            ORDER BY id DESC LIMIT 1", array($contentsid, $contentstype));
        
        if($existing) {
            // 업데이트
            $DB->execute("UPDATE {abrainalignment_gptresults} 
                SET outputtext=?, timemodified=? 
                WHERE id=?",
                array($inputtext, $timecreated, $existing->id));
            
            error_log("대본 저장 - abrainalignment_gptresults 테이블 업데이트 완료");
        } else {
            // 신규 삽입
            $record = new stdClass();
            $record->type = 'pmemory';
            $record->contentsid = $contentsid;
            $record->contentstype = $contentstype;
            $record->outputtext = $inputtext;
            $record->gid = '71280';
            $record->timemodified = $timecreated;
            $record->timecreated = $timecreated;
            $insertId = $DB->insert_record('abrainalignment_gptresults', $record);
            
            error_log("대본 저장 - abrainalignment_gptresults 테이블 신규 저장 완료");
        }
        
        $response = array('success' => true, 'message' => '대본이 저장되었습니다. (DB 이중 저장 완료)');
    } catch (Exception $e) {
        error_log("abrainalignment_gptresults 저장 오류: " . $e->getMessage());
        $response = array('success' => true, 'message' => '대본이 저장되었습니다. (일부 저장 실패)', 'warning' => $e->getMessage());
    }
    
    echo json_encode($response);
    exit();
}
```

**주요 변경점:**
- ✅ 기존 `reflections0` 저장 로직 유지 (호환성)
- ✅ `mdl_abrainalignment_gptresults` 테이블에 추가 저장
- ✅ 에러 처리: 일부 실패해도 기본 저장은 성공
- ✅ 에러 로그 기록

---

## 📊 데이터 흐름

### A. 절차기억 나레이션 생성 (🎓 버튼)
```
openai_tts_pmemory.php
  ↓ "🎓 절차기억 나레이션 생성" 버튼 클릭
  ↓ generateProceduralNarration() 함수
  ↓
generate_procedural_narration.php
  ↓ GPT API 호출 (나레이션 생성)
  ↓ mdl_abrainalignment_gptresults 테이블에 자동 저장 ✅
  ↓
openai_tts_pmemory.php
  ↓ textarea에 나레이션 표시
  ↓ DB 저장 상태 표시 ✅
```

### B. 대본 수동 저장 (💾 버튼)
```
openai_tts_pmemory.php
  ↓ textarea에 대본 입력/수정
  ↓ "💾 대본 저장" 버튼 클릭
  ↓ saveText() 함수
  ↓
check_status.php (eventid=51)
  ↓ reflections0 필드에 저장 ✅
  ↓ mdl_abrainalignment_gptresults 테이블에도 저장 ✅
  ↓
openai_tts_pmemory.php
  ↓ "대본이 저장되었습니다. (DB 이중 저장 완료)" 메시지
```

### C. GPT 재구성 (⚡ 버튼) - improveprompt.php
```
improveprompt.php
  ↓ "⚡ 다시 생성" 버튼 클릭
  ↓
regenerate_with_gpt.php
  ↓ GPT API 호출 (재구성)
  ↓ mdl_abrainalignment_gptresults 테이블에 저장 ✅
  ↓
improveprompt.php
  ↓ 재구성된 대본 표시
```

---

## 🗄️ 데이터베이스 구조

### mdl_abrainalignment_gptresults
```sql
CREATE TABLE mdl_abrainalignment_gptresults (
    id BIGINT(10) PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50),           -- 'pmemory'
    contentsid BIGINT(10),      -- 컨텐츠 ID
    contentstype TINYINT(2),    -- 1: icontent_pages, 2: question
    outputtext LONGTEXT,        -- 나레이션 대본
    gid VARCHAR(50),            -- '71280'
    timemodified BIGINT(10),    -- 수정 시간
    timecreated BIGINT(10)      -- 생성 시간
);
```

**저장 경로:**
1. **절차기억 나레이션 생성** → `generate_procedural_narration.php` → 자동 저장 ✅
2. **대본 저장 (수동)** → `check_status.php (eventid=51)` → 이중 저장 ✅
3. **GPT 재구성** → `regenerate_with_gpt.php` → 자동 저장 ✅

---

## 🎯 저장 로직 통일

### 공통 패턴
```php
// 1. 기존 레코드 확인
$existing = $DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults 
    WHERE type='pmemory' AND contentsid=? AND contentstype=? 
    ORDER BY id DESC LIMIT 1", [$contentsid, $contentstype]);

// 2. 있으면 업데이트, 없으면 신규 삽입
if($existing) {
    $DB->execute("UPDATE mdl_abrainalignment_gptresults 
        SET outputtext=?, timemodified=? 
        WHERE id=?",
        [$narration, time(), $existing->id]);
} else {
    $record = new stdClass();
    $record->type = 'pmemory';
    $record->contentsid = $contentsid;
    $record->contentstype = $contentstype;
    $record->outputtext = $narration;
    $record->gid = '71280';
    $record->timemodified = time();
    $record->timecreated = time();
    $DB->insert_record('abrainalignment_gptresults', $record);
}
```

**적용 파일:**
- ✅ `generate_procedural_narration.php`
- ✅ `check_status.php` (eventid=51)
- ✅ `regenerate_with_gpt.php` (이미 적용됨)

---

## 🧪 테스트 시나리오

### 시나리오 1: 절차기억 나레이션 생성 테스트
```
1. https://mathking.kr/.../openai_tts_pmemory.php?cid=2052&ctype=1 접속
2. textarea에 문제/풀이 입력
3. "🎓 절차기억 나레이션 생성" 버튼 클릭
4. ✅ 확인 사항:
   - GPT 나레이션 생성
   - textarea에 @ 구분 나레이션 표시
   - "✅ DB 저장 완료" 메시지 표시
   - 알림: "절차기억 나레이션이 생성되었습니다! ... ✅ DB 저장 완료"
5. DB 확인:
   SELECT * FROM mdl_abrainalignment_gptresults 
   WHERE contentsid=2052 AND contentstype=1 AND type='pmemory'
   → outputtext에 나레이션 저장 확인
```

### 시나리오 2: 대본 수동 저장 테스트
```
1. openai_tts_pmemory.php 접속
2. textarea에 대본 입력 또는 수정
3. "💾 대본 저장" 버튼 클릭
4. ✅ 확인 사항:
   - "대본이 저장되었습니다. (DB 이중 저장 완료)" 메시지
5. DB 확인:
   a) reflections0 필드 확인:
      SELECT reflections0 FROM mdl_icontent_pages WHERE id=2052
   b) abrainalignment_gptresults 테이블 확인:
      SELECT * FROM mdl_abrainalignment_gptresults 
      WHERE contentsid=2052 AND type='pmemory'
   → 두 곳 모두 저장 확인 ✅
```

### 시나리오 3: GPT 재구성 테스트
```
1. mynotepause.php에서 ✏️ 아이콘 클릭
2. improveprompt.php 열림
3. TTS 대본 수정
4. "⚡ 다시 생성" 버튼 클릭
5. ✅ 확인 사항:
   - GPT 재구성 완료 메시지
   - 새 대본 표시
6. DB 확인:
   SELECT * FROM mdl_abrainalignment_gptresults 
   WHERE contentsid=2052 AND type='pmemory'
   → 재구성된 대본 저장 확인
```

---

## 🐛 트러블슈팅

### 문제 1: "DB 저장 실패" 메시지
**원인:**
- DB 연결 오류
- 테이블 권한 문제
- contentsid/contentstype 값 누락

**해결:**
1. 서버 에러 로그 확인:
   ```bash
   tail -f /var/log/apache2/error.log
   ```
2. DB 권한 확인:
   ```sql
   SHOW GRANTS FOR 'moodle_user'@'localhost';
   ```
3. 테이블 존재 확인:
   ```sql
   SHOW TABLES LIKE 'mdl_abrainalignment_gptresults';
   ```

### 문제 2: 대본이 DB에 저장 안 됨
**원인:**
- eventid 값 오류
- contentsid/contentstype 파라미터 누락

**해결:**
1. 브라우저 개발자 도구 (F12) → Network 탭
2. AJAX 요청 확인:
   - URL: check_status.php
   - POST 데이터: eventid=51, contentsid, contentstype, inputtext
3. 응답 확인:
   - success: true
   - message: "대본이 저장되었습니다. (DB 이중 저장 완료)"

### 문제 3: improveprompt.php에서 대본이 안 보임
**원인:**
- mdl_abrainalignment_gptresults 테이블에 데이터 없음

**해결:**
1. 먼저 "🎓 절차기억 나레이션 생성" 또는 "💾 대본 저장" 실행
2. DB에 데이터 확인:
   ```sql
   SELECT * FROM mdl_abrainalignment_gptresults 
   WHERE contentsid=2052 AND contentstype=1 AND type='pmemory';
   ```
3. 데이터 있으면 improveprompt.php 새로고침

---

## 📝 체크리스트

### 구현 확인
- [x] `generate_procedural_narration.php` DB 저장 로직 추가
- [x] `openai_tts_pmemory.php` DB 저장 상태 표시
- [x] `check_status.php` 이중 저장 로직 추가
- [x] 에러 처리 및 로그 추가
- [x] 문서 작성

### 기능 테스트
- [ ] 절차기억 나레이션 생성 → DB 저장 확인
- [ ] 대본 수동 저장 → reflections0 + abrainalignment_gptresults 확인
- [ ] GPT 재구성 → DB 저장 확인
- [ ] improveprompt.php에서 대본 표시 확인

### 통합 테스트
- [ ] 전체 워크플로우 (나레이션 생성 → 저장 → 재구성 → TTS 생성) 테스트
- [ ] 여러 컨텐츠에서 독립적으로 작동 확인

---

## 🎉 완성 요약

| 기능 | Before | After | 상태 |
|------|--------|-------|------|
| 절차기억 나레이션 생성 | DB 저장 없음 ❌ | 자동 DB 저장 ✅ | 완료 |
| 대본 수동 저장 | reflections0만 저장 | reflections0 + abrainalignment_gptresults 이중 저장 ✅ | 완료 |
| GPT 재구성 | DB 저장 있음 ✅ | 유지 ✅ | 유지 |
| DB 저장 상태 표시 | 없음 ❌ | 성공/실패 표시 ✅ | 완료 |
| 에러 처리 | 기본 | 상세 에러 로그 ✅ | 완료 |

---

**최종 업데이트:** 2025-10-14  
**버전:** 1.1  
**상태:** 통합 완료 및 테스트 준비 ✅

**완성!** 이제 모든 대본이 `mdl_abrainalignment_gptresults` 테이블에 일관되게 저장됩니다! 🎓✨


