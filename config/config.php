<?php
/**
 * Default password is "root", with SHA256 hash
 * No / at the end of outputFolder
 * outputFolder is a relative path
 */

$config = [
    "security"     => true,
    "password"     => "4813494d137e1631bba301d5acab6e7bb7aa74ce1185d456565ef51d737677b2",
    "outputFolder" => "downloads",
    "extracter"    => "ffmpeg",
    "max_dl"       => 3,
];
return $config;
