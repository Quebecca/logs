CREATE TABLE tx_foo_log
(
    request_id varchar(13)         DEFAULT ''  NOT NULL,
    time_micro double(16, 4)                   NOT NULL default '0.0000',
    component  varchar(255)        DEFAULT ''  NOT NULL,
    level      tinyint(1) unsigned DEFAULT '0' NOT NULL,
    message    text,
    data       text,

    KEY request (request_id)
);
