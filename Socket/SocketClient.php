<?php
error_reporting(E_ALL);
set_time_limit(0);
ini_set("allow_call_time_pass_reference",true);
require_once ('./SocketBase.php');

/**
 * Socket Client
 * @author sunnyzeng
 * @since 2017/8/31       
 */
class SocketClient extends \SocketBase {
	
	protected $connected = FALSE;
	/**
	 * SocketClient类实例
	 * @var SocketClient
	 */
	public static $instance = NULL;
	/**
	 * 构造函数
	 */
	function __construct($address,$port) {
		$this->address = $address;
		$this->port = $port;
		parent::__construct ();
	}
	
	/**
	 * 与服务器建立连接
	 * @return boolean
	 */
	protected function connect() {
		try {
			$this->connected = @socket_connect($this->socket, $this->address,$this->port);
			if ($this->connected === false) {
				$this->errcode = socket_last_error();
				$this->errmsg  = socket_strerror( $this->errcode );
			}
			return $this->connected ? true : false;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode();
			$this->errmsg  = $e->getMessage();
			echo 'File: '.$e->getFile(),' line: '.$e->getLine(),' error: ', $e->getMessage(),PHP_EOL;
			return false;
		}
	}
	
	/**
	 * 发送数据
	 */
	public function sendMessage($message) {
		try {
			if (empty( $message )) {
				return false;
			}
			$result = true;
			if ($this->connected == false) {
				$result = $this->connect();
			}
			if ($result) {
				$result = parent::send($this->socket, $message );
			}
			return $result;
		} catch( Exception $e ) {
			$this->errcode = $e->getCode();
			$this->errmsg  = $e->getMessage();
			echo 'File: '.$e->getFile(),' line: '.$e->getLine(),' error: ', $e->getMessage(),PHP_EOL;
			return false;
		}
	}
	
	/**
	 * 接收数据(non-PHPdoc)
	 * @see SocketBase::recvive()
	 */
	public function recvMessage() {
		try {
			$recv = '';
			$result = true;
			if ($this->connected == false) {
				$result = $this->connect();
			}
			if ($result) {
				$recv = parent::receive($this->socket);
			}
			return $recv;
		} catch (Exception $e) {
			$this->errcode = $e->getCode();
			$this->errmsg  = $e->getMessage();
			echo 'File: '.$e->getFile(),' line: '.$e->getLine(),' error: ', $e->getMessage(),PHP_EOL;
			return false;
		}
	}
	/**
	 * 启动客户端
	 * @return SocketClient
	 */
	public static function start($host,$port){
		if (!isset(self::$instance)) {
			self::$instance = new self($host,$port);
		}
		$conn = self::$instance->connect();
		echo "connect to the server [$host,$port] result:",$conn ? 'success' : 'fail',PHP_EOL;
		return self::$instance;
	}
	
	/**
	 * 析构方法(non-PHPdoc)
	 * 
	 * @see SocketBase::__destruct()
	 */
	function __destruct() {
		// parent::__destruct ();
	}
}

// 测试代码
//$client = SocketClient::start('127.0.0.1', '8080'); 
$client = new SocketClient('127.0.0.1', '8080');

echo 'Please input the want to send message !!'.PHP_EOL;
$message = '';
$end = "\r\n";
$result = $client->recvMessage();
if ($result == false) {
	return ;
}
echo '------------------------------->',PHP_EOL;
while (($input=fgets(STDIN)) != "") {
	$message .= $input;
	if (preg_match('/\r|\n/', $input)>0) {
		if (trim($input) == 'exit') {
			break;
		}
		if (empty(trim($input))) {
			continue;
		}
		$result = $client->sendMessage(trim($input).$end);
		$message = '';
		if ($result === false) {
			echo 'The server connect is get down!! '.PHP_EOL;
			break;
		}else{
			echo 'The message is send success !! '.PHP_EOL;
		}

		echo '[147][SocketClient] The server message: '. $client->recvMessage(),PHP_EOL;
	}
}

?>