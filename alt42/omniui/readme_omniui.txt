ㅌ# 강의 진단 및 개선을 위한 대화형 Omni interface 개발 (이현선 R)
- mathking과 자연어로 상호작용
- 선생님 : 우리반 기말고사 준비 상태 점검해줘.
- 선생님 : 오늘 지각할 거 같은 학생들 메세지보내줘. ... 
- 시스템 : OOO 학생은 내일 과제를 미이행할 위험이 있습니다. 메세지를 발송하시겠습니까 ?
- 시스템 : OOO 학생 학부모에게 시험 준비상황을 안내하시겠습니까 ? 
https://6067-221-158-175-237.ngrok-free.app/ 


# 블로그 자동화

실감나는 글을 구성하기 위한 파이프라인 설계
 
🧱 mdl_alt42_contextlog 테이블 구조
열 이름
자료형
널 허용
기본값
설명
id
int(11)
아니오
없음
기본키, 자동 증가
userid
int(11)
아니오
없음
사용자 ID
type
varchar(100)
예
NULL
활동 타입 ("비디오", "퀴즈")
text
text
예
NULL
텍스트 설명
start
bigint(20)
예
NULL
시작 시간 (UNIX 타임)
end
bigint(20)
예
NULL
종료 시간 (UNIX 타임)
timecreated
bigint(20)
예
NULL
생성 시간 (UNIX 타임)


 type - firsttalk, parentaltalk, personaltalk, journaling

📘 mdl_alt42_activitylog 테이블 구조 (최신)
열 이름
자료형
널 허용
기본값
설명
id
int(11)
아니오
없음
기본키, 자동 증가
userid
int(11)
아니오
없음
사용자 ID
course
varchar(100)
예
NULL
강좌 이름 또는 ID
curriculum
varchar(100)
예
NULL
교육과정 이름 또는 ID
type
varchar(100)
예
NULL
활동 유형
context
text
예
NULL
활동에 대한 설명 또는 맥락
status
varchar(50)
예
NULL
상태 ("완료", "진행중" 등)
value
int(11)
예
NULL
값 (점수, 수치 등)
text
text
예
NULL
추가적인 설명 텍스트
memory
text
예
NULL
기억/학습 이력
feedback
text
예
NULL
피드백
timecreated
bigint(20)
예
NULL
생성 시간 (UNIX 타임스탬프)


# 아래 내용들을 전략적으로 구성하고 기록하는 룰을 구성하여 적용한다. 

course : 개념, 심화, 내신, 수능
curriculum : 교과목
type : 분기목표, 준간목표, 오늘목표, 포모도르
context : 수업시작, 수업 초반, 수업 중반, 수업 후반, 귀가검사
status : 정규수업, 보충수업, 시험대비, 방학특강
value : 
text : 해석된 내용
memory : 학생메모 
feedback : 선생님 메모
 # 실감나는 학습 블로그 한편을 구성하기 위한 생생한 기록이 되기 위한 조건에 대해 연구한 결과 ....

암기효과 탁월한 UI 설계.
내적 사고회로 활성화하는 UX 설계 (외부에서 나타났다고 블러되면서 내부에서 진동하고 곧 다시 외부에 나타나게 하는 UI/UX).... 외부정보 >> 내부 시각회로(시신경 활성화)
질의응답...의 인지적 focusing 효과를 빌드업하여 폭발시키는 사고회로 설계.



## 인터페이스 ##
OmniUI - 선생님용용

## 인터페이스 ##
OmniUI - 학생용용

## 스트레칭 컨텐츠 ##

미러뉴런의 반응을 극대화 - 올림픽 운동 GIF, 요가, 스트레칭, 등산 등...
