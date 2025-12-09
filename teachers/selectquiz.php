<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

echo '
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.5">
    <title>수학 강좌 선택</title>
    <style>
        body {
            font-family: "Nanum Gothic", Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 40%;
            margin: left;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .column {
            flex: 1;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .elementary { border-top: 5px solid #4CAF50; }
        .middle { border-top: 5px solid #2196F3; }
        .high { border-top: 5px solid #FF9800; }
        h2 {
            color: #444;
            margin-top: 0;
            font-size: 1.5em;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 10px;
        }
        a {
            color: #333;
            text-decoration: none;
            display: block;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        a:hover {
            background-color: #f0f0f0;
        }
        .elementary a:hover { background-color: #E8F5E9; }
        .middle a:hover { background-color: #E3F2FD; }
        .high a:hover { background-color: #FFF3E0; }

        @media (max-width: 768px) {
            .grid {
                flex-direction: column;
            }
            .column {
                width: 100%;
            }
        }
    </style>
 
<body>
    <div class="container">
        <h1>수학 강좌 선택</h1>
        
        <div class="grid">
            <div class="column elementary">
                <h2>초등학교</h2>
                <ul>
                    <li><a href=" "target="_blank">초등수학 4-1 (X)</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6636"target="_blank">초등수학 4-2</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6637"target="_blank">초등수학 5-1</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6638"target="_blank">초등수학 5-2</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6639"target="_blank">초등수학 6-1</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6640"target="_blank">초등수학 6-2</a></li>
                </ul>
            </div>
            
            <div class="column middle">
                <h2>중학교</h2>
                <ul>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6623"target="_blank">중등수학 1-1</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6624"target="_blank">중등수학 1-2</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6625"target="_blank">중등수학 2-1</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6626"target="_blank">중등수학 2-2</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6627"target="_blank">중등수학 3-1</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6628"target="_blank">중등수학 3-2</a></li>
                </ul>
            </div>
            
            <div class="column high">
                <h2>고등학교</h2>
                <ul>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6629"target="_blank">고등수학 상</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=7233"target="_blank">고등수학 하</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=7234"target="_blank">수학1</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=7235"target="_blank">수학 2</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=7236"target="_blank">미분과 적분</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6633"target="_blank">확률과 통계</a></li>
                    <li><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid=6634"target="_blank">기하와 벡터</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
 ';
?>