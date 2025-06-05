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

CREATE TABLE llx_auditdigital_answer(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_audit integer NOT NULL,
	fk_question integer NOT NULL,
	answer_value varchar(255),
	answer_score integer DEFAULT 0,
	answer_comment text,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer
) ENGINE=innodb;

-- Index pour optimiser les recherches
ALTER TABLE llx_auditdigital_answer ADD INDEX idx_auditdigital_answer_rowid (rowid);
ALTER TABLE llx_auditdigital_answer ADD INDEX idx_auditdigital_answer_fk_audit (fk_audit);
ALTER TABLE llx_auditdigital_answer ADD INDEX idx_auditdigital_answer_fk_question (fk_question);
ALTER TABLE llx_auditdigital_answer ADD CONSTRAINT llx_auditdigital_answer_fk_audit FOREIGN KEY (fk_audit) REFERENCES llx_auditdigital_audit(rowid);
ALTER TABLE llx_auditdigital_answer ADD CONSTRAINT llx_auditdigital_answer_fk_question FOREIGN KEY (fk_question) REFERENCES llx_auditdigital_question(rowid);
ALTER TABLE llx_auditdigital_answer ADD CONSTRAINT llx_auditdigital_answer_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);

-- Index unique pour éviter les doublons
ALTER TABLE llx_auditdigital_answer ADD UNIQUE KEY uk_auditdigital_answer_audit_question (fk_audit, fk_question);