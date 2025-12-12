# 각 에이전트의 rules 폴더에서 파일들을 통합하여 gendata.md 생성

$agentsDir = "C:\1 Project\augmented_teacher\alt42\orchestration\agents"
$agents = Get-ChildItem -Path $agentsDir -Directory -Filter "agent*" | Sort-Object Name

foreach ($agent in $agents) {
    $rulesPath = Join-Path $agent.FullName "rules"
    
    if (-not (Test-Path $rulesPath)) {
        Write-Host "[SKIP] $($agent.Name) - rules 폴더 없음"
        continue
    }
    
    $gendataPath = Join-Path $rulesPath "gendata.md"
    $content = @()
    
    # 헤더
    $content += "# $($agent.Name) - Generated Data Documentation"
    $content += ""
    $content += "생성일: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    $content += ""
    $content += "---"
    $content += ""
    
    # mission.md
    $missionPath = Join-Path $rulesPath "mission.md"
    if (Test-Path $missionPath) {
        $content += "## Mission"
        $content += ""
        $content += (Get-Content $missionPath -Raw)
        $content += ""
        $content += "---"
        $content += ""
    }
    
    # questions.md
    $questionsPath = Join-Path $rulesPath "questions.md"
    if (Test-Path $questionsPath) {
        $content += "## Questions"
        $content += ""
        $content += (Get-Content $questionsPath -Raw)
        $content += ""
        $content += "---"
        $content += ""
    }
    
    # metadata.md
    $metadataPath = Join-Path $rulesPath "metadata.md"
    if (Test-Path $metadataPath) {
        $content += "## Metadata"
        $content += ""
        $content += (Get-Content $metadataPath -Raw)
        $content += ""
        $content += "---"
        $content += ""
    }
    
    # rules.yaml
    $rulesYamlPath = Join-Path $rulesPath "rules.yaml"
    if (Test-Path $rulesYamlPath) {
        $content += "## Rules"
        $content += ""
        $content += "```yaml"
        $content += (Get-Content $rulesYamlPath -Raw)
        $content += "```"
    }
    
    # 파일 작성
    if ($content.Count -gt 0) {
        $content -join "`n" | Out-File -FilePath $gendataPath -Encoding UTF8 -NoNewline
        Write-Host "[OK] $($agent.Name)/gendata.md 생성 완료"
    } else {
        Write-Host "[SKIP] $($agent.Name) - 통합할 파일 없음"
    }
}

Write-Host ""
Write-Host "완료: 모든 에이전트의 gendata.md 생성 완료"

