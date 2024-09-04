<?php

namespace App\Util\Upload;

use Illuminate\Support\Str;

class ImageUploadHandler
{
    // 只允许以下后缀名的图片文件上传
    protected $allowed_ext = ["png", "jpg", "gif", 'jpeg'];

    public function save($file, $folder, $file_prefix = '')
    {
        // 存储文件夹
        if ($folder) {
            $folderName = config('app.image_dir') . "/$folder/" . date("Ym/d", time());
        } else {
            $folderName = config('app.image_dir') . '/' . date("Ym/d", time());
        }

        // 文件具体存储的物理路径
        $upload_path = public_path($folderName);

        // 生成文件名
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'png';
        $filename = $file_prefix . '_' . time() . '_' . Str::random(5) . '.' . $extension;

        // 如果上传的不是图片将终止操作
        if (!in_array($extension, $this->allowed_ext)) {
            return false;
        }

        // 将图片移动到我们的目标存储路径中
        $file->move($upload_path, $filename);

        // 建立一个软连接指向uploads，尝试看看能否访问
        return [
            'path' => "/$folderName/$filename"
        ];
    }
}
