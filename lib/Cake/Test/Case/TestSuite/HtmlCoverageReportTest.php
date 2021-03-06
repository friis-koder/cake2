<?php
/**
 * Test case for HtmlCoverageReport
 *
 * PHP5
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * @link          https://cakephp.org CakePHP(tm) Project
 *
 * @package       Cake.Test.Case.TestSuite
 *
 * @since         CakePHP(tm) v 2.0
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('HtmlCoverageReport', 'TestSuite/Coverage');
App::uses('CakeBaseReporter', 'TestSuite/Reporter');

/**
 * HtmlCoverageReportTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class HtmlCoverageReportTest extends CakeTestCase
{
    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        App::build([
            'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS]
        ], App::RESET);
        CakePlugin::load(['TestPlugin']);
        $reporter = new CakeBaseReporter();
        $reporter->params = ['app' => false, 'plugin' => false, 'group' => false];
        $coverage = [];
        $this->Coverage = new HtmlCoverageReport($coverage, $reporter);
    }

    /**
     * test getting the path filters.
     */
    public function testGetPathFilter()
    {
        $this->Coverage->appTest = false;
        $result = $this->Coverage->getPathFilter();
        $this->assertEquals(CAKE, $result);

        $this->Coverage->appTest = true;
        $result = $this->Coverage->getPathFilter();
        $this->assertEquals(ROOT . DS . APP_DIR . DS, $result);

        $this->Coverage->appTest = false;
        $this->Coverage->pluginTest = 'TestPlugin';
        $result = $this->Coverage->getPathFilter();
        $this->assertEquals(CakePlugin::path('TestPlugin'), $result);
    }

    /**
     * test filtering coverage data.
     */
    public function testFilterCoverageDataByPathRemovingElements()
    {
        $data = [
            CAKE . 'dispatcher.php' => [
                10 => -1,
                12 => 1
            ],
            APP . 'app_model.php' => [
                50 => 1,
                52 => -1
            ]
        ];
        $this->Coverage->setCoverage($data);
        $result = $this->Coverage->filterCoverageDataByPath(CAKE);
        $this->assertTrue(isset($result[CAKE . 'dispatcher.php']));
        $this->assertFalse(isset($result[APP . 'app_model.php']));
    }

    /**
     * test generating HTML reports from file arrays.
     */
    public function testGenerateDiff()
    {
        $file = [
            'line 1',
            'line 2',
            'line 3',
            'line 4',
            'line 5',
            'line 6',
            'line 7',
            'line 8',
            'line 9',
            'line 10',
        ];
        $coverage = [
            1  => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            2  => -2,
            3  => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            4  => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            5  => -1,
            6  => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            7  => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            8  => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']],
            9  => -1,
            10 => [['id' => 'HtmlCoverageReportTest::testGenerateDiff']]
        ];
        $result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);
        $this->assertRegExp('/myfile\.php Code coverage\: \d+\.?\d*\%/', $result);
        $this->assertRegExp('/<div class="code-coverage-results" id\="coverage\-myfile\.php-' . md5('myfile.php') . '"/', $result);
        $this->assertRegExp('/<pre>/', $result);
        foreach ($file as $i => $line) {
            $this->assertTrue(strpos($line, $result) !== 0, 'Content is missing ' . $i);
            $class = 'covered';
            if (in_array($i + 1, [5, 9, 2])) {
                $class = 'uncovered';
            }
            if ($i + 1 === 2) {
                $class .= ' dead';
            }
            $this->assertTrue(strpos($class, $result) !== 0, 'Class name is wrong ' . $i);
        }
    }

    /**
     * Test that coverage works with phpunit 3.6 as the data formats from coverage are totally different.
     */
    public function testPhpunit36Compatibility()
    {
        $file = [
            'line 1',
            'line 2',
            'line 3',
            'line 4',
            'line 5',
            'line 6',
            'line 7',
            'line 8',
            'line 9',
            'line 10',
        ];
        $coverage = [
            1  => ['HtmlCoverageReportTest::testGenerateDiff'],
            2  => null,
            3  => ['HtmlCoverageReportTest::testGenerateDiff'],
            4  => ['HtmlCoverageReportTest::testGenerateDiff'],
            5  => [],
            6  => ['HtmlCoverageReportTest::testGenerateDiff'],
            7  => ['HtmlCoverageReportTest::testGenerateDiff'],
            8  => ['HtmlCoverageReportTest::testGenerateDiff'],
            9  => [],
            10 => ['HtmlCoverageReportTest::testSomething', 'HtmlCoverageReportTest::testGenerateDiff']
        ];

        $result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);
        $this->assertRegExp('/myfile\.php Code coverage\: \d+\.?\d*\%/', $result);
        $this->assertRegExp('/<div class="code-coverage-results" id\="coverage\-myfile\.php-' . md5('myfile.php') . '"/', $result);
        $this->assertRegExp('/<pre>/', $result);
        foreach ($file as $i => $line) {
            $this->assertTrue(strpos($line, $result) !== 0, 'Content is missing ' . $i);
            $class = 'covered';
            if (in_array($i + 1, [5, 9, 2])) {
                $class = 'uncovered';
            }
            if ($i + 1 === 2) {
                $class .= ' dead';
            }
            $this->assertTrue(strpos($class, $result) !== 0, 'Class name is wrong ' . $i);
        }
    }

    /**
     * test that covering methods show up as title attributes for lines.
     */
    public function testCoveredLinesTitleAttributes()
    {
        $file = [
            'line 1',
            'line 2',
            'line 3',
            'line 4',
            'line 5',
        ];

        $coverage = [
            1 => [['id' => 'HtmlCoverageReportTest::testAwesomeness']],
            2 => -2,
            3 => [['id' => 'HtmlCoverageReportTest::testCakeIsSuperior']],
            4 => [['id' => 'HtmlCoverageReportTest::testOther']],
            5 => -1
        ];

        $result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);

        $this->assertTrue(
            strpos($result, "title=\"Covered by:\nHtmlCoverageReportTest::testAwesomeness\n\"><span class=\"line-num\">1") !== false,
            'Missing method coverage for line 1'
        );
        $this->assertTrue(
            strpos($result, "title=\"Covered by:\nHtmlCoverageReportTest::testCakeIsSuperior\n\"><span class=\"line-num\">3") !== false,
            'Missing method coverage for line 3'
        );
        $this->assertTrue(
            strpos($result, "title=\"Covered by:\nHtmlCoverageReportTest::testOther\n\"><span class=\"line-num\">4") !== false,
            'Missing method coverage for line 4'
        );
        $this->assertTrue(
            strpos($result, 'title=""><span class="line-num">5') !== false,
            'Coverage report is wrong for line 5'
        );
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        CakePlugin::unload();
        unset($this->Coverage);
        parent::tearDown();
    }
}
