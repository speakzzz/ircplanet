<?php
/*
 * IRCPlanet Services for ircu
 * Copyright (c) 2025 Felix Alcantara.
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

	// Syntax: SUSPEND <channel> <reason>
	if ($cmd_num_args < 2) {
		$bot->notice($user, "Syntax: SUSPEND <channel> <reason>");
		return false;
	}

	$chan_name = $pargs[1];
	$reason = assemble($pargs, 2);
	
	// 1. Verify Registration
	$reg = $this->getChannelReg($chan_name);
	if (!$reg) {
		$bot->noticef($user, "Channel %s is not registered.", $chan_name);
		return false;
	}
	
	// 2. Verify Admin Access (Level 800+)
	if ($this->getAdminLevel($user) < 800) {
		$bot->notice($user, "You do not have permission to suspend channels.");
		return false;
	}
	
	// 3. Check Status (Prevent toggling)
	if ($reg->isSuspended()) {
		$bot->noticef($user, "Channel %s is already suspended.", $chan_name);
		return false;
	}
	
	// 4. Perform Suspension
	$reg->setSuspend(true);
	$reg->save();
	
	// 5. Action: Kick bot out and lock down
	$bot->part($chan_name, "Channel Suspended by " . $user->getNick() . ": " . $reason);
	$this->clearModes($chan_name);
	
	$bot->noticef($user, "Channel %s has been suspended.", $chan_name);
	
	// Log to Wallops
	$this->sendf(FMT_WALLOPS, SERVER_NUM, sprintf("Channel %s suspended by administrator %s (%s)", 
		$chan_name, $user->getNick(), $reason));
?>
