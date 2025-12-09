<?php
// 간단한 그리드 테스트 - CSS 충돌 해결
?>
<!DOCTYPE html>
<html>
<head>
    <title>Grid Fix Test</title>
    <style>
        body {
            background: #111;
            color: white;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        /* 명확한 그리드 스타일 */
        .test-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 15px !important;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .test-card {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            padding: 20px;
            border-radius: 10px;
            min-height: 150px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .test-card:hover {
            transform: scale(1.05);
        }
        
        .test-card.exam { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .test-card.custom { background: linear-gradient(135deg, #10b981, #059669); }
        .test-card.mission { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .test-card.reflection { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .test-card.selfled { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        
        .icon { font-size: 48px; margin-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; }
        
        /* 디버그 정보 */
        .debug-info {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            margin: 20px auto;
            max-width: 900px;
            border-radius: 5px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>Grid Display Test - All Cards Should Be Visible</h1>
    
    <h2>Teacher Modes (6 cards in 3x2 grid)</h2>
    <div class="test-grid">
        <?php
        $teacher_modes = [
            'curriculum' => ['icon' => '📚', 'title' => '커리큘럼 중심모드'],
            'exam' => ['icon' => '✏️', 'title' => '시험대비 중심모드'],
            'custom' => ['icon' => '🎯', 'title' => '맞춤학습 중심모드'],
            'mission' => ['icon' => '⚡', 'title' => '단기미션 중심모드'],
            'reflection' => ['icon' => '🧠', 'title' => '자기성찰 중심모드'],
            'selfled' => ['icon' => '🚀', 'title' => '자기주도 중심모드']
        ];
        
        foreach ($teacher_modes as $key => $mode): ?>
            <div class="test-card <?php echo $key; ?>" onclick="console.log('Clicked: <?php echo $key; ?>')">
                <div class="icon"><?php echo $mode['icon']; ?></div>
                <div class="title"><?php echo $mode['title']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="debug-info">
        <strong>Debug Info:</strong><br>
        <?php echo "PHP Cards Generated: " . count($teacher_modes); ?><br>
        <span id="jsCount"></span><br>
        <span id="gridInfo"></span>
    </div>
    
    <h2>Student Modes (6 cards in 3x2 grid)</h2>
    <div class="test-grid">
        <?php
        $student_modes = [
            'curriculum' => ['icon' => '📚', 'title' => '커리큘럼 중심모드'],
            'exam' => ['icon' => '✏️', 'title' => '시험대비 중심모드'],
            'custom' => ['icon' => '🎯', 'title' => '맞춤학습 중심모드'],
            'mission' => ['icon' => '⚡', 'title' => '단기미션 중심모드'],
            'reflection' => ['icon' => '🧠', 'title' => '자기성찰 중심모드'],
            'selfled' => ['icon' => '🚀', 'title' => '자기주도 중심모드']
        ];
        
        foreach ($student_modes as $key => $mode): ?>
            <div class="test-card <?php echo $key; ?>" onclick="console.log('Clicked: <?php echo $key; ?>')">
                <div class="icon"><?php echo $mode['icon']; ?></div>
                <div class="title"><?php echo $mode['title']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // JavaScript에서 카드 수 확인
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.test-card');
            document.getElementById('jsCount').textContent = 'JS Cards Found: ' + cards.length;
            
            const grid = document.querySelector('.test-grid');
            const computedStyle = window.getComputedStyle(grid);
            document.getElementById('gridInfo').textContent = 
                'Grid Display: ' + computedStyle.display + 
                ', Columns: ' + computedStyle.gridTemplateColumns;
            
            console.log('Total cards rendered:', cards.length);
            console.log('Grid computed style:', computedStyle.gridTemplateColumns);
        });
    </script>
</body>
</html>