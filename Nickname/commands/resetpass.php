<?php
/*
 * IRCPlanet Services for ircu
 * Copyright (c) 2025 Felix Alcantara.
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

	// Syntax: /msg N RESETPASS <account> <email>
	if ($cmd_num_args < 2) {
		$bot->notice($user, "Syntax: RESETPASS <account> <email>");
		return false;
	}

	$account_name = $pargs[1];
	$email_input = $pargs[2];
	
	// 1. Verify Account Exists
	$account = $this->getAccount($account_name);
	if (!$account) {
		// Security: Don't reveal if account exists or not, just give a generic error or pretend.
		// However, for IRC services, it's usually standard to say "Account not found".
		$bot->notice($user, "Account '$account_name' not found.");
		return false;
	}

	// 2. Verify Email Matches
	if (strtolower($account->getEmail()) !== strtolower($email_input)) {
		$bot->notice($user, "The email address provided does not match the one on file for this account.");
		return false;
	}

	// 3. Check for existing pending resets (Rate Limiting)
	$account_id = $account->getId();
	$res = db_query("SELECT id FROM password_resets WHERE account_id = $account_id");
	if ($res && $res->rowCount() > 0) {
		$bot->notice($user, "A password reset request is already pending for this account. Please check your email.");
		return false;
	}

	// 4. Generate Code
	try {
		$code = strtoupper(bin2hex(random_bytes(3))); // 6 char hex
	} catch (Exception $e) {
		$code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
	}
	
	$ts = time();
	$safe_code = db_escape($code);

	// 5. Save to Database
	db_query("INSERT INTO password_resets (account_id, code, timestamp) VALUES ($account_id, '$safe_code', $ts)");

	// 6. Send Email
	$mail = new Email();
	$mail->addAddress($account->getEmail());
	$mail->setSubject("Password Reset for " . $account->getName());
	
	$body = "Hello,\n\n" .
			"A password reset was requested for the account '" . $account->getName() . "' on " . SERVER_NAME . ".\n\n" .
			"To reset your password, type the following command on IRC:\n" .
			"/msg " . $bot->getNick() . " CONFIRMPASS $code <new_password>\n\n" .
			"If you did not request this, please ignore this email.";
	
	$mail->setBody($body);

	if (!$mail->send()) {
		$bot->notice($user, "Error sending email: " . $mail->getError());
		db_query("DELETE FROM password_resets WHERE account_id = $account_id");
		return false;
	}

	$bot->noticef($user, "A verification code has been emailed to %s.", $account->getEmail());
	$bot->noticef($user, "Type %s/msg %s CONFIRMPASS <code> <new_password>%s to finish.", 
		BOLD_START, $bot->getNick(), BOLD_END);
?>
