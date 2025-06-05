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

CREATE TABLE llx_auditdigital_question(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref varchar(128) NOT NULL,
	label varchar(400) NOT NULL,
	description text,
	category varchar(100) NOT NULL,
	subcategory varchar(100),
	question_text text NOT NULL,
	question_type varchar(50) DEFAULT 'multiple_choice',
	weight integer DEFAULT 1,
	max_points integer DEFAULT 5,
	order_position integer DEFAULT 0,
	active tinyint DEFAULT 1,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer
) ENGINE=innodb;

-- Index pour optimiser les recherches
ALTER TABLE llx_auditdigital_question ADD INDEX idx_auditdigital_question_rowid (rowid);
ALTER TABLE llx_auditdigital_question ADD INDEX idx_auditdigital_question_ref (ref);
ALTER TABLE llx_auditdigital_question ADD INDEX idx_auditdigital_question_category (category);
ALTER TABLE llx_auditdigital_question ADD INDEX idx_auditdigital_question_active (active);
ALTER TABLE llx_auditdigital_question ADD INDEX idx_auditdigital_question_order (order_position);
ALTER TABLE llx_auditdigital_question ADD CONSTRAINT llx_auditdigital_question_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);