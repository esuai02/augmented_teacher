# UTF-8 인코딩 설정
$OutputEncoding = [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::InputEncoding = [System.Text.Encoding]::UTF8

# 파일 경로 설정
$inputFile = Join-Path $PSScriptRoot "contents_info.md"
$outputDir = $PSScriptRoot

# 영역별 라인 번호 (0-based index)
$areaLines = @(
    @{ Start = 0; Name = "수체계_영역.md" },
    @{ Start = 1100; Name = "지수와_로그_영역.md" },
    @{ Start = 1246; Name = "수열_영역.md" },
    @{ Start = 1421; Name = "식의_계산_영역.md" },
    @{ Start = 1706; Name = "집합과_명제_영역.md" },
    @{ Start = 1821; Name = "방정식_영역.md" },
    @{ Start = 2099; Name = "부등식_영역.md" },
    @{ Start = 2215; Name = "함수_영역.md" },
    @{ Start = 2659; Name = "미분_영역.md" },
    @{ Start = 3033; Name = "적분_영역.md" },
    @{ Start = 3253; Name = "평면도형_영역.md" },
    @{ Start = 3887; Name = "평면좌표_영역.md" },
    @{ Start = 4088; Name = "입체도형_영역.md" },
    @{ Start = 4257; Name = "공간좌표_영역.md" },
    @{ Start = 4406; Name = "벡터_영역.md" },
    @{ Start = 4576; Name = "경우의_수와_확률_영역.md" },
    @{ Start = 4808; Name = "통계_영역.md" }
)

# 파일 전체 읽기
$allLines = [System.IO.File]::ReadAllLines($inputFile, [System.Text.Encoding]::UTF8)
$totalLines = $allLines.Length

# UTF-8 인코딩 (BOM 없음)
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

# 각 영역별로 파일 생성
for ($i = 0; $i -lt $areaLines.Count; $i++) {
    $area = $areaLines[$i]
    $startLine = $area.Start
    
    # 다음 영역 시작 위치 또는 파일 끝까지
    if ($i -lt $areaLines.Count - 1) {
        $endLine = $areaLines[$i + 1].Start - 1
    } else {
        $endLine = $totalLines - 1
    }
    
    # 해당 라인 추출
    $contentLines = $allLines[$startLine..$endLine]
    
    # 파일 경로 생성
    $outputPath = Join-Path $outputDir $area.Name
    
    # 파일 쓰기
    [System.IO.File]::WriteAllLines($outputPath, $contentLines, $utf8NoBom)
    
    $lineCount = $endLine - $startLine + 1
    Write-Host "Created: $($area.Name) ($lineCount lines)" -Encoding UTF8
}

Write-Host "`n모든 파일이 생성되었습니다!" -Encoding UTF8