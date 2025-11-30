<?php

	/**
	 * Abstract Email wrapper class. Currently uses PHPMailer, 
	 * found in the Core/lib/phpmailer directory.
	 */
	require_once(CORE_LIB_DIR .'phpmailer/class.phpmailer.php');
	
	class Email {
		private $mailer = null;
		
		function __construct() {
			$this->mailer = new PHPMailer();
			$this->mailer->IsSMTP();
			
			// Basic Connection Config
			$this->mailer->Host = SMTP_HOST;
			$this->mailer->Port = SMTP_PORT;
			
			// FIX: Enable SMTP Authentication if a user is specified
			if (defined('SMTP_USER') && SMTP_USER != "") {
				$this->mailer->SMTPAuth = true;
				$this->mailer->Username = SMTP_USER;
				$this->mailer->Password = SMTP_PASS;
				
				// Automatically enable TLS for port 587 (Standard Submission Port)
				if (SMTP_PORT == 587) {
					$this->mailer->SMTPSecure = 'tls';
				}
				// Automatically enable SSL for port 465 (Legacy SMTPS)
				elseif (SMTP_PORT == 465) {
					$this->mailer->SMTPSecure = 'ssl';
				}
			} else {
				$this->mailer->SMTPAuth = false;
			}

			// Set "From" address
			if (defined('SMTP_FROM') && !empty(SMTP_FROM)) {
				$this->mailer->SetFrom(SMTP_FROM, 'IRC Services');
			} else {
				$this->mailer->SetFrom('noreply@' . gethostname(), 'IRC Services');
			}
		}
		
		
		public function addAddress($address, $name = '')  { return $this->mailer->AddAddress($address, $name); }
		public function addCC($address, $name = '')       { return $this->mailer->AddCC($address, $name); }
		public function addBCC($address, $name = '')      { return $this->mailer->AddBCC($address, $name); }
		public function setFrom($address, $name = '')     { return $this->mailer->SetFrom($address, $name); }
		public function setSubject($subject)              { $this->mailer->Subject = $subject; }
		public function setBody($plainText)               { $this->mailer->Body = $plainText; }
		
		public function send()     { return $this->mailer->Send(); }
		public function getError() { return $this->mailer->ErrorInfo; }
	}
?>
