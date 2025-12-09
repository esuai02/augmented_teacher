# Claude Code Subagents 사용 가이드

## 📚 개요

awesome-claude-code-subagents는 특정 개발 작업을 위해 설계된 전문 AI 에이전트 모음입니다. 각 서브에이전트는 프로덕션 환경에서 검증되었으며 업계 표준과 모범 사례를 따릅니다.

## 🚀 설치 완료

서브에이전트 컬렉션이 다음 경로에 설치되었습니다:
```
/mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/awesome-claude-code-subagents-main/
```

## 🛠️ 사용 가능한 도구

### 1. PHP 웹 인터페이스
**URL**: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/subagents_viewer.php

웹 브라우저에서 서브에이전트를 탐색하고 사용할 수 있는 시각적 인터페이스입니다.

**기능**:
- 카테고리별 서브에이전트 브라우징
- 작업 유형별 추천 받기
- 서브에이전트 프롬프트 복사
- 도구 및 설명 확인

### 2. Python CLI 도구
**파일**: `subagent_cli.py`

명령줄에서 서브에이전트를 관리하고 사용할 수 있습니다.

**명령어**:
```bash
# 모든 서브에이전트 목록 보기
python3 subagent_cli.py list

# 특정 서브에이전트 상세 정보 보기
python3 subagent_cli.py show 01-core-development backend-developer

# 작업 유형에 따른 추천 받기
python3 subagent_cli.py recommend php

# 키워드로 서브에이전트 검색
python3 subagent_cli.py search database

# 도움말 보기
python3 subagent_cli.py help
```

### 3. PHP 유틸리티 클래스
**파일**: `use_subagents.php`

PHP 프로젝트에서 프로그래밍 방식으로 서브에이전트를 사용할 수 있습니다.

**사용 예시**:
```php
require_once 'use_subagents.php';

$manager = new SubagentManager();

// 백엔드 개발자 서브에이전트 가져오기
$backendDev = $manager->getSubagent('01-core-development', 'backend-developer');

// 모든 서브에이전트 목록
$allSubagents = $manager->listSubagents();

// API 개발을 위한 추천 받기
$apiRecommendations = $manager->getRecommendations('api');
```

## 📂 카테고리 구조

### 01. Core Development (핵심 개발)
- `api-designer` - REST 및 GraphQL API 설계
- `backend-developer` - 서버 사이드 전문가
- `frontend-developer` - UI/UX 스페셜리스트
- `fullstack-developer` - 엔드투엔드 기능 개발
- `microservices-architect` - 분산 시스템 설계자
- `mobile-developer` - 크로스 플랫폼 모바일 전문가

### 02. Language Specialists (언어 전문가)
- `php-pro` - PHP 개발 전문가
- `laravel-specialist` - Laravel 프레임워크 전문가
- `python-pro` - Python 생태계 마스터
- `javascript-pro` - JavaScript 개발 전문가
- `typescript-pro` - TypeScript 스페셜리스트
- `sql-pro` - 데이터베이스 쿼리 전문가

### 03. Infrastructure (인프라)
- `cloud-architect` - AWS/GCP/Azure 전문가
- `database-administrator` - 데이터베이스 관리 전문가
- `deployment-engineer` - 배포 자동화 전문가
- `docker-expert` - 컨테이너화 전문가
- `kubernetes-master` - K8s 오케스트레이션 전문가

### 04. Quality & Security (품질 및 보안)
- `security-expert` - 보안 전문가
- `test-engineer` - 테스트 자동화 전문가
- `qa-specialist` - 품질 보증 전문가
- `performance-engineer` - 성능 최적화 전문가

### 05. Data & AI (데이터 및 AI)
- `data-engineer` - 데이터 파이프라인 전문가
- `ml-engineer` - 머신러닝 엔지니어
- `ai-researcher` - AI 연구 전문가

### 06. Developer Experience (개발자 경험)
- `documentation-writer` - 기술 문서 작성자
- `api-documenter` - API 문서화 전문가
- `code-reviewer` - 코드 리뷰 전문가

### 07. Specialized Domains (특수 도메인)
- `blockchain-developer` - 블록체인 개발자
- `game-developer` - 게임 개발 전문가
- `iot-engineer` - IoT 시스템 전문가

### 08. Business & Product (비즈니스 및 제품)
- `product-manager` - 제품 관리자
- `business-analyst` - 비즈니스 분석가
- `scrum-master` - 애자일 코치

### 09. Meta Orchestration (메타 오케스트레이션)
- `architect-reviewer` - 아키텍처 리뷰어
- `migration-specialist` - 마이그레이션 전문가
- `refactoring-expert` - 리팩토링 전문가

### 10. Research & Analysis (연구 및 분석)
- `code-analyst` - 코드 분석가
- `vulnerability-researcher` - 취약점 연구원
- `performance-analyst` - 성능 분석가

## 🎯 MathKing 프로젝트를 위한 추천 서브에이전트

### PHP/Moodle 개발
```bash
# PHP 개발 추천
python3 subagent_cli.py recommend php

# Moodle 개발 추천
python3 subagent_cli.py recommend moodle
```

### 데이터베이스 작업
```bash
# 데이터베이스 관련 추천
python3 subagent_cli.py recommend database

# SQL 전문가 보기
python3 subagent_cli.py show 02-language-specialists sql-pro
```

### API 개발
```bash
# API 개발 추천
python3 subagent_cli.py recommend api

# 백엔드 개발자 보기
python3 subagent_cli.py show 01-core-development backend-developer
```

### 프론트엔드 개발
```bash
# 프론트엔드 추천
python3 subagent_cli.py recommend frontend

# UI 디자이너 보기
python3 subagent_cli.py show 01-core-development ui-designer
```

## 💡 활용 팁

1. **작업 시작 전**: 해당 작업에 맞는 서브에이전트를 선택하여 전문가 수준의 가이드를 받으세요.

2. **코드 리뷰**: `code-reviewer` 서브에이전트를 사용하여 코드 품질을 향상시키세요.

3. **문서화**: `documentation-writer`를 사용하여 프로젝트 문서를 개선하세요.

4. **성능 최적화**: `performance-engineer`로 시스템 성능을 분석하고 개선하세요.

5. **보안 강화**: `security-expert`로 보안 취약점을 찾고 수정하세요.

## 🔧 커스터마이징

서브에이전트 프롬프트는 프로젝트 요구사항에 맞게 수정할 수 있습니다:

1. 웹 인터페이스에서 프롬프트 복사
2. 프로젝트별 요구사항 추가
3. Claude Code와 함께 사용

## 📝 예시: PHP 백엔드 개발

```bash
# 1. PHP 전문가 서브에이전트 확인
python3 subagent_cli.py show 02-language-specialists php-pro

# 2. Laravel 전문가 확인
python3 subagent_cli.py show 02-language-specialists laravel-specialist

# 3. 백엔드 개발자 확인
python3 subagent_cli.py show 01-core-development backend-developer
```

## 🌐 웹 인터페이스 접속

브라우저에서 다음 URL로 접속:
https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/subagents_viewer.php

## ⚡ 빠른 시작

1. **웹 인터페이스 사용**: 시각적으로 서브에이전트 탐색
2. **CLI로 검색**: `python3 subagent_cli.py search [키워드]`
3. **추천 받기**: `python3 subagent_cli.py recommend [작업유형]`
4. **프롬프트 복사**: 웹 인터페이스의 "Copy Full Prompt" 버튼 클릭

## 📚 추가 리소스

- 서브에이전트 저장소: `awesome-claude-code-subagents-main/`
- 카테고리별 MD 파일: `categories/[카테고리명]/[에이전트명].md`
- 각 서브에이전트는 YAML 프론트매터와 상세 프롬프트 포함

---

이제 Claude Code 서브에이전트를 활용하여 더 효율적이고 전문적인 개발이 가능합니다!