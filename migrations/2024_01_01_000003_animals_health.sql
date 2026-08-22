
CREATE TABLE animals (
    id              CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name            VARCHAR(100) NULL,
    species         ENUM('dog','cat') NOT NULL,
    breed_type      ENUM('aspin','puspin') NOT NULL,
    sex             ENUM('male','female') NOT NULL,
    age_estimate    VARCHAR(50) NULL,
    color_markings  TEXT NULL,
    description     TEXT NULL,
    photo_urls      JSON NULL,
    model_3d_url    TEXT NULL,
    photo_360_set   JSON NULL,
    adoption_status ENUM('not_listed','available','pending','adopted') NOT NULL DEFAULT 'not_listed',
    source          ENUM('rescued_case','resident_listing') NOT NULL,
    created_by      CHAR(36) NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE animal_field_status (
    id            CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    animal_id     CHAR(36) NOT NULL,
    case_id       CHAR(36) NULL,
    rescue_status ENUM('rescued','not_rescued') NOT NULL,
    health_status ENUM('healthy','not_healthy') NOT NULL,
    logged_by     CHAR(36) NOT NULL,
    logged_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id),
    FOREIGN KEY (case_id) REFERENCES cases(id),
    FOREIGN KEY (logged_by) REFERENCES users(id)
);

CREATE TABLE animal_medical_records (
    id                    CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    animal_id             CHAR(36) NOT NULL UNIQUE,
    medical_history_notes TEXT NULL,
    vaccination_status    ENUM('none','partial','complete') NOT NULL DEFAULT 'none',
    vaccination_details   JSON NULL,
    last_checkup_date     DATE NULL,
    updated_by            CHAR(36) NULL,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

CREATE TABLE vitals_log (
    id             CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    animal_id      CHAR(36) NOT NULL,
    heart_rate_bpm INT NOT NULL,
    recorded_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    source         VARCHAR(50) NULL,
    FOREIGN KEY (animal_id) REFERENCES animals(id),
    INDEX idx_vitals_animal_time (animal_id, recorded_at)
);
