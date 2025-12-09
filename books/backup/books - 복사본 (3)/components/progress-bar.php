<?php
/**
 * 진행률 바 컴포넌트
 * 학습 진행 상황을 시각적으로 표시합니다.
 */

/**
 * 진행률 바 렌더링 함수
 * 
 * @param float $progressfilled 진행률 (0-100)
 * @param string $bgtype Bootstrap 색상 타입 (success, danger, warning, info, primary)
 * @param array $options 추가 옵션 설정
 * @return string HTML 문자열
 */
function renderProgressBar($progressfilled, $bgtype = 'primary', $options = []) {
    // 기본 옵션 설정
    $defaults = [
        'showLabel' => true,
        'showTooltip' => true,
        'animated' => true,
        'striped' => true,
        'height' => '15px',
        'customClass' => '',
        'description' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // 진행률 범위 제한 (0-100)
    $progressfilled = max(0, min(100, $progressfilled));
    
    // Bootstrap 5 진행률 바 클래스
    $progressBarClasses = ['progress-bar'];
    
    if ($options['striped']) {
        $progressBarClasses[] = 'progress-bar-striped';
    }
    
    if ($options['animated']) {
        $progressBarClasses[] = 'progress-bar-animated';
    }
    
    $progressBarClasses[] = 'bg-' . $bgtype;
    
    if ($options['customClass']) {
        $progressBarClasses[] = $options['customClass'];
    }
    
    $progressBarClass = implode(' ', $progressBarClasses);
    
    // 접근성을 위한 aria-label 생성
    $ariaLabel = sprintf('진행률 %d%%', round($progressfilled));
    
    ob_start();
    ?>
    <div class="progress-card">
        <?php if ($options['description']): ?>
        <div class="progress-description mb-2">
            <?php echo htmlspecialchars($options['description']); ?>
        </div>
        <?php endif; ?>
        
        <div class="progress" style="height: <?php echo $options['height']; ?>; background-color: #bdbdbd;">
            <div class="<?php echo $progressBarClass; ?>" 
                 role="progressbar" 
                 style="width: <?php echo $progressfilled; ?>%;" 
                 aria-valuenow="<?php echo $progressfilled; ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100"
                 aria-label="<?php echo $ariaLabel; ?>"
                 <?php if ($options['showTooltip']): ?>
                 data-bs-toggle="tooltip" 
                 data-bs-placement="top" 
                 title="<?php echo round($progressfilled); ?>%"
                 <?php endif; ?>>
                <?php if ($options['showLabel'] && $progressfilled > 10): ?>
                    <span class="progress-label"><?php echo round($progressfilled); ?>%</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * 진행률 상태 메시지 생성 함수
 * 진행률에 따른 적절한 메시지를 반환합니다.
 * 
 * @param float $progressfilled 진행률 (0-100)
 * @return string 상태 메시지
 */
function getProgressMessage($progressfilled) {
    if ($progressfilled < 20) {
        return "시작 단계입니다. 계속 진행해주세요!";
    } elseif ($progressfilled < 40) {
        return "좋은 진행입니다. 계속 노력하세요!";
    } elseif ($progressfilled < 60) {
        return "절반 정도 완료되었습니다!";
    } elseif ($progressfilled < 80) {
        return "거의 다 왔습니다. 조금만 더!";
    } elseif ($progressfilled < 100) {
        return "마지막 단계입니다. 완료가 눈앞에!";
    } else {
        return "축하합니다! 모두 완료했습니다!";
    }
}

/**
 * 다중 진행률 바 렌더링 함수
 * 여러 카테고리의 진행률을 함께 표시합니다.
 * 
 * @param array $progressData 진행률 데이터 배열
 * @return string HTML 문자열
 */
function renderMultipleProgressBars($progressData) {
    ob_start();
    ?>
    <div class="progress-group">
        <?php foreach ($progressData as $data): ?>
        <div class="progress-item mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="progress-title mb-0"><?php echo htmlspecialchars($data['title']); ?></h6>
                <small class="text-muted"><?php echo round($data['value']); ?>%</small>
            </div>
            <?php 
            echo renderProgressBar(
                $data['value'], 
                $data['type'] ?? 'primary', 
                ['showLabel' => false, 'height' => '10px']
            ); 
            ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * 원형 진행률 표시기 렌더링 함수
 * SVG를 사용한 원형 진행률 표시기를 생성합니다.
 * 
 * @param float $progressfilled 진행률 (0-100)
 * @param array $options 추가 옵션
 * @return string HTML 문자열
 */
function renderCircularProgress($progressfilled, $options = []) {
    $defaults = [
        'size' => 120,
        'strokeWidth' => 8,
        'color' => '#007bff',
        'bgColor' => '#e9ecef'
    ];
    
    $options = array_merge($defaults, $options);
    
    $radius = ($options['size'] - $options['strokeWidth']) / 2;
    $circumference = $radius * 2 * pi();
    $offset = $circumference - ($progressfilled / 100) * $circumference;
    
    ob_start();
    ?>
    <div class="circular-progress" style="width: <?php echo $options['size']; ?>px; height: <?php echo $options['size']; ?>px;">
        <svg width="<?php echo $options['size']; ?>" height="<?php echo $options['size']; ?>">
            <!-- 배경 원 -->
            <circle
                cx="<?php echo $options['size'] / 2; ?>"
                cy="<?php echo $options['size'] / 2; ?>"
                r="<?php echo $radius; ?>"
                stroke="<?php echo $options['bgColor']; ?>"
                stroke-width="<?php echo $options['strokeWidth']; ?>"
                fill="none"
            />
            <!-- 진행률 원 -->
            <circle
                cx="<?php echo $options['size'] / 2; ?>"
                cy="<?php echo $options['size'] / 2; ?>"
                r="<?php echo $radius; ?>"
                stroke="<?php echo $options['color']; ?>"
                stroke-width="<?php echo $options['strokeWidth']; ?>"
                fill="none"
                stroke-dasharray="<?php echo $circumference; ?> <?php echo $circumference; ?>"
                stroke-dashoffset="<?php echo $offset; ?>"
                stroke-linecap="round"
                transform="rotate(-90 <?php echo $options['size'] / 2; ?> <?php echo $options['size'] / 2; ?>)"
                style="transition: stroke-dashoffset 0.3s ease;"
            />
        </svg>
        <div class="circular-progress-text" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 20px; font-weight: bold;">
            <?php echo round($progressfilled); ?>%
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * 진행률 바 초기화 스크립트
 * 툴팁 및 애니메이션 초기화를 위한 JavaScript 코드
 * 
 * @return string JavaScript 코드
 */
function initProgressBarScript() {
    ob_start();
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bootstrap 툴팁 초기화
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // 진행률 바 애니메이션
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(function(bar) {
            const targetWidth = bar.style.width;
            bar.style.width = '0%';
            
            setTimeout(function() {
                bar.style.transition = 'width 1s ease-in-out';
                bar.style.width = targetWidth;
            }, 100);
        });
        
        // 원형 진행률 애니메이션
        const circularProgressElements = document.querySelectorAll('.circular-progress circle:last-child');
        circularProgressElements.forEach(function(circle) {
            const offset = circle.getAttribute('stroke-dashoffset');
            const circumference = circle.getAttribute('stroke-dasharray').split(' ')[0];
            
            circle.setAttribute('stroke-dashoffset', circumference);
            
            setTimeout(function() {
                circle.style.transition = 'stroke-dashoffset 1s ease-in-out';
                circle.setAttribute('stroke-dashoffset', offset);
            }, 100);
        });
    });
    </script>
    <?php
    
    return ob_get_clean();
}
?>