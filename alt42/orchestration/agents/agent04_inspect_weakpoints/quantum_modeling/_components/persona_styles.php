<?php
/**
 * 페르소나 시뮬레이터 전용 스타일 컴포넌트
 * @package AugmentedTeacher\Agent04\QuantumModeling\Components
 */
?>
<style>
    /* 상황 코드 탭 */
    .context-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .context-tab {
        padding: 12px 20px;
        border-radius: 10px;
        background: var(--bg-dark);
        border: 2px solid var(--border);
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        min-width: 120px;
        text-decoration: none;
        color: var(--text-primary);
    }
    
    .context-tab:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
    }
    
    .context-tab.active {
        background: var(--primary);
        border-color: var(--primary);
    }
    
    .context-tab.critical {
        border-color: var(--danger);
    }
    
    .context-tab.critical.active {
        background: var(--danger);
    }
    
    .context-tab-icon {
        font-size: 1.5rem;
        display: block;
        margin-bottom: 5px;
    }
    
    .context-tab-name {
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    /* 페르소나 카드 그리드 */
    .persona-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }
    
    .persona-card {
        background: var(--bg-dark);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .persona-card:hover {
        border-color: var(--primary);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
    }
    
    .persona-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .persona-icon {
        font-size: 2rem;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-card);
        border-radius: 12px;
    }
    
    .persona-name {
        font-weight: 600;
        font-size: 1rem;
    }
    
    .persona-name-en {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }
    
    .persona-desc {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 12px;
        line-height: 1.5;
    }
    
    .persona-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }
    
    .persona-tag {
        padding: 4px 10px;
        background: var(--bg-card);
        border-radius: 15px;
        font-size: 0.75rem;
        color: var(--text-secondary);
    }
    
    .persona-intervention {
        padding: 12px;
        background: rgba(99, 102, 241, 0.1);
        border-radius: 8px;
        margin-top: 10px;
    }
    
    .persona-intervention-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 6px;
    }
    
    .persona-intervention-content {
        font-size: 0.85rem;
    }
    
    /* 톤 카드 */
    .tone-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin-top: 15px;
    }
    
    .tone-card {
        padding: 15px;
        background: var(--bg-dark);
        border-radius: 10px;
        text-align: center;
        border: 1px solid var(--border);
    }
    
    .tone-card.active {
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.1);
    }
    
    .tone-icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    
    .tone-name {
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    /* 메시지 입력 영역 */
    .message-input-area {
        margin-bottom: 20px;
    }
    
    .message-input {
        width: 100%;
        padding: 15px;
        background: var(--bg-dark);
        border: 1px solid var(--border);
        border-radius: 12px;
        color: var(--text-primary);
        font-size: 1rem;
        resize: vertical;
        min-height: 80px;
    }
    
    .message-input:focus {
        outline: none;
        border-color: var(--primary);
    }
    
    /* 시뮬레이션 결과 */
    .sim-result-box {
        padding: 20px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
        border-radius: 12px;
        border: 1px solid var(--primary);
        margin-top: 20px;
    }
    
    .sim-result-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }
    
    .sim-context-badge {
        padding: 8px 16px;
        background: var(--primary);
        border-radius: 20px;
        font-weight: 600;
    }
    
    .sim-persona-badge {
        padding: 8px 16px;
        background: var(--secondary);
        border-radius: 20px;
        font-weight: 600;
    }
    
    .sim-actions-list {
        margin-top: 15px;
    }
    
    .sim-action-item {
        padding: 10px 15px;
        background: var(--bg-dark);
        border-radius: 8px;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }
    
    /* 양자 상태 미니 뷰 */
    .quantum-mini-view {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .quantum-mini-item {
        flex: 1;
        padding: 10px;
        background: var(--bg-dark);
        border-radius: 8px;
        text-align: center;
    }
    
    .quantum-mini-label {
        font-size: 0.7rem;
        color: var(--text-secondary);
    }
    
    .quantum-mini-value {
        font-size: 1.2rem;
        font-weight: 700;
    }
    
    /* 뷰 모드 전환 */
    .view-mode-switch {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--bg-dark);
        padding: 6px;
        border-radius: 10px;
        border: 1px solid var(--border);
    }
    
    .view-mode-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: var(--text-secondary);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .view-mode-btn:hover {
        color: var(--text-primary);
    }
    
    .view-mode-btn.active {
        background: var(--primary);
        color: white;
    }
    
    /* 메인 탭 네비게이션 */
    .main-tabs {
        display: flex;
        gap: 5px;
        margin-bottom: 20px;
        background: var(--bg-card);
        padding: 8px;
        border-radius: 12px;
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    
    .main-tab {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: var(--text-secondary);
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .main-tab:hover {
        background: var(--bg-dark);
        color: var(--text-primary);
    }
    
    .main-tab.active {
        background: var(--primary);
        color: white;
    }
    
    /* 탭 컨텐츠 */
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* 스크롤뷰 모드 */
    .scroll-view .tab-content {
        display: block !important;
    }
    
    .scroll-view .main-tabs {
        display: none;
    }
    
    /* 탭뷰 모드에서 grid 조정 */
    .tab-view .grid {
        display: block;
    }
    
    .tab-view .col-4,
    .tab-view .col-6,
    .tab-view .col-8,
    .tab-view .col-12 {
        grid-column: span 12;
        margin-bottom: 20px;
    }
    
    /* 탭 컨텐츠 내부 그리드 */
    .tab-inner-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 20px;
    }
    
    @media (max-width: 1200px) {
        .tab-inner-grid .col-4,
        .tab-inner-grid .col-6,
        .tab-inner-grid .col-8 {
            grid-column: span 12;
        }
    }
</style>

