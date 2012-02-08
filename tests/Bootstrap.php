<?php


// Ensure library/ and Zend libraries are on include_path
set_include_path(implode(PATH_SEPARATOR,
    array(
        realpath(__dir__ . '/../lib'),
        get_include_path(),
    )
));

