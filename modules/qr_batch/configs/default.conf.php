<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
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
*/
global $arrConfModule;
$arrConfModule['module_name'] = 'qr_batch';
$arrConfModule['templates_dir'] = 'themes';

$configTemplates = array();
$configTemplates['gswave']="
    <?xml version='1.0' encoding='utf-8'?>
    <AccountConfig version='1'>
      <Account>
          <RegisterServer>{SERVER}</RegisterServer>
          <OutboundServer></OutboundServer>
          <UserID>{EXTENSION}</UserID>
          <AuthID>{EXTENSION}</AuthID>
          <AuthPass>{SECRET}</AuthPass>
          <AccountName>{EXTENSION}</AccountName>
          <DisplayName>{NAME}</DisplayName>
          <Dialplan>{x+|*x+|*++}</Dialplan>
          <RandomPort>0</RandomPort>
          <SecOutboundServer></SecOutboundServer>
          <Voicemail>*97</Voicemail>
        </Account>
    </AccountConfig>
";
?>
