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
	$chan = $this->getChannelReg($chan_name);

	if (!$chan) {
		$bot->noticef($user, '%s is not a registered channel.', $chan_name);
		return false;
	}

	$registered = date('M j Y', $chan->getRegisterTs());
	$last_active = 'Never';

	if ($chan->getLastActivityTime() > 0)
		$last_active = date('M j Y H:i:s', $chan->getLastActivityTime());

	$bot->noticef($user, "%sChannel info for %s:%s", BOLD_START, $chan->getName(), BOLD_END);
	$bot->noticef($user, "  Registered: %s", $registered);
	$bot->noticef($user, "  Last Active: %s", $last_active);
	$bot->noticef($user, "  Purpose: %s", $chan->getPurpose());

	if ($chan->getUrl())
		$bot->noticef($user, "  URL: %s", $chan->getUrl());

	$flags = array();

	// FIX: Handle Suspension Reason display
	if ($chan->isSuspended()) {
		$flags[] = "Suspended";
		$suspend_reason = $chan->getSuspendReason();

		// Display the reason immediately if suspended
		if (!empty($suspend_reason)) {
			$bot->noticef($user, "  Suspend Reason: %s", $suspend_reason);
		}
	}

	if ($chan->isPermanent()) $flags[] = "NoPurge";
	if ($chan->topicLock())   $flags[] = "TopicLock";
	if ($chan->isPrivate())   $flags[] = "Private";
	if ($chan->isSecret())    $flags[] = "Secret";

	if (!empty($flags))
		$bot->noticef($user, "  Flags: %s", implode(', ', $flags));

	// Fix: DB_Channel uses getDefaultModes(), not getModes()
	$bot->noticef($user, "  Options: %s", $chan->getDefaultModes());
?>
