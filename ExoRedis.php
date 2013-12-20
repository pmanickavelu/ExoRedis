<?php
function save_data($save){
	$save_data=json_encode($save);
	`echo $save_data > save.data`;
}

$store=json_decode(file_get_contents("save.data"));
do{
	$command=fgets(STDIN);
	$command=str_replace("\n","",$command);
	$command_tmp=strtoupper($command);
	if($command_tmp!="EXIT" && $command_tmp!="SAVE"){
		list($command,$pas_data) = split(' ',$command,2);
	}
		$command=strtoupper($command);
		switch ($command) {
			case "GET":
				fwrite(STDOUT, ((isset($store[$pas_data]))?$store[$pas_data]['value']:'(nil)')."\n");
				break;
			case "SET":
				list($pas_data,$value) = split(' ',$pas_data,2);
				$store[$pas_data]['type']="var";
				$store[$pas_data]['value']=$value;
				fwrite(STDOUT, "OK\n");
				break;
			case "SETBIT":
				list($pas_data,$pos,$value) = split(' ',$pas_data,3);
				if(in_array($value,array(0,1))){
					$store[$pas_data]['type']="bit";
					$store[$pas_data]['value'][$pos]=$value;
					fwrite(STDOUT, "(integet)".$value."\n");
				}else{fwrite(STDOUT, "Invalid Input\n");}
				break;
			case "GETBIT":
				list($pas_data,$pos) = split(' ',$pas_data,2);
				fwrite(STDOUT, "(integet)".((isset($store[$pas_data]['value'][$pos]))?$store[$pas_data]['value'][$pos]:0)."\n");
				break;
			case "ZADD":
				list($pas_data,$value,$key) = split(' ',$pas_data,3);
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
				
				fwrite(STDOUT, "(integet)".$redudent."\n");
				break;
			case "ZCARD":
				fwrite(STDOUT, "(integet)".((isset($store[$pas_data]['value'])&&$store[$pas_data]['type']="array")?count($store[$pas_data]['value']):0)."\n");
				break;
			case "ZCOUNT":
				list($pas_data,$min,$max) = split(' ',$pas_data,3);
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
				fwrite(STDOUT, "(integet)".$count."\n");
				break;
			case "ZRANGE":
				list($pas_data,$min,$max) = split(' ',$pas_data,3);
				if($min<0){
					$min=(-1*($min+1));
				}
				if($max<0){
					$max=count($store[$pas_data]['value']);
				}
				for ($i=$min,$pos=1; $i < $max; $i++,$pos++) { 
					fwrite(STDOUT, $pos.") ".$store[$pas_data]['value'][$i]['key']."\n");
				}
				break;
			case "SAVE":
			case "EXIT":
				save_data($store);
				break;
			default:
				fwrite(STDOUT, "Command Not found $command please input correct command\n");
				break;
		}
}while($command_tmp!="EXIT");
save_data();
fwrite(STDOUT, "and we are out\n");
exit(0);
?>









