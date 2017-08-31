<?php

/** 
 * socket 基类
 * @author sunnyzeng
 * @since 2017/8/31
 */
abstract class SocketBase {
	/**
	 * 服务器地址
	 * @var string
	 */
	public $address = '';
	/**
	 * 服务器端口
	 * @var number
	 */
	public $port = 0;
	/**
	 * 网络地址类型
	 * @var int AF_INET|AF_INET6|AF_UNIX
	 */
	public $domain = AF_INET;
	/**
	 * 套接字使用的类型
	 * @var int  SOCK_STREAM|SOCK_DGRAM|SOCK_SEQPACKET|SOCK_RAW|SOCK_RDM
	 */
	public $tranType = SOCK_STREAM;
	/**
	 * 数据传输协议
	 * @var int SOL_TCP|SOL_UDP
	 */
	public $protocol = SOL_TCP;
	/**
	 * 套接字实例
	 * @var resource 
	 */
	public $socket = NULL;
	public $errcode = 0;
	public $errmsg = '';
	/**
	 * 数据读取类型
	 * @var int
	 */
	public $dataType = PHP_NORMAL_READ;
	Const SOCKET_DATA_NORMAL = PHP_NORMAL_READ;
	Const SOCKET_DATA_BINARY = PHP_BINARY_READ;
	
	/**
	 * 构造函数
	 */
	function __construct() {
		if (!isset($this->socket)) {
			$this->createSocket();
		}
	}
	/**
	 * 创建一个套接字
	 * @return boolean
	 */
	protected final function createSocket() {
		try {
			$socket = @socket_create ( $this->domain, $this->tranType, $this->protocol );
			if ($socket) {
				$this->socket = $socket;
				return true;
			}else{
				$this->errcode = socket_last_error($socket);
				$this->errmsg = socket_strerror($this->errcode);
				return false;
			}
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode();
			$this->errmsg = $e->getMessage();
			return false;
		}
	}
	/**
	 * 数据加密处理
	 * @param string $message
	 * @return string
	 */
	protected function encrypt($message){
		return $message;
	}
	
	/**
	 * 数据解密处理
	 * @param string $message
	 * @return string
	 */
	protected function decrypt($message) {
		return $message;
	}
	/**
	 * 发送消息
	 * @param string $message
	 * @return boolean
	 */
	protected function send($message) {
		try {
			if (empty ( $message )) {
				return false;
			}
			$result = true;
			if ($this->connected == false) {
				$result = $this->connect ();
			}
			$msg = $this->encrypt ( $message );
			$msg = "$msg\n\0";
			$length = strlen ( $msg );
			while ( $this->connected ) {
				$sent = socket_write ( $this->socket, $msg, $length );
				if ($sent === false) {
					break;
				}
				if ($sent < $length) {
					$msg = substr ( $msg, $sent );
					$length -= $sent;
					print ("Message truncated: Resending: $msg") ;
				} else {
					return true;
				}
			}
			return false;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode ();
			$this->errmsg = $e->getMessage ();
			return false;
		}
	}
	
	/**
	 * 接收消息
	 * 
	 * @return boolean
	 */
	protected function receive($length = 0) {
		try {
			$data = '';
			switch ($this->dataType) {
				// 读取普通字符型数据
				case self::SOCKET_DATA_NORMAL :
					$data = $this->read ( $length );
					break;
				// 读取二进制数据
				case self::SOCKET_DATA_BINARY :
					$data = $this->recv ( $length );
					break;
				default :
					break;
			}
			return $data;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode ();
			$this->errmsg = $e->getMessage ();
			return false;
		}
	}
	/**
	 * 读取普通字符串数据
	 * @param number $length
	 * @return Ambigous <string, string>|boolean
	 */
	private function read($length) {
		try {
			$recvData = $data = [ ];
			do {
				$maxSize = $length == 0 ? 2048 : $length;
				$buf = socket_read ( $this->socket, $maxSize );
				if (! empty ( $buf )) {
					$recvData [] = $buf;
				} elseif ($buf === false) {
					$this->errcode = socket_last_error ();
					$this->errmsg = socket_strerror ( $this->errcode );
					return false;
				} else {
					$data = implode ( '', $recvData );
					break;
				}
			} while ( true );
			
			$data = $this->decrypt ( $data );
			return $data;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode ();
			$this->errmsg = $e->getMessage ();
			return false;
		}
	}
	/**
	 * 读取二进制数据
	 * @param number $length
	 * @return Ambigous <string, string>|boolean
	 */
	private function recv($length){
		try {
			$recvData = [];
			$data = '';
			do {
				$buf = '';
				$maxSize = $length == 0 ? 2048 : $length;
				$bytes = socket_recv($this->socket, $buf, $maxSize, MSG_PEEK|MSG_DONTWAIT);			
				if ($bytes === false) {
					$this->errcode = socket_last_error();
					$this->errmsg = socket_strerror( $this->errcode );
					return false;
				}elseif ($bytes > 0){
					$recvData[] = $buf;
				}else{
					$data = implode('', $recvData);
					break;
				}	
			} while ( true );
				
			$data = $this->decrypt ( $data );
			return $data;
		} catch ( Exception $e ) {
			$this->errcode = $e->getCode ();
			$this->errmsg = $e->getMessage ();
			return false;
		}
	}
	/**
	 * 获取最后一次产生的错误信息
	 * @return array
	 */
	protected function getLastError() {
		return ['errno'=>$this->errcode,'errstr'=>$this->errmsg];
	}
	/**
	 * 关闭连接
	 */
	protected function close() {
		@socket_shutdown($this->socket,2);
		@socket_close($this->socket);
		$this->connected = false; 
		$this->socket = null;;
	}
	/**
	 * 释放资源
	 */
	function __destruct() {
		
	}
}

?>