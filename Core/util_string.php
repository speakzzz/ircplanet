<?php
/*
 * ircPlanet Services for ircu
 * Copyright (c) 2005 Brian Cline.
 * All rights reserved.
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
		$ident = substr($mask, $ex_pos + 1, $at_pos - $ex_pos - 1);
		
		if (strlen($ident) > IDENT_LEN) {
			$mask = substr($mask, 0, $ex_pos) .'!*'. right($ident, IDENT_LEN - 1) . substr($mask, $at_pos);
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
	
	function irc_vsprintf($format, $args)
	{
		$std_types = 'bcdeufFosxX';
		$custom_types = 'ACHNU';

		$len = strlen($format);
		$arg_index = -1;
		$pct_count = 0;

		for ($i = 0; $i < $len - 1; $i++) {
			$char = $format[$i];
			$next = $format[$i + 1];

			if ($char == '%')
				$pct_count++;
			else
				$pct_count = 0;

			if ($pct_count != 1 || $next == '%')
				continue;

			$spec_start = $i;
			$spec_end = $i + 1;
			$type = '';

			for ($j = $i + 1; $j < $len; $j++) {
				$tmp_char = $format[$j];
				$is_std_type = (false !== strpos($std_types, $tmp_char));
				$is_cust_type = (false !== strpos($custom_types, $tmp_char));

				if ($is_std_type || $is_cust_type) {
					$type = $tmp_char;
					$arg_index++;
					$spec_end = $j;
					break;
				}
			}

			if ($is_cust_type) {
				$arg_obj = isset($args[$arg_index]) ? $args[$arg_index] : null;
				$cust_text = '';

				switch ($type) {
					case 'A':
						if (is_array($arg_obj)) $cust_text = implode(' ', $arg_obj);
						else $cust_text = (string)$arg_obj;
						break;


					case 'C':
					case 'H':
						if (isUser($arg_obj))
							$cust_text = $arg_obj->getNick();
						elseif (isChannel($arg_obj) || isServer($arg_obj))
							$cust_text = $arg_obj->getName();
						elseif (isGline($arg_obj))
							$cust_text = $arg_obj->getMask();

						break;


					case 'N':
						if (isUser($arg_obj) || isServer($arg_obj))
							$cust_text = $arg_obj->getNumeric();

						break;


					case 'U':
						if (isUser($arg_obj))
							$cust_text = $arg_obj->getAccountName();
						elseif (isAccount($arg_obj))
							$cust_text = $arg_obj->getName();

						break;

					default:
						continue 2;
				}
				
				$format[$spec_end] = 's';
				$args[$arg_index] = $cust_text;
				$i = $spec_end + 1;
			}
		}

		return vsprintf($format, $args);
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
