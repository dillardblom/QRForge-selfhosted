<?php
require_once 'config/config.php';
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\QRCode as QRCodeEx;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRImagick;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRGdImageJPEG;
use chillerlan\QRCode\Output\QREps;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\Settings\SettingsContainerInterface;

require_once __DIR__.'/../../vendor/autoload.php';

class Qrcode {
    private string $table;
    private string $redirect_url;

    const ALLOWED_FORMATS = ['png', 'gif', 'jpeg', 'jpg', 'svg', 'svgbw', 'eps'];

    /**
     *
     */
    public function __construct($type) {
        if($type === "static") {
            $this->table = "static_qrcodes";
            $this->redirect_url = "static_qrcodes.php";
        } else if($type === "dynamic") {
            $this->table = "dynamic_qrcodes";
            $this->redirect_url = "dynamic_qrcodes.php";
        } else {
            $this->redirect_url = "index.php";
            $this->failure("Type not allowed");
        }
    }

    /**
     *
     */
    public function __destruct()
    {
    }

    /**
     * Voorkomt path traversal / arbitrary file write via een gemanipuleerde bestandsnaam.
     */
    private function sanitizeFilename($filename) {
        $filename = trim((string) $filename);

        if ($filename === '' || strlen($filename) > 45) {
            throw new \InvalidArgumentException('Filename must be between 1 and 45 characters.');
        }

        if (preg_match('#[\\/\\\\]#', $filename) || strpos($filename, '..') !== false || strpos($filename, "\0") !== false) {
            throw new \InvalidArgumentException('Filename cannot contain path separators.');
        }

        return $filename;
    }

    private function validateFormat($format) {
        $format = strtolower((string) $format);

        if (!in_array($format, self::ALLOWED_FORMATS, true)) {
            throw new \InvalidArgumentException('Invalid qr code format.');
        }

        return $format;
    }

    const FRAME_FONT_DIR = '/usr/share/fonts/truetype/dejavu/';

    const ALLOWED_FRAME_FONTS = [
        'sans' => 'DejaVuSans.ttf',
        'sans-bold' => 'DejaVuSans-Bold.ttf',
        'serif' => 'DejaVuSerif.ttf',
        'serif-bold' => 'DejaVuSerif-Bold.ttf',
        'mono' => 'DejaVuSansMono.ttf',
        'mono-bold' => 'DejaVuSansMono-Bold.ttf',
    ];

    private static function resolveFrameFont($fontKey) {
        $file = self::ALLOWED_FRAME_FONTS[$fontKey] ?? self::ALLOWED_FRAME_FONTS['sans'];
        $path = self::FRAME_FONT_DIR . $file;

        return is_file($path) ? $path : null;
    }

    /**
     * Renders an optional text label below the qr code. Only supported for raster
     * formats (png/jpg/jpeg/gif) via GD; a no-op for svg/svgbw/eps.
     */
    private function addFrameText($path, $format, $text, $fontKey = 'sans', $fontSize = 16) {
        $text = trim((string) $text);
        $loaders = ['png' => 'imagecreatefrompng', 'jpg' => 'imagecreatefromjpeg', 'jpeg' => 'imagecreatefromjpeg', 'gif' => 'imagecreatefromgif'];
        $savers = ['png' => 'imagepng', 'jpg' => 'imagejpeg', 'jpeg' => 'imagejpeg', 'gif' => 'imagegif'];

        if ($text === '' || !isset($loaders[$format]) || !is_file($path)) {
            return;
        }

        $fontFile = self::resolveFrameFont($fontKey);
        $fontSize = min(max((int) $fontSize, 8), 60);

        $source = @$loaders[$format]($path);
        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $padding = $fontFile !== null ? $fontSize + 20 : 30;

        $canvas = imagecreatetruecolor($width, $height + $padding);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 0, 0, 0);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        if ($fontFile !== null && function_exists('imagettftext')) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
            $text_width = abs($bbox[2] - $bbox[0]);
            $text_height = abs($bbox[1] - $bbox[7]);
            $x = max(0, (int) (($width - $text_width) / 2));
            $y = $height + (int) (($padding + $text_height) / 2);
            imagettftext($canvas, $fontSize, 0, $x, $y, $black, $fontFile, $text);
        } else {
            $font = 5;
            $text_width = imagefontwidth($font) * strlen($text);
            $x = max(0, (int) (($width - $text_width) / 2));
            $y = $height + (int) (($padding - imagefontheight($font)) / 2);
            imagestring($canvas, $font, $x, $y, $text, $black);
        }

        $savers[$format]($canvas, $path);

        imagedestroy($source);
        imagedestroy($canvas);
    }

    /**
     * Renders an optional user-uploaded icon above the qr code (not embedded inside
     * it, so scanability is unaffected). Only supported for raster formats via GD.
     */
    private function addTopIcon($path, $format, $iconPath) {
        $loaders = ['png' => 'imagecreatefrompng', 'jpg' => 'imagecreatefromjpeg', 'jpeg' => 'imagecreatefromjpeg', 'gif' => 'imagecreatefromgif'];
        $savers = ['png' => 'imagepng', 'jpg' => 'imagejpeg', 'jpeg' => 'imagejpeg', 'gif' => 'imagegif'];

        if (!$iconPath || !is_file($iconPath) || !isset($loaders[$format]) || !is_file($path)) {
            return;
        }

        $iconInfo = @getimagesize($iconPath);
        $iconLoaders = [IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_GIF => 'imagecreatefromgif'];

        if ($iconInfo === false || !isset($iconLoaders[$iconInfo[2]])) {
            return;
        }

        $source = @$loaders[$format]($path);
        $icon = @$iconLoaders[$iconInfo[2]]($iconPath);

        if ($source === false || $icon === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $iconWidth = imagesx($icon);
        $iconHeight = imagesy($icon);

        $maxIconHeight = (int) ($height * 0.625);
        $scale = min($maxIconHeight / $iconHeight, ($width * 0.6) / $iconWidth, 1);
        $targetWidth = max(1, (int) ($iconWidth * $scale));
        $targetHeight = max(1, (int) ($iconHeight * $scale));

        $margin = 15;
        $topPadding = $targetHeight + ($margin * 2);

        $canvas = imagecreatetruecolor($width, $height + $topPadding);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $resizedIcon = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($resizedIcon, false);
        imagesavealpha($resizedIcon, true);
        $transparent = imagecolorallocatealpha($resizedIcon, 0, 0, 0, 127);
        imagefill($resizedIcon, 0, 0, $transparent);
        imagealphablending($icon, true);
        imagecopyresampled($resizedIcon, $icon, 0, 0, 0, 0, $targetWidth, $targetHeight, $iconWidth, $iconHeight);

        $x = (int) (($width - $targetWidth) / 2);
        imagecopy($canvas, $resizedIcon, $x, $margin, 0, 0, $targetWidth, $targetHeight);
        imagecopy($canvas, $source, 0, $topPadding, 0, 0, $width, $height);

        $savers[$format]($canvas, $path);

        imagedestroy($source);
        imagedestroy($icon);
        imagedestroy($resizedIcon);
        imagedestroy($canvas);
    }

    public function getQrcode($id) {
        $db = getDbInstance();

        $db->where('id', $id);
        $result = $db->getOne($this->table);

        if($result !== NULL)
            return $result;
        else
            $this->failure("Qrcode not found");
    }
    
    /**
     * Set option for qr code like:
     * Error Correction Level, size (default = 100), foreground, background
     * return array of values
     */
    private function setOptions($input_data) {
        $errorCorrectionLevel = 'L';

        if (isset($input_data['level']) && in_array($input_data['level'], array('L','M','Q','H')))
            $errorCorrectionLevel = $input_data['level'];
      
        $size = 100;
        if (isset($input_data['size']))
            $size = min(max((int)$input_data['size'], 100), 2000);

        //character # deleted
        $foreground = substr($input_data['foreground'], 1);
        $background = substr($input_data['background'], 1);

        //$logo = $_POST['optionlogo'];
           
        return array(
            "errorCorrectionLevel" => $errorCorrectionLevel,
            "size" => $size,
            "foreground" => $foreground,
            "background" => $background,
            //"optionlogo" => $logo,
        );
    }
    
    /**
     * Add qr code
     * Check out http://goqr.me/api/ for more information
     * We save the file obtained with the chosen name and in the selected folder
     * We save into db the url of qrcode image
     */
    public function addQrcode($input_data, $data_to_db, $data_to_qrcode) {
        try {
            $last_id = $this->renderAndStore($input_data, $data_to_db, $data_to_qrcode);
        } catch (\Throwable $e) {
            $this->failure($e->getMessage());
        }

        audit_log('qrcode_created', $this->table, $last_id);
        $this->success('Qr code added successfully!');
    }

    /**
     * Batch-safe variant of addQrcode(): generates and stores the qr code but returns a
     * result array (['ok' => bool, 'id'|'error' => ...]) instead of redirecting/exiting,
     * so batch_qrcode.php can create many codes in one request.
     */
    public function addQrcodeBatch($input_data, $data_to_db, $data_to_qrcode) {
        try {
            $last_id = $this->renderAndStore($input_data, $data_to_db, $data_to_qrcode);
            audit_log('qrcode_created', $this->table, $last_id);
            return ['ok' => true, 'id' => $last_id];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Core qr code rendering + storage, shared by addQrcode() and addQrcodeBatch().
     * Throws instead of calling failure() so batch processing can catch and continue.
     */
    private function renderAndStore($input_data, $data_to_db, $data_to_qrcode) {
        $options = $this->setOptions($input_data);

        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
        $data_to_db['format'] = $this->validateFormat($data_to_db['format']);

        $outputInterface = QRGdImagePNG::class;
        $imageFormat = strtolower($data_to_db['format']);
        $fileExt = $imageFormat;
        $forceBlackWhite = false;


         switch ($imageFormat)
         {
             case 'png':
                 $outputInterface = QRGdImagePNG::class;
                 $imageFormat = 'png';
                 break;
             case 'gif':
                 $outputInterface = QRImagick::class;
                 $imageFormat = 'gif';
                 break;
             case 'jpg':
                 $outputInterface = QRImagick::class;
                 $imageFormat = 'jpg';
                 break;
             case 'jpeg':
                 $outputInterface = QRImagick::class;
                 $imageFormat = 'jpeg';
                 break;
             case 'svg':
                 # $outputInterface = QRImagick::class;
                 $outputInterface = QRMarkupSVG::class;
                 $imageFormat = 'svg';
                 break;
             case 'svgbw':
                 $outputInterface = QRMarkupSVG::class;
                 $imageFormat = 'svg';
                 $fileExt = 'svg';
                 $forceBlackWhite = true;
                 $data_to_db['format'] = $fileExt;
                 $data_to_db['qrcode'] = str_replace('.svgbw', '.svg', $data_to_db['qrcode']);
                 break;
             case 'eps':
                 $outputInterface = QREps::class;
                 $imageFormat = 'eps';
                 break;
         }


        if(!file_exists(SAVED_QRCODE_DIRECTORY.$data_to_db['filename'].'.' . $fileExt)){
            $eccLevel = constant(EccLevel::class.'::'.strtoupper($options['errorCorrectionLevel']));

            $qroptions                       = new QROptions;
            $qroptions->outputInterface      = $outputInterface;
            $qroptions->outputBase64         = false;
            $qroptions->eccLevel             = $eccLevel;
            $qroptions->quietzoneSize        = 2;

            $moduleValues = [
                // finder
                QRMatrix::M_FINDER_DARK    => $options['foreground'],
                QRMatrix::M_FINDER_DOT     => $options['foreground'],
                QRMatrix::M_FINDER         => $options['background'],
                // alignment
                QRMatrix::M_ALIGNMENT_DARK => $options['foreground'],
                QRMatrix::M_ALIGNMENT      => $options['background'],
                // timing
                QRMatrix::M_TIMING_DARK    => $options['foreground'],
                QRMatrix::M_TIMING         => $options['background'],
                // format
                QRMatrix::M_FORMAT_DARK    => $options['foreground'],
                QRMatrix::M_FORMAT         => $options['background'],
                // version
                QRMatrix::M_VERSION_DARK   => $options['foreground'],
                QRMatrix::M_VERSION        => $options['background'],
                // data
                QRMatrix::M_DATA_DARK      => $options['foreground'],
                QRMatrix::M_DATA           => $options['background'],
                // darkmodule
                QRMatrix::M_DARKMODULE     => $options['foreground'],
                // separator
                QRMatrix::M_SEPARATOR      => $options['background'],
                // quietzone
                QRMatrix::M_QUIETZONE      => $options['background'],
            ];

            if(in_array($data_to_db['format'], ['png', 'jpg', 'gif'], true))
            {
                $moduleValues = array_map(function($v)
                {
                    if(preg_match('/[a-f\d]{6}/i', $v) === 1)
                    {
                        return array_map('hexdec', str_split($v, 2));
                    }

                    return null;
                }, $moduleValues);

                $qroptions->moduleValues = $moduleValues;
            }
            else
            {
                $moduleValues = array_map(function($v)
                {
                    if(preg_match('/[a-f\d]{6}/i', $v) === 1)
                    {
                        return '#' . $v ;
                    }

                    return null;
                }, $moduleValues);

                $qroptions->moduleValues = $moduleValues;
            }

            if ($outputInterface === QRMarkupSVG::class)
            {
                $qroptions->version              = Version::AUTO;
                // if set to false, the light modules won't be rendered
                $qroptions->drawLightModules     = true;
                $qroptions->svgUseFillAttributes = false;
                // draw the modules as circles isntead of squares
                $qroptions->drawCircularModules  = true;
                $qroptions->circleRadius         = 0.4;
                // connect paths
                $qroptions->connectPaths         = true;
                // keep modules of these types as square
                $qroptions->keepAsSquare = [
                    QRMatrix::M_FINDER_DARK,
                    QRMatrix::M_FINDER_DOT,
                    QRMatrix::M_ALIGNMENT_DARK,
                ];
                
                if($forceBlackWhite)
                {
                    $qroptions->drawLightModules     = false;
                    $qroptions->drawCircularModules  = false;
                    // https://developer.mozilla.org/en-US/docs/Web/SVG/Element/linearGradient
                    $qroptions->svgDefs = '
                        <linearGradient id="rainbow" x1="1" y2="1">
                            <stop stop-color="#' . $options['foreground'] . '" offset="0"/>
                            <stop stop-color="#' . $options['foreground'] . '" offset="1"/>
                        </linearGradient>
                        <style><![CDATA[
                            .dark{fill: url(#rainbow);}
                            .light{fill: #eee;}
                        ]]></style>';
                }
                else
                {
                  // https://developer.mozilla.org/en-US/docs/Web/SVG/Element/linearGradient
                    $qroptions->svgDefs             = '
                        <linearGradient id="rainbow" x1="1" y2="1">
                            <stop stop-color="#e2453c" offset="0"/>
                            <stop stop-color="#e07e39" offset="0.2"/>
                            <stop stop-color="#e5d667" offset="0.4"/>
                            <stop stop-color="#51b95b" offset="0.6"/>
                            <stop stop-color="#1e72b7" offset="0.8"/>
                            <stop stop-color="#6f5ba7" offset="1"/>
                        </linearGradient>
                        <style><![CDATA[
                            .dark{fill: url(#rainbow);}
                            .light{fill: #eee;}
                        ]]></style>';
                }
            }
            else
            {
                $qroptions->version         = Version::AUTO;
                $qroptions->scale           = 20;
                $qroptions->quality         = 83;
            }

            if ($outputInterface === QRImagick::class)
            {
                $qroptions->imagickFormat       = $imageFormat;
                $qroptions->returnResource = true;
                $imagick = (new QRCodeEx($qroptions))->render(urldecode($data_to_qrcode));
                $imagick->scaleImage($options['size'], $options['size'], true);
                $content = $imagick->getImageBlob();
                $imagick->destroy();
            }
            else
            {
                $content = (new QRCodeEx($qroptions))->render(urldecode($data_to_qrcode));
            }

            $filename = SAVED_QRCODE_DIRECTORY.$data_to_db['filename'].'.' . $fileExt;

            try
            {
                file_put_contents($filename, $content);
                if ($outputInterface !== QRImagick::class &&
                    $outputInterface !== QRMarkupSVG::class)
                {
                    $imagick = new \Imagick(realpath($filename));
                    $imagick->resizeImage($options['size'], $options['size'], imagick::FILTER_LANCZOS, 1, false);
                    $imagick->writeImage($filename);
                    $imagick->destroy();
                }
            }
            catch(Exception $e)
            {
                throw new \RuntimeException($e->getMessage());
            }

            $this->addTopIcon($filename, $fileExt, $input_data['icon_tmp_path'] ?? null);
            $this->addFrameText($filename, $fileExt, $input_data['frame_text'] ?? '', $input_data['frame_font'] ?? 'sans', $input_data['frame_font_size'] ?? 16);

            if (!empty($input_data['icon_tmp_path'])) {
                @unlink($input_data['icon_tmp_path']);
            }

            // If you want you can customi<e qr code with logo
            //$this->addLogo($data_to_db['qrcode'], $options['optionlogo']);

            $db = getDbInstance();
            $last_id = $db->insert($this->table, $data_to_db);
        }
        else
            throw new \RuntimeException('You cannot create a new qr code with an existing name on the server!');

        if (!$last_id) {
            throw new \RuntimeException('Insert failed: ' . $db->getLastError());
        }

        return $last_id;
    }

    /**
     * Edit qr code
     *
     */
    public function editQrcode($input_data, $data_to_db) {
        $db = getDbInstance();
        $old_qrcode = $this->getQrcode($input_data["id"]);

        try {
            $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
        } catch (\InvalidArgumentException $e) {
            $this->failure($e->getMessage());
        }
        $data_to_db['qrcode'] = $data_to_db['filename'].'.'.$old_qrcode["format"];

        if(!file_exists(SAVED_QRCODE_DIRECTORY.$data_to_db['filename'].'.'.$old_qrcode["format"]) || $data_to_db['filename'] == $input_data["old_filename"]){
            $db->where('id', $input_data["id"]);
            $stat = $db->update($this->table, $data_to_db);
            
            try{
                rename(SAVED_QRCODE_DIRECTORY.$old_qrcode["qrcode"], SAVED_QRCODE_DIRECTORY.$data_to_db['filename'].'.'.$old_qrcode["format"]);
            }
            catch(Exception $e){
                $this->failure($e->getMessage());
            }
        }
        else
            $this->failure('You cannot edit a qr code with an existing name on the server!');
        
        if ($stat){
            audit_log('qrcode_updated', $this->table, $input_data['id']);
            $this->success('Qr code updated successfully!');
        }
        else {
            $this->failure('Insert failed: ' . $db->getLastError());
        }
    }


    /**
     * Delete qr code
     *
     */
    public function deleteQrcode($id, $async = false) {
        $db = getDbInstance();

        $qrcode = $this->getQrcode($id);

        $db->where('id', $id);
        $status = $db->delete($this->table);

        if ($status) {
            audit_log('qrcode_deleted', $this->table, $id);
        }

        try{
            unlink(SAVED_QRCODE_DIRECTORY.$qrcode["filename"].'.'.$qrcode["format"]);
        }
        catch(Exception $e){
            $this->failure($e->getMessage());
        }

        if ($status)
            if (!$async) {
                $this->info('Qr code deleted successfully!');
            }
        else
            if (!$async) {
                $this->failure('Unable to delete qr code');
            }
    }
    
    /**
     * Add logo
     * IMPORTANT: I do not recommend to use this option because there may be problems with the scanning of the qr code as some readers may not recognize the code
     */
    private function addLogo($src, $logo = 'none') {
        try
        {
            if($logo != 'none')
            {
                $logo = imagecreatefrompng($_SERVER['HTTP_HOST'].'/admin'.$logo);
                $QR = imagecreatefrompng(BASE_PATH.$src);
            
	            $QR_width = imagesx($QR);
	            $QR_height = imagesy($QR);
	
	            $logo_width = imagesx($logo);
	            $logo_height = imagesy($logo);
	
	            // Scale logo to fit in the QR Code
	            $logo_qr_width = $QR_width/3;
	            $scale = $logo_width/$logo_qr_width;
	            $logo_qr_height = $logo_height/$scale;
	            
	            // You can try also with imagecopymerge() with same arguments
	            imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
	    
	            //$output = Set directory for saving image;
	            header('Content-Type: image/png'); 
	            imagepng($QR /*, $output*/); 
                imagedestroy($QR);
            }
        }
        catch(Exception $e)
        {
            $this->failure($e->getMessage());
        }
    }

    /**
     * Flash message Failure process
     */
    private function failure($message) {
        $_SESSION['failure'] = $message;
        header('Location: ' . $this->redirect_url);
    	exit();
    }
    
    /**
     * Flash message Success process
     */
    private function success($message) {
        $_SESSION['success'] = $message;
        header('Location: ' . $this->redirect_url);
    	exit();
    }
    
    /**
     * Flash message Info process
     */
    private function info($message) {
        $_SESSION['info'] = $message;
        header('Location: ' . $this->redirect_url);
    	exit();
    }

    public function debug($data) {
        echo '<pre>' . var_export($data, true) . '</pre>';
        exit();
    }
}
?>
