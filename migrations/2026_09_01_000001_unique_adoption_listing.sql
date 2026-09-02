-- Keep one live (pending_review or approved) listing per animal.
-- Reject older live duplicates, then add a generated unique key.
-- Rejected rows stay so the animal can be re-listed.

UPDATE adoption_listings
SET status = 'rejected',
    review_notes = CASE
      WHEN review_notes IS NULL OR review_notes = '' THEN 'Superseded by a newer listing for the same animal.'
      ELSE review_notes
    END
WHERE id IN (
  SELECT id FROM (
    SELECT id,
           ROW_NUMBER() OVER (
             PARTITION BY animal_id
             ORDER BY created_at DESC, id DESC
           ) AS rn
    FROM adoption_listings
    WHERE status IN ('pending_review', 'approved')
  ) ranked
  WHERE rn > 1
);

ALTER TABLE adoption_listings
  ADD COLUMN live_animal_id CHAR(36)
    GENERATED ALWAYS AS (
      CASE
        WHEN status IN ('pending_review', 'approved') THEN animal_id
        ELSE NULL
      END
    ) STORED,
  ADD UNIQUE KEY uq_adoption_listings_live_animal (live_animal_id);
