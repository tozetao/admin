<?php

namespace App\Util\Upload;

use Illuminate\Support\Facades\Log;
use OSS\Core\OssException;
use OSS\Model\CorsConfig;
use OSS\Model\CorsRule;
use OSS\OssClient;

class AliyunOSS
{
    private $ossClient;
    private $bucket;
    private $requestUrl;

    public function __construct()
    {
        $accessKeyId = '';
        $accessKeySecret = '';
        $endpoint = '';
        $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

        $this->bucket = env('ALIYUN_OSS_BUCKET');
//        $this->requestUrl = sprintf('http://%s.oss-cn-hangzhou.aliyuncs.com', $this->bucket);
        $this->requestUrl = '';
    }

    public function showAllBuckets()
    {
        $bucketListInfo = $this->ossClient->listBuckets();
        $bucketList = $bucketListInfo->getBucketList();
        foreach($bucketList as $bucket) {
            echo sprintf('location: %s, name: %s, date: %s, <br/>', $bucket->getLocation(), $bucket->getName(), $bucket->getCreateDate());
        }
    }

    public function cors()
    {
        $corsConfig = new CorsConfig();
        $rule = new CorsRule();
        $rule->addAllowedHeader('*');
        $rule->addAllowedOrigin('*');
        $rule->addAllowedMethod('GET');
        $rule->addAllowedMethod('POST');
        $rule->setMaxAgeSeconds(10);
        $corsConfig->addRule($rule);

        return $this->ossClient->putBucketCors($this->bucket, $corsConfig);
    }

    public function getCors()
    {
        return $this->ossClient->getBucketCors($this->bucket);
    }

    public function uploadFile(string $object, $file)
    {
        try {
            $this->ossClient->uploadFile($this->bucket, $object, $file);
        } catch (OssException $e) {
            Log::error($e->getMessage());
            return '';
        }
        return $this->requestUrl . '/' . $object;
    }

    public function deleteFile(string $url)
    {
        $object = $this->parseObjectName($url);
        return $this->ossClient->deleteObject($this->bucket, $object);
    }

    public function parseObjectName($url)
    {
        return str_replace($this->requestUrl . '/', '', $url);
    }
}
