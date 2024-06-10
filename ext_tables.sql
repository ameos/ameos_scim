CREATE TABLE fe_users (
    scim_external_id varchar(255) DEFAULT '' NOT NULL,
    scim_id varchar(255) DEFAULT '' NOT NULL,
);

CREATE TABLE fe_groups (
    scim_external_id varchar(255) DEFAULT '' NOT NULL,
    scim_id varchar(255) DEFAULT '' NOT NULL,
);

CREATE TABLE be_users (
    scim_external_id varchar(255) DEFAULT '' NOT NULL,
    scim_id varchar(255) DEFAULT '' NOT NULL,
);

CREATE TABLE be_groups (
    scim_external_id varchar(255) DEFAULT '' NOT NULL,
    scim_id varchar(255) DEFAULT '' NOT NULL,
);

CREATE TABLE tx_scim_access (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    createdon int(11) DEFAULT '0' NOT NULL,
    updatedon int(11) DEFAULT '0' NOT NULL,    
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,

    name varchar(255) DEFAULT '' NOT NULL,
    secret varchar(255) DEFAULT '' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);