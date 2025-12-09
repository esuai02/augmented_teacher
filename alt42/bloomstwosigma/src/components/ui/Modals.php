<?php
/**
 * Modals Component
 * 모든 모달 UI를 담당
 */

function renderModals() {
    return '
        <!-- DB 테이블 선택 모달 -->
        <div id="dbTablesModal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-4xl max-h-[80vh] overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold">
                        <i class="fas fa-database mr-2"></i>
                        DB 테이블 선택
                    </h3>
                    <button onclick="closeModal(\'dbTablesModal\')" class="p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- 검색 -->
                <div class="mb-4">
                    <input
                        type="text"
                        id="modalTableSearchInput"
                        class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="테이블 검색..."
                        onkeyup="searchModalTables()"
                    />
                </div>
                
                <!-- 선택된 테이블 편집 섹션 -->
                <div id="selectedTableEditSection" class="mb-4 p-4 bg-white/5 border border-white/20 rounded-lg" style="display: none;">
                    <h4 class="text-sm font-semibold mb-3 text-purple-300">
                        <i class="fas fa-edit mr-2"></i>
                        테이블 설명 편집
                    </h4>
                    <div class="mb-2">
                        <span class="text-sm text-gray-400">선택된 테이블:</span>
                        <span id="selectedTableNameDisplay" class="text-sm font-medium text-white ml-2">-</span>
                    </div>
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-400 mb-1">타입</label>
                            <select
                                id="editTableType"
                                class="w-full px-2 py-2 bg-white border border-white/20 rounded text-sm text-black focus:outline-none focus:ring-2 focus:ring-purple-500"
                                onchange="updateTableDescriptionFromEdit()">
                                <option value="사용자 정보">사용자 정보</option>
                                <option value="강좌정보">강좌정보</option>
                                <option value="활동정보">활동정보</option>
                                <option value="목표 및 계획">목표 및 계획</option>
                                <option value="출결정보">출결정보</option>
                                <option value="시험대비">시험대비</option>
                                <option value="컨텐츠 활용">컨텐츠 활용</option>
                            </select>
                        </div>
                        <div class="col-span-10">
                            <label class="block text-xs text-gray-400 mb-1">설명</label>
                            <input
                                type="text"
                                id="editTableDescription"
                                class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="테이블 설명을 입력하세요..."
                                onchange="updateTableDescriptionFromEdit()"
                            />
                        </div>
                    </div>
                </div>
                
                <!-- 테이블 목록 -->
                <div class="space-y-2 max-h-96 overflow-y-auto mb-4" id="modalDbTablesList">
                    <div class="text-gray-400 text-center py-4">테이블 목록을 로딩중...</div>
                </div>
                
                <!-- 페이지네이션 -->
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-400" id="modalTablesInfo">-</div>
                    <div class="flex space-x-2" id="modalPagination">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>
            </div>
        </div>

        <!-- 테이블 데이터 미리보기 모달 -->
        <div id="tablePreviewModal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-6xl max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold" id="previewTableTitle">
                        <i class="fas fa-table mr-2"></i>
                        테이블 미리보기
                    </h3>
                    <button onclick="closeModal(\'tablePreviewModal\')" class="p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="previewTableContent">
                    <!-- JavaScript로 동적 생성 -->
                </div>
            </div>
        </div>

        <!-- DB 정보 모달 -->
        <div id="dbInfoModal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold">
                        <i class="fas fa-info-circle mr-2"></i>
                        DB 정보
                    </h3>
                    <button onclick="closeModal(\'dbInfoModal\')" class="p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="dbInfoContent">
                    <!-- JavaScript로 동적 생성 -->
                </div>
            </div>
        </div>

        <!-- 피드백 선택 모달 -->
        <div id="feedback-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold" id="feedback-modal-title">피드백 선택</h3>
                    <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 gap-3" id="feedback-list">
                    <!-- JavaScript로 동적 생성 -->
                </div>
                
                <div class="flex justify-end mt-6">
                    <button class="close-modal px-6 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg">
                        확인
                    </button>
                </div>
            </div>
        </div>

        <!-- 추적 설정 추가 모달 -->
        <div id="tracking-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold">추적 설정 추가</h3>
                    <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="tracking-form">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">설정명</label>
                            <input
                                type="text"
                                id="tracking-name"
                                class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="예: 문제해결 속도 향상도"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">설명</label>
                            <textarea
                                id="tracking-description"
                                class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                rows="3"
                                placeholder="추적 목적과 내용을 설명해주세요..."
                            ></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button" class="close-modal px-6 py-2 bg-gray-500 hover:bg-gray-600 rounded-lg">
                            취소
                        </button>
                        <button type="submit" class="px-6 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg">
                            추가
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 설문 모달 -->
        <div id="survey-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold">실험별 맞춤 설문</h3>
                    <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- 설문 생성 도구 -->
                    <div class="bg-white/5 p-4 rounded-lg">
                        <h4 class="font-semibold mb-3">새 설문 항목 추가</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">질문 유형</label>
                                <select class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm">
                                    <option>Likert 척도 (5점)</option>
                                    <option>객관식 (4지선다)</option>
                                    <option>주관식 (서술형)</option>
                                    <option>평점 (10점 척도)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">카테고리</label>
                                <select class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm">
                                    <option>메타인지 인식</option>
                                    <option>학습 만족도</option>
                                    <option>피드백 효과성</option>
                                    <option>자기효능감</option>
                                    <option>학습 동기</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium mb-2">질문 내용</label>
                            <textarea
                                class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm"
                                rows="2"
                                placeholder="예: 메타인지 피드백을 받은 후 문제 해결 능력이 향상되었다고 느낍니까?"
                            ></textarea>
                        </div>
                        <button class="mt-3 px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded text-sm">
                            질문 추가
                        </button>
                    </div>

                    <!-- 기존 설문 목록 -->
                    <div>
                        <h4 class="font-semibold mb-3">실험 맞춤 설문 항목</h4>
                        <div class="space-y-3" id="survey-items">
                            <!-- JavaScript로 동적 생성 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 분석 모달 -->
        <div id="analysis-modal" class="modal fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-content bg-slate-800 border border-white/20 rounded-xl p-6 w-full max-w-5xl max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold">분석 보고서</h3>
                    <button class="close-modal p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- 분석 요약 -->
                    <div class="bg-white/5 p-4 rounded-lg">
                        <h4 class="font-semibold mb-3">분석 요약</h4>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="bg-blue-500/20 p-3 rounded">
                                <div class="text-2xl font-bold text-blue-300">85%</div>
                                <div class="text-sm text-gray-300">만족도</div>
                            </div>
                            <div class="bg-green-500/20 p-3 rounded">
                                <div class="text-2xl font-bold text-green-300">92%</div>
                                <div class="text-sm text-gray-300">효과성</div>
                            </div>
                            <div class="bg-purple-500/20 p-3 rounded">
                                <div class="text-2xl font-bold text-purple-300">78%</div>
                                <div class="text-sm text-gray-300">참여도</div>
                            </div>
                        </div>
                    </div>

                    <!-- 상세 분석 -->
                    <div>
                        <h4 class="font-semibold mb-3">상세 분석 결과</h4>
                        <div class="space-y-4" id="analysis-details">
                            <!-- JavaScript로 동적 생성 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>';
}