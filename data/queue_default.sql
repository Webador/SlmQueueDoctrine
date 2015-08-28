/* GENERIC */
CREATE TABLE IF NOT EXISTS `queue_default` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(64) NOT NULL,
  `data` text NOT NULL,
  `status` smallint(1) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `scheduled` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `executed` datetime NULL DEFAULT NULL,
  `finished` datetime NULL DEFAULT NULL,
  `message` text DEFAULT NULL,
  `trace` text,
  PRIMARY KEY (`id`),
  KEY `pop` (`status`,`queue`,`scheduled`),
  KEY `prune` (`status`,`queue`,`finished`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


/* ORACLE */
CREATE TABLE QUEUE_DEFAULT
  (
  ID NUMBER(10,0) PRIMARY KEY NOT NULL,
  QUEUE VARCHAR2(64) NOT NULL,
  DATA VARCHAR2(4000) NOT NULL,
  STATUS NUMBER(5,0) NOT NULL,
  CREATED DATE DEFAULT sysdate,
  SCHEDULED DATE DEFAULT sysdate,
  EXECUTED DATE,
  FINISHED DATE,
  MESSAGE VARCHAR2(4000) DEFAULT NULL,
  TRACE VARCHAR2(4000)
  );

CREATE INDEX IX_QUEUE_DEFAULT_POP
  ON QUEUE_DEFAULT (STATUS, QUEUE, SCHEDULED);
CREATE INDEX IX_QUEUE_DEFAULT_PRUNE
  ON QUEUE_DEFAULT (STATUS, QUEUE, FINISHED);

CREATE OR REPLACE TRIGGER  DEFAULT_QUEUE_TRIGGER
  before insert on QUEUE_DEFAULT
  for each row
begin
  if :NEW.ID is null then
    select QUEUE_SEQ.nextval into :NEW.ID from dual;
  end if;
end;