/**
 * 수학인지관성 도감 JavaScript
 */

// 현재 필터 상태
let currentFilter = 'all';
let currentView = 'grid';

// DOM이 로드되면 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeViewToggle();
    updatePatternDisplay();
});

// 필터 초기화
function initializeFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 활성 버튼 업데이트
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // 필터 상태 업데이트
            currentFilter = this.getAttribute('data-filter');
            updatePatternDisplay();
        });
    });
}

// 뷰 토글 초기화
function initializeViewToggle() {
    const viewButtons = document.querySelectorAll('.view-btn');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 활성 버튼 업데이트
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // 뷰 상태 업데이트
            currentView = this.getAttribute('data-view');
            updateViewMode();
        });
    });
}

// 패턴 표시 업데이트
function updatePatternDisplay() {
    const categorySections = document.querySelectorAll('.category-section');
    
    categorySections.forEach(section => {
        const categoryId = section.getAttribute('data-category');
        
        if (currentFilter === 'all' || currentFilter === categoryId) {
            section.style.display = 'block';
            
            // 애니메이션 효과
            setTimeout(() => {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, 50);
        } else {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                section.style.display = 'none';
            }, 300);
        }
    });
}

// 뷰 모드 업데이트
function updateViewMode() {
    const container = document.querySelector('.categories-container');
    
    if (currentView === 'list') {
        container.classList.add('list-view');
    } else {
        container.classList.remove('list-view');
    }
}

// 패턴 상세 정보 표시
function showPatternDetail(patternId) {
    const modal = document.getElementById('pattern-detail-modal');
    const content = document.getElementById('pattern-detail-content');
    
    // 로딩 표시
    content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fa fa-spinner fa-spin"></i> 로딩 중...</div>';
    modal.style.display = 'flex';
    
    // AJAX로 상세 정보 가져오기
    fetch(`api/get_pattern_detail.php?pattern_id=${patternId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
                
                // 애니메이션 효과
                content.style.opacity = '0';
                setTimeout(() => {
                    content.style.opacity = '1';
                }, 50);
            } else {
                content.innerHTML = '<div style="text-align: center; padding: 40px; color: red;">오류: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div style="text-align: center; padding: 40px; color: red;">패턴 정보를 불러오는 중 오류가 발생했습니다.</div>';
        });
}

// 패턴 상세 정보 닫기
function closePatternDetail() {
    const modal = document.getElementById('pattern-detail-modal');
    modal.style.display = 'none';
}

// 연습 시작
function startPractice(patternId) {
    // 연습 페이지로 이동 또는 연습 모달 열기
    alert(`패턴 #${patternId} 연습을 시작합니다!`);
    // TODO: 실제 연습 기능 구현
}

// 메모 수정
function editNote(patternId) {
    const noteText = prompt('메모를 입력하세요:');
    
    if (noteText !== null) {
        // AJAX로 메모 저장
        fetch('api/save_pattern_note.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pattern_id: patternId,
                note: noteText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('메모가 저장되었습니다!');
                // 상세 정보 새로고침
                showPatternDetail(patternId);
            } else {
                alert('메모 저장 중 오류가 발생했습니다.');
            }
        });
    }
}

// 모달 외부 클릭 시 닫기
document.addEventListener('click', function(event) {
    const modal = document.getElementById('pattern-detail-modal');
    if (event.target === modal) {
        closePatternDetail();
    }
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePatternDetail();
    }
});

// 카드 클릭 애니메이션
document.addEventListener('DOMContentLoaded', function() {
    const patternCards = document.querySelectorAll('.pattern-card');
    
    patternCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });
});

// 진행률 애니메이션
function animateProgress() {
    const progressBar = document.querySelector('.progress-bar');
    const targetWidth = progressBar.style.width;
    
    progressBar.style.width = '0%';
    
    setTimeout(() => {
        progressBar.style.width = targetWidth;
    }, 100);
}

// 페이지 로드 시 진행률 애니메이션 실행
window.addEventListener('load', animateProgress);