<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/
class File
{

    public static function copyFolder($from, $to, $exclude = [])
    {
        $files = scandir($from);
        foreach ($files as $file) {
            if (is_file($from . $file) && !in_array($file, $exclude)) {
                if (file_exists($to . $file)) unlink($to . $file);
                rename($from . $file, $to . $file);
            } else if (is_dir($from . $file) && !in_array($file, ['.', '..'])) {
                if (!file_exists($to . $file)) {
                    mkdir($to . $file);
                }
                File::copyFolder($from . $file . DIRECTORY_SEPARATOR, $to . $file . DIRECTORY_SEPARATOR, $exclude);
            }
        }
    }

    public static function deleteFolder($path)
    {
        $files = scandir($path);
        foreach ($files as $file) {
            if (is_file($path . $file)) {
                unlink($path . $file);
            } else if (is_dir($path . $file) && !in_array($file, ['.', '..'])) {
                File::deleteFolder($path . $file . DIRECTORY_SEPARATOR);
                rmdir($path . $file);
            }
        }
        rmdir($path);
    }

    public static function resizeCropImage($source_file, $dst_dir, $max_width, $max_height, $quality = 80)
    {
        $imgsize = getimagesize($source_file);
        $width = $imgsize[0];
        $height = $imgsize[1];
        $mime = $imgsize['mime'];

        switch ($mime) {
            case 'image/gif':
                $image_create = "imagecreatefromgif";
                $image = "imagegif";
                break;

            case 'image/png':
                $image_create = "imagecreatefrompng";
                $image = "imagepng";
                $quality = 7;
                break;

            case 'image/jpeg':
                $image_create = "imagecreatefromjpeg";
                $image = "imagejpeg";
                $quality = 80;
                break;

            default:
                return false;
                break;
        }

        if ($max_width == 0) {
            $max_width  = $width;
        }

        if ($max_height == 0) {
            $max_height = $height;
        }

        $widthRatio = $max_width / $width;
        $heightRatio = $max_height / $height;
        $ratio = min($widthRatio, $heightRatio);
        $nwidth  = (int)$width  * $ratio;
        $nheight = (int)$height * $ratio;

        $dst_img = imagecreatetruecolor($nwidth, $nheight);
        $white = imagecolorallocate($dst_img, 255, 255, 255);
        imagefill($dst_img, 0, 0, $white);
        $src_img = $image_create($source_file);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $nwidth, $nheight, $width, $height);

        imagepng($dst_img, $dst_dir);

        if ($dst_img) imagedestroy($dst_img);
        if ($src_img) imagedestroy($src_img);
        return file_exists($dst_dir);
    }

    public static function makeThumb($srcFile, $thumbFile, $thumbSize = 200)
    {
        /* Determine the File Type */
        $type = substr($srcFile, strrpos($srcFile, '.') + 1);
        $imgsize = getimagesize($srcFile);
        $oldW = $imgsize[0];
        $oldH = $imgsize[1];
        $mime = $imgsize['mime'];
        switch ($mime) {
            case 'image/gif':
                $src = imagecreatefromgif($srcFile);
                break;

            case 'image/png':
                $src = imagecreatefrompng($srcFile);
                break;

            case 'image/jpeg':
                $src = imagecreatefromjpeg($srcFile);
                break;

            default:
                return false;
                break;
        }
        /* Calculate the New Image Dimensions */
        $limiting_dim = 0;
        if ($oldH > $oldW) {
            /* Portrait */
            $limiting_dim = $oldW;
        } else {
            /* Landscape */
            $limiting_dim = $oldH;
        }
        /* Create the New Image */
        $new = imagecreatetruecolor($thumbSize, $thumbSize);
        /* Transcribe the Source Image into the New (Square) Image */
        imagecopyresampled($new, $src, 0, 0, ($oldW - $limiting_dim) / 2, ($oldH - $limiting_dim) / 2, $thumbSize, $thumbSize, $limiting_dim, $limiting_dim);
        imagejpeg($new, $thumbFile, 100);
        imagedestroy($new);
        return file_exists($thumbFile);
    }

    /**
     * file path fixer
     *
     * @access public
     * @param string $path
     * @return string
     */
    public static function pathFixer($path)
    {
        return str_replace("/", DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Handle an uploaded profile photo (field name "photo") for a user/customer/admin record.
     *
     * Stores the resized image and thumbnail under {UPLOAD_PATH}/photos/{hash-prefix}/,
     * removes the record's previous photo (unless it is a default), and assigns the new
     * relative path to $record->{photoField}. When PHP GD is unavailable the caller is
     * redirected via r2() (same behaviour as the inline code this replaces).
     *
     * @param object $record       ORM record exposing the photo field via array access and magic setter
     * @param string $UPLOAD_PATH  absolute uploads directory
     * @param string $photoField   record field to update (default "photo")
     * @return bool  true when a photo was processed, false when there was nothing to do
     */
    public static function handleUserPhotoUpload($record, $UPLOAD_PATH, $photoField = 'photo')
    {
        if (empty($_FILES['photo']['name']) || !file_exists($_FILES['photo']['tmp_name'])) {
            return false;
        }
        if (!function_exists('imagecreatetruecolor')) {
            r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
            return false;
        }
        $hash = md5_file($_FILES['photo']['tmp_name']);
        $subfolder = substr($hash, 0, 2);
        $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        $imgPath = $folder . $hash . '.jpg';
        if (!file_exists($imgPath)) {
            File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
        }
        if (!file_exists($imgPath . '.thumb.jpg')) {
            if (_post('faceDetect') == 'yes') {
                try {
                    $detector = new \svay\FaceDetector();
                    $detector->setTimeout(5000);
                    $detector->faceDetect($imgPath);
                    $detector->cropFaceToJpeg($imgPath . '.thumb.jpg', false);
                } catch (Exception $e) {
                    File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                } catch (Throwable $e) {
                    File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                }
            } else {
                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
            }
        }
        if (file_exists($imgPath)) {
            if ($record[$photoField] != '' && strpos($record[$photoField], 'default') === false) {
                if (file_exists($UPLOAD_PATH . $record[$photoField])) {
                    unlink($UPLOAD_PATH . $record[$photoField]);
                    if (file_exists($UPLOAD_PATH . $record[$photoField] . '.thumb.jpg')) {
                        unlink($UPLOAD_PATH . $record[$photoField] . '.thumb.jpg');
                    }
                }
            }
            $record->$photoField = '/photos/' . $subfolder . '/' . $hash . '.jpg';
        }
        if (file_exists($_FILES['photo']['tmp_name'])) {
            unlink($_FILES['photo']['tmp_name']);
        }
        return true;
    }

    /**
     * Download a GitHub repository archive (master branch) into $CACHE_PATH and extract it.
     *
     * Applies the configured GitHub credentials to the URL when present, then returns the
     * extracted top-level folder (handling both "-main" and "-master" naming), or false when
     * no extracted folder can be found.
     *
     * @param string $githubUrl   base GitHub repository URL
     * @param array  $config       app config (uses github_token / github_username)
     * @param string $CACHE_PATH   absolute cache directory to extract into
     * @param string $zipFile      absolute path to write the downloaded zip to
     * @param string $pluginId     plugin/repo id used to locate the extracted folder
     * @return string|false        extracted folder path, or false when not found
     */
    public static function downloadGithubPluginZip($githubUrl, $config, $CACHE_PATH, $zipFile, $pluginId)
    {
        if (!empty($config['github_token']) && !empty($config['github_username'])) {
            $githubUrl = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $githubUrl);
        }
        $fp = fopen($zipFile, 'w+');
        $ch = curl_init($githubUrl . '/archive/refs/heads/master.zip');
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        $zip = new ZipArchive();
        $zip->open($zipFile);
        $zip->extractTo($CACHE_PATH);
        $zip->close();
        $folder = $CACHE_PATH . File::pathFixer('/' . $pluginId . '-main/');
        if (!file_exists($folder)) {
            $folder = $CACHE_PATH . File::pathFixer('/' . $pluginId . '-master/');
        }
        if (!file_exists($folder)) {
            return false;
        }
        return $folder;
    }
}
