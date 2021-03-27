<?php
/**
 * TestLoader for CakePHP Test suite.
 *
 * Turns partial paths used on the testsuite console and web UI into full file paths.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @package Cake.TestSuite
 */

use PHPUnit\Runner\StandardTestSuiteLoader;

/**
 * TestLoader for CakePHP Test suite.
 *
 * Turns partial paths used on the testsuite console and web UI into full file paths.
 *
 * @package Cake.TestSuite
 */
class CakeTestLoader
{
    /**
     * Get the list of files for the test listing.
     *
     * @param array|null $params Path parameters
     *
     * @return array
     */
    public static function generateTestList(?array $params): array
    {
        $directory = static::_basePath($params);
        $fileList = static::_getRecursiveFileList($directory);

        $testCases = array();
        foreach ($fileList as $testCaseFile) {
            $case = str_replace($directory . DS, '', $testCaseFile);
            $case = str_replace('Test.php', '', $case);
            $testCases[$testCaseFile] = $case;
        }

        sort($testCases);

        return $testCases;
    }

    /**
     * Gets a recursive list of files from a given directory and matches then against
     * a given fileTestFunction, like isTestCaseFile()
     *
     * @param string $directory The directory to scan for files.
     *
     * @return array
     */
    protected static function _getRecursiveFileList($directory = '.'): array
    {
        $fileList = array();

        if (!is_dir($directory)) {
            return $fileList;
        }

        $files = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)),
            '/.*Test.php$/'
        );

        foreach ($files as $file) {
            $fileList[] = $file->getPathname();
        }

        return $fileList;
    }

    /**
     * Load a file and find the first test case / suite in that file.
     *
     * @param string $filePath The file path to load
     * @param array|null $params Additional parameters
     *
     * @return ReflectionClass
     */
    public function load(string $filePath, ?array $params): ReflectionClass
    {
        $file = $this->_resolveTestFile($filePath, $params);

        $loader = new StandardTestSuiteLoader();

        return $loader->load($file);
    }

    /**
     * Convert path fragments used by CakePHP's test runner to absolute paths that can be fed to PHPUnit.
     *
     * @param string $filePath The file path to load.
     * @param array $params Additional parameters.
     *
     * @return string Converted path fragments.
     */
    protected function _resolveTestFile(string $filePath, array $params): string
    {
        $basePath = $this->_basePath($params) . DS . $filePath;
        $ending = 'Test.php';

        return (strpos($basePath, $ending) === (strlen($basePath) - strlen($ending))) ? $basePath : $basePath . $ending;
    }

    /**
     * Generates the base path to a set of tests based on the parameters.
     *
     * @param array|null $params The path parameters.
     *
     * @return string|null
     */
    protected static function _basePath(?array $params): ?string
    {
        $result = null;

        if (!empty($params['core'])) {
            $result = CORE_TEST_CASES;
        } elseif (!empty($params['plugin'])) {
            if (!CakePlugin::loaded($params['plugin'])) {
                try {
                    CakePlugin::load($params['plugin']);
                    $result = CakePlugin::path($params['plugin']) . 'Test' . DS . 'Case';
                } catch (MissingPluginException $e) {
                }
            } else {
                $result = CakePlugin::path($params['plugin']) . 'Test' . DS . 'Case';
            }
        } elseif (!empty($params['app'])) {
            $result = APP_TEST_CASES;
        }

        return $result;
    }
}
