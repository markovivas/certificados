<?php

namespace GCWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Utils {

    /**
     * Convert hex color to RGB
     *
     * @param string $hex Hex color (e.g. #000000)
     * @param bool $as_array Return as array for TCPDF if true
     * @return array|associative array
     */
    public static function hex2rgb($hex, $as_array = false) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        $rgb = array('r' => $r, 'g' => $g, 'b' => $b);

        if ($as_array) {
            return [$r, $g, $b];
        }
        return $rgb;
    }

    /**
     * Recursively delete a directory
     *
     * @param string $dirPath
     */
    public static function delete_dir_recursive($dirPath) {
        if (!is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::delete_dir_recursive($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}
