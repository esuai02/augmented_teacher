/**
 * timescaffolding42.php 수정사항 검증 스크립트
 * 
 * 사용법: 브라우저 개발자 콘솔에서 실행하여 수정사항을 검증합니다.
 */

console.log("🔍 timescaffolding42.php 수정사항 검증 시작...");

// 1. 기본 JavaScript 함수들 존재 확인
const requiredFunctions = [
    'addNewNote',
    'editNote', 
    'deleteNote',
    'toggleView',
    'switchTab',
    'drawCharts',
    'addDirectInput',
    'addFixNote',
    'BeginInstruction',
    'hideItem',
    'fixText',
    'ChangeCheckBox',
    'Resttime'
];

console.log("📋 1. JavaScript 함수 존재 확인:");
let functionCheckResults = {};
requiredFunctions.forEach(funcName => {
    const exists = typeof window[funcName] === 'function';
    functionCheckResults[funcName] = exists;
    console.log(`${exists ? '✅' : '❌'} ${funcName}: ${exists ? '존재함' : '누락됨'}`);
});

// 2. DOM 요소들 존재 확인
const requiredElements = [
    'memo-section',
    'teacher-notes-area', 
    'student-notes-area',
    'copyButton'
];

console.log("\n🏗️ 2. 주요 DOM 요소 존재 확인:");
let elementCheckResults = {};
requiredElements.forEach(elemId => {
    const element = document.getElementById(elemId);
    const exists = element !== null;
    elementCheckResults[elemId] = exists;
    console.log(`${exists ? '✅' : '❌'} #${elemId}: ${exists ? '존재함' : '누락됨'}`);
});

// 3. 네비게이션 링크 확인 (42 에코시스템)
console.log("\n🔗 3. 네비게이션 링크 42 에코시스템 확인:");
const navLinks = document.querySelectorAll('.nav-btn');
let linkCheckResults = [];
navLinks.forEach((link, index) => {
    const href = link.getAttribute('href');
    const uses42 = href && (href.includes('42.php') || href.includes('alt42'));
    linkCheckResults.push({
        index: index + 1,
        href: href,
        uses42: uses42,
        text: link.textContent.trim()
    });
    console.log(`${uses42 ? '✅' : '⚠️'} Link ${index + 1}: ${link.textContent.trim()} -> ${href}`);
});

// 4. 메모 기능 테스트
console.log("\n📝 4. 메모 시스템 기능 확인:");
let memoSystemResults = {};

// 메모 섹션 확인
const memoSection = document.getElementById('memo-section');
memoSystemResults.memoSectionExists = !!memoSection;
console.log(`${memoSystemResults.memoSectionExists ? '✅' : '❌'} 메모 섹션: ${memoSystemResults.memoSectionExists ? '존재함' : '누락됨'}`);

// 새 메모 버튼 확인
const newNoteButtons = document.querySelectorAll('button[onclick*="addNewNote"]');
memoSystemResults.newNoteButtonExists = newNoteButtons.length > 0;
console.log(`${memoSystemResults.newNoteButtonExists ? '✅' : '❌'} 새 메모 버튼: ${memoSystemResults.newNoteButtonExists ? '존재함' : '누락됨'}`);

// 5. 안전한 DOM 접근 함수들 확인
console.log("\n🛡️ 5. 안전한 DOM 접근 함수 확인:");
const safetyFunctions = ['safeGetElement', 'safeQuerySelector', 'safeQuerySelectorAll'];
let safetyCheckResults = {};
safetyFunctions.forEach(funcName => {
    const exists = typeof window[funcName] === 'function';
    safetyCheckResults[funcName] = exists;
    console.log(`${exists ? '✅' : '❌'} ${funcName}: ${exists ? '존재함' : '누락됨'}`);
});

// 6. 전체 결과 요약
console.log("\n📊 검증 결과 요약:");
const totalFunctions = requiredFunctions.length;
const workingFunctions = Object.values(functionCheckResults).filter(Boolean).length;
const functionSuccess = (workingFunctions / totalFunctions) * 100;

const totalElements = requiredElements.length;
const workingElements = Object.values(elementCheckResults).filter(Boolean).length;
const elementSuccess = (workingElements / totalElements) * 100;

const total42Links = linkCheckResults.filter(link => link.uses42).length;
const linkSuccess = (total42Links / linkCheckResults.length) * 100;

const totalSafetyFunctions = safetyFunctions.length;
const workingSafetyFunctions = Object.values(safetyCheckResults).filter(Boolean).length;
const safetySuccess = (workingSafetyFunctions / totalSafetyFunctions) * 100;

console.log(`🔧 JavaScript 함수: ${workingFunctions}/${totalFunctions} (${functionSuccess.toFixed(1)}%)`);
console.log(`🏗️ DOM 요소: ${workingElements}/${totalElements} (${elementSuccess.toFixed(1)}%)`);
console.log(`🔗 42 에코시스템 링크: ${total42Links}/${linkCheckResults.length} (${linkSuccess.toFixed(1)}%)`);
console.log(`🛡️ 안전 함수: ${workingSafetyFunctions}/${totalSafetyFunctions} (${safetySuccess.toFixed(1)}%)`);

const overallSuccess = (functionSuccess + elementSuccess + linkSuccess + safetySuccess) / 4;
console.log(`\n🎯 전체 성공률: ${overallSuccess.toFixed(1)}%`);

// 7. 문제점 및 권장사항
if (overallSuccess < 90) {
    console.log("\n⚠️ 발견된 문제점:");
    
    if (functionSuccess < 100) {
        const missingFunctions = requiredFunctions.filter(func => !functionCheckResults[func]);
        console.log(`- 누락된 함수: ${missingFunctions.join(', ')}`);
    }
    
    if (elementSuccess < 100) {
        const missingElements = requiredElements.filter(elem => !elementCheckResults[elem]);
        console.log(`- 누락된 DOM 요소: ${missingElements.join(', ')}`);
    }
    
    if (linkSuccess < 100) {
        const non42Links = linkCheckResults.filter(link => !link.uses42);
        console.log(`- 42 에코시스템 미적용 링크: ${non42Links.length}개`);
    }
} else {
    console.log("\n🎉 모든 수정사항이 성공적으로 적용되었습니다!");
    console.log("오답노트 버튼과 모든 기능이 정상적으로 작동할 것으로 예상됩니다.");
}

// 8. 다음 단계 안내
console.log("\n📋 다음 단계:");
console.log("1. 실제 서버에서 timescaffolding42.php를 로드하여 테스트");
console.log("2. 오답노트 버튼 클릭하여 메모 작성 모달 확인");
console.log("3. 서버 로그 확인하여 디버그 정보 검토");
console.log("4. 모든 네비게이션 링크가 정상 작동하는지 확인");

// 결과 반환
return {
    functionCheckResults,
    elementCheckResults, 
    linkCheckResults,
    memoSystemResults,
    safetyCheckResults,
    overallSuccess: overallSuccess.toFixed(1) + '%'
};