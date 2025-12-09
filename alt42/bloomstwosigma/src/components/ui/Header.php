<?php
/**
 * Header Component
 * 시스템 헤더 UI를 담당
 */

function renderHeader($user, $role) {
    return '
        <header class="header bg-white/10 backdrop-blur-md border-b border-white/20">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-flask mr-3 text-purple-400"></i>
                            BTS 실험 추적 시스템
                        </h1>
                        <p class="text-sm text-gray-300 mt-1">
                            Beyond Traditional Science - 전인교육 실험 플랫폼
                        </p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="user-info text-sm">
                            <span>사용자: ' . htmlspecialchars($user->firstname . ' ' . $user->lastname) . '</span>
                            <span class="ml-4">역할: ' . htmlspecialchars($role) . '</span>
                        </div>
                        <div class="px-4 py-2 rounded-lg bg-blue-500/20 text-blue-400 flex items-center space-x-2">
                            <i class="fas fa-users"></i>
                            <span class="text-sm" id="participant-count">참가자: 0명</span>
                        </div>
                        <button class="p-2 bg-white/10 hover:bg-white/20 rounded-lg">
                            <i class="fas fa-save"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>';
}