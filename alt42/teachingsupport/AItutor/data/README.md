# FileDB 데이터 디렉토리

로컬 파일을 DB처럼 사용하는 파일 기반 데이터베이스의 데이터 저장소입니다.

## 구조

```
data/
├── rule_contents/          # 룰 컨텐츠 테이블
│   ├── _schema.json        # 테이블 스키마
│   └── {id}.json          # 레코드 파일
├── interactions/           # 상호작용 히스토리 테이블
│   ├── _schema.json
│   └── {id}.json
├── ontology_data/          # 온톨로지 데이터 테이블
│   ├── _schema.json
│   └── {id}.json
├── student_contexts/       # 학생 컨텍스트 테이블
│   ├── _schema.json
│   └── {id}.json
├── generated_rules/        # 생성된 룰 테이블
│   ├── _schema.json
│   └── {id}.json
└── _indexes.json           # 전체 인덱스 파일
```

## 파일 형식

각 레코드는 JSON 파일로 저장됩니다:

```json
{
  "id": "rule_contents_1234567890_1234",
  "rule_id": "U0_R1",
  "type": "rule_verification",
  "title": "룰 검증",
  "content": {...},
  "metadata": {...},
  "created_at": "2025-01-27 10:00:00",
  "updated_at": "2025-01-27 10:00:00"
}
```

## 인덱스

인덱스는 `_indexes.json` 파일에 저장되며, 빠른 검색을 위해 사용됩니다.

```json
{
  "rule_contents": {
    "rule_id": {
      "U0_R1": ["id1", "id2"],
      "U0_R2": ["id3"]
    },
    "type": {
      "rule_verification": ["id1", "id3"]
    }
  }
}
```

## 주의사항

- 이 디렉토리는 자동으로 생성됩니다
- 파일은 직접 수정하지 마세요
- 백업 시 이 디렉토리 전체를 복사하세요
- 권한은 755로 설정되어야 합니다

