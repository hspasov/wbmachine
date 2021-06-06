CREATE DATABASE wbmachine;

USE wbmachine;

CREATE TABLE sites (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  url VARCHAR(512) UNIQUE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX sites_created_at_idx ON sites (created_at);

CREATE TABLE archive_statuses (
  id BIGINT PRIMARY KEY,
  name VARCHAR(64) UNIQUE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX archive_statuses_created_at_idx ON archive_statuses (created_at);

INSERT INTO archive_statuses (id, name)
VALUES
(10, 'Pending'),
(20, 'In progress'),
(30, 'Done');

CREATE TABLE archives (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_hash CHAR(36) NOT NULL DEFAULT (UUID()),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  site_id BIGINT NOT NULL,
  status_id BIGINT NOT NULL,
  FOREIGN KEY (site_id) REFERENCES sites(id),
  FOREIGN KEY (status_id) REFERENCES archive_statuses(id)
);

CREATE INDEX archives_id_hash_idx ON archives (id_hash);
CREATE INDEX archives_created_at_idx ON archives (created_at);
CREATE INDEX archives_updated_at_idx ON archives (updated_at);
CREATE INDEX archives_site_id_idx ON archives (site_id);
CREATE INDEX archives_status_id_idx ON archives (status_id);

CREATE TRIGGER archives_update_trig
BEFORE UPDATE ON archives
FOR EACH ROW SET NEW.updated_at = CURRENT_TIMESTAMP;

CREATE TABLE schedule_intervals (
  id BIGINT PRIMARY KEY,
  name VARCHAR(64) UNIQUE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX schedule_intervals_created_at_idx ON schedule_intervals (created_at);

INSERT INTO schedule_intervals (id, name)
VALUES
(10, 'None'),
(20, 'Now'),
(30, 'Every month'),
(40, 'Every 6 months'),
(50, 'Every year'),
(60, 'Every 3 years'),
(70, 'Every 5 years'),
(80, 'Every 10 years');

CREATE TABLE archive_schedules (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  schedule_interval_id BIGINT NOT NULL CHECK (schedule_interval_id NOT IN (10, 20)),
  site_id BIGINT UNIQUE NOT NULL,
  FOREIGN KEY (schedule_interval_id) REFERENCES schedule_intervals(id),
  FOREIGN KEY (site_id) REFERENCES sites(id)
);

CREATE INDEX archive_schedules_schedule_interval_id_idx ON archive_schedules (schedule_interval_id);
CREATE INDEX archive_schedules_site_id_idx ON archive_schedules (site_id);

CREATE USER wbmachine@localhost IDENTIFIED BY 'ParolataESlozhna';
GRANT ALL PRIVILEGES ON wbmachine.* TO wbmachine@localhost;
