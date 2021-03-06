DROP TRIGGER IF EXISTS check_insert;
DROP TRIGGER IF EXISTS check_update;

CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\[' THEN SET NEW.isrequestid = 1;ELSEIF NEW.releasenfoid = 0 THEN SET NEW.nfostatus = -1; END IF; END;
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\[' THEN SET NEW.isrequestid = 1;ELSEIF NEW.releasenfoid = 0 THEN SET NEW.nfostatus = -1; END IF; END;

UPDATE releases set isrequestid = 1
WHERE name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\['
AND isrequestid = 0;