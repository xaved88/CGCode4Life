<?php

define('APP_ROOT_DIR', getcwd());
define('APP_SYSTEM_DIR', APP_ROOT_DIR . '/system/');
define('APP_OUTPUT_DIR', APP_ROOT_DIR . '/output/');
define('APP_INCLUDES_DIR', APP_ROOT_DIR . '/includes/');


class AutoLoadCrap
{
    const OUTPUT_FILE_NAME = 'compiled';

    private $pleaseBefore = [
        'helper.php',
        'AbstractGame.php',
        'MainGame.php',
    ];
    private $pleaseAfter = [
        'run.php',
    ];


    public function makeUnifiedGameFile()
    {
        $filePaths = $this->getAllFilePathsOrdered();
        $this->makeCompiledFile($filePaths);
    }

    /**
     * @param string[] $filePaths
     */
    private function makeCompiledFile($filePaths)
    {
        $compiledString = $this->makeCompiledFileString($filePaths);
        $dir            = APP_OUTPUT_DIR . static::OUTPUT_FILE_NAME . '.php';
        file_put_contents($dir, $compiledString);
    }

    /**
     * @param string[] $filePaths
     *
     * @return string
     */
    private function makeCompiledFileString($filePaths)
    {
        $outFile = "<?php
        ";

        foreach ($filePaths as $filePath) {
            $file = file_get_contents($filePath);
            $outFile .= $this->cleanFile($file);
        }

        return $outFile;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function cleanFile($file)
    {
        $pattern     = '~\s?+\<\?php~';
        $replacement = '';
        $limit       = 1;

        return preg_replace($pattern, $replacement, $file, $limit);
    }

    /**
     * @return string[]
     */
    private function getAllFilePathsOrdered()
    {
        $files     = $this->getAllFilesOrdered();
        $filePaths = [];
        foreach ($files as $file) {
            $filePaths[] = $file->getPathname();
        }

        return $filePaths;
    }

    /**
     * @return RecursiveDirectoryIterator[]
     */
    private function getAllFilesOrdered()
    {
        $files     = $this->findFilesInDirectory(APP_INCLUDES_DIR);
        $beforeMap = array_flip($this->pleaseBefore);
        $afterMap  = array_flip($this->pleaseAfter);

        usort($files, function ($a, $b) use ($afterMap, $beforeMap) {
            $aName = $a->getFilename();
            $bName = $b->getFilename();

            if (isset($beforeMap[$aName]) || isset($beforeMap[$bName])) {
                if (!isset($beforeMap[$aName])) {
                    return 1;
                } elseif (!isset($beforeMap[$bName])) {
                    return -1;
                }

                return $beforeMap[$aName] < $beforeMap[$bName] ? -1 : 1;
            }


            if (isset($afterMap[$aName]) || isset($afterMap[$bName])) {
                if (!isset($afterMap[$aName])) {
                    return -1;
                } elseif (!isset($afterMap[$bName])) {
                    return 1;
                }

                return $afterMap[$aName] < $afterMap[$bName] ? 1 : -1;
            }

            return 0;
        });

        return $files;
    }

    /**
     * @param string $directory
     *
     * @return RecursiveDirectoryIterator[]
     */
    private function findFilesInDirectory($directory)
    {
        $filesFound        = [];
        $directoryIterator = new RecursiveDirectoryIterator($directory);
        foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
            $parts     = explode('.', $file);
            $extension = array_pop($parts);
            if (strtolower($extension) === 'php') {
                $filesFound[] = $file;
            }
        }

        return $filesFound;
    }
}

$test = new AutoLoadCrap();
$test->makeUnifiedGameFile();