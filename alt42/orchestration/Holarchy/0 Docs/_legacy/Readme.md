# 📦 Legacy Documents

> **이 폴더의 문서들은 더 이상 활성 시스템에서 사용되지 않습니다.**

## 이동된 문서

| 파일 | 이유 | 대체 문서 |
|------|------|-----------|
| `Readme.md` | Holon 형식 아님 | `holons/00-holarchy-overview.md` |
| `WXSPERTA.md` | Holon 형식 아님 | `holons/wxsperta-framework.md` |
| `양식/` | 구식 템플릿 | `.cursor/rules`의 holon_template |

## 주의사항

- 이 폴더의 문서들은 검증 대상이 아닙니다
- 새 문서 작성 시 이 폴더의 템플릿을 사용하지 마세요
- 참고용으로만 보존됩니다

## 현재 시스템

```
0 Docs/
├── holons/           ← 활성 문서 (Holon JSON 형식)
├── meetings/         ← 회의 기록
├── decisions/        ← 의사결정
├── tasks/            ← 작업 항목
└── _legacy/          ← 이 폴더 (비활성)
```

## 새 문서 생성 방법

```bash
cd "0 Docs/holons"
python _cli.py create <type> "<title>" --parent <parent_id>
```
