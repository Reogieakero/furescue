
CREATE TABLE messages (
    id            CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    sender_id     CHAR(36) NOT NULL,
    receiver_id   CHAR(36) NOT NULL,
    related_type  ENUM('report','case','adoption') NOT NULL,
    related_id    CHAR(36) NOT NULL,
    message_text  TEXT NOT NULL,
    sent_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at       TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    INDEX idx_messages_thread (related_type, related_id)
);

CREATE TABLE notifications (
    id           CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id      CHAR(36) NOT NULL,
    type         VARCHAR(50) NOT NULL,
    message      TEXT NOT NULL,
    related_type VARCHAR(50) NULL,
    related_id   CHAR(36) NULL,
    is_read      BOOLEAN NOT NULL DEFAULT FALSE,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_notifications_user_unread (user_id, is_read)
);

CREATE TABLE elearning_modules (
    id                CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    title             VARCHAR(150) NOT NULL,
    category          ENUM('dog_behavior','cat_behavior','basic_training','general_care') NOT NULL,
    content_body      TEXT NOT NULL,
    published_status  ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_by        CHAR(36) NOT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE elearning_progress (
    id           CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    resident_id  CHAR(36) NOT NULL,
    module_id    CHAR(36) NOT NULL,
    status       ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (resident_id) REFERENCES users(id),
    FOREIGN KEY (module_id) REFERENCES elearning_modules(id),
    UNIQUE KEY unique_resident_module (resident_id, module_id)
);
