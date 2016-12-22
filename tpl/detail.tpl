<form class="contactform" action="?page=save_phone"  method="post">

<div class="pricing" style="padding: 5px;">
<table>
<input class="form-control" type="hidden" name="id" value="<?php echo $this->id; ?>">
<tr><td>Номер</td><td><?php echo $this->phone_num; ?></td></tr>
<tr><td>Имя</td><td><input class="form-control" type="text" name="name" value="<?php echo $this->name; ?>"></td></tr>
<tr><td>Черный список</td><td><input type="checkbox" name="blacklist" value="1" <?php echo $this->blacklist; ?>></td></tr>

</table>



</div>

<div class="cta-bar">
	<div class="container">
		<div class="row">
			<div class="col-sm-4 form-group"><input class="btn btn-main btn-default" type="submit"></div>	

		</div>

	</div>
</div>
</form>



