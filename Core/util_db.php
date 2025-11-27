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
	
	require_once("core_globals.php");
	
	function db_get_pdo() {
		global $pdo_db;
		if (!isset($pdo_db) || $pdo_db === null) {
			// Attempt to find a running service to reconnect, or fail
			if (isset($GLOBALS['INSTANTIATED_SERVICES']) && count($GLOBALS['INSTANTIATED_SERVICES']) > 0) {
				foreach ($GLOBALS['INSTANTIATED_SERVICES'] as $service) {
					$service->db_connect();
					return $GLOBALS['pdo_db'];
				}
			}
			debug("DB Error: No active database connection.");
			return false;
		}
		return $pdo_db;
	}

	function db_query($query, $log = false)
	{
		$pdo = db_get_pdo();
		if (!$pdo) return false;

		try {
			$stmt = $pdo->query($query);
			
			if ($log) {
				debug("DB> $query");
				debug("DB> (" . $stmt->rowCount() . " rows affected)");
			}
			return $stmt;
		} catch (PDOException $e) {
			debug("DB Error> " . $e->getMessage());
			debug("DB Query> " . $query);
			
			// Handle 'Server has gone away' (MySQL error 2006) logic via Exception code/message if needed here
			// But strictly, PDO usually throws exceptions.
			return false;
		}
	}
	

	function db_queryf($format)
	{
		$args = func_get_args();
		$format = array_shift($args);
		$query = vsprintf($format, $args);
		
		return db_query($query);
	}
	

	function db_date($ts = 0)
	{
		if ($ts == 0)
			$ts = time();

		return date('Y-m-d H:i:s', $ts);
	}

	// Helper to escape strings (replacing addslashes)
	function db_escape($string) {
		$pdo = db_get_pdo();
		if (!$pdo) return addslashes($string); // Fallback
		// PDO::quote adds quotes around the string, we often just want the escaped string
		// for building legacy queries. We strip the surrounding quotes.
		$quoted = $pdo->quote($string);
		if ($quoted === false) return addslashes($string);
		return substr($quoted, 1, -1);
	}
