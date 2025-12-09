# Shining Stars - AI 기반 수학 학습 동반자 시스템

## 🌟 프로젝트 소개

Shining Stars는 학생들의 수학에 대한 부정적 인식을 제거하고, 뇌가소성을 활용하여 잠재력을 최대화하는 AI 기반 교육 시스템입니다.

### 핵심 목표
- 🧠 **심리적 변화**: 수학에 대한 무의식적 부정 인식 제거
- 💊 **도파민 시스템 활성화**: Tonic/Phasic 도파민 분비 유도
- 🌱 **뇌가소성 촉진**: 지속적인 성찰과 피드백을 통한 성장
- 🎯 **개인화된 학습**: AI 에이전트의 맞춤형 가이드

## 🚀 주요 기능

### 학생용 기능
- **몰입형 여정 맵**: 우주 테마의 시각적 학습 경로
- **자유로운 성찰**: 제약 없는 감정과 생각 표현
- **AI 피드백**: 공감적이고 통찰력 있는 즉각적 응답
- **진행 시각화**: 성장 과정을 별자리로 표현

### 교사용 기능
- **학급 대시보드**: 전체 학생 현황 한눈에 보기
- **AI 인사이트**: 특별 관심이 필요한 학생 알림
- **감정 분석**: 학생들의 심리 상태 모니터링

## 🛠 기술 스택

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **AI**: OpenAI GPT-4 API
- **Database**: MySQL/MariaDB
- **Authentication**: Moodle LMS Integration

## 📦 설치 방법

### 요구사항
- PHP 7.4 이상
- MySQL 5.7 이상 또는 MariaDB 10.3 이상
- Moodle LMS 3.9 이상
- Composer
- OpenAI API Key

### 설치 단계

1. **프로젝트 클론**
```bash
git clone https://github.com/yourusername/shiningstars.git
cd shiningstars
```

2. **의존성 설치**
```bash
composer install
```

3. **환경 설정**
```bash
cp .env.example .env
# .env 파일을 열어 설정값 입력
```

4. **데이터베이스 설정**
```bash
mysql -u root -p < sql/schema.sql
mysql -u root -p < sql/seed.sql
```

5. **권한 설정**
```bash
chmod 755 logs/
chmod 755 data/
chmod 600 .env
```

6. **Moodle 통합**
- Moodle 관리자 페이지에서 외부 도구 추가
- URL: `https://yourdomain.com/shiningstars/index.php`

## 🎯 사용 방법

### 학생
1. Moodle에서 "수학 성찰의 별자리" 링크 클릭
2. 첫 번째 별(시작점)을 클릭하여 여정 시작
3. 질문에 대해 자유롭게 성찰 작성
4. AI 피드백 확인 후 다음 별로 이동

### 교사
1. 교사 대시보드 접속 (`/teacher/dashboard.php`)
2. 학급 전체 진행 상황 확인
3. AI가 제공하는 인사이트 검토
4. 필요 시 개별 학생 지도

## 📚 문서

- [시스템 아키텍처](SYSTEM_ARCHITECTURE.md)
- [프로젝트 구조](PROJECT_STRUCTURE.md)
- [API 문서](API_DESIGN.md)
- [데이터베이스 스키마](sql/schema.sql)

## 🔒 보안

- 모든 API 통신은 HTTPS 필수
- OpenAI API 키는 환경 변수로 관리
- SQL 인젝션 방지를 위한 prepared statements 사용
- XSS 방지를 위한 출력 이스케이핑

## 🤝 기여 방법

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다.

## 👥 팀

- **프로젝트 리더**: [이름]
- **AI 개발**: [이름]
- **UI/UX 디자인**: [이름]
- **교육 전문가**: [이름]

## 📞 문의

프로젝트에 대한 문의사항은 [email@example.com](mailto:email@example.com)으로 연락주세요.

---

*"모든 학생은 수학의 별이 될 수 있습니다. 우리는 그 여정의 동반자입니다."* ⭐