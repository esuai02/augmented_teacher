# 📚 문서-시스템 자동 동기화 도구

> engine_config.php (SSOT)를 기준으로 문서들을 자동으로 동기화합니다.

## 🎯 목적

- **일관성 유지**: 코드와 문서가 항상 같은 정보를 반영
- **자동화**: 수동 동기화 작업 최소화
- **추적성**: 변경사항 자동 감지 및 리포트

## 📁 파일 구조

```
scripts/
├── parse_agents.py      # SSOT 파서 (engine_config.php → Python 객체)
├── check_doc_sync.py    # 동기화 상태 검사
├── sync_docs.py         # 문서 자동 업데이트
├── run_sync.bat         # Windows 배치 스크립트
├── run_sync.ps1         # PowerShell 스크립트
├── requirements.txt     # Python 의존성
└── README.md            # 이 문서
```

## 🚀 사용법

### 방법 1: 배치 스크립트 (권장)

```batch
# Windows CMD
cd alt42\orchestration\scripts
run_sync.bat
```

### 방법 2: PowerShell

```powershell
# 검사만
.\run_sync.ps1 -Check

# 동기화 수행
.\run_sync.ps1 -Sync

# 에이전트 목록 생성
.\run_sync.ps1 -Generate

# 변경 없이 미리보기
.\run_sync.ps1 -Sync -DryRun
```

### 방법 3: Python 직접 실행

```bash
# 동기화 상태 검사
python check_doc_sync.py

# 문서 동기화
python sync_docs.py

# 에이전트 목록 생성
python sync_docs.py --generate-list

# DRY RUN (변경 없이 확인)
python sync_docs.py --dry-run
```

## 🔄 동기화 흐름

```
┌─────────────────────────────────────────────────────────┐
│                    SSOT (진실의 원천)                     │
│                                                         │
│   engine_core/config/engine_config.php                  │
│   └─ AGENT_CONFIG 배열                                  │
│   └─ AGENT_CATEGORIES 배열                              │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│                   parse_agents.py                        │
│                                                         │
│   PHP 파일 파싱 → Python ParseResult 객체                │
└────────────────────────┬────────────────────────────────┘
                         │
         ┌───────────────┴───────────────┐
         ▼                               ▼
┌─────────────────┐           ┌─────────────────┐
│ check_doc_sync  │           │   sync_docs     │
│                 │           │                 │
│ 불일치 탐지     │           │ 자동 업데이트   │
│ 리포트 생성     │           │ 버전 갱신       │
└─────────────────┘           └─────────────────┘
         │                               │
         └───────────────┬───────────────┘
                         ▼
┌─────────────────────────────────────────────────────────┐
│                    동기화된 문서들                        │
│                                                         │
│   • SYSTEM_STATUS.yaml                                  │
│   • quantum-orchestration-design.md                     │
│   • quantum-learning-model.md                           │
│   • AGENT_LIST.md (자동 생성)                           │
└─────────────────────────────────────────────────────────┘
```

## 📋 검사 항목

| 항목 | 설명 | 심각도 |
|------|------|--------|
| 에이전트 수 | 문서의 에이전트 수가 SSOT와 일치하는지 | High |
| 에이전트 이름 | 모든 에이전트가 문서에 있는지 | Medium |
| 버전 정보 | last_updated가 최신인지 | Low |

## ⚙️ 설정

### SSOT 경로

기본값: `../agents/engine_core/config/engine_config.php`

커스텀 경로 사용:
```bash
python check_doc_sync.py --base-path /custom/path
```

## 🔧 트러블슈팅

### "engine_config.php not found"

```bash
# 경로 확인
ls ../agents/engine_core/config/

# 또는 절대 경로 지정
python check_doc_sync.py --base-path "C:\1 Project\augmented_teacher\alt42\orchestration"
```

### "yaml module not found"

```bash
pip install pyyaml
```

## 📝 출력 예시

### 동기화 완료 상태

```
============================================================
📋 문서-시스템 동기화 검사 결과
============================================================
검사 시간: 2025-12-08T15:30:00
SSOT 버전: 1.0.0
SSOT 에이전트 수: 22

✅ 모든 문서가 동기화되어 있습니다!
```

### 불일치 발견 시

```
============================================================
📋 문서-시스템 동기화 검사 결과
============================================================
검사 시간: 2025-12-08T15:30:00
SSOT 버전: 1.0.0
SSOT 에이전트 수: 22

⚠️ 2개의 동기화 이슈 발견

------------------------------------------------------------

🟠 HIGH (1개)
  • [SYSTEM_STATUS.yaml] mismatch: count: 22 → count: 21

🟡 MEDIUM (1개)
  • [quantum-learning-model.md] mismatch: 22단계 → 21단계
```

## 🔗 관련 문서

- [SYSTEM_STATUS.yaml](../Holarchy/0%20Docs/quantum%20modeling/SYSTEM_STATUS.yaml) - 시스템 현황
- [quantum-orchestration-design.md](../Holarchy/0%20Docs/quantum%20modeling/quantum-orchestration-design.md) - 설계 문서
- [quantum-learning-model.md](../Holarchy/0%20Docs/quantum%20modeling/quantum-learning-model.md) - 학습 모델

---

*자동 동기화 도구 v1.0 | 2025-12-08*

