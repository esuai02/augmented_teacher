<?php
// 간단한 테스트 페이지
$role = $_GET['role'] ?? 'teacher';
?>
<!DOCTYPE html>
<html>
<head>
    <title>모드 테스트</title>
    <style>
        .modes-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 20px;
            background: #f0f0f0;
        }
        
        .mode-card {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
        }
        
        .mode-card.curriculum { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .mode-card.custom { background: linear-gradient(135deg, #10b981, #059669); }
        .mode-card.exam { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .mode-card.mission { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .mode-card.reflection { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .mode-card.selfled { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        
        .mode-icon { font-size: 36px; margin-bottom: 10px; }
        .mode-title { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>역할: <?php echo $role; ?></h1>
    
    <h2>선생님 모드 (6개가 보여야 함)</h2>
    <div class="modes-grid">
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
            <div class="mode-card <?php echo $key; ?>">
                <div class="mode-icon"><?php echo $mode['icon']; ?></div>
                <div class="mode-title"><?php echo $mode['title']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <p>표시된 카드 수: <?php echo count($teacher_modes); ?>개</p>
</body>
</html>