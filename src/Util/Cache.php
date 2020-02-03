<?php
namespace App\Util;


class Cache {
    
    static public function clear($env) {
        $cache_dir = __DIR__ . '/../../../../app/cache';
        if (is_dir($cache_dir)) {
            if (basename($cache_dir) == "cache") {
                    echo "<br/><br/><b>clearing cache :</b>";
                    self::cc($cache_dir, $env);
                    echo "<br/><br/><b>done !</b>";
            }
            else {
                    die("<br/> Error : cache_dir not named cache ?");
            }
        }
        else {
                die("<br/> Error : cache_dir is not a dir");
        }
    }
    
    static protected function cc($cache_dir, $name) {
	$d = $cache_dir . '/' . $name;
	if (is_dir($d)) {
		echo "<br/><br/><b>clearing " . $name . ' :</b>';
		self::rrmdir($d);
	}
        
    }
    
    
    static protected function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $o = $dir . "/" . $object;
                    if (filetype($o) == "dir") {
                        self::rrmdir($dir."/".$object);
                    }
                    else {
                            echo "<br/>" . $o;
                        unlink($o);
                    }
                }
            }
            reset($objects);
            echo "<br/>" . $dir;
            rmdir($dir);
        }
    }
    
    static public function clearAllEnv() {
        $cache_dir = __DIR__ . '/../../../../app/cache';
        $objects = scandir($cache_dir);
        foreach($objects as $object) {
            if (is_dir($cache_dir.'/'.$object)) {
                self::clear($object);
            }
        }
    }
}
