<?php
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
require_once __DIR__ . '/../frontend/views/home.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/rendered_home.html', $html);
echo "HTML rendered successfully, length: " . strlen($html) . " bytes";
