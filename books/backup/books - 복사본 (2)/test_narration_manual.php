<?php
/**
 * Manual test script for narration generation
 *
 * Usage: Run this file in browser to test the narration generation workflow
 * URL: https://mathking.kr/moodle/local/augmented_teacher/books/test_narration_manual.php?contentsid=YOUR_CONTENT_ID
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_login();

// Get content ID from parameter
$contentsid = optional_param('contentsid', 0, PARAM_INT);

if (!$contentsid) {
    // Try to get a sample content ID from database
    $sample = $DB->get_record_sql("SELECT id FROM {icontent_pages} WHERE audiourl2 IS NULL LIMIT 1");
    if ($sample) {
        $contentsid = $sample->id;
        echo "<p>No contentsid provided. Using sample ID: $contentsid</p>";
    } else {
        die("Please provide a contentsid parameter or ensure there are contents without audiourl2 in the database.");
    }
}

// Get content details
$content = $DB->get_record_sql("SELECT * FROM {icontent_pages} WHERE id = ?", array($contentsid));

if (!$content) {
    die("Content not found for ID: $contentsid");
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Narration Generation Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .content-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .content-info h3 {
            margin-top: 0;
            color: #495057;
        }
        .content-info p {
            margin: 5px 0;
        }
        .test-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px 0;
        }
        .test-button:hover {
            background-color: #218838;
        }
        .test-button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .result.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .audio-player {
            margin-top: 20px;
            display: none;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎧 Narration Generation Test</h1>

        <div class="content-info">
            <h3>Content Information</h3>
            <p><strong>Content ID:</strong> <?php echo $contentsid; ?></p>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($content->title); ?></p>
            <p><strong>Current Audio URL:</strong> <?php echo $content->audiourl2 ? htmlspecialchars($content->audiourl2) : '<em>None</em>'; ?></p>
            <p><strong>Content Length:</strong> <?php echo strlen(strip_tags($content->maintext)); ?> characters</p>
        </div>

        <button class="test-button" onclick="testNarrationGeneration()">
            Generate Narration & TTS
        </button>

        <div id="result" class="result"></div>

        <div id="audioPlayer" class="audio-player">
            <h3>Generated Audio:</h3>
            <audio controls id="audioControl">
                <source id="audioSource" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        </div>
    </div>

    <script>
    function testNarrationGeneration() {
        const button = document.querySelector('.test-button');
        const resultDiv = document.getElementById('result');
        const audioPlayer = document.getElementById('audioPlayer');

        // Disable button and show loading
        button.disabled = true;
        button.textContent = 'Generating...';
        resultDiv.style.display = 'none';
        audioPlayer.style.display = 'none';

        // Show loading dialog
        Swal.fire({
            title: 'Generating Narration',
            html: 'Creating narration text and TTS audio...<br>This may take 30-60 seconds.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Make AJAX request
        $.ajax({
            url: 'generate_narration.php',
            type: 'POST',
            dataType: 'json',
            timeout: 120000, // 2 minutes timeout
            data: {
                contentsid: <?php echo $contentsid; ?>,
                contentstype: 1,
                generateTTS: 'true',
                audioType: 'audiourl2',
                userid: <?php echo $USER->id; ?>
            },
            success: function(response) {
                Swal.close();
                button.disabled = false;
                button.textContent = 'Generate Narration & TTS';

                resultDiv.className = 'result success';
                resultDiv.style.display = 'block';

                if (response.success) {
                    let html = '<h3>✅ Success!</h3>';
                    html += '<p><strong>Message:</strong> ' + response.message + '</p>';

                    if (response.narration) {
                        html += '<p><strong>Narration Preview:</strong></p>';
                        html += '<pre>' + response.narration + '</pre>';
                    }

                    if (response.ttsSuccess && response.audioUrl) {
                        html += '<p><strong>Audio URL:</strong> <a href="' + response.audioUrl + '" target="_blank">' + response.audioUrl + '</a></p>';

                        // Show audio player
                        audioPlayer.style.display = 'block';
                        document.getElementById('audioSource').src = response.audioUrl;
                        document.getElementById('audioControl').load();
                    } else if (response.ttsError) {
                        html += '<p><strong>TTS Error:</strong> ' + response.ttsError + '</p>';
                    }

                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '<h3>❌ Error</h3><p>' + response.message + '</p>';
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                button.disabled = false;
                button.textContent = 'Generate Narration & TTS';

                resultDiv.className = 'result error';
                resultDiv.style.display = 'block';

                let errorMsg = '<h3>❌ Request Failed</h3>';
                errorMsg += '<p><strong>Status:</strong> ' + status + '</p>';
                errorMsg += '<p><strong>Error:</strong> ' + error + '</p>';

                if (xhr.status === 0) {
                    errorMsg += '<p>Network error or timeout. Check your connection.</p>';
                } else {
                    errorMsg += '<p><strong>HTTP Status:</strong> ' + xhr.status + '</p>';
                    if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg += '<p><strong>Server Message:</strong> ' + (response.message || 'Unknown error') + '</p>';
                        } catch (e) {
                            errorMsg += '<p><strong>Response:</strong> ' + xhr.responseText.substring(0, 200) + '...</p>';
                        }
                    }
                }

                resultDiv.innerHTML = errorMsg;

                console.error('Narration generation error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    responseText: xhr.responseText
                });
            }
        });
    }
    </script>
</body>
</html>