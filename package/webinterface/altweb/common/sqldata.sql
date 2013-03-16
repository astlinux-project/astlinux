
CREATE TABLE IF NOT EXISTS 'sip_users' (
  'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  'sipuser' TEXT NOT NULL,
  'lastname' TEXT DEFAULT '',
  'firstname' TEXT DEFAULT '',
  'out_cxid' INTEGER DEFAULT 7,
  'vm' INTEGER DEFAULT 0,
  'vmbox' TEXT DEFAULT '',
  'email' TEXT DEFAULT '',
  'ext_intern' TEXT DEFAULT '',
  'ext_extern' TEXT DEFAULT '',
  'fax_ext' TEXT DEFAULT '',
  'fax_email' TEXT DEFAULT '',
  'xmpp_jid' TEXT DEFAULT ''
);

CREATE TABLE IF NOT EXISTS 'out_context' (
  'id' INTEGER PRIMARY KEY NOT NULL,
  'context' TEXT NOT NULL,
  'description' TEXT DEFAULT ''
);

CREATE TABLE IF NOT EXISTS 'ip_phones' (
  'id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  'type' TEXT DEFAULT '',
  'firmware' TEXT DEFAULT '',
  'hostname' TEXT DEFAULT '',
  'ipv4' TEXT DEFAULT '',
  'ipv6' TEXT DEFAULT '',
  'mac' TEXT DEFAULT '',
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

