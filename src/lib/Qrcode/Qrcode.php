<?php
require_once 'config/config.php';

class Qrcode {
    private string $table;
    private string $redirect_url;

    const ALLOWED_FORMATS = ['png', 'gif', 'jpeg', 'jpg', 'svg', 'eps'];

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
            $this->failure('Filename must be between 1 and 45 characters.');
        }

        if (preg_match('#[\\/\\\\]#', $filename) || strpos($filename, '..') !== false || strpos($filename, "\0") !== false) {
            $this->failure('Filename cannot contain path separators.');
        }

        return $filename;
    }

    private function validateFormat($format) {
        $format = strtolower((string) $format);

        if (!in_array($format, self::ALLOWED_FORMATS, true)) {
            $this->failure('Invalid qr code format.');
        }

        return $format;
    }

    /**
     * Renders an optional text label below the qr code. Only supported for raster
     * formats (png/jpg/jpeg/gif) via GD; a no-op for svg/svgbw/eps.
     */
    private function addFrameText($path, $format, $text) {
        $text = trim((string) $text);
        $loaders = ['png' => 'imagecreatefrompng', 'jpg' => 'imagecreatefromjpeg', 'jpeg' => 'imagecreatefromjpeg', 'gif' => 'imagecreatefromgif'];
        $savers = ['png' => 'imagepng', 'jpg' => 'imagejpeg', 'jpeg' => 'imagejpeg', 'gif' => 'imagegif'];

        if ($text === '' || !isset($loaders[$format]) || !is_file($path)) {
            return;
        }

        $source = @$loaders[$format]($path);
        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $padding = 30;

        $canvas = imagecreatetruecolor($width, $height + $padding);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 0, 0, 0);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        $font = 5;
        $text_width = imagefontwidth($font) * strlen($text);
        $x = max(0, (int) (($width - $text_width) / 2));
        $y = $height + (int) (($padding - imagefontheight($font)) / 2);
        imagestring($canvas, $font, $x, $y, $text, $black);

        $savers[$format]($canvas, $path);

        imagedestroy($source);
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
            $size = min(max((int)$input_data['size'], 100), 1000);

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
        $options = $this->setOptions($input_data);

        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
        $data_to_db['format'] = $this->validateFormat($data_to_db['format']);

        if(!file_exists(SAVED_QRCODE_DIRECTORY.$data_to_db['filename'].'.'.$data_to_db['format'])){
            $url =
                'https://api.qrserver.com/v1/create-qr-code/?data='.
                $data_to_qrcode.
                '&amp;&size='.$options['size'].'x'.$options['size'].
                '&ecc='.$options['errorCorrectionLevel'].
                '&margin=0&color='.$options['foreground'].
                '&bgcolor='.$options['background'].
                '&qzone=2'.
                '&format='.$data_to_db['format'];

            $content = file_get_contents($url);
            
            $filename = SAVED_QRCODE_DIRECTORY.$data_to_db['filename'].'.'.$data_to_db['format'];

            try{
                file_put_contents($filename, $content);
            }
            catch(Exception $e){
                $this->failure($e->getMessage());
            }

            $this->addFrameText($filename, $data_to_db['format'], $input_data['frame_text'] ?? '');

            // If you want you can customi<e qr code with logo
            //$this->addLogo($data_to_db['qrcode'], $options['optionlogo']);
              
            $db = getDbInstance();
            $last_id = $db->insert($this->table, $data_to_db);
        }
        else
            $this->failure('You cannot create a new qr code with an existing name on the server!');
        
        if ($last_id){
            audit_log('qrcode_created', $this->table, $last_id);
            $this->success('Qr code added successfully!');
        }
        else {
            $this->failure('Insert failed: ' . $db->getLastError());
        }
    }
    
    /**
     * Batch-safe variant of addQrcode(): generates and stores the qr code but returns a
     * result array (['ok' => bool, 'id'|'error' => ...]) instead of redirecting/exiting,
     * so batch_qrcode.php can create many codes in one request. Deliberately does not
     * reuse addQrcode()/sanitizeFilename()/validateFormat(), since those call failure()
     * (redirect + exit) which would abort the whole batch after the first bad row.
     */
    public function addQrcodeBatch($input_data, $data_to_db, $data_to_qrcode) {
        $filename = trim((string) $data_to_db['filename']);
        $format = strtolower((string) $data_to_db['format']);

        if ($filename === '' || strlen($filename) > 45) {
            return ['ok' => false, 'error' => 'Filename must be between 1 and 45 characters.'];
        }

        if (preg_match('#[\\/\\\\]#', $filename) || strpos($filename, '..') !== false || strpos($filename, "\0") !== false) {
            return ['ok' => false, 'error' => 'Filename cannot contain path separators.'];
        }

        if (!in_array($format, self::ALLOWED_FORMATS, true)) {
            return ['ok' => false, 'error' => 'Invalid qr code format.'];
        }

        $data_to_db['filename'] = $filename;
        $data_to_db['format'] = $format;

        $path = SAVED_QRCODE_DIRECTORY.$filename.'.'.$format;

        if (file_exists($path)) {
            return ['ok' => false, 'error' => 'A qr code with this filename already exists.'];
        }

        $options = $this->setOptions($input_data);
        $url =
            'https://api.qrserver.com/v1/create-qr-code/?data='.
            $data_to_qrcode.
            '&amp;&size='.$options['size'].'x'.$options['size'].
            '&ecc='.$options['errorCorrectionLevel'].
            '&margin=0&color='.$options['foreground'].
            '&bgcolor='.$options['background'].
            '&qzone=2'.
            '&format='.$format;

        $content = @file_get_contents($url);
        if ($content === false) {
            return ['ok' => false, 'error' => 'Could not generate the qr code image.'];
        }

        if (@file_put_contents($path, $content) === false) {
            return ['ok' => false, 'error' => 'Could not write the qr code file.'];
        }

        $this->addFrameText($path, $format, $input_data['frame_text'] ?? '');

        $db = getDbInstance();
        $last_id = $db->insert($this->table, $data_to_db);

        if (!$last_id) {
            return ['ok' => false, 'error' => 'Insert failed: ' . $db->getLastError()];
        }

        audit_log('qrcode_created', $this->table, $last_id);

        return ['ok' => true, 'id' => $last_id];
    }

    /**
     * Edit qr code
     *
     */
    public function editQrcode($input_data, $data_to_db) {
        $db = getDbInstance();
        $old_qrcode = $this->getQrcode($input_data["id"]);

        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
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
