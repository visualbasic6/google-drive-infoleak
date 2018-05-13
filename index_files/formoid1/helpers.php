<?php if (!defined('_DIR_')) exit();

/** magic quotes (info: http://bit.ly/14MGaMW) */
if (version_compare(PHP_VERSION, '5.4.0', '<')){
    if (get_magic_quotes_gpc()){
        $process = array(&$_GET, &$_POST);
        while (list($key, $val) = each($process)) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = &$process[$key][stripslashes($k)];
                } else {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }
        unset($process);
    } else if (version_compare(PHP_VERSION, '5.3.0', '>='))
        ini_set('magic_quotes_runtime', 0);
    else set_magic_quotes_runtime(0);
}

/** file_get_contents */
if (!function_exists('file_get_contents')){
    function file_get_contents($path){
        if (!file_exists($path)) return false;
        $fh = fopen($path, 'r');
        $data = fread($fh, filesize($path));
        fclose($fh);
        return $data;
    }
}

/** file_put_contents */
if (!function_exists('file_put_contents')){
    function file_put_contents($path, $data){
        if (!$fp = fopen($path, 'a')) return false;
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        fputs($fp, $data);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
}

/** mb_strlen */
if (!function_exists('mb_strlen')){
    function mb_strlen($s){
        return preg_match_all('/[\x00-\x7F\xC0-\xFD]/', $s, $d);
    }
} else if (function_exists('mb_internal_encoding')){
    mb_internal_encoding(
        (defined('PAGE_ENCODING') ? PAGE_ENCODING : 'UTF-8')
    );
}

/** json_decode */
if (!function_exists('json_decode')){
    require_once _DIR_ . 'JSON.php';
    function json_decode($json, $assoc = false){
        if ($assoc) $obj = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        else $obj = new Services_JSON();
        return $obj -> decode($json);
    }
}

?>
