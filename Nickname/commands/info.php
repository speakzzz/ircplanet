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
	
	$target_name = $pargs[1];
	$account = $this->getAccount($target_name);
	
	if (!$account) {
		$bot->noticef($user, 'Account %s does not exist.', $target_name);
		return false;
	}
	
	$registered = date('M j Y H:i:s', $account->getRegisterTs());
	$last_seen = 'Never';
	if ($account->getLastseenTs() > 0)
		$last_seen = date('M j Y H:i:s', $account->getLastseenTs());
		
	$bot->noticef($user, "%sAccount info for %s:%s", BOLD_START, $account->getName(), BOLD_END);
	$bot->noticef($user, "  Registered: %s", $registered);
	$bot->noticef($user, "  Last Seen:  %s", $last_seen);
	
	// Only show email to admins (Level >= 500) or the user themselves
	$is_owner = ($user->isLoggedIn() && $user->getAccountId() == $account->getId());
	$is_admin = ($this->getUserLevel($user) >= 500);
	
	if ($is_owner || $is_admin) {
		$bot->noticef($user, "  E-mail:     %s", $account->getEmail());
	}
	
	if ($account->hasInfoLine())
		$bot->noticef($user, "  Info:       %s", $account->getInfoLine());
		
	// Handle Flags
	$flags = array();
	if ($account->isSuspended()) $flags[] = "Suspended";
	if ($account->isPermanent()) $flags[] = "NoPurge";
	if ($account->autoOps())     $flags[] = "AutoOp";
	if ($account->autoVoices())  $flags[] = "AutoVoice";
	
	if (!empty($flags))
		$bot->noticef($user, "  Flags:      %s", implode(', ', $flags));
?>
