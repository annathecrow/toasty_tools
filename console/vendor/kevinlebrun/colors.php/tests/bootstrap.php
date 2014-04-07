<?php

set_include_path(implode(PATH_SEPARATOR, array(
    __DIR__ . '/../lib',
    get_include_path(),
)));

require_once 'Colors/Exception.php';
require_once 'Colors/InvalidArgumentException.php';
require_once 'Colors/Color.php';
