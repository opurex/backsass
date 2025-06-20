CREATE TABLE fiscaltickets (type VARCHAR(255) NOT NULL, sequence VARCHAR(255) NOT NULL, number INT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, content TEXT NOT NULL, signature VARCHAR(255) NOT NULL, PRIMARY KEY(type, sequence, number));
CREATE SEQUENCE archiverequests_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE TABLE archives (number INT NOT NULL, info TEXT NOT NULL, content BYTEA NOT NULL, contentHash VARCHAR(255) NOT NULL, signature VARCHAR(255) NOT NULL, PRIMARY KEY(number));
CREATE TABLE archiverequests (id INT NOT NULL, startDate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, stopDate TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, processing BOOLEAN NOT NULL, PRIMARY KEY(id));
