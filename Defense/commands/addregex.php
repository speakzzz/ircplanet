<?php
/*
 * IRCPlanet Services for ircu
 * Copyright (c) 2025 Felix Alcantara.
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

	// Syntax: ADDREGEX <pattern> <reason>
	if ($cmd_num_args < 3) {
		$bot->notice($user, "Syntax: ADDREGEX <pattern> <reason>");
		$bot->notice($user, "Example: ADDREGEX /.*vpn.*/i Detected VPN");
		return false;
	}

	$pattern = $pargs[1];
	$reason = assemble($pargs, 2);

	// 1. Validate Regex
	if (@preg_match($pattern, "test") === false) {
		$bot->notice($user, "Invalid Regex pattern.");
		return false;
	}

	// 2. Save to Database
	$regex = new DB_DroneRegex();
	$regex->setPattern($pattern);
	$regex->setReason($reason);
	$regex->setSetBy($user->getNick());
	$regex->save();
	
	// 3. Reload Memory
	$this->loadDroneRegexes();
	
	$bot->notice($user, "Added regex pattern: $pattern");

	// 4. SCAN EXISTING USERS (The Fix)
	$match_count = 0;
	$bot->notice($user, "Scanning network for matches...");

	foreach ($this->users as $u) {
		// Skip services and bots
		if ($u->isBot() || $u->isService()) continue;

		// Check this user against the NEW regex list
		// We use the specific checkDroneRegex method from ds.php
		if ($match = $this->checkDroneRegex($u)) {
			// It matched! Ban them.
			$gline_mask = '*@' . $u->getIp();
			
			// Prevent double-glining if we already hit this IP in this loop
			// (Optimization for clones)
			
			$this->performGline($gline_mask, '1d', 'Drone/Bot detected: ' . $match->getReason());
			
			$this->sendf(FMT_WALLOPS, SERVER_NUM, sprintf("Regex Ban on %s (Match: %s)", 
				$u->getNick(), $match->getPattern()));
			
			$match_count++;
		}
	}

	if ($match_count > 0) {
		$bot->noticef($user, "Scan complete: Banned %d existing users matching this pattern.", $match_count);
	} else {
		$bot->notice($user, "Scan complete: No existing users matched.");
	}
?>
