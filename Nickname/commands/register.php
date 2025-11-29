<?php
/*
 * ircPlanet Services for ircu
 * Copyright (c) 2005 Brian Cline.
 * All rights reserved.
 * * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:

 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 * 3. Neither the name of ircPlanet nor the names of its contributors may be
 * used to endorse or promote products derived from this software without 
 * specific prior written permission.
 * * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */
	
	require_once(CORE_DIR .'/email.php');

	$numeric = $args[0];
	$user = $this->getUser($numeric);
	$user_name = $user->getNick();
	$password = $pargs[1];
	$email = $pargs[2];
	
	if (!$user->isLoggedIn()) {
		if (!isValidEmail($email)) {
			$bot->notice($user, "You have specified an invalid e-mail address. Please try again.");
			return false;
		}
		
		// Check active accounts for duplications
		if ($this->getAccountByEmail($email)) {
			$bot->notice($user, "That e-mail address is already associated with a registered nickname.");
			return false;
		}
		
		if ($this->getAccount($user_name)) {
			$bot->noticef($user,
				"The nickname %s%s%s has already been registered. Please choose another.",
				BOLD_START, $user_name, BOLD_END);
			return false;
		}

		if ($this->isBadnick($user_name)) {
			$bot->noticef($user, 'You are not allowed to register that nickname.');
			return false;
		}
		
		// Check for existing pending registration to prevent spam
		$safe_nick = db_escape($user_name);
		$res = db_query("SELECT id FROM pending_registers WHERE nickname = '$safe_nick'");
		if ($res && $res->rowCount() > 0) {
			$bot->notice($user, "There is already a pending registration for this nickname. Please check your email.");
			return false;
		}

		// Generate Verification Data
		try {
			$code = strtoupper(bin2hex(random_bytes(3))); // 6 characters
		} catch (Exception $e) {
			// Fallback for older PHP versions without random_bytes
			$code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
		}

		$password_hash = password_hash($password, PASSWORD_DEFAULT);
		$ts = time();
		
		// Prepare Database Values
		$safe_email = db_escape($email);
		$safe_pass = db_escape($password_hash);
		$safe_code = db_escape($code);
		
		// Insert Pending Registration
		$q = db_query("INSERT INTO pending_registers (nickname, password, email, code, timestamp) 
		               VALUES ('$safe_nick', '$safe_pass', '$safe_email', '$safe_code', $ts)");
		
		if (!$q) {
			$bot->notice($user, "An error occurred while processing your registration. Please try again later.");
			return false;
		}

		// Prepare Email
		$mail = new Email();
		$mail->addAddress($email);
		$mail->setSubject("Nickname Verification for $user_name");
		
		$body = "Hello,\n\n" .
		        "Someone (hopefully you) requested to register the nickname '$user_name' on " . SERVER_NAME . ".\n\n" .
		        "To complete your registration, verify your email by typing the following command on IRC:\n" .
		        "/msg " . $bot->getNick() . " VERIFY $code\n\n" .
		        "If you did not request this, please ignore this email.";
		
		$mail->setBody($body);
		
		// Send Email
		if (!$mail->send()) {
			$bot->notice($user, "Error sending verification email: " . $mail->getError());
			// Cleanup the failed pending record
			db_query("DELETE FROM pending_registers WHERE nickname = '$safe_nick'");
			return false;
		}
		
		$bot->noticef($user, "A verification code has been emailed to %s.", $email);
		$bot->noticef($user, "Type %s/msg %s VERIFY <code>%s to complete registration.", 
			BOLD_START, $bot->getNick(), BOLD_END);
	}
	else {
		$bot->notice($user, "You have already registered your nick and logged in.");
	}
?>
