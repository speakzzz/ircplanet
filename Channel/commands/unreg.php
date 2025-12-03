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

	$chan_name = $pargs[1];

	// FIX: Check if a password was provided before accessing the array index
	$password = isset($pargs[2]) ? $pargs[2] : '';

	// 1. Verify channel exists
	$reg = $this->getChannelReg($chan_name);
	if (!$reg) {
		$bot->noticef($user, 'Channel %s is not registered.', $chan_name);
		return false;
	}

	// 2. Verify User Access (Must be Level 500 Owner)
	$user_level = $this->getChannelLevel($chan_name, $user);
	if ($user_level < 500) {
		$bot->noticef($user, 'You do not have permission to unregister %s.', $chan_name);
		return false;
	}

	// 3. Verify Password (Optional logic)
	// If you want to require the password if one is set on the channel:
	// if ($reg->getAdminPass() && $password !== $reg->getAdminPass()) {
	//    $bot->notice($user, "Invalid channel password.");
	//    return false;
	// }

	// 4. Perform Deletion
	$chan_id = $reg->getId();
	$name = $reg->getName();

	// Use PDO prepared statements to clean up relations
	db_query("DELETE FROM channel_access WHERE chan_id = ?", [$chan_id]);
	db_query("DELETE FROM channel_bans WHERE chan_id = ?", [$chan_id]);

	// Delete the channel record itself
	$reg->delete();

	// Remove from memory
	$this->removeChannelReg($name);

	// 5. Bot Leaves
	$bot->part($name, "Channel unregistered by " . $user->getNick());

	// 6. Remove modes
	if ($chan = $this->getChannel($name)) {
		$this->mode($name, '-r');
	}

	$bot->noticef($user, 'Channel %s has been unregistered.', $name);
?>
