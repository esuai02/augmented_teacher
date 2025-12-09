# ============================================
# 문서-시스템 동기화 실행 스크립트 (PowerShell)
# ============================================

param(
    [switch]$Check,      # 검사만 수행
    [switch]$Sync,       # 동기화 수행
    [switch]$Generate,   # 에이전트 목록 생성
    [switch]$DryRun,     # 변경 없이 미리보기
    [switch]$Force       # 확인 없이 실행
)

Write-Host ""
Write-Host "========================================"
Write-Host "  문서-시스템 자동 동기화 도구"
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 스크립트 위치로 이동
Set-Location $PSScriptRoot

# Python 확인
try {
    $pythonVersion = python --version 2>&1
    Write-Host "[OK] $pythonVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Python이 설치되어 있지 않습니다." -ForegroundColor Red
    exit 1
}

# 기본: 검사 수행
if (-not $Check -and -not $Sync -and -not $Generate) {
    $Check = $true
}

# 1. 동기화 상태 검사
if ($Check) {
    Write-Host ""
    Write-Host "[1/3] 동기화 상태 검사 중..." -ForegroundColor Yellow
    Write-Host "----------------------------------------"
    
    python check_doc_sync.py
    $checkResult = $LASTEXITCODE
    
    if ($checkResult -eq 2) {
        Write-Host "[ERROR] 검사 중 오류 발생" -ForegroundColor Red
        exit 2
    }
    
    if ($checkResult -eq 1 -and -not $Sync) {
        Write-Host ""
        Write-Host "[!] 동기화 이슈가 발견되었습니다." -ForegroundColor Yellow
        
        if (-not $Force) {
            $confirm = Read-Host "동기화를 수행하시겠습니까? (y/n)"
            if ($confirm -eq 'y') {
                $Sync = $true
            }
        }
    }
}

# 2. 문서 동기화
if ($Sync) {
    Write-Host ""
    Write-Host "[2/3] 문서 동기화 수행 중..." -ForegroundColor Yellow
    Write-Host "----------------------------------------"
    
    if ($DryRun) {
        python sync_docs.py --dry-run
    } else {
        python sync_docs.py
    }
}

# 3. 에이전트 목록 생성
if ($Generate -or $Sync) {
    Write-Host ""
    Write-Host "[3/3] 에이전트 목록 생성 중..." -ForegroundColor Yellow
    Write-Host "----------------------------------------"
    
    if ($DryRun) {
        python sync_docs.py --generate-list --dry-run
    } else {
        python sync_docs.py --generate-list
    }
}

Write-Host ""
Write-Host "========================================"
Write-Host "  완료!"
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

