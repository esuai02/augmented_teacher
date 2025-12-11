-- modification_prompt 필드 추가
ALTER TABLE mdl_ktm_teaching_interactions 
ADD COLUMN modification_prompt LONGTEXT DEFAULT NULL 
AFTER audio_url;