<?php
/*
 * ircPlanet Services for ircu
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
	
	function right($str, $len)
	{
		return substr($str, (-1 * $len));
	}
	
	
	function isValidEmail($email)
	{
		$b = preg_match('/^[a-z0-9._\-%]+@[a-z0-9._\-]+\.[a-z]{2,4}$/i', $email);
		
		return $b;
	}
	
	
	function isIp($s)
	{
		return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $s);
	}


	function isPrivateIp($ip)
	{
		$private_ranges = array(
			'0.0.0.0/8',	  // Self-identification
			'1.0.0.0/8',      // IANA Unallocated
			'2.0.0.0/8',      // IANA Unallocated
			'5.0.0.0/8',      // IANA Unallocated
			'10.0.0.0/8',     // Private networks
			'127.0.0.0/8',    // Loopback
			'169.254.0.0/16', // DHCP Self-assignment
			'172.16.0.0/12',  // Private networks
			'192.168.0.0/16'  // Private networks
		);
		
		foreach ($private_ranges as $range) {
			list($range_start, $range_mask) = explode('/', $range);
			$tmp_mask = 0xffffffff << (32 - $range_mask);
			$tmp_range_mask = ip2long($range_start) & $tmp_mask;
			$tmp_ip_mask = ip2long($ip) & $tmp_mask;

			if ($tmp_ip_mask == $tmp_range_mask)
				return true;
		}

		return false;
	}


	function fixHostMask($mask)
	{
		$ex_pos = strpos($mask, '!');
		$at_pos = strpos($mask, '@');
		
		if ($ex_pos === false) $ex_pos = 0;
		if ($at_pos === false) $at_pos = 0;

		if ($ex_pos > 0 && $at_pos > $ex_pos) {
			$ident = substr($mask, $ex_pos + 1, $at_pos - $ex_pos - 1);
			
			if (strlen($ident) > IDENT_LEN) {
				$mask = substr($mask, 0, $ex_pos) .'!*'. right($ident, IDENT_LEN - 1) . substr($mask, $at_pos);
			}
		}
		
		return $mask;
	}
	
	function fixNickHostMask($mask)
	{
		$ex_pos = strpos($mask, '!');
		$at_pos = strpos($mask, '@');
		
		if ($at_pos === false) {
			$mask = '*@'. $mask;
			$at_pos = 1;
		}
		
		if ($ex_pos === false) {
			$mask = '*!'. $mask;
			$ex_pos = 1;
			$at_pos = strpos($mask, '@');
		}
		
		$ident = substr($mask, $ex_pos + 1, $at_pos - $ex_pos - 1);
		if (strlen($ident) > IDENT_LEN) {
			$mask = substr($mask, 0, $ex_pos) .'!*'. right($ident, IDENT_LEN - 1) . substr($mask, $at_pos);
		}
		
		return $mask;
	}
	

	function lineNumArgs($s)
	{
		$tokens = 1;
		$s = trim($s);
		$len = strlen($s);
		
		if ($len == 0)
			return 0;
		
		for ($i = 0; $i < strlen($s) - 1; ++$i) {
			if ($s[$i] == ' ' && $s[$i + 1] == ':') {
				$tokens++;
				break;
			}
			elseif ($s[$i] == ' ') {
				$tokens++;
			}
		}
		
		return $tokens;
	}
	
	
	function lineGetArgs($s, $stop_at_colon = true)
	{
		$start = 0;
		$tokens = array();
		$s = trim($s);
		$len = strlen($s);
		
		if ($len == 0)
			return 0;
		
		for ($i = 0; $i < $len; ++$i) {
			if ($stop_at_colon && ($s[$i] == ' ' && $i < ($len - 1) && $s[$i + 1] == ':')) {
				$tokens[] = substr($s, $start, $i - $start);
				$tokens[] = substr($s, $i + 2);
				break;
			}
			elseif ($s[$i] == ' ') {
				$tokens[] = substr($s, $start, $i - $start);
				$start = $i + 1;
			}
			elseif ($i == ($len - 1)) {
				$tokens[] = substr($s, $start);
			}
		}
		
		return $tokens;
	}
	
	
	function getPrettySize($bytes)
	{
		$units = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');
		$precision = 2;
		
		for ($i = 0; $bytes >= 1024; ++$i)
			$bytes /= 1024;
		
		if ($i > 0)
			$bytes = sprintf('%0.'. $precision .'f', $bytes);
		
		return ($bytes .' '. $units[$i]);
	}
	
	
	function irc_sprintf($format)
	{
		$args = func_get_args(); 
		array_shift($args); 
		return irc_vsprintf($format, $args);
	}
	
	/**
	 * irc_vsprintf: Modern implementation using callbacks to handle custom specifiers safely.
	 */ 
	function irc_vsprintf($format, $args)
	{
		// Track the current argument index
		$arg_idx = 0;

		$callback = function($matches) use (&$args, &$arg_idx) {
			// $matches[0] is the full specifier (e.g. "%-10s" or "%A")
			// $matches[1] is the type char (e.g. "s" or "A")

			if ($matches[1] == '%') return '%'; // Handle escaped %%

			if (!array_key_exists($arg_idx, $args)) {
				return ''; // Missing argument protection
			}

			$arg = $args[$arg_idx++];
			$type = $matches[1];
			$full_spec = $matches[0];

			switch ($type) {
				case 'A': // Array: implode with spaces
					$val = is_array($arg) ? implode(' ', $arg) : (string)$arg;
					// Replace %A with %s in the specifier string so sprintf can handle it
					$spec = str_replace('A', 's', $full_spec);
					return sprintf($spec, $val);

				case 'C': // Channel/Server/User Name
				case 'H': 
					$val = '';
					if (is_object($arg)) {
						if (method_exists($arg, 'getNick')) $val = $arg->getNick();
						elseif (method_exists($arg, 'getName')) $val = $arg->getName();
						elseif (method_exists($arg, 'getMask')) $val = $arg->getMask();
					} else {
						$val = (string)$arg;
					}
					$spec = str_replace($type, 's', $full_spec);
					return sprintf($spec, $val);

				case 'N': // Numeric
					$val = '';
					if (is_object($arg) && method_exists($arg, 'getNumeric')) {
						$val = $arg->getNumeric();
					} else {
						$val = (string)$arg;
					}
					$spec = str_replace('N', 's', $full_spec);
					return sprintf($spec, $val);

				case 'U': // Account Name
					$val = '';
					if (is_object($arg)) {
						if (method_exists($arg, 'getAccountName')) $val = $arg->getAccountName();
						elseif (method_exists($arg, 'getName')) $val = $arg->getName();
					} else {
						$val = (string)$arg;
					}
					$spec = str_replace('U', 's', $full_spec);
					return sprintf($spec, $val);

				default:
					// Standard specifier, use original arg and spec
					return sprintf($full_spec, $arg);
			}
		};

		// Regex to match printf specifiers: % [flags/width/precision] type
		// Types: Standard (bcdeufFosxX) + Custom (ACHNU) + Literal (%)
		return preg_replace_callback(
			'/%(?:[0-9.\-]*[bcdeufFosxXACHNU%])/', 
			$callback, 
			$format
		);
	}
	

	function randomKickReason()
	{
		$ban_reasons = array(
			"Don't let the door hit you on the way out!",
			"Sorry to see you go... actually no, not really.",
			"This is your wake-up call...",
			"Behave yourself!",
			"Ooh, behave...",
			"Not today, child.",
			"All your base are belong to me",
			"Watch yourself!",
			"Better to remain silent and be thought a fool than to speak out and remove all doubt.",
			"kthxbye."
		);
		
		$index = rand(0, count($ban_reasons) - 1);
		return $ban_reasons[$index];
	}
?>
