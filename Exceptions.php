<?php

class AbortException extends Exception {}

class InvalidChecksumException extends Exception {
	public function __construct($message,  $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

class UserNotFoundException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
	
class CannotAffordException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
	
class DatabaseException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
	
class UnknownCommandException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
	
class NoUndoException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

?>
