-- =====================================================
-- Quantum Modeling 초기 데이터 삽입 스크립트
-- 
-- y=x²-ax 정삼각형 문제 데이터
-- content_id: 'default_equilateral'
-- 
-- 실행 방법: 이 SQL을 Moodle DB에서 실행
-- =====================================================

-- 사용할 content_id (필요시 변경)
SET @content_id = 'default_equilateral';

-- =====================================================
-- 1. 컨텐츠 메타데이터 삽입
-- =====================================================
INSERT INTO `mdl_at_quantum_contents` 
    (`content_id`, `contents_type`, `title`, `answer`, `stage_names`, `is_active`, `created_at`)
VALUES 
    (@content_id, 'math_problem', 
     'y=x²-ax 정삼각형 문제 - 양자 경로 분석', 
     'a=2√3',
     '["시작", "문제해석", "x절편", "꼭짓점", "접근법", "거리계산", "방정식", "최종"]',
     1, NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `answer` = VALUES(`answer`),
    `stage_names` = VALUES(`stage_names`),
    `updated_at` = NOW();

-- =====================================================
-- 2. 개념(Concepts) 데이터 삽입
-- =====================================================
INSERT INTO `mdl_at_quantum_concepts` 
    (`concept_id`, `content_id`, `name`, `icon`, `color`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('factor', @content_id, '인수분해', '🧩', '#10b981', 1, 1, NOW()),
    ('vertex', @content_id, '꼭짓점 공식', '📍', '#8b5cf6', 2, 1, NOW()),
    ('distance', @content_id, '거리 계산', '📏', '#f59e0b', 3, 1, NOW()),
    ('equilateral', @content_id, '정삼각형 성질', '△', '#06b6d4', 4, 1, NOW()),
    ('midpoint', @content_id, '중점 공식', '◐', '#ec4899', 5, 1, NOW()),
    ('complete_sq', @content_id, '완전제곱식', '²', '#3b82f6', 6, 1, NOW()),
    ('equation', @content_id, '방정식 풀이', '⚖️', '#ef4444', 7, 1, NOW()),
    ('condition', @content_id, '조건 확인', '✓', '#14b8a6', 8, 1, NOW()),
    ('graph', @content_id, '그래프 해석', '📈', '#a855f7', 9, 1, NOW()),
    ('height', @content_id, '삼각형 높이', '↕', '#f97316', 10, 1, NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `icon` = VALUES(`icon`),
    `color` = VALUES(`color`),
    `order_index` = VALUES(`order_index`),
    `updated_at` = NOW();

-- =====================================================
-- 3. 노드(Nodes) 데이터 삽입
-- =====================================================

-- Stage 0: 시작
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('start', @content_id, '문제 인식', 'start', 0, 500, 50, '이차함수, 정삼각형 조건 파악', 1, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- Stage 1: 문제 해석
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('s1_full', @content_id, '완전 이해', 'correct', 1, 200, 170, 'A,B는 x절편, C는 꼭짓점, 정삼각형 조건', 1, 1, NOW()),
    ('s1_partial', @content_id, '부분 이해', 'partial', 1, 500, 170, '점들의 의미는 알지만 정삼각형 조건 모호', 2, 1, NOW()),
    ('s1_confuse', @content_id, '혼란', 'confused', 1, 800, 170, '무엇을 구해야 할지 모름', 3, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- Stage 2: x절편 구하기
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('s2_factor', @content_id, 'x(x-a)=0', 'correct', 2, 100, 310, '인수분해로 x=0, x=a', 1, 1, NOW()),
    ('s2_formula', @content_id, '근의 공식', 'partial', 2, 280, 310, '근의 공식 사용 (비효율적이지만 정답)', 2, 1, NOW()),
    ('s2_sign_err', @content_id, 'x=-a 오류', 'wrong', 2, 500, 310, 'x(x-a)=0에서 x=0, x=-a로 착각', 3, 1, NOW()),
    ('s2_forget_zero', @content_id, 'x=0 누락', 'wrong', 2, 700, 310, 'x-a=0만 풀어서 x=a만 구함', 4, 1, NOW()),
    ('s2_stuck', @content_id, '막힘', 'confused', 2, 900, 310, '어떻게 교점을 구하는지 모름', 5, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- Stage 3: 꼭짓점 구하기
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('s3_complete', @content_id, '완전제곱식', 'correct', 3, 80, 460, 'y=(x-a/2)²-a²/4 → C(a/2, -a²/4)', 1, 1, NOW()),
    ('s3_formula', @content_id, '꼭짓점 공식', 'correct', 3, 260, 460, 'x=-b/2a=a/2, y 대입', 2, 1, NOW()),
    ('s3_mid_sub', @content_id, '중점 대입', 'partial', 3, 440, 460, 'A,B 중점의 x좌표를 대입', 3, 1, NOW()),
    ('s3_sign_err', @content_id, 'y좌표 부호오류', 'wrong', 3, 640, 460, 'C(a/2, a²/4)로 착각 (양수)', 4, 1, NOW()),
    ('s3_coef_err', @content_id, '계수 착각', 'wrong', 3, 860, 460, '-b/2a에서 a=1 대입 오류', 5, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- Stage 4: 정삼각형 조건 접근법
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('s4_height', @content_id, '높이 활용', 'correct', 4, 100, 610, 'MC = (√3/2)AB 관계 사용', 1, 1, NOW()),
    ('s4_sides', @content_id, '세 변 같음', 'correct', 4, 300, 610, 'AB=BC=CA 조건 사용', 2, 1, NOW()),
    ('s4_angle', @content_id, '60° 조건', 'partial', 4, 500, 610, '각도 60° 조건으로 접근 (복잡)', 3, 1, NOW()),
    ('s4_iso_only', @content_id, '이등변만', 'wrong', 4, 700, 610, 'BC=CA만 확인, AB 무시', 4, 1, NOW()),
    ('s4_height_err', @content_id, '높이공식 오류', 'wrong', 4, 900, 610, '√3/2 대신 1/2 또는 √3 사용', 5, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- Stage 5: 거리 계산
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('s5_ab_correct', @content_id, 'AB=a 정확', 'correct', 5, 100, 760, '|a-0|=a', 1, 1, NOW()),
    ('s5_mc_correct', @content_id, 'MC=a²/4', 'correct', 5, 300, 760, 'M(a/2,0), C(a/2,-a²/4) → MC=a²/4', 2, 1, NOW()),
    ('s5_bc_calc', @content_id, 'BC 거리계산', 'partial', 5, 500, 760, '√[(a-a/2)²+(a²/4)²] 계산', 3, 1, NOW()),
    ('s5_ab_err', @content_id, 'AB=2a 오류', 'wrong', 5, 700, 760, 'AB를 2a로 착각', 4, 1, NOW()),
    ('s5_mc_sign', @content_id, 'MC 부호오류', 'wrong', 5, 900, 760, 'MC=-a²/4 (음수 처리 실패)', 5, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- Stage 6: 방정식 설정
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('s6_eq_correct', @content_id, 'a²/4=(√3/2)a', 'correct', 6, 150, 910, '정삼각형 높이 관계식 설정', 1, 1, NOW()),
    ('s6_eq_sides', @content_id, 'a=BC 설정', 'correct', 6, 400, 910, 'AB=BC에서 방정식 유도', 2, 1, NOW()),
    ('s6_eq_wrong', @content_id, '관계식 오류', 'wrong', 6, 650, 910, 'a²/4 = a/2 등 잘못된 관계', 3, 1, NOW()),
    ('s6_sqrt_err', @content_id, '√3 누락', 'wrong', 6, 880, 910, '높이=(1/2)×밑변으로 착각', 4, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- Stage 7: 최종 답
INSERT INTO `mdl_at_quantum_nodes` 
    (`node_id`, `content_id`, `label`, `type`, `stage`, `x`, `y`, `description`, `order_index`, `is_active`, `created_at`)
VALUES 
    ('s7_success', @content_id, '💥 a=2√3', 'success', 7, 200, 1060, 'a²-2√3a=0 → a=2√3 (a>0)', 1, 1, NOW()),
    ('s7_success2', @content_id, '✨ a=2√3', 'success', 7, 450, 1060, '세 변 방법으로도 동일 결과', 2, 1, NOW()),
    ('s7_fail_calc', @content_id, '❌ 계산오류', 'fail', 7, 680, 1060, 'a=√3 또는 a=2 등 오답', 3, 1, NOW()),
    ('s7_fail_cond', @content_id, '❌ a=0 선택', 'fail', 7, 900, 1060, 'a>0 조건 무시하고 a=0', 4, 1, NOW())
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `type` = VALUES(`type`), `x` = VALUES(`x`), `y` = VALUES(`y`), `description` = VALUES(`description`), `updated_at` = NOW();

-- =====================================================
-- 4. 노드-개념 연결 데이터 삽입
-- =====================================================

-- Stage 1 노드 개념 연결
INSERT INTO `mdl_at_quantum_node_concepts` (`node_id`, `concept_id`, `content_id`, `order_index`, `created_at`)
VALUES 
    ('s1_full', 'graph', @content_id, 1, NOW()),
    ('s1_partial', 'graph', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `order_index` = VALUES(`order_index`);

-- Stage 2 노드 개념 연결
INSERT INTO `mdl_at_quantum_node_concepts` (`node_id`, `concept_id`, `content_id`, `order_index`, `created_at`)
VALUES 
    ('s2_factor', 'factor', @content_id, 1, NOW()),
    ('s2_formula', 'equation', @content_id, 1, NOW()),
    ('s2_sign_err', 'factor', @content_id, 1, NOW()),
    ('s2_forget_zero', 'factor', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `order_index` = VALUES(`order_index`);

-- Stage 3 노드 개념 연결
INSERT INTO `mdl_at_quantum_node_concepts` (`node_id`, `concept_id`, `content_id`, `order_index`, `created_at`)
VALUES 
    ('s3_complete', 'complete_sq', @content_id, 1, NOW()),
    ('s3_complete', 'vertex', @content_id, 2, NOW()),
    ('s3_formula', 'vertex', @content_id, 1, NOW()),
    ('s3_mid_sub', 'midpoint', @content_id, 1, NOW()),
    ('s3_sign_err', 'vertex', @content_id, 1, NOW()),
    ('s3_coef_err', 'vertex', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `order_index` = VALUES(`order_index`);

-- Stage 4 노드 개념 연결
INSERT INTO `mdl_at_quantum_node_concepts` (`node_id`, `concept_id`, `content_id`, `order_index`, `created_at`)
VALUES 
    ('s4_height', 'equilateral', @content_id, 1, NOW()),
    ('s4_height', 'height', @content_id, 2, NOW()),
    ('s4_sides', 'equilateral', @content_id, 1, NOW()),
    ('s4_sides', 'distance', @content_id, 2, NOW()),
    ('s4_angle', 'equilateral', @content_id, 1, NOW()),
    ('s4_iso_only', 'distance', @content_id, 1, NOW()),
    ('s4_height_err', 'height', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `order_index` = VALUES(`order_index`);

-- Stage 5 노드 개념 연결
INSERT INTO `mdl_at_quantum_node_concepts` (`node_id`, `concept_id`, `content_id`, `order_index`, `created_at`)
VALUES 
    ('s5_ab_correct', 'distance', @content_id, 1, NOW()),
    ('s5_mc_correct', 'distance', @content_id, 1, NOW()),
    ('s5_mc_correct', 'midpoint', @content_id, 2, NOW()),
    ('s5_bc_calc', 'distance', @content_id, 1, NOW()),
    ('s5_ab_err', 'distance', @content_id, 1, NOW()),
    ('s5_mc_sign', 'distance', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `order_index` = VALUES(`order_index`);

-- Stage 6 노드 개념 연결
INSERT INTO `mdl_at_quantum_node_concepts` (`node_id`, `concept_id`, `content_id`, `order_index`, `created_at`)
VALUES 
    ('s6_eq_correct', 'equation', @content_id, 1, NOW()),
    ('s6_eq_correct', 'equilateral', @content_id, 2, NOW()),
    ('s6_eq_sides', 'equation', @content_id, 1, NOW()),
    ('s6_eq_sides', 'distance', @content_id, 2, NOW()),
    ('s6_eq_wrong', 'equation', @content_id, 1, NOW()),
    ('s6_sqrt_err', 'equilateral', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `order_index` = VALUES(`order_index`);

-- Stage 7 노드 개념 연결
INSERT INTO `mdl_at_quantum_node_concepts` (`node_id`, `concept_id`, `content_id`, `order_index`, `created_at`)
VALUES 
    ('s7_success', 'equation', @content_id, 1, NOW()),
    ('s7_success', 'condition', @content_id, 2, NOW()),
    ('s7_success2', 'equation', @content_id, 1, NOW()),
    ('s7_success2', 'condition', @content_id, 2, NOW()),
    ('s7_fail_calc', 'equation', @content_id, 1, NOW()),
    ('s7_fail_cond', 'condition', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `order_index` = VALUES(`order_index`);

-- =====================================================
-- 5. 엣지(Edges) 데이터 삽입
-- =====================================================
INSERT INTO `mdl_at_quantum_edges` 
    (`source_node_id`, `target_node_id`, `content_id`, `is_active`, `created_at`)
VALUES 
    -- Stage 0 → 1
    ('start', 's1_full', @content_id, 1, NOW()),
    ('start', 's1_partial', @content_id, 1, NOW()),
    ('start', 's1_confuse', @content_id, 1, NOW()),
    
    -- Stage 1 → 2
    ('s1_full', 's2_factor', @content_id, 1, NOW()),
    ('s1_full', 's2_formula', @content_id, 1, NOW()),
    ('s1_partial', 's2_formula', @content_id, 1, NOW()),
    ('s1_partial', 's2_sign_err', @content_id, 1, NOW()),
    ('s1_confuse', 's2_stuck', @content_id, 1, NOW()),
    ('s1_confuse', 's2_forget_zero', @content_id, 1, NOW()),
    
    -- Stage 2 → 3
    ('s2_factor', 's3_complete', @content_id, 1, NOW()),
    ('s2_factor', 's3_formula', @content_id, 1, NOW()),
    ('s2_formula', 's3_formula', @content_id, 1, NOW()),
    ('s2_formula', 's3_mid_sub', @content_id, 1, NOW()),
    ('s2_sign_err', 's3_sign_err', @content_id, 1, NOW()),
    ('s2_forget_zero', 's3_coef_err', @content_id, 1, NOW()),
    ('s2_stuck', 's3_mid_sub', @content_id, 1, NOW()),
    
    -- Stage 3 → 4
    ('s3_complete', 's4_height', @content_id, 1, NOW()),
    ('s3_complete', 's4_sides', @content_id, 1, NOW()),
    ('s3_formula', 's4_height', @content_id, 1, NOW()),
    ('s3_formula', 's4_sides', @content_id, 1, NOW()),
    ('s3_mid_sub', 's4_angle', @content_id, 1, NOW()),
    ('s3_mid_sub', 's4_sides', @content_id, 1, NOW()),
    ('s3_sign_err', 's4_height_err', @content_id, 1, NOW()),
    ('s3_coef_err', 's4_iso_only', @content_id, 1, NOW()),
    
    -- Stage 4 → 5
    ('s4_height', 's5_ab_correct', @content_id, 1, NOW()),
    ('s4_height', 's5_mc_correct', @content_id, 1, NOW()),
    ('s4_sides', 's5_bc_calc', @content_id, 1, NOW()),
    ('s4_sides', 's5_ab_correct', @content_id, 1, NOW()),
    ('s4_angle', 's5_bc_calc', @content_id, 1, NOW()),
    ('s4_iso_only', 's5_ab_err', @content_id, 1, NOW()),
    ('s4_height_err', 's5_mc_sign', @content_id, 1, NOW()),
    
    -- Stage 5 → 6
    ('s5_ab_correct', 's6_eq_correct', @content_id, 1, NOW()),
    ('s5_mc_correct', 's6_eq_correct', @content_id, 1, NOW()),
    ('s5_bc_calc', 's6_eq_sides', @content_id, 1, NOW()),
    ('s5_ab_err', 's6_eq_wrong', @content_id, 1, NOW()),
    ('s5_mc_sign', 's6_sqrt_err', @content_id, 1, NOW()),
    
    -- Stage 6 → 7
    ('s6_eq_correct', 's7_success', @content_id, 1, NOW()),
    ('s6_eq_sides', 's7_success2', @content_id, 1, NOW()),
    ('s6_eq_wrong', 's7_fail_calc', @content_id, 1, NOW()),
    ('s6_sqrt_err', 's7_fail_cond', @content_id, 1, NOW())
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- =====================================================
-- 완료 메시지
-- =====================================================
SELECT 
    (SELECT COUNT(*) FROM `mdl_at_quantum_contents` WHERE `content_id` = @content_id) as contents_count,
    (SELECT COUNT(*) FROM `mdl_at_quantum_concepts` WHERE `content_id` = @content_id) as concepts_count,
    (SELECT COUNT(*) FROM `mdl_at_quantum_nodes` WHERE `content_id` = @content_id) as nodes_count,
    (SELECT COUNT(*) FROM `mdl_at_quantum_node_concepts` WHERE `content_id` = @content_id) as node_concepts_count,
    (SELECT COUNT(*) FROM `mdl_at_quantum_edges` WHERE `content_id` = @content_id) as edges_count;

-- 예상 결과:
-- contents_count: 1
-- concepts_count: 10
-- nodes_count: 27
-- node_concepts_count: 32
-- edges_count: 40

