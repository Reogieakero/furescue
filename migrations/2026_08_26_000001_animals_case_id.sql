-- Optional 1:1 link from a registered animal back to its resolved rescue case.
ALTER TABLE animals
    ADD COLUMN case_id CHAR(36) NULL AFTER source;

ALTER TABLE animals
    ADD UNIQUE KEY uq_animals_case_id (case_id);

ALTER TABLE animals
    ADD CONSTRAINT fk_animals_case_id FOREIGN KEY (case_id) REFERENCES cases(id);
