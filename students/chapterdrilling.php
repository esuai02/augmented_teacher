<?php
/**
 * moodle_react_progress.php
 * Moodle + React + PHP 단일 예시
 * DB에서 단원 정보 가져와 React를 이용해 학습진행도 표시
 */

// 1) Moodle 설정 불러오기
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

// 2) GET 파라미터 읽기 (예시: cid, domain, studentid 등)
$cid      = optional_param('cid', 0, PARAM_INT);
$domain   = optional_param('domain', '', PARAM_TEXT);
$studentid= optional_param('studentid', 0, PARAM_INT);

if (!$studentid) {
  // 로그인 정보 없는 경우 현재 사용자로 대체
  $studentid = $USER->id;
}

// (예시) domain 테이블에서 단원 수, cid목록 가져오기
// domain이 예를 들어 'math'라 가정
$chlist = $DB->get_record_sql(
  "SELECT * FROM {abessi_domain} WHERE domain = :domain",
  ['domain' => $domain]
);

if (!$chlist) {
  // domain 값이 올바르지 않으면 종료
  die('Invalid domain.');
}

// 예: $chlist->chnum에 총 챕터 수가 들어있다고 가정
$chapnum = $chlist->chnum;

// 3) 각 챕터별 curriculum 정보를 가져와서 배열 구성
$chapters = array();
for ($nch = 1; $nch <= $chapnum; $nch++) {
  $cidstr = 'cid' . $nch;  // 예: cid1, cid2 ...
  $chstr  = 'nch' . $nch;  // 예: nch1, nch2 ...
  $cid2   = $chlist->$cidstr;    // curriculum 테이블 id
  $nchapter = $chlist->$chstr;   // 실제 챕터번호

  $curri = $DB->get_record_sql(
    "SELECT * FROM {abessi_curriculum} WHERE id = :id",
    ['id' => $cid2]
  );
  // 예: curriculum 테이블에 ch1, ch2 ... 이런 컬럼명이 있고
  // $nchapter가 3이면 ch3 칼럼을 가져옴
  $chname = 'ch' . $nchapter;
  $title = $curri->$chname;

  // 실제로 이동할 URL 등
  $chapterUrl = "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php"
              . "?cid={$cid2}&nch={$nchapter}&studentid={$studentid}";

  // React에서 사용할 id, title, url
  $chapters[] = [
    'id'    => $nch,
    'title' => $title,
    'url'   => $chapterUrl
  ];
}

// 4) JSON으로 변환 (React에서 사용)
$chapters_json = json_encode($chapters, JSON_UNESCAPED_UNICODE);

// 이제 HTML 영역을 출력해 React를 렌더링
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>React 학습 진행도 예시</title>
  <!-- Tailwind CSS (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- React, ReactDOM (개발용 CDN) -->
  <script src="https://unpkg.com/react@17/umd/react.development.js" crossorigin></script>
  <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js" crossorigin></script>
  <!-- Babel: 브라우저 환경에서 JSX 트랜스파일링 (개발 테스트용) -->
  <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
  <!-- Lucide React 아이콘 (CDN) -->
  <script src="https://unpkg.com/lucide-react"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-2xl mx-auto py-10">
  <div id="root"></div>
</div>

<script type="text/babel">
  // Lucide React 아이콘을 window.lucideReact에서 꺼내옴
  const { Lock, CheckCircle, Circle } = window.lucideReact;
  const { useState, useEffect } = React;

  // LearningProgress 컴포넌트
  const LearningProgress = () => {
    // 1) state 정의
    const [chapters, setChapters] = useState([]);
    const [completedChapters, setCompletedChapters] = useState(new Set());
    const [currentChapter, setCurrentChapter] = useState(1);

    // 2) 마운트 시 PHP로부터 챕터정보(JSON) 받기
    useEffect(() => {
      const phpChapters = JSON.parse('<?php echo addslashes($chapters_json); ?>');
      setChapters(phpChapters);
    }, []);

    // 3) 클릭 시 완료 처리
    const handleChapterClick = (chapterId) => {
      // 현재 챕터 이하만 클릭 유효
      if (chapterId <= currentChapter) {
        if (!completedChapters.has(chapterId)) {
          const newCompleted = new Set(completedChapters);
          newCompleted.add(chapterId);
          setCompletedChapters(newCompleted);

          if (chapterId === currentChapter) {
            setCurrentChapter(currentChapter + 1);
          }
        }
      }
    };

    // 4) 챕터 상태 구분
    const getChapterStatus = (chapterId) => {
      if (completedChapters.has(chapterId)) {
        return 'completed';
      }
      if (chapterId === currentChapter) {
        return 'current';
      }
      return 'locked';
    };

    // 5) JSX 출력
    return (
      <div className="p-4 bg-white rounded shadow">
        <h2 className="text-2xl font-bold mb-6 text-center">수학 학습 여정</h2>
        <div className="space-y-4">
          {chapters.map((chapter) => {
            const status = getChapterStatus(chapter.id);

            return (
              <div
                key={chapter.id}
                className={
                  "transition-all duration-300 border rounded p-3 " +
                  (status === 'current'
                    ? "border-blue-500"
                    : status === 'completed'
                    ? "bg-green-50"
                    : "opacity-75"
                  )
                }
              >
                <div
                  className={
                    "flex items-center justify-between cursor-pointer " +
                    (status === 'locked' ? 'cursor-not-allowed' : '')
                  }
                  onClick={() => handleChapterClick(chapter.id)}
                >
                  <div className="flex items-center space-x-4">
                    {status === 'completed' ? (
                      <CheckCircle className="text-green-500" />
                    ) : status === 'current' ? (
                      <Circle className="text-blue-500" />
                    ) : (
                      <Lock className="text-gray-400" />
                    )}
                    <span
                      className={
                        "font-medium " +
                        (status === 'current'
                          ? "text-blue-600"
                          : status === 'completed'
                          ? "text-green-600"
                          : "text-gray-500")
                      }
                    >
                      {chapter.title}
                    </span>
                  </div>
                  <div className="flex items-center space-x-2">
                    {status === 'completed' && (
                      <span className="text-sm text-green-600">완료!</span>
                    )}
                    {status === 'current' && (
                      <span className="text-sm text-blue-600">학습 가능</span>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>

        {/* 하단 진행도 표시 */}
        <div className="mt-6 text-center text-gray-600">
          {chapters.length > 0 && completedChapters.size === chapters.length ? (
            <p className="text-lg font-bold text-green-600">
              축하합니다! 모든 단원을 완료했습니다! 🎉
            </p>
          ) : (
            <p>
              완료한 단원: {completedChapters.size} / {chapters.length}
            </p>
          )}
        </div>
      </div>
    );
  };

  // 6) ReactDOM.render(...)
  ReactDOM.render(<LearningProgress />, document.getElementById('root'));
</script>

</body>
</html>
