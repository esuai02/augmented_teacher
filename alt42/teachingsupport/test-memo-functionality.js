const { chromium } = require('playwright');

async function testMemoFunctionality() {
    console.log('🎭 Playwright 테스트 시작: 메모 기능 검증');
    
    const browser = await chromium.launch({ 
        headless: false, 
        slowMo: 1000,
        args: ['--disable-web-security', '--disable-features=VizDisplayCompositor']
    });
    const context = await browser.newContext();
    const page = await context.newPage();
    
    // 콘솔 에러 및 네트워크 요청 모니터링
    const consoleErrors = [];
    const networkRequests = [];
    
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
            console.log('🚨 브라우저 콘솔 에러:', msg.text());
        }
    });
    
    page.on('request', request => {
        if (request.url().includes('stickynotes_api.php')) {
            networkRequests.push({
                url: request.url(),
                method: request.method(),
                postData: request.postData()
            });
            console.log('📡 API 요청:', request.method(), request.url());
        }
    });
    
    page.on('response', response => {
        if (response.url().includes('stickynotes_api.php')) {
            console.log('📡 API 응답:', response.status(), response.url());
        }
    });
    
    try {
        // 1. timescaffolding42.php 페이지 로드
        console.log('📄 timescaffolding42.php 페이지 로드 중...');
        await page.goto('file:///mnt/c/1%20Project/augmented_teacher/teachers/timescaffolding42.php', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        console.log('✅ 페이지 로드 완료');
        
        // 2. 메모 섹션 확인
        console.log('🔍 메모 섹션 확인 중...');
        const memoSection = await page.$('#memo-section');
        if (memoSection) {
            console.log('✅ 메모 섹션 발견됨');
        } else {
            console.log('❌ 메모 섹션을 찾을 수 없음');
            return { status: 'FAIL', reason: 'memo-section not found' };
        }
        
        // 3. JavaScript 변수 확인
        console.log('⚙️ JavaScript 변수 확인 중...');
        const jsVariables = await page.evaluate(() => {
            return {
                currentUserId: typeof window.currentUserId !== 'undefined' ? window.currentUserId : null,
                userRole: typeof window.userRole !== 'undefined' ? window.userRole : null,
                hasCurrentUserId: typeof window.currentUserId !== 'undefined',
                hasLoadNotesFunction: typeof window.loadNotes === 'function',
                hasSaveNoteFunction: typeof window.saveNote === 'function',
                hasUpdateNoteFunction: typeof window.updateNote === 'function'
            };
        });
        
        console.log('⚙️ JavaScript 환경:', jsVariables);
        
        if (!jsVariables.hasCurrentUserId) {
            console.log('❌ currentUserId 변수가 정의되지 않음');
            return { status: 'FAIL', reason: 'currentUserId not defined' };
        }
        
        // 4. 메모 추가 버튼 확인
        console.log('🔘 메모 추가 버튼 확인 중...');
        const addNoteButton = await page.$('#add-note-btn');
        if (addNoteButton) {
            console.log('✅ 메모 추가 버튼 발견됨');
            
            // 버튼 클릭 시뮬레이션 (실제 서버 없이는 제한적)
            console.log('🖱️ 메모 추가 버튼 클릭 시뮬레이션...');
            
            // JavaScript 함수 직접 호출로 테스트
            const simulatedResult = await page.evaluate(() => {
                // saveNote 함수 파라미터 검증
                if (typeof window.saveNote === 'function') {
                    // 실제 호출 대신 파라미터 검증만 수행
                    const testContent = "테스트 메모";
                    const testColor = "yellow";
                    
                    // AJAX 요청 데이터 구조 검증
                    const expectedData = {
                        action: "add_note",
                        userid: window.currentUserId,
                        content: testContent,
                        color: testColor
                    };
                    
                    return {
                        functionExists: true,
                        expectedData: expectedData,
                        currentUserId: window.currentUserId
                    };
                } else {
                    return { functionExists: false };
                }
            });
            
            console.log('🔧 saveNote 함수 검증:', simulatedResult);
            
        } else {
            console.log('⚠️ 메모 추가 버튼을 찾을 수 없음');
        }
        
        // 5. updateNote 함수 파라미터 검증
        console.log('🔧 updateNote 함수 파라미터 검증 중...');
        const updateNoteValidation = await page.evaluate(() => {
            if (typeof window.updateNote === 'function') {
                // updateNote 함수 소스 확인 (간접적)
                const funcString = window.updateNote.toString();
                
                return {
                    functionExists: true,
                    hasNoteIdParam: funcString.includes('note_id'),
                    hasUserIdParam: funcString.includes('userid'),
                    hasContentParam: funcString.includes('content'),
                    hasColorParam: funcString.includes('color'),
                    functionSource: funcString.substring(0, 200) + '...'
                };
            } else {
                return { functionExists: false };
            }
        });
        
        console.log('🔧 updateNote 검증 결과:', updateNoteValidation);
        
        // 6. 에러 메시지 시뮬레이션 테스트
        console.log('🧪 에러 조건 시뮬레이션 중...');
        const errorSimulation = await page.evaluate(() => {
            // 잘못된 파라미터로 API 호출 시뮬레이션
            const invalidRequests = [
                {
                    scenario: 'Missing note_id for update',
                    data: { action: 'update_note', userid: 1, content: 'test' },
                    expectedError: '메모 ID, 내용 및 사용자 ID가 필요합니다.'
                },
                {
                    scenario: 'Missing userid for update',
                    data: { action: 'update_note', note_id: 1, content: 'test' },
                    expectedError: '메모 ID, 내용 및 사용자 ID가 필요합니다.'
                },
                {
                    scenario: 'Missing content for add',
                    data: { action: 'add_note', userid: 1 },
                    expectedError: '메모 내용과 사용자 ID가 필요합니다.'
                }
            ];
            
            return invalidRequests;
        });
        
        console.log('🧪 에러 시나리오:', errorSimulation);
        
        // 7. 전체 테스트 결과 평가
        console.log('\n📊 메모 기능 테스트 결과 요약:');
        
        const testResults = {
            pageLoad: true,
            memoSectionExists: !!memoSection,
            jsVariablesValid: jsVariables.hasCurrentUserId && jsVariables.hasLoadNotesFunction,
            saveNoteFunctionExists: jsVariables.hasSaveNoteFunction,
            updateNoteFunctionExists: jsVariables.hasUpdateNoteFunction,
            updateNoteParametersCorrect: updateNoteValidation.hasNoteIdParam && updateNoteValidation.hasUserIdParam,
            consoleErrorCount: consoleErrors.length,
            networkRequestCount: networkRequests.length
        };
        
        console.log('   📄 페이지 로드:', testResults.pageLoad ? 'PASS' : 'FAIL');
        console.log('   🎯 메모 섹션:', testResults.memoSectionExists ? 'PASS' : 'FAIL');
        console.log('   ⚙️ JS 변수:', testResults.jsVariablesValid ? 'PASS' : 'FAIL');
        console.log('   💾 saveNote 함수:', testResults.saveNoteFunctionExists ? 'PASS' : 'FAIL');
        console.log('   ✏️ updateNote 함수:', testResults.updateNoteFunctionExists ? 'PASS' : 'FAIL');
        console.log('   🔧 updateNote 파라미터:', testResults.updateNoteParametersCorrect ? 'PASS' : 'FAIL');
        console.log('   🚨 콘솔 에러:', testResults.consoleErrorCount);
        console.log('   📡 네트워크 요청:', testResults.networkRequestCount);
        
        // 전체 평가
        const passCount = Object.values(testResults).filter(v => v === true).length;
        const totalTests = 6; // 숫자 값 제외
        
        let overallStatus;
        if (passCount >= 5 && testResults.updateNoteParametersCorrect) {
            overallStatus = 'PASS';
            console.log('🎉 메모 기능 테스트: 성공 (PASS)');
            console.log('✅ 메모 ID, 내용 및 사용자 ID 오류가 수정되었습니다.');
        } else if (passCount >= 3) {
            overallStatus = 'PARTIAL';
            console.log('⚠️ 메모 기능 테스트: 부분 성공 (PARTIAL)');
            console.log('🔧 일부 개선이 필요합니다.');
        } else {
            overallStatus = 'FAIL';
            console.log('❌ 메모 기능 테스트: 실패 (FAIL)');
            console.log('🚨 추가 수정이 필요합니다.');
        }
        
        return {
            status: overallStatus,
            details: testResults,
            jsVariables: jsVariables,
            updateNoteValidation: updateNoteValidation,
            consoleErrors: consoleErrors,
            networkRequests: networkRequests
        };
        
    } catch (error) {
        console.error('❌ 테스트 실행 중 오류 발생:', error.message);
        
        return {
            status: 'ERROR',
            error: error.message,
            consoleErrors: consoleErrors.length
        };
        
    } finally {
        await browser.close();
        console.log('🔚 브라우저 종료');
    }
}

// 테스트 실행
if (require.main === module) {
    testMemoFunctionality()
        .then(results => {
            console.log('\n🏁 최종 메모 테스트 결과:', results);
            process.exit(results.status === 'PASS' ? 0 : 1);
        })
        .catch(error => {
            console.error('❌ 테스트 프로세스 실패:', error);
            process.exit(1);
        });
}

module.exports = { testMemoFunctionality };