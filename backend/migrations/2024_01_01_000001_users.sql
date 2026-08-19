
CREATE TABLE users (
    id                CHAR(36)     PRIMARY KEY DEFAULT (UUID()),
    full_name         VARCHAR(150) NOT NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NULL,
    auth_provider     ENUM('native','google') NOT NULL DEFAULT 'native',
    google_id         VARCHAR(255) NULL UNIQUE,
    phone_number      VARCHAR(20)  NULL,
    address           TEXT         NULL,
    role              ENUM('resident','rescuer','admin') NOT NULL,
    account_status    ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'active',
    profile_photo_url TEXT         NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE rescuer_approvals (
    id           CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id      CHAR(36) NOT NULL UNIQUE,
    reviewed_by  CHAR(36) NULL,
    decision     ENUM('approved','rejected') NULL,
    remarks      TEXT NULL,
    reviewed_at  TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

CREATE TABLE rescuer_duty_status (
    id         CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id    CHAR(36) NOT NULL UNIQUE,
    status     ENUM('on_duty','off_duty') NOT NULL DEFAULT 'off_duty',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
