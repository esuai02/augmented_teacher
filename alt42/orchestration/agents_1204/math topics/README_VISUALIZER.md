# OWL 온톨로지 시각화 도구

수학 주제 온톨로지 파일을 인터랙티브하게 시각화하는 웹 도구입니다.

## 📁 파일 구조

```
math topics/
├── owl_parser.py              # OWL 파일 파서 (Python)
├── ontology_visualizer.php    # 메인 웹 인터페이스
├── ontology_visualizer.js     # D3.js 시각화 로직
├── ontology_visualizer.css    # 스타일시트
└── *.owl                      # OWL 온톨로지 파일들
```

## 🚀 사용 방법

### 1. 웹 인터페이스 사용

1. 브라우저에서 다음 URL 접속:
   ```
   https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/math%20topics/ontology_visualizer.php
   ```

2. 드롭다운에서 원하는 OWL 파일 선택
   - 예: `1 numbers_ontology.owl`

3. 자동으로 파싱 및 시각화 실행
   - 첫 로드 시 Python 스크립트가 자동 실행되어 JSON 변환
   - 이후에는 캐시된 JSON 파일 사용

### 2. Python 스크립트 직접 사용

```bash
# 기본 사용법
python3 owl_parser.py 1 numbers_ontology.owl

# 출력 파일 지정
python3 owl_parser.py 1 numbers_ontology.owl output.json
```

## 🎨 기능

### 시각화 기능
- **Force 레이아웃**: 기본 물리 시뮬레이션 기반 배치
- **계층형 레이아웃**: Stage별로 수평 배치
- **원형 레이아웃**: 원형으로 노드 배치
- **줌/팬**: 마우스 휠로 확대/축소, 드래그로 이동
- **노드 드래그**: 노드를 자유롭게 이동 가능

### 인터랙션
- **호버 효과**: 노드에 마우스 오버 시 관련 노드 강조
- **툴팁**: 노드 정보 표시 (라벨, Stage, 설명)
- **클릭**: 노드 클릭 시 URL이 있으면 새 탭에서 열기
- **라벨 토글**: 노드 라벨 표시/숨김

### 필터링
- **관계 타입별 색상**:
  - 파란색: `precedes` 관계 (선행 관계)
  - 주황색: `dependsOn` 관계 (의존 관계)

## 🔧 기술 스택

- **백엔드**: PHP 7.1.9 (Moodle 통합)
- **파서**: Python 3 (xml.etree.ElementTree)
- **시각화**: D3.js v7
- **스타일**: 순수 CSS

## 📊 데이터 구조

### 입력 (OWL)
```xml
<owl:NamedIndividual rdf:about="http://example.org/adaptive-review#만">
    <rdfs:label xml:lang="ko">만</rdfs:label>
    <ar:stage>1</ar:stage>
    <ar:hasURL>...</ar:hasURL>
    <ar:description>...</ar:description>
</owl:NamedIndividual>

<rdf:Description rdf:about="...">
    <ar:precedes rdf:resource="..."/>
    <ar:dependsOn rdf:resource="..."/>
</rdf:Description>
```

### 출력 (JSON)
```json
{
  "nodes": [
    {
      "id": "만",
      "label": "만",
      "stage": 1,
      "url": "...",
      "description": "...",
      "group": 1
    }
  ],
  "links": [
    {
      "source": "만",
      "target": "다섯_자리_수",
      "type": "precedes",
      "value": 1
    }
  ],
  "metadata": {
    "filename": "1 numbers_ontology.owl",
    "title": "...",
    "comment": "..."
  }
}
```

## 🐛 문제 해결

### Python을 찾을 수 없음
- 서버에 Python 3가 설치되어 있는지 확인
- `python3 --version` 명령어로 확인
- 필요시 PHP 코드에서 Python 경로 수정

### 파싱 오류
- OWL 파일이 올바른 형식인지 확인
- Python 스크립트를 직접 실행하여 오류 메시지 확인
- 파일 권한 확인 (읽기 권한 필요)

### 시각화가 표시되지 않음
- 브라우저 콘솔에서 JavaScript 오류 확인
- D3.js 라이브러리가 로드되었는지 확인
- JSON 파일이 올바르게 생성되었는지 확인

## 📝 참고사항

- JSON 파일은 자동으로 캐시됩니다
- OWL 파일이 수정되면 자동으로 재파싱됩니다
- 대용량 온톨로지의 경우 로딩 시간이 걸릴 수 있습니다

## 🔄 업데이트 이력

- 2025-01-XX: 초기 버전 생성
  - 기본 시각화 기능
  - 파일 선택 기능
  - 인터랙티브 그래프

