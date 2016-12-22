<center>
	<a href='?page=all'>Все звонки</a> | <a href='?page=phonebook'>Телефонная книга</a> | <a href='?page=sms'>SMS</a> | <a href='?page=ussd'>USSD</a>
	<br>
	<?php echo $this->phone_num_table; ?>
	<br>
<table cellspacing="2" border="1" cellpadding="5" >
	<tr><td width="80" ><center>Номер</center></td>
	    <td width="180"><center>Имя</center></td>
	    <td width="150"><center>Время звонка</center></td>
	    <td width="80"><center>Статус</center></td>
	    <td width="80"><center>Тип</center></td>
	    <td width="120"><center>Длительность</center></td>
	    <td><center>Слушать</center></td></tr>
	<?php echo $this->table; ?>
</table>
<br>
<?php echo $this->next; ?>
<br>
</center>
