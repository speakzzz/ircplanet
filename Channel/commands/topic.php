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
	$topic = "";

	// Assemble the topic string from the remaining arguments
	if ($cmd_num_args > 1) {
		$topic = assemble($pargs, 2);
	}

	// 1. Get Channel Registration from Database
	$reg = $this->getChannelReg($chan_name);
	if (!$reg) {
		$bot->noticef($user, "Channel %s is not registered.", $chan_name);
		return false;
	}

	// 2. Check User Permissions
	// Level 100 (Op) or higher is typically required to change the registered topic
	$user_level = $this->getChannelLevel($chan_name, $user);
	$is_admin = ($this->getAdminLevel($user) > 0);

	if ($user_level < 100 && !$is_admin) {
		$bot->notice($user, "You do not have permission to change the registered topic.");
		return false;
	}

	// 3. View or Update Topic
	if (empty($topic)) {
		// Display current registered topic
		$bot->noticef($user, "Topic for %s: %s", $chan_name, $reg->getLastTopic());
	} else {
		// Update Topic in Memory and Database
		$reg->setLastTopic($topic);
		$reg->setLastAutoTopicTime(time());

		// Update the 'default topic' setting so it persists
		$reg->setDefaultTopic($topic);
		$reg->save();

		// Broadcast the new topic to the IRC network
		$this->topic($chan_name, $topic);

		$bot->noticef($user, "Topic updated for %s.", $chan_name);
	}
?>
