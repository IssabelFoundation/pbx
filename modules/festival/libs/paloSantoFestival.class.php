<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
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
  $Id: paloSantoFestival.class.php, Thu 20 May 2021 08:48:27 AM EDT, nicolas@issabel.com
*/

class paloSantoFestival{
    /**
     * Description error message
     *
     * @var string
     */
    var $errMsg;

    /**
     * Constructor. It sets the attribute errMsg to an empty string
     *
     */
    function __construct()
    {
        $this->errMsg = "";
    }

    /**
     * Function that activates the festival service
     *
     * @return  int   0 if festival was activated, 1 if festival.scm was modified
     *                before activation, -1 on error.
     */
    function activateFestival()
    {
        $output = $retval = NULL;
        exec('/usr/bin/issabel-helper festival --enable 2>&1', $output, $retval);
        if ($retval != 0) {
            $this->errMsg = implode(' ', $output);
            return -1;
        }
        if (count($output) > 0 && strpos($output[0], 'Modified') !== FALSE) return 1;
        return 0;        
    }

    /**
     * Function that deactives the festival service
     *
     * @return  boolean   true if the festival service is correctly deactivated, false if not
     */
    function deactivateFestival()
    {
        $output = $retval = NULL;
        exec('/usr/bin/issabel-helper festival --disable', $output, $retval);
        return ($retval == 0);        
    }

    /**
     * Function that verifies if the festival service is running
     *
     * @return  boolean   true if the festival service is running, false if not
     */
    function isFestivalActivated()
    {
        // Se requiere usar pidof directamente en lugar de service festival status
        // https://bugzilla.redhat.com/show_bug.cgi?id=684881
        $output = $retval = NULL;
        exec('/sbin/pidof -o $$ -o $PPID -o %PPID -x festival', $output, $retval);
        foreach ($output as $linea) {
        	if (preg_match('/\d+/', $linea)) return TRUE;
        }
        return FALSE;
    }

    /**
     * Function that returns the error attribute variable of this class
     *
     * @return  string   string with the error message
     */
    function getError()
    {
        return $this->errMsg;
    }
}
?>
