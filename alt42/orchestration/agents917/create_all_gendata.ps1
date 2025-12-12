# Create gendata.md for all agents
$agentsDir = "C:\1 Project\augmented_teacher\alt42\orchestration\agents"
$agents = Get-ChildItem -Path $agentsDir -Directory -Filter "agent*" | Sort-Object Name

foreach ($agent in $agents) {
    $rulesPath = Join-Path $agent.FullName "rules"
    
    if (-not (Test-Path $rulesPath)) {
        Write-Host "[SKIP] $($agent.Name) - rules folder not found"
        continue
    }
    
    $gendataPath = Join-Path $rulesPath "gendata.md"
    
    # Collect file contents
    $contentParts = @()
    
    # Header
    $contentParts += "# $($agent.Name) - Generated Data Documentation"
    $contentParts += ""
    $contentParts += "Created: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    $contentParts += ""
    $contentParts += "---"
    $contentParts += ""
    
    # mission.md
    $missionPath = Join-Path $rulesPath "mission.md"
    if (Test-Path $missionPath) {
        $contentParts += "## Mission"
        $contentParts += ""
        $missionContent = Get-Content $missionPath -Raw -Encoding UTF8
        $contentParts += $missionContent.TrimEnd()
        $contentParts += ""
        $contentParts += ""
        $contentParts += "---"
        $contentParts += ""
    }
    
    # questions.md
    $questionsPath = Join-Path $rulesPath "questions.md"
    if (Test-Path $questionsPath) {
        $contentParts += "## Questions"
        $contentParts += ""
        $questionsContent = Get-Content $questionsPath -Raw -Encoding UTF8
        $contentParts += $questionsContent.TrimEnd()
        $contentParts += ""
        $contentParts += ""
        $contentParts += "---"
        $contentParts += ""
    }
    
    # metadata.md
    $metadataPath = Join-Path $rulesPath "metadata.md"
    if (Test-Path $metadataPath) {
        $contentParts += "## Metadata"
        $contentParts += ""
        $metadataContent = Get-Content $metadataPath -Raw -Encoding UTF8
        $contentParts += $metadataContent.TrimEnd()
        $contentParts += ""
        $contentParts += ""
        $contentParts += "---"
        $contentParts += ""
    }
    
    # rules.yaml
    $rulesPathFile = Join-Path $rulesPath "rules.yaml"
    if (Test-Path $rulesPathFile) {
        $contentParts += "## Rules"
        $contentParts += ""
        $contentParts += "```yaml"
        $rulesContent = Get-Content $rulesPathFile -Raw -Encoding UTF8
        $contentParts += $rulesContent.TrimEnd()
        $contentParts += "```"
    }
    
    # Write file
    if ($contentParts.Count -gt 0) {
        $finalContent = $contentParts -join "`n"
        [System.IO.File]::WriteAllText($gendataPath, $finalContent, [System.Text.Encoding]::UTF8)
        Write-Host "[OK] $($agent.Name)/gendata.md created"
    } else {
        Write-Host "[SKIP] $($agent.Name) - no files to merge"
    }
}

Write-Host ""
Write-Host "All gendata.md files created!"

