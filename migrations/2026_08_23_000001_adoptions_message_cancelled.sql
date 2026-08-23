-- Applicant note on adoption applications + cancellable pending state.
ALTER TABLE adoptions
    ADD COLUMN message TEXT NULL AFTER applicant_id;

ALTER TABLE adoptions
    MODIFY COLUMN status ENUM('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending';
