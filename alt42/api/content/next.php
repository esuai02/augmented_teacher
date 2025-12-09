<?php
/**
 * GET /api/content/next
 * Returns the next educational content for a student
 * File: alt42/api/content/next.php
 */

// Load API configuration
require_once(__DIR__ . '/../config.php');

// Log the request
logRequest('content/next', 'GET', $_GET);

try {
    // Validate student ID
    if (!isset($_GET['studentId']) || $_GET['studentId'] === '') {
        sendError('Missing required parameter: studentId', 400, __FILE__, __LINE__);
    }

    $studentId = $_GET['studentId'];

    // TODO: Replace with actual database query and TTS generation
    // For now, using mock data for testing

    // Mock content data
    $mockContents = [
        [
            'contentId' => 'content_001',
            'text' => '안녕하세요! 오늘은 수학 문제를 풀어볼까요?',
            'ttsUrl' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3'
        ],
        [
            'contentId' => 'content_002',
            'text' => '2 더하기 3은 얼마일까요?',
            'ttsUrl' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3'
        ],
        [
            'contentId' => 'content_003',
            'text' => '정답입니다! 아주 잘했어요.',
            'ttsUrl' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3'
        ]
    ];

    // Randomly select content (in production, this would be based on student progress)
    $randomIndex = array_rand($mockContents);
    $content = $mockContents[$randomIndex];

    // Return successful response
    sendResponse([
        'contentId' => $content['contentId'],
        'text' => $content['text'],
        'ttsUrl' => $content['ttsUrl'],
        'timestamp' => date('Y-m-d H:i:s')
    ], 200);

} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500, __FILE__, __LINE__);
}
