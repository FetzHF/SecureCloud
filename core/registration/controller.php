<?php
class OC_Core_Registration_Controller {
	protected static function displayRegisterPage($errormsg, $entered) {
		OC_Template::printGuestPage('core/registration', 'register',
			array('errormsg' => $errormsg,
				'entered' => $entered));
	}

	/**
	 * @brief Renders the registration form
	 * @param $errormsgs numeric array containing error messages to displey
	 * @param $entered_data associative array containing previously entered data by user
	 * @param $email User email
	 */
	protected static function displayRegisterForm($errormsgs, $entered_data, $email) {
		OC_Template::printGuestPage('core/registration', 'form',
			array('errormsgs' => $errormsgs,
			'entered_data' => $entered_data,
			'email' => $email ));
	}

	public static function index($args) {
		self::displayRegisterPage(false, false);
	}


	/**
	 * @brief Send registration email to given address (check if regrequest or user with this email exists )
	 */
	public static function sendEmail($args) {
		$l = OC_L10N::get('core');

		if ( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ) {
			self::displayRegisterPage($l->t('Email address you entered is not valid'), true);
			return;
		}
		$email = $_POST['email'];
		
		// Check if user with this email already exists
		$query = OC_DB::prepare('SELECT * FROM `*PREFIX*preferences` WHERE `configvalue` = ? ');
		$values=$query->execute(array($email))->fetchAll();
		$existing_email=(count($values)>0);

		if ( $existing_email ) {
			self::displayRegisterPage($l->t('A user with this email address already exists!'), true);
			return;
		}
	
		$token = self::savePendingRegistration($_POST['email']);
			
		if ( $token === false ) {
			//pending registration already exists in databasetable pending_regist
			self::displayRegisterPage($l->t('There is already a pending registration with this email'), true);
			return;
		} 
		elseif ( strlen($token) === 64 ) {
			$link = OC_Helper::linkToRoute('core_registration_register_form',
				array('token' => $token));
			$link = OC_Helper::makeURLAbsolute($link);
			$from = OCP\Util::getDefaultEmailAddress('register');
			$tmpl = new OC_Template('core/registration', 'email');
			$tmpl->assign('link', $link, false);
			$msg = $tmpl->fetchPage();
			try {
                            OC_Mail::send($_POST['email'], 'ownCloud User', $l->t('Verify your ownCloud registration request'), $msg, $from, 'ownCloud');
			} catch (Exception $e) {
				OC_Template::printErrorPage( 'A problem occurs during sending the e-mail please contact your administrator.');
			}
			self::displayRegisterPage('', true);
		}
	}





	public static function registerForm($args) {
		$l = OC_L10N::get('core');
		$email = self::verifyToken($args['token']);

		if ( $email !== false ) {
			self::displayRegisterForm(array(), array(), $email);
		} else {
			self::displayRegisterPage($l->t('Your registration request has expired or already been used, please make a new request below.'), false);
		}
	}



	/**
	 * @brief Create Useraccaunt (set email address in preferences/settings, delete registration request)
	 */

	public static function createAccount($args) {
		$l = OC_L10N::get('core');
		$email = self::verifyToken($args['token']);

		if ( $email !== false ) {
			$query = OC_DB::prepare('SELECT `requested` FROM `*PREFIX*pending_regist` WHERE `email` = ? ');
			$requested = $query->execute(array($email))->fetchOne();
			
			if ( time() - $requested > 86400 ) { // expired - delete from database
					$query = OC_DB::prepare('DELETE FROM `*PREFIX*pending_regist` WHERE `email` = ? ');
					$deleted = $query->execute(array($email));
					self::displayRegisterPage($l->t('Your registration request has expired, please make a new request below.'), false);
			} 
			else {
				try {
					OC_User::createUser($_POST['user'], $_POST['password']);    //create user now
					OC_Group::addToGroup($_POST['user'] , 'selfregistered' );			//create default group for new selfregistered users
				} catch (\Exception $e) {
					self::displayRegisterForm(array($e->getMessage()), $_POST, $email);
					$caught = true;
				}
					// if successfully created the user - set preferences-settings-email to the given email adress (for lostpassword and userwiththisemail already exists check )
				if (!$caught) {
					OC_Preferences::setValue($_POST['user'], "settings", "email", "$email");
					OC_Template::printGuestPage('core/registration', 'message',
					array('success' => "you did it"));
					// delete request after account created
					$query = OC_DB::prepare('DELETE FROM `*PREFIX*pending_regist` WHERE `email` = ? ');
					$deleted = $query->execute(array($email));
				}

				
			}
		} else {
			self::displayRegisterPage($l->t('Your registration request has expired or already been used, please make a new request below.'), false);
		}
	}



	/**
	 * @brief Save a registration request to database
	 * @param string $email Request from this email
	 * @return false if a request with the email already exists, returns the generated token when success
	 */
	public static function savePendingRegistration($email) {
		// Check if the email does exist
		$query = OC_DB::prepare('SELECT `email` FROM `*PREFIX*pending_regist` WHERE `email` = ? ');
		$values=$query->execute(array($email))->fetchAll();
		$exists=(count($values)>0);
		if ( $exists ) {
			return false;
		} else {
			$query = OC_DB::prepare( 'INSERT INTO `*PREFIX*pending_regist`'
				.' ( `email`, `token`, `requested`) VALUES( ?, ?, ? )' );
	
			$token = hash('sha256', \OC_Util::generateRandomBytes(30).\OC_Config::getValue('passwordsalt', ''));
			$query->execute(array( $email, $token, time() ));
			return $token;
		}
	}



	public static function verifyToken($token) {
		$query = OC_DB::prepare('SELECT `email` FROM `*PREFIX*pending_regist` WHERE `token` = ? ');
		$email = $query->execute(array($token))->fetchOne();
		return OC_DB::isError($email) ? false : $email;
	}
}

?>
