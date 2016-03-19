<?php

class CustomUploadHandler extends UploadHandler {
    protected function header($str) {
        # Need this to avoid error in UT
        @header($str);
    }

    protected function trim_file_name($file_path, $name, $size, $type, $error, $index, $content_range) {
        $name = uniqid();
        return $name;
    }
}
