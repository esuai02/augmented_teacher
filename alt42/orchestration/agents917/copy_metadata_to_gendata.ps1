# Copy metadata.md to gendata.md for all agents
$agentsDir = "C:\1 Project\augmented_teacher\alt42\orchestration\agents"
$agents = Get-ChildItem -Path $agentsDir -Directory -Filter "agent*" | Sort-Object Name

foreach ($agent in $agents) {
    $rulesPath = Join-Path $agent.FullName "rules"
    
    if (-not (Test-Path $rulesPath)) {
        Write-Host "[SKIP] $($agent.Name) - rules folder not found"
        continue
    }
    
    $metadataPath = Join-Path $rulesPath "metadata.md"
    $gendataPath = Join-Path $rulesPath "gendata.md"
    
    if (Test-Path $metadataPath) {
        Copy-Item -Path $metadataPath -Destination $gendataPath -Force
        Write-Host "[OK] $($agent.Name)/gendata.md created from metadata.md"
    } else {
        Write-Host "[SKIP] $($agent.Name) - metadata.md not found"
    }
}

Write-Host ""
Write-Host "All gendata.md files created from metadata.md!"

