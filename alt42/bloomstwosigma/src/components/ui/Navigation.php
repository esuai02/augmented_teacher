<?php
/**
 * Navigation Component
 * 탭 네비게이션 UI를 담당
 */

function renderNavigation() {
    $tabs = [
        'design' => ['icon' => 'fas fa-cog', 'label' => '실험 설계'],
        'groups' => ['icon' => 'fas fa-users', 'label' => '그룹 배정'],
        'tracking' => ['icon' => 'fas fa-database', 'label' => '데이터 추적'],
        'experiment' => ['icon' => 'fas fa-file-text', 'label' => '실험 기록']
    ];
    
    $navHTML = '<nav class="navigation bg-white/5 border-b border-white/20">
        <div class="px-6 flex space-x-6">';
    
    foreach ($tabs as $tabId => $tab) {
        $activeClass = $tabId === 'design' ? 'active' : '';
        $navHTML .= '
            <button class="nav-tab ' . $activeClass . '" data-tab="' . $tabId . '">
                <i class="' . $tab['icon'] . '"></i>
                <span>' . $tab['label'] . '</span>
            </button>';
    }
    
    $navHTML .= '</div></nav>';
    
    return $navHTML;
}