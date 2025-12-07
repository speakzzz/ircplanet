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

	// Syntax: REMREGEX <id>
	if ($cmd_num_args < 1) {
		$bot->notice($user, "Syntax: REMREGEX <id>");
		$bot->notice($user, "Use SHOWREGEX to find the ID number.");
		return false;
	}

	$target_id = $pargs[1];
	
	// Validate that ID is a number
	if (!is_numeric($target_id)) {
		$bot->notice($user, "Invalid ID. Please specify the numeric ID of the pattern.");
		return false;
	}

	$found = false;

	// Find the regex object in memory to confirm it exists and get the pattern string
	foreach ($this->drone_regexes as $regex) {
		if ($regex->getId() == $target_id) {
			$found = $regex;
			break;
		}
	}

	if (!$found) {
		$bot->noticef($user, "Regex ID %d not found.", $target_id);
		return false;
	}

	$pattern_str = $found->getPattern();
	$safe_id = db_escape($target_id);

	// Delete from Database
	// We use direct interpolation to ensure compatibility with your current db_query
	db_query("DELETE FROM ds_drone_regex WHERE id = '$safe_id'");

	// Reload Memory to reflect changes
	$this->loadDroneRegexes();

	$bot->noticef($user, "Removed regex pattern (ID %d): %s", $target_id, $pattern_str);
	
	// Log it
	$this->sendf(FMT_WALLOPS, SERVER_NUM, sprintf("Regex ID %d (%s) removed by %s", 
		$target_id, $pattern_str, $user->getNick()));
?>
