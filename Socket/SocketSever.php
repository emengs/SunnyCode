<?php
require_once ('Socket/SocketBase.php');

/**
 * Socket Server
 * @author sunnyzeng
 * @since 2017/8/31       
 */
class SocketSever extends \SocketBase {
	
	//已经连接的客户端
	protected static $clients = [];
	/**
	 * SocketSever类实例
	 * @var SocketSever
	 */
	public static $instance = NULL;
	/**
	 * 构造函数
	 */
	function __construct($address,$port) {
		$this->address = $address;
		$this->port = $port;
		parent::__construct();
	}
	
	/**
	 * 绑定主机端口
	 * @return boolean
	 */
	protected function bind() {
		try {
			$result = true;
			if ($this->socket == null) {
				$result = parent::createSocket ();
			}
			if ($result) {
				$result = socket_bind ( $this->socket, $this->address, $this->port );
				if ($result === false) {
					$this->errcode = socket_last_error ();
					$this->errmsg = socket_strerror ( $this->errcode );
				}
			}	
			return $result ? true : false;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode ();
			$this->errmsg = $e->getMessage ();
			return false;
		}
	}
	
	/**
	 * 启动事件监听
	 * @return boolean
	 */
	protected function listen() {
		try {		
			$result = socket_listen($this->socket);
			if ($result === false) {
				$this->errcode = socket_last_error ();
				$this->errmsg = socket_strerror ( $this->errcode );
			}		
			return $result ? true : false;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode ();
			$this->errmsg = $e->getMessage ();
			return false;
		}
	}
	
	/**
	 * 等待客户端建立连接
	 */
	protected function accept(){
		do{
			socket_set_nonblock($this->socket);
			$client = socket_accept($this->socket);
			if ($client === false) {
				$this->errcode = socket_last_error ();
				$this->errmsg = socket_strerror ( $this->errcode );
				sleep(1);
			}elseif ($client > 0){
				self::$clients[] = $client;			
			}
			print_r(self::$clients);
		}while(true);
	}
	
	/**
	 * 启动服务器
	 */
	public static function start($host,$port){
		if (!isset(self::$instance)) {
			self::$instance = new self($host,$port);
		}
		$result = self::$instance->createSocket();
		$result = $result ? self::$instance->bind() : false;
		$result = $result ? self::$instance->listen() : false;
		$result = $result ? self::$instance->accept() : false;
		return $result ? true : false;
	}
	
	/**
	 * 析构函数(non-PHPdoc)
	 * @see SocketBase::__destruct()
	 */
	function __destruct() {
		parent::__destruct();
	}
}
// 启动服务器
SocketSever::start('127.0.0.1', '8080');
?>