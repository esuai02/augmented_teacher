// KTM 통합 진단 시스템 JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Lucide icons 초기화 (약간의 지연 후)
    setTimeout(() => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }, 100);
    
    // 상태 관리
    let selectedDiagnosis = 'overview';
    let selectedStudent = null;
    let activeMetric = 'all';
    
    // 필터 버튼 이벤트 리스너
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            activeMetric = this.dataset.filter;
            updateFilterButtons();
            filterModules();
        });
    });
    
    // 필터 버튼 상태 업데이트
    function updateFilterButtons() {
        filterButtons.forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-100', 'text-gray-600', 'hover:bg-gray-200');
        });
        
        const activeBtn = document.querySelector(`[data-filter="${activeMetric}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-100', 'text-gray-600', 'hover:bg-gray-200');
            activeBtn.classList.add('bg-blue-600', 'text-white');
        }
    }
    
    // 모듈 필터링
    function filterModules() {
        const moduleCards = document.querySelectorAll('.module-card');
        moduleCards.forEach(card => {
            const status = card.dataset.status;
            let shouldShow = false;
            
            if (activeMetric === 'all') {
                shouldShow = true;
            } else if (activeMetric === 'warning') {
                shouldShow = status === '주의' || status === '경고';
            } else if (activeMetric === 'critical') {
                shouldShow = status === '위험';
            }
            
            card.style.display = shouldShow ? 'block' : 'none';
        });
    }
    
    // 모듈 카드 클릭 이벤트
    const moduleCards = document.querySelectorAll('.module-card');
    moduleCards.forEach(card => {
        card.addEventListener('click', function() {
            selectedDiagnosis = this.dataset.moduleId;
            console.log('Selected module:', selectedDiagnosis);
            // 여기에 상세 보기 로직 추가 가능
        });
    });
    
    // 학생 카드 클릭 이벤트
    const studentCards = document.querySelectorAll('.student-card');
    studentCards.forEach(card => {
        card.addEventListener('click', function() {
            const studentData = JSON.parse(this.dataset.student);
            showStudentModal(studentData);
        });
    });
    
    // 학생 상세 모달 표시
    function showStudentModal(student) {
        selectedStudent = student;
        const modal = document.getElementById('studentModal');
        const modalContent = document.getElementById('studentModalContent');
        
        const severityClass = getSeverityClass(student.severity);
        const severityText = student.severity === 'critical' ? '긴급 관리' : '주의 관찰';
        
        modalContent.innerHTML = `
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-bold flex items-center">
                        ${student.name} KTM 상세 분석
                        <span class="ml-3 text-sm px-3 py-1 rounded-full ${severityClass}">
                            ${severityText}
                        </span>
                    </h2>
                    <p class="text-gray-600">${student.grade} | ${student.issue}</p>
                </div>
                <button onclick="closeStudentModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    ×
                </button>
            </div>
            
            <div class="space-y-4">
                <!-- KTM 지수 상세 -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4">
                        <h4 class="font-medium mb-2 text-red-900">종합 KTM 지수</h4>
                        <div class="flex items-baseline">
                            <span class="text-4xl font-bold text-red-600">${student.ktmScore}</span>
                            <span class="ml-2 text-sm text-red-600">${student.change}%</span>
                        </div>
                        <p class="text-xs text-red-700 mt-1">위험 수준</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium mb-2">최근 변화</h4>
                        <div class="flex items-center">
                            <i data-lucide="trending-up" class="text-red-500 rotate-180 mr-2 w-6 h-6"></i>
                            <span class="text-2xl font-bold text-red-600">${student.change}%</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">2주간 변화율</p>
                    </div>
                    
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-medium mb-2">예상 회복 기간</h4>
                        <div class="text-2xl font-bold text-blue-600">3-4주</div>
                        <p class="text-xs text-blue-700 mt-1">집중 관리 시</p>
                    </div>
                </div>
                
                <!-- KTM 세부 지표 -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium mb-3">KTM 세부 지표 분석</h4>
                    <div class="space-y-2">
                        ${renderDetailMetrics(student)}
                    </div>
                </div>
                
                <!-- KTM 권장 조치 -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-medium mb-2 flex items-center">
                        <i data-lucide="shield" class="mr-2 text-blue-600 w-4 h-4"></i>
                        KTM 권장 조치
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start">
                            <span class="text-blue-600 mr-2">1.</span>
                            <span>즉시 1:1 상담 진행 (학습 동기 파악)</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-600 mr-2">2.</span>
                            <span>맞춤형 보충 커리큘럼 적용 (주 2회)</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-600 mr-2">3.</span>
                            <span>부모님 협력 프로그램 참여 권유</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-600 mr-2">4.</span>
                            <span>주간 KTM 지수 모니터링 강화</span>
                        </li>
                    </ul>
                </div>
                
                <!-- 실행 버튼 -->
                <div class="flex gap-3 pt-4">
                    <button class="flex-1 bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        KTM 회복 프로그램 시작
                    </button>
                    <button class="flex-1 bg-green-600 text-white py-2.5 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium">
                        상담 일정 예약
                    </button>
                </div>
            </div>
        `;
        
        modal.classList.remove('hidden');
        // Lucide 아이콘 재초기화
        setTimeout(() => lucide.createIcons(), 100);
    }
    
    // 학생 세부 지표 렌더링
    function renderDetailMetrics(student) {
        const metrics = [
            { label: '학습 진행률', value: 45, status: 'critical' },
            { label: '메타인지 수준', value: 52, status: 'warning' },
            { label: '집중도 지수', value: 38, status: 'critical' },
            { label: '목표 달성률', value: 35, status: 'critical' },
            { label: '부모 참여도', value: 65, status: 'normal' }
        ];
        
        return metrics.map(metric => `
            <div class="flex items-center">
                <span class="w-32 text-sm">${metric.label}</span>
                <div class="flex-1 mx-3">
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="h-4 rounded-full ${getMetricColorClass(metric.status)}" style="width: ${metric.value}%"></div>
                    </div>
                </div>
                <span class="text-sm font-medium ${getMetricTextColor(metric.status)}">${metric.value}%</span>
            </div>
        `).join('');
    }
    
    // 학습 진행률 분포 차트 렌더링
    function renderProgressDistribution() {
        const distribution = [
            { range: '90-100', count: 38, percentage: 24, label: '최우수' },
            { range: '80-89', count: 45, percentage: 29, label: '우수' },
            { range: '70-79', count: 35, percentage: 22, label: '양호' },
            { range: '60-69', count: 26, percentage: 17, label: '보통' },
            { range: '0-59', count: 12, percentage: 8, label: '미달' }
        ];
        
        const container = document.getElementById('progressDistribution');
        container.innerHTML = distribution.map((item, idx) => `
            <div class="flex items-center">
                <span class="w-20 text-sm font-medium">${item.label}</span>
                <span class="w-16 text-xs text-gray-500">${item.range}%</span>
                <div class="flex-1 mx-4">
                    <div class="w-full bg-gray-200 rounded-full h-6 relative">
                        <div class="h-6 rounded-full transition-all duration-500 ${getDistributionColorClass(idx)}"
                             style="width: ${item.percentage}%"></div>
                        <span class="absolute inset-0 flex items-center justify-center text-xs font-medium">
                            ${item.count}명 (${item.percentage}%)
                        </span>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    // 색상 클래스 헬퍼 함수들
    function getSeverityClass(severity) {
        switch(severity) {
            case 'critical': return 'text-red-600 bg-red-50 border-red-200';
            case 'warning': return 'text-yellow-600 bg-yellow-50 border-yellow-200';
            case 'normal': return 'text-blue-600 bg-blue-50 border-blue-200';
            default: return 'text-gray-600 bg-gray-50 border-gray-200';
        }
    }
    
    function getMetricColorClass(status) {
        switch(status) {
            case 'critical': return 'bg-red-500';
            case 'warning': return 'bg-yellow-500';
            default: return 'bg-blue-500';
        }
    }
    
    function getMetricTextColor(status) {
        switch(status) {
            case 'critical': return 'text-red-600';
            case 'warning': return 'text-yellow-600';
            default: return 'text-blue-600';
        }
    }
    
    function getDistributionColorClass(idx) {
        const colors = [
            'bg-gradient-to-r from-green-500 to-green-600',
            'bg-gradient-to-r from-blue-500 to-blue-600',
            'bg-gradient-to-r from-yellow-400 to-yellow-500',
            'bg-gradient-to-r from-orange-400 to-orange-500',
            'bg-gradient-to-r from-red-400 to-red-500'
        ];
        return colors[idx] || '';
    }
    
    // 모달 닫기 (전역 함수로 설정)
    window.closeStudentModal = function() {
        const modal = document.getElementById('studentModal');
        modal.classList.add('hidden');
        selectedStudent = null;
    };
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && selectedStudent) {
            closeStudentModal();
        }
    });
    
    // 모달 배경 클릭으로 닫기
    const modal = document.getElementById('studentModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeStudentModal();
            }
        });
    }
    
    // 초기 렌더링
    renderProgressDistribution();
    
    // 버튼 클릭 이벤트 핸들러들
    document.querySelectorAll('button').forEach(button => {
        if (button.textContent.includes('KTM 리포트 생성')) {
            button.addEventListener('click', generateKTMReport);
        } else if (button.textContent.includes('긴급 대응 프로토콜 실행')) {
            button.addEventListener('click', executeEmergencyProtocol);
        } else if (button.textContent.includes('AI 상세 분석 리포트 생성')) {
            button.addEventListener('click', generateAIReport);
        }
    });
    
    // KTM 리포트 생성
    function generateKTMReport() {
        console.log('KTM 리포트 생성 중...');
        alert('KTM 종합 리포트가 생성되었습니다.');
    }
    
    // 긴급 대응 프로토콜 실행
    function executeEmergencyProtocol() {
        console.log('긴급 대응 프로토콜 실행 중...');
        alert('긴급 대응 프로토콜이 시작되었습니다. 관련 교사에게 알림이 발송됩니다.');
    }
    
    // AI 분석 리포트 생성
    function generateAIReport() {
        console.log('AI 분석 리포트 생성 중...');
        alert('AI 상세 분석 리포트가 준비되었습니다.');
    }
    
    // 실행 센터 버튼들
    const executionButtons = document.querySelectorAll('.bg-gradient-to-r');
    executionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const buttonText = this.textContent.trim();
            console.log('실행:', buttonText);
            
            if (buttonText.includes('위험군 일괄 상담 예약')) {
                alert('위험군 학생들의 상담이 예약되었습니다.');
            } else if (buttonText.includes('맞춤형 커리큘럼 생성')) {
                alert('맞춤형 커리큘럼이 생성 중입니다.');
            } else if (buttonText.includes('학부모 통합 브리핑')) {
                alert('학부모 브리핑 자료가 준비되었습니다.');
            } else if (buttonText.includes('KTM 성과 예측 시뮬레이션')) {
                alert('성과 예측 시뮬레이션을 시작합니다.');
            }
        });
    });
    
    // 실시간 데이터 업데이트 시뮬레이션 (옵션)
    function simulateRealtimeUpdate() {
        setInterval(() => {
            // 시스템 상태 업데이트
            const systemHealthElement = document.querySelector('.text-lg.font-bold');
            if (systemHealthElement) {
                const currentHealth = parseFloat(ktmStats.systemHealth);
                const newHealth = (currentHealth + (Math.random() * 2 - 1)).toFixed(1);
                systemHealthElement.innerHTML = `<i data-lucide="zap" class="mr-1 w-4 h-4"></i>${newHealth}% 정상`;
                lucide.createIcons();
            }
        }, 30000); // 30초마다 업데이트
    }
    
    // 실시간 업데이트 시작 (필요시 활성화)
    // simulateRealtimeUpdate();
});

// 차트 애니메이션 효과
document.addEventListener('DOMContentLoaded', function() {
    const progressBars = document.querySelectorAll('.h-6.rounded-full');
    progressBars.forEach((bar, index) => {
        setTimeout(() => {
            bar.style.transition = 'width 1s ease-out';
        }, index * 100);
    });
});

// 페이지 완전 로드 후 아이콘 재초기화
window.addEventListener('load', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});