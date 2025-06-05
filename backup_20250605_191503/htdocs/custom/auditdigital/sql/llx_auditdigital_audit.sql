-- Copyright (C) 2024 Up Digit Agency
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- Table principale des audits
CREATE TABLE llx_auditdigital_audit (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    ref varchar(128) NOT NULL UNIQUE,
    label varchar(255) NOT NULL,
    audit_type varchar(50) NOT NULL,
    structure_type varchar(50) NOT NULL, -- 'tpe_pme' ou 'collectivite'
    fk_soc integer NOT NULL,
    fk_projet integer,
    date_creation datetime NOT NULL,
    date_audit datetime,
    date_valid datetime,
    fk_user_creat integer NOT NULL,
    fk_user_valid integer,
    status integer DEFAULT 0, -- 0:brouillon, 1:validé, 2:envoyé
    score_global integer,
    score_maturite integer,
    score_cybersecurite integer,
    score_cloud integer,
    score_automatisation integer,
    json_config text,
    json_responses text,
    json_recommendations text,
    note_private text,
    note_public text,
    model_pdf varchar(255) DEFAULT 'standard',
    entity integer DEFAULT 1,
    import_key varchar(14),
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

