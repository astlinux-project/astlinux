
CREATE TABLE IF NOT EXISTS 'sip_users' (
  'userid' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  'sipuser' TEXT NOT NULL,
  'lastname' TEXT,
  'firstname' TEXT,
  'out_contextid' INTEGER DEFAULT 7,
  'vm' INTEGER DEFAULT 0,
  'mailbox' TEXT,
  'email' TEXT,
  'exten_intern' TEXT,
  'exten_exten' TEXT,
  'fax_number' TEXT,
  'fax_email' TEXT,
  'xmpp_jid' TEXT
);

CREATE TABLE IF NOT EXISTS 'out_context' (
  'out_contextid' INTEGER PRIMARY KEY NOT NULL,
  'outgoing_context' TEXT NOT NULL,
  'out_cx_description' TEXT
);

CREATE TABLE IF NOT EXISTS 'ip_phones' (
  'phone_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  'phone_type1' TEXT,
  'phone_fw1' TEXT,
  'phone_ip' TEXT,
  'phone_mac' TEXT,
  'userid' INTEGER
);

INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('0','emergency','Emergency calls only')
;
INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('1','local_outgoing','Local calls only')
;
INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('2','local_outgoing','Unused1')
;
INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('3','national_outgoing','National calls')
;
INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('4','national_outgoing','Unused2')
;
INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('5','national_outgoing','Unused3')
;
INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('6','national_outgoing','Unused4')
;
INSERT OR IGNORE INTO "out_context" ("out_contextid","outgoing_context","out_cx_description")
VALUES ('7','international_outgoing','International calls')
;

