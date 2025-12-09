const { chromium } = require('playwright');

async function testInteractionHistory() {
    console.log('🎭 Playwright 테스트 시작: interaction_history.php 풀이 보기 기능');
    
    const browser = await chromium.launch({ headless: false, slowMo: 1000 });
    const context = await browser.newContext();
    const page = await context.newPage();
    
    try {
        // 1. interaction_history.php 페이지로 이동
        console.log('📄 interaction_history.php 페이지 로드 중...');
        await page.goto('file:///mnt/c/1%20Project/augmented_teacher/alt42/teachingsupport/interaction_history.php', {
            waitUntil: 'networkidle'
        });
        
        // 2. 페이지 로드 확인
        await page.waitForSelector('.header h1', { timeout: 10000 });
        console.log('✅ 페이지 로드 완료');
        
        // 3. 상호작용 목록 로드 대기
        console.log('⏳ 상호작용 목록 로드 대기 중...');
        await page.waitForSelector('.interaction-list', { timeout: 15000 });
        
        // 4. 풀이 보기 버튼 찾기
        const solutionButtons = await page.$$('.action-btn-compact[onclick*="viewSolution"]');
        
        if (solutionButtons.length === 0) {
            console.log('⚠️ 풀이 보기 버튼을 찾을 수 없습니다. 데이터가 없을 수 있습니다.');
            
            // 빈 상태 확인
            const noInteractions = await page.$('.no-interactions');
            if (noInteractions) {
                console.log('📭 상호작용 데이터가 없는 상태입니다.');
                console.log('🧪 모의 데이터로 테스트를 진행합니다.');
                
                // 모의 모달 테스트를 위해 JavaScript 실행
                await page.evaluate(() => {
                    // 모의 데이터로 모달 열기
                    const mockInteractionId = 1;
                    if (typeof openLectureModal === 'function') {
                        openLectureModal(mockInteractionId);
                    } else {
                        console.error('openLectureModal function not found');
                    }
                });
            }
        } else {
            console.log(`🎯 ${solutionButtons.length}개의 풀이 보기 버튼 발견`);
            
            // 5. 첫 번째 풀이 보기 버튼 클릭
            console.log('🖱️ 첫 번째 풀이 보기 버튼 클릭...');
            await solutionButtons[0].click();
        }
        
        // 6. 모달 열림 확인
        console.log('⏳ 풀이 모달 로드 대기 중...');
        await page.waitForSelector('.modal-overlay.active', { timeout: 10000 });
        console.log('✅ 풀이 모달이 성공적으로 열렸습니다');
        
        // 7. 플레이 버튼 확인 및 클릭
        const playButton = await page.$('#playPauseBtn');
        if (playButton) {
            console.log('🎵 플레이 버튼 발견, 클릭 테스트 중...');
            
            // 클릭 전 상태 확인
            const beforeClick = await page.evaluate(() => {
                return {
                    isPlaying: window.isPlaying || false,
                    hasAudioPlayer: !!document.getElementById('modalAudioPlayer'),
                    audioSrc: document.getElementById('modalAudioPlayer')?.src || null
                };
            });
            
            console.log('🔍 클릭 전 상태:', beforeClick);
            
            // 플레이 버튼 클릭
            await playButton.click();
            console.log('🖱️ 플레이 버튼 클릭 완료');
            
            // 클릭 후 상태 확인
            await page.waitForTimeout(2000); // 2초 대기
            
            const afterClick = await page.evaluate(() => {
                return {
                    isPlaying: window.isPlaying || false,
                    hasAudioPlayer: !!document.getElementById('modalAudioPlayer'),
                    audioSrc: document.getElementById('modalAudioPlayer')?.src || null,
                    audioCurrentTime: document.getElementById('modalAudioPlayer')?.currentTime || 0,
                    audioDuration: document.getElementById('modalAudioPlayer')?.duration || 0,
                    errors: window.consoleErrors || []
                };
            });
            
            console.log('🔍 클릭 후 상태:', afterClick);
            
            // 결과 검증
            if (afterClick.hasAudioPlayer) {
                console.log('✅ 오디오 플레이어가 정상적으로 존재합니다');
                
                if (afterClick.audioSrc) {
                    console.log('✅ 오디오 소스가 설정되어 있습니다:', afterClick.audioSrc);
                } else {
                    console.log('⚠️ 오디오 소스가 설정되지 않았습니다');
                }
                
                if (afterClick.isPlaying !== beforeClick.isPlaying) {
                    console.log('✅ 재생 상태가 정상적으로 변경되었습니다');
                } else {
                    console.log('⚠️ 재생 상태 변경이 감지되지 않았습니다');
                }
            } else {
                console.log('❌ 오디오 플레이어를 찾을 수 없습니다');
            }
            
        } else {
            console.log('❌ 플레이 버튼을 찾을 수 없습니다');
        }
        
        // 8. 모달 닫기
        const closeButton = await page.$('.modal-close');
        if (closeButton) {
            await closeButton.click();
            console.log('🚪 모달 닫기 완료');
        }
        
        console.log('🎉 테스트 완료!');
        
    } catch (error) {
        console.error('❌ 테스트 실행 중 오류 발생:', error.message);
        
        // 스크린샷 저장
        await page.screenshot({ 
            path: '/mnt/c/1 Project/augmented_teacher/alt42/teachingsupport/test-error-screenshot.png',
            fullPage: true 
        });
        console.log('📸 오류 스크린샷이 저장되었습니다');
    } finally {
        await browser.close();
        console.log('🔚 브라우저 종료');
    }
}

// 에러 로깅을 위한 콘솔 모니터링
async function addConsoleLogging(page) {
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log('🚨 브라우저 콘솔 에러:', msg.text());
        }
    });
    
    page.on('pageerror', error => {
        console.log('🚨 페이지 에러:', error.message);
    });
}

if (require.main === module) {
    testInteractionHistory().catch(console.error);
}

module.exports = { testInteractionHistory };