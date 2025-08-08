<?php

namespace Deflinhec\LaravelClickHouse\Exceptions;

use Exception;

class ClickHouseException extends Exception
{
    /**
     * 錯誤代碼
     *
     * @var int
     */
    protected $errorCode;

    /**
     * 錯誤上下文
     *
     * @var array
     */
    protected $context;

    /**
     * 創建新的 ClickHouse 異常實例
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param array $context
     */
    public function __construct($message = '', $code = 0, ?Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $code;
        $this->context = $context;
    }

    /**
     * 獲取錯誤代碼
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * 獲取錯誤上下文
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * 創建連接異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function connectionError($message, array $context = [])
    {
        return new static($message, 1001, null, $context);
    }

    /**
     * 創建查詢異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function queryError($message, array $context = [])
    {
        return new static($message, 1002, null, $context);
    }

    /**
     * 創建配置異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function configurationError($message, array $context = [])
    {
        return new static($message, 1003, null, $context);
    }

    /**
     * 創建遷移異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function migrationError($message, array $context = [])
    {
        return new static($message, 1004, null, $context);
    }

    /**
     * 創建叢集異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function clusterError($message, array $context = [])
    {
        return new static($message, 1005, null, $context);
    }

    /**
     * 創建認證異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function authenticationError($message, array $context = [])
    {
        return new static($message, 1006, null, $context);
    }

    /**
     * 創建權限異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function permissionError($message, array $context = [])
    {
        return new static($message, 1007, null, $context);
    }

    /**
     * 創建超時異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function timeoutError($message, array $context = [])
    {
        return new static($message, 1008, null, $context);
    }

    /**
     * 創建語法異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function syntaxError($message, array $context = [])
    {
        return new static($message, 1009, null, $context);
    }

    /**
     * 創建資源異常
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function resourceError($message, array $context = [])
    {
        return new static($message, 1010, null, $context);
    }

    /**
     * 獲取錯誤類型描述
     *
     * @return string
     */
    public function getErrorType()
    {
        $errorTypes = [
            1001 => 'Connection Error',
            1002 => 'Query Error',
            1003 => 'Configuration Error',
            1004 => 'Migration Error',
            1005 => 'Cluster Error',
            1006 => 'Authentication Error',
            1007 => 'Permission Error',
            1008 => 'Timeout Error',
            1009 => 'Syntax Error',
            1010 => 'Resource Error',
        ];

        return $errorTypes[$this->errorCode] ?? 'Unknown Error';
    }

    /**
     * 轉換為陣列
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getErrorCode(),
            'type' => $this->getErrorType(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->getContext(),
        ];
    }
}
