-- mdl_alt42_field_descriptions 테이블의 데이터를 MySQL COLUMN COMMENT로 마이그레이션하는 SQL
-- 실행 전 반드시 백업을 수행하세요!

-- 1. 마이그레이션 프로시저 생성
DELIMITER $$

CREATE PROCEDURE migrate_field_descriptions()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_table_name VARCHAR(255);
    DECLARE v_field_name VARCHAR(255);
    DECLARE v_description TEXT;
    DECLARE v_column_type VARCHAR(100);
    DECLARE v_is_nullable VARCHAR(3);
    DECLARE v_column_default TEXT;
    DECLARE v_extra VARCHAR(50);
    
    -- 커서 선언: mdl_alt42_field_descriptions 테이블의 모든 레코드
    DECLARE cur CURSOR FOR 
        SELECT table_name, field_name, description 
        FROM mdl_alt42_field_descriptions 
        WHERE description IS NOT NULL AND description != '';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- 마이그레이션 시작
    SELECT '마이그레이션 시작...' AS status;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_table_name, v_field_name, v_description;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- 해당 필드의 현재 정보 가져오기
        SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
        INTO v_column_type, v_is_nullable, v_column_default, v_extra
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = v_table_name
        AND COLUMN_NAME = v_field_name
        LIMIT 1;
        
        -- 필드가 존재하는 경우에만 ALTER TABLE 실행
        IF v_column_type IS NOT NULL THEN
            SET @sql = CONCAT('ALTER TABLE `', v_table_name, '` MODIFY COLUMN `', v_field_name, '` ', v_column_type);
            
            -- NULL 허용 여부
            IF v_is_nullable = 'NO' THEN
                SET @sql = CONCAT(@sql, ' NOT NULL');
            ELSE
                SET @sql = CONCAT(@sql, ' NULL');
            END IF;
            
            -- 기본값
            IF v_column_default IS NOT NULL THEN
                SET @sql = CONCAT(@sql, ' DEFAULT ''', v_column_default, '''');
            END IF;
            
            -- EXTRA (auto_increment 등)
            IF v_extra IS NOT NULL AND v_extra != '' THEN
                SET @sql = CONCAT(@sql, ' ', v_extra);
            END IF;
            
            -- COMMENT 추가
            SET @sql = CONCAT(@sql, ' COMMENT ''', REPLACE(v_description, '''', ''''''), '''');
            
            -- SQL 실행
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- 로그 출력
            SELECT CONCAT('마이그레이션 완료: ', v_table_name, '.', v_field_name) AS status;
        ELSE
            SELECT CONCAT('필드를 찾을 수 없음: ', v_table_name, '.', v_field_name) AS status;
        END IF;
        
    END LOOP;
    
    CLOSE cur;
    
    SELECT '마이그레이션 완료!' AS status;
END$$

DELIMITER ;

-- 2. 프로시저 실행
CALL migrate_field_descriptions();

-- 3. 프로시저 삭제
DROP PROCEDURE IF EXISTS migrate_field_descriptions;

-- 4. 마이그레이션 확인 쿼리
-- 기존 테이블의 데이터 확인
SELECT 
    table_name,
    field_name,
    description
FROM mdl_alt42_field_descriptions
ORDER BY table_name, field_name;

-- 마이그레이션된 COMMENT 확인
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND COLUMN_COMMENT != ''
ORDER BY TABLE_NAME, COLUMN_NAME;

-- 5. 백업용 테이블 생성 (선택사항)
-- CREATE TABLE mdl_alt42_field_descriptions_backup AS SELECT * FROM mdl_alt42_field_descriptions;

-- 6. 기존 테이블 삭제 (마이그레이션이 성공적으로 완료된 후에만 실행)
-- DROP TABLE IF EXISTS mdl_alt42_field_descriptions;