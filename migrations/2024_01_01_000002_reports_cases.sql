
CREATE TABLE reports (
    id                  CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    resident_id         CHAR(36) NOT NULL,
    animal_description  TEXT NOT NULL,
    photo_urls          JSON NULL,
    latitude            DECIMAL(9,6) NOT NULL,
    longitude           DECIMAL(9,6) NOT NULL,
    address_text        TEXT NULL,
    content_hash        VARCHAR(64) NOT NULL,
    duplicate_of_report_id CHAR(36) NULL,
    validation_status   ENUM('pending','validated','flagged_duplicate','invalid') NOT NULL DEFAULT 'pending',
    status              ENUM('pending_verification','verified','dismissed') NOT NULL DEFAULT 'pending_verification',
    dismiss_reason      TEXT NULL,
    verified_by         CHAR(36) NULL,
    verified_at         TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    FOREIGN KEY (duplicate_of_report_id) REFERENCES reports(id),
    INDEX idx_reports_location (latitude, longitude),
    INDEX idx_reports_content_hash (content_hash)
);

CREATE TABLE cases (
    id                  CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    report_id           CHAR(36) NOT NULL UNIQUE,
    assigned_rescuer_id CHAR(36) NULL,
    assigned_by         CHAR(36) NULL,
    status              ENUM('assigned','in_progress','resolved') NOT NULL DEFAULT 'assigned',
    resolution_notes    TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id),
    FOREIGN KEY (assigned_rescuer_id) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

CREATE TABLE case_activity_log (
    id         CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    case_id    CHAR(36) NOT NULL,
    actor_id   CHAR(36) NOT NULL,
    actor_role ENUM('admin','rescuer') NOT NULL,
    action     VARCHAR(100) NOT NULL,
    notes      TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id),
    FOREIGN KEY (actor_id) REFERENCES users(id)
);
