<?php
class HttpResponse {
	public int $code;
	public string $message;
	public string $details = '';

	static $error_codes = [
		100 => [
			'message' => 'Continue',
			'details' => ''
		],
		101 => [
			'message' => 'Switching Protocols',
			'details' => ''
		],
		102 => [
			'message' => 'Processing',
			'details' => ''
		],
		103 => [
			'message' => 'Early Hints',
			'details' => ''
		],
		200 => [
			'message' => 'OK',
			'details' => ''
		],
		201 => [
			'message' => 'Created',
			'details' => ''
		],
		202 => [
			'message' => 'Accepted',
			'details' => ''
		],
		203 => [
			'message' => 'Non-Authoritative Information',
			'details' => ''
		],
		204 => [
			'message' => 'No Content',
			'details' => ''
		],
		205 => [
			'message' => 'Reset Content',
			'details' => ''
		],
		206 => [
			'message' => 'Partial Content',
			'details' => ''
		],
		207 => [
			'message' => 'Multi-Status',
			'details' => ''
		],
		208 => [
			'message' => 'Already Reported',
			'details' => ''
		],
		226 => [
			'message' => 'IM Used',
			'details' => ''
		],
		300 => [
			'message' => 'Multiple Choices',
			'details' => ''
		],
		301 => [
			'message' => 'Moved Permanently',
			'details' => ''
		],
		302 => [
			'message' => 'Found',
			'details' => ''
		],
		303 => [
			'message' => 'See Other',
			'details' => ''
		],
		304 => [
			'message' => 'Not Modified',
			'details' => ''
		],
		307 => [
			'message' => 'Temporary Redirect',
			'details' => ''
		],
		308 => [
			'message' => 'Permanent Redirect',
			'details' => ''
		],
		400 => [
			'message' => 'Bad Request',
			'details' => 'Something you sent us didn\'t seem right.'
		],
		401 => [
			'message' => 'Unauthorized',
			'details' => ''
		],
		402 => [
			'message' => 'Payment Required',
			'details' => ''
		],
		403 => [
			'message' => 'Forbidden',
			'details' => 'You have insufficient priviledges for this resource.'
		],
		404 => [
			'message' => 'Not Found',
			'details' => ''
		],
		405 => [
			'message' => 'Method Not Allowed',
			'details' => ''
		],
		406 => [
			'message' => 'Not Acceptable',
			'details' => ''
		],
		407 => [
			'message' => 'Proxy Authentication Required',
			'details' => ''
		],
		408 => [
			'message' => 'Request Timeout',
			'details' => ''
		],
		409 => [
			'message' => 'Conflict',
			'details' => ''
		],
		410 => [
			'message' => 'Gone',
			'details' => ''
		],
		411 => [
			'message' => 'Length Required',
			'details' => ''
		],
		412 => [
			'message' => 'Precondition Failed',
			'details' => ''
		],
		413 => [
			'message' => 'Content Too Large',
			'details' => ''
		],
		414 => [
			'message' => 'URI Too Long',
			'details' => ''
		],
		415 => [
			'message' => 'Unsupported Media Type',
			'details' => ''
		],
		416 => [
			'message' => 'Range Not Satisfiable',
			'details' => ''
		],
		417 => [
			'message' => 'Expectation Failed',
			'details' => ''
		],
		418 => [
			'message' => 'I\'m a teapot',
			'details' => ''
		],
		421 => [
			'message' => 'Misdirected Request',
			'details' => ''
		],
		422 => [
			'message' => 'Unprocessable Content',
			'details' => ''
		],
		423 => [
			'message' => 'Locked',
			'details' => ''
		],
		424 => [
			'message' => 'Failed Dependency',
			'details' => ''
		],
		425 => [
			'message' => 'Too Early',
			'details' => ''
		],
		426 => [
			'message' => 'Upgrade Required',
			'details' => ''
		],
		428 => [
			'message' => 'Precondition Required',
			'details' => ''
		],
		429 => [
			'message' => 'Too Many Requests',
			'details' => ''
		],
		431 => [
			'message' => 'Request Header Fields Too Large',
			'details' => ''
		],
		451 => [
			'message' => 'Unavailable For Legal Reasons',
			'details' => ''
		],
		500 => [
			'message' => 'Internal Server Error',
			'details' => 'Please try again later or inform the website administrator.'
		],
		501 => [
			'message' => 'Not Implemented',
			'details' => ''
		],
		502 => [
			'message' => 'Bad Gateway',
			'details' => ''
		],
		503 => [
			'message' => 'Service Unavailable',
			'details' => ''
		],
		504 => [
			'message' => 'Gateway Timeout',
			'details' => ''
		],
		505 => [
			'message' => 'HTTP Version Not Supported',
			'details' => ''
		],
		506 => [
			'message' => 'Variant Also Negotiates',
			'details' => ''
		],
		507 => [
			'message' => 'Insufficient Storage',
			'details' => ''
		],
		508 => [
			'message' => 'Loop Detected',
			'details' => ''
		],
		510 => [
			'message' => 'Not Extended',
			'details' => ''
		],
		511 => [
			'message' => 'Network Authentication Required',
			'details' => ''
		]
	];

	function __construct( int $code ){
		$this->code = $code;
		$this->message = HttpResponse::$error_codes[$this->code]['message'];
		$this->details = HttpResponse::$error_codes[$this->code]['details'];
	}

	function is_client_error( ): bool { return $code>=400 && $code<=499; }
	function is_server_error( ): bool { return $code>=500 && $code<=599; }
}
?>