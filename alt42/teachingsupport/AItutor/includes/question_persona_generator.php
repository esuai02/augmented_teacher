<?php
/**
 * 문항 맞춤형 페르소나 생성기
 * OpenAI Vision으로 문제/해설 이미지를 분석하여 학습 페르소나 생성
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

require_once(__DIR__ . '/db_manager.php');
require_once(__DIR__ . '/../../config.php'); // OpenAI API 키 설정

class QuestionPersonaGenerator {
    private $apiKey;
    private $model;
    private $dbManager;
    
    // 12가지 기본 페르소나
    private $basePersonas = [
        'avoider' => ['id' => 'avoider', 'name' => '막힘-회피형', 'icon' => '🚫', 'positive' => '도전형', 'positive_icon' => '💪'],
        'checker' => ['id' => 'checker', 'name' => '확인요구형', 'icon' => '❓', 'positive' => '자기확신형', 'positive_icon' => '✨'],
        'emotion_driven' => ['id' => 'emotion_driven', 'name' => '감정출렁형', 'icon' => '🎢', 'positive' => '감정안정형', 'positive_icon' => '😌'],
        'speed_miss' => ['id' => 'speed_miss', 'name' => '빠른데허술형', 'icon' => '⚡', 'positive' => '정확추구형', 'positive_icon' => '🎯'],
        'attention_hopper' => ['id' => 'attention_hopper', 'name' => '집중튐형', 'icon' => '🦘', 'positive' => '집중유지형', 'positive_icon' => '🔬'],
        'pattern_seeker' => ['id' => 'pattern_seeker', 'name' => '패턴추론형', 'icon' => '🧩', 'positive' => '구조마스터형', 'positive_icon' => '🏗️'],
        'efficiency_max' => ['id' => 'efficiency_max', 'name' => '쉬운길형', 'icon' => '🛤️', 'positive' => '효율전문가형', 'positive_icon' => '🚀'],
        'over_focus' => ['id' => 'over_focus', 'name' => '불안과몰입형', 'icon' => '😰', 'positive' => '적정몰입형', 'positive_icon' => '⚖️'],
        'concrete_learner' => ['id' => 'concrete_learner', 'name' => '추상약함형', 'icon' => '📦', 'positive' => '예시활용형', 'positive_icon' => '🎨'],
        'interactive' => ['id' => 'interactive', 'name' => '상호작용의존형', 'icon' => '🤝', 'positive' => '자기주도형', 'positive_icon' => '🌟'],
        'low_drive' => ['id' => 'low_drive', 'name' => '무기력형', 'icon' => '😔', 'positive' => '동기활성형', 'positive_icon' => '🔥'],
        'meta_high' => ['id' => 'meta_high', 'name' => '메타인지고수형', 'icon' => '🧠', 'positive' => '전략마스터형', 'positive_icon' => '👑']
    ];
    
    public function __construct() {
        // config.php에서 정의된 상수 사용
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : getenv('OPENAI_API_KEY');
        $this->model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';
        $this->dbManager = new DBManager();
    }
    
    /**
     * 캐시된 분석 결과 조회
     */
    public function getCachedAnalysis($identifier, $studentId) {
        global $DB;
        
        try {
            $record = $DB->get_record_sql(
                "SELECT * FROM {alt42_question_personas} 
                 WHERE (wboard_id = ? OR question_id = ?) AND student_id = ?
                 ORDER BY created_at DESC LIMIT 1",
                [$identifier, $identifier, $studentId]
            );
            
            if ($record) {
                return [
                    'question_analysis' => json_decode($record->question_analysis, true),
                    'persona' => json_decode($record->persona_data, true),
                    'mastery_recommendations' => json_decode($record->mastery_recommendations, true),
                    'created_at' => $record->created_at
                ];
            }
        } catch (Exception $e) {
            error_log("[QuestionPersonaGenerator] getCachedAnalysis 오류: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * 문항 분석 및 페르소나 생성
     */
    public function analyzeAndGeneratePersona($params) {
        $questionImageUrl = $params['question_image'];
        $solutionImageUrl = $params['solution_image'] ?? null;
        $questionId = $params['question_id'] ?? null;
        $wboardId = $params['wboard_id'] ?? null;
        $studentId = $params['student_id'];
        
        // 1. OpenAI Vision으로 이미지 분석
        $analysis = $this->analyzeImages($questionImageUrl, $solutionImageUrl);
        
        // 2. 분석 결과로 맞춤형 페르소나 생성
        $persona = $this->generatePersona($analysis);
        
        // 3. 장기기억 도달 시 집중숙련 추천 생성
        $masteryRecommendations = $this->generateMasteryRecommendations($analysis);
        
        // 4. DB에 저장
        $this->saveAnalysis($params, $analysis, $persona, $masteryRecommendations);
        
        return [
            'question_analysis' => $analysis,
            'persona' => $persona,
            'mastery_recommendations' => $masteryRecommendations
        ];
    }
    
    /**
     * OpenAI Vision으로 이미지 분석
     */
    private function analyzeImages($questionImageUrl, $solutionImageUrl) {
        $systemPrompt = $this->buildAnalysisSystemPrompt();
        $userContent = $this->buildImageAnalysisContent($questionImageUrl, $solutionImageUrl);
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userContent]
        ];
        
        $response = $this->callOpenAI($messages);
        
        // JSON 파싱
        $parsed = $this->parseJsonResponse($response);
        
        return $parsed;
    }
    
    /**
     * 분석 시스템 프롬프트
     */
    private function buildAnalysisSystemPrompt() {
        return <<<PROMPT
당신은 수학 교육 전문가입니다. 문제 이미지와 해설 이미지를 분석하여 학습자 맞춤형 정보를 추출합니다.

**출력 형식**: 반드시 다음 JSON 형식으로만 출력하세요:

{
  "topic": {
    "name": "단원명 (예: 유리수의 나눗셈)",
    "code": "단원코드 (예: M1-2-3)",
    "prerequisites": ["선수학습1", "선수학습2"]
  },
  "problems": [
    {
      "id": 1,
      "text": "문제 내용 텍스트",
      "type": "계산/증명/응용/추론",
      "difficulty": "easy/medium/hard",
      "key_concepts": ["핵심개념1", "핵심개념2"],
      "common_mistakes": ["자주하는 실수1", "자주하는 실수2"],
      "solving_steps": ["풀이 1단계", "풀이 2단계", "풀이 3단계"]
    }
  ],
  "cognitive_load": {
    "level": 1-5,
    "factors": ["인지부하 요인1", "인지부하 요인2"]
  },
  "recommended_personas": [
    {
      "persona_id": "speed_miss/concrete_learner/등",
      "reason": "이 페르소나를 추천하는 이유",
      "warning_signs": ["주의해야 할 신호1", "주의해야 할 신호2"],
      "guidance": "맞춤형 가이드 메시지"
    }
  ],
  "mastery_focus": [
    {
      "concept": "집중숙련 개념",
      "importance": "high/medium/low",
      "practice_content": "반복필기 내용 (손으로 직접 써볼 내용)",
      "repetition_count": 3
    }
  ]
}

**중요**:
- 이미지에서 모든 문제를 정확히 추출하세요
- 각 문제별 난이도와 인지 페르소나를 매칭하세요
- 장기기억화를 위한 반복필기 내용은 핵심 공식/절차/원리 중심으로 작성하세요
- JSON만 출력하세요
PROMPT;
    }
    
    /**
     * 이미지 분석 콘텐츠 구성
     */
    private function buildImageAnalysisContent($questionImageUrl, $solutionImageUrl) {
        $content = [
            ['type' => 'text', 'text' => "다음 수학 문제 이미지를 분석하세요.\n\n**문제 이미지:**"]
        ];
        
        // 문제 이미지
        $content[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $questionImageUrl, 'detail' => 'high']
        ];
        
        // 해설 이미지 (있는 경우)
        if ($solutionImageUrl) {
            $content[] = ['type' => 'text', 'text' => "\n**해설 이미지:**"];
            $content[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $solutionImageUrl, 'detail' => 'high']
            ];
        }
        
        $content[] = ['type' => 'text', 'text' => "\n\n위 이미지들을 분석하여 JSON 형식으로 결과를 출력하세요."];
        
        return $content;
    }
    
    /**
     * 분석 결과로 페르소나 생성
     */
    private function generatePersona($analysis) {
        $personas = [];
        
        if (!isset($analysis['problems']) || !isset($analysis['recommended_personas'])) {
            // 기본 페르소나 반환
            return $this->getDefaultPersonas($analysis);
        }
        
        foreach ($analysis['problems'] as $idx => $problem) {
            $recommendedPersona = $analysis['recommended_personas'][$idx] ?? $analysis['recommended_personas'][0] ?? null;
            
            if (!$recommendedPersona) {
                $personaId = $this->inferPersonaFromProblem($problem);
                $recommendedPersona = [
                    'persona_id' => $personaId,
                    'reason' => '문제 특성 기반 자동 매칭',
                    'warning_signs' => [],
                    'guidance' => $this->basePersonas[$personaId]['guidance'] ?? ''
                ];
            }
            
            $basePersona = $this->basePersonas[$recommendedPersona['persona_id']] ?? $this->basePersonas['checker'];
            
            $personas[] = [
                'item_id' => $problem['id'] ?? ($idx + 1),
                'item_text' => $problem['text'] ?? '',
                'topic' => $analysis['topic']['name'] ?? '',
                'difficulty' => $problem['difficulty'] ?? 'medium',
                'recommended_persona' => $recommendedPersona['persona_id'],
                'reason' => $recommendedPersona['reason'],
                'warning_signs' => $recommendedPersona['warning_signs'] ?? [],
                'context' => $recommendedPersona['guidance'] ?? '',
                'base_persona' => $basePersona,
                'key_concepts' => $problem['key_concepts'] ?? [],
                'common_mistakes' => $problem['common_mistakes'] ?? [],
                'solving_steps' => $problem['solving_steps'] ?? []
            ];
        }
        
        return $personas;
    }
    
    /**
     * 문제 특성으로 페르소나 추론
     */
    private function inferPersonaFromProblem($problem) {
        $difficulty = $problem['difficulty'] ?? 'medium';
        $type = $problem['type'] ?? '';
        
        if ($difficulty === 'easy') {
            return 'speed_miss'; // 쉬운 문제에서 실수 가능성
        } elseif ($difficulty === 'hard') {
            return 'over_focus'; // 어려운 문제에서 과몰입 가능성
        } elseif (strpos($type, '추론') !== false || strpos($type, '증명') !== false) {
            return 'pattern_seeker'; // 패턴/논리 문제
        } elseif (strpos($type, '응용') !== false) {
            return 'concrete_learner'; // 응용문제
        } else {
            return 'attention_hopper'; // 기본값
        }
    }
    
    /**
     * 기본 페르소나 생성 (분석 실패 시)
     */
    private function getDefaultPersonas($analysis) {
        return [
            [
                'item_id' => 1,
                'item_text' => $analysis['topic']['name'] ?? '문제',
                'topic' => $analysis['topic']['name'] ?? '',
                'difficulty' => 'medium',
                'recommended_persona' => 'checker',
                'reason' => '기본 페르소나',
                'context' => '차근차근 풀어보세요!',
                'base_persona' => $this->basePersonas['checker']
            ]
        ];
    }
    
    /**
     * 장기기억 도달 시 집중숙련 추천 생성
     */
    private function generateMasteryRecommendations($analysis) {
        $recommendations = [];
        
        if (isset($analysis['mastery_focus'])) {
            foreach ($analysis['mastery_focus'] as $idx => $focus) {
                $recommendations[] = [
                    'id' => $idx + 1,
                    'concept' => $focus['concept'],
                    'importance' => $focus['importance'] ?? 'medium',
                    'practice_content' => $focus['practice_content'],
                    'repetition_count' => $focus['repetition_count'] ?? 3,
                    'completed' => false
                ];
            }
        }
        
        // 3개 이상 생성 (부족하면 문제에서 추출)
        if (count($recommendations) < 3 && isset($analysis['problems'])) {
            foreach ($analysis['problems'] as $problem) {
                if (count($recommendations) >= 3) break;
                
                foreach ($problem['key_concepts'] ?? [] as $concept) {
                    if (count($recommendations) >= 3) break;
                    
                    $exists = false;
                    foreach ($recommendations as $rec) {
                        if ($rec['concept'] === $concept) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $recommendations[] = [
                            'id' => count($recommendations) + 1,
                            'concept' => $concept,
                            'importance' => 'medium',
                            'practice_content' => $this->generatePracticeContent($concept, $problem),
                            'repetition_count' => 3,
                            'completed' => false
                        ];
                    }
                }
            }
        }
        
        return array_slice($recommendations, 0, 3);
    }
    
    /**
     * 반복필기 내용 자동 생성
     */
    private function generatePracticeContent($concept, $problem) {
        // 풀이 단계가 있으면 핵심 단계 추출
        if (!empty($problem['solving_steps'])) {
            $steps = array_slice($problem['solving_steps'], 0, 3);
            return implode("\n", array_map(function($s, $i) {
                return ($i + 1) . ". " . $s;
            }, $steps, array_keys($steps)));
        }
        
        return "[ {$concept} ]\n\n핵심 원리:\n_______________\n\n적용 예시:\n_______________";
    }
    
    /**
     * 분석 결과 DB 저장
     */
    private function saveAnalysis($params, $analysis, $persona, $masteryRecommendations) {
        global $DB;
        
        try {
            $record = new stdClass();
            $record->question_id = $params['question_id'];
            $record->wboard_id = $params['wboard_id'];
            $record->student_id = $params['student_id'];
            $record->question_analysis = json_encode($analysis, JSON_UNESCAPED_UNICODE);
            $record->persona_data = json_encode($persona, JSON_UNESCAPED_UNICODE);
            $record->mastery_recommendations = json_encode($masteryRecommendations, JSON_UNESCAPED_UNICODE);
            $record->created_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');
            
            // 기존 레코드 확인
            $existing = $DB->get_record_sql(
                "SELECT id FROM {alt42_question_personas} 
                 WHERE wboard_id = ? AND student_id = ?",
                [$params['wboard_id'], $params['student_id']]
            );
            
            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('alt42_question_personas', $record);
            } else {
                $DB->insert_record('alt42_question_personas', $record);
            }
            
            error_log("[QuestionPersonaGenerator] 분석 결과 저장 완료: wboard_id=" . $params['wboard_id']);
            
        } catch (Exception $e) {
            error_log("[QuestionPersonaGenerator] saveAnalysis 오류: " . $e->getMessage());
        }
    }
    
    /**
     * 집중숙련 완료 기록
     */
    public function markMasteryCompleted($wboardId, $studentId, $recommendationId) {
        global $DB;
        
        try {
            $record = $DB->get_record_sql(
                "SELECT * FROM {alt42_question_personas} 
                 WHERE wboard_id = ? AND student_id = ?",
                [$wboardId, $studentId]
            );
            
            if ($record) {
                $recommendations = json_decode($record->mastery_recommendations, true);
                
                foreach ($recommendations as &$rec) {
                    if ($rec['id'] == $recommendationId) {
                        $rec['completed'] = true;
                        $rec['completed_at'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                
                $record->mastery_recommendations = json_encode($recommendations, JSON_UNESCAPED_UNICODE);
                $record->updated_at = date('Y-m-d H:i:s');
                $DB->update_record('alt42_question_personas', $record);
                
                return true;
            }
        } catch (Exception $e) {
            error_log("[QuestionPersonaGenerator] markMasteryCompleted 오류: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * OpenAI API 호출
     */
    private function callOpenAI($messages) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        $postData = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'response_format' => ['type' => 'json_object']
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($curlError)) {
            throw new Exception("[QuestionPersonaGenerator] OpenAI API 호출 실패: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
            throw new Exception("[QuestionPersonaGenerator] OpenAI API 오류: " . $errorMessage);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception("[QuestionPersonaGenerator] OpenAI 응답 형식 오류");
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    /**
     * JSON 응답 파싱
     */
    private function parseJsonResponse($response) {
        // 마크다운 코드 블록 제거
        $jsonText = $response;
        if (preg_match('/```json\s*(.*?)\s*```/s', $jsonText, $matches)) {
            $jsonText = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $jsonText, $matches)) {
            $jsonText = $matches[1];
        }
        
        $parsed = json_decode(trim($jsonText), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[QuestionPersonaGenerator] JSON 파싱 오류: " . json_last_error_msg());
            return $this->getDefaultAnalysis();
        }
        
        return $parsed;
    }
    
    /**
     * 기본 분석 결과 (파싱 실패 시)
     */
    private function getDefaultAnalysis() {
        return [
            'topic' => ['name' => '수학 문제', 'code' => '', 'prerequisites' => []],
            'problems' => [],
            'cognitive_load' => ['level' => 3, 'factors' => []],
            'recommended_personas' => [],
            'mastery_focus' => []
        ];
    }
    
    /**
     * 기본 페르소나 목록 반환
     */
    public function getBasePersonas() {
        return $this->basePersonas;
    }
}

