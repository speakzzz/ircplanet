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

	$chan_name = $pargs[1];
	$reg = $this->getChannelReg($chan_name);

	if (!$reg) {
		$bot->noticef($user, "Channel %s is not registered.", $chan_name);
		return false;
	}

	// Permissions: Services Admin (Level 800+) OR Channel Founder (Level 500)
	$is_admin = ($this->getAdminLevel($user) >= 800);
	$user_level = $this->getChannelLevel($chan_name, $user);
	$is_founder = ($user_level >= 500);

	if (!$is_admin && !$is_founder) {
		$bot->notice($user, "You do not have permission to drop this channel.");
		return false;
	}

	$chan_id = $reg->getId();
	$name = $reg->getName();

	// 1. Delete from Database using Prepared Statements
	// This removes access lists, bans, and the channel record itself
	db_query("DELETE FROM channel_access WHERE chan_id = ?", [$chan_id]);
	db_query("DELETE FROM channel_bans WHERE chan_id = ?", [$chan_id]);
	db_query("DELETE FROM channels WHERE channel_id = ?", [$chan_id]);

	// 2. Remove from Memory
	$this->removeChannelReg($name);

	// 3. Bot Leaves Channel
	$bot->part($name, "Channel dropped by " . $user->getNick());

	// 4. Clean up modes (remove +r flag if channel exists)
	if ($chan = $this->getChannel($name)) {
		$this->mode($name, '-r');
	}

	$bot->noticef($user, "Channel %s has been dropped.", $name);
	
	// Log to Wallops if an Admin (who isn't the founder) dropped it
	if ($is_admin && !$is_founder) {
		$this->sendf(FMT_WALLOPS, sprintf("Channel %s dropped by administrator %s", $name, $user->getNick()));
	}
?>
