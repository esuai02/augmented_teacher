/**
 * 컨텐츠 및 앱개발 모듈
 */

// 컨텐츠 및 앱개발 데이터 구조
const developmentData = {
    title: '컨텐츠 및 앱개발',
    description: '교육용 컨텐츠와 애플리케이션을 개발하고 관리하여 효과적인 학습 도구를 제공합니다.',
    tabs: [
        {
            id: 'content',
            title: '컨텐츠 개발',
            description: '교육 컨텐츠 생성 및 관리',
            explanation: '다양한 형태의 교육 컨텐츠를 개발하고 관리합니다.',
            items: []
        },
        {
            id: 'application',
            title: '앱 개발',
            description: '애플리케이션 개발',
            explanation: '다양한 플랫폼에서 활용할 수 있는 교육용 애플리케이션을 개발합니다.',
            items: [
                {
                    title: '모바일 앱',
                    description: '스마트폰과 태블릿에서 사용할 수 있는 모바일 교육 앱을 개발합니다.',
                    details: ['iOS 앱 개발', 'Android 앱 개발', '반응형 디자인', '모바일 UX 최적화']
                },
                {
                    title: '웹 애플리케이션',
                    description: '웹 브라우저에서 실행되는 교육용 웹 애플리케이션을 개발합니다.',
                    details: ['프론트엔드 개발', '백엔드 시스템', '클라우드 배포', '웹 보안']
                },
                {
                    title: '데스크톱 앱',
                    description: 'PC와 Mac에서 사용할 수 있는 데스크톱 교육 애플리케이션을 개발합니다.',
                    details: ['Windows 앱 개발', 'macOS 앱 개발', '네이티브 성능', '시스템 통합']
                },
                {
                    title: '크로스 플랫폼',
                    description: '여러 플랫폼에서 동시에 작동하는 크로스 플랫폼 앱을 개발합니다.',
                    details: ['통합 개발 환경', '코드 재사용', '플랫폼 최적화', '일관된 사용자 경험']
                }
            ]
        },
        {
            id: 'tools',
            title: '개발도구',
            description: '개발 도구 및 환경',
            explanation: '효율적인 개발을 위한 다양한 도구와 환경을 구축하고 관리합니다.',
            items: [
                {
                    title: '개발 프레임워크',
                    description: '효율적인 개발을 위한 프레임워크와 라이브러리를 구축합니다.',
                    details: ['프레임워크 선택', '라이브러리 통합', '개발 템플릿', '코드 표준화']
                },
                {
                    title: '테스트 도구',
                    description: '품질 보증을 위한 테스트 도구와 자동화 시스템을 구축합니다.',
                    details: ['자동화 테스트', '단위 테스트', '통합 테스트', '성능 테스트']
                },
                {
                    title: '배포 관리',
                    description: '애플리케이션 배포와 운영을 위한 관리 시스템을 구축합니다.',
                    details: ['CI/CD 파이프라인', '배포 자동화', '모니터링 시스템', '롤백 관리']
                },
                {
                    title: '버전 관리',
                    description: '코드와 컨텐츠의 버전을 체계적으로 관리합니다.',
                    details: ['Git 관리', '브랜치 전략', '릴리스 관리', '협업 도구']
                }
            ]
        }
    ]
};

// 컨텐츠 및 앱개발 관련 함수들
const developmentModule = {
    // 컨텐츠 생성
    createContent: function(type) {
        console.log(`컨텐츠 생성: ${type}`);
        // 컨텐츠 생성 로직
    },

    // 앱 개발
    developApp: function(platform) {
        console.log(`앱 개발: ${platform}`);
        // 앱 개발 로직
    },

    // 개발 도구 관리
    manageDevTools: function() {
        console.log('개발 도구 관리');
        // 개발 도구 관리 로직
    },

    // 개발 데이터 반환
    getData: function() {
        return developmentData;
    },

    // 프로젝트 관리
    manageProject: function() {
        console.log('프로젝트 관리 실행');
        // 프로젝트 관리 로직
    }
};

// 전역으로 노출
window.developmentModule = developmentModule;