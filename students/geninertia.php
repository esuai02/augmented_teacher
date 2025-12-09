<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
  


// Simulated data (would typically come from a database or API)
$hints = [
    "자, 오늘의 숨겨진 수학 문제가 기다리고 있습니다! 준비되셨나요?",
    "이번 도전은 바로, 두 개의 원과 관련된 문제입니다.",
    "머릿 속에서 원의 방정식과 교점에 대한 공식을 떠올려보세요.",
    "여유를 가지고 침착하게 기억들을 떠올려 보세요",
    "이제 곧 연관 키워드들을 공개하겠습니다. "
];

$keywords = ["원", "교점", "방정식"];
   

$timeStats = [
    ['timeRange' => '0-1분', 'count' => 10],
    ['timeRange' => '1-2분', 'count' => 25],
    ['timeRange' => '2-3분', 'count' => 40],
    ['timeRange' => '3-4분', 'count' => 30],
    ['timeRange' => '4-5분', 'count' => 15],
    ['timeRange' => '5분+', 'count' => 5]
];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Quiz Show Preview</title>
    <!-- Include Tailwind CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Include external libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include any custom CSS here -->
    <style>
		@keyframes fadeInOut {
			0%, 100% { opacity: 1; }
			50% { opacity: 0.5; }
		}

		.animate-fade {
			animation: fadeInOut 2s infinite;
		}

        /* Custom styles for animations and overrides */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-bounce {
            animation: bounce 1s infinite;
        }
        /* Background image with 50% opacity */
        body::before {
            content: "";
            background-image: url('https://mathking.kr/Contents/IMAGES/quizshow.png');
            background-size: cover;
            background-position: center;
            opacity: 0.5;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            position: fixed;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div id="quiz-container">
        <!-- The content will be dynamically injected here -->
    </div>

    <!-- Include your JavaScript logic -->
    <script>
        // Simulate the PHP variables in JavaScript
        const hints = <?php echo json_encode($hints); ?>;
        const keywords = <?php echo json_encode($keywords); ?>;
        const timeStats = <?php echo json_encode($timeStats); ?>;
        
        let currentView = 'quiz';
        let stage = 0;
        let showKeywords = false;
        let showStart = false;
        let isCountdown = false;
        let countdown = 3;
        let startTime = null;
        let endTime = null;
        let showResults = false;

        function render() {
            const container = document.getElementById('quiz-container');
            container.innerHTML = ''; // Clear previous content

            if (currentView === 'problem') {
                renderProblemView(container);
            } else {
                renderQuizView(container);
            }
        }

        function renderQuizView(container) {
            // Create elements and append to container
            const quizWrapper = document.createElement('div');
            quizWrapper.className = 'max-w-2xl mx-auto p-6 space-y-6 relative z-10';

            const contentWrapper = document.createElement('div');
            contentWrapper.className = 'bg-gray-50 bg-opacity-100 rounded-lg shadow-lg p-8 space-y-6';

            // Progress bar
			const progressBar = document.createElement('div');
			progressBar.className = 'flex justify-between mb-4';

			for (let i = 0; i < hints.length; i++) {
				const barSegment = document.createElement('div');
				barSegment.className = `h-2 w-full mx-1 rounded ${
					i <= stage ? 'bg-blue-500 animate-fade' : 'bg-gray-200'
				}`;
				progressBar.appendChild(barSegment);
			}

            contentWrapper.appendChild(progressBar);

            // Hint or countdown
            const hintWrapper = document.createElement('div');
            hintWrapper.className = 'min-h-48 flex flex-col justify-center';

            if (!isCountdown) {
                for (let i = 0; i <= Math.min(stage, hints.length - 1); i++) {
                    const hintText = document.createElement('div');
                    hintText.className = `text-lg mb-4 ${i === stage ? 'text-blue-600 font-bold' : 'text-gray-500'}`;
                    hintText.textContent = hints[i];
                    hintWrapper.appendChild(hintText);
                }
            } else {
                const countdownWrapper = document.createElement('div');
                countdownWrapper.className = 'text-center';

                const countdownNumber = document.createElement('div');
                countdownNumber.className = 'text-8xl font-bold text-red-500 animate-pulse mb-4';
                countdownNumber.textContent = countdown;

                const countdownText = document.createElement('div');
                countdownText.className = 'text-xl text-gray-600';
                countdownText.textContent = '잠시 후 문제가 공개됩니다!';

                countdownWrapper.appendChild(countdownNumber);
                countdownWrapper.appendChild(countdownText);
                hintWrapper.appendChild(countdownWrapper);
            }

            contentWrapper.appendChild(hintWrapper);

            // Keywords
            if (showKeywords && !isCountdown) {
                const keywordWrapper = document.createElement('div');
                keywordWrapper.className = 'flex justify-center space-x-4 my-4';
                
                keywords.forEach((keyword, idx) => {
                    const keywordSpan = document.createElement('span');
                    keywordSpan.className = 'inline-block bg-yellow-100 text-yellow-800 px-4 py-2 rounded-full font-bold animate-bounce shadow-md';
                    keywordSpan.style.animationDelay = `${idx * 200}ms`;
                    keywordSpan.style.animationDuration = '1s';
                    keywordSpan.textContent = keyword;
                    keywordWrapper.appendChild(keywordSpan);
                });

                contentWrapper.appendChild(keywordWrapper);
            }

            // Buttons
            const buttonWrapper = document.createElement('div');
            buttonWrapper.className = 'text-center';

            if (showStart && !isCountdown) {
                const startButton = document.createElement('button');
                startButton.className = 'bg-red-600 text-white px-8 py-4 rounded-full text-xl font-bold hover:bg-red-700 transform hover:scale-105 transition-all shadow-lg';
                startButton.textContent = '레디, 스타트!';
                startButton.onclick = startCountdown;
                buttonWrapper.appendChild(startButton);
            } else if (!showKeywords && !isCountdown) {
                const nextHintButton = document.createElement('button');
                nextHintButton.className = 'bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center mx-auto space-x-2 shadow-md';
                nextHintButton.innerHTML = `<span>다음 힌트</span>`;

                // Add Lightbulb icon
                const lightbulbIcon = document.createElement('svg');
                lightbulbIcon.className = 'w-5 h-5 ml-2';
                lightbulbIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                lightbulbIcon.setAttribute('fill', 'none');
                lightbulbIcon.setAttribute('viewBox', '0 0 24 24');
                lightbulbIcon.setAttribute('stroke', 'currentColor');
                lightbulbIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3a1 1 0 00-1 1v1.06A7.002 7.002 0 005 11v1a3 3 0 003 3h8a3 3 0 003-3v-1a7.002 7.002 0 00-5-6.94V4a1 1 0 00-1-1h-2z"/>';

                nextHintButton.appendChild(lightbulbIcon);
                nextHintButton.onclick = nextStage;

                buttonWrapper.appendChild(nextHintButton);
            }

            contentWrapper.appendChild(buttonWrapper);

            quizWrapper.appendChild(contentWrapper);
            container.appendChild(quizWrapper);
        }

        function renderProblemView(container) {
            const problemWrapper = document.createElement('div');
            problemWrapper.className = 'max-w-4xl mx-auto p-6 relative z-10';

            const contentWrapper = document.createElement('div');
            contentWrapper.className = 'bg-gray-50 bg-opacity-50 rounded-lg shadow-lg p-8';

            if (!showResults) {
                // Problem content
                const problemTitle = document.createElement('h1');
                problemTitle.className = 'text-2xl font-bold text-blue-600 mb-4';
                problemTitle.textContent = '문제';

                const problemDescription = document.createElement('div');
                problemDescription.className = 'text-gray-600 mb-6';
                problemDescription.innerHTML = '<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/%EB%AC%B8%EC%A0%9C%EC%9D%80%ED%96%89-%EB%9D%BC%EC%9D%B4%ED%8A%B8%EC%8E%88/%EC%9D%B4%EB%AF%B8%EC%A7%80/MXH1FC12 BN01L BS14 P002.jpg">';

                const completeButton = document.createElement('button');
                completeButton.className = 'bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center space-x-2';
                completeButton.innerHTML = '<span>완료</span>';

                // Add Clock icon
                const clockIcon = document.createElement('svg');
                clockIcon.className = 'w-5 h-5 ml-2';
                clockIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                clockIcon.setAttribute('fill', 'none');
                clockIcon.setAttribute('viewBox', '0 0 24 24');
                clockIcon.setAttribute('stroke', 'currentColor');
                clockIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4"/>';

                completeButton.appendChild(clockIcon);
                completeButton.onclick = handleComplete;

                contentWrapper.appendChild(problemTitle);
                contentWrapper.appendChild(problemDescription);
                contentWrapper.appendChild(completeButton);
            } else {
                // Results
                const resultsWrapper = document.createElement('div');
                resultsWrapper.className = 'space-y-8';

                // Elapsed time
                const timeWrapper = document.createElement('div');
                timeWrapper.className = 'bg-blue-50 p-6 rounded-lg';

                const timeTitle = document.createElement('h2');
                timeTitle.className = 'text-xl font-bold text-blue-600 mb-3 flex items-center';
                timeTitle.innerHTML = '<span>소요 시간</span>';

                // Add Clock icon
                const clockIcon = document.createElement('svg');
                clockIcon.className = 'w-6 h-6 mr-2';
                clockIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                clockIcon.setAttribute('fill', 'none');
                clockIcon.setAttribute('viewBox', '0 0 24 24');
                clockIcon.setAttribute('stroke', 'currentColor');
                clockIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4"/>';

                timeTitle.prepend(clockIcon);

                const timeDisplay = document.createElement('p');
                timeDisplay.className = 'text-4xl font-bold text-blue-500';
                timeDisplay.textContent = getElapsedMinutes() + '분';

                timeWrapper.appendChild(timeTitle);
                timeWrapper.appendChild(timeDisplay);

                // Chart
                const chartWrapper = document.createElement('div');
                chartWrapper.className = 'bg-white p-6 rounded-lg';

                const chartTitle = document.createElement('h2');
                chartTitle.className = 'text-xl font-bold text-gray-700 mb-4 flex items-center';
                chartTitle.innerHTML = '<span>전체 사용자 시간 통계</span>';

                // Add TrendingUp icon
                const trendingUpIcon = document.createElement('svg');
                trendingUpIcon.className = 'w-6 h-6 mr-2';
                trendingUpIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                trendingUpIcon.setAttribute('fill', 'none');
                trendingUpIcon.setAttribute('viewBox', '0 0 24 24');
                trendingUpIcon.setAttribute('stroke', 'currentColor');
                trendingUpIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-8"/>';

                chartTitle.prepend(trendingUpIcon);

                const canvas = document.createElement('canvas');
                canvas.id = 'timeChart';
                canvas.width = 700;
                canvas.height = 250;

                chartWrapper.appendChild(chartTitle);
                chartWrapper.appendChild(canvas);

                // Render the chart after appending to DOM
                setTimeout(renderChart, 0);

                // Explanation
                const explanationWrapper = document.createElement('div');
                explanationWrapper.className = 'bg-white p-6 rounded-lg';

                const explanationTitle = document.createElement('h2');
                explanationTitle.className = 'text-xl font-bold text-gray-700 mb-4 flex items-center';
                explanationTitle.innerHTML = '<span><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/%EB%AC%B8%EC%A0%9C%EC%9D%80%ED%96%89-%EB%9D%BC%EC%9D%B4%ED%8A%B8%EC%8E%88/%EC%9D%B4%EB%AF%B8%EC%A7%80/MXH1FC12 BN01L BS14 S002.jpg"></span>';

                // Add BookOpen icon
                const bookOpenIcon = document.createElement('svg');
                bookOpenIcon.className = 'w-6 h-6 mr-2';
                bookOpenIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                bookOpenIcon.setAttribute('fill', 'none');
                bookOpenIcon.setAttribute('viewBox', '0 0 24 24');
                bookOpenIcon.setAttribute('stroke', 'currentColor');
                bookOpenIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V6m0 0l8.5 5.5M12 6L3.5 11.5"/>';

                explanationTitle.prepend(bookOpenIcon);

                const explanationContent = document.createElement('div');
                explanationContent.className = 'prose max-w-none text-gray-600';
                explanationContent.innerHTML = `
                    
                `;

                explanationWrapper.appendChild(explanationTitle);
                explanationWrapper.appendChild(explanationContent);

                // Restart button
                const restartWrapper = document.createElement('div');
                restartWrapper.className = 'text-center';

                const restartButton = document.createElement('button');
                restartButton.className = 'bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors';
                restartButton.textContent = '다른 문제 풀기';
                restartButton.onclick = () => window.location.reload();

                restartWrapper.appendChild(restartButton);

                // Append all to resultsWrapper
                resultsWrapper.appendChild(timeWrapper);
                resultsWrapper.appendChild(chartWrapper);
                resultsWrapper.appendChild(explanationWrapper);
                resultsWrapper.appendChild(restartWrapper);

                contentWrapper.appendChild(resultsWrapper);
            }

            problemWrapper.appendChild(contentWrapper);
            container.appendChild(problemWrapper);
        }

        function nextStage() {
            if (stage < hints.length - 1) {
                stage++;
                render();
            } else {
                showKeywords = true;
                render();
                setTimeout(() => {
                    showStart = true;
                    render();
                }, 1500);
            }
        }

        function startCountdown() {
            isCountdown = true;
            showStart = false;
            render();

            const countdownInterval = setInterval(() => {
                if (countdown > 1) {
                    countdown--;
                    render();
                } else {
                    clearInterval(countdownInterval);
                    setTimeout(() => {
                        currentView = 'problem';
                        startTime = new Date();
                        render();
                    }, 500);
                }
            }, 1000);
        }

        function handleComplete() {
            endTime = new Date();
            showResults = true;
            render();
        }

        function getElapsedMinutes() {
            if (!startTime || !endTime) return 0;
            const elapsed = ((endTime - startTime) / 1000 / 60).toFixed(1);
            return elapsed;
        }

        function getCurrentTimeRange() {
            const minutes = getElapsedMinutes();
            if (minutes <= 1) return '0-1분';
            else if (minutes <= 2) return '1-2분';
            else if (minutes <= 3) return '2-3분';
            else if (minutes <= 4) return '3-4분';
            else if (minutes <= 5) return '4-5분';
            return '5분+';
        }

        function renderChart() {
            const ctx = document.getElementById('timeChart').getContext('2d');
            const labels = timeStats.map(stat => stat.timeRange);
            const data = timeStats.map(stat => stat.count);

            const currentTimeRange = getCurrentTimeRange();

            const backgroundColors = labels.map(label => {
                return label === currentTimeRange ? '#2563eb' : '#93c5fd';
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '인원수',
                        data: data,
                        backgroundColor: backgroundColors,
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        tooltip: { enabled: true }
                    }
                }
            });
        }

        // Initial render
        render();
    </script>
</body>
</html>
