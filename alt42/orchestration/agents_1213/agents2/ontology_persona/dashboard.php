<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathking 자동개입 v1.0 - 문서 상태 대시보드</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }

        h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .metadata {
            color: #666;
            font-size: 0.9em;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .summary-card h3 {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .summary-card .number {
            font-size: 3em;
            font-weight: bold;
        }

        .summary-card .subtitle {
            font-size: 0.85em;
            opacity: 0.8;
            margin-top: 5px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.8em;
            color: #667eea;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: '';
            width: 5px;
            height: 30px;
            background: #667eea;
            border-radius: 3px;
        }

        .progress-bar-container {
            background: #f0f0f0;
            border-radius: 10px;
            height: 30px;
            margin: 10px 0;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.5s ease;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }

        .document-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            background: white;
            transition: all 0.3s ease;
        }

        .document-card:hover {
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .document-card.completed {
            border-color: #4caf50;
            background: #f1f8f4;
        }

        .document-card.needs_update {
            border-color: #ff9800;
            background: #fff8f0;
        }

        .document-card.validated {
            border-color: #2196f3;
            background: #f0f7ff;
        }

        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .document-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .status-completed {
            background: #4caf50;
            color: white;
        }

        .status-needs_update {
            background: #ff9800;
            color: white;
        }

        .status-validated {
            background: #2196f3;
            color: white;
        }

        .document-stats {
            display: flex;
            gap: 20px;
            margin: 15px 0;
        }

        .stat {
            flex: 1;
        }

        .stat-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        .quality-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .quality-title {
            font-size: 0.9em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .quality-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 0.85em;
        }

        .quality-item {
            display: flex;
            justify-content: space-between;
            padding: 5px;
        }

        .quality-label {
            color: #666;
        }

        .quality-score {
            font-weight: bold;
            color: #667eea;
        }

        .issues-list {
            margin-top: 15px;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #ff9800;
            border-radius: 5px;
        }

        .issue-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ffe0a0;
        }

        .issue-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .issue-line {
            font-weight: bold;
            color: #d84315;
        }

        .agents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .agent-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            background: white;
            transition: all 0.3s ease;
        }

        .agent-card:hover {
            box-shadow: 0 3px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-1px);
        }

        .agent-card.completed {
            border-color: #4caf50;
            background: #f1f8f4;
        }

        .agent-card.planned {
            border-color: #e0e0e0;
            background: #fafafa;
        }

        .agent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .agent-name {
            font-weight: bold;
            color: #333;
        }

        .completion-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .completion-100 {
            background: #4caf50;
            color: white;
        }

        .completion-0 {
            background: #e0e0e0;
            color: #666;
        }

        .completion-partial {
            background: #ff9800;
            color: white;
        }

        .agent-features {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            font-size: 0.85em;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .feature-yes {
            color: #4caf50;
        }

        .feature-no {
            color: #ccc;
        }

        .agent-notes {
            font-size: 0.85em;
            color: #666;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }

        .next-actions {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }

        .action-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .action-priority {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
        }

        .action-content {
            flex: 1;
        }

        .action-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .action-time {
            font-size: 0.85em;
            color: #666;
        }

        .refresh-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        .refresh-button:hover {
            transform: scale(1.1) rotate(90deg);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 1.8em;
            }

            .summary-grid,
            .documents-grid,
            .agents-grid {
                grid-template-columns: 1fr;
            }

            .quality-items {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Mathking 자동개입 v1.0</h1>
            <div class="metadata">
                문서 상태 대시보드 | 최종 업데이트: <span id="lastUpdated">-</span>
            </div>
        </header>

        <div id="content"></div>
    </div>

    <button class="refresh-button" onclick="location.reload()" title="새로고침">
        🔄
    </button>

    <script>
        // JSON 데이터를 직접 포함 (서버 없이 작동)
        const projectData = {
  "metadata": {
    "project": "Mathking 자동개입 v1.0",
    "version": "1.0.0",
    "last_updated": "2025-10-30",
    "total_agents": 22,
    "total_documents": 7
  },
  "agents": {
    "completed": 1,
    "in_progress": 0,
    "planned": 21,
    "total": 22,
    "details": [
      {
        "id": "agent_curriculum",
        "name": "커리큘럼 에이전트",
        "status": "completed",
        "completion": 100,
        "has_config": true,
        "has_tasks": true,
        "has_prompts": true,
        "notes": "완전 구현 (config/tasks/prompts)"
      },
      {
        "id": "agent_exam_prep",
        "name": "시험대비 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "Phase 2 우선순위 #1"
      },
      {
        "id": "agent_adaptive",
        "name": "맞춤학습 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "Phase 2 우선순위 #2"
      },
      {
        "id": "agent_micro_mission",
        "name": "마이크로미션 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_self_reflection",
        "name": "자기성찰 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_self_directed",
        "name": "자기주도학습 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_apprenticeship",
        "name": "도제학습 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_time_reflection",
        "name": "시간성찰 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_inquiry",
        "name": "탐구학습 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_emotion",
        "name": "감정관리 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_motivation",
        "name": "동기부여 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_personality",
        "name": "성격유형 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_learning_style",
        "name": "학습스타일 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_cognitive",
        "name": "인지능력 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_social",
        "name": "사회적학습 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_habit",
        "name": "학습습관 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_time_management",
        "name": "시간관리 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_feedback",
        "name": "피드백 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_goal_setting",
        "name": "목표설정 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "Phase 2 우선순위 #3"
      },
      {
        "id": "agent_metacognition",
        "name": "메타인지 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_creativity",
        "name": "창의성 에이전트",
        "status": "planned",
        "completion": 0,
        "has_config": false,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "빈 폴더"
      },
      {
        "id": "agent_improvement",
        "name": "개선 제안 에이전트",
        "status": "planned",
        "completion": 5,
        "has_config": true,
        "has_tasks": false,
        "has_prompts": false,
        "notes": "config.yaml만 생성 (orchestration agent22 매핑)"
      }
    ]
  },
  "documents": {
    "total": 7,
    "completed": 5,
    "needs_update": 0,
    "validated": 2,
    "details": [
      {
        "id": "01-AGENTS_TASK_SPECIFICATION",
        "name": "01-AGENTS 에이전트 명세",
        "status": "completed",
        "completion": 100,
        "consistency_score": 100,
        "quality_score": 58,
        "quality_details": {
          "구조적_완성도": 10,
          "기능적_명확성": 10,
          "기술적_적합성": 9,
          "유지보수성": 10,
          "보안배포_고려": 9,
          "문서_품질": 10
        },
        "issues": [],
        "notes": "22 agents 반영 + 구현 상태 섹션 추가"
      },
      {
        "id": "02-COLLABORATION_PATTERNS",
        "name": "02-COLLABORATION 협업 패턴",
        "status": "validated",
        "completion": 100,
        "consistency_score": 100,
        "quality_score": 57,
        "quality_details": {
          "구조적_완성도": 10,
          "기능적_명확성": 10,
          "기술적_적합성": 9,
          "유지보수성": 9,
          "보안배포_고려": 9,
          "문서_품질": 10
        },
        "issues": [],
        "notes": "agent 개수 참조 없음 (검증 완료)"
      },
      {
        "id": "03-KNOWLEDGE_BASE_ARCHITECTURE",
        "name": "03-KNOWLEDGE_BASE 지식베이스",
        "status": "validated",
        "completion": 100,
        "consistency_score": 100,
        "quality_score": 56,
        "quality_details": {
          "구조적_완성도": 10,
          "기능적_명확성": 9,
          "기술적_적합성": 9,
          "유지보수성": 10,
          "보안배포_고려": 8,
          "문서_품질": 10
        },
        "issues": [],
        "notes": "agent 개수 참조 없음 (검증 완료)"
      },
      {
        "id": "04-ONTOLOGY_SYSTEM_DESIGN",
        "name": "04-ONTOLOGY 온톨로지 설계",
        "status": "completed",
        "completion": 100,
        "consistency_score": 100,
        "quality_score": 59,
        "quality_details": {
          "구조적_완성도": 10,
          "기능적_명확성": 10,
          "기술적_적합성": 10,
          "유지보수성": 10,
          "보안배포_고려": 9,
          "문서_품질": 10
        },
        "issues": [],
        "notes": "Line 1733 수정 완료 (21 agents → 22 agents)"
      },
      {
        "id": "05-REASONING_ENGINE_SPEC",
        "name": "05-REASONING 추론 엔진",
        "status": "validated",
        "completion": 100,
        "consistency_score": 100,
        "quality_score": 57,
        "quality_details": {
          "구조적_완성도": 10,
          "기능적_명확성": 10,
          "기술적_적합성": 9,
          "유지보수성": 9,
          "보안배포_고려": 9,
          "문서_품질": 10
        },
        "issues": [],
        "notes": "agent 개수 참조 없음 (검증 완료)"
      },
      {
        "id": "06-INTEGRATION_ARCHITECTURE",
        "name": "06-INTEGRATION 통합 아키텍처",
        "status": "completed",
        "completion": 100,
        "consistency_score": 100,
        "quality_score": 58,
        "quality_details": {
          "구조적_완성도": 10,
          "기능적_명확성": 10,
          "기술적_적합성": 10,
          "유지보수성": 9,
          "보안배포_고려": 9,
          "문서_품질": 10
        },
        "issues": [],
        "notes": "Lines 99, 113, 1525 수정 완료 (21 agents → 22 agents)"
      },
      {
        "id": "07-IMPLEMENTATION_ROADMAP",
        "name": "07-ROADMAP 구현 로드맵",
        "status": "completed",
        "completion": 100,
        "consistency_score": 100,
        "quality_score": 56,
        "quality_details": {
          "구조적_완성도": 10,
          "기능적_명확성": 9,
          "기술적_적합성": 9,
          "유지보수성": 10,
          "보안배포_고려": 8,
          "문서_품질": 10
        },
        "issues": [],
        "notes": "22 agents 반영 + Phase 2 우선순위 동기화 완료"
      }
    ]
  },
  "consistency_checks": {
    "agent_count": {
      "target": 22,
      "documents_aligned": 7,
      "documents_needs_update": 0,
      "alignment_percentage": 100
    },
    "phase2_priority": {
      "expected": ["agent_exam_prep", "agent_adaptive", "agent_goal_setting"],
      "documents_aligned": 2,
      "alignment_percentage": 100
    },
    "orchestration_mapping": {
      "agent22_mapped": true,
      "registry_updated": true,
      "folder_structure_updated": true
    },
    "quality_metrics": {
      "average_score": 57.3,
      "highest_score": 59,
      "lowest_score": 56,
      "documents_excellent": 7,
      "documents_good": 0,
      "documents_needs_improvement": 0
    }
  },
  "next_actions": [
    {
      "priority": 1,
      "action": "Phase 2 에이전트 구현 시작 (agent_exam_prep, agent_adaptive, agent_goal_setting)",
      "estimated_time": "5주",
      "status": "ready"
    },
    {
      "priority": 2,
      "action": "나머지 18개 에이전트 구현 (Phase 3)",
      "estimated_time": "10주",
      "status": "planned"
    },
    {
      "priority": 3,
      "action": "통합 테스트 및 문서 최종 검증",
      "estimated_time": "2주",
      "status": "planned"
    }
  ]
};

        function renderDashboard(data) {
            const { metadata, agents, documents, consistency_checks, next_actions } = data;

            document.getElementById('lastUpdated').textContent = metadata.last_updated;

            const content = `
                <div class="summary-grid">
                    <div class="summary-card">
                        <h3>전체 에이전트</h3>
                        <div class="number">${agents.total}</div>
                        <div class="subtitle">완료: ${agents.completed} | 계획: ${agents.planned}</div>
                    </div>
                    <div class="summary-card">
                        <h3>전체 문서</h3>
                        <div class="number">${documents.total}</div>
                        <div class="subtitle">완료: ${documents.completed} | 검증: ${documents.validated}</div>
                    </div>
                    <div class="summary-card">
                        <h3>일관성 점수</h3>
                        <div class="number">${consistency_checks.agent_count.alignment_percentage.toFixed(1)}%</div>
                        <div class="subtitle">정렬된 문서: ${consistency_checks.agent_count.documents_aligned}/${documents.total}</div>
                    </div>
                    <div class="summary-card">
                        <h3>품질 평가</h3>
                        <div class="number">${consistency_checks.quality_metrics.average_score.toFixed(1)}</div>
                        <div class="subtitle">평균 점수 (60점 만점)</div>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title">📄 문서 상태</h2>
                    <div class="documents-grid">
                        ${documents.details.map(doc => renderDocumentCard(doc)).join('')}
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title">🤖 에이전트 상태</h2>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: ${(agents.completed / agents.total * 100).toFixed(1)}%">
                            ${agents.completed}/${agents.total} 완료 (${(agents.completed / agents.total * 100).toFixed(1)}%)
                        </div>
                    </div>
                    <div class="agents-grid">
                        ${agents.details.map(agent => renderAgentCard(agent)).join('')}
                    </div>
                </div>

                <div class="next-actions">
                    <h2 class="section-title">⚡ 다음 액션</h2>
                    ${next_actions.map(action => renderActionItem(action)).join('')}
                </div>
            `;

            document.getElementById('content').innerHTML = content;
        }

        function renderDocumentCard(doc) {
            const qualityHTML = doc.quality_details ? `
                <div class="quality-section">
                    <div class="quality-title">📊 품질 평가 세부사항 (${doc.quality_score}/60)</div>
                    <div class="quality-items">
                        ${Object.entries(doc.quality_details).map(([key, value]) => `
                            <div class="quality-item">
                                <span class="quality-label">${key.replace(/_/g, ' ')}</span>
                                <span class="quality-score">${value}/10</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : '';

            const issuesHTML = doc.issues && doc.issues.length > 0 ? `
                <div class="issues-list">
                    <strong>⚠️ 수정 필요 (${doc.issues.length})</strong>
                    ${doc.issues.map(issue => `
                        <div class="issue-item">
                            <div class="issue-line">Line ${issue.line}</div>
                            <div>현재: ${issue.current}</div>
                            <div>→ 수정: ${issue.expected}</div>
                        </div>
                    `).join('')}
                </div>
            ` : '';

            return `
                <div class="document-card ${doc.status}">
                    <div class="document-header">
                        <div class="document-title">${doc.name}</div>
                        <div class="status-badge status-${doc.status}">
                            ${doc.status === 'completed' ? '✅ 완료' :
                              doc.status === 'needs_update' ? '⚠️ 수정필요' :
                              '✓ 검증완료'}
                        </div>
                    </div>
                    <div class="document-stats">
                        <div class="stat">
                            <div class="stat-label">완성도</div>
                            <div class="stat-value">${doc.completion}%</div>
                        </div>
                        <div class="stat">
                            <div class="stat-label">일관성</div>
                            <div class="stat-value">${doc.consistency_score}%</div>
                        </div>
                        <div class="stat">
                            <div class="stat-label">품질</div>
                            <div class="stat-value">${doc.quality_score}/60</div>
                        </div>
                    </div>
                    ${qualityHTML}
                    ${issuesHTML}
                    <div style="margin-top: 15px; font-size: 0.9em; color: #666;">
                        ${doc.notes}
                    </div>
                </div>
            `;
        }

        function renderAgentCard(agent) {
            const completionClass = agent.completion === 100 ? 'completion-100' :
                                   agent.completion === 0 ? 'completion-0' : 'completion-partial';

            return `
                <div class="agent-card ${agent.status}">
                    <div class="agent-header">
                        <div class="agent-name">${agent.name}</div>
                        <div class="completion-badge ${completionClass}">
                            ${agent.completion}%
                        </div>
                    </div>
                    <div class="agent-features">
                        <div class="feature ${agent.has_config ? 'feature-yes' : 'feature-no'}">
                            ${agent.has_config ? '✓' : '○'} Config
                        </div>
                        <div class="feature ${agent.has_tasks ? 'feature-yes' : 'feature-no'}">
                            ${agent.has_tasks ? '✓' : '○'} Tasks
                        </div>
                        <div class="feature ${agent.has_prompts ? 'feature-yes' : 'feature-no'}">
                            ${agent.has_prompts ? '✓' : '○'} Prompts
                        </div>
                    </div>
                    <div class="agent-notes">${agent.notes}</div>
                </div>
            `;
        }

        function renderActionItem(action) {
            return `
                <div class="action-item">
                    <div class="action-priority">${action.priority}</div>
                    <div class="action-content">
                        <div class="action-title">${action.action}</div>
                        <div class="action-time">⏱️ 예상 소요: ${action.estimated_time}</div>
                    </div>
                </div>
            `;
        }

        // 페이지 로드 시 데이터 렌더링 (서버 없이 즉시 작동)
        renderDashboard(projectData);
    </script>
</body>
</html>
