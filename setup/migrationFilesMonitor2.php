#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  |                                                                      |
  | Created by: Alex Villacís Lasso                                      |
  | Email: a_villacis@palosanto.com                                      |
  +----------------------------------------------------------------------+
*/
require_once("/var/www/html/libs/misc.lib.php");
require_once("/var/www/html/configs/default.conf.php");
require_once("/var/www/html/libs/paloSantoDB.class.php");

$dsn_conn_database = generarDSNSistema('asteriskuser', 'asteriskcdrdb',"/var/www/html/");
$pDBcdrdb = new paloDB($dsn_conn_database);

if ($pDBcdrdb->errMsg != '') {
    fputs(STDERR, "FATAL: {$pDBcdrdb->errMsg}\n");
    exit(1);
}

$sql = <<<SQL_MIGRAR
UPDATE asteriskcdrdb.cdr
    SET recordingfile = REPLACE(userfield,'audio:','')
WHERE (recordingfile IS NULL OR recordingfile = '')
SQL_MIGRAR;
if (!$pDBcdrdb->genQuery($sql)) {
    fputs(STDERR, "ERR: {$pDBcdrdb->errMsg}\n");
    exit(1);
}
exit(0);
?>