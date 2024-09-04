<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Util\Upload\AliyunOSS;
use App\Util\Upload\ImageUploadHandler;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class HandlerController extends Controller
{
    // 1m
    const MaxSize = 1048576;

    const StartupBanner = 3;

    const NewsIcon = 4;
    const NewsBanner = 5;

    const Goods = 6;

    const ClawDetailImage = 7;

    const Loading = 8;

    const WechatCustomerService = 9;

    const Machine = 10;

    const HomeBanner = 11;

    const MachineGroup = 12;

    /**
     * @throws \ErrorException
     */
    public function imageUpload(Request $request, ImageUploadHandler $handler, AliyunOSS $aliyunOSS): \Illuminate\Http\JsonResponse
    {
        if (!$request->file('image') || !$request->file('image')->isValid()) {
            return $this->fail('上传失败, 无效的图片');
        }

        $size = $request->file('image')->getSize();
        if (!$size || $size > self::MaxSize) {
            return $this->fail('上传的图片超过1M');
        }

        $type = (int)$request->post('type');
        if ($type == self::Loading) {
            if ($size > 102400) {
                return $this->fail('Loading Icon必须小于100k');
            }
        }

        $this->validatePixelSize($request);

        $filename = $this->generateFilename($request->file('image'), 'oss');
        $path = $request->file('image')->getRealPath();
        $url = $aliyunOSS->uploadFile($filename, $path);
        if (!$url) {
            return $this->fail('文件保存失败!');
        }
        return $this->data([
            'url' => $url
        ]);

//        $this->validatePixelSize($request);
//        $result = $handler->save($request->image, '', '');
//        if (!$result) {
//            return $this->fail('文件保存失败.');
//        }
//        return $this->data([
//            'url' => $result['path']
//        ]);
    }

    // 生成文件名
    private function generateFilename(UploadedFile $file, $prefix = ''): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'png';
        return $prefix . '_' . time() . '_' . Str::random(5) . '.' . $extension;
    }

    private function validatePixelSize(Request $request)
    {
        // 开屏公告宽高验证
        $pixelSize = getimagesize($request->file('image')->getRealPath());

        $width = $pixelSize[0] ?? 0;
        $height = $pixelSize[1] ?? 0;

        $type = (int)$request->post('type');

        // 开屏图尺寸处理
        if ($type == self::StartupBanner) {
            if ($width > 740 || $height > 1300) {
                throw new ApiException('开屏图大小超出尺寸校址.');
            }
            return true;
        }

        // news_icon:120*120
        if ($type == self::NewsIcon) {
            if ($width != 120 || $height != 120) {
                throw new ApiException('图片尺寸不符合要求.');
            }
            return true;
        }

        // news_banner: 848px
        if ($type == self::NewsBanner) {
            if ($width != 848) {
                throw new ApiException('新闻图片宽度不符合要求.');
            }
            return true;
        }

        if ($type == self::Goods) {
            if ($width != 261 || $height != 261) {
                throw new ApiException('商品图片尺寸不符合要求.');
            }
            return true;
        }

        if ($type == self::ClawDetailImage) {
            // height 4096
            if ($width != 892 || $height > 4096) {
                throw new ApiException('娃娃机详情图宽度不符合要求.');
            }
            return true;
        }

        if ($type == self::WechatCustomerService) {
            if ($width != 363 || $height != 363) {
                throw new ApiException('图片尺寸不符合要求.');
            }
            return true;
        }

        if ($type == self::Machine) {
            if ($width != 252 || $height != 252) {
                throw new ApiException('机台图片尺寸不符合要求.');
            }
            return true;
        }

        if ($type == self::Loading) {
            if ($width != 400 || $height != 160) {
                throw new ApiException('加载Icon尺寸不符合要求.');
            }
            return true;
        }

        if ($type == self::HomeBanner) {
            if ($width != 649 || $height != 273) {
                throw new ApiException('广告图片尺寸不符合要求.');
            }
            return true;
        }

        if ($type === 1) {
            return true;
        }

        throw new ApiException('非预期的类型.', 200, 1, [
            $type, self::HomeBanner
        ]);
    }

}
