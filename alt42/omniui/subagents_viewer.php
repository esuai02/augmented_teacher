<?php
/**
 * Claude Code Subagents Web Viewer
 * 
 * Web interface for browsing and using the awesome-claude-code-subagents collection
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/subagents_viewer.php
 */

require_once __DIR__ . '/use_subagents.php';

$manager = new SubagentManager();

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['action']) {
        case 'list':
            echo json_encode($manager->listSubagents(), JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get':
            $category = $_GET['category'] ?? '';
            $agent = $_GET['agent'] ?? '';
            $result = $manager->getSubagent($category, $agent);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'recommend':
            $task = $_GET['task'] ?? '';
            $recommendations = $manager->getRecommendations($task);
            $results = [];
            foreach ($recommendations as $rec) {
                $agent = $manager->getSubagent($rec[0], $rec[1]);
                if ($agent) {
                    $results[] = [
                        'category' => $rec[0],
                        'agent' => $rec[1],
                        'details' => $agent
                    ];
                }
            }
            echo json_encode($results, JSON_UNESCAPED_UNICODE);
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claude Code Subagents Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .task-selector {
            margin-bottom: 25px;
        }
        
        .task-selector label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        .task-selector select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .task-selector select:hover {
            border-color: #667eea;
        }
        
        .category {
            margin-bottom: 20px;
        }
        
        .category-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .category-header:hover {
            transform: translateX(5px);
        }
        
        .agent-list {
            padding-left: 15px;
        }
        
        .agent-item {
            padding: 8px 12px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .agent-item:hover {
            background: #e9ecef;
            border-left-color: #667eea;
            transform: translateX(5px);
        }
        
        .agent-item.active {
            background: #667eea;
            color: white;
            border-left-color: #764ba2;
        }
        
        .agent-details {
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .agent-header {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .agent-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .agent-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        
        .meta-item {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            color: #555;
        }
        
        .meta-item strong {
            color: #333;
        }
        
        .agent-description {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }
        
        .agent-tools {
            margin-bottom: 25px;
        }
        
        .agent-tools h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .tools-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .tool-badge {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        
        .agent-prompt {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            font-family: 'Courier New', monospace;
            font-size: 0.95em;
            line-height: 1.6;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .copy-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 15px;
            transition: transform 0.2s;
        }
        
        .copy-button:hover {
            transform: translateY(-2px);
        }
        
        .copy-button:active {
            transform: translateY(0);
        }
        
        .recommendations {
            background: #f0f8ff;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .recommendations h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .recommendation-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .recommendation-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .loading {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Claude Code Subagents Viewer</h1>
            <p>Production-ready AI agents for specific development tasks</p>
        </div>
        
        <div class="main-content">
            <div class="sidebar">
                <div class="task-selector">
                    <label for="taskType">Quick Task Recommendations:</label>
                    <select id="taskType" onchange="getRecommendations(this.value)">
                        <option value="">Select a task type...</option>
                        <option value="api">API Development</option>
                        <option value="frontend">Frontend Development</option>
                        <option value="database">Database Management</option>
                        <option value="security">Security</option>
                        <option value="testing">Testing</option>
                        <option value="performance">Performance</option>
                        <option value="documentation">Documentation</option>
                        <option value="php">PHP Development</option>
                        <option value="moodle">Moodle Development</option>
                    </select>
                </div>
                
                <div id="categoryList">
                    <div class="loading">
                        <div class="spinner"></div>
                        Loading subagents...
                    </div>
                </div>
            </div>
            
            <div class="content">
                <div id="agentDetails">
                    <div style="text-align: center; padding: 100px 20px; color: #666;">
                        <h2 style="margin-bottom: 20px;">Welcome to Subagents Viewer</h2>
                        <p>Select a subagent from the left sidebar to view details</p>
                        <p style="margin-top: 10px;">Or choose a task type to get recommendations</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let allSubagents = {};
        
        // Load all subagents on page load
        window.onload = function() {
            loadSubagents();
        };
        
        function loadSubagents() {
            fetch('?action=list')
                .then(response => response.json())
                .then(data => {
                    allSubagents = data;
                    displayCategories(data);
                })
                .catch(error => {
                    console.error('Error loading subagents:', error);
                    document.getElementById('categoryList').innerHTML = 
                        '<div style="color: red; padding: 20px;">Error loading subagents</div>';
                });
        }
        
        function displayCategories(subagents) {
            let html = '';
            
            for (const [category, agents] of Object.entries(subagents)) {
                html += `
                    <div class="category">
                        <div class="category-header" onclick="toggleCategory('${category}')">
                            ${formatCategoryName(category)} (${agents.length})
                        </div>
                        <div class="agent-list" id="category-${category}">
                `;
                
                agents.forEach(agent => {
                    html += `
                        <div class="agent-item" onclick="loadAgent('${category}', '${agent}', this)">
                            ${agent}
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('categoryList').innerHTML = html;
        }
        
        function formatCategoryName(category) {
            return category.replace(/-/g, ' ')
                          .replace(/^\d+\s*/, '')
                          .replace(/\b\w/g, l => l.toUpperCase());
        }
        
        function toggleCategory(category) {
            const element = document.getElementById(`category-${category}`);
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        }
        
        function loadAgent(category, agentName, element) {
            // Update active state
            document.querySelectorAll('.agent-item').forEach(item => {
                item.classList.remove('active');
            });
            if (element) {
                element.classList.add('active');
            }
            
            // Show loading
            document.getElementById('agentDetails').innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Loading agent details...
                </div>
            `;
            
            // Fetch agent details
            fetch(`?action=get&category=${category}&agent=${agentName}`)
                .then(response => response.json())
                .then(agent => {
                    if (!agent) {
                        throw new Error('Agent not found');
                    }
                    displayAgentDetails(agent, category, agentName);
                })
                .catch(error => {
                    console.error('Error loading agent:', error);
                    document.getElementById('agentDetails').innerHTML = 
                        '<div style="color: red; padding: 20px;">Error loading agent details</div>';
                });
        }
        
        function displayAgentDetails(agent, category, agentName) {
            const tools = agent.tools.split(',').map(t => t.trim()).filter(t => t);
            
            let html = `
                <div class="agent-details">
                    <div class="agent-header">
                        <h2>${agent.name}</h2>
                        <div class="agent-meta">
                            <span class="meta-item"><strong>Category:</strong> ${formatCategoryName(category)}</span>
                            <span class="meta-item"><strong>File:</strong> ${agentName}.md</span>
                        </div>
                    </div>
                    
                    <div class="agent-description">
                        <strong>Description:</strong><br>
                        ${agent.description}
                    </div>
                    
                    <div class="agent-tools">
                        <h3>Required Tools:</h3>
                        <div class="tools-list">
                            ${tools.map(tool => `<span class="tool-badge">${tool}</span>`).join('')}
                        </div>
                    </div>
                    
                    <div>
                        <h3>Agent Prompt:</h3>
                        <div class="agent-prompt" id="agentPrompt">${agent.prompt}</div>
                        <button class="copy-button" onclick="copyPrompt()">📋 Copy Full Prompt</button>
                    </div>
                </div>
            `;
            
            document.getElementById('agentDetails').innerHTML = html;
        }
        
        function getRecommendations(taskType) {
            if (!taskType) return;
            
            // Show loading in content area
            document.getElementById('agentDetails').innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Getting recommendations...
                </div>
            `;
            
            fetch(`?action=recommend&task=${taskType}`)
                .then(response => response.json())
                .then(recommendations => {
                    displayRecommendations(recommendations, taskType);
                })
                .catch(error => {
                    console.error('Error getting recommendations:', error);
                    document.getElementById('agentDetails').innerHTML = 
                        '<div style="color: red; padding: 20px;">Error getting recommendations</div>';
                });
        }
        
        function displayRecommendations(recommendations, taskType) {
            if (recommendations.length === 0) {
                document.getElementById('agentDetails').innerHTML = `
                    <div style="padding: 50px; text-align: center; color: #666;">
                        <h2>No recommendations found</h2>
                        <p>No specific subagents found for "${taskType}"</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="recommendations">
                    <h3>Recommended Subagents for "${taskType}":</h3>
            `;
            
            recommendations.forEach((rec, index) => {
                const tools = rec.details.tools.split(',').map(t => t.trim()).filter(t => t).slice(0, 3);
                html += `
                    <div class="recommendation-item" onclick="loadAgent('${rec.category}', '${rec.agent}')">
                        <strong>${index + 1}. ${rec.details.name}</strong><br>
                        <small style="color: #666;">Category: ${formatCategoryName(rec.category)}</small><br>
                        <p style="margin: 10px 0;">${rec.details.description}</p>
                        <div style="margin-top: 10px;">
                            ${tools.map(tool => `<span class="tool-badge" style="font-size: 0.8em;">${tool}</span>`).join(' ')}
                            ${rec.details.tools.split(',').length > 3 ? '<span style="color: #666; font-size: 0.8em;"> ...</span>' : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('agentDetails').innerHTML = html;
        }
        
        function copyPrompt() {
            const promptText = document.getElementById('agentPrompt').textContent;
            navigator.clipboard.writeText(promptText).then(() => {
                alert('Prompt copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy prompt');
            });
        }
    </script>
</body>
</html>