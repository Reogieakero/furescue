SET @db = DATABASE();

SET @h = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'animals' AND COLUMN_NAME = 'birth_date');
SET @s = IF(@h = 0,
  'ALTER TABLE animals ADD COLUMN birth_date DATE NULL AFTER age_estimate',
  'SET @noop = 1');
PREPARE s FROM @s;
EXECUTE s;
DEALLOCATE PREPARE s;
