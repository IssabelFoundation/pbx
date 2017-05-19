<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0                                                  |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
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
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  |                                                                      |
  | Create by: Kleber Loayza                                             |
  | Email: andresloa@palosanto.com                                       |
  +----------------------------------------------------------------------+
  $Id: migrationFilesMonitor.php,v 1.0 2010/10/26 09:49:00 andresloa Exp $ */
  
require_once("/var/www/html/libs/misc.lib.php");
require_once("/var/www/html/configs/default.conf.php");
require_once("/var/www/html/libs/paloSantoDB.class.php");

$dsn_conn_database = generarDSNSistema('asteriskuser', 'asteriskcdrdb',"/var/www/html/");
$pDBcdrdb = new paloDB($dsn_conn_database);

if(!is_dir("/var/spool/asterisk/monitor_migration"))
    mkdir("/var/spool/asterisk/monitor_migration"); 

if($pDBcdrdb==NULL)
    echo $pDBcdrdb->errMsg;
else
    {
       database_migrating($pDBcdrdb);
    }
function database_migrating($pDBcdrdb)
{
    if(is_dir("/var/spool/asterisk/monitor/"))
    {        
        $directorio=dir("/var/spool/asterisk/monitor/");
        while ($archivo = $directorio->read())
        {                       
           if($archivo[0]!=".")//$arreglototal[]=$archivo;   
            actualizarbasedatos($archivo,$pDBcdrdb);
        }  
        $directorio->close();
    }
}
function actualizarbasedatos($archivo,$pDBcdrdb){
    $number   ="[[:digit:]]+";
    $uniqueid ="[[:digit:]|\.]+";          
    $nombrearchivo="audio:{$archivo}";
    $query="update cdr set userfield='$nombrearchivo' ";
           // 1246577053.5428.wav
           if(preg_match("/^($uniqueid)\.[wav|WAV|gsm]/",$archivo,$regs))
           {
                    validaract($pDBcdrdb,$query,$regs[1],$archivo);
           } 
           //20090828-173404-1251498844.22224.wav
           else if(preg_match("/^$number-$number-($uniqueid)\.[wav|WAV|gsm]/",$archivo,$regs))
           {
                    validaract($pDBcdrdb,$query,$regs[1],$archivo);
           }
           //IN-408-????-????.wav 
           else if (preg_match("/^IN-($number)-($number)-($number)\.[wav|WAV|gsm]/",$archivo,$regs))//2
           {
                $fecha=substr($regs[2], 0, 4).'-'.substr($regs[2], 4, 2).'-'.substr($regs[2], 6, 2);
                $hora=substr($regs[3], 0, 2).':'.substr($regs[3], 2, 2).':'.substr($regs[3], 4, 2);
                $calldate="$fecha $hora";                 
                $query .= "WHERE calldate='$calldate'";  
                $query .= " AND dst='$regs[1]'";
                    ejecutaractualizacion($pDBcdrdb,$query,$archivo);
           }
            //IN-104-1208782232.2382.wav
           else if (preg_match("/^IN-$number-($number(\.$number)*)\.[wav|WAV|gsm]/",$archivo,$regs))//3
           {        
                //TODO: Esta validación esta bajo pruebas, es posible que no funcione  
                validaract($pDBcdrdb,$query,$regs[1],$archivo);
                
           }
           //g1-1207292249.1473.wav             
           else if (preg_match("/^g$number-($uniqueid)\.[wav|WAV|gsm]/",$archivo,$regs))//4
           {          
                validaract($pDBcdrdb,$query,$regs[1],$archivo);
           }
           //g121-20070828-162421-1188336241.1610.wav
           else if (preg_match("/^g$number-$number-$number-($uniqueid)\.[wav|WAV|gsm]/",$archivo,$regs))//5
           {             
                validaract($pDBcdrdb,$query,$regs[1],$archivo);
           }
           //OUT-104-1208782232.2382.wav 
           else if (preg_match("/^OUT-$number-($number(\.$number)*)\.[wav|WAV|gsm]/",$archivo,$regs))//6
           {             
                validaract($pDBcdrdb,$query,$regs[1],$archivo);                
           }
           //OUT405-20080620-095526-1213973725.84.wav
           else if (preg_match("/^OUT$number-$number-$number-($uniqueid)\.[wav|WAV|gsm]/",$archivo,$regs))//7
           {    
                validaract($pDBcdrdb,$query,$regs[1],$archivo);
           }
           //OUT408-???-???-
           else if (preg_match("/^OUT($number)-[(.+)|\-]*($number)-($number)\.[wav|WAV|gsm]/",$archivo,$regs))//8
           {          
                $fecha=substr($regs[2], 0, 4).'-'.substr($regs[2], 4, 2).'-'.substr($regs[2], 6, 2);
                $hora=substr($regs[3], 0, 2).':'.substr($regs[3], 2, 2).':'.substr($regs[3], 4, 2);
                $calldate="$fecha $hora";
                $query .= "WHERE calldate='$calldate'";
                $query .= " AND src='$regs[1]'";
                ejecutaractualizacion($pDBcdrdb,$query,$archivo);
           }
           //q7000-20080411-180242-1207954962.473.wav
           else if(preg_match("/^q$number-$number-$number-($uniqueid)\.[wav|WAV|gsm]/",$archivo,$regs))//9
           {          
                validaract($pDBcdrdb,$query,$regs[1],$archivo);
           }
           //q7000-20080411-162833-1207949313.9-in.wav
           else if(preg_match("/^q$number-$number-$number-($uniqueid)-in\.[wav|WAV|gsm]/",$archivo,$regs))//10
           {                
                validaract($pDBcdrdb,$query,$regs[1],$archivo);
           }
           //q7000-20080411-162833-1207949313.9-out.wav
           else if(preg_match("/^q$number-$number-$number-($uniqueid)-out\.[wav|WAV|gsm]/",$archivo,$regs))//11
           {                
                validaract($pDBcdrdb,$query,$regs[1],$archivo);
           }
           else
                `echo $archivo >> /var/spool/asterisk/monitor_migration/no_known_formatfile.log `;  
}
function  ejecutaractualizacion($pDBcdrdb,$query,$archivo)
{
    if($pDBcdrdb->genQuery($query))
       echo "updated file $archivo in database\n";
    else 
       echo "could not execute the update\n";
} 
function validaract($pDBcdrdb,$query,$regs,$archivo)
{
    $query .= "WHERE uniqueid='$regs'";    
    $query2="select count(*) valor from  cdr where uniqueid=?";
    $result = $pDBcdrdb->getFirstRowQuery($query2,true,array($regs));
    if(!$result)
    {
        echo $pDBcdrdb->errMsg;
    }    
    else
    {
        `echo "Updated $result[valor] - $archivo in database." >> /var/spool/asterisk/monitor_migration/file_valid.log `;
        if($result['valor']>0)                    
         ejecutaractualizacion($pDBcdrdb,$query,$archivo);
        else
        {    
            `echo $archivo >> /var/spool/asterisk/monitor_migration/no_found_database.log `; 
            echo "No match found in database\n";
        }
    }
}
?>
