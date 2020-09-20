<?php

if ( !isset($PDOX) ) {
    require_once "../config.php";
    $CURRENT_FILE = __FILE__;
    require $CFG->dirroot."/admin/migrate-setup.php";
}

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}elastic_access",
"drop table if exists {$CFG->dbprefix}elastic_log"
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}elastic_access",
"create table {$CFG->dbprefix}elastic_access (
    index_name  CHAR(128),
    updated_at  TIMESTAMP NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(index_name)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
array( "{$CFG->dbprefix}elastic_log",
"create table {$CFG->dbprefix}elastic_log (
    index_name  varchar(128),
    auth_user varchar(128),
    auth_pw varchar(128),
    request_url TEXT NULL,
    request_method CHAR(10) NULL,
    request_body TEXT NULL,
    request_headers TEXT NULL,
    response_code INTEGER NULL,
    response_body TEXT NULL,
    response_headers TEXT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
);

