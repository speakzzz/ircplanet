<?php
/*
 * ircPlanet Services for ircu
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
	
	// Only scan on new connections, not nick changes
	if (!$nick_change) {
		$whitelisted = $this->isWhitelisted($user);
		$gline_mask = '*@'. $user->getIp();
		$gline_set = false;

		// 1. Check Database Blacklist
		if (defined('BLACK_GLINE') && BLACK_GLINE == true && !$gline_set && !$whitelisted
				&& $this->isBlacklistedDb($user->getIp()))
		{
			$this->performGline($gline_mask, BLACK_DURATION, BLACK_REASON);
			$gline_set = true;
		}
		
		// 2. Check Tor Nodes
		if (defined('TOR_GLINE') && TOR_GLINE == true && !$gline_set && !$whitelisted
				&& $this->isTorHost($user->getIp()))
		{
			$this->performGline($gline_mask, TOR_DURATION, TOR_REASON);
			$gline_set = true;
		}
		
		// 3. Check Compromised Hosts (Open Proxies, Drones)
		if (defined('COMP_GLINE') && COMP_GLINE == true && !$gline_set && !$whitelisted
				&& $this->isCompromisedHost($user->getIp()))
		{
			$this->performGline($gline_mask, COMP_DURATION, COMP_REASON);
			$gline_set = true;
		}

		// 4. Check Drone Regex (Realname/Hostname Patterns)
		// FIX: Added this block to actually perform the regex scan
		if (!$gline_set && !$whitelisted) {
			$match = $this->checkDroneRegex($user);
			if ($match) {
				// Default ban time 1 day for regex matches
				$this->performGline($gline_mask, '1d', 'Drone/Bot detected: ' . $match->getReason());
				
				// Log the hit to Wallops
				$this->sendf(FMT_WALLOPS, SERVER_NUM, sprintf("Regex Ban on %s (Match: %s)", 
					$user->getNick(), $match->getPattern()));
				
				$gline_set = true;
			}
		}
	}
?>
