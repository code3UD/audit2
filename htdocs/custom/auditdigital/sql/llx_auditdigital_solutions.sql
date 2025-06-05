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

-- Table bibliothèque de solutions
CREATE TABLE llx_auditdigital_solutions (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    ref varchar(128) NOT NULL UNIQUE,
    label varchar(255) NOT NULL,
    category varchar(100) NOT NULL,
    sub_category varchar(100),
    solution_type varchar(100) NOT NULL,
    target_audience varchar(100), -- 'tpe', 'pme', 'collectivite', 'all'
    price_range varchar(50), -- '5k', '10k', '15k', '20k'
    implementation_time integer, -- en jours
    priority integer DEFAULT 0,
    roi_percentage integer,
    roi_months integer,
    json_features text,
    json_benefits text,
    json_requirements text,
    description text,
    active integer DEFAULT 1,
    date_creation datetime NOT NULL,
    entity integer DEFAULT 1,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;