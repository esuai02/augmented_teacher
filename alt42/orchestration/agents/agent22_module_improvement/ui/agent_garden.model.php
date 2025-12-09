<?php
/**
 * Agent Garden Model
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/agent_garden.model.php
 * 
 * 에이전트 가든 데이터 모델
 */

class AgentGardenModel {
    
    /**
     * 모든 에이전트 목록 조회
     */
    public function getAllAgents() {
        return [
            [
                'id' => 'agent01',
                'name' => '온보딩',
                'description' => '학생 온보딩 및 프로필 관리',
                'icon' => '🎓',
                'status' => 'active'
            ],
            [
                'id' => 'agent02',
                'name' => '시험 일정',
                'description' => '시험 일정 관리 및 알림',
                'icon' => '📅',
                'status' => 'active'
            ],
            [
                'id' => 'agent03',
                'name' => '목표 분석',
                'description' => '학습 목표 설정 및 분석',
                'icon' => '🎯',
                'status' => 'active'
            ],
            [
                'id' => 'agent04',
                'name' => '약점 분석',
                'description' => '학습 약점 식별 및 분석',
                'icon' => '🔍',
                'status' => 'active'
            ],
            [
                'id' => 'agent05',
                'name' => '학습 감정',
                'description' => '학습 감정 상태 분석',
                'icon' => '😊',
                'status' => 'active'
            ],
            [
                'id' => 'agent06',
                'name' => '교사 피드백',
                'description' => '교사 피드백 수집 및 관리',
                'icon' => '👨‍🏫',
                'status' => 'active'
            ],
            [
                'id' => 'agent07',
                'name' => '상호작용 타겟팅',
                'description' => '맞춤형 상호작용 타겟팅',
                'icon' => '🎯',
                'status' => 'active'
            ],
            [
                'id' => 'agent08',
                'name' => '침착함',
                'description' => '학습 침착함 관리',
                'icon' => '🧘',
                'status' => 'active'
            ],
            [
                'id' => 'agent09',
                'name' => '학습 관리',
                'description' => '학습 활동 관리 및 추적',
                'icon' => '📚',
                'status' => 'active'
            ],
            [
                'id' => 'agent10',
                'name' => '개념 노트',
                'description' => '개념 학습 노트 생성',
                'icon' => '📝',
                'status' => 'active'
            ],
            [
                'id' => 'agent11',
                'name' => '문제 노트',
                'description' => '문제 풀이 노트 생성',
                'icon' => '✏️',
                'status' => 'active'
            ],
            [
                'id' => 'agent12',
                'name' => '휴식 루틴',
                'description' => '휴식 루틴 관리',
                'icon' => '☕',
                'status' => 'active'
            ],
            [
                'id' => 'agent13',
                'name' => '학습 이탈',
                'description' => '학습 이탈 위험 감지',
                'icon' => '⚠️',
                'status' => 'active'
            ],
            [
                'id' => 'agent14',
                'name' => '현재 위치',
                'description' => '학습 진도 및 현재 위치 분석',
                'icon' => '📍',
                'status' => 'active'
            ],
            [
                'id' => 'agent15',
                'name' => '문제 재정의',
                'description' => '문제 재정의 및 해석',
                'icon' => '🔄',
                'status' => 'active'
            ],
            [
                'id' => 'agent16',
                'name' => '상호작용 준비',
                'description' => '상호작용 준비 및 설계',
                'icon' => '🎬',
                'status' => 'active'
            ],
            [
                'id' => 'agent17',
                'name' => '남은 활동',
                'description' => '남은 학습 활동 관리',
                'icon' => '📋',
                'status' => 'active'
            ],
            [
                'id' => 'agent18',
                'name' => '시그니처 루틴',
                'description' => '개인별 시그니처 루틴 생성',
                'icon' => '⭐',
                'status' => 'active'
            ],
            [
                'id' => 'agent19',
                'name' => '상호작용 컨텐츠',
                'description' => '상호작용 컨텐츠 생성',
                'icon' => '💬',
                'status' => 'active'
            ],
            [
                'id' => 'agent20',
                'name' => '개입 준비',
                'description' => '학습 개입 준비 및 계획',
                'icon' => '🎯',
                'status' => 'active'
            ],
            [
                'id' => 'agent21',
                'name' => '개입 실행',
                'description' => '학습 개입 실행 및 모니터링',
                'icon' => '🚀',
                'status' => 'active'
            ],
            [
                'id' => 'agent22',
                'name' => '모듈 개선',
                'description' => '시스템 모듈 개선 분석',
                'icon' => '🔧',
                'status' => 'active'
            ]
        ];
    }
}

