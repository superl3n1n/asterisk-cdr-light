<center>
	<a href='?page=all'>Все звонки</a> | <a href='?page=phonebook'>Телефонная книга</a> | <a href='?page=phonebook&blacklist=1'>Черный список</a>  | <a href='?page=sms'>SMS</a>  | <a href='?page=ussd'>USSD</a>
	<br>
<table cellspacing="2" border="1" cellpadding="5" >
	<tr><td>ID</td><td>Номер</td><td><a href='?page=phonebook&sort=name'>Имя</a></td><td><a href='?page=phonebook&sort=last_call'>Последний звонок</a></td><td>Действие</td></tr>
	<?php echo $this->table; ?>
</table>
</center>
