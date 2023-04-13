<?php

namespace App\Libraries;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class Uploader
{
    public $dropbox;
    public function __construct()
    {
        $myKey = CLOUDINARY_API_KEY;
        $mySecret = CLOUDINARY_API_SECRET;
        $cloudName = CLOUDINARY_NAME;

        Configuration::instance("cloudinary://{$myKey}:{$mySecret}@{$cloudName}?secure=true");
    }

    public function uploadImage($tempFile, $fileName)
    {
        if (isset($tempFile)) {
            $newFileName = 'my-new-file-name.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $upload = new UploadApi();
            $imageUpload = $upload->upload($tempFile, array("folder" => "chatvia_uploads_php", "resource_type" => "auto"));
            return $imageUpload['secure_url'];
        }
        return null;
    }

    public function uploadBase64($image) {
        try {
            $upload = new UploadApi();
            $imageUpload = $upload->upload($image, array("folder" => "chatvia_uploads_php", "resource_type" => "auto"));
            return $imageUpload['secure_url'];
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function uploadPath($path) {
        try {
            $upload = new UploadApi();
            $imageUpload = $upload->upload($path, array("folder" => "chatvia_uploads_php", "resource_type" => "auto"));
            return $imageUpload;
        } catch (\Throwable $th) {
            return null;
        }
    } 
}
