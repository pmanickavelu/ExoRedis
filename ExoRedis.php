<?php
function save_data($save){
    $save_data=json_encode($save);
    `echo $save_data > save.data`;
}
$store=json_decode(file_get_contents("save.data"));

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
$address = 'localhost';$port = 10001;

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
}
$i = 0;

do {
    if (($msgsock = socket_accept($sock)) === false) {
        echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        break;
    }
    do {
        if (false === ($command = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
            echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . "\n";
            break 2;
        }
        if (!$command = trim($command)) {
            continue;
        }
        $org_cmd=str_replace("\n","",$command);
        $command=str_replace("\n","",$command);
        $command_tmp=strtoupper($command);
        if($command_tmp!="EXIT" && $command_tmp!="SAVE"){
            list($command,$pas_data) = preg_split('/[\s]+/',$command,2);
        }
        $command=strtoupper($command);
        switch ($command) {
            case "GET":
                $talkback = ((isset($store[$pas_data]))?$store[$pas_data]['value']:'(nil)')."\n";
                break;
            case "SET":
                list($pas_data,$value) = preg_split('/[\s]+/',$pas_data,2);
                $store[$pas_data]['type']="var";
                $store[$pas_data]['value']=$value;
                $talkback = "OK\n";
                break;
            case "SETBIT":
                list($pas_data,$pos,$value) = preg_split('/[\s]+/',$pas_data,3);
                if(in_array($value,array(0,1))){
                    $store[$pas_data]['type']="bit";
                    $store[$pas_data]['value'][$pos]=$value;
                    $talkback = "(integet)".$value."\n";
                }else{$talkback = "Invalid Input\n";}
                break;
            case "GETBIT":
                list($pas_data,$pos) = preg_split('/[\s]+/',$pas_data,2);
                $talkback = "(integet)".((isset($store[$pas_data]['value'][$pos]))?$store[$pas_data]['value'][$pos]:0)."\n";
                break;
            case "ZADD":
                list($pas_data,$value,$key) = preg_split('/[\s]+/',$pas_data,3);
                $store[$pas_data]['type']="array";
                $redudent=1;
                if(count($store[$pas_data])>0){
                    $tmp=array();
                    $place=0;
                    foreach ($store[$pas_data]['value'] as $subset) {
                        if($subset['value']>$value && $place==0){
                            $tmp[]=array('key'=>$key,'value'=>$value);
                            $place=1;
                        }
                        if($subset['key']!=$key){
                            $tmp[]=$subset;
                        }else{
                            $redudent=0;
                        }
                    }
                    if($place==0){
                        $tmp[]=array('key'=>$key,'value'=>$value);
                    }
                    $store[$pas_data]['value']=$tmp;
                }else{
                    $store[$pas_data]['value']=array(array('key'=>$key,'value'=>$value));
                }
                
                $talkback = "(integet)".$redudent."\n";
                break;
            case "ZCARD":
                $talkback = "(integet)".((isset($store[$pas_data]['value'])&&$store[$pas_data]['type']="array")?count($store[$pas_data]['value']):0)."\n";
                break;
            case "ZCOUNT":
                list($pas_data,$min,$max) = preg_split('/[\s]+/',$pas_data,3);
                $count=0;
                if(isset($store[$pas_data]) && $store[$pas_data]['type']=='array'){
                    if($min=='-inf' && $max=='+inf'){
                        $count=count($store[$pas_data]['value']);
                    }elseif ($min=='-inf') {
                        foreach ($store[$pas_data]['value'] as $value) {
                            if($value['value']<$max){
                                $count++;
                            }
                        }
                    }elseif ($max=='+inf') {
                        foreach ($store[$pas_data]['value'] as $value) {
                            if($value['value']>$min){
                                $count++;
                            }
                        }
                    }else{
                        foreach ($store[$pas_data]['value'] as $value) {
                            if($value['value']<$min&&$value['value']<$max){
                                $count++;
                            }
                        }
                    }
                }
                $talkback = "(integet)".$count."\n";
                break;
            case "ZRANGE":
                list($pas_data,$min,$max) = preg_split('/[\s]+/',$pas_data,3);
                if($min<0){
                    $min=(-1*($min+1));
                }
                if($max<0){
                    $max=count($store[$pas_data]['value']);
                }
                for ($i=$min,$pos=1; $i < $max; $i++,$pos++) { 
                    $talkback = $pos.") ".$store[$pas_data]['value'][$i]['key']."\n";
                }
                break;
            case "SAVE":
                save_data($store);
                break;
            case "EXIT":
                save_data($store);
                socket_close($msgsock);
                break 3;
            default:
                $talkback = "Command Not found $org_cmd please input correct command\n";
                break;
        }
        socket_write($msgsock, $talkback, strlen($talkback));
        echo "$org_cmd\n";
    } while (true);
    socket_close($msgsock);
} while (true);
socket_close($sock);
?>