<?php

namespace App\Controllers;

use App\Libraries\Session;
use App\Libraries\Uploader;
use CodeIgniter\Controller;
use App\Models\User;

class Upload extends BaseController
{
    public function index()
    {
        Session::delete();
        session()->destroy();
        return view('test');
    }

    public function uploadImage()
    {

        // return json_encode($_FILES['images-upload']);
        if (isset($_FILES['images-upload'])) {
            $errors = [];
            $fileNames = [];
            $totalSize = 0;
            $fileTypes = ['image/jpeg', 'image/png', 'image/gif'];

            // Loop through each file
            foreach ($_FILES['images-upload']['tmp_name'] as $key => $tmp_name) {
                $fileName = $_FILES['images-upload']['name'][$key];
                $fileSize = $_FILES['images-upload']['size'][$key];
                $fileType = $_FILES['images-upload']['type'][$key];
                $fileError = $_FILES['images-upload']['error'][$key];
                $fileTmp = $_FILES['images-upload']['tmp_name'][$key];

                // Check for errors
                if ($fileError !== UPLOAD_ERR_OK) {
                    $errors[] = "Error uploading file {$fileName}: " . $fileError;
                    continue;
                }

                // Check file size
                if ($fileSize > 1000000) {
                    $errors[] = "Error uploading file {$fileName}: File size is too large";
                    continue;
                }

                // Move the file to the upload directory
                $uploader = new Uploader();

                $result = $uploader->uploadImage($fileTmp, $fileName);
                if (!$result) {
                    $errors[] = "Error uploading file {$fileName}: Failed to move file";
                    continue;
                }

                $fileNames[] = $fileName;
                $totalSize += $fileSize;
            }

            // Print error messages or success message
            if (count($errors) > 0) {
                echo implode('<br>', $errors);
            } else {
                echo "Files uploaded successfully. Total file size: " . $totalSize . " bytes";
            }
        }
    }
}
