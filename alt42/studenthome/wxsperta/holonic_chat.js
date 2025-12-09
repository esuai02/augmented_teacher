// Holonic WXSPERTA 채팅 시스템 (프로젝트 무한 재귀 포함)

const HolonicChatSystem = ({ agent, userId, onClose, onUpdate, onProjectView }) => {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [showProjects, setShowProjects] = useState(false);
    const [projects, setProjects] = useState([]);
    const [selectedProject, setSelectedProject] = useState(null);
    const [showProjectForm, setShowProjectForm] = useState(false);
    const [pendingUpdate, setPendingUpdate] = useState(null);
    
    useEffect(() => {
        // 초기 메시지
        setMessages([{
            id: Date.now(),
            type: 'agent',
            content: `안녕하세요! ${agent.name}입니다. 무엇을 도와드릴까요?`,
            timestamp: new Date()
        }]);
        
        // 에이전트의 프로젝트 로드
        loadAgentProjects();
    }, [agent]);
    
    const loadAgentProjects = async () => {
        try {
            const response = await fetch(`/studenthome/wxsperta/project_api.php?action=get_agent_projects&agent_id=${agent.id}`);
            const result = await response.json();
            if (result.success) {
                setProjects(result.projects || []);
            }
        } catch (error) {
            console.error('Failed to load projects:', error);
        }
    };
    
    const sendMessage = async () => {
        if (!input.trim() || loading) return;
        
        const userMessage = {
            id: Date.now(),
            type: 'user',
            content: input,
            timestamp: new Date()
        };
        
        setMessages(prev => [...prev, userMessage]);
        setInput('');
        setLoading(true);
        
        try {
            // Chat Bridge를 통해 메시지 처리
            const formData = new FormData();
            formData.append('action', 'process_message');
            formData.append('message', input);
            formData.append('user_id', userId);
            formData.append('agent_id', agent.id);
            formData.append('page_type', 'wxsperta');
            formData.append('context', JSON.stringify({
                agent_properties: agent,
                projects: projects
            }));
            
            const response = await fetch('/studenthome/wxsperta/chat_bridge.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // AI 응답 추가
                setMessages(prev => [...prev, {
                    id: Date.now(),
                    type: 'agent',
                    content: result.response,
                    timestamp: new Date()
                }]);
                
                // 인사이트 처리
                if (result.insights && result.insights.needs_update) {
                    handleInsights(result.insights);
                }
                
                // WXSPERTA 업데이트 제안 확인
                checkForUpdates(input, result.response);
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            setMessages(prev => [...prev, {
                id: Date.now(),
                type: 'error',
                content: '메시지 전송에 실패했습니다.',
                timestamp: new Date()
            }]);
        } finally {
            setLoading(false);
        }
    };
    
    const checkForUpdates = async (userInput, aiResponse) => {
        // LLM을 통해 WXSPERTA 업데이트 필요성 분석
        try {
            const response = await fetch('/studenthome/wxsperta/analyze_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    agent_id: agent.id,
                    user_input: userInput,
                    ai_response: aiResponse,
                    current_properties: agent
                })
            });
            
            const result = await response.json();
            
            if (result.suggested_updates && Object.keys(result.suggested_updates).length > 0) {
                setPendingUpdate(result.suggested_updates);
                
                // 업데이트 제안 메시지
                setMessages(prev => [...prev, {
                    id: Date.now(),
                    type: 'system',
                    content: '💡 대화 내용을 바탕으로 WXSPERTA 속성 업데이트를 제안합니다.',
                    timestamp: new Date(),
                    action: {
                        type: 'update_suggestion',
                        data: result.suggested_updates
                    }
                }]);
            }
        } catch (error) {
            console.error('Failed to analyze updates:', error);
        }
    };
    
    const handleInsights = (insights) => {
        if (insights.suggested_actions && insights.suggested_actions.includes('create_study_plan')) {
            // 새 프로젝트 생성 제안
            setMessages(prev => [...prev, {
                id: Date.now(),
                type: 'system',
                content: '📋 새로운 학습 프로젝트를 생성하시겠습니까?',
                timestamp: new Date(),
                action: {
                    type: 'create_project',
                    data: insights
                }
            }]);
        }
    };
    
    const applyUpdate = async () => {
        if (!pendingUpdate) return;
        
        try {
            const response = await fetch('/studenthome/wxsperta/approval_system.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'request_agent_update',
                    agent_id: agent.id,
                    updates: pendingUpdate,
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                setMessages(prev => [...prev, {
                    id: Date.now(),
                    type: 'system',
                    content: '✅ 업데이트 요청이 전송되었습니다. 승인 대기 중입니다.',
                    timestamp: new Date()
                }]);
                
                setPendingUpdate(null);
                
                // 부모 컴포넌트에 업데이트 알림
                if (onUpdate) {
                    onUpdate(agent.id, pendingUpdate);
                }
            }
        } catch (error) {
            console.error('Failed to apply update:', error);
        }
    };
    
    const createProject = async (parentProjectId = null) => {
        setShowProjectForm(true);
        setSelectedProject(parentProjectId);
    };
    
    const submitProject = async (projectData) => {
        try {
            const response = await fetch('/studenthome/wxsperta/project_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_project',
                    agent_id: agent.id,
                    parent_project_id: selectedProject,
                    project_data: projectData,
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                setMessages(prev => [...prev, {
                    id: Date.now(),
                    type: 'system',
                    content: `✅ 프로젝트 "${projectData.title}"가 생성되었습니다.`,
                    timestamp: new Date()
                }]);
                
                setShowProjectForm(false);
                loadAgentProjects();
            }
        } catch (error) {
            console.error('Failed to create project:', error);
        }
    };
    
    const toggleProjectView = () => {
        setShowProjects(!showProjects);
        if (!showProjects && onProjectView) {
            onProjectView(true);
        }
    };
    
    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };
    
    return (
        <div className="flex flex-col h-full">
            {/* 헤더 */}
            <div className={`flex items-center p-4 bg-gradient-to-r ${agent.color || 'from-blue-500 to-blue-600'} text-white`}>
                <span className="text-2xl mr-3">{agent.icon || '🤖'}</span>
                <div className="flex-1">
                    <h3 className="font-bold">{agent.name}</h3>
                    <p className="text-sm opacity-90">{agent.description}</p>
                </div>
                <button
                    onClick={toggleProjectView}
                    className="p-2 hover:bg-white/20 rounded-lg transition-colors mr-2"
                    title="프로젝트 보기"
                >
                    📋
                </button>
                <button
                    onClick={onClose}
                    className="p-2 hover:bg-white/20 rounded-lg transition-colors"
                >
                    ✕
                </button>
            </div>
            
            {/* 컨텐츠 영역 */}
            {showProjects ? (
                <ProjectView
                    agent={agent}
                    projects={projects}
                    onCreateProject={createProject}
                    onSelectProject={setSelectedProject}
                    onBack={() => setShowProjects(false)}
                />
            ) : showProjectForm ? (
                <ProjectForm
                    agent={agent}
                    parentProject={selectedProject}
                    onSubmit={submitProject}
                    onCancel={() => setShowProjectForm(false)}
                />
            ) : (
                <>
                    {/* 메시지 영역 */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-3">
                        {messages.map(msg => (
                            <MessageBubble
                                key={msg.id}
                                message={msg}
                                onAction={(action) => {
                                    if (action.type === 'update_suggestion') {
                                        applyUpdate();
                                    } else if (action.type === 'create_project') {
                                        createProject();
                                    }
                                }}
                            />
                        ))}
                        {loading && (
                            <div className="flex justify-start">
                                <div className="bg-gray-100 rounded-lg p-3">
                                    <div className="flex space-x-2">
                                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" />
                                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }} />
                                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }} />
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                    
                    {/* 입력 영역 */}
                    <div className="border-t p-4">
                        <div className="flex gap-2">
                            <textarea
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="메시지를 입력하세요..."
                                className="flex-1 resize-none border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                rows="2"
                                disabled={loading}
                            />
                            <button
                                onClick={sendMessage}
                                disabled={loading || !input.trim()}
                                className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                                    loading || !input.trim()
                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                        : 'bg-blue-500 text-white hover:bg-blue-600'
                                }`}
                            >
                                전송
                            </button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
};

// 메시지 버블 컴포넌트
const MessageBubble = ({ message, onAction }) => {
    const isUser = message.type === 'user';
    const isSystem = message.type === 'system';
    
    return (
        <div className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[80%] ${
                isUser ? 'bg-blue-500 text-white' : 
                isSystem ? 'bg-amber-50 border border-amber-200' :
                'bg-gray-100'
            } rounded-lg p-3`}>
                <p className="whitespace-pre-wrap">{message.content}</p>
                
                {message.action && (
                    <div className="mt-2 pt-2 border-t border-gray-200">
                        {message.action.type === 'update_suggestion' && (
                            <button
                                onClick={() => onAction(message.action)}
                                className="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600"
                            >
                                업데이트 적용
                            </button>
                        )}
                        {message.action.type === 'create_project' && (
                            <button
                                onClick={() => onAction(message.action)}
                                className="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600"
                            >
                                프로젝트 생성
                            </button>
                        )}
                    </div>
                )}
                
                <p className={`text-xs mt-1 ${
                    isUser ? 'text-blue-100' : 
                    isSystem ? 'text-amber-600' :
                    'text-gray-500'
                }`}>
                    {message.timestamp.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' })}
                </p>
            </div>
        </div>
    );
};

// 프로젝트 뷰 컴포넌트
const ProjectView = ({ agent, projects, onCreateProject, onSelectProject, onBack }) => {
    const renderProjectTree = (parentId = null, depth = 0) => {
        const childProjects = projects.filter(p => p.parent_project_id == parentId);
        
        return childProjects.map(project => (
            <div key={project.id} style={{ marginLeft: `${depth * 20}px` }}>
                <div className="p-3 mb-2 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                    <div className="flex items-center justify-between">
                        <div className="flex-1">
                            <h4 className="font-medium">{project.title}</h4>
                            <p className="text-sm text-gray-600">{project.description}</p>
                            <div className="flex gap-2 mt-1">
                                <span className={`text-xs px-2 py-1 rounded ${
                                    project.status === 'completed' ? 'bg-green-100 text-green-700' :
                                    project.status === 'active' ? 'bg-blue-100 text-blue-700' :
                                    'bg-gray-100 text-gray-700'
                                }`}>
                                    {project.status}
                                </span>
                                <span className="text-xs text-gray-500">
                                    깊이: {project.depth_level}
                                </span>
                            </div>
                        </div>
                        <button
                            onClick={() => onCreateProject(project.id)}
                            className="p-2 hover:bg-gray-100 rounded"
                            title="하위 프로젝트 생성"
                        >
                            ➕
                        </button>
                    </div>
                </div>
                {renderProjectTree(project.id, depth + 1)}
            </div>
        ));
    };
    
    return (
        <div className="flex-1 overflow-y-auto p-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold">프로젝트 트리</h3>
                <div className="flex gap-2">
                    <button
                        onClick={() => onCreateProject(null)}
                        className="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                        새 프로젝트
                    </button>
                    <button
                        onClick={onBack}
                        className="px-3 py-1 border rounded hover:bg-gray-50"
                    >
                        채팅으로
                    </button>
                </div>
            </div>
            
            <div className="space-y-2">
                {projects.length === 0 ? (
                    <p className="text-gray-500 text-center py-8">
                        아직 프로젝트가 없습니다.
                    </p>
                ) : (
                    renderProjectTree()
                )}
            </div>
        </div>
    );
};

// 프로젝트 생성 폼
const ProjectForm = ({ agent, parentProject, onSubmit, onCancel }) => {
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        wxsperta_layers: {
            worldView: '',
            context: '',
            structure: '',
            process: '',
            execution: '',
            reflection: '',
            transfer: '',
            abstraction: ''
        }
    });
    
    const handleSubmit = (e) => {
        e.preventDefault();
        if (formData.title.trim()) {
            onSubmit(formData);
        }
    };
    
    const layers = [
        { key: 'worldView', label: '세계관', placeholder: '프로젝트의 기본 철학' },
        { key: 'context', label: '문맥', placeholder: '프로젝트가 실행되는 환경' },
        { key: 'structure', label: '구조', placeholder: '프로젝트의 구조적 설계' },
        { key: 'process', label: '절차', placeholder: '단계별 프로세스' },
        { key: 'execution', label: '실행', placeholder: '구체적 실행 방법' },
        { key: 'reflection', label: '성찰', placeholder: '평가 및 개선 전략' },
        { key: 'transfer', label: '전파', placeholder: '학습 내용 공유 방법' },
        { key: 'abstraction', label: '추상화', placeholder: '핵심 가치와 목표' }
    ];
    
    return (
        <div className="flex-1 overflow-y-auto p-4">
            <h3 className="text-lg font-semibold mb-4">
                {parentProject ? '하위 프로젝트 생성' : '새 프로젝트 생성'}
            </h3>
            
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium mb-1">프로젝트 제목</label>
                    <input
                        type="text"
                        value={formData.title}
                        onChange={(e) => setFormData({...formData, title: e.target.value})}
                        className="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
                        required
                    />
                </div>
                
                <div>
                    <label className="block text-sm font-medium mb-1">설명</label>
                    <textarea
                        value={formData.description}
                        onChange={(e) => setFormData({...formData, description: e.target.value})}
                        className="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
                        rows="3"
                    />
                </div>
                
                <div className="space-y-3">
                    <h4 className="font-medium">WXSPERTA 속성</h4>
                    {layers.map(layer => (
                        <div key={layer.key}>
                            <label className="block text-sm font-medium mb-1">{layer.label}</label>
                            <input
                                type="text"
                                value={formData.wxsperta_layers[layer.key]}
                                onChange={(e) => setFormData({
                                    ...formData,
                                    wxsperta_layers: {
                                        ...formData.wxsperta_layers,
                                        [layer.key]: e.target.value
                                    }
                                })}
                                placeholder={layer.placeholder}
                                className="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm"
                            />
                        </div>
                    ))}
                </div>
                
                <div className="flex gap-2 pt-4">
                    <button
                        type="submit"
                        className="flex-1 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                        생성
                    </button>
                    <button
                        type="button"
                        onClick={onCancel}
                        className="flex-1 py-2 border rounded hover:bg-gray-50"
                    >
                        취소
                    </button>
                </div>
            </form>
        </div>
    );
};

// Export
window.HolonicChatSystem = HolonicChatSystem;