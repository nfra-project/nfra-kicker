<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 6/19/18
 * Time: 8:33 PM
 */

namespace Kick;


class ConfigWriter
{




    public static function WriteConfig($infile, $outfile)
    {
        if (! file_exists($infile)) {
            Out::warn("Config file template '$infile' missing.");
            return;
        }

        $data = file_get_contents($infile);
        $missingEnv = [];

        $data = preg_replace("/\\\\%(.+?)\\\\%/mi", "REPLACED", $data);

        $parsed = preg_replace_callback("/%(.+?)%/mi",
            function ($matches) use (&$missingEnv) {
                $name = $matches[1];
                $default = null;
                if (strpos($name, "?") !== false) {
                    [$name, $default] = explode("?", $name, 2);
                }

                switch ($name) {
                    default:
                        $data = getenv($name);
                        if ($data === false) {
                            if ($default !== null) {
                                return $default;
                            }
                            $missingEnv[] = $name;
                        }
                        return addslashes($data);
                }
            }, $data);
        if (count ($missingEnv) > 0) {
            Out::fail("Missing environment variables: " . implode(", ", $missingEnv));
            throw new \InvalidArgumentException("Missing environment variables: " . implode(", ", $missingEnv));
        }

        file_put_contents($outfile, $parsed);
    }

}