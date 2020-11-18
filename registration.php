<?php

// Register modules
$path = implode(DIRECTORY_SEPARATOR, [__DIR__, '*', 'registration.php']);
$files = glob($path, GLOB_NOSORT);
foreach ($files as $file) {
    include $file;
}
