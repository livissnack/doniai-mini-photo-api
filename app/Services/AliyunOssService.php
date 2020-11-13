<?php

namespace App\Services;

use JohnLui\AliyunOSS;
use Exception;

/**
 * Class AliyunOssService
 * @package App\Services
 */
class AliyunOssService
{
    /**
     *  经典网络下可选：杭州、上海、青岛、北京、张家口、深圳、香港、硅谷、弗吉尼亚、新加坡、悉尼、日本、法兰克福、迪拜
     *  VPC 网络下可选：杭州、上海、青岛、北京、张家口、深圳、硅谷、弗吉尼亚、新加坡、悉尼、日本、法兰克福、迪拜
     * @var string 节点城市
     */
    private $city = '深圳';

    /**
     * 经典网络
     * VPC网络
     * @var string 网络类型
     */
    private $networkType = '经典网络';

    /**
     * @var AliyunOSS 对象客户端
     */
    private $ossClient;

    /**
     * 私有初始化 API，非 API，不用关注
     * Oss constructor.
     * @param false $isInternal 是否使用内网
     * @throws Exception
     */
    public function __construct($isInternal = false)
    {
        if ($this->networkType == 'VPC' && !$isInternal) {
            throw new Exception("VPC 网络下不提供外网上传、下载等功能");
        }
        $access_key = env('ALI_OSS_ACCESS_KEY', '');
        $access_secret = env('ALI_OSS_ACCESS_SECRET', '');
        if ($access_key === '' || $access_secret === '') {
            throw new Exception("Bucket访问key和secret值为空");
        }
        $this->ossClient = AliyunOSS::boot(
            $this->city,
            $this->networkType,
            $isInternal,
            $access_key,
            $access_secret
        );
    }

    /**
     * 使用外网上传文件
     * @param $bucketName string bucket名称
     * @param $ossKey string 上传之后的 OSS object 名称
     * @param $filePath string 上传文件路径
     * @param array $options
     * @return \Aliyun\OSS\Models\PutObjectResult
     */
    public static function publicUpload(string $bucketName, string $ossKey, string $filePath, $options = [])
    {
        $oss = new AliyunOssService();
        $oss->ossClient->setBucket($bucketName);
        return $oss->ossClient->uploadFile($ossKey, $filePath, $options);
    }

    /**
     * 使用外网删除对象
     * @param $bucketName string
     * @param $ossKey string
     */
    public static function publicDeleteObject(string $bucketName, string $ossKey)
    {
        $oss = new AliyunOssService();
        $oss->ossClient->setBucket($bucketName);
        $oss->ossClient->deleteObject($bucketName, $ossKey);
    }

    /**
     * 得到公共对象地址
     * @param $bucketName string
     * @param $ossKey string
     * @return string
     * @throws Exception
     */
    public static function getPublicObjectURL(string $bucketName, string $ossKey)
    {
        $oss = new AliyunOssService();
        $oss->ossClient->setBucket($bucketName);
        return $oss->ossClient->getPublicUrl($ossKey);
    }

    /**
     * 创建bucket存取桶
     * @param $bucketName string
     * @return \Aliyun\OSS\Models\Bucket
     */
    public static function createBucket(string $bucketName)
    {
        $oss = new AliyunOssService();
        return $oss->ossClient->createBucket($bucketName);
    }
}
