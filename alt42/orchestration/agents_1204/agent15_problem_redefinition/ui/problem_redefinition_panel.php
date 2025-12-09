<?php
/**
 * Agent 15: 문제 재정의 & 개선방안 - 우측 패널 UI
 *
 * 이 컴포넌트는 orchestration_hs2의 Step 15 기능을 agent15 폴더로 이식한 것입니다.
 * "문제 재정의 가져오기" 버튼 클릭 시 우측 패널에 내용을 표시합니다.
 *
 * File: alt42/orchestration/agents/agent15_problem_redefinition/ui/problem_redefinition_panel.php
 * Error: line number in errors
 */
?>

<div id="agent15-problem-redefinition-panel" class="agent15-panel">
    <h3>🔄 문제 재정의 & 개선방안</h3>

    <div class="problem-redefinition-container">
        <div class="textarea-wrapper">
            <textarea id="agent15-problem-redefinition-text"
                      placeholder="'문제 재정의 가져오기' 버튼을 클릭하면 자동 생성된 문제 재정의 내용이 표시됩니다. 직접 수정 가능합니다."
                      rows="15"
                      style="width: 100%; padding: 15px; font-size: 14px; line-height: 1.6; border: 1px solid #ddd; border-radius: 8px; resize: vertical;"></textarea>
        </div>

        <div class="button-container" style="margin-top: 15px; display: flex; gap: 10px;">
            <button id="agent15-fetch-problem-redefinition-btn"
                    onclick="agent15FetchProblemRedefinition()"
                    class="btn-primary"
                    style="padding: 10px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                📊 문제 재정의 가져오기
            </button>

            <button id="agent15-save-problem-redefinition-btn"
                    onclick="agent15SaveProblemRedefinition()"
                    class="btn-secondary"
                    style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                💾 저장
            </button>

            <div id="agent15-loading" style="display: none; align-items: center;">
                <span class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: inline-block;"></span>
                <span style="margin-left: 10px;">데이터 수집 및 분석 중...</span>
            </div>
        </div>

        <div id="agent15-status" style="margin-top: 10px; padding: 10px; border-radius: 8px; display: none;"></div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.agent15-panel {
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.agent15-panel h3 {
    margin-bottom: 20px;
    color: #495057;
    font-size: 18px;
    font-weight: 600;
}

.btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.btn-secondary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

#agent15-status.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

#agent15-status.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

#agent15-status.info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}
</style>
