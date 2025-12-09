<?php
/**
 * 토픽 카드 컴포넌트
 * 학습 토픽 카드를 렌더링합니다 (collapse 항목)
 */

/**
 * 토픽 카드 렌더링 함수
 * 
 * @param string $mode 렌더링 모드 (review, normal)
 * @param array $cardData 카드 데이터
 * @return string HTML 문자열
 */
function renderTopicCard($mode, $cardData) {
    // 데이터 추출
    $ntopic = $cardData['ntopic'] ?? 1;
    $ncolap = $cardData['ncolap'] ?? 1;
    $displaytext = $cardData['displaytext'] ?? '';
    $linkurl = $cardData['linkurl'] ?? '';
    $checkstatus = $cardData['checkstatus'] ?? '';
    $thismenutext = $cardData['thismenutext'] ?? '';
    $chkitemid = $cardData['chkitemid'] ?? '';
    $studentid = $cardData['studentid'] ?? '';
    $cid = $cardData['cid'] ?? '';
    $nstage = $cardData['nstage'] ?? 0;
    $alerttext = $cardData['alerttext'] ?? '';
    $alerttext2 = $cardData['alerttext2'] ?? '';
    $alerttext3 = $cardData['alerttext3'] ?? '';
    $isdone = $cardData['isdone'] ?? false;
    
    ob_start();
    
    if ($mode === 'review') {
        // 복습 모드 카드
        ?>
        <div class="card topic-card topic-card--review mb-3">
            <div class="card-header" 
                 role="button"
                 data-bs-toggle="collapse" 
                 data-bs-target="#collapse<?php echo $ncolap; ?>" 
                 aria-expanded="false" 
                 aria-controls="collapse<?php echo $ncolap; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="topic-number">Topic <?php echo $ntopic; ?>:</span>
                    <span class="topic-title"><?php echo htmlspecialchars($displaytext); ?></span>
                    <?php if ($checkstatus === 'checked'): ?>
                    <span class="badge bg-success">완료</span>
                    <?php endif; ?>
                </div>
            </div>
            <div id="collapse<?php echo $ncolap; ?>" class="collapse">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <?php if ($linkurl): ?>
                        <a href="<?php echo htmlspecialchars($linkurl); ?>" 
                           target="_blank" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i> 학습 시작
                        </a>
                        <?php endif; ?>
                        
                        <div class="form-check form-check-inline">
                            <input type="checkbox" 
                                   class="form-check-input topic-checkbox" 
                                   name="checkbox<?php echo $ntopic; ?>" 
                                   id="checkbox<?php echo $ntopic; ?>"
                                   onclick="CheckProgress(<?php echo $ntopic; ?>, '<?php echo $thismenutext; ?>', <?php echo $chkitemid; ?>, <?php echo $studentid; ?>, <?php echo $cid; ?>, <?php echo $nstage; ?>)"
                                   <?php echo $checkstatus; ?>>
                            <label class="form-check-label" for="checkbox<?php echo $ntopic; ?>">
                                학습 완료
                            </label>
                        </div>
                    </div>
                    
                    <?php if ($alerttext): ?>
                    <div class="alert alert-info mt-3" role="alert">
                        <i class="fas fa-info-circle"></i> <?php echo $alerttext; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($alerttext2): ?>
                    <div class="alert alert-warning mt-2" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $alerttext2; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($alerttext3): ?>
                    <div class="alert alert-danger mt-2" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $alerttext3; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    } else {
        // 일반 모드 카드
        ?>
        <div class="card topic-card topic-card--normal mb-3 <?php echo $isdone ? 'topic-card--done' : ''; ?>">
            <div class="card-header" 
                 role="button"
                 data-bs-toggle="collapse" 
                 data-bs-target="#collapse<?php echo $ncolap; ?>" 
                 aria-expanded="false" 
                 aria-controls="collapse<?php echo $ncolap; ?>">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="topic-number fw-bold">Topic <?php echo $ntopic; ?>:</span>
                    </div>
                    <div class="col">
                        <span class="topic-title"><?php echo htmlspecialchars($displaytext); ?></span>
                    </div>
                    <div class="col-auto">
                        <?php if ($checkstatus === 'checked'): ?>
                        <i class="fas fa-check-circle text-success"></i>
                        <?php else: ?>
                        <i class="fas fa-chevron-down"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="collapse<?php echo $ncolap; ?>" class="collapse">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <?php if ($linkurl): ?>
                            <a href="<?php echo htmlspecialchars($linkurl); ?>" 
                               target="_blank" 
                               class="btn btn-outline-primary mb-3">
                                <i class="fas fa-play-circle"></i> 학습 콘텐츠 보기
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($alerttext): ?>
                            <p class="text-muted">
                                <i class="fas fa-lightbulb"></i> <?php echo $alerttext; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input topic-checkbox" 
                                       name="checkbox<?php echo $ntopic; ?>" 
                                       id="checkbox<?php echo $ntopic; ?>"
                                       onclick="CheckProgress(<?php echo $ntopic; ?>, '<?php echo $thismenutext; ?>', <?php echo $chkitemid; ?>, <?php echo $studentid; ?>, <?php echo $cid; ?>, <?php echo $nstage; ?>)"
                                       <?php echo $checkstatus; ?>>
                                <label class="form-check-label" for="checkbox<?php echo $ntopic; ?>">
                                    이 토픽 완료
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    return ob_get_clean();
}

/**
 * 토픽 카드 그룹 렌더링 함수
 * 여러 토픽 카드를 그룹으로 렌더링합니다.
 * 
 * @param string $mode 렌더링 모드
 * @param array $cards 카드 데이터 배열
 * @param array $options 추가 옵션
 * @return string HTML 문자열
 */
function renderTopicCardGroup($mode, $cards, $options = []) {
    $defaults = [
        'containerClass' => 'topic-card-group',
        'showProgress' => true,
        'showFilter' => false
    ];
    
    $options = array_merge($defaults, $options);
    
    ob_start();
    ?>
    <div class="<?php echo $options['containerClass']; ?>">
        <?php if ($options['showFilter']): ?>
        <div class="topic-filter mb-3">
            <div class="btn-group" role="group" aria-label="Topic filter">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="filterTopics('all')">
                    전체
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="filterTopics('completed')">
                    완료
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterTopics('incomplete')">
                    미완료
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($options['showProgress']): ?>
        <div class="topic-progress mb-3">
            <?php
            $completed = 0;
            foreach ($cards as $card) {
                if ($card['checkstatus'] === 'checked') {
                    $completed++;
                }
            }
            $progress = count($cards) > 0 ? ($completed / count($cards)) * 100 : 0;
            ?>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-success" 
                     role="progressbar" 
                     style="width: <?php echo $progress; ?>%;"
                     aria-valuenow="<?php echo $progress; ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                    <?php echo $completed; ?> / <?php echo count($cards); ?> 완료
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="topic-cards">
            <?php foreach ($cards as $card): ?>
                <?php echo renderTopicCard($mode, $card); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * 토픽 카드 스타일 CSS
 * 토픽 카드 전용 스타일을 정의합니다.
 * 
 * @return string CSS 코드
 */
function getTopicCardStyles() {
    ob_start();
    ?>
    <style>
    /* 토픽 카드 기본 스타일 */
    .topic-card {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
        margin-bottom: 10px;
    }
    
    .topic-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .topic-card .card-header {
        cursor: pointer;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 12px 15px;
    }
    
    .topic-card .card-header:hover {
        background-color: #e9ecef;
    }
    
    .topic-card--done .card-header {
        background-color: #d4edda;
    }
    
    .topic-card--review .card-header {
        background-color: #cce5ff;
    }
    
    .topic-number {
        font-weight: 600;
        color: #495057;
        min-width: 80px;
    }
    
    .topic-title {
        color: #212529;
        flex-grow: 1;
    }
    
    .topic-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    /* 반응형 디자인 */
    @media (max-width: 768px) {
        .topic-card .card-header {
            padding: 10px 12px;
        }
        
        .topic-number {
            font-size: 14px;
        }
        
        .topic-title {
            font-size: 14px;
        }
        
        .topic-card .card-body {
            padding: 12px;
        }
    }
    
    /* 애니메이션 */
    .topic-card.fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* 필터 버튼 스타일 */
    .topic-filter .btn-group {
        width: 100%;
    }
    
    .topic-filter .btn {
        flex: 1;
    }
    </style>
    <?php
    
    return ob_get_clean();
}

/**
 * 토픽 카드 초기화 스크립트
 * 토픽 카드 관련 JavaScript 초기화 코드
 * 
 * @return string JavaScript 코드
 */
function initTopicCardScript() {
    ob_start();
    ?>
    <script>
    // 토픽 필터링 함수
    function filterTopics(filter) {
        const cards = document.querySelectorAll('.topic-card');
        
        cards.forEach(card => {
            const isCompleted = card.querySelector('.topic-checkbox:checked') !== null;
            
            switch(filter) {
                case 'all':
                    card.style.display = 'block';
                    break;
                case 'completed':
                    card.style.display = isCompleted ? 'block' : 'none';
                    break;
                case 'incomplete':
                    card.style.display = !isCompleted ? 'block' : 'none';
                    break;
            }
        });
    }
    
    // 토픽 카드 초기화
    document.addEventListener('DOMContentLoaded', function() {
        // Collapse 토글 아이콘 변경
        const collapseElements = document.querySelectorAll('.topic-card [data-bs-toggle="collapse"]');
        
        collapseElements.forEach(element => {
            const targetId = element.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            
            if (target) {
                target.addEventListener('show.bs.collapse', function() {
                    const icon = element.querySelector('.fa-chevron-down');
                    if (icon) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    }
                });
                
                target.addEventListener('hide.bs.collapse', function() {
                    const icon = element.querySelector('.fa-chevron-up');
                    if (icon) {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                });
            }
        });
        
        // 완료된 토픽 자동 접기
        const completedCards = document.querySelectorAll('.topic-card--done');
        completedCards.forEach(card => {
            const collapseElement = card.querySelector('.collapse');
            if (collapseElement && bootstrap.Collapse) {
                const bsCollapse = new bootstrap.Collapse(collapseElement, {
                    toggle: false
                });
            }
        });
        
        // 페이드인 애니메이션
        const allCards = document.querySelectorAll('.topic-card');
        allCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 50);
        });
    });
    </script>
    <?php
    
    return ob_get_clean();
}
?>