<?php
/*
 * IRCPlanet Services for ircu
 * Copyright (c) 2005 Brian Cline.
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
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

	$target = $pargs[1];
	$account = $this->getAccount($target);
	
	if (!$account) {
		$bot->noticef($user, 'Account %s does not exist.', $target);
		return false;
	}
	
	$target_level = $this->getUserLevel($account);
	
	if ($this->getUserLevel($user) <= $target_level) {
		$bot->notice($user, 'You cannot remove a user with a level equal to or higher than your own.');
		return false;
	}

	// Modernization: Use db_escape
	$safe_id = db_escape($account->getId());
	
	// Delete from ss_admins
	db_query("DELETE FROM ss_admins WHERE user_id = '$safe_id'");
	
	if ($this->getUserLevel($account) == 0) {
		$bot->noticef($user, 'Removed %s from the %s admin list.', 
			$account->getName(), $this->getNick());
	}
	else {
		$bot->noticef($user, 'Failed to remove %s from the admin list (or they were not an admin).', 
			$account->getName());
	}
?>
