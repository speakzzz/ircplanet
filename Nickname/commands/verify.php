<?php
/*
 * ircPlanet Services for ircu
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

	if ($cmd_num_args < 1) {
		$bot->notice($user, "Syntax: VERIFY <code>");
		return false;
	}

	$code = $pargs[1];
	$safe_code = db_escape($code);
	
	// Find the pending request
	$res = db_query("SELECT * FROM pending_registers WHERE code = '$safe_code'");
	
	if ($res && $res->rowCount() > 0) {
		$row = $res->fetch(PDO::FETCH_ASSOC);
		$nick = $row['nickname'];
		$pass = $row['password'];
		$email = $row['email'];
		
		// Final check to ensure nick wasn't taken while waiting
		if ($this->getAccount($nick)) {
			$bot->notice($user, "Sorry, this nickname was registered by someone else while you were waiting.");
			db_query("DELETE FROM pending_registers WHERE id = " . $row['id']);
			return false;
		}

		// Create the Real Account
		$account = new DB_User();
		$account->setName($nick);
		$account->setRegisterTs(time());
		$account->setPassword($pass); // Already hashed in register.php
		$account->setEmail($email);
		$account->setAutoOp(true);
		$account->setAutoVoice(true);
		$account->updateLastseen();
		$account->save();
		
		$this->addAccount($account);
		
		// Clean up pending table
		db_query("DELETE FROM pending_registers WHERE id = " . $row['id']);
		
		// Log the user in if they are currently using the nick
		if (strtolower($user->getNick()) == strtolower($nick)) {
			$this->sendf(FMT_ACCOUNT, SERVER_NUM, $user->getNumeric(), $nick, $account->getRegisterTs());
			$user->setAccountName($nick);
			$user->setAccountId($account->getId());
			$bot->notice($user, "Verification successful! You are now logged in.");
		} else {
			$bot->notice($user, "Verification successful! You can now login with: /msg " . $bot->getNick() . " LOGIN $nick <password>");
		}
	}
	else {
		$bot->notice($user, "Invalid or expired verification code.");
	}
?>
