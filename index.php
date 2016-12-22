<?php
require 'class.view.php';

 class MyDB extends SQLite3
{
    function __construct($filename)
    {		
		$this->path = "/www/";
		
		if($filename == 'phonebook.db' && !file_exists($this->path.$filename)){
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
			if(!empty($this->index))
			{
				$row[$res[$this->index]] = $res;
			}else{
				$row[$i] = $res;               
			}
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
	$time+=7200; // смещение времени на 2 часа
	return date('d-m-Y H:i:s',$time);
}

function show_phone_base($phone)
{
	if(strlen($phone) != 12) return $phone;
	$n1 = substr($phone,2,3);
	$n2 = substr($phone,5,3);
	$n3 = substr($phone,8,2);
	$n4 = substr($phone,10,2);
	
	return "(".$n1.") - ".$n2." - ".$n3." - ".$n4;
}

function sec_to_time($sec)
{
	$hour = floor($sec/3600);
	if($hour > 0) $sec -= $hour * 3600;
	if($hour < 10) $hour = "0".$hour;
	$minute = floor($sec/60);
	if($minute > 0) $sec -= $minute * 60;
	if($minute < 10) $minute = "0".$minute;
	if($sec < 10) $sec = "0".$sec;
	
	return $hour.":".$minute.":".$sec;
}

function show_call_table()
{
	$show_view = new View('./tpl/');
	$db_master = new MyDB('master.db');
	$db_phonebook = new MyDB('phonebook.db');
	
	if(isset($_GET['page']) && $_GET['page']=="detail"){
		$show_view->set('phone_num_table', show_phone_detail());
		if(strlen($_GET['num']) == 12){
			$num10 = substr($_GET['num'],2);
			$num11 = substr($_GET['num'],1);
			$num_in = "'".$_GET['num']."','+".$_GET['num']."','".$num10."','+".$num10."','".$num11."','+".$num11."'";
		}else{
			$num_in = "'".$_GET['num']."'";
		}
		$sql = "select * from cdr WHERE ";
		$sql .="call_num IN (".$num_in.") ";
        $sql .="order by calldate desc";
		 //echo $sql; 
	}else{
		$show_view->set('next', show_next_prev());	
		if(!isset($_GET['offset'])) $_GET['offset'] = 0;
		if($_GET['offset'] < 0 ) $_GET['offset'] = 0;
		//$sql="select * from cdr where call_num != 'sms_ussd' AND channel NOT LIKE '%ussd%' ORDER BY calldate DESC LIMIT ".$_GET['offset'].",20";
		$sql="select * from cdr where call_num != 'sms_ussd' AND channel NOT LIKE '%ussd%' GROUP BY call_num ORDER BY calldate DESC LIMIT ".$_GET['offset'].",20";
	}
    $call_list = $db_master->doit($sql);

	//print_r($call_list);
	$find_in_book = "";
	$table = "";
	
	foreach($call_list as $key => $value)
	{
		if(substr($value['call_num'],0,1) == "+") $value['call_num'] = substr($value['call_num'],1);		
		if(strlen($value['call_num']) > 10 && substr($value['call_num'],0,3) == "380") $value['call_num'] = substr($value['call_num'],2); //заменяем 380ХХ на 0ХХ
		$find_in_book .= "'".$value['call_num']."',";	
	}
	
	$find_in_book = substr($find_in_book,0,-1);
	
	$db_phonebook->index = "phone_num";
	$sql="select * from phonebook WHERE phone_num IN (".$find_in_book.")";
	
	$book = $db_phonebook->doit($sql);
	
	$green_color[0]	= "#71D873";
	$green_color[1]	= "#89F28B";
	$yellow_color[0] = "#F1F5AE";
	$yellow_color[1] = "#E5EB8A";
	$blue_color[0] = "#8AAAEB";
	$blue_color[1] = "#9BAFD7";
	
	$color_count = 0;
	
	foreach($call_list as $key => $value)	
	{
		$color_count++;
		if($color_count > 1) $color_count = 0;
		if($value['dist'] == "in"){
			 $show_color = $green_color[$color_count];
			 $type = "Входящий";
		}
		if($value['dist'] == "out"){
			 $show_color = $yellow_color[$color_count];
			 $type = "Исходящий";
		}
		if($value['dist'] == "inout"){
			 $type = "Внутренний";
			 $show_color = $blue_color[$color_count];
		 }
		
		if($value['disposition'] == "ANSWERED"){
			$status = "Принят";
		}else{
			$status = "<font color='red'>Не принят</font>";
		}		
		
		
		
		if(substr($value['call_num'],0,1) == "+") $value['call_num'] = substr($value['call_num'],1);
		$table.="<tr bgcolor='".$show_color."'><td><center><a href='?page=detail&num=".$value['call_num']."'>".show_phone_base($value['call_num'])."</a></center></td>";
		if($book[$value['call_num']]['blacklist'] == 1){
			$table.="<td><center><a href='?page=detail&num=".$value['call_num']."'>".$book[$value['call_num']]['name']."</a> (Заблокирован)</center></td>";
		}else{
			$table.="<td><center><a href='?page=detail&num=".$value['call_num']."'>".$book[$value['call_num']]['name']."</a></center></td>";
		}
		$table.="<td><center>".show_date_from_base($value['calldate'])."</center></td>";
		$table.="<td><center>".$status."</center></td>";
		$table.="<td><center>".$type."</center></td>";
		$table.="<td><center>".sec_to_time($value['billsec'])."</center></td>";
		$table.="<td><audio src='monitor/".$value['userfield'].".mp3' controls></audio></td></tr>\n";	
	}
	$show_view->set('table', $table);
	
	$show_view->display('calllist.tpl');	
}

function show_next_prev()
{
	$db_master = new MyDB('master.db');
	$sql="select COUNT(*) as total from cdr where call_num != 'sms_ussd' AND channel NOT LIKE '%ussd%' GROUP BY call_num";
	$call = $db_master->doit($sql);
	if($call[0]['total'] > 20){
		
		$page = "";
		for($i=0; $i < $call[0]['total']; $i+=20){
			$page .= "<a href='?offset=".$i."'>".($i/20+1)."</a> | ";	
		}		
		$page = substr($page,0,-2);
		if(isset($_GET['offset']) && $_GET['offset'] > 0 && (($_GET['offset']+20) < $call[0]['total'])){					
			$prev = $_GET['offset']-20;
			if($prev < 0) $prev = 0;
			$next = $_GET['offset']+20;	
			$ret = "<a href='?offset=".$prev."'>&lt;&lt;&lt; Предыдущая страница</a>||| ".$page."|||<a href='?offset=".$next."'>Следующая страница &gt;&gt;&gt;</a>";
		}else if(isset($_GET['offset']) && $_GET['offset'] > 0 && (($_GET['offset']+20) > $call[0]['total'])){
			$prev = $_GET['offset']-20;
			if($prev < 0) $prev = 0;			
			$ret = "<a href='?offset=".$prev."'>&lt;&lt;&lt; Предыдущая страница</a> ||| ".$page;
		}else{			
			$next = $_GET['offset']+20;	
			$ret = $page."|||<a href='?offset=".$next."'>Следующая страница &gt;&gt;&gt;</a>";
		}	
		return $ret;
	}
}

function show_phone_detail()
{
	$show_view = new View('./tpl/');
	$db = new MyDB('phonebook.db');
		
	if(strlen($_GET['num']) > 10 && substr($_GET['num'],0,3) == "380") $_GET['num'] = substr($_GET['num'],2); //заменяем 380ХХ на 0ХХ
	$sql="select * from phonebook where phone_num='".$_GET['num']."'";
	//echo $sql; 
    $result = $db->doit($sql);
	
	$show_view->set('id', $result[0]['id']);
	$show_view->set('phone_num', $result[0]['phone_num']);
	$show_view->set('name', $result[0]['name']);
	if($result[0]['blacklist'] == 1)
	{
		$show_view->set('blacklist', "checked");
	}
	
	return $show_view->add_tpl('detail.tpl');	
}


function save_phone()
{
	if(!isset($_POST['id'])) return;
	$db = new MyDB('phonebook.db');
	
	$sql="update phonebook set name='".$_POST['name']."', ";
	if(isset($_POST['blacklist']) && $_POST['blacklist']==1){
	    $sql .= "blacklist = '1' ";
	}else{
	    $sql .= "blacklist = '0' ";
	}
	$sql .= "where id='".$_POST['id']."'";
	$db->exec($sql);
}
	
function show_phonebook_table()
{
	$show_view = new View('./tpl/');
	$db = new MyDB('phonebook.db');
		
	$sql="select * from phonebook";
	if(isset($_GET['blacklist']) && $_GET['blacklist']=="1") $sql .= " WHERE blacklist = 1 ";	
	if(isset($_GET['sort']) && $_GET['sort']=="name")		 $sql .= " ORDER BY name";
	if(isset($_GET['sort']) && $_GET['sort']=="last_call")	 $sql .= " ORDER BY last_call DESC";
    $result = $db->doit($sql);

	$table = "";
	
	foreach($result as $key => $value)
	{
		if($value['blacklist'] == 1 && !isset($_GET['blacklist']))
		{
			$table.="<tr><td><s>".$value['id']."</s></td><td><s>".show_phone_base($value['phone_num'])."</s></td><td><s>".$value['name']."</s></td><td>".show_date_from_base($value['last_call'])."</td><td><a href='?page=detail&num=".$value['phone_num']."'>Изменить</a></td></tr>\n";
		}else{
			$table.="<tr><td>".$value['id']."</td><td>".show_phone_base($value['phone_num'])."</td><td>".$value['name']."</td><td>".show_date_from_base($value['last_call'])."</td><td><a href='?page=detail&num=".$value['phone_num']."'>Изменить</a></td></tr>\n";
		}
	}
	$show_view->set('table', $table);
	
	$show_view->display('phonebook.tpl');
	exit();
}	

function delete_sms_ussd()
{
	$db = new MyDB('phonebook.db');
	$sql="DELETE FROM sms WHERE id = ".$_GET['id'];
	$db->query($sql);
}

function show_sms_table()
{
	if(isset($_GET['do']) && $_GET['do']=="delete")delete_sms_ussd();
	$show_view = new View('./tpl/');
	$db = new MyDB('phonebook.db');
		
	if($_GET['page'] == "sms"){	
		$sql="select * from sms WHERE phone_num != 'ussd' ORDER BY sms_time DESC";
	}else{
		$sql="select * from ussd WHERE phone_num = 'ussd' ORDER BY sms_time DESC";
	}
	
    $sms = $db->doit($sql);
    
	$table = "";
	
	foreach($sms as $key => $value)
	{
		$time=strtotime($value['sms_time']);	
		$time=date('d-m-Y H:i:s',$time);
		$table.="<tr><td>".$value['phone_num']."</td><td>".$value['sms_text']."</td><td>".$time."</td><td><a href='?page=".$_GET['page']."&do=delete&id=".$value['id']."'>Удалить</a></td></tr>\n";		
	}
	$show_view->set('table', $table);
	
	$show_view->display('sms.tpl');
	exit();
}	
	
//////menu

if(isset($_GET['page']) && $_GET['page']=="phonebook")show_phonebook_table();
if(isset($_GET['page']) && $_GET['page']=="save_phone")save_phone();
if(isset($_GET['page']) && $_GET['page']=="sms")show_sms_table();
if(isset($_GET['page']) && $_GET['page']=="ussd")show_sms_table();

show_call_table();

?>
