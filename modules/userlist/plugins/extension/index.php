<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
 Codificación: UTF-8
 +----------------------------------------------------------------------+
 | Issabel version 0.5                                                  |
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
 +----------------------------------------------------------------------+
 $Id: paloSantoACL.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

class paloUserPlugin_extension extends paloSantoUserPluginBase
{
    function userReport_labels()
    {
        return array(_tr("Extension"));
    }

    function userReport_data($username, $id_user)
    {
        $ext = $this->_pACL->getUserExtension($username);
        if (is_null($ext) || $ext == '')
            $ext = _tr("No extension associated");
        return array(
            htmlentities($ext, ENT_COMPAT, 'UTF-8'),
        );
    }

    function addFormElements($privileged)
    {
        if ($privileged) {
            $arrData = array(
                '' => _tr("no extension")
            );

            // Cargar lista de extensiones conocidas desde FreePBX
            $dsn = generarDSNSistema('asteriskuser', 'asterisk');
            $pDBa = new paloDB($dsn);
            $rs = $pDBa->fetchTable('SELECT extension FROM users ORDER BY extension', TRUE);
            if (is_array($rs)) foreach ($rs as $item) {
                $arrData[$item["extension"]] = $item["extension"];
            }

            // TODO: ¿qué extensiones ya han sido asignadas a usuarios Issabel?

            return array(
                "extension"   => array(
                    "LABEL"                  => _tr("Extension"),
                    "REQUIRED"               => "no",
                    "INPUT_TYPE"             => "SELECT",
                    "INPUT_EXTRA_PARAM"      => $arrData,
                    "VALIDATION_TYPE"        => "text",
                    "VALIDATION_EXTRA_PARAM" => ""
                ),
            );
        } else {
            return array(
                "extension"   => array(
                    "LABEL"                  => _tr("Extension"),
                    "REQUIRED"               => "no",
                    "INPUT_TYPE"             => "TEXT",
                    "INPUT_EXTRA_PARAM"      => '',
                    "VALIDATION_TYPE"        => "text",
                    "VALIDATION_EXTRA_PARAM" => "",
                    'EDITABLE'               => 'no',
                ),
            );
        }
    }

    function loadFormEditValues($username, $id_user)
    {
        if (!isset($_POST['extension'])) {
            $_POST['extension'] = $this->_pACL->getUserExtension($username);
        }
    }

    function fetchForm($smarty, $oForm, $local_templates_dir, $pvars)
    {
        $smarty->assign('LBL_EXTENSION_FIELDS', _tr("PBX Profile"));
        return $oForm->fetchForm("$local_templates_dir/new_extension.tpl", '', $pvars);
    }

    function runPostCreateUser($smarty, $username, $id_user)
    {
        $r = $this->_pACL->setUserExtension($username,
            (trim($_POST['extension']) == '') ? NULL : trim($_POST['extension']));
        if (!$r) {
            $smarty->assign(array(
                'mb_title'  =>  'ERROR',
                'mb_message'=>  $this->_pACL->errMsg,
            ));
            return FALSE;
        }
        return TRUE;
    }

    function runPostUpdateUser($smarty, $username, $id_user, $privileged)
    {
        // Sólo el usuario con editany puede cambiar la extensión
        return $privileged ? $this->runPostCreateUser($smarty, $username, $id_user) : TRUE;
    }
}
