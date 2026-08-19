-- Adds an explicit pre-assignment state to cases so a case created on report
-- verification is "open" (no rescuer yet) instead of incorrectly "assigned".
-- Back-fills any existing case that has no assigned rescuer.

ALTER TABLE cases
  MODIFY COLUMN status ENUM('open','assigned','in_progress','resolved') NOT NULL DEFAULT 'open';

UPDATE cases SET status = 'open' WHERE assigned_rescuer_id IS NULL;
