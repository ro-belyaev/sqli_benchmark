DROP DATABASE IF EXISTS sqli_benchmark;
CREATE DATABASE sqli_benchmark CHARACTER SET utf8;
SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION';

USE sqli_benchmark;

CREATE TABLE IF NOT EXISTS generation(
	id int NOT NULL AUTO_INCREMENT,
	num_of_tests int NOT NULL DEFAULT 0,
	max_num_of_tests int NOT NULL,
	process int NOT NULL,
	state int NOT NULL,
	xml text,
	generation_time datetime NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8;

CREATE TABLE IF NOT EXISTS mytester(
	launch_id int NOT NULL AUTO_INCREMENT,
	generation_id int NOT NULL,
	state int NOT NULL,
	start_time datetime NOT NULL,
	PRIMARY KEY launch_id (launch_id),
	FOREIGN KEY (generation_id) REFERENCES generation (id)
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8;

CREATE TABLE IF NOT EXISTS scanners_launch(
	scanner_id int NOT NULL,
	scanner_name char(35) NOT NULL,
	launch_id int NOT NULL,
	state int NOT NULL,
	num_of_tests int NOT NULL DEFAULT 0,
	finish_time datetime,
	PRIMARY KEY (scanner_id, launch_id),
	FOREIGN KEY (launch_id) REFERENCES mytester (launch_id)
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8;

CREATE TABLE IF NOT EXISTS report(
	launch_id int NOT NULL,
	report_id int NOT NULL AUTO_INCREMENT,
	report_state enum('classes', 'scanners', 'done') NOT NULL,
	PRIMARY KEY (report_id),
	FOREIGN KEY (launch_id) REFERENCES mytester (launch_id)
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8;

CREATE TABLE IF NOT EXISTS report_classes(
	report_id int NOT NULL,
	class_id int NOT NULL AUTO_INCREMENT,
	xml_slice text NOT NULL,
	type enum('vuln', 'not-vuln') NOT NULL,
	name_of_class char(60) NOT NULL,
	PRIMARY KEY (class_id),
	FOREIGN KEY (report_id) REFERENCES report (report_id)
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8;

CREATE TABLE IF NOT EXISTS scanners_report(
	report_id int NOT NULL,
	combination_id int NOT NULL AUTO_INCREMENT,
	scanner1_id int NOT NULL,
	scanner2_id int,
	PRIMARY KEY (combination_id),
	FOREIGN KEY (report_id) REFERENCES report (report_id)
	ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET=UTF8;
