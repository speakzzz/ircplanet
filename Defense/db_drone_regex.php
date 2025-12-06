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

	class DB_DroneRegex extends DB_Record
	{
		protected $_table_name = 'ds_drone_regex';
		protected $_key_field = 'id';
		protected $_insert_timestamp_field = 'create_date';
		
		protected $id = 0;
		protected $pattern = '';
		protected $reason = '';
		protected $set_by = '';
		protected $create_date = null;
		
		protected function recordConstruct() { }
		protected function recordDestruct()  { }
		
		// FIX: Added getId() for removal commands
		public function getId()      { return $this->id; }
		public function getPattern() { return $this->pattern; }
		public function getReason()  { return $this->reason; }
		public function getSetBy()   { return $this->set_by; }
		public function getSetDate() { return $this->create_date; }
		
		public function setPattern($s) { $this->pattern = $s; }
		public function setReason($s)  { $this->reason = $s; }
		public function setSetBy($s)   { $this->set_by = $s; }
		
		public function matches($string)
		{
			return @preg_match($this->pattern, $string);
		}	
	}
?>
