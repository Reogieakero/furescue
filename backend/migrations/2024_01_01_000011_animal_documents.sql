CREATE TABLE IF NOT EXISTS animal_documents (
  id CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY DEFAULT (UUID()),
  animal_id CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  name VARCHAR(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  doc_type VARCHAR(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  file_url VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  meta VARCHAR(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  uploaded_by CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (animal_id) REFERENCES animals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
