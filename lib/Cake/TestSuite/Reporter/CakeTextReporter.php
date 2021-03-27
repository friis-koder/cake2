<?php
/**
 * CakeTextReporter contains reporting features used for plain text based output
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestResult;
use SebastianBergmann\CodeCoverage\ProcessedCodeCoverageData;

App::uses('CakeBaseReporter', 'TestSuite/Reporter');
App::uses('TextCoverageReport', 'TestSuite/Coverage');

/**
 * CakeTextReporter contains reporting features used for plain text based output
 *
 * @package       Cake.TestSuite.Reporter
 */
class CakeTextReporter extends CakeBaseReporter
{
    /**
     * Sets the text/plain header if the test is not a CLI test.
     *
     * @return void
     */
    public function paintDocumentStart()
    {
        if (!headers_sent()) {
            header('Content-type: text/plain');
        }
    }

    /**
     * Paints a pass
     *
     * @return void
     */
    public function paintPass()
    {
        echo '.';
    }

    /**
     * Paints a failing test.
     *
     * @param AssertionFailedError $message Failure object displayed in the context of the other tests.
     */
    public function paintFail(AssertionFailedError $message)
    {
        $context = $message->getTrace();
        $realContext = $context[3];
        $context = $context[2];

        printf(
            "FAIL on line %s\n%s in\n%s %s()\n\n",
            $context['line'],
            $message->toString(),
            $context['file'],
            $realContext['function']
        );
    }

    /**
     * Paints the end of the test with a summary of
     * the passes and failures.
     *
     * @param TestResult $result Result object
     */
    public function paintFooter(TestResult $result)
    {
        if ($result->failureCount() + $result->errorCount()) {
            echo "FAILURES!!!\n";
        } else {
            echo "\nOK\n";
        }

        echo "Test cases run: " . $result->count() .
            "/" . ($result->count() - $result->skippedCount()) .
            ', Passes: ' . $this->numAssertions .
            ', Failures: ' . $result->failureCount() .
            ', Exceptions: ' . $result->errorCount() . "\n";

        echo 'Time: ' . $result->time() . " seconds\n";
        echo 'Peak memory: ' . number_format(memory_get_peak_usage()) . " bytes\n";

        if (isset($this->params['codeCoverage']) && $this->params['codeCoverage']) {
            $coverage = $result->getCodeCoverage()->getData();
            $this->paintCoverage($coverage);
        }
    }

    /**
     * Paints the title only.
     *
     * @return void
     */
    public function paintHeader()
    {
        $this->paintDocumentStart();
        flush();
    }

    /**
     * Paints a PHP exception.
     *
     * @param Throwable $throwable Exception to describe.
     */
    public function paintException(Throwable $throwable)
    {
        $message = 'Unexpected exception of type [' . get_class($throwable) .
            '] with message [' . $throwable->getMessage() .
            '] in [' . $throwable->getFile() .
            ' line ' . $throwable->getLine() . ']';
        echo $message . "\n\n";
    }

    /**
     * Prints the message for skipping tests.
     *
     * @param Throwable $throwable Text of skip condition.
     *
     * @return void
     */
    public function paintSkip(Throwable $throwable)
    {
        printf("Skip: %s\n", $throwable->getMessage());
    }

    /**
     * Paints formatted text such as dumped variables.
     *
     * @param string $message Text to show.
     *
     * @return void
     */
    public function paintFormattedMessage(string $message)
    {
        echo "$message\n";
        flush();
    }

    /**
     * Generate a test case list in plain text.
     * Creates as series of URLs for tests that can be run.
     * One case per line.
     *
     * @return void
     */
    public function testCaseList()
    {
        $testCases = parent::testCaseList();
        $app = $this->params['app'];
        $plugin = $this->params['plugin'];

        $buffer = "Core Test Cases:\n";

        if ($app) {
            $buffer = "App Test Cases:\n";
        } elseif ($plugin) {
            $buffer = Inflector::humanize($plugin) . " Test Cases:\n";
        }

        if (count($testCases) < 1) {
            $buffer .= 'EMPTY';
            echo $buffer;
        }

        foreach ($testCases as $testCase) {
            $buffer .= $_SERVER['SERVER_NAME'] . $this->baseUrl() . "?case=" . $testCase . "&output=text\n";
        }

        $buffer .= "\n";

        echo $buffer;
    }

    /**
     * Generates a Text summary of the coverage data.
     *
     * @param ProcessedCodeCoverageData $coverage Array of coverage data.
     *
     * @return void
     */
    public function paintCoverage(ProcessedCodeCoverageData $coverage)
    {
        $reporter = new TextCoverageReport($coverage, $this);

        echo $reporter->report();
    }

}
