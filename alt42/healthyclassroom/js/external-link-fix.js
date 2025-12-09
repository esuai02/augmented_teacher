// 외부링크 카드 중복 생성 방지 스크립트
(function() {
    // 외부링크 카드가 이미 초기화되었는지 확인하는 플래그
    let externalLinkCardsInitialized = false;
    
    // 외부링크 카드 초기화 함수
    function initializeExternalLinkCards() {
        // 이미 초기화되었으면 중복 실행 방지
        if (externalLinkCardsInitialized) {
            console.log('External link cards already initialized');
            return;
        }
        
        // 기존 외부링크 카드 제거
        const existingCards = document.querySelectorAll('.external-link-card');
        existingCards.forEach(card => {
            card.remove();
        });
        
        // 새로운 외부링크 카드 생성 로직
        // (실제 카드 생성 코드가 있다면 여기에 추가)
        
        // 초기화 완료 플래그 설정
        externalLinkCardsInitialized = true;
    }
    
    // 페이지 네비게이션 시 플래그 리셋
    function resetExternalLinkCards() {
        externalLinkCardsInitialized = false;
        // 기존 카드 모두 제거
        const allCards = document.querySelectorAll('.external-link-card');
        allCards.forEach(card => {
            card.remove();
        });
    }
    
    // DOM 로드 시 한 번만 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeExternalLinkCards);
    } else {
        initializeExternalLinkCards();
    }
    
    // 페이지 visibility 변경 시 처리
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            // 페이지가 다시 보일 때 중복 카드 제거
            const cards = document.querySelectorAll('.external-link-card');
            if (cards.length > 1) {
                // 첫 번째 카드만 남기고 나머지 제거
                for (let i = 1; i < cards.length; i++) {
                    cards[i].remove();
                }
            }
        }
    });
    
    // MutationObserver로 DOM 변경 감지
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // 외부링크 카드가 추가되었는지 확인
                const cards = document.querySelectorAll('.external-link-card');
                if (cards.length > 1) {
                    // 중복 카드 제거 (첫 번째만 남김)
                    for (let i = 1; i < cards.length; i++) {
                        cards[i].remove();
                    }
                }
            }
        });
    });
    
    // body 요소 감시 시작
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // 전역 함수로 노출 (필요시 외부에서 호출 가능)
    window.resetExternalLinkCards = resetExternalLinkCards;
    window.initializeExternalLinkCards = initializeExternalLinkCards;
})();