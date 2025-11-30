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

	// Syntax: /msg N CONFIRMPASS <code> <new_password>
	if ($cmd_num_args < 2) {
		$bot->notice($user, "Syntax: CONFIRMPASS <code> <new_password>");
		return false;
	}

	$code = $pargs[1];
	$new_password = $pargs[2];
	$safe_code = db_escape($code);

	// 1. Find the reset request
	$res = db_query("SELECT * FROM password_resets WHERE code = '$safe_code'");

	if ($res && $res->rowCount() > 0) {
		$row = $res->fetch(PDO::FETCH_ASSOC);
		$account_id = $row['account_id'];
		$reset_id = $row['id'];

		// 2. Load the Account
		$account = $this->getAccountById($account_id);
		
		if (!$account) {
			$bot->notice($user, "Error: Account associated with this code no longer exists.");
			db_query("DELETE FROM password_resets WHERE id = $reset_id");
			return false;
		}

		// 3. Update Password
		$password_hash = password_hash($new_password, PASSWORD_DEFAULT);
		$account->setPassword($password_hash);
		$account->save();

		// 4. Cleanup
		db_query("DELETE FROM password_resets WHERE id = $reset_id");

		$bot->notice($user, "Success! The password for " . $account->getName() . " has been changed.");
		$bot->notice($user, "You can now log in using: /msg " . $bot->getNick() . " LOGIN " . $account->getName() . " <password>");

		// Optional: Notify the user if they are currently online under a different nick
		// Note: Implementation depends on if you track users by account ID globally
	}
	else {
		$bot->notice($user, "Invalid or expired verification code.");
	}
?>
