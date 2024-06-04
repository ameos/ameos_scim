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