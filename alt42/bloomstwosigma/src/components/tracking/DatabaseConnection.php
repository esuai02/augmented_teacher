<?php
/**
 * Database Connection Component
 * 데이터베이스 연결 및 테이블 선택 UI를 담당
 */

function renderDatabaseConnection() {
    return '
        <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold">
                    <i class="fas fa-database mr-2"></i>
                    DB 연결
                </h3>
                <div class="flex space-x-2">
                    <button 
                        id="selectDBBtn"
                        onclick="showDBTablesModal()"
                        class="px-3 py-1 bg-blue-500 hover:bg-blue-600 rounded text-sm"
                    >
                        <i class="fas fa-database mr-1"></i>
                        DB 선택하기
                    </button>
                    <button 
                        id="showDBInfoBtn"
                        onclick="showDBInfo()"
                        class="px-3 py-1 bg-green-500 hover:bg-green-600 rounded text-sm"
                    >
                        <i class="fas fa-info-circle mr-1"></i>
                        DB 정보
                    </button>
                </div>
            </div>
            
            <!-- 선택된 테이블 정보 -->
            <div id="selectedTableInfo" class="mb-4" style="display: none;">
                <div class="bg-blue-500/10 border border-blue-400/30 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium text-blue-300">선택된 테이블</h4>
                        <button onclick="clearSelectedTable()" class="text-gray-400 hover:text-white">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="text-sm text-gray-300" id="selectedTableName">-</div>
                    <div class="text-xs text-gray-400" id="selectedTableDetails">-</div>
                </div>
            </div>
            
            <!-- 필드 목록 -->
            <div id="fieldsSection" style="display: none;">
                <h4 class="font-medium mb-3 text-purple-300">
                    <i class="fas fa-list mr-2"></i>
                    테이블 필드
                </h4>
                <div class="space-y-2 max-h-64 overflow-y-auto mb-4" id="fieldsList">
                    <!-- JavaScript로 동적 생성 -->
                </div>
            </div>
            
            <!-- 조건 설정 -->
            <div id="conditionsSection" style="display: none;">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-green-300">
                        <i class="fas fa-filter mr-2"></i>
                        데이터 조건
                    </h4>
                    <button onclick="addCondition()" class="px-2 py-1 bg-green-500 hover:bg-green-600 rounded text-xs">
                        <i class="fas fa-plus mr-1"></i>
                        조건 추가
                    </button>
                </div>
                <div class="space-y-2" id="conditionsList">
                    <!-- JavaScript로 동적 생성 -->
                </div>
                
                <!-- SQL 미리보기 -->
                <div class="mt-4 p-3 bg-gray-800 rounded-lg">
                    <div class="text-xs text-gray-400 mb-1">SQL 미리보기:</div>
                    <div class="text-xs font-mono text-green-300" id="sqlPreview">SELECT * FROM table_name</div>
                </div>
                
                <!-- 실행 버튼 -->
                <div class="mt-4 flex space-x-2">
                    <button onclick="executeQuery()" class="px-3 py-1 bg-purple-500 hover:bg-purple-600 rounded text-sm">
                        <i class="fas fa-play mr-1"></i>
                        실행
                    </button>
                    <button onclick="saveQuery()" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 rounded text-sm">
                        <i class="fas fa-save mr-1"></i>
                        저장
                    </button>
                </div>
            </div>
            
            <!-- 초기 상태 -->
            <div id="initialState" class="text-gray-400 text-center py-8">
                <i class="fas fa-database text-4xl mb-2"></i>
                <p>DB 선택하기 버튼을 클릭하여 테이블을 선택하세요</p>
            </div>
        </div>';
}