-- Copyright (C) 2024 AuditDigital Module
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

CREATE TABLE llx_auditdigital_audit(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL,
	label varchar(400),
	description text,
	note_public text,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	last_main_doc varchar(255),
	import_key varchar(14),
	model_pdf varchar(255),
	status integer NOT NULL DEFAULT 0,
	-- END MODULEBUILDER FIELDS
	-- Champs spécifiques au module audit
	fk_soc integer,
	audit_type varchar(50) DEFAULT 'digital_maturity',
	start_date date,
	end_date date,
	auditor_name varchar(255),
	auditor_email varchar(255),
	company_size varchar(50),
	sector varchar(100),
	total_score integer DEFAULT 0,
	max_score integer DEFAULT 0,
	maturity_level varchar(50),
	recommendations text,
	action_plan text,
	pdf_generated tinyint DEFAULT 0,
	proposal_generated tinyint DEFAULT 0
) ENGINE=innodb;

-- Index pour optimiser les recherches
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_rowid (rowid);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_ref (ref);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_fk_soc (fk_soc);
ALTER TABLE llx_auditdigital_audit ADD CONSTRAINT llx_auditdigital_audit_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_status (status);