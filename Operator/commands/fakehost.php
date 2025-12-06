<?php
/*
 * ircPlanet Services for ircu
 * Copyright (c) 2005 Brian Cline.
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:

 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. Neither the name of ircPlanet nor the names of its contributors may be
 *    used to endorse or promote products derived from this software without 
 *    specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
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

	if ($cmd_num_args < 2) {
		$bot->notice($user, "Syntax: FAKEHOST <nick> <host>");
		return false;
	}

	$target_name = $pargs[1];
	$host = $pargs[2];

	// 1. Find the account
	$account = $this->getAccount($target_name);
	if (!$account) {
		$bot->noticef($user, "Account %s does not exist.", $target_name);
		return false;
	}

	// 2. Validate Host Length
	if (strlen($host) > 63) {
		$bot->notice($user, "Fakehost is too long (Max 63 chars).");
		return false;
	}

	// 3. Save to Database
	// This uses the updated DB_User class which uses PDO prepared statements internally via save()
	$account->setFakehost($host);
	$account->save();

	// 4. Apply to live user if online
	$target_user = $this->getUserByNick($target_name);
	
	// If looking up by nick failed, search by account ID (user might be on a different nick)
	if (!$target_user) {
		foreach ($this->users as $u) {
			if ($u->getAccountId() == $account->getId()) {
				$target_user = $u;
				break;
			}
		}
	}

	if ($target_user) {
		// Update memory
		$target_user->setFakehost($host);

		// Broadcast change to network (FMT_FAKEHOST = "%s FA %s %s")
		$this->sendf(FMT_FAKEHOST, SERVER_NUM, $target_user->getNumeric(), $host);
		
		// Notify User
		$bot->noticef($target_user, "Your vHost has been changed to: %s", $host);
		
		if (!$target_user->hasMode(UMODE_HIDDENHOST)) {
			$bot->notice($target_user, "Type /mode " . $target_user->getNick() . " +x to activate it.");
		}
	}

	$bot->noticef($user, "Updated vHost for account %s.", $account->getName());
	
	// Log to Wallops
	$this->sendf(FMT_WALLOPS, SERVER_NUM, sprintf("vHost for %s changed to %s by %s", 
		$account->getName(), $host, $user->getNick()));
?>
