#!/bin/bash
mysql -h '127.0.0.1' -e 'CREATE DATABASE IF NOT EXISTS plasma_tmp'
mysql -h '127.0.0.1' -e 'CREATE TABLE plasma_tmp.test_cursors (testcol VARCHAR(50) NOT NULL)'
mysql -h '127.0.0.1' -e 'INSERT INTO plasma_tmp.test_cursors VALUES ("HELLO"), ("WORLD"), ("PLASMA"), ("IN"), ("ACTION")'
mysql -h '127.0.0.1' -e "CREATE TABLE plasma_tmp.test_strings (testcol1 CHAR(20) NOT NULL, testcol2 VARCHAR(20) NOT NULL, testcol3 TINYTEXT NOT NULL, testcol4 TEXT NOT NULL, testcol5 MEDIUMTEXT NOT NULL, testcol6 LONGTEXT NOT NULL, testcol7 BINARY(3) NOT NULL, testcol8 VARBINARY(20) NOT NULL, testcol9 TINYBLOB NOT NULL, testcol10 MEDIUMBLOB NOT NULL, testcol11 BLOB NOT NULL, testcol12 LONGBLOB NOT NULL, testcol13 ENUM('hey','hello') NOT NULL, testcol14 SET('world','internet') NOT NULL, testcol15 VARCHAR(5) NOT NULL, testcol16 BIT NOT NULL, testcol17 DECIMAL(2,1) NOT NULL, testcol18 VARCHAR(20) NOT NULL)"
mysql -h '127.0.0.1' -e "CREATE TABLE plasma_tmp.test_ints (testcol1 TINYINT(5) UNSIGNED ZEROFILL NOT NULL, testcol2 SMALLINT(20) NOT NULL, testcol3 YEAR(4) NOT NULL, testcol4 MEDIUMINT(20) NOT NULL, testcol5 INT(20) NOT NULL, testcol6 BIGINT(20) NOT NULL)"
mysql -h '127.0.0.1' -e "CREATE TABLE plasma_tmp.test_floats (testcol1 FLOAT NOT NULL, testcol2 DOUBLE NOT NULL)"
mysql -h '127.0.0.1' -e "CREATE TABLE plasma_tmp.test_dates (testcol1 DATE NOT NULL, testcol2 DATETIME NOT NULL, testcol3 TIME NOT NULL, testcol4 TIMESTAMP NOT NULL)"
