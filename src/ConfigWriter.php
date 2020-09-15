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


    protected static function GetVersionInfo()
    {

        exec("git log -1 --pretty=format:'{%n  \"commit\": \"%H\",%n  \"abbreviated_commit\": \"%h\",%n  \"tree\": \"%T\",%n  \"abbreviated_tree\": \"%t\",%n  \"parent\": \"%P\",%n  \"abbreviated_parent\": \"%p\",%n  \"refs\": \"%D\",%n  \"encoding\": \"%e\",%n  \"subject\": \"%s\",%n  \"sanitized_subject_line\": \"%f\",%n  \"body\": \"%b\",%n  \"commit_notes\": \"%N\",%n  \"verification_flag\": \"%G?\",%n  \"signer\": \"%GS\",%n  \"signer_key\": \"%GK\",%n  \"author\": {%n    \"name\": \"%aN\",%n    \"email\": \"%aE\",%n    \"date\": \"%aD\"%n  },%n  \"commiter\": {%n    \"name\": \"%cN\",%n    \"email\": \"%cE\",%n    \"date\": \"%cD\"%n  }%n}'", $output);
        $commitData = json_decode(implode("", $output), true);

        if ($commitData === null)
        {
            return [
                "VERSION_NUMBER" => 0,
                "VERSION_COMMIT_ID" => "0000",
                "VERSION_DATE" => "Mon, 01 Jan 1970 00:00:00 +0000",
                "VERSION_DATE_COMPACT" => "YYYYMMDD-HHiiss",
                "VERSION_AUTHOR_NAME" => "author"
            ];
        }

        $versionInfo = [
            "VERSION_NUMBER" => trim (exec("git log --oneline | wc -l")),
            "VERSION_COMMIT_ID" => $commitData["abbreviated_commit"],
            "VERSION_DATE" => $commitData["author"]["date"],
            "VERSION_DATE_COMPACT" => date("Ymd-His", strtotime($commitData["author"]["date"])),
            "VERSION_AUTHOR_NAME" => $commitData["author"]["name"]
        ];

        return $versionInfo;
    }


    public static function WriteConfig($infile, $outfile)
    {
        if (! file_exists($infile)) {
            Out::warn("Config file template '$infile' missing.");
            return;
        }

        $data = file_get_contents($infile);
        $missingEnv = [];
        $versionInfo = self::GetVersionInfo();

        $data = preg_replace("/\\\\%(.+?)\\\\%/mi", "REPLACED", $data);

        $parsed = preg_replace_callback("/%(.+?)%/mi",
            function ($matches) use (&$missingEnv, $versionInfo) {
                $name = $matches[1];
                $default = null;
                if (strpos($name, "?") !== false) {
                    [$name, $default] = explode("?", $name, 2);
                }

                if (strpos($name, "VERSION_") === 0) {
                    if ( ! isset ($versionInfo[$name]))
                        throw new \InvalidArgumentException("Version info tag '$name' is not available.");
                    return addslashes($versionInfo[$name]);
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