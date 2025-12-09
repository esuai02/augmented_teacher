<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>출결관리 시스템</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Alpine.js for reactive functionality -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* 커스텀 애니메이션 */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .animate-spin { animation: spin 1s linear infinite; }
        
        /* 스크롤바 스타일 */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="attendanceApp()" x-init="init()">
        <!-- 캘린더 뷰 -->
        <div x-show="currentView === 'calendar' && selectedStudent" class="min-h-screen bg-gray-50 p-4 md:p-6">
            <!-- 캘린더 헤더 -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center justify-between space-y-4 md:space-y-0">
                    <div class="flex items-center space-x-4">
                        <button @click="currentView = 'list'; selectedStudent = null" 
                                class="flex items-center space-x-2 text-blue-600 hover:text-blue-800 transition-colors">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            <span class="font-medium">목록으로 돌아가기</span>
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button @click="navigateMonth(-1)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <span class="text-lg font-semibold min-w-[120px] text-center" x-text="formatMonth(currentMonth)"></span>
                        <button @click="navigateMonth(1)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h1 class="text-2xl font-bold text-gray-900" x-text="selectedStudent.name + ' 수업 캘린더'"></h1>
                    <p class="text-gray-600 mt-1" x-text="selectedStudent.grade + ' | ' + selectedStudent.subject"></p>
                    <div x-show="selectedStudent.currentSession" 
                         class="mt-3 px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm inline-flex items-center">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></div>
                        <span x-text="'현재 수업 진행 중 (' + selectedStudent.currentSession.currentDuration + '분)'"></span>
                    </div>
                </div>
            </div>

            <!-- 캘린더 그리드 -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                <!-- 요일 헤더 -->
                <div class="grid grid-cols-7 gap-2 mb-4">
                    <template x-for="(day, index) in ['일', '월', '화', '수', '목', '금', '토']">
                        <div :class="{'text-red-500': index === 0, 'text-blue-500': index === 6, 'text-gray-600': index > 0 && index < 6}"
                             class="p-4 text-center font-semibold bg-gray-50 rounded-lg" x-text="day"></div>
                    </template>
                </div>

                <!-- 날짜 그리드 -->
                <div class="grid grid-cols-7 gap-2">
                    <template x-for="(date, index) in getDaysInMonth(currentMonth)">
                        <div @click="handleCalendarDateClick(date)"
                             :class="{
                                'border-blue-500 bg-blue-50': date && isToday(date),
                                'ring-2 ring-blue-500 shadow-lg': selectedCalendarDate && date && isSameDate(date, selectedCalendarDate),
                                'hover:shadow-md': date && getCalendarData(selectedStudent, date)
                             }"
                             class="p-3 min-h-[100px] border-2 rounded-xl cursor-pointer hover:bg-gray-50 transition-all border-gray-200">
                            <template x-if="date">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <span :class="{
                                            'text-red-500': date.getDay() === 0,
                                            'text-blue-500': date.getDay() === 6,
                                            'text-gray-900': date.getDay() > 0 && date.getDay() < 6
                                        }" class="text-sm font-semibold" x-text="date.getDate()"></span>
                                        <button x-show="getCalendarData(selectedStudent, date)"
                                                @click.stop="handleCalendarEdit(date, selectedStudent)"
                                                class="text-gray-400 hover:text-gray-600 p-1 rounded transition-colors">
                                            <i data-lucide="edit-3" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                    
                                    <div x-show="getCalendarData(selectedStudent, date)" class="space-y-1">
                                        <template x-if="getCalendarData(selectedStudent, date)?.type === 'regular'">
                                            <div :class="getCalendarData(selectedStudent, date)?.attended ? 
                                                'bg-green-100 text-green-800 border-green-200' : 
                                                'bg-red-100 text-red-800 border-red-200'"
                                                 class="text-xs px-2 py-1 rounded-lg font-medium border">
                                                <div class="flex items-center space-x-1">
                                                    <div :class="getCalendarData(selectedStudent, date)?.attended ? 
                                                        'bg-green-500' : 'bg-red-500'" 
                                                         class="w-2 h-2 rounded-full"></div>
                                                    <span x-text="(getCalendarData(selectedStudent, date)?.actualHours || 
                                                                   getCalendarData(selectedStudent, date)?.hours) + 'h'"></span>
                                                    <span x-show="getCalendarData(selectedStudent, date)?.overtime > 0" 
                                                          class="text-purple-600"
                                                          x-text="'+' + getCalendarData(selectedStudent, date)?.overtime + 'h'"></span>
                                                </div>
                                                <div x-show="!getCalendarData(selectedStudent, date)?.attended && 
                                                            getCalendarData(selectedStudent, date)?.reason"
                                                     class="text-xs text-red-600 mt-1 font-normal"
                                                     x-text="getCalendarData(selectedStudent, date)?.reason"></div>
                                            </div>
                                        </template>
                                        
                                        <template x-if="getCalendarData(selectedStudent, date)?.type === 'makeup'">
                                            <div class="text-xs px-2 py-1 rounded-lg bg-gray-800 text-white font-medium">
                                                <span x-text="'보강 ' + getCalendarData(selectedStudent, date)?.hours + 'h'"></span>
                                                <div x-show="getCalendarData(selectedStudent, date)?.status === 'pending'" 
                                                     class="text-yellow-300 text-xs">예정</div>
                                            </div>
                                        </template>
                                        
                                        <template x-if="getCalendarData(selectedStudent, date)?.type === 'unscheduled' && 
                                                        getCalendarData(selectedStudent, date)?.isActive">
                                            <div class="text-xs px-2 py-1 rounded-lg bg-orange-100 text-orange-800 border border-orange-200 font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <div class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                                                    <span>접속 중</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- 보강 시간 표시 -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">남은 보강 시간</h3>
                <div class="flex items-center justify-center space-x-6">
                    <div class="text-center p-6 border-2 border-red-200 rounded-xl flex-1 bg-red-50">
                        <div class="text-3xl font-bold text-red-600" x-text="selectedStudent.requiredMakeupHours"></div>
                        <div class="text-sm text-gray-600 font-medium">보강 필요</div>
                    </div>
                    <div class="text-center p-6 border-2 border-blue-200 rounded-xl flex-1 bg-blue-50">
                        <div class="text-3xl font-bold text-blue-600" x-text="selectedStudent.scheduledMakeupHours"></div>
                        <div class="text-sm text-gray-600 font-medium">보강 예정</div>
                    </div>
                    <div class="text-center p-6 border-2 border-gray-200 rounded-xl flex-1 bg-gray-50">
                        <div class="text-3xl font-bold text-gray-600" x-text="selectedStudent.totalMissedHours"></div>
                        <div class="text-sm text-gray-600 font-medium">총 휴강</div>
                    </div>
                </div>
            </div>

            <!-- 빠른 입력 버튼 -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">빠른 입력</h3>
                <div class="flex flex-wrap gap-2 mb-6">
                    <template x-for="hours in [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6]">
                        <button @click="quickAddCalendarEntry(hours)"
                                class="px-4 py-2 text-sm border-2 border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-colors font-medium"
                                x-text="hours + '시간'"></button>
                    </template>
                </div>
                <div class="flex space-x-4">
                    <button @click="quickAddAbsence()"
                            class="flex-1 px-6 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors font-medium">
                        휴강 처리
                    </button>
                    <button @click="quickAddMakeup()"
                            class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        보강 처리
                    </button>
                </div>
            </div>
        </div>

        <!-- 메인 목록 뷰 -->
        <div x-show="currentView === 'list'" class="min-h-screen bg-gray-50 p-4 md:p-6">
            <!-- 헤더 -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center justify-between space-y-4 md:space-y-0">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">출결관리 시스템</h1>
                        <p class="text-gray-600 mt-2"><span x-text="currentUser"></span> 선생님의 담당 학생 관리</p>
                    </div>
                    <div class="flex items-center justify-between w-full md:w-auto">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="calendar" class="w-5 h-5 text-gray-500"></i>
                                <input type="date" x-model="selectedDate" 
                                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <button @click="refreshData()" :disabled="isLoading"
                                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50"
                                    title="새로고침">
                                <i data-lucide="refresh-cw" :class="{'animate-spin': isLoading}" class="w-5 h-5"></i>
                            </button>
                            
                            <div class="text-sm text-gray-500 hidden md:block" x-text="'마지막 업데이트: ' + formatTime(currentTime)"></div>
                        </div>
                        
                        <!-- 알림 버튼 -->
                        <div class="relative">
                            <button @click="showNotificationPopup = !showNotificationPopup"
                                    class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="알림">
                                <i data-lucide="bell" :class="{'text-red-500': getActionRequiredAlerts().length > 0}" class="w-5 h-5"></i>
                                <span x-show="getActionRequiredAlerts().length > 0"
                                      class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse font-medium"
                                      x-text="getActionRequiredAlerts().length"></span>
                            </button>
                            
                            <!-- 알림 팝업 -->
                            <div x-show="showNotificationPopup" @click.away="showNotificationPopup = false"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute top-full right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 max-h-96 overflow-y-auto"
                                 style="max-width: calc(100vw - 2rem)">
                                
                                <div class="p-4 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-900">실시간 알림</h3>
                                        <button @click="showNotificationPopup = false"
                                                class="text-gray-400 hover:text-gray-600 p-1 rounded transition-colors">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <template x-if="realtimeAlerts.length === 0">
                                    <div class="p-6 text-center">
                                        <i data-lucide="check-circle" class="w-8 h-8 text-green-400 mx-auto mb-2"></i>
                                        <p class="text-sm font-medium text-gray-600">모든 상황이 정상입니다</p>
                                        <p class="text-xs text-gray-500">현재 처리가 필요한 알림이 없습니다.</p>
                                    </div>
                                </template>
                                
                                <template x-if="realtimeAlerts.length > 0">
                                    <div class="divide-y divide-gray-200">
                                        <template x-for="alert in realtimeAlerts" :key="alert.id">
                                            <div class="p-4 hover:bg-gray-50 transition-colors">
                                                <div class="flex items-start space-x-3">
                                                    <div :class="getAlertColor(alert.priority)" class="p-2 rounded-full flex-shrink-0">
                                                        <i :data-lucide="getAlertIconName(alert.type)" class="w-5 h-5"></i>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-medium text-gray-900 text-sm" x-text="alert.studentName"></div>
                                                        <div class="text-xs text-gray-600 mb-2" x-text="alert.message"></div>
                                                        <div class="text-xs text-gray-500 mb-3" x-text="formatTimeAgo(alert.timestamp)"></div>
                                                        
                                                        <div x-show="alert.actionRequired" class="flex space-x-2">
                                                            <button x-show="alert.type === 'absence'"
                                                                    @click="handleAlertAction(alert, 'absence'); showNotificationPopup = false"
                                                                    class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                                                                결석 처리
                                                            </button>
                                                            <button x-show="alert.type === 'unscheduled_access'"
                                                                    @click="handleAlertAction(alert, 'unscheduled'); showNotificationPopup = false"
                                                                    class="px-3 py-1 text-xs bg-orange-600 text-white rounded hover:bg-orange-700 transition-colors">
                                                                접속 처리
                                                            </button>
                                                            <button x-show="alert.type === 'overtime'"
                                                                    @click="handleAlertAction(alert, 'overtime'); showNotificationPopup = false"
                                                                    class="px-3 py-1 text-xs bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                                                                연장 처리
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                
                                <div x-show="realtimeAlerts.length > 0" class="p-3 border-t border-gray-200 bg-gray-50">
                                    <button @click="realtimeAlerts = []; showNotificationPopup = false"
                                            class="w-full text-xs text-gray-600 hover:text-gray-800 transition-colors">
                                        모든 알림 지우기
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 학생 목록 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div @click="isStudentListExpanded = !isStudentListExpanded"
                     class="px-6 py-4 border-b border-gray-200 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors">
                    <div class="flex items-center space-x-3">
                        <h2 class="text-lg font-semibold text-gray-900">담당 학생 목록</h2>
                        <span class="text-sm text-gray-500">(<span x-text="filteredStudents().length"></span>명)</span>
                    </div>
                    <i :data-lucide="isStudentListExpanded ? 'chevron-up' : 'chevron-down'" class="w-5 h-5 text-gray-400"></i>
                </div>
                
                <div x-show="isStudentListExpanded" x-transition>
                    <!-- 검색 및 필터 -->
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                            <div class="flex-1 relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="text" x-model="searchTerm"
                                       placeholder="학생 이름, 과목, 학년으로 검색..."
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <select x-model="statusFilter"
                                    class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="all">모든 상태</option>
                                <option value="정상">정상</option>
                                <option value="보강 필요">보강 필요</option>
                                <option value="보강 중">보강 중</option>
                                <option value="긴급">긴급</option>
                                <option value="수업 중">수업 중</option>
                                <option value="예정외 접속">예정외 접속</option>
                            </select>
                        </div>
                    </div>

                    <!-- 학생 테이블 -->
                    <template x-if="filteredStudents().length === 0">
                        <div class="p-12 text-center">
                            <i data-lucide="users" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                            <p class="text-lg font-medium text-gray-600">검색 결과가 없습니다</p>
                            <p class="text-sm text-gray-500">다른 검색어나 필터를 시도해보세요.</p>
                        </div>
                    </template>

                    <template x-if="filteredStudents().length > 0">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">학생 정보</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">보강 예정</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">보강 필요</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">총 휴강</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">상태</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">최근 활동</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">관리</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="student in filteredStudents()" :key="student.id">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-12 w-12 relative">
                                                        <div :class="student.currentSession ? 
                                                            'bg-purple-100 border-purple-300' : 
                                                            'bg-blue-100 border-blue-300'"
                                                             class="h-12 w-12 rounded-full flex items-center justify-center border-2">
                                                            <i data-lucide="user" :class="student.currentSession ? 
                                                                'text-purple-600' : 'text-blue-600'" class="h-6 w-6"></i>
                                                            <div x-show="student.currentSession" 
                                                                 class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-pulse border-2 border-white"></div>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <button @click="handleStudentClick(student)"
                                                                class="text-lg font-semibold text-blue-600 hover:text-blue-800 underline transition-colors"
                                                                x-text="student.name"></button>
                                                        <div class="text-sm text-gray-600 mt-1" x-text="student.grade + ' | ' + student.subject"></div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            정규수업: <span x-text="formatRegularSchedule(student.regularSchedule)"></span>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            📞 <span x-text="student.phoneNumber"></span> | 👨‍👩‍👧‍👦 <span x-text="student.parentPhone"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div x-show="editingStudent !== student.id" class="text-sm text-gray-900">
                                                    <span class="font-semibold text-lg" x-text="student.scheduledMakeupHours"></span>
                                                    <span class="text-gray-500 ml-1">시간</span>
                                                </div>
                                                <input x-show="editingStudent === student.id"
                                                       type="number" step="0.5"
                                                       x-model="editData.scheduledMakeupHours"
                                                       class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div x-show="editingStudent !== student.id" class="text-sm text-gray-900">
                                                    <span :class="student.requiredMakeupHours > 0 ? 'text-red-600' : 'text-gray-900'"
                                                          class="font-semibold text-lg" x-text="student.requiredMakeupHours"></span>
                                                    <span class="text-gray-500 ml-1">시간</span>
                                                </div>
                                                <input x-show="editingStudent === student.id"
                                                       type="number" step="0.5"
                                                       x-model="editData.requiredMakeupHours"
                                                       class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div x-show="editingStudent !== student.id" class="text-sm text-gray-900">
                                                    <span class="font-semibold text-lg" x-text="student.totalMissedHours"></span>
                                                    <span class="text-gray-500 ml-1">시간</span>
                                                </div>
                                                <input x-show="editingStudent === student.id"
                                                       type="number" step="0.5"
                                                       x-model="editData.totalMissedHours"
                                                       class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="getStatusColor(student.status)"
                                                      class="inline-flex px-3 py-1 text-xs font-semibold rounded-full border"
                                                      x-text="student.status"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <div x-text="student.recentActivity"></div>
                                                <div x-show="student.currentSession" class="text-xs text-purple-600 mt-1 font-medium">
                                                    진행시간: <span x-text="student.currentSession.currentDuration"></span>분
                                                    <span x-show="student.currentSession.isOvertime">(연장중)</span>
                                                    <span x-show="student.currentSession.isUnscheduled">(예정외)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div x-show="editingStudent === student.id" class="flex space-x-2">
                                                    <button @click="handleSave(student.id)" :disabled="isLoading"
                                                            class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors disabled:opacity-50">
                                                        <i data-lucide="save" class="w-5 h-5"></i>
                                                    </button>
                                                    <button @click="handleCancel()"
                                                            class="text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                                        <i data-lucide="x" class="w-5 h-5"></i>
                                                    </button>
                                                </div>
                                                <div x-show="editingStudent !== student.id" class="flex flex-wrap gap-2">
                                                    <button @click="handleEdit(student)"
                                                            class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                                            title="수정">
                                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    </button>
                                                    <button @click="markAttendance(student.id, '보강완료')"
                                                            class="text-green-600 hover:text-green-900 text-xs px-3 py-1 border border-green-600 rounded-lg hover:bg-green-50 transition-colors font-medium">
                                                        보강완료
                                                    </button>
                                                    <button @click="markAttendance(student.id, '휴강추가')"
                                                            class="text-red-600 hover:text-red-900 text-xs px-3 py-1 border border-red-600 rounded-lg hover:bg-red-50 transition-colors font-medium">
                                                        휴강추가
                                                    </button>
                                                    <button @click="window.open('tel:' + student.phoneNumber)"
                                                            class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                                            title="학생 연락">
                                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                                    </button>
                                                    <button @click="window.open('tel:' + student.parentPhone)"
                                                            class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors"
                                                            title="학부모 연락">
                                                        <i data-lucide="message-square" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>

            <!-- 하단 안내 -->
            <div class="mt-6 bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-6">
                <div class="flex">
                    <i data-lucide="book-open" class="w-6 h-6 text-blue-500 mt-0.5"></i>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-blue-800 mb-3">시스템 자동화 안내</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700">
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                    <span><strong>정규수업 15분 경과</strong> → 자동 결석 처리 → 교사 알림</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                    <span><strong>예정외 접속 감지</strong> → 실시간 알림 → 교사 승인</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span><strong>수업시간 연장</strong> → 자동 감지 → 교사 확인</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span><strong>보강수업 완료</strong> → 자동 시간 차감 → 학부모 알림</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span><strong>매일 자정</strong> → 일일 정산 → 주간/월간 리포트</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                                    <span><strong>실시간 모니터링</strong> → 접속 상태 추적 → 최적화 제안</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 캘린더 수정 모달 -->
        <div x-show="editingCalendarEntry" x-transition
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-bold mb-6">수업 정보 수정</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">날짜</label>
                        <input type="text" :value="editingCalendarEntry?.date" disabled 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">수업 유형</label>
                        <select x-model="editingCalendarEntry.type"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="regular">정규 수업</option>
                            <option value="makeup">보강 수업</option>
                            <option value="unscheduled">예정외 접속</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">예정 시간</label>
                            <input type="number" step="0.5" x-model="editingCalendarEntry.hours"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">실제 시간</label>
                            <input type="number" step="0.5" x-model="editingCalendarEntry.actualHours"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div x-show="!editingCalendarEntry?.attended">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">결석 사유</label>
                        <input type="text" x-model="editingCalendarEntry.reason"
                               placeholder="예: 질병, 개인사정, 무단결석"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" x-model="editingCalendarEntry.attended"
                                   class="mr-2 w-4 h-4 text-blue-600">
                            <span class="font-medium">출석</span>
                        </label>
                        <label x-show="editingCalendarEntry?.type === 'makeup'" class="flex items-center cursor-pointer">
                            <input type="checkbox" x-model="editingCalendarEntry.scheduled"
                                   class="mr-2 w-4 h-4 text-blue-600">
                            <span class="font-medium">예정됨</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-8">
                    <button @click="deleteCalendarEntry()" :disabled="isLoading"
                            class="px-4 py-3 text-red-600 border-2 border-red-600 rounded-lg hover:bg-red-50 transition-colors font-medium disabled:opacity-50">
                        삭제
                    </button>
                    <button @click="editingCalendarEntry = null"
                            class="flex-1 px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                        취소
                    </button>
                    <button @click="saveCalendarEntry()" :disabled="isLoading"
                            class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50">
                        <span x-text="isLoading ? '저장 중...' : '저장'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 액션 모달들 -->
        <!-- 결석 처리 모달 -->
        <div x-show="activeModal?.type === 'absence'" x-transition
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="p-3 bg-red-100 rounded-full">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">결석 처리</h3>
                        <p class="text-sm text-gray-600" x-text="activeModal?.alert?.studentName"></p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">수업 정보</div>
                        <div class="font-semibold text-lg" x-text="activeModal?.alert?.classInfo?.subject"></div>
                        <div class="text-sm text-gray-600" x-text="activeModal?.alert?.classInfo?.scheduledTime"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">결석 사유</label>
                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">사유 선택</option>
                            <option value="sick">질병 (진단서 필요)</option>
                            <option value="sick_minor">몸살/감기 (진단서 불필요)</option>
                            <option value="personal">개인사정 (가족행사, 여행)</option>
                            <option value="traffic">교통사정 (날씨, 교통사고)</option>
                            <option value="unauthorized">무단결석</option>
                            <option value="other">기타</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">보강 처리</label>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="makeup" value="required" checked class="mr-3 text-blue-600">
                                <div>
                                    <div class="font-medium">보강 필요</div>
                                    <div class="text-sm text-gray-600">2시간 추가</div>
                                </div>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="makeup" value="excused" class="mr-3 text-blue-600">
                                <div>
                                    <div class="font-medium">사유있는 결석</div>
                                    <div class="text-sm text-gray-600">보강 불필요</div>
                                </div>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="makeup" value="partial" class="mr-3 text-blue-600">
                                <div>
                                    <div class="font-medium">부분 보강</div>
                                    <div class="text-sm text-gray-600">1시간만 추가</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button @click="callStudent(activeModal.alert)"
                                class="flex items-center justify-center space-x-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                            <span class="font-medium">학생</span>
                        </button>
                        <button @click="callParent(activeModal.alert)"
                                class="flex items-center justify-center space-x-2 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                            <span class="font-medium">학부모</span>
                        </button>
                    </div>
                </div>

                <div class="flex space-x-3 mt-8">
                    <button @click="activeModal = null"
                            class="flex-1 px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                        취소
                    </button>
                    <button @click="handleAbsenceAction('mark_excused', { hours: 2 })"
                            :disabled="isLoading"
                            class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50">
                        <span x-text="isLoading ? '처리 중...' : '결석 처리 완료'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 예정외 접속 처리 모달 -->
        <div x-show="activeModal?.type === 'unscheduled'" x-transition
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="p-3 bg-orange-100 rounded-full">
                        <i data-lucide="zap" class="w-6 h-6 text-orange-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">예정외 접속</h3>
                        <p class="text-sm text-gray-600" x-text="activeModal?.alert?.studentName"></p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">접속 정보</div>
                        <div class="space-y-1">
                            <div>접속 시간: <span class="font-semibold" x-text="activeModal?.alert?.accessInfo?.currentTime"></span></div>
                            <div>보강 필요: <span class="font-semibold text-red-600" x-text="activeModal?.alert?.accessInfo?.hasRequiredMakeup + '시간'"></span></div>
                            <div>현재 경과: <span class="font-semibold" x-text="formatElapsedTime(activeModal?.alert?.timestamp)"></span></div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button @click="handleUnscheduledAction('approve_makeup', { hours: 2 })"
                                :disabled="isLoading"
                                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium disabled:opacity-50">
                            보강수업으로 승인 (보강필요시간에서 차감)
                        </button>
                        
                        <button @click="handleUnscheduledAction('mark_selfstudy')"
                                :disabled="isLoading"
                                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50">
                            자습으로 처리 (시간 차감 안함)
                        </button>
                        
                        <button @click="handleUnscheduledAction('contact_student')"
                                :disabled="isLoading"
                                class="w-full px-4 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-medium disabled:opacity-50">
                            학생에게 연락 후 결정
                        </button>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button @click="activeModal = null"
                            class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        취소
                    </button>
                </div>
            </div>
        </div>

        <!-- 수업 연장 처리 모달 -->
        <div x-show="activeModal?.type === 'overtime'" x-transition
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i data-lucide="timer" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">수업 연장</h3>
                        <p class="text-sm text-gray-600" x-text="activeModal?.alert?.studentName"></p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">연장 정보</div>
                        <div class="space-y-1">
                            <div>예정 종료: <span class="font-semibold" x-text="activeModal?.alert?.overtimeInfo?.plannedEnd"></span></div>
                            <div>현재 진행: <span class="font-semibold" x-text="activeModal?.alert?.overtimeInfo?.currentDuration + '분'"></span></div>
                            <div>연장 시간: <span class="font-semibold text-purple-600">15분</span></div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button @click="handleOvertimeAction('approve_extension')"
                                :disabled="isLoading"
                                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium disabled:opacity-50">
                            정규수업 연장 승인 (무료 서비스)
                        </button>
                        
                        <button @click="handleOvertimeAction('deduct_makeup')"
                                :disabled="isLoading"
                                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50">
                            보강시간에서 차감 (0.25시간)
                        </button>
                        
                        <button @click="handleOvertimeAction('correct_time')"
                                :disabled="isLoading"
                                class="w-full px-4 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-medium disabled:opacity-50">
                            정규 종료시간으로 수정
                        </button>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button @click="activeModal = null"
                            class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        취소
                    </button>
                </div>
            </div>
        </div>

        <!-- 로딩 오버레이 -->
        <div x-show="isLoading" x-transition
             class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-40">
            <div class="bg-white rounded-xl p-6 shadow-2xl flex items-center space-x-4">
                <i data-lucide="refresh-cw" class="w-6 h-6 text-blue-600 animate-spin"></i>
                <span class="text-lg font-medium text-gray-900">처리 중...</span>
            </div>
        </div>
    </div>

    <script>
        function attendanceApp() {
            return {
                // 상태 변수들
                currentUser: '김선생님',
                selectedDate: new Date().toISOString().split('T')[0],
                editingStudent: null,
                editData: {},
                currentView: 'list',
                selectedStudent: null,
                currentMonth: new Date(),
                selectedCalendarDate: null,
                editingCalendarEntry: null,
                activeModal: null,
                currentTime: new Date(),
                isAlertsExpanded: false,
                isStudentListExpanded: false,
                searchTerm: '',
                statusFilter: 'all',
                isLoading: false,
                showNotificationPopup: false,

                // 실시간 알림 데이터
                realtimeAlerts: [
                    {
                        id: 1,
                        type: 'absence',
                        priority: 'urgent',
                        studentName: '김철수',
                        message: '정규수업 결석 (15분 경과)',
                        timestamp: new Date(Date.now() - 15 * 60 * 1000),
                        actionRequired: true,
                        classInfo: { subject: '수학', scheduledTime: '14:00-16:00' }
                    },
                    {
                        id: 2,
                        type: 'unscheduled_access',
                        priority: 'normal',
                        studentName: '박민수',
                        message: '예정외 접속 감지',
                        timestamp: new Date(Date.now() - 5 * 60 * 1000),
                        actionRequired: true,
                        accessInfo: { currentTime: '15:30', hasRequiredMakeup: 3.5 }
                    },
                    {
                        id: 3,
                        type: 'overtime',
                        priority: 'normal',
                        studentName: '이영희',
                        message: '정규수업 15분 연장 중',
                        timestamp: new Date(Date.now() - 2 * 60 * 1000),
                        actionRequired: true,
                        overtimeInfo: { plannedEnd: '16:00', currentDuration: 135 }
                    }
                ],

                // 학생 데이터
                students: [
                    {
                        id: 1,
                        name: "김철수",
                        grade: "고2",
                        subject: "수학",
                        phoneNumber: "010-1234-5678",
                        parentPhone: "010-9876-5432",
                        scheduledMakeupHours: 4.5,
                        requiredMakeupHours: 2.0,
                        totalMissedHours: 6.5,
                        regularSchedule: [
                            { day: "월", time: "14:00-16:00", duration: 2 },
                            { day: "수", time: "14:00-16:00", duration: 2 },
                            { day: "금", time: "14:00-16:00", duration: 2 }
                        ],
                        recentActivity: "2시간 전 온라인 접속",
                        status: "보강 필요",
                        currentSession: null,
                        calendarData: {
                            "2025-08-01": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-04": { type: "regular", hours: 2, attended: false, reason: "질병" },
                            "2025-08-06": { type: "regular", hours: 2, attended: true, actualHours: 2.5, overtime: 0.5 },
                            "2025-08-08": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-11": { type: "regular", hours: 2, attended: false, reason: "개인사정" },
                            "2025-08-13": { type: "regular", hours: 2, attended: true, actualHours: 1.5, early: true },
                            "2025-08-15": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-18": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-19": { type: "makeup", hours: 1.5, scheduled: true, status: "pending" },
                            "2025-08-20": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-22": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-25": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-27": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-29": { type: "regular", hours: 2, attended: true, actualHours: 2 }
                        }
                    },
                    {
                        id: 2,
                        name: "이영희",
                        grade: "고3",
                        subject: "영어",
                        phoneNumber: "010-2345-6789",
                        parentPhone: "010-8765-4321",
                        scheduledMakeupHours: 1.5,
                        requiredMakeupHours: 0,
                        totalMissedHours: 1.5,
                        regularSchedule: [
                            { day: "화", time: "16:00-17:30", duration: 1.5 },
                            { day: "목", time: "16:00-17:30", duration: 1.5 }
                        ],
                        recentActivity: "현재 수업 진행 중 (15분 연장)",
                        status: "수업 중",
                        currentSession: {
                            startTime: "16:00",
                            plannedEnd: "17:30",
                            currentDuration: 105,
                            isOvertime: true
                        },
                        calendarData: {
                            "2025-08-05": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 },
                            "2025-08-07": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 },
                            "2025-08-12": { type: "regular", hours: 1.5, attended: false, reason: "교통사정" },
                            "2025-08-14": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 },
                            "2025-08-19": { type: "regular", hours: 1.5, attended: true, actualHours: 1.75, isActive: true },
                            "2025-08-21": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 },
                            "2025-08-26": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 },
                            "2025-08-28": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 }
                        }
                    },
                    {
                        id: 3,
                        name: "박민수",
                        grade: "고1",
                        subject: "수학",
                        phoneNumber: "010-3456-7890",
                        parentPhone: "010-7654-3210",
                        scheduledMakeupHours: 0,
                        requiredMakeupHours: 3.5,
                        totalMissedHours: 3.5,
                        regularSchedule: [
                            { day: "월", time: "18:00-20:00", duration: 2 },
                            { day: "수", time: "18:00-19:30", duration: 1.5 }
                        ],
                        recentActivity: "현재 예정외 접속 중",
                        status: "예정외 접속",
                        currentSession: {
                            startTime: "15:30",
                            isUnscheduled: true,
                            currentDuration: 45
                        },
                        calendarData: {
                            "2025-08-04": { type: "regular", hours: 2, attended: false, reason: "무단결석" },
                            "2025-08-06": { type: "regular", hours: 1.5, attended: false, reason: "무단결석" },
                            "2025-08-11": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-13": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 },
                            "2025-08-18": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-19": { type: "unscheduled", hours: 0, isActive: true, startTime: "15:30" },
                            "2025-08-20": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 },
                            "2025-08-25": { type: "regular", hours: 2, attended: true, actualHours: 2 },
                            "2025-08-27": { type: "regular", hours: 1.5, attended: true, actualHours: 1.5 }
                        }
                    }
                ],

                // 초기화
                init() {
                    // Lucide 아이콘 초기화
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });

                    // 시간 업데이트 타이머
                    setInterval(() => {
                        this.currentTime = new Date();
                    }, 60000);

                    // 아이콘 재초기화 감지
                    this.$watch('currentView', () => {
                        this.$nextTick(() => {
                            lucide.createIcons();
                        });
                    });

                    this.$watch('showNotificationPopup', () => {
                        this.$nextTick(() => {
                            lucide.createIcons();
                        });
                    });

                    this.$watch('activeModal', () => {
                        this.$nextTick(() => {
                            lucide.createIcons();
                        });
                    });
                },

                // 필터링된 학생 목록
                filteredStudents() {
                    return this.students.filter(student => {
                        const matchesSearch = student.name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                                             student.subject.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                                             student.grade.toLowerCase().includes(this.searchTerm.toLowerCase());
                        
                        const matchesStatus = this.statusFilter === 'all' || student.status === this.statusFilter;
                        
                        return matchesSearch && matchesStatus;
                    });
                },

                // 헬퍼 함수들
                getStatusColor(status) {
                    switch(status) {
                        case "정상": return "text-green-600 bg-green-50 border-green-200";
                        case "보강 필요": return "text-yellow-600 bg-yellow-50 border-yellow-200";
                        case "보강 중": return "text-blue-600 bg-blue-50 border-blue-200";
                        case "긴급": return "text-red-600 bg-red-50 border-red-200";
                        case "수업 중": return "text-purple-600 bg-purple-50 border-purple-200";
                        case "예정외 접속": return "text-orange-600 bg-orange-50 border-orange-200";
                        default: return "text-gray-600 bg-gray-50 border-gray-200";
                    }
                },

                getAlertColor(priority) {
                    switch(priority) {
                        case 'urgent': return 'bg-red-100 border-red-300 text-red-800';
                        case 'normal': return 'bg-yellow-100 border-yellow-300 text-yellow-800';
                        case 'info': return 'bg-blue-100 border-blue-300 text-blue-800';
                        default: return 'bg-gray-100 border-gray-300 text-gray-800';
                    }
                },

                getAlertIconName(type) {
                    switch(type) {
                        case 'absence': return 'alert-triangle';
                        case 'unscheduled_access': return 'zap';
                        case 'overtime': return 'timer';
                        default: return 'bell';
                    }
                },

                formatTimeAgo(timestamp) {
                    const diff = Math.floor((this.currentTime - timestamp) / (1000 * 60));
                    if (diff < 1) return '방금 전';
                    if (diff < 60) return `${diff}분 전`;
                    const hours = Math.floor(diff / 60);
                    return `${hours}시간 전`;
                },

                formatElapsedTime(timestamp) {
                    const diff = Math.floor((this.currentTime - timestamp) / (1000 * 60));
                    return `${diff}분`;
                },

                formatTime(date) {
                    return date.toLocaleTimeString('ko-KR');
                },

                formatMonth(date) {
                    return `${date.getFullYear()}년 ${date.getMonth() + 1}월`;
                },

                formatRegularSchedule(schedule) {
                    return schedule.map(s => `${s.day} ${s.time}`).join(', ');
                },

                getActionRequiredAlerts() {
                    return this.realtimeAlerts.filter(a => a.actionRequired);
                },

                getDaysInMonth(date) {
                    const year = date.getFullYear();
                    const month = date.getMonth();
                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    const daysInMonth = lastDay.getDate();
                    const startingDayOfWeek = firstDay.getDay();

                    const days = [];
                    
                    for (let i = 0; i < startingDayOfWeek; i++) {
                        days.push(null);
                    }
                    
                    for (let i = 1; i <= daysInMonth; i++) {
                        days.push(new Date(year, month, i));
                    }
                    
                    return days;
                },

                isToday(date) {
                    const today = new Date();
                    return date.getDate() === today.getDate() &&
                           date.getMonth() === today.getMonth() &&
                           date.getFullYear() === today.getFullYear();
                },

                isSameDate(date1, date2) {
                    return date1.getDate() === date2.getDate() &&
                           date1.getMonth() === date2.getMonth() &&
                           date1.getFullYear() === date2.getFullYear();
                },

                formatDateKey(date) {
                    if (!date) return '';
                    return date.toISOString().split('T')[0];
                },

                getCalendarData(student, date) {
                    if (!student || !date) return null;
                    const dateKey = this.formatDateKey(date);
                    return student.calendarData?.[dateKey] || null;
                },

                // 액션 함수들
                refreshData() {
                    this.isLoading = true;
                    setTimeout(() => {
                        this.currentTime = new Date();
                        this.isLoading = false;
                        lucide.createIcons();
                    }, 1000);
                },

                handleEdit(student) {
                    this.editingStudent = student.id;
                    this.editData = {
                        scheduledMakeupHours: student.scheduledMakeupHours,
                        requiredMakeupHours: student.requiredMakeupHours,
                        totalMissedHours: student.totalMissedHours
                    };
                },

                handleSave(studentId) {
                    this.isLoading = true;
                    setTimeout(() => {
                        const studentIndex = this.students.findIndex(s => s.id === studentId);
                        if (studentIndex !== -1) {
                            this.students[studentIndex] = {
                                ...this.students[studentIndex],
                                scheduledMakeupHours: parseFloat(this.editData.scheduledMakeupHours) || 0,
                                requiredMakeupHours: parseFloat(this.editData.requiredMakeupHours) || 0,
                                totalMissedHours: parseFloat(this.editData.totalMissedHours) || 0
                            };
                        }
                        this.editingStudent = null;
                        this.editData = {};
                        this.isLoading = false;
                    }, 500);
                },

                handleCancel() {
                    this.editingStudent = null;
                    this.editData = {};
                },

                markAttendance(studentId, type) {
                    const hours = prompt(`${type} 시간을 입력하세요 (시간 단위, 예: 1.5):`);
                    if (hours && !isNaN(parseFloat(hours))) {
                        this.isLoading = true;
                        setTimeout(() => {
                            const studentIndex = this.students.findIndex(s => s.id === studentId);
                            if (studentIndex !== -1) {
                                if (type === "보강완료") {
                                    this.students[studentIndex].scheduledMakeupHours = 
                                        Math.max(0, this.students[studentIndex].scheduledMakeupHours - parseFloat(hours));
                                    this.students[studentIndex].recentActivity = '방금 전 보강수업 완료';
                                } else if (type === "휴강추가") {
                                    this.students[studentIndex].requiredMakeupHours += parseFloat(hours);
                                    this.students[studentIndex].totalMissedHours += parseFloat(hours);
                                }
                            }
                            this.isLoading = false;
                        }, 500);
                    }
                },

                handleStudentClick(student) {
                    this.selectedStudent = student;
                    this.currentView = 'calendar';
                    this.currentMonth = new Date();
                },

                handleCalendarDateClick(date) {
                    if (!date) return;
                    this.selectedCalendarDate = date;
                    this.editingCalendarEntry = null;
                },

                handleCalendarEdit(date, student) {
                    const dateKey = this.formatDateKey(date);
                    const existingData = student.calendarData?.[dateKey];
                    this.editingCalendarEntry = {
                        date: dateKey,
                        type: existingData?.type || 'regular',
                        hours: existingData?.hours || 0,
                        attended: existingData?.attended || false,
                        scheduled: existingData?.scheduled || false,
                        actualHours: existingData?.actualHours || existingData?.hours || 0,
                        reason: existingData?.reason || '',
                        overtime: existingData?.overtime || 0
                    };
                },

                saveCalendarEntry() {
                    if (!this.editingCalendarEntry || !this.selectedStudent) return;
                    
                    this.isLoading = true;
                    setTimeout(() => {
                        const studentIndex = this.students.findIndex(s => s.id === this.selectedStudent.id);
                        if (studentIndex !== -1) {
                            this.students[studentIndex].calendarData = {
                                ...this.students[studentIndex].calendarData,
                                [this.editingCalendarEntry.date]: {
                                    type: this.editingCalendarEntry.type,
                                    hours: parseFloat(this.editingCalendarEntry.hours),
                                    attended: this.editingCalendarEntry.attended,
                                    scheduled: this.editingCalendarEntry.scheduled,
                                    actualHours: parseFloat(this.editingCalendarEntry.actualHours),
                                    reason: this.editingCalendarEntry.reason,
                                    overtime: parseFloat(this.editingCalendarEntry.overtime) || 0
                                }
                            };
                            this.selectedStudent = this.students[studentIndex];
                        }
                        
                        this.editingCalendarEntry = null;
                        this.selectedCalendarDate = null;
                        this.isLoading = false;
                    }, 500);
                },

                deleteCalendarEntry() {
                    if (!this.editingCalendarEntry || !this.selectedStudent) return;
                    
                    this.isLoading = true;
                    setTimeout(() => {
                        const studentIndex = this.students.findIndex(s => s.id === this.selectedStudent.id);
                        if (studentIndex !== -1) {
                            const newCalendarData = { ...this.students[studentIndex].calendarData };
                            delete newCalendarData[this.editingCalendarEntry.date];
                            this.students[studentIndex].calendarData = newCalendarData;
                            this.selectedStudent = this.students[studentIndex];
                        }
                        
                        this.editingCalendarEntry = null;
                        this.selectedCalendarDate = null;
                        this.isLoading = false;
                    }, 500);
                },

                navigateMonth(direction) {
                    const newMonth = new Date(this.currentMonth);
                    newMonth.setMonth(newMonth.getMonth() + direction);
                    this.currentMonth = newMonth;
                },

                quickAddCalendarEntry(hours) {
                    if (this.selectedCalendarDate) {
                        this.editingCalendarEntry = {
                            date: this.formatDateKey(this.selectedCalendarDate),
                            type: 'makeup',
                            hours: hours,
                            attended: true,
                            scheduled: true,
                            actualHours: hours,
                            reason: '',
                            overtime: 0
                        };
                    }
                },

                quickAddAbsence() {
                    if (this.selectedCalendarDate) {
                        this.editingCalendarEntry = {
                            date: this.formatDateKey(this.selectedCalendarDate),
                            type: 'regular',
                            hours: 2,
                            attended: false,
                            scheduled: false,
                            actualHours: 0,
                            reason: '무단결석',
                            overtime: 0
                        };
                    }
                },

                quickAddMakeup() {
                    if (this.selectedCalendarDate) {
                        this.editingCalendarEntry = {
                            date: this.formatDateKey(this.selectedCalendarDate),
                            type: 'makeup',
                            hours: 2,
                            attended: true,
                            scheduled: true,
                            actualHours: 2,
                            reason: '',
                            overtime: 0
                        };
                    }
                },

                handleAlertAction(alert, action) {
                    this.activeModal = { type: action, alert: alert };
                },

                handleAbsenceAction(action, data = {}) {
                    const alert = this.activeModal.alert;
                    this.isLoading = true;
                    
                    setTimeout(() => {
                        switch(action) {
                            case 'contact_student':
                                console.log(`Calling student: ${alert.studentName}`);
                                break;
                            case 'contact_parent':
                                console.log(`Calling parent of: ${alert.studentName}`);
                                break;
                            case 'mark_excused':
                                const studentIndex = this.students.findIndex(s => s.name === alert.studentName);
                                if (studentIndex !== -1) {
                                    this.students[studentIndex].requiredMakeupHours = 
                                        this.students[studentIndex].requiredMakeupHours - (data.hours || 2);
                                }
                                break;
                            case 'schedule_makeup':
                                console.log(`Scheduling makeup for: ${alert.studentName}`, data);
                                break;
                        }
                        
                        this.realtimeAlerts = this.realtimeAlerts.filter(a => a.id !== alert.id);
                        this.activeModal = null;
                        this.isLoading = false;
                    }, 1000);
                },

                handleUnscheduledAction(action, data = {}) {
                    const alert = this.activeModal.alert;
                    this.isLoading = true;
                    
                    setTimeout(() => {
                        switch(action) {
                            case 'approve_makeup':
                                const studentIndex = this.students.findIndex(s => s.name === alert.studentName);
                                if (studentIndex !== -1) {
                                    this.students[studentIndex].requiredMakeupHours = 
                                        Math.max(0, this.students[studentIndex].requiredMakeupHours - (data.hours || 2));
                                    this.students[studentIndex].status = '보강 중';
                                }
                                break;
                            case 'mark_selfstudy':
                                console.log(`Marking as self-study: ${alert.studentName}`);
                                break;
                            case 'contact_student':
                                console.log(`Contacting student: ${alert.studentName}`);
                                this.isLoading = false;
                                return;
                        }
                        
                        this.realtimeAlerts = this.realtimeAlerts.filter(a => a.id !== alert.id);
                        this.activeModal = null;
                        this.isLoading = false;
                    }, 1000);
                },

                handleOvertimeAction(action, data = {}) {
                    const alert = this.activeModal.alert;
                    this.isLoading = true;
                    
                    setTimeout(() => {
                        switch(action) {
                            case 'approve_extension':
                                console.log(`Approved extension for: ${alert.studentName}`);
                                break;
                            case 'deduct_makeup':
                                const studentIndex = this.students.findIndex(s => s.name === alert.studentName);
                                if (studentIndex !== -1) {
                                    this.students[studentIndex].requiredMakeupHours = 
                                        Math.max(0, this.students[studentIndex].requiredMakeupHours - 0.25);
                                }
                                break;
                            case 'correct_time':
                                console.log(`Correcting time for: ${alert.studentName}`, data);
                                break;
                        }
                        
                        this.realtimeAlerts = this.realtimeAlerts.filter(a => a.id !== alert.id);
                        this.activeModal = null;
                        this.isLoading = false;
                    }, 1000);
                },

                callStudent(alert) {
                    const student = this.students.find(s => s.name === alert.studentName);
                    if (student) {
                        window.open(`tel:${student.phoneNumber}`);
                    }
                },

                callParent(alert) {
                    const student = this.students.find(s => s.name === alert.studentName);
                    if (student) {
                        window.open(`tel:${student.parentPhone}`);
                    }
                }
            };
        }
    </script>
</body>
</html>