#!/usr/bin/php-cgi -q
<?php

class MyDB extends SQLite3
{
    function __construct($filename)
    {		
		$this->path = "/www/";
		$this->filename = $filename;
		
		if(!file_exists($this->path.$filename) && $filename == 'phonebook.db'){
			$this->create_phonebook_db();
		}else{		
			$this->open($this->path.$filename);
		}
    }
    
    public function doit($sql)
    {
		$result = $this->query($sql);
    
		 $row = array(); 
		 $i=0;
		 
		 while($res = $result->fetchArray(SQLITE3_ASSOC)){ 
				  $row[$i] = $res;               
				  $i++; 
		 } 
		 
		 return $row;
	}
	
	private function create_phonebook_db()
    {
		$this->open($this->path.'phonebook.db');
		$sql="CREATE TABLE phonebook(
            id INTEGER PRIMARY KEY  AUTOINCREMENT,
            phone_num TEXT,
            name TEXT,
            blacklist INTEGER,
            last_call DATATIME             
        )";	
        $this->query($sql);
        
        $sql="CREATE TABLE sms(
            id INTEGER PRIMARY KEY  AUTOINCREMENT,
            phone_num TEXT,
            sms_text TEXT,            
            sms_time DATATIME             
        )";	
        $this->query($sql);
        
        $sql="CREATE TABLE ussd(
            id INTEGER PRIMARY KEY  AUTOINCREMENT,
            phone_num TEXT,
            sms_text TEXT,            
            sms_time DATATIME             
        )";	
        $this->query($sql);
	}
}

function show_date_from_base($date)
{
	$time = strtotime($date);
	$time+=7200;
	return date('d.m.Y в H:i:s',$time);
}
 
    set_time_limit(0);
    require('phpagi.php'); # специальная библиотека для удобства работы с AGI

    $agi = new AGI();
	$db = new MyDB('phonebook.db');    
    
    $now = date('Y-m-d H:i:s');
    $cid = $agi->request['agi_callerid'];
    
	if(substr($cid,0,1) == "+") $cid = substr($cid,1); //убираем +
	if(strlen($cid) > 10 && substr($cid,0,3) == "380") $cid = substr($cid,2); //заменяем 380ХХ на 0ХХ
	
	$agi->set_variable("CDR(call_num)", $cid);
	
	if($cid == "553470"){
		$db_cdr = new MyDB('master.db');
		$sql="select * from cdr where call_num != 'sms_ussd' AND channel NOT LIKE '%ussd%' AND dist = 'in' GROUP BY call_num ORDER BY calldate DESC LIMIT 0,5";
		$calls = $db_cdr->doit($sql);    	
		
		$agi->answer();
		$agi->send_text("_____________\n");
		$agi->send_text("_____________\n");
		for($n = 0; $n < count($calls);$n++){			
			$agi->send_text($calls[$n]['call_num']."\n");
			$agi->send_text("звонил ".show_date_from_base($calls[$n]['calldate'])."\n");
		}
		$agi->wait_for_digit(10000);
		
	}
	
	$sql="select * from phonebook where phone_num = '".$cid."'";
    $book = $db->doit($sql);    
    
    if(count($book) == 0)
    {    
		$agi->exec_dial("SIP/221&SIP/222&SIP/33"); # ну и собственно звонок
		$agi->hangup(); # конец звонка
		if(trim($cid)!=""){
			$sql="insert into phonebook( phone_num, name, blacklist, last_call) VALUES ('".$cid."', '', '0', '".$now."')";
			$db->exec($sql);	
		}	
	}else{
		if($book[0]['blacklist'] == 0)
		{
			$agi->exec_dial("SIP/221&SIP/222&SIP/33"); # ну и собственно звонок
			$agi->hangup(); # конец звонка
			if(trim($cid)!=""){
				$sql="UPDATE phonebook SET last_call = '".$now."' where phone_num = '".$cid."'";
				$db->exec($sql);
			}
		}else{
			$agi->hangup(); # конец звонка
			if(trim($cid)!=""){
				$sql="UPDATE phonebook SET last_call = '".$now."' where phone_num = '".$cid."'";
				$db->exec($sql);
			}
		}		
	}
 
?>
