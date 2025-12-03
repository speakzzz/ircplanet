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

	if ($cmd_num_args < 1) {
		$bot->notice($user, "Syntax: SCAN <nick|ip>");
		return false;
	}

	$target = $pargs[1];
	$ip = $target;
	$found_user = false;

	// 1. Resolve Nickname to IP if possible
	$target_user = $this->getUserByNick($target);
	if ($target_user) {
		$ip = $target_user->getIp();
		$found_user = true;
		$bot->noticef($user, "Scanning user %s (IP: %s)...", $target_user->getNick(), $ip);
	} else {
		// 2. Validate if it's a raw IP
		if (!isIp($target)) {
			$bot->notice($user, "Invalid target. Please specify a valid nickname or an IP address.");
			return false;
		}
		$bot->noticef($user, "Scanning IP %s...", $ip);
	}

	// Note: Private IPs are skipped by the checker methods automatically
	$hits = 0;

	// 3. Check Tor Blacklists
	// Uses the updated isTorHost() in os.php (which we cleaned of dead lists)
	if ($this->isTorHost($ip)) {
		$bot->noticef($user, "%s%s MATCH:%s Tor Exit Node", BOLD_START, $ip, BOLD_END);
		$hits++;
	}

	// 4. Check Compromised Host Blacklists (DroneBL, etc)
	// Uses the updated isCompromisedHost() in os.php
	if ($this->isCompromisedHost($ip)) {
		$bot->noticef($user, "%s%s MATCH:%s Compromised Host (Open Proxy/Drone)", BOLD_START, $ip, BOLD_END);
		$hits++;
	}

	// 5. Report Results
	if ($hits == 0) {
		$bot->noticef($user, "Scan complete. No threats found for %s.", $ip);
	} else {
		$bot->noticef($user, "Scan complete. %d threat(s) detected!", $hits);

		// Optional: Offer advice
		if ($hits > 0) {
			$bot->notice($user, "Recommended Action: /msg O GLINE *@$ip 1d Compromised Host");
		}
	}
?>
