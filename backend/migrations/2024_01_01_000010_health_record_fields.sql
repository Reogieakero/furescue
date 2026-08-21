
SET @db = DATABASE();

SET @h1 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'animal_medical_records' AND COLUMN_NAME = 'deworming_status');
SET @s1 = IF(@h1 = 0,
  'ALTER TABLE animal_medical_records ADD COLUMN deworming_status ENUM(''unknown'',''up_to_date'',''overdue'') NOT NULL DEFAULT ''unknown'' AFTER treatment_stage',
  'SET @noop = 1');
PREPARE s1 FROM @s1;
EXECUTE s1;
DEALLOCATE PREPARE s1;

SET @h2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'animal_medical_records' AND COLUMN_NAME = 'neutered');
SET @s2 = IF(@h2 = 0,
  'ALTER TABLE animal_medical_records ADD COLUMN neutered ENUM(''unknown'',''yes'',''no'') NOT NULL DEFAULT ''unknown'' AFTER deworming_status',
  'SET @noop = 1');
PREPARE s2 FROM @s2;
EXECUTE s2;
DEALLOCATE PREPARE s2;

SET @h3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'vitals_log' AND COLUMN_NAME = 'respiratory_rate_bpm');
SET @s3 = IF(@h3 = 0,
  'ALTER TABLE vitals_log ADD COLUMN respiratory_rate_bpm INT NULL AFTER heart_rate_bpm',
  'SET @noop = 1');
PREPARE s3 FROM @s3;
EXECUTE s3;
DEALLOCATE PREPARE s3;
