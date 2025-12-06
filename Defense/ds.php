<?php
/*
 * IRCPPlanet Services for ircu
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

	require('globals.php');
	require('../Core/service.php');
	require_once(SERVICE_DIR .'/db_whitelistentry.php');
	
	
	class DefenseService extends Service
	{
		var $pending_events = array();
		var $pending_commands = array();
		var $whitelist = array();
		
		
		function loadWhitelistEntries()
		{
			$res = db_query('select * from ds_whitelist order by whitelist_id asc');
			
			// Modernization: Use PDO fetch loop
			if ($res) {
				while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
					$entry = new DB_WhitelistEntry($row);
					
					$entry_key = strtolower($entry->getMask());
					$this->whitelist[$entry_key] = $entry;
				}
			}

			debugf('Loaded %d whitelist entries.', count($this->whitelist));
		}


		function serviceConstruct()
		{
		}
		
		
		function serviceDestruct()
		{
		}
		

		function serviceLoad()
		{
			$this->loadWhitelistEntries();
		}
		
		
		function servicePreburst()
		{
		}
		
		
		function servicePostburst()
		{
			$bot_num = $this->default_bot->getNumeric();
			foreach ($this->default_bot->channels as $chan_name) {
				$chan = $this->getChannel($chan_name);
				
				if (!$chan->isOp($bot_num))
					$this->op($chan->getName(), $bot_num);
			}
		}
		
		
		function servicePreread()
		{
		}
		

		function serviceClose($reason = 'So long, and thanks for all the fish!')
		{
			foreach ($this->users as $numeric => $user) {
				if ($user->isBot()) {
					$this->sendf(FMT_QUIT, $numeric, $reason);
					$this->removeUser($numeric);
				}
			}
		}

		
		function serviceMain()
		{
		}
		
		
		function getUserLevel($user_obj)
		{
			$acct_id = $user_obj;
			
			if (is_object($user_obj) && isUser($user_obj)) {
				if (!$user_obj->isLoggedIn())
					return 0;
				
				$acct_id = $user_obj->getAccountId();
			}
			
			// Modernization: Escape input
			if (function_exists('db_escape')) {
				$acct_id = db_escape($acct_id);
			} else {
				$acct_id = addslashes($acct_id);
			}

			$res = db_query("select `level` from `ds_admins` where user_id = '$acct_id'");
			
			// Modernization: Use rowCount() and fetchColumn()
			if ($res && $res->rowCount() > 0) {
				$level = $res->fetchColumn(0);
				return $level;
			}
			
			return 0;
		}
		
		
		function addWhitelistEntry($mask)
		{
			$entry = new DB_WhitelistEntry();
			$entry->setMask($mask);
			$entry->save();
			
			$key = strtolower($mask);
			$this->whitelist[$key] = $entry;
			
			return $this->whitelist[$key];
		}
		
		
		function getWhitelistEntry($mask)
		{
			$key = strtolower($mask);
			if (array_key_exists($key, $this->whitelist))
				return $this->whitelist[$key];
			
			return false;
		}
		
		
		function removeWhitelistEntry($mask)
		{
			$key = strtolower($mask);
			if (!array_key_exists($key, $this->whitelist))
				return;
			
			$this->whitelist[$key]->delete();
			unset($this->whitelist[$key]);
		}
		
		
		function isWhitelisted($mask)
		{
			foreach ($this->whitelist as $entry) {
				if ($entry->matches($mask))
					return true;
			}
			
			return false;
		}
		

		function isBlacklistedDb($ip)
		{
			if (!defined('BLACK_GLINE') || isPrivateIp($ip))
				return false;
			
			// Modernization: Use db_escape instead of addslashes
			$res = db_query(sprintf(
					"select count(entry_id) FROM `ds_blacklist` WHERE `ip_address` = '%s'", 
					db_escape($ip)));
			
			// Modernization: Use fetchColumn()
			if ($res && $res->fetchColumn(0) > 0) {
				debugf('IP %s blacklisted by admin.', $ip);
				return true;
			}
			
			return false;
		}


		/**
		 * isBlacklistedDns is a generic function to provide extensibility
		 * for easily checking DNS based blacklists.
		 */
		function isBlacklistedDns($host, $dns_suffix, $pos_responses = -1)
		{
			// Don't waste time checking private class IPs.
			if (isPrivateIp($host))
				return false;
			
			$start_ts = microtime(true);
			
			$octets = explode('.', $host);
			$reverse_octets = implode('.', array_reverse($octets));
			$lookup_addr = $reverse_octets .'.'. $dns_suffix .'.';

			debugf('DNSBL checking %s', $lookup_addr);
			$dns_result = @dns_get_record($lookup_addr, DNS_A);

			// FIX: Check if $dns_result is explicitly NOT false before counting
			if ($dns_result !== false && count($dns_result) > 0) {
				$dns_result = $dns_result[0]['ip'];
				$resolved = true;
			}
			else {
				$dns_result = $lookup_addr;
				$resolved = false;
			}
			
			$end_ts = microtime(true);
			debugf('DNSBL check time elapsed: %0.4f seconds (%s = %s)', 
					$end_ts - $start_ts, $lookup_addr, $dns_result);
			
			// If it didn't resolve, don't check anything
			if (!$resolved)
				return false;
			
			// Check for any successful resolution
			if ($resolved && $pos_responses == -1 || empty($pos_responses))
				return true;
			
			// Check for a match against the provided string
			if (is_string($pos_responses) && !empty($pos_responses)
			 		&& $dns_result == ('127.0.0.'. $pos_responses))
				return true;
			
			// Check for a match within the provided array
			if (is_array($pos_responses)) {
				foreach ($pos_responses as $tmp_match) {
					$tmp_match = '127.0.0.'. $tmp_match;
					if ($tmp_match == $dns_result)
						return true;
				}
			}
			
			// All checks failed; host tested negative.
			return false;
		}
		
		
		function isTorHost($host)
		{
			// UPDATED: Removed dead blacklists
			$blacklists = array(
				'tor.dan.me.uk'        => array(100)
			);

			foreach ($blacklists as $dns_suffix => $responses) {
				if ($this->isBlacklistedDns($host, $dns_suffix, $responses))
					return true;
			}
			
			return false;
		}
		
		
		function isCompromisedHost($host)
		{
			// UPDATED: Removed dead blacklists (ahbl, swiftbl, etc) to prevent mass bans
			$blacklists = array(
				'dnsbl.dronebl.org'   => array(3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19),
				'rbl.efnetrbl.org'    => array(1, 2, 3, 4, 5)
			);
			
			foreach ($blacklists as $dns_suffix => $responses) {
				if ($this->isBlacklistedDns($host, $dns_suffix, $responses))
					return true;
			}
			
			return false;
		}
		
		
		function performGline($gline_mask, $gline_duration, $gline_reason)
		{
			if (defined('OS_GLINE') && OS_GLINE == true && defined('OS_NICK')) {
				$oper_service = $this->getUserByNick(OS_NICK);
				$gline_command = irc_sprintf('GLINE %s %s %s', 
						$gline_mask, $gline_duration, $gline_reason);

				if (!$oper_service) {
					$pending_commands[] = $gline_command;
					return;
				}

				$this->default_bot->message($oper_service, $gline_command);
			}
			else {
				$gline_secs = convertDuration($gline_duration);
				$gline_lifetime = time() + $gline_secs;
				$new_gl = $this->addGline($gline_mask, $gline_secs, time(), time(), $gline_lifetime, $gline_reason);
				$this->enforceGline($new_gl);
			}
		}
	}
	
	$ds = new DefenseService();
?>
