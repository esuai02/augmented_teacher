// 탭 컨텐츠 렌더링
function renderTabContent(tabIndex) {
    const content = document.getElementById('tabContent');
    const tabTitles = ['기본 정보', '학습 진도', '학습 스타일', '학습 방식', '목표 설정', '추가 정보'];
    const tabIcons = ['user', 'book-open', 'brain', 'settings', 'target', 'heart'];
    const tabColors = ['purple', 'blue', 'green', 'indigo', 'orange', 'pink'];

    let html = `
        <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i data-lucide="${tabIcons[tabIndex]}" class="mr-3 text-${tabColors[tabIndex]}-500 w-7 h-7"></i>
            ${tabTitles[tabIndex]}
        </h3>
    `;

    switch(tabIndex) {
        case 0:
            html += getBasicInfoHTML();
            break;
        case 1:
            html += getLearningProgressHTML();
            break;
        case 2:
            html += getLearningStyleHTML();
            break;
        case 3:
            html += getLearningMethodHTML();
            break;
        case 4:
            html += getGoalSettingHTML();
            break;
        case 5:
            html += getAdditionalInfoHTML();
            break;
    }

    content.innerHTML = html;
    setTimeout(() => {
        lucide.createIcons();
        attachEventListeners();
        initializeSliders();
    }, 100);
}

// 스크롤 뷰 전체 컨텐츠 렌더링
function renderAllContent() {
    document.getElementById('basicInfoContent').innerHTML = getBasicInfoHTML();
    document.getElementById('learningProgressContent').innerHTML = getLearningProgressHTML();
    document.getElementById('learningStyleContent').innerHTML = getLearningStyleHTML();
    document.getElementById('learningMethodContent').innerHTML = getLearningMethodHTML();
    document.getElementById('goalSettingContent').innerHTML = getGoalSettingHTML();
    document.getElementById('additionalInfoContent').innerHTML = getAdditionalInfoHTML();

    setTimeout(() => {
        lucide.createIcons();
        attachEventListeners();
        initializeSliders();
        initializeGoals();
    }, 100);
}

// 기본 정보 HTML
function getBasicInfoHTML() {
    return `
        <div class="space-y-4 animate-fadeIn">
            <div class="flex items-center space-x-4">
                <label class="w-24 text-sm font-medium text-gray-700">학교</label>
                <input type="text" name="school" value="${formData.school}"
                    onchange="updateFormData('school', this.value)"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="학교명 입력">
            </div>

            <div>
                <div class="flex items-center space-x-4 mb-3">
                    <label class="w-24 text-sm font-medium text-gray-700">과정</label>
                    <div class="flex gap-2">
                        ${['초등', '중등', '고등'].map(level => `
                            <button type="button" onclick="updateFormData('courseLevel', '${level}')"
                                class="${formData.courseLevel === level
                                    ? 'px-4 py-2 rounded-lg font-medium transition-all bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg'
                                    : 'px-4 py-2 rounded-lg font-medium transition-all bg-gray-100 hover:bg-gray-200'}">
                                ${level}
                            </button>
                        `).join('')}
                    </div>
                </div>

                ${formData.courseLevel ? `
                    <div class="flex items-center space-x-4">
                        <label class="w-24 text-sm font-medium text-gray-700">학년</label>
                        <div class="flex gap-2">
                            ${gradeOptions[formData.courseLevel].map(grade => `
                                <button type="button" onclick="updateFormData('gradeDetail', '${grade}')"
                                    class="${formData.gradeDetail === grade
                                        ? 'px-4 py-2 rounded-lg font-medium transition-all bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg'
                                        : 'px-4 py-2 rounded-lg font-medium transition-all bg-white border border-gray-200 hover:border-purple-300'}">
                                    ${grade}
                                </button>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>

            <div class="flex items-center space-x-4">
                <label class="w-24 text-sm font-medium text-gray-700">학생이름</label>
                <input type="text" name="studentName" value="${formData.studentName}"
                    onchange="updateFormData('studentName', this.value)"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="이름 입력">
            </div>

            <div class="flex items-center space-x-4">
                <label class="w-24 text-sm font-medium text-gray-700">학생연락처</label>
                <input type="tel" name="studentPhone" value="${formData.studentPhone}"
                    onchange="updateFormData('studentPhone', this.value)"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="010-0000-0000">
            </div>

            <div class="border-t pt-4">
                <p class="text-xs text-gray-600 mb-3">※ 부모님 연락처 (한 분만 입력 가능)</p>
                <div class="flex items-center space-x-4">
                    <label class="w-24 text-sm font-medium text-gray-700">부모님연락처</label>
                    <div class="flex items-center space-x-2 flex-1">
                        <span class="text-sm text-gray-700">부</span>
                        <input type="tel" name="parentPhoneFather" value="${formData.parentPhoneFather}"
                            onchange="updateFormData('parentPhoneFather', this.value)"
                            ${formData.parentPhoneMother !== '010-' ? 'disabled' : ''}
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent disabled:bg-gray-100"
                            placeholder="010-0000-0000">
                        <span class="text-sm text-gray-700 ml-4">모</span>
                        <input type="tel" name="parentPhoneMother" value="${formData.parentPhoneMother}"
                            onchange="updateFormData('parentPhoneMother', this.value)"
                            ${formData.parentPhoneFather !== '010-' ? 'disabled' : ''}
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent disabled:bg-gray-100"
                            placeholder="010-0000-0000">
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <label class="w-24 text-sm font-medium text-gray-700">주소</label>
                <input type="text" name="address" value="${formData.address}"
                    onchange="updateFormData('address', this.value)"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="주소를 입력하세요">
            </div>
        </div>
    `;
}

// 학습 진도 HTML
function getLearningProgressHTML() {
    return `
        <div class="space-y-6 animate-fadeIn">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">학교 수학성적</label>
                <div class="grid grid-cols-3 gap-3">
                    ${['상위권', '중위권', '수학이 어려워요'].map(level => `
                        <button type="button" onclick="updateFormData('mathLevel', '${level}')"
                            class="${formData.mathLevel === level
                                ? 'p-4 rounded-xl font-medium transition-all transform hover:scale-105 bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-xl'
                                : 'p-4 rounded-xl font-medium transition-all transform hover:scale-105 bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'}">
                            ${level}
                        </button>
                    `).join('')}
                </div>
            </div>

            ${getProgressSliderHTML('개념공부 진도', 'conceptLevel', 'conceptProgress', formData.conceptLevel, formData.conceptProgress)}
            ${getProgressSliderHTML('심화학습 진도', 'advancedLevel', 'advancedProgress', formData.advancedLevel, formData.advancedProgress)}

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">참고사항</label>
                <textarea name="notes" onchange="updateFormData('notes', this.value)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    rows="4" placeholder="학습에 참고할 사항을 입력하세요">${formData.notes}</textarea>
            </div>
        </div>
    `;
}

// 진도 슬라이더 HTML
function getProgressSliderHTML(label, levelField, progressField, levelValue, progressValue) {
    const currentOptions = progressOptions[levelValue];
    const sliderValue = progressValue || 0;

    return `
        <div class="mb-8">
            <label class="text-sm font-medium text-gray-700 mb-3 block">
                ${label}: <span class="text-purple-600 font-semibold">${currentOptions[sliderValue]}</span>
            </label>

            <div class="flex gap-2 mb-4">
                ${['초등', '중등', '고등'].map(level => `
                    <button type="button" onclick="updateProgressLevel('${levelField}', '${progressField}', '${level}')"
                        class="${levelValue === level
                            ? 'px-4 py-2 rounded-lg font-medium transition-all bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg'
                            : 'px-4 py-2 rounded-lg font-medium transition-all bg-gray-100 hover:bg-gray-200'}">
                        ${level}
                    </button>
                `).join('')}
            </div>

            <div class="relative">
                <div class="relative h-2 bg-gray-200 rounded-full">
                    <div class="absolute h-full bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-300"
                        style="width: ${(sliderValue / (currentOptions.length - 1)) * 100}%"></div>
                </div>

                <input type="range"
                    name="${progressField}"
                    min="0"
                    max="${currentOptions.length - 1}"
                    value="${sliderValue}"
                    onchange="updateFormData('${progressField}', parseInt(this.value))"
                    oninput="updateProgressDisplay('${levelValue}', '${progressField}', this.value)"
                    class="absolute inset-0 w-full h-2 opacity-0 cursor-pointer"
                    style="z-index: 10">

                <div class="absolute inset-0 flex justify-between pointer-events-none">
                    ${currentOptions.map((option, index) => {
                        const position = (index / (currentOptions.length - 1)) * 100;
                        const isActive = index === sliderValue;

                        return `
                            <div class="absolute transform -translate-x-1/2" style="left: ${position}%">
                                <div class="${isActive
                                    ? 'w-3 h-3 rounded-full border-2 transition-all duration-300 bg-purple-600 border-purple-600 shadow-lg'
                                    : 'w-3 h-3 rounded-full border-2 transition-all duration-300 bg-white border-gray-300'}"></div>
                                <div class="${isActive
                                    ? 'absolute top-6 left-1/2 transform -translate-x-1/2 text-center transition-all duration-300 leading-tight text-purple-600 font-bold'
                                    : 'absolute top-6 left-1/2 transform -translate-x-1/2 text-center transition-all duration-300 leading-tight text-gray-400'}"
                                    style="writing-mode: vertical-lr; text-orientation: upright; font-size: 12px; letter-spacing: -1px;">
                                    ${option}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>

                <div class="mt-24 text-center">
                    <span id="${progressField}_display" class="inline-block px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full text-sm font-semibold shadow-lg">
                        ${currentOptions[sliderValue]}
                    </span>
                </div>
            </div>
        </div>
    `;
}

// 학습 스타일 HTML
function getLearningStyleHTML() {
    return `
        <div class="space-y-6 animate-fadeIn">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">문제풀이 선호도</label>
                <div class="grid grid-cols-3 gap-3">
                    ${['개념 정리 위주', '다양한 문제풀이', '고난도 심화 선호'].map(pref => `
                        <button type="button" onclick="updateFormData('problemPreference', '${pref}')"
                            class="${formData.problemPreference === pref
                                ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg'
                                : 'p-3 rounded-lg font-medium transition-all bg-white border border-gray-200 hover:border-blue-300'}">
                            ${pref}
                        </button>
                    `).join('')}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">시험 대비 성향</label>
                <div class="grid grid-cols-3 gap-3">
                    ${['벼락치기', '꾸준한 준비', '전략적 집중'].map(style => `
                        <button type="button" onclick="updateFormData('examStyle', '${style}')"
                            class="${formData.examStyle === style
                                ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-pink-500 to-purple-500 text-white shadow-lg'
                                : 'p-3 rounded-lg font-medium transition-all bg-gray-50 hover:bg-gray-100'}">
                            ${style}
                        </button>
                    `).join('')}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    수학 자신감: <span id="confidence_value">${formData.mathConfidence}</span>/10
                </label>
                <div class="relative">
                    <div class="h-3 bg-gray-200 rounded-full">
                        <div id="confidence_bar" class="h-full bg-gradient-to-r from-red-400 via-yellow-400 to-green-400 rounded-full transition-all duration-300"
                            style="width: ${(formData.mathConfidence / 10) * 100}%"></div>
                    </div>
                    <input type="range"
                        name="mathConfidence"
                        min="0"
                        max="10"
                        value="${formData.mathConfidence}"
                        onchange="updateFormData('mathConfidence', parseInt(this.value))"
                        oninput="updateConfidenceDisplay(this.value)"
                        class="absolute inset-0 w-full opacity-0 cursor-pointer">
                </div>
            </div>
        </div>
    `;
}

// 학습 방식 HTML
function getLearningMethodHTML() {
    return `
        <div class="space-y-6 animate-fadeIn">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">부모님 학습 지도 스타일</label>
                <div class="grid grid-cols-3 gap-3">
                    ${['적극 개입', '부분 지원', '자율 존중'].map(style => `
                        <button type="button" onclick="updateFormData('parentStyle', '${style}')"
                            class="${formData.parentStyle === style
                                ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg'
                                : 'p-3 rounded-lg font-medium transition-all bg-gray-50 hover:bg-gray-100'}">
                            ${style}
                        </button>
                    `).join('')}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">학습 스트레스</label>
                <div class="grid grid-cols-3 gap-3">
                    ${['낮음', '보통', '높음'].map(level => `
                        <button type="button" onclick="updateFormData('stressLevel', '${level}')"
                            class="${formData.stressLevel === level
                                ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-blue-500 to-teal-500 text-white shadow-lg'
                                : 'p-3 rounded-lg font-medium transition-all bg-gray-50 hover:bg-gray-100'}">
                            ${level}
                        </button>
                    `).join('')}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">피드백 선호 방식</label>
                <div class="grid grid-cols-3 gap-3">
                    ${[
                        {key: '직접', label: '직접', desc: '선생님 1:1 설명'},
                        {key: '컨텐츠', label: '컨텐츠', desc: '동영상, AI 설명 등'},
                        {key: '해설지', label: '해설지 제공', desc: '문제 해설지'}
                    ].map(pref => `
                        <button type="button" onclick="updateFormData('feedbackPreference', '${pref.key}')"
                            class="${formData.feedbackPreference === pref.key
                                ? 'p-3 rounded-lg transition-all bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                                : 'p-3 rounded-lg transition-all bg-white border border-gray-200 hover:border-purple-300'}">
                            <div class="font-medium">${pref.label}</div>
                            <div class="text-xs mt-1 opacity-80">${pref.desc}</div>
                        </button>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}

// 목표 설정 HTML
function getGoalSettingHTML() {
    const goalOptions = {
        '단기': [
            '이번 시험에서 점수 올리기',
            '틀린 문제 줄이기',
            '숙제 빠짐없이 하기',
            '시험 범위 개념 다시 확인하기',
            '오답노트 만들어 보기'
        ],
        '중기': [
            '교과서 개념 다 이해하기',
            '단원별 문제집 풀어보기',
            '어려운 문제도 혼자 풀어보기',
            '꾸준히 공부하는 습관 만들기',
            '수학에 대한 자신감 기르기'
        ],
        '장기': [
            '수학을 잘해서 원하는 학교 가기',
            '경시대회 준비해 보기',
            '심화 문제도 풀 수 있는 실력 쌓기',
            '수학을 좋아하게 되기',
            '긴 목표를 두고 꾸준히 공부하기'
        ]
    };

    return `
        <div class="space-y-6 animate-fadeIn">
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">📌 단기 목표 (시험 대비 · 성적 향상)</h4>
                ${formData.shortTermGoal ? `
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-700">✓ ${formData.shortTermGoal}</p>
                    </div>
                ` : `
                    <div id="shortTermGoalContainer">
                        <div class="p-4 bg-purple-50 rounded-lg mb-3">
                            <p class="text-sm text-purple-700 italic">이번 학기 중간고사에서 좋은 성적을 받고 싶어요!</p>
                        </div>
                        <div class="space-y-2">
                            ${goalOptions['단기'].map((option, index) => `
                                <button type="button" onclick="selectGoal('shortTermGoal', '${option}')"
                                    class="w-full p-3 rounded-lg text-left transition-all bg-white border border-gray-200 hover:border-blue-300 hover:bg-blue-50">
                                    <span class="text-sm">${index + 1}. ${option}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `}
            </div>

            ${formData.shortTermGoal ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">📌 중기 목표 (개념 완성 · 심화 학습)</h4>
                    ${formData.midTermGoal ? `
                        <div class="p-4 bg-green-50 rounded-lg">
                            <p class="text-sm text-green-700">✓ ${formData.midTermGoal}</p>
                        </div>
                    ` : `
                        <div id="midTermGoalContainer">
                            <div class="p-4 bg-purple-50 rounded-lg mb-3">
                                <p class="text-sm text-purple-700 italic">수학 개념을 완벽하게 이해하고 심화 학습을 하고 싶어요!</p>
                            </div>
                            <div class="space-y-2">
                                ${goalOptions['중기'].map((option, index) => `
                                    <button type="button" onclick="selectGoal('midTermGoal', '${option}')"
                                        class="w-full p-3 rounded-lg text-left transition-all bg-white border border-gray-200 hover:border-green-300 hover:bg-green-50">
                                        <span class="text-sm">${index + 1}. ${option}</span>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    `}
                </div>
            ` : ''}

            ${formData.midTermGoal ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">📌 장기 목표 (상위권 진학 · 올림피아드)</h4>
                    ${formData.longTermGoal ? `
                        <div class="p-4 bg-purple-50 rounded-lg">
                            <p class="text-sm text-purple-700">✓ ${formData.longTermGoal}</p>
                        </div>
                    ` : `
                        <div id="longTermGoalContainer">
                            <div class="p-4 bg-purple-50 rounded-lg mb-3">
                                <p class="text-sm text-purple-700 italic">명문대 진학과 수학 올림피아드 도전을 준비하고 있어요!</p>
                            </div>
                            <div class="space-y-2">
                                ${goalOptions['장기'].map((option, index) => `
                                    <button type="button" onclick="selectGoal('longTermGoal', '${option}')"
                                        class="w-full p-3 rounded-lg text-left transition-all bg-white border border-gray-200 hover:border-purple-300 hover:bg-purple-50">
                                        <span class="text-sm">${index + 1}. ${option}</span>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    `}
                </div>
            ` : ''}

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">참고사항</label>
                <input type="text" name="goalNote" value="${formData.goalNote}"
                    onchange="updateFormData('goalNote', this.value)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="추가로 전달하고 싶은 목표가 있다면 입력해주세요">
            </div>
        </div>
    `;
}

// 추가 정보 HTML
function getAdditionalInfoHTML() {
    return `
        <div class="space-y-6 animate-fadeIn">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    평소 주당 학습시간: <span id="weeklyHours_value">${formData.weeklyHours}</span>시간
                </label>
                <div class="relative">
                    <div class="h-3 bg-gray-200 rounded-full">
                        <div id="weeklyHours_bar" class="h-full bg-gradient-to-r from-green-400 to-blue-500 rounded-full transition-all duration-300"
                            style="width: ${(formData.weeklyHours / 30) * 100}%"></div>
                    </div>
                    <input type="range"
                        name="weeklyHours"
                        min="0"
                        max="30"
                        value="${formData.weeklyHours}"
                        onchange="updateFormData('weeklyHours', parseInt(this.value))"
                        oninput="updateWeeklyHoursDisplay(this.value)"
                        class="absolute inset-0 w-full opacity-0 cursor-pointer">
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-2">
                    <span>0시간</span>
                    <span>15시간</span>
                    <span>30시간</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">과거 학원/과외 경험 (총 경험 기간)</label>
                <div class="grid grid-cols-4 gap-2">
                    ${['처음', '1년이상', '2년이상', '3년이상', '4년이상', '5년이상', '6년이상'].map(exp => `
                        <button type="button" onclick="updateFormData('academyExperience', '${exp}')"
                            class="${formData.academyExperience === exp
                                ? 'p-2.5 rounded-lg text-sm font-medium transition-all bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg'
                                : 'p-2.5 rounded-lg text-sm font-medium transition-all bg-gray-50 hover:bg-gray-100'}">
                            ${exp}
                        </button>
                    `).join('')}
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox"
                        name="dataConsent"
                        ${formData.dataConsent ? 'checked' : ''}
                        onchange="updateFormData('dataConsent', this.checked)"
                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                    <span class="text-sm text-gray-700">
                        학생 학습 데이터 추적·분석에 동의합니다
                    </span>
                </label>
            </div>
        </div>
    `;
}

// Helper Functions - window 객체에 추가
window.updateProgressLevel = function(levelField, progressField, level) {
    formData[levelField] = level;
    formData[progressField] = Math.floor(progressOptions[level].length / 2);
    updateHiddenInputs();
    if (viewMode === 'tab') {
        renderTabContent(activeTab);
    } else {
        renderAllContent();
    }
}

window.updateProgressDisplay = function(levelValue, progressField, value) {
    formData[progressField] = parseInt(value);
    const display = document.getElementById(`${progressField}_display`);
    if (display) {
        display.textContent = progressOptions[levelValue][value];
    }
    updateHiddenInputs();
}

window.updateConfidenceDisplay = function(value) {
    formData.mathConfidence = parseInt(value);
    if (document.getElementById('confidence_value')) {
        document.getElementById('confidence_value').textContent = value;
        document.getElementById('confidence_bar').style.width = `${(value / 10) * 100}%`;
    }
    updateHiddenInputs();
}

window.updateWeeklyHoursDisplay = function(value) {
    formData.weeklyHours = parseInt(value);
    if (document.getElementById('weeklyHours_value')) {
        document.getElementById('weeklyHours_value').textContent = value;
        document.getElementById('weeklyHours_bar').style.width = `${(value / 30) * 100}%`;
    }
    updateHiddenInputs();
}

window.selectGoal = function(goalType, value) {
    formData[goalType] = value;
    updateHiddenInputs();
    if (viewMode === 'tab') {
        renderTabContent(activeTab);
    } else {
        renderAllContent();
    }
}

// 글로벌에서 사용할 수 있도록 window 객체에 추가
window.updateFormData = function(field, value) {
    formData[field] = value;
    updateHiddenInputs();

    // 전화번호 특별 처리
    if (field === 'parentPhoneFather' && value !== '010-') {
        formData.parentPhoneMother = '010-';
        updateInputs();
    } else if (field === 'parentPhoneMother' && value !== '010-') {
        formData.parentPhoneFather = '010-';
        updateInputs();
    }

    // 과정 변경시 학년 초기화
    if (field === 'courseLevel') {
        formData.gradeDetail = '';
        if (viewMode === 'tab') {
            renderTabContent(activeTab);
        } else {
            renderAllContent();
        }
    }
}

// Hidden input 업데이트
function updateHiddenInputs() {
    Object.keys(formData).forEach(key => {
        const hiddenInput = document.getElementById(key + '_hidden');
        if (hiddenInput) {
            if (typeof formData[key] === 'boolean') {
                hiddenInput.value = formData[key] ? '1' : '0';
            } else {
                hiddenInput.value = formData[key];
            }
        }
    });
}

// 입력 필드 업데이트
function updateInputs() {
    Object.keys(formData).forEach(key => {
        const input = document.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = formData[key];
            } else {
                input.value = formData[key];
            }
        }
    });
}

function attachEventListeners() {
    // 이벤트 리스너는 인라인으로 처리
}

function initializeSliders() {
    // 슬라이더 초기화는 인라인으로 처리
}

function initializeGoals() {
    // 목표 초기화
}

// 페이지 로드시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 초기 값 설정
    updateHiddenInputs();

    setViewMode('scroll');

    // 폼 제출 시 hidden inputs 업데이트
    const form = document.getElementById('onboardingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            updateHiddenInputs();
        });
    }
});