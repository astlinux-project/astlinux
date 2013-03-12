
CREATE TABLE IF NOT EXISTS 'sip_users' (
  'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  'sipuser' TEXT NOT NULL,
  'lastname' TEXT,
  'firstname' TEXT,
  'out_cxid' INTEGER DEFAULT 7,
  'vm' INTEGER DEFAULT 0,
  'vmbox' TEXT,
  'email' TEXT,
  'ext_intern' TEXT,
  'ext_extern' TEXT,
  'fax_ext' TEXT,
  'fax_email' TEXT,
  'xmpp_jid' TEXT
);

CREATE TABLE IF NOT EXISTS 'out_context' (
  'id' INTEGER PRIMARY KEY NOT NULL,
  'context' TEXT NOT NULL,
  'description' TEXT
);

CREATE TABLE IF NOT EXISTS 'ip_phones' (
  'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  'type' TEXT,
  'firmware' TEXT,
  'hostname' TEXT,
  'ipv4' TEXT,
  'ipv6' TEXT,
  'mac' TEXT,
  'sipuser_id' INTEGER
);

INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('0','emergency','Emergency calls only')
;
INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('1','local_outgoing','Local calls only')
;
INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('2','local_outgoing','Unused1')
;
INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('3','national_outgoing','National calls')
;
INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('4','national_outgoing','Unused2')
;
INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('5','national_outgoing','Unused3')
;
INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('6','national_outgoing','Unused4')
;
INSERT OR IGNORE INTO "out_context" ("id","context","description")
VALUES ('7','international_outgoing','International calls')
;

