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
				$result = parent::createSocket();
			}
			if ($result) {
				// set the option to reuse the port
				@socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
				$result = socket_bind ( $this->socket, $this->address, $this->port );
				if ($result === false) {
					$this->errcode = socket_last_error();
					$this->errmsg = socket_strerror( $this->errcode );
				}
			}	
			return $result ? true : false;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode();
			$this->errmsg = $e->getMessage();
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
				$this->errcode = socket_last_error();
				$this->errmsg = socket_strerror( $this->errcode );
			}		
			return $result ? true : false;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode();
			$this->errmsg = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * 等待客户端建立连接
	 */
	protected function accept() {
		socket_set_nonblock( $this->socket );
		$clients = array($this->socket);
		do {
			$read = $clients;
			if (socket_select( $read, $write = NULL, $except = NULL, 0 ) < 1)
				continue;
			if (in_array( $this->socket, $read )) {
				$client = socket_accept( $this->socket );
				if ($client > 0) {
					self::$clients [] = $clients [] = $client;
					socket_write($client, "There are ".(count($clients) - 1)." client(s) connected to the server\n");					
					socket_getpeername( $client, $ip );
					echo "New client connected: {$ip}\n";
					$key = array_search( $this->sock, $read );
					unset( $read [$key] );
				}
			}
			foreach ($read as $read_sock){
				$data = $this->receive($read_sock);
				if ($data === false) {
					$key = array_search($read_sock, $clients);
					unset($clients[$key]);
					echo "client disconnected.\n";
					continue;
				}
				$data = trim($data);				 
				if (!empty($data)) {			 
					$this->send($read_sock,'good is receive the message!!');
					echo $data;
				}
			}
			print_r( self::$clients );
		} while ( true );
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