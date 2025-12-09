# 온톨로지 시각화 도구 통합 완료 보고서

**날짜**: 2025-11-01
**작업 시간**: 약 30분
**상태**: ✅ 완료

---

## 📋 작업 개요

온톨로지 추론 시스템에 대화형 RDF 그래프 시각화 도구를 통합하여, 사용자가 온톨로지 구조를 시각적으로 탐색하고 이해할 수 있도록 했습니다.

### 사용된 기술

- **JSON-LD**: W3C 표준 링크드 데이터 형식
- **vis-network v9.1.9**: 계층적 그래프 시각화 라이브러리
- **jsonld v8.3.3**: JSON-LD 확장 및 파싱 라이브러리
- **HTML5 + ES6 Modules**: 모던 웹 표준

---

## 🎯 구현 기능

### 1. 원클릭 온톨로지 로드

**Phase 1 온톨로지 로드 버튼**:
```javascript
// ontology_app.js:87-88
loadOntologyBtn.addEventListener('click', () =>
    loadOntologyFile('../examples/01_minimal_ontology.json'));
```

**백업 온톨로지 로드 버튼**:
```javascript
// ontology_app.js:89-90
loadBackupBtn.addEventListener('click', () =>
    loadOntologyFile('../examples/01_minimal_ontology.backup.json'));
```

### 2. 자동 온톨로지 정보 표시

로드된 온톨로지의 메타데이터를 자동으로 분석하여 표시:

- **파일명**: 01_minimal_ontology.json
- **규칙 수**: 10개
- **감정 수**: 5개
- **클래스 수**: 5개

```javascript
// ontology_app.js:121-144
function displayOntologyInfo(ontology, filePath) {
    const graph = ontology['@graph'] || [];

    let ruleCount = 0;
    let emotionCount = 0;
    let classCount = 0;

    for (const item of graph) {
        const type = item['@type'];
        if (type === 'InferenceRule') ruleCount++;
        else if (type === 'EmotionType') emotionCount++;
        else if (type === 'rdfs:Class' || type === 'Class') classCount++;
    }

    infoFilename.textContent = filePath.split('/').pop();
    infoRules.textContent = `${ruleCount}개`;
    infoEmotions.textContent = `${emotionCount}개`;
    infoClasses.textContent = `${classCount}개`;
}
```

### 3. 확장 가능한 노드 시각화

**노드 타입별 색상 구분**:
- 🔵 **파란색** (#3498db): RDF 주체/객체 (IRIs)
- 🟣 **보라색** (#9b59b6): RDF 술어 (IRIs)
- 🟠 **주황색** (#f39c12): 리터럴, 블랭크 노드

**확장 가능한 노드** (점선 테두리):
- 클릭하여 하위 구조 확장/축소
- 레벨 확장/축소 버튼으로 일괄 제어

### 4. 계층적 그래프 레이아웃

```javascript
// ontology_app.js:23-32
const options = {
    layout: {
        hierarchical: {
            enabled: true,
            direction: 'LR',      // 좌→우 방향
            sortMethod: 'directed',
            nodeSpacing: 180,
            levelSeparation: 250
        }
    },
    physics: { enabled: false }
};
```

### 5. 양방향 네비게이션

**추론 실험실 → 시각화 도구**:
```html
<!-- inference_lab_v3.php:424-438 -->
<a href="ontology_visualizer/ontology_visualizer.html" target="_blank">
    🎨 온톨로지 시각화 도구 열기
</a>
```

**시각화 도구 → 추론 실험실**:
```html
<!-- ontology_visualizer.html:18-22 -->
<div class="breadcrumb">
    <a href="../inference_lab_v3.php">← 추론 실험실로 돌아가기</a>
</div>
```

---

## 📁 생성된 파일

### 1. `/ontology_visualizer/ontology_visualizer.html` (6.0KB)

**메인 HTML 파일 - 한국어 인터페이스**

주요 섹션:
- **헤더**: 제목 + 네비게이션 링크
- **온톨로지 로드 섹션**: Phase 1/백업 로드 버튼 + 정보 표시
- **입력 섹션**: 텍스트 영역 + 수동 편집 지원
- **버튼 컨트롤**: 시각화, 예제 로드, 지우기
- **그래프 컨테이너**: vis-network 렌더링 영역
- **범례**: 노드 타입별 색상 설명

### 2. `/ontology_visualizer/ontology_app.js` (16KB)

**메인 JavaScript 모듈 - ES6 Module 형식**

핵심 함수:
- `loadOntologyFile(filePath)`: 온톨로지 파일 로드 (87-108행)
- `displayOntologyInfo(ontology, filePath)`: 메타데이터 표시 (121-144행)
- `handleVisualize()`: JSON-LD 파싱 및 시각화 (147-161행)
- `processJsonLd(data)`: RDF 그래프 처리 (354-372행)
- `expandNode(node)`: 노드 확장 (268-342행)
- `collapseNode(nodeId)`: 노드 축소 (345-351행)

### 3. `/ontology_visualizer/style.css` (3.4KB)

**원본 스타일시트 - 재사용**

기존 jsonld-visualizer의 스타일을 그대로 활용

### 4. `/ontology_visualizer/app.js` + `index.html`

**원본 파일 - 백업용**

원본 jsonld-visualizer 파일들을 보존하여 향후 업데이트 시 참고

---

## 🔗 접근 URL

### 프로덕션 URL

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/ontology_visualizer/ontology_visualizer.html
```

### 로컬 경로

```
/mnt/c/1 Project/augmented_teacher/alt42/ontology_brain/ontology_visualizer/ontology_visualizer.html
```

---

## 🎨 사용자 워크플로우

### 기본 워크플로우

1. **추론 실험실에서 시각화 도구 열기**
   - `inference_lab_v3.php`의 "🎨 온톨로지 시각화 도구 열기" 버튼 클릭
   - 새 탭에서 시각화 도구 열림

2. **온톨로지 로드**
   - "Phase 1 온톨로지 로드" 버튼 클릭
   - 자동으로 `01_minimal_ontology.json` 로드 및 시각화
   - 온톨로지 정보 (규칙 10개, 감정 5개, 클래스 5개) 표시

3. **그래프 탐색**
   - 확장 가능한 노드 (점선 테두리) 클릭하여 하위 구조 탐색
   - "레벨 확장" 버튼으로 한 단계 확장
   - "레벨 축소" 버튼으로 한 단계 축소
   - "중앙 정렬" 버튼으로 그래프 리셋

4. **수동 편집 (고급)**
   - 텍스트 영역에 JSON-LD 직접 붙여넣기
   - "시각화" 버튼 클릭하여 커스텀 온톨로지 시각화

### 백업 파일 비교 워크플로우

1. "Phase 1 온톨로지 로드" → 현재 버전 확인
2. "백업 온톨로지 로드" → Phase 0 버전 확인
3. 두 버전의 구조 차이 비교:
   - Phase 0: 3개 규칙
   - Phase 1: 10개 규칙 + 5개 감정 + 우선순위

---

## 📊 기술적 세부사항

### JSON-LD 확장 프로세스

```javascript
// ontology_app.js:354-372
async function processJsonLd(data) {
    // 1. JSON-LD 확장 (jsonld.expand)
    const expanded = await jsonld.expand(data);

    // 2. 루트 노드 처리
    if (Array.isArray(expanded)) {
        for (let i = 0; i < expanded.length; i++) {
            await processNode(expanded[i], null, `root_${i}`);
            const rootNode = nodesDataset.get(`root_${i}`);
            if (rootNode && rootNode.expandable) {
                expandNode(rootNode);  // 자동 확장
            }
        }
    } else {
        await processNode(expanded, null, 'root');
        const rootNode = nodesDataset.get('root');
        if (rootNode && rootNode.expandable) {
            expandNode(rootNode);
        }
    }

    // 3. 레이아웃 애니메이션
    animateLayout();
}
```

### vis-network 레이아웃 최적화

**계층적 레이아웃 설정**:
- **방향**: 좌→우 (LR)
- **정렬 방식**: directed (방향성 기반)
- **노드 간격**: 180px
- **레벨 간격**: 250px
- **물리 엔진**: 비활성화 (성능 최적화)

**애니메이션 전략**:
```javascript
// ontology_app.js:251-256
function animateLayout() {
    network.setOptions({physics: {enabled: true}});
    network.once("stabilized", () => {
        network.setOptions({physics: {enabled: false}});
    });
}
```

노드 확장/축소 시에만 일시적으로 물리 엔진 활성화 → 안정화 후 비활성화

---

## ✅ 통합 검증 체크리스트

### 파일 구조 검증

- [x] `/ontology_visualizer/ontology_visualizer.html` (6.0KB)
- [x] `/ontology_visualizer/ontology_app.js` (16KB)
- [x] `/ontology_visualizer/style.css` (3.4KB)
- [x] `/ontology_visualizer/app.js` (백업, 18KB)
- [x] `/ontology_visualizer/index.html` (백업, 2.9KB)

### 기능 검증

- [x] Phase 1 온톨로지 로드 버튼 동작
- [x] 백업 온톨로지 로드 버튼 동작
- [x] 온톨로지 정보 자동 표시
- [x] 그래프 시각화 (계층적 레이아웃)
- [x] 노드 확장/축소 인터랙션
- [x] 레벨 확장/축소 버튼
- [x] 중앙 정렬 버튼
- [x] 수동 JSON-LD 입력 지원

### 네비게이션 검증

- [x] `inference_lab_v3.php` → 시각화 도구 링크
- [x] 시각화 도구 → `inference_lab_v3.php` 링크
- [x] 새 탭에서 열기 (target="_blank")

### 문서 업데이트

- [x] `README.md`에 시각화 도구 섹션 추가
- [x] `VISUALIZATION_INTEGRATION.md` 작성 (본 문서)

---

## 🎯 향후 개선 사항

### 1. 검색 기능 추가

```javascript
// 미래 구현
function searchNodes(query) {
    const matchedNodes = nodesDataset.get({
        filter: node => node.label.toLowerCase().includes(query.toLowerCase())
    });

    // 매칭된 노드 하이라이트
    nodesDataset.update(matchedNodes.map(node => ({
        id: node.id,
        color: { background: '#ffeb3b', border: '#f57c00' }
    })));
}
```

### 2. 온톨로지 편집 기능

- 노드 더블클릭 → 속성 편집 모달 표시
- 편집된 온톨로지를 JSON-LD로 내보내기
- 서버에 저장 기능 추가

### 3. 차분(Diff) 시각화

```javascript
// 두 온톨로지 비교
function compareOntologies(ontology1, ontology2) {
    // 추가된 노드: 초록색
    // 삭제된 노드: 빨간색
    // 변경된 노드: 주황색
}
```

### 4. 필터링 기능

- 노드 타입별 필터 (규칙만, 감정만, 클래스만)
- 우선순위별 필터 (>0.8, >0.5, 전체)

### 5. 내보내기 기능

- PNG 이미지로 내보내기
- SVG 벡터 그래픽으로 내보내기
- PDF 문서로 내보내기

---

## 📚 참고 자료

### 사용된 라이브러리

- **vis-network**: https://visjs.github.io/vis-network/docs/network/
- **jsonld.js**: https://github.com/digitalbazaar/jsonld.js
- **epiverse/jsonld-visualizer**: https://github.com/epiverse/jsonld-visualizer

### 관련 표준

- **JSON-LD 1.1**: https://www.w3.org/TR/json-ld11/
- **RDF Primer**: https://w3c.github.io/rdf-primer/spec/
- **RDFS**: https://www.w3.org/TR/rdf-schema/

### 프로젝트 문서

- [Phase 1 완료 보고서](PHASE1_COMPLETION_REPORT.md)
- [Phase 1 실행 계획](PHASE1_EXECUTION_PLAN.md)
- [Phase 1 체크리스트](PHASE1_FINAL_CHECKLIST.md)
- [로드맵 분석](ROADMAP_ANALYSIS.md)

---

## 📝 작업 완료 요약

### ✅ 완료된 작업

1. **jsonld-visualizer 저장소 클론** ✓
2. **ontology_visualizer 폴더 생성** ✓
3. **파일 복사 및 수정** ✓
   - `ontology_visualizer.html` (한국어 인터페이스)
   - `ontology_app.js` (온톨로지 로드 기능 추가)
4. **온톨로지 자동 로드 기능 구현** ✓
5. **양방향 네비게이션 링크 추가** ✓
6. **README.md 업데이트** ✓
7. **통합 문서 작성** ✓

### 📊 생성된 코드 통계

- **새 파일**: 2개 (ontology_visualizer.html, ontology_app.js)
- **수정 파일**: 2개 (inference_lab_v3.php, README.md)
- **총 코드 라인**: ~600줄
- **작업 시간**: ~30분

### 🎉 주요 성과

1. **원클릭 시각화**: 버튼 한 번으로 온톨로지 구조 시각화
2. **대화형 탐색**: 확장/축소 인터랙션으로 구조 탐색
3. **자동 정보 표시**: 규칙, 감정, 클래스 수 자동 분석
4. **원활한 통합**: 추론 실험실과 시각화 도구 간 양방향 네비게이션

---

**작성자**: Claude Code
**날짜**: 2025-11-01
**버전**: 1.0
