@echo off
cd /d "%~dp0"
python generate_expression_ontology.py
if %errorlevel% neq 0 (
    python3 generate_expression_ontology.py
    if %errorlevel% neq 0 (
        py generate_expression_ontology.py
        if %errorlevel% neq 0 (
            echo Python을 찾을 수 없습니다. Python이 설치되어 있고 PATH에 추가되어 있는지 확인하세요.
            pause
        )
    )
)

