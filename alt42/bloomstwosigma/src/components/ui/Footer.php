<?php
/**
 * Footer Component
 * 시스템 푸터 상태바 UI를 담당
 */

function renderFooter() {
    return '
        <footer class="fixed bottom-0 left-0 right-0 bg-white/10 backdrop-blur-md border-t border-white/20 px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6 text-sm text-gray-300">
                    <span class="flex items-center">
                        <i class="fas fa-calendar mr-2"></i>
                        기간: <span id="footer-duration">8</span>주
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-brain mr-2"></i>
                        피드백: <span id="footer-feedback">0</span>개 선택
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-target mr-2"></i>
                        추적: <span id="footer-tracking">0</span>개 활성
                    </span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button class="text-sm text-blue-400 hover:text-blue-300 flex items-center">
                        <i class="fas fa-book-open mr-1"></i>
                        실험 가이드
                    </button>
                    <button class="text-sm text-purple-400 hover:text-purple-300 flex items-center">
                        <i class="fas fa-info mr-1"></i>
                        도움말
                    </button>
                </div>
            </div>
        </footer>';
}