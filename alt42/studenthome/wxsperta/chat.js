// AI 에이전트 대화 시스템

const ChatSystem = ({ agent, userId, onClose }) => {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [currentQuestion, setCurrentQuestion] = useState(null);
    const [missionProgress, setMissionProgress] = useState(null);
    
    useEffect(() => {
        // 에이전트 질문 가져오기
        loadAgentQuestion();
        // 오늘의 미션 진행상황 가져오기
        loadMissionProgress();
    }, [agent]);
    
    const loadAgentQuestion = async () => {
        try {
            const response = await fetch(`${phpData.apiUrl}?action=get_agent_questions&agent_id=${agent.id}&question_type=ask`);
            const result = await response.json();
            
            if (result.success && result.questions.length > 0) {
                // 랜덤하게 질문 선택
                const randomQ = result.questions[Math.floor(Math.random() * result.questions.length)];
                setCurrentQuestion(randomQ);
                
                // 첫 질문을 메시지로 추가
                setMessages([{
                    type: 'agent',
                    content: randomQ.question,
                    timestamp: new Date()
                }]);
            }
        } catch (error) {
            console.error('Failed to load questions:', error);
        }
    };
    
    const loadMissionProgress = async () => {
        try {
            const response = await fetch(`${phpData.apiUrl}?action=get_daily_mission&user_id=${userId}&date=${new Date().toISOString().split('T')[0]}`);
            const result = await response.json();
            
            if (result.success) {
                const agentMission = result.missions.find(m => m.agent_id == agent.id);
                setMissionProgress(agentMission);
            }
        } catch (error) {
            console.error('Failed to load mission:', error);
        }
    };
    
    const sendMessage = async () => {
        if (!input.trim()) return;
        
        const userMessage = {
            type: 'user',
            content: input,
            timestamp: new Date()
        };
        
        setMessages(prev => [...prev, userMessage]);
        setInput('');
        setLoading(true);
        
        try {
            // AI 응답 생성 (OpenAI API 호출)
            const aiResponse = await generateAIResponse(input);
            
            // 대화 저장
            await saveInteraction(input, aiResponse);
            
            // AI 응답 추가
            setMessages(prev => [...prev, {
                type: 'agent',
                content: aiResponse,
                timestamp: new Date()
            }]);
            
            // 미션 진행상황 업데이트
            if (missionProgress && missionProgress.status === 'pending') {
                updateMissionProgress();
            }
            
        } catch (error) {
            console.error('Failed to send message:', error);
            setMessages(prev => [...prev, {
                type: 'error',
                content: '메시지 전송에 실패했습니다.',
                timestamp: new Date()
            }]);
        } finally {
            setLoading(false);
        }
    };
    
    const generateAIResponse = async (userInput) => {
        // 에이전트의 페르소나에 맞는 응답 생성
        const systemPrompt = `당신은 "${agent.name}"이라는 AI 에이전트입니다.
        세계관: ${agent.world_view || ''}
        역할: ${agent.description}
        학생의 입력에 대해 당신의 역할과 세계관에 맞게 응답하세요.
        간결하고 동기부여가 되는 응답을 하세요.`;
        
        try {
            const response = await fetch('/studenthome/wxsperta/generate_response.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    system_prompt: systemPrompt,
                    user_input: userInput,
                    agent_name: agent.name
                })
            });
            
            const result = await response.json();
            return result.response || '응답을 생성할 수 없습니다.';
            
        } catch (error) {
            console.error('AI response error:', error);
            return '죄송합니다. 일시적인 오류가 발생했습니다.';
        }
    };
    
    const saveInteraction = async (userInput, agentResponse) => {
        await fetch(phpData.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_interaction',
                user_id: userId,
                agent_id: agent.id,
                question_id: currentQuestion?.id,
                user_input: userInput,
                agent_response: agentResponse,
                interaction_type: 'answer'
            })
        });
    };
    
    const updateMissionProgress = async () => {
        // 미션 진행상황 업데이트 로직
        if (missionProgress) {
            const newValue = Math.min(missionProgress.current_value + 1, missionProgress.target_value);
            setMissionProgress(prev => ({
                ...prev,
                current_value: newValue,
                status: newValue >= missionProgress.target_value ? 'completed' : 'in_progress'
            }));
        }
    };
    
    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };
    
    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl h-[80vh] flex flex-col">
                {/* Header */}
                <div className={`flex items-center p-4 border-b bg-gradient-to-r ${agent.color} rounded-t-2xl`}>
                    <Icon name={agent.icon} className="text-3xl text-white mr-3" />
                    <div className="flex-1">
                        <h3 className="text-xl font-bold text-white">{agent.name}</h3>
                        <p className="text-sm text-white/80">{agent.description}</p>
                    </div>
                    <button 
                        onClick={onClose}
                        className="p-2 hover:bg-white/20 rounded-lg transition-colors"
                    >
                        <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                {/* Mission Progress */}
                {missionProgress && (
                    <div className="px-4 py-2 bg-blue-50 border-b">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium">오늘의 미션: {missionProgress.mission_text}</span>
                            <div className="flex items-center gap-2">
                                <div className="w-32 bg-gray-200 rounded-full h-2">
                                    <div 
                                        className="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                        style={{ width: `${(missionProgress.current_value / missionProgress.target_value) * 100}%` }}
                                    />
                                </div>
                                <span className="text-xs text-gray-600">
                                    {missionProgress.current_value}/{missionProgress.target_value}
                                </span>
                            </div>
                        </div>
                    </div>
                )}
                
                {/* Messages */}
                <div className="flex-1 overflow-y-auto p-4 space-y-4">
                    {messages.map((msg, idx) => (
                        <div key={idx} className={`flex ${msg.type === 'user' ? 'justify-end' : 'justify-start'}`}>
                            <div className={`max-w-[70%] rounded-lg p-3 ${
                                msg.type === 'user' 
                                    ? 'bg-blue-500 text-white' 
                                    : msg.type === 'error'
                                    ? 'bg-red-100 text-red-700'
                                    : 'bg-gray-100 text-gray-800'
                            }`}>
                                <p className="text-sm">{msg.content}</p>
                                <p className="text-xs opacity-70 mt-1">
                                    {msg.timestamp.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' })}
                                </p>
                            </div>
                        </div>
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
                
                {/* Input */}
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
            </div>
        </div>
    );
};

// Export component for use in main app
window.ChatSystem = ChatSystem;