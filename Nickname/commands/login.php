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

	/**
	 * SECURITY CHECK: Force secure syntax
	 * This prevents users from logging in via simple /msg N.
	 * Requires that the P10 handler correctly detects the '@' syntax.
	 */
	if (!isset($is_secure) || !$is_secure) {
		$bot->notice($user, "For security reasons, you must log in using the secure syntax:");
		$bot->noticef($user, "/msg %s@%s LOGIN <account> <password>", $bot->getNick(), SERVER_NAME);
		return false;
	}
	
	if ($cmd_num_args == 1) {
		$user_name = $user->getNick();
		$password = $pargs[1];
	}
	else {
		$user_name = $pargs[1];
		$password = $pargs[2];
	}
	
	if ($account = $this->getAccount($user_name)) {
		$stored_pass = $account->getPassword();
		$login_success = false;
		$rehash_needed = false;

		// 1. Check Modern Hash (Bcrypt/Argon2)
		if (password_verify($password, $stored_pass)) {
			$login_success = true;
			// Check if the hash needs updating (e.g., algorithm changed or cost increased)
			if (password_needs_rehash($stored_pass, PASSWORD_DEFAULT)) {
				$rehash_needed = true;
			}
		}
		// 2. Check Legacy Hash (MD5) - MIGRATION LOGIC
		// MD5 hashes are always 32 hex characters. If it matches, we migrate it.
		elseif (strlen($stored_pass) === 32 && md5($password) === $stored_pass) {
			$login_success = true;
			$rehash_needed = true; // Force upgrade to secure hash
		}

		if ($login_success) {
			if ($account->isSuspended()) {
				$bot->noticef($user, "Your account is suspended.");
				return false;
			}
			elseif ($user->isLoggedIn()) {
				$bot->notice($user, "You are already logged in as ". $user->getAccountName() ."!");
				return false;
			}

			// Perform Automatic Migration or Rehash
			if ($rehash_needed) {
				$new_hash = password_hash($password, PASSWORD_DEFAULT);
				$account->setPassword($new_hash);
				$account->save();
				// debug("Security: Migrated/Rehashed password for user " . $account->getName());
			}

			$user_name = $account->getName();
			$bot->notice($user, "Authentication successful as $user_name!");
			
			/**
			 * Always send the AC token last as it will activate the default hidden host
			 * unless a fakehost is already set.
			 */
			if ($account->hasFakehost()) {
				$this->sendf(FMT_FAKEHOST, SERVER_NUM, $user->getNumeric(), $account->getFakehost());
				
				if (!$user->hasMode(UMODE_HIDDENHOST)) {
					$bot->noticef($user, 'Enable user mode +x (/mode %s +x) in order to cloak your host.',
						$user->getNick());
				}
			}

			$this->sendf(FMT_ACCOUNT, SERVER_NUM, $user->getNumeric(), $user_name, $account->getRegisterTs());
			$user->setAccountName($user_name);
			$user->setAccountId($account->getId());
		}
		else {
			$bot->notice($user, "Invalid password!");
		}
	}
	else {
		$bot->notice($user, "No such account!");
	}
?>
