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
	
	// Iterate through all active Mutes in memory
	foreach ($this->mutes as $mask => $mute) {
		// Check if the mute has expired
		if ($mute->isExpired()) {
			$mute_key = strtolower($mask);
			
			// 1. Remove from Database safely
			// We use db_escape to prevent SQL injection issues with the mask
			$safe_mask = db_escape($mask);
			db_query("DELETE FROM os_mutes WHERE mask = '$safe_mask'");
			
			// 2. Broadcast Removal to Network (MT -)
			// This tells the IRCd and other services to lift the mute
			$this->sendf(FMT_MUTE_INACTIVE, SERVER_NUM, $mask, 
				$mute->getDuration(), $mute->getLastMod(), 
				$mute->getLifetime());
			
			// 3. Remove from Service Memory
			unset($this->mutes[$mute_key]);
			
			// Optional: Log to wallops so admins know it expired
			// $this->sendf(FMT_WALLOPS, SERVER_NUM, "Mute for $mask expired.");
		}
	}
?>
