---
allowed-tools: \[Read, Write, Edit, MultiEdit, Bash, Glob, TodoWrite, Task]
description: "글쓰기를 위한 연관 연구결과 조사 요청(문헌조사·출처수집·팩트체크) with 지능형 페르소나 활성화 & MCP 연동"
-------------------------------------------------------------------------------

# /sc\:research - Research Request for Writing

## Purpose

글/보고서/화이트페이퍼/학술문서 작성을 위해 **주제 관련 연구·자료를 신속 수집·평가·요약**하고, **인용 가능한 증거 꾸러미**(Annotated Bibliography, Evidence Table, 핵심 인용문, 수치·도표 후보, 아웃라인)를 산출합니다.

## Usage

```
/sc:research [topic-or-question]
  [--goal brief|blog|techdoc|whitepaper|academic]
  [--scope narrow|broad]
  [--depth snapshot|standard|deep]
  [--time-window "YYYY-YYYY"]
  [--regions KR,US,EU,...]
  [--languages ko,en,...]
  [--citation apa|mla|chicago|numeric]
  [--include "키워드1,키워드2"] [--exclude "키워드A,키워드B"]
  [--with-outline] [--with-bibliography] [--with-quotes]
  [--with-figures] [--with-datasets]
  [--factcheck strict|light]
  [--iterative] [--safe]
```

## Arguments

* `topic-or-question` : 조사 주제 또는 핵심 질문(문장 형태 권장)
* `--goal` : 결과물 유형(요약 브리프/블로그/기술문서/화이트페이퍼/학술)
* `--scope` : 범위(좁게/넓게)
* `--depth` : 조사 심도(스냅샷/표준/심층)
* `--time-window` : 연도 범위 필터(예: "2018-2025")
* `--regions` : 지리적 초점(예: KR, US)
* `--languages` : 소스 언어
* `--citation` : 인용 스타일
* `--include / --exclude` : 포함·제외 키워드
* `--with-*` : 산출물 포함 옵션(아웃라인, 참고문헌, 인용문, 도표, 데이터셋)
* `--factcheck` : 검증 강도
* `--iterative` : 단계별 검증 및 보강 루프
* `--safe` : 보수적(검증 우선) 접근

## Execution

1. **요구분석 & 쿼리정의**: 주제→하위 질문 트리, 키워드·동의어·한국어/영어 확장.
2. **소스탐색 & 수집**: 1차(리뷰·메타분석·정책보고서)→2차(학술·특허·표준)→3차(뉴스·블로그) 우선순위.
3. **품질평가**: 출처 신뢰도, 방법론 적정성, 표본·통계, 재현성·한계 기록.
4. **증거정리**:

   * *Evidence Table* (저자/연도/방법/표본/핵심결과/한계)
   * *Annotated Bibliography* (요지 3줄, 활용 포인트, 인용문)
   * *Quotes Set* (직접 인용 ≤ 규정 글자)
5. **종합·대조**: 합의점/쟁점/공백 영역, 상충 결과 원인 가설.
6. **아웃라인 제시**: 섹션별 주장-증거 매핑, 도표·도식 후보.
7. **팩트체크 & 리스크**: 과도한 일반화/출처 편향/출판편향 점검, 반례 탐색.
8. **산출물 패키징**: `research_brief.md`, `annotated_bibliography.md|bib`, `evidence_table.csv`, `quotes.md`, `outline.md`, `claims_matrix.md`.

## Claude Code Integration

* **Read/Glob**: 로컬 노트·기존 문서 스캔(중복 방지, 문맥 결합).
* **Write/Edit/MultiEdit**: 브리프·아웃라인·참고문헌 자동 생성/수정.
* **TodoWrite**: 조사 체크리스트 생성(소스 확보·요약·검증).
* **Task**: 단계별(탐색→평가→종합) 워크플로 조율.
* **Bash(옵션)**: 허용 시 CLI 기반 서지툴/간단 파싱 스크립트 실행.
* **MCP 연동**: Context7(검색 패턴/프롬프트), Sequential(복잡 질의 오케스트레이션), Magic(UI 보조) 등과 협업.

## Auto-Activation Personas

* **리서치 라이브러리언**: 소스 발굴·데이터베이스 전략
* **도메인 전문가**: 개념·방법론 적합성 점검
* **방법론/통계가이드**: 효과크기·유의성·메타분석 해석
* **팩트체커**: 수치·인용 검증, 반증 자료 탐색
* **에디터**: 톤·흐름·주장-증거 정합성 편집
* **번역·현지화**: 다국어 소스 요약·표준 인용 변환

## Examples

```
/sc:research "Bloom의 Two Sigma 문제와 AI 튜터의 현대적 재현 가능성"
  --goal whitepaper --depth deep --time-window "2018-2025"
  --languages ko,en --citation apa --with-outline --with-bibliography
  --factcheck strict --iterative

/sc:research "수능 수학 AI 튜터링의 학업성취 영향(한국 사례)"
  --goal blog --scope narrow --regions KR --languages ko
  --with-quotes --with-figures --citation chicago

/sc:research "Agentic Tutor의 LMS 연동 패턴(SSO/SCORM/LTI/xAPI)"
  --goal techdoc --depth standard --include "LTI,xAPI" --with-outline

/sc:research "Palantir의 교육 섹터 파트너십 동향"
  --goal brief --time-window "2023-2025" --exclude "healthcare"

/sc:research "Neural interface가 학습지표에 미치는 잠재효과"
  --goal academic --depth deep --with-bibliography --with-datasets
```
 