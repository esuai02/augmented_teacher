document.addEventListener('DOMContentLoaded', function() {
    // 현재 페이지 URL
    const currentUrl = window.location.href;
    
    // 모든 메뉴 아이템 선택
    const navItems = document.querySelectorAll('.sidebar-wrapper .nav-item');
    
    // 각 메뉴 아이템을 확인하여 현재 페이지에 해당하는 항목 활성화
    navItems.forEach(item => {
        const link = item.querySelector('a');
        if (link && currentUrl.includes(link.getAttribute('href'))) {
            item.classList.add('active');
        }
    });
}); 