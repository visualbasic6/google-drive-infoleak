<?php

define('EMAIL_FOR_REPORTS', '');
define('RECAPTCHA_PRIVATE_KEY', '@privatekey@');
define('FINISH_URI', 'reveal.php?target=');
define('FINISH_ACTION', 'redirect');
define('FINISH_MESSAGE', '');
define('UPLOAD_ALLOWED_FILE_TYPES', 'doc, docx, xls, csv, txt, rtf, html, zip, jpg, jpeg, png, gif');

define('_DIR_', str_replace('\\', '/', dirname(__FILE__)) . '/');
require_once _DIR_ . '/handler.php';

?>

<?php if (frmd_message()): ?>
<link rel="stylesheet" href="<?php echo dirname($form_path); ?>/formoid-solid-red.css" type="text/css" />
<span class="alert alert-success"><?php echo FINISH_MESSAGE; ?></span>
<?php else: ?>
<!-- Start Formoid form-->
<link rel="stylesheet" href="<?php echo dirname($form_path); ?>/formoid-solid-red.css" type="text/css" />
<script type="text/javascript" src="<?php echo dirname($form_path); ?>/jquery.min.js"></script>
<form class="formoid-solid-red" style="background-color:#FFFFFF;font-size:9px;font-family:Arial,Helvetica,sans-serif;color:#34495E;max-width:480px;min-width:150px" method="post"><div class="title"><h2>gmail full name disclosure</h2></div>
	<div class="element-input<?php frmd_add_class("input"); ?>" title="enter your target's gmail address"><label class="title"></label><div class="item-cont"><input class="large" type="text" name="input" placeholder="target@gmail.com"/><span class="icon-place"></span></div></div>
<div class="submit"><input type="submit" value="reveal"/></div></form><script type="text/javascript" src="<?php echo dirname($form_path); ?>/formoid-solid-red.js"></script>

<!-- Stop Formoid form-->
<?php endif; ?>

<?php frmd_end_form(); ?>