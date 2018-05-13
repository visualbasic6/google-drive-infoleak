<?php

define('PROJECT_FILE', _DIR_ . 'form.formoid');
include _DIR_ . 'helpers.php';

function frmd_message(){
    if (isset($_GET['success']) && 'true' == $_GET['success'])
        return true;
    return false;
}

function frmd_ready(){
    if ('redirect' == FINISH_ACTION) exit(header('Location: ' . FINISH_URI));
    exit(header('Location: ' . frmd_action() . '?success=true'));
}

function frmd_error($msg = '', $field = ''){
    static $error = array();
    if ($num = func_num_args()){
        frmd_delete_uploaded_files();
        if (1 == $num) exit($msg);
        $error = func_get_args();
        return;
    }
    return $error;
}

function frmd_add_class($field = ''){
    static $last_error = null;
    if (is_null($last_error)) $last_error = frmd_error();
    if ($last_error && $field == $last_error[1])
        echo ' error-field';
}

function frmd_action($only_path = false){
    $url = 'http://' . $_SERVER['HTTP_HOST'] .
        preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
    if ($only_path) $url = preg_replace('/\/[^\/]+$/', '/', $url);
    return $url;
}

function frmd_captcha_is_valid(&$request){
    require_once _DIR_ . 'recaptchalib.php';
    foreach (array(
            'recaptcha_challenge_field',
            'recaptcha_response_field'
        ) as $key){
        if (!isset($request[$key]))
            $request[$key] = '';
    }
    $resp = recaptcha_check_answer(
        RECAPTCHA_PRIVATE_KEY,
        $_SERVER['REMOTE_ADDR'],
        $request['recaptcha_challenge_field'],
        $request['recaptcha_response_field']
    );
    return $resp -> is_valid;
}

function frmd_upload_file($field){
    if (!isset($_FILES[$field])) return '';
    $file = &$_FILES[$field];
    $upload_error_strings = array(
        false,
        'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        'The uploaded file was only partially uploaded.',
        'No file was uploaded.',
        '',
        'Missing a temporary folder.',
        'Failed to write file to disk.',
        'File upload stopped by extension.'
    );
    if ($file['error'] > 0){
        if ($file['error'] == 4) return '';
        return frmd_error($upload_error_strings[ $file['error'] ], $field);
    }
    $allowed = implode('|', array_map('trim', explode(',', UPLOAD_ALLOWED_FILE_TYPES)));
    if (!preg_match('/\.(' . $allowed . ')$/i', $file['name'], $match))
        return frmd_error('Sorry, this file type is not permitted for security reasons.', $field);
    $ext = strtolower($match[0]);
    $path = defined('UPLOAD_DIR_PATH') ? rtrim(UPLOAD_DIR_PATH, '/') . '/' : './uploads/';
    $folders = array('', date('Y/'), date('m/'));
    foreach ($folders as $folder){
        $path .= $folder;
        if (!is_dir($path) && !@mkdir($path))
            return frmd_error('Cannot create folder "' . $path . '"');
        if ('' == $folder && !is_file($path . '.htaccess')){
            $status = @file_put_contents($path . '.htaccess', '# Don\'t show directory listings for URLs which map to a directory.
Options -Indexes
# Disable handling
RemoveHandler .phtml .php .php3 .php4 .php5 .php6 .phps .cgi .exe .pl .asp .aspx .shtml .shtm .fcgi .fpl .jsp .htm .html .wml
AddType application/x-httpd-php-source .phtml .php .php3 .php4 .php5 .php6 .phps .cgi .exe .pl .asp .aspx .shtml .shtm .fcgi .fpl .jsp .htm .html .wml');
            if (!$status) return frmd_error('Cannot create file "' . $path . '.htaccess"');
        }
    }
    if (function_exists('uniqid')) $filename = md5(uniqid(md5(rand()), true));
    else {
        $filename = array(microtime());
        $filename[] = $_SERVER['REMOTE_ADDR'];
        $filename[] = rand(1, 65535);
        $filename = md5(implode('@', $filename));
    }
    $filename .= $ext;
    if (!@move_uploaded_file($file['tmp_name'], $path . $filename))
        return frmd_error('The uploaded file could not be moved to ' . $path . '.');
    frmd_delete_uploaded_files($path . $filename);
    return frmd_uploaded_file(implode('', $folders) . $filename);
}

function frmd_uploaded_file($file){
    if (defined('UPLOAD_DIR_URL')) $url = rtrim(UPLOAD_DIR_URL, '/') . '/';
    else $url = frmd_action(true) . 'uploads/';
    return $url . $file;
}

function frmd_delete_uploaded_files(){
    static $files = array();
    if (func_num_args()){
        $files[] = func_get_arg(0);
        return true;
    }
    foreach ($files as $file)
        @unlink($file);
    return true;
}

function frmd_mail($report, $subject = ''){
    if (!defined('EMAIL_FOR_REPORTS') || !EMAIL_FOR_REPORTS) return false;
    if (!$subject){
            $subject = 'Report from ' . $_SERVER['HTTP_HOST'] .
                ' at ' . strftime('%m/%d/%y %H:%M %p');
    }
    $charset = defined('PAGE_ENCODING') ? PAGE_ENCODING : 'UTF-8';
    if (defined('EMAIL_SENDER') && EMAIL_SENDER) $sender = EMAIL_SENDER;
    else $sender = 'robot@' . $_SERVER['HTTP_HOST'];
    $headers  = "From: " . $sender . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/plain; charset=" . $charset . "\r\n";
    $headers .="Content-Transfer-Encoding: 8bit";
    return mail(EMAIL_FOR_REPORTS, "=?" . $charset . "?B?" . base64_encode($subject) . '?=', $report, $headers);
}

function frmd_pre_save($elm = array(), $value = '', $original = ''){
    static $data = array();
    if (!func_num_args()) return $data;
    $data[ $elm['name'] ] = array(
        'title' => frmd_label($elm),
        'type'  => $elm['type'],
        'value' => $original ? $original : $value
    );
}

function frmd_csv_row($row, $comma = ',', $quote = '"', $end = "\n"){
    $csv = '';
    for ($i = 0, $count = count($row); $i < $count; $i++){
        $csv .= $quote . str_replace($quote, $quote . $quote, $row[$i]) . $quote;
        $csv .= $i < ($count - 1) ? $comma : $end;
    }
    return $csv;
}

function frmd_save(){
    static $assoc = array(
        'first'   => 'First Name',
        'last'    => 'Last Name',
        'addr1'   => 'Street',
        'addr2'   => 'Extended',
        'city'    => 'City',
        'state'   => 'Region',
        'zip'     => 'Postal Code',
        'country' => 'Country'
    );
    $row = $titles = array();
    $fields = frmd_pre_save();
    foreach ($fields as $name=>$field){
        switch ($field['type']){
            case 'name':
            case 'address':
                foreach ($field['value'] as $k=>$v){
                    $titles[] = isset($assoc[$k]) ? $assoc[$k] : $k;
                    $row[] = $v;
                }
                break;
            default:
                $titles[] = $field['title'];
                if (is_array($field['value']))
                    $row[] = implode(', ', $field['value']);
                else $row[] = $field['value'];
                break;
        }
    }
    if (defined('FILE_FOR_REPORTS')){
        if (false === FILE_FOR_REPORTS) return false;
        $file = FILE_FOR_REPORTS;
    } else {
        $path = './reports/';
        if (!is_dir($path) && !@mkdir($path))
            return frmd_error('Cannot create folder "' . $path . '"');
        if (!is_file($path . '.htaccess')){
            $status = @file_put_contents($path . '.htaccess', '# Don\'t show directory listings for URLs which map to a directory.
Options -Indexes
# Protect files and directories from prying eyes.
<FilesMatch "\.csv$">
    Order allow,deny
</FilesMatch>');
            if (!$status) return frmd_error('Cannot create file "' . $path . '.htaccess"');
        }
        $file = $path . 'formoid.csv';
    }
    $exists = file_exists($file);
    if ($fh = @fopen($file, 'a')){
        if (!$exists) fputs($fh, frmd_csv_row($titles));
        fputs($fh, frmd_csv_row($row));
        fclose($fh);
    } else return frmd_error('Cannot create file "' . $file . '"');
    return true;
}

function frmd_label(&$elm){
    $label = '';
    if (isset($elm['label'])) $label = trim(strip_tags($elm['label']));
    if (!$label) $label = $elm['name'];
    return $label;
}

function frmd_end_form(){
    static $form = null;
    if (is_null($form)){
        $form = @ob_get_contents();
        ob_clean();
    }
    return $form;
}

function frmd_handler(){

    ob_start();
    register_shutdown_function(create_function('', '
        echo str_replace(
            "{{Formoid}}",
            frmd_end_form(),
            @ob_get_clean()
        );
    '));

    $request = &$_POST;
    if (0 == count($request)) return;
    
    if (!file_exists(PROJECT_FILE)) return frmd_error('Project file not found.');
    $project = json_decode(file_get_contents(PROJECT_FILE), true);
    
    $report = '';

    foreach ($project['elements'] as $elm){
        if (isset($elm['type']) && 'recaptcha' === $elm['type']){
            if (!frmd_captcha_is_valid($request))
                return frmd_error('The reCAPTCHA wasn\'t entered correctly. Go back and try it again.', 'captcha');
            continue;
        } else if (!isset($elm['required'], $elm['name'], $elm['type'])
            || !$elm['name']) continue;
        $value = $original = '';
        $supported = true;
        if (isset($request[ $elm['name'] ]))
            $value = $request[ $elm['name'] ];
        if ('file' == $elm['type']){
            $value = frmd_upload_file($elm['name']);
            if (is_null($value)) return;
        }
        if ($elm['required'] && !$value)
            return frmd_error('Field is required.', $elm['name']);
        switch ($elm['type']){
            
            case 'input':
            case 'textarea':
            case 'password':
            case 'radio':
            case 'select':
                $value = (string)$value;
                if (!in_array($elm['type'], array('select', 'radio'))) break;
                if ('radio' == $elm['type'] && !$value) break;
                if (!in_array($value, $elm['items']))
                    return frmd_error('Incorrect value.', $elm['name']);
                break;
            
            case 'checkbox':
            case 'multiple':
                if (!$value) break;
                if (!is_array($value)) $value = array($value);
                $value = array_map('strval', $value);
                if (array_diff($value, $elm['items']))
                    return frmd_error('Incorrect value.', $elm['name']);
                break;
            
            case 'date':
                if ($value && (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])))
                    return frmd_error('Please enter a valid date (yyyy-mm-dd).', $elm['name']);
                break;

            case 'rating':
                if ('' !== $value) $value = (int)$value;
                break;

            case 'number':
                if ($value && (!is_numeric($value) || $value < $elm['min'] || $value > $elm['max']))
                    return frmd_error(sprintf('Please enter a value between %d and %d.', $elm['min'], $elm['max']), $elm['name']);
                break;

            case 'email':
                if ($value && !preg_match('/^((([a-z]|\d|[!#$%&\'*+\-\/=?\^_`{|}~]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])+(\.([a-z]|\d|[!#$%&\'*+\-\/=?\^_`{|}~]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\\\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.)+(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.?$/iu', $value))
                    return frmd_error('Please enter a valid email address.', $elm['name']);
                break;
            
            case 'url':
                if ('http://' === $value) $value = '';
                if ($value && !preg_match('/^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.)+(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)|[\x{E000}-\x{F8FF}]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/iu', $value))
                    return frmd_error('Please enter a valid URL.', $elm['name']);
                break;
            
            case 'name':
                $original = array('first' => '', 'last' => '');
                if (isset($value['first'], $value['last'])){
                    $original = array(
                        'first' => (string)$value['first'],
                        'last' => (string)$value['last']
                    );
                    $value = trim(implode(' ', $original));
                } else $value = '';
                if ($value && !preg_match('/^\w[-\w\s]{6,60}\w$/u', $value))
                    return frmd_error('Please enter a valid name.', $elm['name']);
                if (!$value && $elm['required']) return frmd_error('Field is required.', $elm['name']);
                break;

            case 'address':
                $fields = array('addr1', 'addr2', 'city', 'state', 'zip', 'country');
                $original = array();
                foreach ($fields as $field){
                    if (isset($value[$field])) $original[$field] = trim((string)$value[$field]);
                    else $original[$field] = '';
                    if ('addr2' != $field && !$original[$field] && $elm['required'])
                        return frmd_error('Field is required.', $elm['name']);
                }
                $value = "\n\n";
                if ($original['addr1']) $value .= $original['addr1'] . "\n";
                if ($original['addr2']) $value .= $original['addr2'] . "\n";
                $value .= $original['city'] . ', ' . $original['state'] . ' ' . $original['zip'] . "\n";
                $value .= $original['country'];
                break;

            case 'phone':
                if ($value && !preg_match('/^' . $elm['phoneFormat'] . '$/', $value))
                    return frmd_error('Please specify a valid phone number.', $elm['name']);
                break;

            case 'file': break;
            
            default:
                $supported = false;
                break;
                
        }
        if ($supported){
            frmd_pre_save($elm, $value, $original);
            $report .= frmd_label($elm) . ': ';
            if (is_array($value)) $report .= implode(', ', $value);
            else if ('rating' == $elm['type'] && isset($elm['stars']) && is_int($value))
                $report .= sprintf('%d of %d', $value, $elm['stars']);
            else $report .= $value;
            $report .= "\n\n";
        }
    }
    
    frmd_save();
    frmd_mail($report);
    frmd_ready();
    
}

frmd_handler();

?>
