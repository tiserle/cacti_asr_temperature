<?php
/* do NOT run this script through a web browser */
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die('<br><strong>This script is only meant to run at the command line.</strong>');
}

global $config;

$no_http_headers = true;

/* display No errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
        include_once(dirname(__FILE__) . '/../include/global.php');
        include_once(dirname(__FILE__) . '/../lib/snmp.php');

        array_shift($_SERVER['argv']);

        print call_user_func_array('ss_cisco_temperature', $_SERVER['argv']);
} else {
        include_once($config['library_path'] . '/snmp.php');
}


function ss_cisco_temperature($hostname, $host_id, $snmp_auth, $cmd, $arg1 = '', $arg2 = '')
{
    # |host_hostname| |host_id| |host_snmp_version|:|host_snmp_port|:|host_snmp_timeout|:
    # |host_ping_retries|:|host_max_oids|:|host_snmp_community|:|host_snmp_username|:
    # |host_snmp_password|:|host_snmp_auth_protocol|:|host_snmp_priv_passphrase|:
    # |host_snmp_priv_protocol|:|host_snmp_context|

    $snmp         = explode(':', $snmp_auth);

    $host_args = array();

    $host_args['version']         = $snmp[0];
    $host_args['port']            = $snmp[1];
    $host_args['timeout']         = $snmp[2];
    $host_args['ping_retries']    = $snmp[3];
    $host_args['max_oids']        = $snmp[4];

    $host_args['auth_username']   = '';
    $host_args['auth_password']   = '';
    $host_args['auth_protocol']   = '';
    $host_args['priv_passphrase'] = '';
    $host_args['priv_protocol']   = '';
    $host_args['context']         = '';
    $host_args['community']       = '';

    if ($host_args['version'] === '3') {
        $host_args['auth_username']   = $snmp[6];
        $host_args['auth_password']   = $snmp[7];
        $host_args['auth_protocol']   = $snmp[8];
        $host_args['priv_passphrase'] = $snmp[9];
        $host_args['priv_protocol']   = $snmp[10];
        $host_args['context']         = $snmp[11];
    } else {
        $host_args['community']       = $snmp[5];
    }

    $host_args['oids'] = array(
        "entPhysicalName" => ".1.3.6.1.2.1.47.1.1.1.1.7",
        "entPhysicalDescr"   => ".1.3.6.1.2.1.47.1.1.1.1.2",
        "entSensorValue"     => ".1.3.6.1.4.1.9.9.91.1.1.1.1.4"
            );

    if (($cmd == 'index')) {
        $arr_index = ss_cisco_temperature_reindex(cacti_snmp_walk(
            $hostname,
            $host_args['community'],
            $host_args['oids']['entPhysicalName'],
            $host_args['version'],
            $host_args['auth_username'],
            $host_args['auth_password'],
            $host_args['auth_protocol'],
            $host_args['priv_passphrase'],
            $host_args['priv_protocol'],
            $host_args['context'],
            $host_args['port'],
            $host_args['timeout'],
            $host_args['ping_retries'],
            $host_args['max_oids'],
            SNMP_POLLER
        ));
        foreach ($arr_index as $index => $value) {
            print $index . "\n";
        }
    } elseif (($cmd == 'num_indexes')) {
        $arr_index = ss_cisco_temperature_reindex(cacti_snmp_walk(
            $hostname,
            $host_args['community'],
            $host_args['oids']['entPhysicalName'],
            $host_args['version'],
            $host_args['auth_username'],
            $host_args['auth_password'],
            $host_args['auth_protocol'],
            $host_args['priv_passphrase'],
            $host_args['priv_protocol'],
            $host_args['context'],
            $host_args['port'],
            $host_args['timeout'],
            $host_args['ping_retries'],
            $host_args['max_oids'],
            SNMP_POLLER
        ));
        return sizeof($arr_index);
    } elseif ($cmd == 'query') {
        switch ($arg1) {
            case "tempDesc":
                $arr = ss_cisco_temperature_desc($hostname, $host_args);
                break;

            case "entSensorValue":
                $arr = ss_cisco_temperature_reindex(cacti_snmp_walk(
                    $hostname,
                    $host_args['community'],
                    $host_args['oids'][$arg1],
                    $host_args['version'],
                    $host_args['auth_username'],
                    $host_args['auth_password'],
                    $host_args['auth_protocol'],
                    $host_args['priv_passphrase'],
                    $host_args['priv_protocol'],
                    $host_args['context'],
                    $host_args['port'],
                    $host_args['timeout'],
                    $host_args['ping_retries'],
                    $host_args['max_oids'],
                    SNMP_POLLER
                ));
                break;
        }

        foreach ($arr as $index => $value) {
            print $index.':'.$value."\n";
        }
    } elseif ($cmd == 'get') {

        $index = rtrim($arg2);

        switch ($arg1) {


            case "TempDesc":
                $arr = ss_cisco_temperature_desc($hostname, $host_args);

                if (isset($arr[$index])) {
                    return $arr[$index];
                } else {
                    cacti_log('ERROR: Invalid Return Value in ss_cisco_temperature.php for get ('.$arg1.') '.$index.' and host_id '.$host_id, false);
                    return 'U';
                }
                break;

            case "entSensorValue":
                $value = cacti_snmp_get(
                    $hostname,
                    $host_args['community'],
                    $host_args['oids'][$arg1].'.'.$index,
                    $host_args['version'],
                    $host_args['auth_username'],
                    $host_args['auth_password'],
                    $host_args['auth_protocol'],
                    $host_args['priv_passphrase'],
                    $host_args['priv_protocol'],
                    $host_args['context'],
                    $host_args['port'],
                    $host_args['timeout'],
                    $host_args['ping_retries'],
                    $host_args['max_oids'],
                    SNMP_POLLER
                );
		//print_r($value);
                if (!is_numeric($value)) {
                    cacti_log('ERROR: Invalid Return Value ['. $value .'] in ss_cisco_temperature.php for get ('.$arg1.'), Index: '.$index.' and host_id '.$host_id, false);
                }
		$value = $value/10;
                return $value;
                break;
        }

        cacti_log('ERROR: Unable to determine get type in ss_cisco_temperature.php for get ('.$arg1.') '.$index.' and host_id '.$host_id, false);
        return 'U';
    }
}

function ss_cisco_temperature_desc($hostname, $host_args)
{
    $hwidx_arr = ss_cisco_temperature_reindex(cacti_snmp_walk(
        $hostname,
        $host_args['community'],
        $host_args['oids']['entPhysicalName'],
        $host_args['version'],
        $host_args['auth_username'],
        $host_args['auth_password'],
        $host_args['auth_protocol'],
        $host_args['priv_passphrase'],
        $host_args['priv_protocol'],
        $host_args['context'],
        $host_args['port'],
        $host_args['timeout'],
        $host_args['ping_retries'],
        $host_args['max_oids'],
        SNMP_POLLER
    ));
    $hw_arr = ss_cisco_temperature_reindex(cacti_snmp_walk(
        $hostname,
        $host_args['community'],
        $host_args['oids']['entPhysicalDescr'],
        $host_args['version'],
        $host_args['auth_username'],
        $host_args['auth_password'],
        $host_args['auth_protocol'],
        $host_args['priv_passphrase'],
        $host_args['priv_protocol'],
        $host_args['context'],
        $host_args['port'],
        $host_args['timeout'],
        $host_args['ping_retries'],
        $host_args['max_oids'],
        SNMP_POLLER
    ));
    // hw_arr
    //     [25520190] => Hot Temperature SensorHotspot0
    // $hwidx_arr
    // [67070430] => temperature 0/0/1

    $return_arr = array();

    foreach ($hw_arr as $index => $hw_index) {
        if (is_numeric($index)) {
            if (isset($hw_arr[$index])) {
                //$hardware_desc = preg_replace('/ Temperature Sensor/i', ' ', $hw_arr[$index]);
	      $port_desc = preg_replace('/temperature /','',$hwidx_arr[$index]);
	      $hardware_desc = $hw_arr[$index];
	      $hardware_desc = preg_replace('/Inlet /','',$hardware_desc);
	      $hardware_desc = preg_replace('/Hot /','',$hardware_desc);
	      $hardware_desc = preg_replace('/Temperature /','',$hardware_desc);
	      $return_arr[$index] = "$port_desc $hardware_desc";
		}
         /*
                if ($hardware_desc === null) {
                  $hardware_desc = $hw_arr[$hw_index];
                }
                $return_arr[$index] = $hardware_desc;
            } else {
                $return_arr[$index] = 'Unknown CPU ' . $hw_index;
            }
	*/
        }
    }


    return $return_arr;
}

function ss_cisco_temperature_reindex($arr)
{
    //print_r($arr);
    $return_arr = array();
    for ($i=0; ($i<sizeof($arr)); $i++) {
     if(preg_match("/temperature/",$arr[$i]['value']) or preg_match("/Temperature/",$arr[$i]['value']) )
     {
        $index = substr($arr[$i]['oid'], strrpos($arr[$i]['oid'], '.') + 1);
        if (is_numeric($index)) {
            $return_arr[$index] = $arr[$i]['value'];
        }
     }
    }
    //print_r($return_arr);
    return $return_arr;
}
