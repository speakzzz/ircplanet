<?php
/*
 * IRCPlanet Services for ircu
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

	$target = $pargs[1];
	$level = $pargs[2];
	
	if (!is_numeric($level)) {
		$bot->notice($user, 'Level must be a number.');
		return false;
	}
	
	$account = $this->getAccount($target);
	
	if (!$account) {
		$bot->noticef($user, 'Account %s does not exist.', $target);
		return false;
	}
	
	// Security Check: Prevent adding users with equal or higher access
	if ($this->getUserLevel($user) <= $level) {
		$bot->notice($user, 'You cannot add a user with a level equal to or higher than your own.');
		return false;
	}

	// Modernization: Use db_escape for safety
	$safe_id = db_escape($account->getId());
	$safe_level = db_escape($level);
	
	// Insert or Update (Defense Service Admins)
	db_query("INSERT INTO ds_admins (user_id, level) VALUES ('$safe_id', '$safe_level') 
	          ON DUPLICATE KEY UPDATE level='$safe_level'");

	$bot->noticef($user, 'Added %s to the %s admin list with level %d.', 
		$account->getName(), $this->getNick(), $level);
?>
