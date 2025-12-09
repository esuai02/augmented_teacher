<?php
// editpersona.php
// 1) DB에서 해당 contentstype, contentsid로 페르소나 레코드 조회
// 2) 결과값 있으면 React 폼을 통해 탭 구조로 수정 가능
// 3) POST(저장) 요청 시 DB 업데이트 처리

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 파라미터(GET 또는 기본값)
$cnttype = $_GET['cnttype'] ?? 0;
$cntid   = $_GET['cntid']   ?? 0;

// --------------------------
// (A) POST 요청 시 DB 업데이트
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON 형태로 전달받은 데이터를 PHP 배열로 변환
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    // personas 배열을 순회하며 DB 업데이트
    if (!empty($requestData['personas'])) {
        foreach ($requestData['personas'] as $p) {
            // Moodle DB update용 객체 생성
            $update = new stdClass();
            $update->id                = $p['dbid'];              // mdl_prsn_contents 테이블의 PK(id)
            $update->negative_prsnname = $p['negative_prsnname']; // 부정 타이틀
            $update->negative_persona  = $p['negative_persona'];  // 부정 페르소나 설명
            $update->positive_prsnname = $p['positive_prsnname']; // 긍정 타이틀
            $update->positive_persona  = $p['positive_persona'];  // 긍정 페르소나 설명

            // 실제 테이블명, 필드명이 맞는지 다시 확인 후 수정하세요.
            $DB->update_record('prsn_contents', $update);
        }
    }

    // 저장 완료 후 결과를 JSON으로 응답
    echo json_encode(["status" => "ok"]);
    exit;
}

// --------------------------
// (B) GET 요청 시 현재 데이터 조회
// --------------------------
$rows = $DB->get_records_sql("
  SELECT id, npersona,
         negative_prsnname, positive_prsnname,
         negative_persona,   positive_persona
    FROM mdl_prsn_contents
   WHERE contentstype = :ctype
     AND contentsid   = :cid
 ORDER BY npersona
", [
    'ctype' => $cnttype,
    'cid'   => $cntid
]);

// --------------------------
// (C) 조회 결과가 없는 경우 안내
// --------------------------
if (empty($rows)) {
    echo '
    <div class="no-data-container">
        <h3>아직 페르소나 정보가 없습니다</h3>
        <p>아래 링크에서 새 페르소나를 생성 후, 이 화면에서 다시 수정할 수 있습니다.</p>
        <a class="persona-link" href="https://chatgpt.com/g/g-6798cec5f9ec81918767cde8c32655ad-hagseub-pereusona-saengseonggi" target="_blank">
          학습 페르소나 생성기
        </a>
        <iframe 
            src="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/inputpersonainfo.php?cnttype='.$cnttype.'&type=contents&cntid='.$cntid.'" 
            class="persona-iframe">
        </iframe>
    </div>
    <style>
        .no-data-container {
          display: flex;
          flex-direction: column;
          align-items: center;
          margin: 2rem auto;
          padding: 1rem;
          max-width: 800px;
          border: 1px solid #ccc;
          border-radius: 8px;
          background: #fafafa;
          color: #333;
        }
        .persona-link {
          display: inline-block;
          padding: 0.75rem 1.5rem;
          background: #007bff;
          color: #fff !important;
          text-decoration: none;
          border-radius: 4px;
          margin: 1rem 0;
          transition: background 0.3s ease;
        }
        .persona-link:hover {
          background: #0056b3;
        }
        .persona-iframe {
          width: 100%;
          height: 600px;
          border: none;
          margin-top: 1rem;
          border-radius: 4px;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
    ';
    exit;
}

// (D) 배열 형태로 변환하여 React에게 넘김
$rowsArray = array_values($rows);

// 이 아래부터는 React + Babel로 폼 UI 렌더링
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>Persona Editor</title>
  <!-- React / ReactDOM (개발용) -->
  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <!-- Babel (Stand-alone) -->
  <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
  <!-- Tailwind CSS (옵션) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      margin: 0; 
      padding: 0; 
      background: lightblue;
      color: black;
      font-family: sans-serif;
    }
    .container {
      max-width: 900px;
      margin: 2rem auto;
      background: skyblue;
      border-radius: 8px;
      padding: 1rem 2rem;
    }
    label {
      display: inline-block;
      margin-bottom: 0.25rem;
      font-weight: bold;
    }
    input, textarea {
      width: 100%;
      margin-bottom: 1rem;
      padding: 0.5rem;
      color: #333;
      border-radius: 4px;
      border: none;
    }
    button {
      padding: 0.75rem 1.5rem;
      margin-right: 1rem;
      border: none;
      border-radius: 4px;
    }
    .btn-save {
      background: #4ade80; /* Tailwind green-400 */
      color: #111;
    }
    .btn-save:hover {
      background: #22c55e; /* green-500 */
    }
    /* 탭 스타일 */
    .tab-container {
      display: flex;
      gap: 8px;
      margin-bottom: 1rem;
    }
    .tab-button {
      padding: 0.5rem 0.75rem;
      border-radius: 4px;
      background: #f1f5f9; /* gray-100 */
      cursor: pointer;
      border: 1px solid #ccc;
      font-weight: bold;
    }
    .tab-button.active {
      background: #dbeafe; /* blue-100 */
      border-color: #93c5fd; /* blue-300 */
      color: #1e40af; /* blue-900 */
    }
    .tab-content {
      border: 1px solid #999;
      border-radius: 4px;
      padding: 1rem;
      background: #fff;
    }
  </style>
</head>
<body>
<div id="root"></div>

<script type="text/javascript">
  // PHP -> JS로 데이터 전달
  var serverData   = <?php echo json_encode($rowsArray, JSON_UNESCAPED_UNICODE); ?>;
  var contentstype = <?php echo (int) $cnttype; ?>;
  var contentsid   = <?php echo (int) $cntid; ?>;
</script>

<!-- React 코드 (Babel 컴파일 대상) -->
<script type="text/babel">
const { useState, useEffect } = React;

function PersonaEditorApp() {
  const [personas, setPersonas] = useState([]);
  const [saveStatus, setSaveStatus] = useState("");
  // 현재 활성화된 탭(페르소나)의 index
  const [activeTab, setActiveTab] = useState(0);

  // 컴포넌트 마운트 시, 서버로부터 받은 데이터를 state에 넣음
  useEffect(() => {
    const initData = serverData.map(item => ({
      dbid: item.id,
      npersona: item.npersona,
      negative_prsnname: item.negative_prsnname || "",
      negative_persona: item.negative_persona || "",
      positive_prsnname: item.positive_prsnname || "",
      positive_persona: item.positive_persona || ""
    }));
    setPersonas(initData);
  }, []);

  // 인풋 값 변경 처리
  const handleChange = (index, field, value) => {
    setPersonas(prev => {
      const updated = [...prev];
      updated[index] = { ...updated[index], [field]: value };
      return updated;
    });
  };

  // 저장 버튼 클릭 시 (또는 onBlur 이벤트에서 호출)
  const handleSave = async () => {
    setSaveStatus("저장 중...");

    try {
      const response = await fetch(window.location.href, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ personas })
      });
      const result = await response.json();

      if (result.status === "ok") {
        setSaveStatus("저장 성공!");
      } else {
        setSaveStatus("저장 실패: " + JSON.stringify(result));
      }
    } catch (err) {
      console.error(err);
      setSaveStatus("오류 발생: " + err.message);
    }
  };

  // 탭 버튼을 클릭하면 activeTab을 해당 index로 변경
  const handleTabClick = (index) => {
    setActiveTab(index);
  };

  return (
    <div className="container">
      <h1 className="text-2xl font-bold mb-4">페르소나 정보 수정 (탭 구성)</h1>

      {/* 탭 버튼 렌더링 영역 */}
      <div className="tab-container">
        {personas.map((p, idx) => (
          <div
            key={p.dbid}
            className={"tab-button" + (activeTab === idx ? " active" : "")}
            onClick={() => handleTabClick(idx)}
          >
            {p.negative_prsnname ? p.negative_prsnname : `페르소나 ${idx + 1}`}
          </div>
        ))}
      </div>

      {/* 활성화된 탭의 페르소나 정보만 표시 */}
      {personas.length > 0 && (
        <div className="tab-content">
          <h2 className="text-xl mb-2">
            nPersona: {personas[activeTab].npersona}
          </h2>

          {/* 음성 페르소나 */}
          <div className="mb-2">
            <label>음성 페르소나</label>
            <input
              type="text"
              value={personas[activeTab].negative_prsnname}
              onChange={e => handleChange(activeTab, "negative_prsnname", e.target.value)}
              onBlur={handleSave}
            />
          </div>
          <div className="mb-2">
            <label>설명 (음성)</label>
            <textarea
              rows="3"
              value={personas[activeTab].negative_persona}
              onChange={e => handleChange(activeTab, "negative_persona", e.target.value)}
              onBlur={handleSave}
            />
          </div>

          {/* 양성 페르소나 */}
          <div className="mb-2">
            <label>양성 페르소나</label>
            <input
              type="text"
              value={personas[activeTab].positive_prsnname}
              onChange={e => handleChange(activeTab, "positive_prsnname", e.target.value)}
              onBlur={handleSave}
            />
          </div>
          <div className="mb-2">
            <label>설명 (양성)</label>
            <textarea
              rows="3"
              value={personas[activeTab].positive_persona}
              onChange={e => handleChange(activeTab, "positive_persona", e.target.value)}
              onBlur={handleSave}
            />
          </div>
        </div>
      )}

      {/* 저장 버튼 및 상태 (수동 저장도 함께 지원) */}
      <button onClick={handleSave} className="btn-save mt-4">저장하기</button>
      {saveStatus && <span className="ml-3 text-sm">{saveStatus}</span>}
    </div>
  );
}

ReactDOM.render(<PersonaEditorApp />, document.getElementById("root"));
</script>
</body>
</html>
