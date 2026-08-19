
CREATE TABLE adoption_listings (
    id            CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    animal_id     CHAR(36) NOT NULL,
    posted_by     CHAR(36) NOT NULL,
    status        ENUM('pending_review','approved','rejected') NOT NULL DEFAULT 'pending_review',
    reviewed_by   CHAR(36) NULL,
    review_notes  TEXT NULL,
    reviewed_at   TIMESTAMP NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id),
    FOREIGN KEY (posted_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

CREATE TABLE adoptions (
    id               CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    animal_id        CHAR(36) NOT NULL,
    applicant_id     CHAR(36) NOT NULL,
    status           ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL,
    reviewed_by      CHAR(36) NULL,
    reviewed_at      TIMESTAMP NULL,
    completed_at     TIMESTAMP NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id),
    FOREIGN KEY (applicant_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);
