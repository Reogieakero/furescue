ALTER TABLE animals ADD COLUMN deleted_at TIMESTAMP NULL;
CREATE INDEX idx_animals_deleted_at ON animals(deleted_at);
