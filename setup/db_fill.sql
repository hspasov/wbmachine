USE wbmachine;

INSERT INTO sites (url) VALUES ('https://www.gnu.org/software/wget/');

INSERT INTO archives (id_hash, site_id, status_id) VALUES ('4c4b1c57-d1e7-11eb-ade0-58961dc4ecd0', (SELECT id FROM sites WHERE url = 'https://www.gnu.org/software/wget/'), 30);
