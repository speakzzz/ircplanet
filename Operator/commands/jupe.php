<?php
/*
 * IRCPlanet Services for ircu
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

	if ($cmd_num_args < 3) {
		$bot->notice($user, "Syntax: JUPE <server> <duration> <reason>");
		return false;
	}

	$server = $pargs[1];
	$duration_str = $pargs[2];
	$reason = assemble($pargs, 3);

	// Validate and convert duration (e.g., "1d" -> 86400)
	$duration = convertDuration($duration_str);
	if ($duration <= 0) {
		$bot->notice($user, "Invalid duration specified (e.g. use 1h, 1d, 1w).");
		return false;
	}

	// Check if Jupe already exists in memory
	if ($this->getJupe($server)) {
		$bot->noticef($user, "A Jupe for %s already exists.", $server);
		return false;
	}

	// Create and Save Database Record
	// This uses the modern DB_Jupe class we fixed earlier
	$jupe = new DB_Jupe();
	$jupe->setServer($server);
	$jupe->setReason($reason);
	$jupe->setDuration($duration);
	$jupe->setTs(time());
	$jupe->setLastMod(time());
	$jupe->setActiveState(true);
	$jupe->save();

	// Add to Service Memory
	// Note: Jupes are typically enforced via configuration or during the burst,
	// but keeping it in memory allows the service to track expiry.
	$this->addJupe($server, $duration, time(), time(), $reason, true);

	$bot->noticef($user, "Jupe added for %s (Expires in: %s)", $server, $duration_str);
	
	// Log action to network administrators
	$this->sendf(FMT_WALLOPS, sprintf("Jupe for %s added by %s (%s)", $server, $user->getNick(), $reason));
?>
