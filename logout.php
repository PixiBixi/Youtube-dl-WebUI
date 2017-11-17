<?php
require_once 'vendor/autoload.php';
Session::getInstance()->logout();
header("Location: index.php");
