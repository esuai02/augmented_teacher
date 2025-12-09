<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
$studentid = $_GET["userid"];

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;
?>

import React, { useState, useEffect } from 'react';
import { Send, Phone, Video, MoreVertical, Paperclip, Smile, Mic, Search, Bell, Settings, Circle, Check, CheckCheck, MessageSquare, Grid, BarChart3, Users, BookOpen, Brain, TrendingUp, PieChart, Activity, FileText, Calendar, Target, Award, AlertTriangle, Zap, Sparkles, UserCheck, GraduationCap, Map, Radio, MessageCircle, Shield, Heart, Gauge, Focus, HelpCircle } from 'lucide-react';

const MathTeacherAISystem = () => {
  const [messages, setMessages] = useState({});
  const [inputMessage, setInputMessage] = useState('');
  const [activeAgent, setActiveAgent] = useState('attendance');
  const [isTyping, setIsTyping] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [viewMode, setViewMode] = useState('chat'); // 'chat' or 'menu'
  const [selectedMenu, setSelectedMenu] = useState(null);

  const diagnosticAgents = [
    {
      id: 'attendance',
      name: '출결관리',
      status: 'online',
      avatar: <UserCheck className="w-6 h-6" />,
      role: '실시간 출석 및 참여 추적',
      lastMessage: '오늘 출석률 96.8% - 김민수 학생 지각',
      lastTime: '방금',
      unread: 1,
      color: 'from-green-500 to-emerald-500',
      menus: [
        { id: 'realtime-check', icon: Radio, title: '실시간 출결', description: '현재 수업 참여 상태 모니터링', color: 'from-green-500 to-emerald-500' },
        { id: 'pattern-analysis', icon: Activity, title: '출결 패턴 분석', description: '장기 결석/지각 패턴 감지', color: 'from-red-500 to-orange-500' },
        { id: 'auto-notify', icon: Bell, title: '자동 알림', description: '학부모/학생 자동 알림 시스템', color: 'from-blue-500 to-cyan-500' },
        { id: 'attendance-report', icon: FileText, title: '출결 리포트', description: '월별/학기별 출결 통계', color: 'from-purple-500 to-pink-500' }
      ]
    },
    {
      id: 'learning',
      name: '학습진단',
      status: 'online',
      avatar: <GraduationCap className="w-6 h-6" />,
      role: '개인별 학습 수준 및 성취도 분석',
      lastMessage: '이차방정식 단원 평균 이해도 72%',
      lastTime: '2분 전',
      unread: 0,
      color: 'from-blue-500 to-indigo-500',
      menus: [
        { id: 'level-test', icon: Target, title: '수준 진단', description: '실시간 학습 수준 평가', color: 'from-blue-500 to-indigo-500' },
        { id: 'weakness-detect', icon: AlertTriangle, title: '취약점 분석', description: '개념별 이해도 상세 분석', color: 'from-red-500 to-pink-500' },
        { id: 'progress-track', icon: TrendingUp, title: '성장 추적', description: '시간대별 학습 성장 곡선', color: 'from-green-500 to-teal-500' },
        { id: 'peer-compare', icon: Users, title: '또래 비교', description: '동급생 대비 위치 파악', color: 'from-purple-500 to-violet-500' }
      ]
    },
    {
      id: 'curriculum',
      name: '커리큘럼 진단',
      status: 'online',
      avatar: <Map className="w-6 h-6" />,
      role: '교육과정 적합성 및 진도 분석',
      lastMessage: '현재 진도율 78% - 예정보다 3일 빠름',
      lastTime: '10분 전',
      unread: 2,
      color: 'from-purple-500 to-violet-500',
      menus: [
        { id: 'pace-check', icon: Gauge, title: '진도 체크', description: '교육과정 대비 현재 진도', color: 'from-purple-500 to-violet-500' },
        { id: 'content-fit', icon: Target, title: '난이도 적합성', description: '학급 수준별 커리큘럼 조정', color: 'from-orange-500 to-red-500' },
        { id: 'curriculum-gap', icon: AlertTriangle, title: '학습 공백', description: '놓친 개념 및 선수학습 체크', color: 'from-red-500 to-rose-500' },
        { id: 'future-plan', icon: Calendar, title: '진도 계획', description: '남은 학기 최적 진도 설계', color: 'from-blue-500 to-cyan-500' }
      ]
    },
    {
      id: 'activity',
      name: '현재활동 진단',
      status: 'online',
      avatar: <Radio className="w-6 h-6" />,
      role: '실시간 학습 활동 모니터링',
      lastMessage: '15명 문제풀이 중, 8명 완료',
      lastTime: '방금',
      unread: 0,
      color: 'from-orange-500 to-amber-500',
      menus: [
        { id: 'live-monitor', icon: Activity, title: '실시간 모니터', description: '현재 학생들의 활동 추적', color: 'from-orange-500 to-amber-500' },
        { id: 'engagement-level', icon: Zap, title: '참여도 측정', description: '수업 집중도 실시간 분석', color: 'from-yellow-500 to-orange-500' },
        { id: 'help-request', icon: Heart, title: '도움 요청', description: '실시간 질문 및 도움 신호', color: 'from-red-500 to-pink-500' },
        { id: 'screen-time', icon: Radio, title: '화면 시간', description: '디바이스 사용 패턴 분석', color: 'from-blue-500 to-indigo-500' }
      ]
    },
    {
      id: 'communication',
      name: '소통진단',
      status: 'busy',
      avatar: <MessageCircle className="w-6 h-6" />,
      role: '교사-학생 상호작용 분석',
      lastMessage: '이번 주 질문 빈도 30% 증가',
      lastTime: '15분 전',
      unread: 3,
      color: 'from-pink-500 to-rose-500',
      menus: [
        { id: 'question-analysis', icon: MessageSquare, title: '질문 분석', description: '학생 질문 패턴 및 빈도', color: 'from-pink-500 to-rose-500' },
        { id: 'interaction-quality', icon: Heart, title: '상호작용 품질', description: '소통의 질적 수준 평가', color: 'from-purple-500 to-pink-500' },
        { id: 'feedback-track', icon: CheckCheck, title: '피드백 추적', description: '교사 피드백 효과성 분석', color: 'from-green-500 to-emerald-500' },
        { id: 'peer-interaction', icon: Users, title: '또래 소통', description: '학생 간 협업 및 토론 분석', color: 'from-blue-500 to-cyan-500' }
      ]
    },
    {
      id: 'data-risk',
      name: '데이터위험 진단',
      status: 'online',
      avatar: <Shield className="w-6 h-6" />,
      role: '학습 데이터 기반 위험 요소 감지',
      lastMessage: '3명 학생 학습 부진 위험 감지',
      lastTime: '30분 전',
      unread: 1,
      color: 'from-red-500 to-rose-500',
      menus: [
        { id: 'dropout-risk', icon: AlertTriangle, title: '중도탈락 위험', description: '학습 포기 위험도 예측', color: 'from-red-500 to-rose-500' },
        { id: 'burnout-detect', icon: Shield, title: '번아웃 감지', description: '학습 피로도 조기 발견', color: 'from-orange-500 to-red-500' },
        { id: 'pattern-alert', icon: Activity, title: '이상 패턴', description: '비정상적 학습 패턴 감지', color: 'from-purple-500 to-pink-500' },
        { id: 'intervention-suggest', icon: Zap, title: '개입 시점', description: '적절한 교육 개입 시기 제안', color: 'from-yellow-500 to-amber-500' }
      ]
    },
    {
      id: 'counseling',
      name: '상담진단',
      status: 'online',
      avatar: <Heart className="w-6 h-6" />,
      role: '정서적 지원 필요성 분석',
      lastMessage: '박지호 학생 상담 필요 신호 감지',
      lastTime: '1시간 전',
      unread: 0,
      color: 'from-indigo-500 to-purple-500',
      menus: [
        { id: 'emotion-track', icon: Heart, title: '정서 상태', description: '학습 관련 정서 변화 추적', color: 'from-indigo-500 to-purple-500' },
        { id: 'stress-level', icon: Activity, title: '스트레스 수준', description: '학업 스트레스 지표 분석', color: 'from-red-500 to-pink-500' },
        { id: 'counseling-need', icon: Users, title: '상담 필요도', description: '개별 상담 우선순위 판단', color: 'from-green-500 to-teal-500' },
        { id: 'parent-comm', icon: MessageCircle, title: '학부모 소통', description: '가정 연계 상담 필요성', color: 'from-blue-500 to-cyan-500' }
      ]
    },
    {
      id: 'speed',
      name: '속도진단',
      status: 'online',
      avatar: <Gauge className="w-6 h-6" />,
      role: '학습 속도 및 효율성 분석',
      lastMessage: '평균 문제 해결 시간 2분 30초',
      lastTime: '5분 전',
      unread: 0,
      color: 'from-yellow-500 to-orange-500',
      menus: [
        { id: 'solve-speed', icon: Gauge, title: '문제 해결 속도', description: '유형별 풀이 시간 분석', color: 'from-yellow-500 to-orange-500' },
        { id: 'learning-pace', icon: TrendingUp, title: '학습 페이스', description: '개인별 최적 학습 속도', color: 'from-green-500 to-emerald-500' },
        { id: 'efficiency-score', icon: Zap, title: '효율성 점수', description: '시간 대비 성취도 분석', color: 'from-purple-500 to-violet-500' },
        { id: 'time-management', icon: Calendar, title: '시간 관리', description: '학습 시간 배분 최적화', color: 'from-blue-500 to-indigo-500' }
      ]
    },
    {
      id: 'focus',
      name: '몰입진단',
      status: 'online',
      avatar: <Focus className="w-6 h-6" />,
      role: '학습 몰입도 및 집중력 측정',
      lastMessage: '현재 수업 몰입도 85%',
      lastTime: '방금',
      unread: 1,
      color: 'from-teal-500 to-cyan-500',
      menus: [
        { id: 'focus-time', icon: Focus, title: '집중 시간', description: '연속 집중 가능 시간 측정', color: 'from-teal-500 to-cyan-500' },
        { id: 'distraction-factor', icon: AlertTriangle, title: '방해 요소', description: '집중력 저하 원인 분석', color: 'from-red-500 to-orange-500' },
        { id: 'flow-state', icon: Sparkles, title: '몰입 상태', description: '깊은 몰입 구간 파악', color: 'from-purple-500 to-pink-500' },
        { id: 'optimal-time', icon: Calendar, title: '최적 시간대', description: '개인별 집중력 피크 시간', color: 'from-green-500 to-emerald-500' }
      ]
    },
    {
      id: 'usage',
      name: '사용법 진단',
      status: 'online',
      avatar: <HelpCircle className="w-6 h-6" />,
      role: '시스템 활용도 및 사용 패턴',
      lastMessage: 'AI 기능 활용률 65% - 개선 여지 있음',
      lastTime: '20분 전',
      unread: 0,
      color: 'from-gray-500 to-gray-600',
      menus: [
        { id: 'feature-usage', icon: Grid, title: '기능 활용도', description: '시스템 기능별 사용 빈도', color: 'from-gray-500 to-gray-600' },
        { id: 'user-pattern', icon: Users, title: '사용 패턴', description: '교사/학생 이용 패턴 분석', color: 'from-blue-500 to-indigo-500' },
        { id: 'tips-guide', icon: HelpCircle, title: '활용 가이드', description: '맞춤형 사용 팁 제공', color: 'from-green-500 to-teal-500' },
        { id: 'efficiency-tips', icon: Zap, title: '효율화 제안', description: '더 나은 활용 방법 추천', color: 'from-purple-500 to-violet-500' }
      ]
    }
  ];

  // 초기 메시지 설정
  useEffect(() => {
    const initialMessages = {};
    diagnosticAgents.forEach(agent => {
      initialMessages[agent.id] = [
        {
          id: 1,
          text: `${agent.name} 시스템이 준비되었습니다. 무엇을 도와드릴까요?`,
          sender: 'agent',
          time: '오전 9:00',
          read: true
        }
      ];
    });
    setMessages(initialMessages);
  }, []);

  const handleSendMessage = () => {
    if (!inputMessage.trim()) return;

    const newMessage = {
      id: Date.now(),
      text: inputMessage,
      sender: 'teacher',
      time: new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' }),
      read: false
    };

    setMessages(prev => ({
      ...prev,
      [activeAgent]: [...(prev[activeAgent] || []), newMessage]
    }));
    setInputMessage('');
    setIsTyping(true);

    // AI 응답 시뮬레이션
    setTimeout(() => {
      const agent = diagnosticAgents.find(a => a.id === activeAgent);
      let responseText = '';
      
      switch(agent.id) {
        case 'attendance':
          responseText = '현재 출석률은 96.8%입니다. 김민수 학생이 10분 지각했고, 이서연 학생은 보건실에 있습니다. 전체적으로 양호한 출석 상태입니다. 📊';
          break;
        case 'learning':
          responseText = '이차방정식 단원 분석 결과, 상위 30%는 평균 92점, 중위권은 75점, 하위 30%는 58점입니다. 특히 판별식 개념에서 어려움을 겪고 있네요. 📈';
          break;
        case 'curriculum':
          responseText = '현재 진도는 계획 대비 3일 빠르게 진행 중입니다. 학생들의 이해도를 고려하면 적절한 속도입니다. 다음 주에는 복습 시간을 추가하는 것이 좋겠습니다. 📚';
          break;
        case 'activity':
          responseText = '현재 23명이 온라인 상태이며, 15명이 문제를 풀고 있습니다. 평균 집중 시간은 25분이며, 3명의 학생이 도움을 요청했습니다. 🎯';
          break;
        case 'communication':
          responseText = '이번 주 학생 질문이 지난주 대비 30% 증가했습니다. 특히 수업 후 개별 질문이 활발해졌네요. 긍정적인 신호입니다! 💬';
          break;
        case 'data-risk':
          responseText = '주의가 필요한 학생 3명을 감지했습니다. 최근 2주간 과제 미제출, 시험 점수 하락 패턴을 보이고 있어 조기 개입이 필요합니다. ⚠️';
          break;
        case 'counseling':
          responseText = '박지호 학생의 최근 학습 패턴과 참여도 변화를 볼 때, 개별 상담이 필요해 보입니다. 학업 스트레스 지수가 높게 나타나고 있어요. 💙';
          break;
        case 'speed':
          responseText = '학급 평균 문제 해결 시간은 2분 30초입니다. 상위권은 1분 45초, 하위권은 4분 이상 소요됩니다. 시간 단축을 위한 연습이 필요합니다. ⏱️';
          break;
        case 'focus':
          responseText = '현재 수업의 전체 몰입도는 85%로 양호합니다. 오전 10-11시가 가장 높은 집중도를 보이며, 점심 직후가 가장 낮습니다. 🎯';
          break;
        case 'usage':
          responseText = 'AI 시스템 활용률이 65%입니다. 특히 "맞춤 학습 계획" 기능을 더 활용하시면 학생 관리가 더욱 효율적일 것 같아요! 💡';
          break;
        default:
          responseText = '분석을 진행하고 있습니다...';
      }

      const aiResponse = {
        id: Date.now() + 1,
        text: responseText,
        sender: 'agent',
        time: new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' }),
        read: false
      };

      setMessages(prev => ({
        ...prev,
        [activeAgent]: [...(prev[activeAgent] || []), aiResponse]
      }));
      setIsTyping(false);
    }, 2000);
  };

  const handleMenuClick = (menu) => {
    setSelectedMenu(menu);
    
    // 메뉴 선택 시 자동으로 관련 메시지 생성
    const menuMessage = {
      id: Date.now(),
      text: `"${menu.title}" 기능을 실행합니다.`,
      sender: 'agent',
      time: new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' }),
      read: false,
      isSystem: true
    };

    setMessages(prev => ({
      ...prev,
      [activeAgent]: [...(prev[activeAgent] || []), menuMessage]
    }));

    // 잠시 후 상세 응답
    setTimeout(() => {
      let detailResponse = '';
      const agent = diagnosticAgents.find(a => a.id === activeAgent);
      
      if (agent.id === 'attendance' && menu.id === 'realtime-check') {
        detailResponse = '실시간 출결 현황: 전체 32명 중 30명 출석, 1명 지각, 1명 조퇴. 온라인 접속률 93.8%입니다.';
      } else if (agent.id === 'learning' && menu.id === 'level-test') {
        detailResponse = '학습 수준 진단 완료: 상(25%), 중상(31%), 중(28%), 중하(13%), 하(3%). 전반적으로 균형잡힌 분포를 보입니다.';
      } else {
        detailResponse = `${menu.title} 분석이 완료되었습니다. 상세 리포트를 확인하세요.`;
      }

      const detailMessage = {
        id: Date.now() + 1,
        text: detailResponse,
        sender: 'agent',
        time: new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' }),
        read: false
      };

      setMessages(prev => ({
        ...prev,
        [activeAgent]: [...(prev[activeAgent] || []), detailMessage]
      }));
    }, 1500);
  };

  const getStatusColor = (status) => {
    switch(status) {
      case 'online': return 'bg-green-500';
      case 'busy': return 'bg-yellow-500';
      case 'offline': return 'bg-gray-400';
      default: return 'bg-gray-400';
    }
  };

  const getStatusText = (status) => {
    switch(status) {
      case 'online': return '활성';
      case 'busy': return '분석 중';
      case 'offline': return '비활성';
      default: return '비활성';
    }
  };

  const currentAgent = diagnosticAgents.find(a => a.id === activeAgent);

  return (
    <div className="h-screen bg-gray-900 flex overflow-hidden">
      {/* 좌측 진단 메뉴 */}
      <div className="w-80 bg-gray-800 border-r border-gray-700 flex flex-col">
        {/* 헤더 */}
        <div className="p-4 border-b border-gray-700">
          <div className="flex items-center justify-between mb-4">
            <h1 className="text-xl font-bold text-white">AI 진단 시스템</h1>
            <div className="flex space-x-2">
              <button className="text-gray-400 hover:text-white transition-colors">
                <Bell className="w-5 h-5" />
              </button>
              <button className="text-gray-400 hover:text-white transition-colors">
                <Settings className="w-5 h-5" />
              </button>
            </div>
          </div>
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              type="text"
              placeholder="진단 항목 검색..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-gray-700 text-white rounded-lg pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
            />
          </div>
        </div>

        {/* 진단 항목 목록 */}
        <div className="flex-1 overflow-y-auto">
          {diagnosticAgents.filter(agent => 
            agent.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            agent.role.toLowerCase().includes(searchQuery.toLowerCase())
          ).map(agent => (
            <div
              key={agent.id}
              onClick={() => setActiveAgent(agent.id)}
              className={`flex items-center p-4 hover:bg-gray-700 cursor-pointer transition-all ${
                activeAgent === agent.id ? 'bg-gray-700 border-l-4 border-purple-500' : ''
              }`}
            >
              {/* 아이콘 */}
              <div className="relative mr-3">
                <div className={`w-12 h-12 rounded-full bg-gradient-to-br ${agent.color} flex items-center justify-center text-white`}>
                  {agent.avatar}
                </div>
                <div className={`absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-gray-800 ${getStatusColor(agent.status)}`} />
              </div>

              {/* 정보 */}
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between">
                  <h3 className="font-semibold text-white truncate">{agent.name}</h3>
                  <span className="text-xs text-gray-400">{agent.lastTime}</span>
                </div>
                <p className="text-xs text-gray-400 mb-1">{agent.role}</p>
                <p className="text-sm text-gray-300 truncate flex items-center">
                  {agent.typing ? (
                    <span className="text-purple-400">분석 중...</span>
                  ) : (
                    agent.lastMessage
                  )}
                </p>
              </div>

              {/* 읽지 않은 알림 */}
              {agent.unread > 0 && (
                <div className="ml-2 bg-purple-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                  {agent.unread}
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* 우측 채팅/메뉴 영역 */}
      <div className="flex-1 flex flex-col bg-gray-850">
        {/* 헤더 */}
        <div className="bg-gray-800 border-b border-gray-700 p-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <div className="relative mr-3">
                <div className={`w-10 h-10 rounded-full bg-gradient-to-br ${currentAgent?.color} flex items-center justify-center text-white`}>
                  {currentAgent?.avatar}
                </div>
                <div className={`absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-gray-800 ${
                  getStatusColor(currentAgent?.status)
                }`} />
              </div>
              <div>
                <h2 className="font-semibold text-white">
                  {currentAgent?.name}
                </h2>
                <p className="text-xs text-gray-400">
                  {getStatusText(currentAgent?.status)}
                </p>
              </div>
            </div>
            <div className="flex items-center space-x-3">
              {/* 모드 전환 버튼 */}
              <div className="flex bg-gray-700 rounded-lg p-1">
                <button
                  onClick={() => setViewMode('chat')}
                  className={`px-3 py-1 rounded flex items-center space-x-1 transition-all ${
                    viewMode === 'chat' ? 'bg-purple-600 text-white' : 'text-gray-400 hover:text-white'
                  }`}
                >
                  <MessageSquare className="w-4 h-4" />
                  <span className="text-sm">채팅</span>
                </button>
                <button
                  onClick={() => setViewMode('menu')}
                  className={`px-3 py-1 rounded flex items-center space-x-1 transition-all ${
                    viewMode === 'menu' ? 'bg-purple-600 text-white' : 'text-gray-400 hover:text-white'
                  }`}
                >
                  <Grid className="w-4 h-4" />
                  <span className="text-sm">메뉴</span>
                </button>
              </div>
              <button className="text-gray-400 hover:text-white transition-colors">
                <Phone className="w-5 h-5" />
              </button>
              <button className="text-gray-400 hover:text-white transition-colors">
                <Video className="w-5 h-5" />
              </button>
              <button className="text-gray-400 hover:text-white transition-colors">
                <MoreVertical className="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        {/* 콘텐츠 영역 */}
        {viewMode === 'chat' ? (
          <>
            {/* 메시지 영역 */}
            <div className="flex-1 overflow-y-auto p-4 bg-gray-850">
              <div className="max-w-3xl mx-auto space-y-4">
                {(messages[activeAgent] || []).map((message, index) => (
                  <div
                    key={message.id}
                    className={`flex ${message.sender === 'teacher' ? 'justify-end' : 'justify-start'} animate-fadeIn`}
                  >
                    <div className={`max-w-[70%] ${message.sender === 'teacher' ? 'order-2' : 'order-1'}`}>
                      {message.isSystem && (
                        <div className="text-center text-xs text-gray-500 mb-2">
                          시스템 기능 실행
                        </div>
                      )}
                      <div className={`rounded-2xl px-4 py-3 ${
                        message.sender === 'teacher'
                          ? 'bg-purple-600 text-white rounded-br-none'
                          : 'bg-gray-700 text-white rounded-bl-none'
                      }`}>
                        <p className="text-sm leading-relaxed">{message.text}</p>
                      </div>
                      <div className={`flex items-center mt-1 space-x-2 ${
                        message.sender === 'teacher' ? 'justify-end' : 'justify-start'
                      }`}>
                        <span className="text-xs text-gray-500">{message.time}</span>
                        {message.sender === 'teacher' && (
                          <span className="text-gray-500">
                            {message.read ? <CheckCheck className="w-3 h-3" /> : <Check className="w-3 h-3" />}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
                {isTyping && (
                  <div className="flex justify-start animate-fadeIn">
                    <div className="bg-gray-700 rounded-2xl rounded-bl-none px-4 py-3">
                      <div className="flex space-x-2">
                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
                        <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* 입력 영역 */}
            <div className="bg-gray-800 border-t border-gray-700 p-4">
              <div className="max-w-3xl mx-auto">
                <div className="flex items-center space-x-3">
                  <button className="text-gray-400 hover:text-white transition-colors">
                    <Paperclip className="w-5 h-5" />
                  </button>
                  <div className="flex-1 relative">
                    <input
                      type="text"
                      value={inputMessage}
                      onChange={(e) => setInputMessage(e.target.value)}
                      onKeyPress={(e) => e.key === 'Enter' && handleSendMessage()}
                      placeholder="진단 요청을 입력하세요..."
                      className="w-full bg-gray-700 text-white rounded-full px-4 py-3 pr-12 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                    <button className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                      <Smile className="w-5 h-5" />
                    </button>
                  </div>
                  <button className="text-gray-400 hover:text-white transition-colors">
                    <Mic className="w-5 h-5" />
                  </button>
                  <button
                    onClick={handleSendMessage}
                    className="bg-purple-600 hover:bg-purple-700 text-white rounded-full p-3 transition-all transform hover:scale-110"
                  >
                    <Send className="w-5 h-5" />
                  </button>
                </div>
              </div>
            </div>
          </>
        ) : (
          /* 메뉴 모드 */
          <div className="flex-1 overflow-y-auto p-6 bg-gray-850">
            <div className="max-w-4xl mx-auto">
              <h3 className="text-xl font-bold text-white mb-6">
                {currentAgent?.name} 세부 기능
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {currentAgent?.menus.map(menu => (
                  <button
                    key={menu.id}
                    onClick={() => handleMenuClick(menu)}
                    className={`group relative overflow-hidden rounded-xl p-6 bg-gray-800 border border-gray-700 hover:border-purple-500 transition-all duration-300 transform hover:scale-105 hover:shadow-xl ${
                      selectedMenu?.id === menu.id ? 'ring-2 ring-purple-500' : ''
                    }`}
                  >
                    <div className={`absolute inset-0 bg-gradient-to-br ${menu.color} opacity-0 group-hover:opacity-10 transition-opacity duration-300`} />
                    <div className="relative z-10">
                      <div className={`w-12 h-12 rounded-lg bg-gradient-to-br ${menu.color} flex items-center justify-center mb-4`}>
                        <menu.icon className="w-6 h-6 text-white" />
                      </div>
                      <h4 className="text-lg font-bold text-white mb-2">{menu.title}</h4>
                      <p className="text-sm text-gray-400">{menu.description}</p>
                    </div>
                    <div className="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                      <Sparkles className="w-5 h-5 text-purple-400" />
                    </div>
                  </button>
                ))}
              </div>

              {/* 진단 요약 */}
              <div className="mt-8 bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h4 className="text-lg font-bold text-white mb-3">📊 진단 요약</h4>
                <div className="grid grid-cols-3 gap-4 text-sm">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-purple-400">87%</p>
                    <p className="text-gray-400">전체 건강도</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-green-400">23</p>
                    <p className="text-gray-400">활성 학생</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-orange-400">3</p>
                    <p className="text-gray-400">주의 필요</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      <style jsx>{`
        @keyframes fadeIn {
          from {
            opacity: 0;
            transform: translateY(10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        .animate-fadeIn {
          animation: fadeIn 0.3s ease-out;
        }
        .bg-gray-850 {
          background-color: #1a1b23;
        }
      `}</style>
    </div>
  );
};

export default MathTeacherAISystem;