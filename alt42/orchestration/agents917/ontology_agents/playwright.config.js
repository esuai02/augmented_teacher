/**
 * Playwright 설정 파일
 *
 * 온톨로지 추론 엔진 웹 인터페이스 E2E 테스트
 */

const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  // 테스트 파일 위치
  testDir: './tests/e2e',

  // 타임아웃 설정
  timeout: 60000,  // 테스트당 60초 (Python 스크립트 실행 시간 고려)

  // 재시도 설정
  retries: 2,      // 실패 시 2번 재시도

  // 병렬 실행 (Worker 수)
  workers: 1,      // 한 번에 하나씩 실행 (서버 부하 고려)

  // 전역 설정
  use: {
    // 기본 URL
    baseURL: 'https://mathking.kr',

    // 스크린샷 설정
    screenshot: 'only-on-failure',  // 실패 시에만 캡처

    // 비디오 녹화
    video: 'retain-on-failure',     // 실패 시에만 저장

    // 트레이스 수집
    trace: 'on-first-retry',        // 재시도 시 트레이스

    // 뷰포트 크기
    viewport: { width: 1280, height: 720 },

    // 네비게이션 타임아웃
    navigationTimeout: 30000,       // 페이지 로드 30초

    // 액션 타임아웃
    actionTimeout: 10000,           // 요소 클릭 등 10초

    // 추가 헤더 (필요시)
    extraHTTPHeaders: {
      'Accept-Language': 'ko-KR,ko;q=0.9,en;q=0.8'
    },
  },

  // 브라우저 설정
  projects: [
    {
      name: 'chromium',
      use: {
        browserName: 'chromium',
        // 헤드리스 모드 (디버깅 시 false로 변경)
        headless: true,
        // 브라우저 컨텍스트 옵션
        contextOptions: {
          // 권한 설정 (필요시)
          // permissions: ['clipboard-read', 'clipboard-write'],
        },
      },
    },
  ],

  // 리포터 설정
  reporter: [
    // HTML 리포트
    ['html', {
      outputFolder: 'test-results/html-report',
      open: 'never'  // 자동으로 열지 않음
    }],
    // 콘솔 출력
    ['list'],
    // JSON 리포트
    ['json', {
      outputFile: 'test-results/results.json'
    }],
  ],

  // 결과 저장 위치
  outputDir: 'test-results/',

  // 전역 설정 (beforeAll, afterAll)
  globalSetup: undefined,
  globalTeardown: undefined,

  // 웹 서버 설정 (로컬 서버 테스트 시 사용)
  // webServer: {
  //   command: 'npm run start',
  //   port: 3000,
  //   timeout: 120000,
  //   reuseExistingServer: !process.env.CI,
  // },
});
