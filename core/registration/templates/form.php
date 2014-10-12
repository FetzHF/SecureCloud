<?php
	require_once('core/registration/ip.php');
	if(!fnmatch("$iprange", $_SERVER['REMOTE_ADDR'])){
		echo 	"<p class='info'>sorry. you are not allowed to register <br> <a href='../../'>back to login page</a></p>";
		return;
	 } 
?>

<form action="" method="post">
	<fieldset>
		<?php if ( $_['errormsgs'] ) {?>
		<div class="errors">
<?php foreach ( $_['errormsgs'] as $errormsg ) {
	echo "<p>$errormsg</p>";
} ?>
		</div>
		<?php } ?>
		
		<p class='info'><?php print_unescaped($l->t('Choose a unique username and password')); ?></p>
		<p class="infield grouptop">
		<input style="width: 223px !important;	padding-left: 1.8em;" type="email" name="email" id="email" value="<?php echo $_['email']; ?>" disabled />
		<label for="email" class="infield"><?php echo $_['email']; ?></label>
		<img style="position:absolute; left:1.25em; top:1.65em;-ms-filter:'progid:DXImageTransform.Microsoft.Alpha(Opacity=30)'; filter:alpha(opacity=30); opacity:.3;" class="svg" src="<?php print_unescaped(image_path('', 'actions/mail.svg')); ?>" alt=""/>
		</p>

		<p class="infield groupmiddle">
		<input type="text" name="user" id="user" value="<?php echo $_['entered_data']['user']; ?>" />
		<label for="user" class="infield"><?php print_unescaped($l->t( 'Username' )); ?></label>
		<img class="svg" src="<?php print_unescaped(image_path('', 'actions/user.svg')); ?>" alt=""/>
		</p>


		
		
		<p class="infield groupbottom">
			<input type="password" name="password" id="password" value="" data-typetoggle="#show" placeholder="" required="" original-title="" style="display: inline-block;"><input type="text" name="password-clone" tabindex="0" autocomplete="off" style="display: none;" original-title="">
			<label for="password" class="infield"><?php print_unescaped($l->t( 'Password' )); ?></label>
			<img id="password-icon" class="svg" src="<?php print_unescaped(image_path('', 'actions/password.svg')); ?>" alt=""/>
			
			
			<input type="hidden" id="groups" name="groups" value="users">
		</p>
		<input type="submit" id="submit"  class="login primary" value="<?php print_unescaped($l->t('Create account')); ?>" />
	</fieldset>
</form>
