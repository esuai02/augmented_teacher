-- problem_image 필드를 LONGTEXT로 변경 (대용량 이미지 지원)
ALTER TABLE mdl_ktm_teaching_interactions 
MODIFY COLUMN problem_image LONGTEXT;

-- modification_prompt 필드 추가 (없는 경우)
ALTER TABLE mdl_ktm_teaching_interactions 
ADD COLUMN IF NOT EXISTS modification_prompt LONGTEXT DEFAULT NULL 
AFTER audio_url;