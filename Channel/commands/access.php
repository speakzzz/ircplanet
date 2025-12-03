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
	$mask = '*';
	
	if ($cmd_num_args > 1)
		$mask = $pargs[2];
	
	$chan_reg = $this->getChannelReg($chan_name);
	
	if (!$chan_reg) {
		$bot->noticef($user, 'Channel %s is not registered.', $chan_name);
		return false;
	}
	
	// Modernization: Escape input
	$safe_id = db_escape($chan_reg->getId());
	
	// Modernization: Use PDO query
	$res = db_query("SELECT * FROM channel_access WHERE chan_id = '$safe_id' ORDER BY level DESC");
	
	$access_list = array();
	
	// Modernization: Use fetch() loop instead of mysql_fetch_assoc
	if ($res) {
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
			$u = $this->getAccountById($row['user_id']);
			
			// Only add to list if the account exists and matches the search mask
			if ($u && ($mask == '*' || fnmatch($mask, $u->getName()))) {
				$access_list[] = array(
					'name' => $u->getName(), 
					'level' => $row['level'], 
					'suspend' => $row['suspend']
				);
			}
		}
	}

	if (empty($access_list)) {
		$bot->noticef($user, 'No access records found for %s matching %s.', $chan_name, $mask);
		return false;
	}

	$bot->noticef($user, '%s  %5s  %-20s  %s%s', BOLD_START, 'Level', 'User', 'Status', BOLD_END);
	$bot->noticef($user, str_repeat('-', 40));

	foreach ($access_list as $a) {
		$status = ($a['suspend'] == 1) ? '[SUSPENDED]' : '';
		$bot->noticef($user, '  %5d  %-20s  %s', $a['level'], $a['name'], $status);
	}
	
	$bot->noticef($user, '%d access records found.', count($access_list));
?>
