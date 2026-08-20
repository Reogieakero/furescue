-- Stores the rescuer's proof-of-rescue photos as a JSON array of URLs,
-- submitted by the rescuer (or added by an admin) as evidence the rescue happened.
ALTER TABLE cases
  ADD COLUMN resolution_photos JSON NULL;
