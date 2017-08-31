<?php
require_once ('Socket/SocketBase.php');

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
			$this->connected = socket_connect($this->socket, $this->address,$this->port);
			if ($this->connected === false) {
				$this->errcode = socket_last_error();
				$this->errmsg  = socket_strerror( $this->errcode );
			}
			return $this->connected ? true : false;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode();
			$this->errmsg  = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * 发送数据(non-PHPdoc)
	 * @see SocketBase::send()
	 */
	public function send($message) {
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
			return false;
		}
	}
	
	/**
	 * 接收数据(non-PHPdoc)
	 * @see SocketBase::recvive()
	 */
	public function receive() {
		try {
			$recv = '';
			if ($this->connected == false) {
				$result = $this->connect();
			}
			if ($result) {
				$recv = parent::receive();
			}
			return $recv;
		} catch (Exception $e) {
			$this->errcode = $e->getCode();
			$this->errmsg  = $e->getMessage();
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
		parent::__destruct ();
	}
}

// 测试代码
SocketClient::start('127.0.0.1', '8080')->send('hello world');
?>