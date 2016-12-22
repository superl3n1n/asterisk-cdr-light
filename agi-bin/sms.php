#!/usr/bin/php-cgi -q
<?php

class MyDB extends SQLite3
{
    function __construct()
    {
		$this->path = "/www/";
		$filename = 'phonebook.db';
		
		if(!file_exists($this->path.$filename)){
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

    set_time_limit(0);
    require('phpagi.php'); # специальная библиотека для удобства работы с AGI

    $agi = new AGI();
	$db = new MyDB();

	$now = date('Y-m-d H:i:s');	
	$sms = htmlspecialchars($argv[2]);
	$sql="insert into sms( phone_num, sms_text, sms_time) VALUES ('".(trim($argv[1]))."', '".(trim($sms))."', '".$now."')";
	$db->exec($sql);		
	
?>
