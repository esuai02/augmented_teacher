#!/bin/bash
# Mathking 문서 순환 업데이트 워크플로우 초기 설정 스크립트

set -e

echo "🚀 Mathking 문서 순환 업데이트 워크플로우 설정 시작..."

# 필요한 디렉토리 생성
echo "📁 디렉토리 생성 중..."
mkdir -p status
mkdir -p logs
mkdir -p backups/workflow_runs
mkdir -p .cache/workflow

# 초기 상태 파일 생성
echo "📝 초기 상태 파일 생성 중..."

# status/document_scores.json
cat > status/document_scores.json <<EOF
{
  "metadata": {
    "created": "$(date -Iseconds)",
    "workflow_version": "1.0.0"
  }
}
EOF

# status/consistency_report.json
cat > status/consistency_report.json <<EOF
{
  "all_checks_pass": false,
  "checks": [],
  "timestamp": "$(date -Iseconds)"
}
EOF

# Python 가상환경 설정 (선택사항)
if command -v python3 &> /dev/null; then
    echo "🐍 Python 버전 확인..."
    python3 --version

    # PyYAML 설치 확인
    if python3 -c "import yaml" 2>/dev/null; then
        echo "✅ PyYAML이 이미 설치되어 있습니다."
    else
        echo "📦 PyYAML 설치 중..."
        pip3 install pyyaml
    fi
else
    echo "⚠️ Python3가 설치되어 있지 않습니다. Python 3.10 이상을 설치해주세요."
    exit 1
fi

# 실행 권한 부여
echo "🔧 실행 권한 부여 중..."
chmod +x run_document_loop.py

# 설정 파일 검증
echo "✅ 설정 파일 검증 중..."
if [ -f "document_update_loop.yaml" ]; then
    python3 -c "import yaml; yaml.safe_load(open('document_update_loop.yaml'))"
    echo "✅ YAML 설정 파일 검증 완료"
else
    echo "❌ document_update_loop.yaml 파일을 찾을 수 없습니다."
    exit 1
fi

echo ""
echo "✅ 설정 완료!"
echo ""
echo "다음 명령어로 워크플로우를 실행하세요:"
echo ""
echo "  # 전체 루프 실행"
echo "  python3 run_document_loop.py --mode loop_until_pass --verbose"
echo ""
echo "  # 단일 반복 실행 (테스트)"
echo "  python3 run_document_loop.py --mode single_iteration"
echo ""
echo "자세한 사용법은 README.md를 참고하세요."
