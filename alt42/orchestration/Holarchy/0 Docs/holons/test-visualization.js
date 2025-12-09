// Playwright 시각화 테스트 스크립트
// 사용법: npx playwright test test-visualization.js --headed
const { test, expect } = require('@playwright/test');

test.describe('Quantum Dashboard Visualization Tests', () => {
    const baseUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/pocdashboard.php';

    test.beforeEach(async ({ page }) => {
        // 로그인 처리 (Moodle 세션 필요할 수 있음)
        await page.goto(baseUrl);
        await page.waitForLoadState('networkidle');
    });

    test('시나리오 1 파동 시각화 렌더링', async ({ page }) => {
        // 시나리오 1 카드 클릭
        await page.click('.scenario-card:nth-child(1)');

        // 시각화 컨테이너 로딩 대기
        await page.waitForSelector('.visualization-container', { timeout: 10000 });

        // Canvas 요소 존재 확인
        const waveCanvas = await page.locator('#wave-canvas-1');
        await expect(waveCanvas).toBeVisible();

        // 확률 분포 막대 확인
        const probBars = await page.locator('.probability-bars');
        await expect(probBars).toBeVisible();

        console.log('✅ 시나리오 1 시각화 렌더링 성공');
    });

    test('시나리오 2 다중 에이전트 간섭 시각화', async ({ page }) => {
        // 시나리오 2 카드 클릭
        await page.click('.scenario-card:nth-child(2)');

        // 시각화 컨테이너 대기
        await page.waitForSelector('.visualization-container', { timeout: 10000 });

        // 위상 레이더 차트 확인
        const radarCanvas = await page.locator('[id^="phase-radar-"]');
        await expect(radarCanvas).toBeVisible();

        // 위상 범례 확인
        const phaseLegend = await page.locator('.phase-legend');
        await expect(phaseLegend).toBeVisible();

        console.log('✅ 시나리오 2 다중 에이전트 시각화 성공');
    });

    test('모든 시나리오 실행 및 시각화', async ({ page }) => {
        // "모든 시나리오 실행" 버튼 클릭
        await page.click('button:has-text("모든 시나리오")');

        // 결과 대기
        await page.waitForTimeout(5000);

        // 시각화 컨테이너 개수 확인 (6개 시나리오)
        const containers = await page.locator('.visualization-container').count();
        console.log(`📊 시각화 컨테이너 개수: ${containers}`);

        // 최소 1개 이상의 시각화 확인
        expect(containers).toBeGreaterThanOrEqual(1);
    });
});
