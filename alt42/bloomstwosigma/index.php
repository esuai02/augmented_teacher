<?php
/**
 * BTS 실험 추적 시스템 - 메인 페이지
 * 전인교육 실험 플랫폼
 */

// Moodle 설정 및 인증
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET["userid"] ?? null;
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? 'student';

// 컴포넌트 로드
require_once(__DIR__ . '/src/components/ui/Header.php');
require_once(__DIR__ . '/src/components/ui/Navigation.php');
require_once(__DIR__ . '/src/components/ui/Footer.php');
require_once(__DIR__ . '/src/components/ui/Modals.php');
require_once(__DIR__ . '/src/components/tracking/DatabaseConnection.php');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTS 실험 추적 시스템</title>
    <link rel="stylesheet" href="src/styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="min-h-screen text-white bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="min-h-screen flex flex-col">
        <?php echo renderHeader($USER, $role); ?>
        
        <?php echo renderNavigation(); ?>

        <!-- 메인 콘텐츠 -->
        <main class="main-content p-6">
            <!-- 2칼럼 레이아웃 -->
            <div class="grid grid-cols-12 gap-6 h-full">
                <!-- 첫 번째 칼럼: BTS 축적 시스템 (4칼럼) -->
                <div class="col-span-4">
                    <div class="card bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20 h-fit">
                    <h3 class="text-xl font-semibold mb-6 flex items-center">
                        <i class="fas fa-database mr-2 text-purple-400"></i>
                        BTS 축적 시스템
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- 주요 지표 -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-purple-500/10 rounded-lg border border-purple-400/20">
                                <span class="text-sm text-gray-300">진행중인 실험</span>
                                <span class="text-xl font-bold text-purple-400" id="dashboard-active-experiments">0</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-green-500/10 rounded-lg border border-green-400/20">
                                <span class="text-sm text-gray-300">완료된 실험</span>
                                <span class="text-xl font-bold text-green-400" id="dashboard-completed-experiments">0</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-500/10 rounded-lg border border-blue-400/20">
                                <span class="text-sm text-gray-300">총 참가자</span>
                                <span class="text-xl font-bold text-blue-400" id="dashboard-total-participants">0</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-yellow-500/10 rounded-lg border border-yellow-400/20">
                                <span class="text-sm text-gray-300">데이터 수집</span>
                                <span class="text-xl font-bold text-yellow-400" id="dashboard-data-collected">0</span>
                            </div>
                        </div>
                        
                        <!-- 성과 진행률 -->
                        <div class="space-y-3">
                            <h4 class="text-lg font-medium flex items-center">
                                <i class="fas fa-chart-line mr-2 text-orange-400"></i>
                                성과 진행률
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300">성공률</span>
                                        <span class="text-green-400">75%</span>
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: 75%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300">참여도</span>
                                        <span class="text-blue-400">88%</span>
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 88%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300">효과성</span>
                                        <span class="text-purple-400">92%</span>
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-purple-500 h-2 rounded-full" style="width: 92%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 최근 활동 -->
                        <div>
                            <h4 class="text-lg font-medium mb-3 flex items-center">
                                <i class="fas fa-clock mr-2 text-green-400"></i>
                                최근 활동
                            </h4>
                            <div class="space-y-2 max-h-48 overflow-y-auto" id="recent-activities">
                                <div class="text-sm text-gray-300">활동 내역이 없습니다.</div>
                            </div>
                        </div>
                        
                        <!-- 알림 -->
                        <div>
                            <h4 class="text-lg font-medium mb-3 flex items-center">
                                <i class="fas fa-bell mr-2 text-red-400"></i>
                                시스템 알림
                            </h4>
                            <div class="space-y-2 max-h-32 overflow-y-auto" id="notifications">
                                <div class="text-sm text-gray-300">새로운 알림이 없습니다.</div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
                
                <!-- 두 번째 칼럼: 탭 컨텐츠 (8칼럼) -->
                <div class="col-span-8 flex flex-col h-full">
                    <!-- 콘텐츠 영역 -->
                    <div class="flex-1 overflow-y-auto bg-white/5 rounded-xl border border-white/20 p-6">
                    
                    <!-- 실험 설계 탭 -->
                    <section id="design-tab" class="tab-content active">
                        <div class="space-y-6">
                            <!-- 실험 기본 설정 -->
                            <div class="card mb-6">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-cog mr-2"></i>
                        실험 기본 설정
                    </h3>
                    
                    <form id="experiment-config-form">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-2">실험명</label>
                                <input
                                    type="text"
                                    id="experiment-name"
                                    name="experiment-name"
                                    class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    placeholder="예: 메타인지 피드백 효과 검증 실험"
                                />
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-2">설명</label>
                                <textarea
                                    id="experiment-description"
                                    name="experiment-description"
                                    class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    rows="3"
                                    placeholder="실험의 목적과 배경을 간단히 설명해주세요..."
                                ></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">시작일</label>
                                <input
                                    type="date"
                                    id="start-date"
                                    name="start-date"
                                    class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">실험 기간 (주)</label>
                                <input
                                    type="number"
                                    id="duration"
                                    name="duration"
                                    value="8"
                                    min="1"
                                    max="24"
                                    class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                />
                            </div>
                        </div>
                        
                        <button type="submit" class="mt-6 px-6 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg">
                            <i class="fas fa-save mr-2"></i>
                            실험 설정 저장
                        </button>
                    </form>
                            </div>

                            <!-- 개입 방법 -->
                            <div class="card mb-6">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-brain mr-2"></i>
                        개입 방법
                    </h3>
                    
                    <div class="space-y-3">
                        <button 
                            class="feedback-method w-full p-4 rounded-lg border-2 border-purple-400/30 bg-purple-500/10 hover:bg-purple-500/20 transition-all text-left"
                            data-type="metacognitive"
                            onclick="selectFeedbackMethod('metacognitive')"
                        >
                            <h4 class="font-semibold mb-1">메타인지 피드백</h4>
                            <p class="text-sm text-gray-300 mb-2">자기점검 질문, 조건 확인 유도</p>
                            <p class="text-xs text-purple-300" id="metacognitive-count">선택됨: 0개</p>
                        </button>
                        
                        <button 
                            class="feedback-method w-full p-4 rounded-lg border-2 border-blue-400/30 bg-blue-500/10 hover:bg-blue-500/20 transition-all text-left"
                            data-type="learning"
                            onclick="selectFeedbackMethod('learning')"
                        >
                            <h4 class="font-semibold mb-1">학습인지 피드백</h4>
                            <p class="text-sm text-gray-300 mb-2">콘텐츠 요약, 전략 제안</p>
                            <p class="text-xs text-blue-300" id="learning-count">선택됨: 0개</p>
                        </button>
                        
                        <button 
                            class="feedback-method w-full p-4 rounded-lg border-2 border-green-400/30 bg-green-500/10 hover:bg-green-500/20 transition-all text-left"
                            data-type="combined"
                            onclick="selectFeedbackMethod('combined')"
                        >
                            <h4 class="font-semibold mb-1">결합형 피드백</h4>
                            <p class="text-sm text-gray-300 mb-2">메타인지 + 학습인지 교차</p>
                            <p class="text-xs text-green-300" id="combined-count">선택됨: 0개</p>
                        </button>
                        
                        <div class="w-full p-4 rounded-lg border-2 border-gray-400/30 bg-gray-500/10">
                            <h4 class="font-semibold mb-1">통제그룹</h4>
                            <p class="text-sm text-gray-300 mb-2">선택된 피드백 비활성화</p>
                            <p class="text-xs text-gray-400" id="disabled-count">비활성화됨: 0개</p>
                        </div>
                    </div>
                            </div>

                            <!-- 측정 지표 선택 -->
                            <div class="card">
                                <h3 class="text-xl font-semibold mb-4 flex items-center">
                                    <i class="fas fa-target mr-2"></i>
                                    측정 지표 선택
                                </h3>
                                
                                <div class="space-y-3" id="tracking-configs">
                                    <!-- JavaScript로 동적 생성 -->
                                </div>
                            </div>
                        </div>
                    </section>

                    
                    <!-- 그룹 배정 탭 -->
                    <section id="groups-tab" class="tab-content">
                        <div class="space-y-6">
                            <!-- 선생님 선택 -->
                            <div class="card mb-6">
                    <h3 class="text-xl font-semibold mb-4">선생님 선택</h3>
                    <select 
                        id="teacher-select"
                        class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        onchange="loadTeacherStudents()"
                    >
                        <option value="">선생님을 선택하세요</option>
                        <option value="김선생님">김선생님</option>
                        <option value="박선생님">박선생님</option>
                        <option value="이선생님">이선생님</option>
                    </select>
                            </div>

                            <div id="group-assignment-container" class="grid grid-cols-3 gap-6" style="display: none;">
                                <!-- 학생 목록 -->
                                <div class="card">
                                    <h3 class="text-lg font-semibold mb-4" id="teacher-students-title">학생 목록</h3>
                                    <div class="space-y-2 max-h-96 overflow-y-auto" id="available-students"></div>
                                </div>

                                <!-- 통제 그룹 -->
                                <div class="card">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold">통제 그룹 (<span id="control-group-count">0</span>명)</h3>
                                        <button 
                                            id="add-to-control"
                                            class="text-sm px-3 py-1 bg-gray-500 hover:bg-gray-600 rounded disabled:opacity-50"
                                            disabled
                                            onclick="addToControlGroup()"
                                        >
                                            선택된 학생 추가
                                        </button>
                                    </div>
                                    <div class="space-y-2 max-h-96 overflow-y-auto" id="control-group"></div>
                                </div>

                                <!-- 실험 그룹 -->
                                <div class="card">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold">실험 그룹 (<span id="experiment-group-count">0</span>명)</h3>
                                        <button 
                                            id="add-to-experiment"
                                            class="text-sm px-3 py-1 bg-green-500 hover:bg-green-600 rounded disabled:opacity-50"
                                            disabled
                                            onclick="addToExperimentGroup()"
                                        >
                                            선택된 학생 추가
                                        </button>
                                    </div>
                                    <div class="space-y-2 max-h-96 overflow-y-auto" id="experiment-group"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    
                    <!-- 데이터 추적 탭 -->
                    <section id="tracking-tab" class="tab-content">
                        <div class="space-y-6">
                            <!-- 추적 설정 목록 -->
                            <div class="card mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold">추적 설정 목록</h3>
                        <button 
                            id="add-tracking-config"
                            class="px-4 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg flex items-center space-x-2"
                            onclick="showTrackingModal()"
                        >
                            <i class="fas fa-plus"></i>
                            <span>추적 설정 추가</span>
                        </button>
                    </div>
                    
                    <div class="space-y-3" id="tracking-config-list">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                            </div>

                            <!-- DB 연결 -->
                            <div class="card">
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
                        <div class="bg-blue-500/10 border border-blue-400/30 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-blue-300">선택된 테이블</h4>
                                <button onclick="clearSelectedTable()" class="text-gray-400 hover:text-white">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="mb-3">
                                <div class="text-sm text-gray-300 font-medium mb-1" id="selectedTableName">-</div>
                                <div class="text-xs text-gray-400" id="selectedTableDetails">-</div>
                            </div>
                            
                            <!-- 타입과 설명 입력 영역 -->
                            <div class="space-y-3 pt-3 border-t border-white/10">
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">타입</label>
                                    <select
                                        id="mainTableType"
                                        class="w-full px-2 py-2 bg-white border border-white/20 rounded text-sm text-black focus:outline-none focus:ring-2 focus:ring-purple-500"
                                        onchange="updateTableDescriptionFromMain()">
                                        <option value="사용자 정보">사용자 정보</option>
                                        <option value="강좌정보">강좌정보</option>
                                        <option value="활동정보">활동정보</option>
                                        <option value="목표 및 계획">목표 및 계획</option>
                                        <option value="출결정보">출결정보</option>
                                        <option value="시험대비">시험대비</option>
                                        <option value="컨텐츠 활용">컨텐츠 활용</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-400 mb-1">설명</label>
                                    <input
                                        type="text"
                                        id="mainTableDescription"
                                        class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                        placeholder="테이블 설명을 입력하세요..."
                                        onchange="updateTableDescriptionFromMain()"
                                    />
                                </div>
                            </div>
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
                    <div id="initialState">
                        <div class="text-gray-400 text-center py-4 mb-6">
                            <i class="fas fa-database text-3xl mb-2"></i>
                            <p>DB 선택하기 버튼을 클릭하여 테이블을 선택하세요</p>
                        </div>
                        
                        <!-- 설명이 있는 테이블 목록 -->
                        <div id="describedTablesList">
                            <h4 class="font-medium mb-3 text-purple-300">
                                <i class="fas fa-list-ul mr-2"></i>
                                설명이 등록된 테이블
                            </h4>
                            <div id="describedTablesContent" class="space-y-2 max-h-64 overflow-y-auto">
                                <!-- JavaScript로 동적 생성 -->
                            </div>
                        </div>
                    </div>
                            </div>
                        </div>
                    </section>

                    
                    <!-- 실험 기록 탭 -->
                    <section id="experiment-tab" class="tab-content">
                        <div class="space-y-6">
                            <!-- 실험 기록 -->
                            <div class="card mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold">실험 기록</h3>
                        <div class="flex space-x-2">
                            <button 
                                id="show-survey-modal"
                                class="px-3 py-1 bg-blue-500 hover:bg-blue-600 rounded text-sm"
                                onclick="showSurveyModal()"
                            >
                                학생설문
                            </button>
                            <button 
                                id="show-analysis-modal"
                                class="px-3 py-1 bg-green-500 hover:bg-green-600 rounded text-sm"
                                onclick="showAnalysisModal()"
                            >
                                분석보고서
                            </button>
                            <button 
                                id="generate-comprehensive-analysis"
                                class="px-3 py-1 bg-purple-500 hover:bg-purple-600 rounded text-sm"
                                onclick="generateComprehensiveAnalysis()"
                                title="모든 추적 설정 결과를 종합 분석"
                            >
                                종합분석
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-3 max-h-96 overflow-y-auto" id="experiment-results">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                            </div>

                            <!-- 가설 기록 -->
                            <div class="card">
                                <h3 class="text-xl font-semibold mb-4">가설 기록 (미래 실험 추천)</h3>
                    
                    <!-- 가설 입력 -->
                    <div class="mb-4">
                        <textarea
                            id="new-hypothesis"
                            class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                            rows="3"
                            placeholder="새로운 가설을 입력하세요..."
                        ></textarea>
                        <button
                            id="add-hypothesis"
                            class="mt-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 rounded text-sm disabled:opacity-50"
                            onclick="addHypothesis()"
                        >
                            가설 추가
                        </button>
                    </div>
                    
                                <!-- 가설 목록 -->
                                <div class="space-y-3 max-h-64 overflow-y-auto" id="hypotheses-list">
                                    <!-- JavaScript로 동적 생성 -->
                                </div>
                                
                                <!-- 실험 시작 버튼 -->
                                <div class="mt-6 pt-4 border-t border-white/20">
                                    <button
                                        id="start-experiment"
                                        class="w-full px-6 py-3 bg-green-500 hover:bg-green-600 rounded-lg text-white font-semibold transition-colors disabled:opacity-50"
                                        onclick="startExperimentFromHypotheses()"
                                    >
                                        <i class="fas fa-play mr-2"></i>
                                        실험 시작 - 가설을 바탕으로 실험 설계
                                    </button>
                                    <p class="text-xs text-gray-400 mt-2 text-center">
                                        가설 내용이 실험 설계 탭의 설명란으로 복사됩니다
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                    </div>
                </div>
            </div>
        </main>

        <?php echo renderFooter(); ?>
    </div>

    <?php echo renderModals(); ?>

    <!-- JavaScript 파일들 -->
    <script>
        // PHP 변수를 JavaScript로 전달
        window.USER_ID = <?php echo $USER->id; ?>;
        window.USER_ROLE = '<?php echo $role; ?>';
        window.STUDENT_ID = <?php echo $studentid ? $studentid : 'null'; ?>;
        
        console.log('사용자 정보:', {
            userId: window.USER_ID,
            role: window.USER_ROLE,
            studentId: window.STUDENT_ID
        });
    </script>
    
    <script src="src/components/ui/utils.js"></script>
    <script src="src/components/tracking/database.js"></script>
    <script src="src/components/experiment/experiment_api.js"></script>
    <script src="src/components/ui/main.js"></script>
</body>
</html>