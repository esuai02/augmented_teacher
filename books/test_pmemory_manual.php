<?php
/**
 * Manual test script for P-Memory narration generation
 *
 * Usage: Run this file in browser to test the procedural memory narration workflow
 * URL: https://mathking.kr/moodle/local/augmented_teacher/books/test_pmemory_manual.php?contentsid=YOUR_CONTENT_ID
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_login();

// Get content ID from parameter
$contentsid = optional_param('contentsid', 0, PARAM_INT);

if (!$contentsid) {
    // Try to get a sample content ID from database (with audiourl but without audiourl2)
    $sample = $DB->get_record_sql("SELECT id FROM {icontent_pages} WHERE audiourl IS NOT NULL AND audiourl2 IS NULL LIMIT 1");
    if ($sample) {
        $contentsid = $sample->id;
        echo "<p>No contentsid provided. Using sample ID: $contentsid</p>";
    } else {
        die("Please provide a contentsid parameter or ensure there are contents with audiourl but without audiourl2 in the database.");
    }
}

// Get content details
$content = $DB->get_record_sql("SELECT * FROM {icontent_pages} WHERE id = ?", array($contentsid));

if (!$content) {
    die("Content not found for ID: $contentsid");
}

// Check if audiourl exists (P-Memory requires base narration)
if (empty($content->audiourl)) {
    die("P-Memory generation requires base narration (audiourl) to exist first. Content ID $contentsid doesn't have base narration.");
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P-Memory Narration Generation Test</title>
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
            border-bottom: 2px solid #FF6B6B;
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
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-exists {
            background-color: #28a745;
            color: white;
        }
        .status-missing {
            background-color: #dc3545;
            color: white;
        }
        .test-button {
            background-color: #FF6B6B;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px 0;
        }
        .test-button:hover {
            background-color: #FF5252;
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
        .audio-section {
            margin: 10px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .audio-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚩 P-Memory Narration Generation Test</h1>

        <div class="content-info">
            <h3>Content Information</h3>
            <p><strong>Content ID:</strong> <?php echo $contentsid; ?></p>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($content->title); ?></p>
            <p>
                <strong>Base Audio (audiourl):</strong>
                <?php if ($content->audiourl): ?>
                    <span class="status-badge status-exists">EXISTS</span>
                    <?php echo htmlspecialchars($content->audiourl); ?>
                <?php else: ?>
                    <span class="status-badge status-missing">MISSING</span>
                    <em>Required for P-Memory generation</em>
                <?php endif; ?>
            </p>
            <p>
                <strong>P-Memory Audio (audiourl2):</strong>
                <?php if ($content->audiourl2): ?>
                    <span class="status-badge status-exists">EXISTS</span>
                    <?php echo htmlspecialchars($content->audiourl2); ?>
                <?php else: ?>
                    <span class="status-badge status-missing">NOT GENERATED</span>
                <?php endif; ?>
            </p>
            <p><strong>Content Length:</strong> <?php echo strlen(strip_tags($content->maintext)); ?> characters</p>
        </div>

        <?php if ($content->audiourl && !$content->audiourl2): ?>
        <button class="test-button" onclick="testPMemoryGeneration()">
            🚩 Generate P-Memory Narration & TTS
        </button>
        <?php elseif (!$content->audiourl): ?>
        <div style="color: #dc3545; padding: 15px; background-color: #f8d7da; border-radius: 5px;">
            ⚠️ Cannot generate P-Memory narration because base narration (audiourl) is missing.
            Please generate the base narration first.
        </div>
        <?php else: ?>
        <div style="color: #28a745; padding: 15px; background-color: #d4edda; border-radius: 5px;">
            ✅ P-Memory narration already exists for this content.
        </div>
        <?php endif; ?>

        <div id="result" class="result"></div>

        <div id="audioPlayer" class="audio-player">
            <?php if ($content->audiourl): ?>
            <div class="audio-section">
                <div class="audio-label">🎧 Base Narration:</div>
                <audio controls style="width: 100%;">
                    <source src="<?php echo $content->audiourl; ?>" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
            </div>
            <?php endif; ?>

            <div class="audio-section" id="pmemoryAudioSection" style="display: none;">
                <div class="audio-label">🚩 P-Memory Narration:</div>
                <audio controls id="pmemoryAudioControl" style="width: 100%;">
                    <source id="pmemoryAudioSource" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
            </div>
        </div>
    </div>

    <script>
    function testPMemoryGeneration() {
        const button = document.querySelector('.test-button');
        const resultDiv = document.getElementById('result');
        const pmemorySection = document.getElementById('pmemoryAudioSection');

        // Disable button and show loading
        button.disabled = true;
        button.textContent = 'Generating P-Memory...';
        resultDiv.style.display = 'none';

        // Show loading dialog
        Swal.fire({
            title: '절차기억 생성 중',
            html: 'AI가 절차기억을 형성하고 TTS 음성을 생성하고 있습니다...<br>30-60초 정도 소요됩니다.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Make AJAX request
        $.ajax({
            url: 'pmemory_generator.php',
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
                button.textContent = '🚩 Generate P-Memory Narration & TTS';

                resultDiv.className = 'result success';
                resultDiv.style.display = 'block';

                if (response.success) {
                    let html = '<h3>✅ Success!</h3>';
                    html += '<p><strong>Message:</strong> ' + response.message + '</p>';

                    if (response.data) {
                        if (response.data.audio_url) {
                            html += '<p><strong>Audio URL:</strong> <a href="' + response.data.audio_url + '" target="_blank">' + response.data.audio_url + '</a></p>';

                            // Show P-Memory audio player
                            document.getElementById('audioPlayer').style.display = 'block';
                            pmemorySection.style.display = 'block';
                            document.getElementById('pmemoryAudioSource').src = response.data.audio_url;
                            document.getElementById('pmemoryAudioControl').load();
                        }

                        if (response.data.narration_length) {
                            html += '<p><strong>Narration Length:</strong> ' + response.data.narration_length + ' characters</p>';
                        }
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
                button.textContent = '🚩 Generate P-Memory Narration & TTS';

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

                console.error('P-Memory generation error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    responseText: xhr.responseText
                });
            }
        });
    }

    // Show existing audio player if audio exists
    <?php if ($content->audiourl || $content->audiourl2): ?>
    document.getElementById('audioPlayer').style.display = 'block';
    <?php if ($content->audiourl2): ?>
    document.getElementById('pmemoryAudioSection').style.display = 'block';
    document.getElementById('pmemoryAudioSource').src = '<?php echo $content->audiourl2; ?>';
    document.getElementById('pmemoryAudioControl').load();
    <?php endif; ?>
    <?php endif; ?>
    </script>
</body>
</html>