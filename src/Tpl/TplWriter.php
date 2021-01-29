<?php


namespace Kick\Tpl;


use Kick\Out;

class TplWriter
{


    public function parse (string $inputDir, string $targetDir)
    {
        $rdi = new \RecursiveDirectoryIterator($inputDir, \RecursiveDirectoryIterator::KEY_AS_PATHNAME | \RecursiveDirectoryIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($rdi) as $curFile => $info) {
            /* @var $info \SplFileInfo */
            ini_set("display_errors", 1);
            try {
                $parser = new TplPhpParser($curFile);
                $outFile = $targetDir . "/" . absolute_to_relative_uri($curFile, $inputDir);
                Out::log("Rewriting: $curFile -> $outFile");

                $content = $parser->parseFile([]);

                $dir = dirname($outFile);
                if ( ! is_dir($dir)) {
                    Out::warn("Directory '$dir' not exsisting.");
                    if ( ! mkdir($dir, 0755, true) && !is_dir($dir))
                    throw new \Exception("Cannot create directory '$dir'");
                }
                file_put_contents($outFile, $content);
            } catch (\Exception $e) {
                Out::fail("Error parsing '$curFile': " . $e->getMessage());
                throw new \Exception("Error parsing '$curFile': " . $e->getMessage());
            }


        }

    }

}