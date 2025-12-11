# Contents Bank

룰 검증 및 상호작용을 위한 컨텐츠 저장소

## 구조

```
contentsbank/
├── rule_verification_{rule_id}_{timestamp}.json  # 룰 검증 컨텐츠
├── rule_scenario_{rule_id}_{timestamp}.json      # 룰 실행 시나리오
├── rule_test_case_{rule_id}_{timestamp}.json    # 룰 테스트 케이스
├── ontology_{node_class}_{timestamp}.json       # 온톨로지 노드 컨텐츠
└── index.json                                    # 컨텐츠 인덱스
```

## 컨텐츠 타입

### 1. 룰 검증 컨텐츠 (rule_verification)
- 룰 정보 및 조건/액션 검증
- 검증 체크리스트
- 테스트 시나리오

### 2. 룰 실행 시나리오 (rule_scenario)
- 조건별 실행 시나리오
- 실행 흐름
- 상호작용 포인트

### 3. 룰 테스트 케이스 (rule_test_case)
- 정상 케이스
- 실패 케이스
- 엣지 케이스

### 4. 온톨로지 컨텐츠 (ontology)
- 온톨로지 노드 정보
- 관계 및 속성
- 추론 규칙

## 사용 방법

컨텐츠는 `analyze_content.php`에서 자동으로 생성되어 저장됩니다.

수동으로 컨텐츠를 생성하려면:

```php
require_once('includes/rule_content_generator.php');

$generator = new RuleContentGenerator();
$contents = $generator->generateRuleContents($rules, $context);
$generator->saveContents($contents, __DIR__);
```

## 인덱스 업데이트

컨텐츠 인덱스는 자동으로 생성되지만, 수동으로 업데이트하려면:

```php
require_once('includes/content_loader.php');

$loader = new ContentLoader();
$index = $loader->generateContentIndex();
```

