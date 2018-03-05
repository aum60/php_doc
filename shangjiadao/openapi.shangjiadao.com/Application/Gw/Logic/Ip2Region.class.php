<?php
namespace Gw\Logic;

use Think\Cache\Driver\Redis;

defined('INDEX_BLOCK_LENGTH')   or define('INDEX_BLOCK_LENGTH',  12);
defined('TOTAL_HEADER_LENGTH')  or define('TOTAL_HEADER_LENGTH', 8192);

class Ip2Region 
{
    /**
     * db 文件对象
    */
    private $dbFileHandler = NULL;
    private $redis = NULL;

    /**
     * 头部区块信息
    */
    private $HeaderSip    = NULL;
    private $HeaderPtr    = NULL;
    private $headerLen  = 0;

    /**
     * super 区块索引信息
    */
    private $firstIndexPtr = 0;
    private $lastIndexPtr  = 0;
    private $totalBlocks   = 0;

    /**
     * 内存搜索模式和二分查找的参数
     * 
    */
    private $dbBinStr = NULL;
    private $dbFile = dirname(__FILE__) . '/ip2region.db';
    private $redisObj = NULL;
    
    /**
     * construct method
     *
     * @param    ip2regionFile
    */
    public function __construct()
    {
        $this->redisObj = new Redis();
    }

    /**
     * 数据存入redis或其它缓存中，利用内存来查询
     * 
     * @param   $ip
    */
    public function memorySearch($ip)
    {
        //检查数据文件
        if ( $this->dbBinStr == NULL ) {
            $this->dbBinStr = json_decode($this->redisObj->get('sjd_microservice_ip2region'));
            if(!$this->dbBinStr){
                $this->dbBinStr = file_get_contents($this->dbFile);
            }
            
            if ( $this->dbBinStr == false ) {
                throw new Exception("Fail to read the ip_db info {$this->dbFile}");
            }else{
                $this->redisObj->set('sjd_microservice_ip2region',json_encode($this->dbBinStr));
            }

            $this->firstIndexPtr = self::getLong($this->dbBinStr, 0);
            $this->lastIndexPtr  = self::getLong($this->dbBinStr, 4);
            $this->totalBlocks   = ($this->lastIndexPtr-$this->firstIndexPtr)/INDEX_BLOCK_LENGTH + 1;
        }

        if ( is_string($ip) ) $ip = self::safeIp2long($ip);

        //二分查找对应的城市
        $l = 0;
        $h = $this->totalBlocks;
        $dataPtr = 0;
        while ( $l <= $h ) {
            $m = (($l + $h) >> 1);
            $p = $this->firstIndexPtr + $m * INDEX_BLOCK_LENGTH;
            $sip = self::getLong($this->dbBinStr, $p);
            if ( $ip < $sip ) {
                $h = $m - 1;
            } else {
                $eip = self::getLong($this->dbBinStr, $p + 4);
                if ( $ip > $eip ) {
                    $l = $m + 1;
                } else {
                    $dataPtr = self::getLong($this->dbBinStr, $p + 8);
                    break;
                }
            }
        }

        //not matched just stop it here
        if ( $dataPtr == 0 ) return NULL;

        //获取对应的城市id和区域信息
        $dataLen = (($dataPtr >> 24) & 0xFF);
        $dataPtr = ($dataPtr & 0x00FFFFFF);

        return array(
            'city_id' => self::getLong($this->dbBinStr, $dataPtr), 
            'region'  => substr($this->dbBinStr, $dataPtr + 4, $dataLen - 4)
        );
    }


   


    /**
     * safe self::safeIp2long function 
     *
     * @param ip 
     * */
    public static function safeIp2long($ip) 
    {
        $ip = ip2long($ip);

        // convert signed int to unsigned int if on 32 bit operating system
        if ($ip < 0 && PHP_INT_SIZE == 4) {
            $ip = sprintf("%u", $ip);
        } 

        return $ip;
    }



    /**
     * read a long from a byte buffer
     *
     * @param    b
     * @param    offset
    */
    public static function getLong( $b, $offset )
    {
        $val =  (
            (ord($b[$offset++]))        | 
            (ord($b[$offset++]) << 8)   | 
            (ord($b[$offset++]) << 16)  | 
            (ord($b[$offset  ]) << 24)
        );

        // convert signed int to unsigned int if on 32 bit operating system
        if ($val < 0 && PHP_INT_SIZE == 4) {
            $val = sprintf("%u", $val);
        } 

        return $val;
    }

    /**
     * destruct method, resource destroy
    */
    public function __destruct()
    {
        if ( $this->dbFileHandler != NULL ) {
            fclose($this->dbFileHandler);
        }

        $this->dbBinStr  = NULL;
        $this->HeaderSip = NULL;
        $this->HeaderPtr = NULL;
    }
}
?>
