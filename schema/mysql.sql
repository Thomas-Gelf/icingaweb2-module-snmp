
CREATE TABLE snmp_credential (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(64) NOT NULL,
  snmp_version enum('1', '2c', '3') NOT NULL,
  security_name varchar(64) NOT NULL COMMENT 'This is the community for v1/v2c and the user for v3',
  security_level enum ('noAuthNoPriv', 'authNoPriv', 'authPriv') NOT NULL DEFAULT 'noAuthNoPriv',
  auth_protocol enum('md5', 'sha') NULL DEFAULT NULL,
  auth_key varchar(64) NULL DEFAULT NULL,
  priv_protocol enum('des', 'aes') NULL DEFAULT NULL,
  priv_key varchar(64) NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE snmp_agent (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  host_id int(10) unsigned NULL DEFAULT NULL,
  credential_id int(10) unsigned NOT NULL,
  ip_address varbinary(16) NOT NULL,
  ip_protocol enum('ipv4', 'ipv6') NOT NULL,
  snmp_port smallint(5) unsigned NOT NULL DEFAULT 161,
  snmp_engine_id varbinary(64) DEFAULT NULL,
  snmp_agent_startup timestamp NULL DEFAULT NULL,
  sys_name varchar(255) DEFAULT NULL,
  sys_descr varchar(255) DEFAULT NULL,
  sys_object_id varbinary(255) DEFAULT NULL,
  sys_contact varchar(255) DEFAULT NULL,
  sys_location varchar(255) DEFAULT NULL,
  sys_services tinyint(3) unsigned DEFAULT NULL,
  last_seen DATETIME NULL DEFAULT NULL,
  state enum('ok', 'unreacheable', 'discovered', 'blacklisted') NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT snmp_agent_credential FOREIGN KEY (credential_id) REFERENCES snmp_credential (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
