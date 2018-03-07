<?php

namespace Gw\Logic;

use Gw\Logic\RedisCache;


class Ip2Region
{
    /**
     * db 文件对象
     */
    private $dbFileHandler = NULL;
    private $dbFileName = 'sjd_microservice_ip2region';

    /**
     * 头部区块信息
     */
    private $HeaderSip = NULL;
    private $HeaderPtr = NULL;
    private $headerLen = 0;

    /**
     * super 区块索引信息
     */
    private $firstIndexPtr = 0;
    private $lastIndexPtr = 0;
    private $totalBlocks = 0;
    private $index_block_length = 12;
    private $total_header_length = 8192;

    /**
     * 内存搜索模式和二分查找的参数
     *
     */
    private $dbBinStr = NULL;
    private $dbFile = NULL;
    private $redisObj = NULL;

    /**
     * construct method
     *
     * @param    ip2regionFile
     */
    public function __construct()
    {
        $this->dbFile = __DIR__ . DIRECTORY_SEPARATOR . 'ip2region.db';
        $this->redisObj = RedisCache::getInstance(C('REDIS_HOST'), C('REDIS_PORT'), C('REDIS_AUTH'));
        $this->dbBinStr = unserialize($this->redisObj->get($this->dbFileName));
    }

    /**
     * 数据存入redis或其它缓存中，利用内存来查询
     * @param   $is_city  是否匹配城市
     * @param   $ip
     */
    public function memorySearch($ip)
    {
        //检查数据文件
        if (!$this->dbBinStr) {
            $this->dbBinStr = file_get_contents($this->dbFile);

            if ($this->dbBinStr == false) {
                throw new Exception("Fail to read the ip_db info {$this->dbFile}");
            } else {
                $this->redisObj->set($this->dbFileName, serialize($this->dbBinStr));
            }

            $this->firstIndexPtr = self::getLong($this->dbBinStr, 0);
            $this->lastIndexPtr = self::getLong($this->dbBinStr, 4);
            $this->totalBlocks = ($this->lastIndexPtr - $this->firstIndexPtr) / $this->index_block_length + 1;
        }

//        if (is_string($ip)) $ip = self::safeIp2long($ip);

        //二分查找对应的城市
        $l = 0;
        $h = $this->totalBlocks;
        $dataPtr = 0;
        while ($l <= $h) {
            $m = (($l + $h) >> 1);
            $p = $this->firstIndexPtr + $m * $this->index_block_length;
            $sip = self::getLong($this->dbBinStr, $p);
            if ($ip < $sip) {
                $h = $m - 1;
            } else {
                $eip = self::getLong($this->dbBinStr, $p + 4);
                if ($ip > $eip) {
                    $l = $m + 1;
                } else {
                    $dataPtr = self::getLong($this->dbBinStr, $p + 8);
                    break;
                }
            }
        }

        //没找到返回null
        if ($dataPtr == 0) return NULL;

        //获取对应的城市id和区域信息
        $dataLen = (($dataPtr >> 24) & 0xFF);
        $dataPtr = ($dataPtr & 0x00FFFFFF);

        $result = array(
            'city_id' => self::getLong($this->dbBinStr, $dataPtr),
            'region' => substr($this->dbBinStr, $dataPtr + 4, $dataLen - 4)
        );

        //对最后的结果进行过滤，根据条件取出省份和城市
        $temp = explode('|', trim($result['region'], '|'));
        $result['area_province'] = $temp[2];
        $result['area_city'] = $temp[3];
        return $result;
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
    public static function getLong($b, $offset)
    {
        $val = (
            (ord($b[$offset++])) |
            (ord($b[$offset++]) << 8) |
            (ord($b[$offset++]) << 16) |
            (ord($b[$offset]) << 24)
        );

        // convert signed int to unsigned int if on 32 bit operating system
        if ($val < 0 && PHP_INT_SIZE == 4) {
            $val = sprintf("%u", $val);
        }

        return $val;
    }

    /**
     * 验证一个字符串是否在另一字符串存在
     * @param $str
     * @param $target
     * @return bool
     */
    public function checkStr($str,$target)
    {
        $tmpArr = explode($str,$target);
        if(count($tmpArr)>1)return true;
        else return false;
    }

    /**
     * destruct method, resource destroy
     */
    public function __destruct()
    {
        if ($this->dbFileHandler != NULL) {
            fclose($this->dbFileHandler);
        }

        $this->dbBinStr = NULL;
        $this->HeaderSip = NULL;
        $this->HeaderPtr = NULL;
    }
}
