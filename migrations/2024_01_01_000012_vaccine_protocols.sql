SET @db = DATABASE();

SET @h1 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'animal_medical_records' AND COLUMN_NAME = 'vaccine_protocols');
SET @s1 = IF(@h1 = 0,
  'ALTER TABLE animal_medical_records ADD COLUMN vaccine_protocols JSON NULL AFTER vaccination_details',
  'SET @noop = 1');
PREPARE s1 FROM @s1;
EXECUTE s1;
DEALLOCATE PREPARE s1;

SET @h2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'animal_medical_records' AND COLUMN_NAME = 'vaccination_records');
SET @s2 = IF(@h2 = 0,
  'ALTER TABLE animal_medical_records ADD COLUMN vaccination_records JSON NULL AFTER vaccine_protocols',
  'SET @noop = 1');
PREPARE s2 FROM @s2;
EXECUTE s2;
DEALLOCATE PREPARE s2;
