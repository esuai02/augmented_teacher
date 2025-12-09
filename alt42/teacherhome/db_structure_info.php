<?php
/**
 * 데이터베이스 구조 정보 표시 페이지
 * 새로운 DB 구조를 시각적으로 표시
 */

require_once __DIR__ . '/plugin_db_config.php';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB 구조 정보</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            background: white;
        }
        tr:nth-child(even) td {
            background: #f8f9fa;
        }
        .field-name {
            font-weight: 600;
            color: #2c3e50;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .data-type {
            color: #7f8c8d;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9em;
        }
        .not-null {
            color: #e74c3c;
            font-weight: 600;
        }
        .nullable {
            color: #95a5a6;
        }
        .default-value {
            color: #27ae60;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .section-header {
            background: #ecf0f1;
            padding: 8px 12px;
            font-weight: 600;
            color: #2c3e50;
            margin-top: 20px;
            border-left: 4px solid #3498db;
        }
        .plugin-section {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .internal-link { background: #fff3cd; border-left: 4px solid #ffc107; }
        .external-link { background: #d4edda; border-left: 4px solid #28a745; }
        .send-message { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .agent { background: #f8d7da; border-left: 4px solid #dc3545; }
        .common { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .system { background: #e2e3e5; border-left: 4px solid #6c757d; }
        
        .not-used {
            text-decoration: line-through;
            color: #bdc3c7;
            background: #ecf0f1;
        }
        .not-used td {
            color: #95a5a6;
        }
        .primary-key {
            background: #f39c12;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .unique-key {
            background: #9b59b6;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .index-key {
            background: #3498db;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .info-box {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .warning-box {
            background: #fcf8e3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f39c12;
        }
        .diagram {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-family: 'Consolas', 'Monaco', monospace;
            white-space: pre;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>📊 데이터베이스 구조 정보</h1>
    
    <div class="container">
        <h2>🏗️ 테이블 구조 개요</h2>
        
        <div class="info-box">
            <strong>정규화된 테이블 구조:</strong> JSON 필드를 개별 컬럼으로 분리하여 쿼리 성능과 데이터 무결성을 향상시켰습니다.
        </div>
        
        <div class="warning-box">
            <strong>⚠️ 주의:</strong> <code>agent_config_details</code> 필드는 사용하지 않습니다. 이 필드는 하위 호환성을 위해 유지되지만 NULL로 설정됩니다.
        </div>
        
        <h3>📋 mdl_alt42DB_card_plugin_settings</h3>
        
        <div class="diagram">
┌─────────────────────────────────────────────────────────────────┐
│                  mdl_alt42DB_card_plugin_settings               │
├─────────────────────────────────────────────────────────────────┤
│ PK: id                                                          │
│ UK: (user_id, category, card_title, card_index)               │
│ IDX: user_id, category, plugin_id                             │
└─────────────────────────────────────────────────────────────────┘
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="25%">필드명</th>
                    <th width="20%">데이터 타입</th>
                    <th width="10%">NULL</th>
                    <th width="15%">기본값</th>
                    <th width="30%">설명</th>
                </tr>
            </thead>
            <tbody>
                <!-- Primary Key & Indexes -->
                <tr>
                    <td colspan="5" class="section-header">🔑 키 필드</td>
                </tr>
                <tr>
                    <td><span class="field-name">id</span> <span class="primary-key">PK</span></td>
                    <td class="data-type">INT AUTO_INCREMENT</td>
                    <td class="not-null">NOT NULL</td>
                    <td class="default-value">-</td>
                    <td>고유 식별자</td>
                </tr>
                
                <!-- 기본 필드 -->
                <tr>
                    <td colspan="5" class="section-header">📌 기본 필드</td>
                </tr>
                <tr>
                    <td><span class="field-name">user_id</span> <span class="index-key">IDX</span></td>
                    <td class="data-type">INT</td>
                    <td class="not-null">NOT NULL</td>
                    <td class="default-value">-</td>
                    <td>사용자 ID</td>
                </tr>
                <tr>
                    <td><span class="field-name">category</span> <span class="index-key">IDX</span></td>
                    <td class="data-type">VARCHAR(50)</td>
                    <td class="not-null">NOT NULL</td>
                    <td class="default-value">-</td>
                    <td>카테고리 (menu_tab 등)</td>
                </tr>
                <tr>
                    <td><span class="field-name">card_title</span></td>
                    <td class="data-type">VARCHAR(255)</td>
                    <td class="not-null">NOT NULL</td>
                    <td class="default-value">-</td>
                    <td>카드 제목</td>
                </tr>
                <tr>
                    <td><span class="field-name">card_index</span></td>
                    <td class="data-type">INT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">0</td>
                    <td>카드 인덱스 (순서)</td>
                </tr>
                <tr>
                    <td><span class="field-name">plugin_id</span> <span class="index-key">IDX</span></td>
                    <td class="data-type">VARCHAR(50)</td>
                    <td class="not-null">NOT NULL</td>
                    <td class="default-value">-</td>
                    <td>플러그인 타입 ID</td>
                </tr>
                
                <!-- 공통 필드 -->
                <tr class="common">
                    <td colspan="5" class="section-header">🌐 공통 필드</td>
                </tr>
                <tr class="common">
                    <td><span class="field-name">plugin_name</span></td>
                    <td class="data-type">VARCHAR(255)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>플러그인 이름</td>
                </tr>
                <tr class="common">
                    <td><span class="field-name">card_description</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>카드 설명</td>
                </tr>
                
                <!-- internal_link 전용 -->
                <tr class="internal-link">
                    <td colspan="5" class="section-header">📑 internal_link 전용</td>
                </tr>
                <tr class="internal-link">
                    <td><span class="field-name">internal_url</span></td>
                    <td class="data-type">VARCHAR(500)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>내부 URL 경로</td>
                </tr>
                
                <!-- external_link 전용 -->
                <tr class="external-link">
                    <td colspan="5" class="section-header">🌐 external_link 전용</td>
                </tr>
                <tr class="external-link">
                    <td><span class="field-name">external_url</span></td>
                    <td class="data-type">VARCHAR(500)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>외부 URL 주소</td>
                </tr>
                <tr class="external-link">
                    <td><span class="field-name">open_new_tab</span></td>
                    <td class="data-type">TINYINT(1)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">0</td>
                    <td>새 탭에서 열기 (0: 현재 탭, 1: 새 탭)</td>
                </tr>
                
                <!-- send_message 전용 -->
                <tr class="send-message">
                    <td colspan="5" class="section-header">💬 send_message 전용</td>
                </tr>
                <tr class="send-message">
                    <td><span class="field-name">message_content</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>메시지 내용</td>
                </tr>
                <tr class="send-message">
                    <td><span class="field-name">message_type</span></td>
                    <td class="data-type">VARCHAR(50)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>메시지 타입 (info, success, warning, error)</td>
                </tr>
                
                <!-- agent 전용 -->
                <tr class="agent">
                    <td colspan="5" class="section-header">🤖 agent 전용</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_type</span></td>
                    <td class="data-type">VARCHAR(50)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>에이전트 타입 (php, javascript, api, custom)</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_code</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>실행할 PHP 코드 또는 스크립트</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_url</span></td>
                    <td class="data-type">VARCHAR(500)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>API 엔드포인트 URL</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_prompt</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>AI 에이전트 프롬프트</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_parameters</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>JSON 형식의 파라미터</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_description</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>에이전트 설명</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_config_title</span></td>
                    <td class="data-type">VARCHAR(255)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>UI 카드 제목</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_config_description</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>UI 카드 설명</td>
                </tr>
                <tr class="not-used">
                    <td><span class="field-name">agent_config_details</span> ⛔</td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>⚠️ 사용하지 않음 (하위 호환성용)</td>
                </tr>
                <tr class="agent">
                    <td><span class="field-name">agent_config_action</span></td>
                    <td class="data-type">VARCHAR(100)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>실행 액션 이름</td>
                </tr>
                
                <!-- 시스템 필드 -->
                <tr class="system">
                    <td colspan="5" class="section-header">⚙️ 시스템 필드</td>
                </tr>
                <tr class="system">
                    <td><span class="field-name">extra_config</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>추가 설정 (JSON)</td>
                </tr>
                <tr class="system">
                    <td><span class="field-name">is_active</span></td>
                    <td class="data-type">TINYINT(1)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">1</td>
                    <td>활성화 여부</td>
                </tr>
                <tr class="system">
                    <td><span class="field-name">display_order</span></td>
                    <td class="data-type">INT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">0</td>
                    <td>표시 순서</td>
                </tr>
                <tr class="system">
                    <td><span class="field-name">timecreated</span></td>
                    <td class="data-type">INT UNSIGNED</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">0</td>
                    <td>생성 시간 (Unix timestamp)</td>
                </tr>
                <tr class="system">
                    <td><span class="field-name">timemodified</span></td>
                    <td class="data-type">INT UNSIGNED</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">0</td>
                    <td>수정 시간 (Unix timestamp)</td>
                </tr>
            </tbody>
        </table>
        
        <h3>🔧 mdl_alt42DB_plugin_types</h3>
        
        <div class="diagram">
┌─────────────────────────────────────────────────────────────────┐
│                    mdl_alt42DB_plugin_types                     │
├─────────────────────────────────────────────────────────────────┤
│ PK: plugin_id                                                   │
└─────────────────────────────────────────────────────────────────┘
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="25%">필드명</th>
                    <th width="20%">데이터 타입</th>
                    <th width="10%">NULL</th>
                    <th width="15%">기본값</th>
                    <th width="30%">설명</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="field-name">plugin_id</span> <span class="primary-key">PK</span></td>
                    <td class="data-type">VARCHAR(50)</td>
                    <td class="not-null">NOT NULL</td>
                    <td class="default-value">-</td>
                    <td>플러그인 타입 ID</td>
                </tr>
                <tr>
                    <td><span class="field-name">plugin_title</span></td>
                    <td class="data-type">VARCHAR(255)</td>
                    <td class="not-null">NOT NULL</td>
                    <td class="default-value">-</td>
                    <td>플러그인 제목</td>
                </tr>
                <tr>
                    <td><span class="field-name">plugin_icon</span></td>
                    <td class="data-type">VARCHAR(10)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>아이콘 이모지</td>
                </tr>
                <tr>
                    <td><span class="field-name">plugin_description</span></td>
                    <td class="data-type">TEXT</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">NULL</td>
                    <td>플러그인 설명</td>
                </tr>
                <tr>
                    <td><span class="field-name">is_active</span></td>
                    <td class="data-type">TINYINT(1)</td>
                    <td class="nullable">NULL</td>
                    <td class="default-value">1</td>
                    <td>활성화 여부</td>
                </tr>
            </tbody>
        </table>
        
        <h2>📊 플러그인 타입별 필드 사용 매트릭스</h2>
        
        <table>
            <thead>
                <tr>
                    <th>필드명</th>
                    <th>internal_link</th>
                    <th>external_link</th>
                    <th>send_message</th>
                    <th>agent</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="field-name">plugin_name</td>
                    <td>✅</td>
                    <td>✅</td>
                    <td>✅</td>
                    <td>✅</td>
                </tr>
                <tr>
                    <td class="field-name">card_description</td>
                    <td>✅</td>
                    <td>✅</td>
                    <td>✅</td>
                    <td>✅</td>
                </tr>
                <tr>
                    <td class="field-name">internal_url</td>
                    <td>✅</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="field-name">external_url</td>
                    <td>-</td>
                    <td>✅</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="field-name">open_new_tab</td>
                    <td>✅</td>
                    <td>✅</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="field-name">message_content</td>
                    <td>-</td>
                    <td>-</td>
                    <td>✅</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="field-name">message_type</td>
                    <td>-</td>
                    <td>-</td>
                    <td>✅</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="field-name">agent_*</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>✅</td>
                </tr>
            </tbody>
        </table>
        
        <h2>🔄 마이그레이션 정보</h2>
        
        <div class="info-box">
            <h4>이전 구조 (JSON 기반):</h4>
            <pre>plugin_config: {
    "plugin_name": "...",
    "card_description": "...",
    // 플러그인별 설정들...
}</pre>
        </div>
        
        <div class="info-box">
            <h4>새 구조 (정규화):</h4>
            <pre>plugin_name: VARCHAR(255)
card_description: TEXT
internal_url: VARCHAR(500)
external_url: VARCHAR(500)
// ... 개별 필드들</pre>
        </div>
        
        <h2>🚀 사용 예시</h2>
        
        <div class="plugin-section internal-link">
            <h4>📑 Internal Link 저장:</h4>
            <pre>$config = [
    'plugin_name' => '학생 관리',
    'card_description' => '학생 정보를 관리합니다',
    'internal_url' => '/student/manage.php',
    'open_new_tab' => false
];</pre>
        </div>
        
        <div class="plugin-section agent">
            <h4>🤖 Agent 저장:</h4>
            <pre>$config = [
    'plugin_name' => 'AI 튜터',
    'card_description' => 'AI 기반 학습 도우미',
    'agent_type' => 'api',
    'agent_url' => '/api/ai-tutor',
    'agent_config' => [
        'title' => 'AI 튜터',
        'description' => '질문에 답변합니다',
        'action' => 'startTutor'
    ]
];</pre>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="test_new_db_integration.html" style="padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">테스트 페이지로 돌아가기</a>
    </div>
</body>
</html>