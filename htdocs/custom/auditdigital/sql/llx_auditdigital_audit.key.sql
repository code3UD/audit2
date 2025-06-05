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

-- Index et contraintes pour la table llx_auditdigital_audit
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_ref (ref);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_fk_soc (fk_soc);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_fk_projet (fk_projet);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_fk_user_creat (fk_user_creat);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_status (status);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_structure_type (structure_type);
ALTER TABLE llx_auditdigital_audit ADD INDEX idx_auditdigital_audit_entity (entity);

-- Contraintes de clés étrangères
ALTER TABLE llx_auditdigital_audit ADD CONSTRAINT fk_audit_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
ALTER TABLE llx_auditdigital_audit ADD CONSTRAINT fk_audit_projet FOREIGN KEY (fk_projet) REFERENCES llx_projet(rowid);
ALTER TABLE llx_auditdigital_audit ADD CONSTRAINT fk_audit_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_auditdigital_audit ADD CONSTRAINT fk_audit_user_valid FOREIGN KEY (fk_user_valid) REFERENCES llx_user(rowid);