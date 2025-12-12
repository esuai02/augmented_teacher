<?php
/**
 * 양자 모델링 대시보드 스타일 컴포넌트
 * @package AugmentedTeacher\Agent04\QuantumModeling\Components
 */
?>
<style>
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #8b5cf6;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --bg-dark: #0f172a;
        --bg-card: #1e293b;
        --bg-card-hover: #334155;
        --text-primary: #f1f5f9;
        --text-secondary: #94a3b8;
        --border: #334155;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--bg-dark);
        color: var(--text-primary);
        min-height: 100vh;
        line-height: 1.6;
    }
    
    .dashboard {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }
    
    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: 16px;
    }
    
    .header h1 {
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .header .version {
        font-size: 0.8rem;
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 20px;
    }
    
    /* Grid Layout */
    .grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 20px;
    }
    
    .col-4 { grid-column: span 4; }
    .col-6 { grid-column: span 6; }
    .col-8 { grid-column: span 8; }
    .col-12 { grid-column: span 12; }
    
    @media (max-width: 1200px) {
        .col-4, .col-6, .col-8 { grid-column: span 12; }
    }
    
    /* Cards */
    .card {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        border-color: var(--primary);
        box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border);
    }
    
    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Form Controls */
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-label {
        display: block;
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-dark);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--text-primary);
        font-size: 0.95rem;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
    
    select.form-control {
        cursor: pointer;
    }
    
    /* Range Slider */
    .range-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .range-slider {
        flex: 1;
        -webkit-appearance: none;
        height: 6px;
        border-radius: 3px;
        background: var(--border);
    }
    
    .range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
    }
    
    .range-value {
        min-width: 45px;
        text-align: right;
        font-weight: 600;
        color: var(--primary);
    }
    
    /* Buttons */
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: var(--bg-dark);
        color: var(--text-primary);
        border: 1px solid var(--border);
    }
    
    /* State Vector Display */
    .state-vector {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-top: 20px;
    }
    
    .state-item {
        text-align: center;
        padding: 15px;
        background: var(--bg-dark);
        border-radius: 12px;
        border: 1px solid var(--border);
    }
    
    .state-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }
    
    .state-name {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 5px;
    }
    
    .state-value {
        font-size: 1.4rem;
        font-weight: 700;
    }
    
    .state-value.high { color: var(--success); }
    .state-value.medium { color: var(--warning); }
    .state-value.low { color: var(--danger); }
    
    /* Dynamics Display */
    .dynamics-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    
    .dynamics-item {
        padding: 20px;
        background: var(--bg-dark);
        border-radius: 12px;
        text-align: center;
    }
    
    .dynamics-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }
    
    .dynamics-value {
        font-size: 1.8rem;
        font-weight: 700;
    }
    
    .dynamics-value.synergy { color: var(--success); }
    .dynamics-value.backfire { color: var(--danger); }
    .dynamics-value.golden { color: var(--warning); }
    
    /* Progress Bar */
    .progress-bar {
        height: 8px;
        background: var(--bg-dark);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 10px;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    
    .progress-fill.synergy { background: linear-gradient(90deg, var(--success), #34d399); }
    .progress-fill.backfire { background: linear-gradient(90deg, var(--danger), #f87171); }
    
    /* Recommendation Box */
    .recommendation-box {
        padding: 20px;
        border-radius: 12px;
        margin-top: 20px;
    }
    
    .recommendation-box.normal {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid var(--success);
    }
    
    .recommendation-box.high {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid var(--warning);
    }
    
    .recommendation-box.critical {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid var(--danger);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .recommendation-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        margin-bottom: 12px;
    }
    
    .recommendation-item {
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .recommendation-item:last-child {
        border-bottom: none;
    }
    
    /* Switching Path */
    .switching-path {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 20px;
        background: var(--bg-dark);
        border-radius: 12px;
        margin-top: 15px;
    }
    
    .path-node {
        padding: 10px 20px;
        background: var(--primary);
        border-radius: 20px;
        font-weight: 600;
    }
    
    .path-arrow {
        color: var(--text-secondary);
        font-size: 1.2rem;
    }
    
    /* History Table */
    .history-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .history-table th,
    .history-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }
    
    .history-table th {
        color: var(--text-secondary);
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .history-table tr:hover {
        background: var(--bg-card-hover);
    }
    
    /* Chart Container */
    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 15px;
    }
    
    /* Persona Badge */
    .persona-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: var(--primary);
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    /* Interference Display */
    .interference-display {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 20px;
        background: var(--bg-dark);
        border-radius: 12px;
        margin-top: 15px;
    }
    
    .interference-type {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
    }
    
    .interference-type.constructive { background: rgba(16, 185, 129, 0.2); color: var(--success); }
    .interference-type.destructive { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
    .interference-type.neutral { background: rgba(148, 163, 184, 0.2); color: var(--text-secondary); }
    
    /* Status Indicator */
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    
    .status-indicator.active { background: var(--success); box-shadow: 0 0 10px var(--success); }
    .status-indicator.warning { background: var(--warning); box-shadow: 0 0 10px var(--warning); }
    .status-indicator.danger { background: var(--danger); box-shadow: 0 0 10px var(--danger); }
</style>

