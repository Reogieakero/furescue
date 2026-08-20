
SET @db = DATABASE();

SET @has = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'animals' AND COLUMN_NAME = 'barangay');
SET @sql = IF(@has = 0, 'ALTER TABLE animals ADD COLUMN barangay VARCHAR(80) NULL AFTER color_markings', 'SET @noop = 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @has2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'animal_medical_records' AND COLUMN_NAME = 'next_checkup_due');
SET @sql2 = IF(@has2 = 0,
  'ALTER TABLE animal_medical_records ADD COLUMN next_checkup_due DATE NULL AFTER last_checkup_date, ADD COLUMN vaccination_expiry DATE NULL AFTER next_checkup_due, ADD COLUMN `condition` VARCHAR(120) NULL AFTER vaccination_expiry, ADD COLUMN treatment_stage ENUM(''none'',''ongoing'',''completed'') NULL AFTER `condition`, ADD COLUMN weight_kg DECIMAL(5,2) NULL AFTER treatment_stage, ADD COLUMN temperature_c DECIMAL(4,1) NULL AFTER weight_kg, ADD COLUMN vet_name VARCHAR(120) NULL AFTER temperature_c',
  'SET @noop = 1');
PREPARE s2 FROM @sql2;
EXECUTE s2;
DEALLOCATE PREPARE s2;

