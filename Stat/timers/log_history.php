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

	// Calculate Stats from Memory
	$user_count = 0;
	$oper_count = 0;
	$chan_count = 0;
	$serv_count = 0;
	$serv_s_count = 0;
	$acct_count = 0;
	
	// Count Users and Opers
	foreach ($this->users as $u) {
		if ($u->isBot()) continue;
		$user_count++;
		if ($u->isOper()) $oper_count++;
	}
	
	// Count Channels
	$chan_count = count($this->channels);
	
	// Count Servers and Services
	foreach ($this->servers as $s) {
		if ($s->isJupe()) continue;
		$serv_count++;
		if ($s->isService()) $serv_s_count++;
	}
	
	// Modernization: Use PDO to get total registered accounts
	$res = db_query("SELECT COUNT(*) FROM accounts");
	if ($res) {
		$acct_count = $res->fetchColumn(0);
	}
	
	// Modernization: Use PDO Prepared Statement for insertion
	// Note: 'services' column is set to 0 as a placeholder or if not tracked separately
	db_query(
		"INSERT INTO stats_history (date, servers, users, channels, accounts, opers, services, service_servers) 
		 VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)",
		array($serv_count, $user_count, $chan_count, $acct_count, $oper_count, 0, $serv_s_count)
	);
?>
