BEGIN TRANSACTION;
CREATE TABLE item_box 
(
    id                   INTEGER PRIMARY KEY,
    id_area              INTEGER,
    id_device            INTEGER,
    FOREIGN KEY(id_area) REFERENCES area(id)
);
CREATE TABLE area 
(
    id              INTEGER PRIMARY KEY,
    name            varchar (80),
    height          INTEGER,
    width           INTEGER,
    description     varchar (100),
    no_column       INTEGER
);
INSERT INTO "area" VALUES(1, 'Extension', 232, 608, 'Extensions', 3);
INSERT INTO "area" VALUES(2, 'Area1', 100, 380, 'Area 1', 2);
INSERT INTO "area" VALUES(3, 'Area2', 100, 381, 'Area 2', 2);
INSERT INTO "area" VALUES(4, 'Area3', 100, 380, 'Area 3', 2);
INSERT INTO "area" VALUES(5, 'Queues', 100, 380, 'Queues', 2);
INSERT INTO "area" VALUES(6, 'Trunks', 271, 608, 'DAHDI Trunks', 3);
COMMIT;
