<?php
/**
 * JsHelper Test Case
 *
 * TestCase for the JsHelper
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 *
 * @package       Cake.Test.Case.View.Helper
 *
 * @since         CakePHP(tm) v 1.3
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('HtmlHelper', 'View/Helper');
App::uses('JsHelper', 'View/Helper');
App::uses('JsBaseEngineHelper', 'View/Helper');
App::uses('FormHelper', 'View/Helper');
App::uses('View', 'View');
App::uses('ClassRegistry', 'Utility');

/**
 * JsEncodingObject
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsEncodingObject
{
    protected $_title = 'Old thing';

    //@codingStandardsIgnoreStart
    private $__noshow = 'Never ever';

    //@codingStandardsIgnoreEnd
}

/**
 * OptionEngineHelper
 *
 * @package       Cake.Test.Case.View.Helper
 */
class OptionEngineHelper extends JsBaseEngineHelper
{
    protected $_optionMap = [
        'request' => [
            'complete' => 'success',
            'request'  => 'beforeSend',
            'type'     => 'dataType'
        ]
    ];

    /**
     * test method for testing option mapping
     *
     * @param array $options
     *
     * @return array
     */
    public function testMap($options = [])
    {
        return $this->_mapOptions('request', $options);
    }

    /**
     * test method for option parsing
     *
     * @param $options
     * @param array $safe
     */
    public function testParseOptions($options, $safe = [])
    {
        return $this->_parseOptions($options, $safe);
    }

    public function get($selector)
    {
    }

    public function event($type, $callback, $options = [])
    {
    }

    public function domReady($functionBody)
    {
    }

    public function each($callback)
    {
    }

    public function effect($name, $options = [])
    {
    }

    public function request($url, $options = [])
    {
    }

    public function drag($options = [])
    {
    }

    public function drop($options = [])
    {
    }

    public function sortable($options = [])
    {
    }

    public function slider($options = [])
    {
    }

    public function serializeForm($options = [])
    {
    }
}

/**
 * JsHelper TestCase.
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsHelperTest extends CakeTestCase
{
    /**
     * Regexp for CDATA start block
     *
     * @var string
     */
    public $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';

    /**
     * Regexp for CDATA end block
     *
     * @var string
     */
    public $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('Asset.timestamp', false);

        $controller = null;
        $this->View = $this->getMock('View', ['append'], [&$controller]);
        $this->Js = new JsHelper($this->View, 'Option');
        $request = new CakeRequest(null, false);
        $this->Js->request = $request;
        $this->Js->Html = new HtmlHelper($this->View);
        $this->Js->Html->request = $request;
        $this->Js->Form = new FormHelper($this->View);

        $this->Js->Form->request = $request;
        $this->Js->Form->Html = $this->Js->Html;
        $this->Js->OptionEngine = new OptionEngineHelper($this->View);
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Js);
    }

    /**
     * Switches $this->Js to a mocked engine.
     */
    protected function _useMock()
    {
        $request = new CakeRequest(null, false);

        $this->Js = new JsHelper($this->View, ['TestJs']);
        $this->Js->TestJsEngine = $this->getMock('JsBaseEngineHelper', [], [$this->View]);
        $this->Js->request = $request;
        $this->Js->Html = new HtmlHelper($this->View);
        $this->Js->Html->request = $request;
        $this->Js->Form = new FormHelper($this->View);
        $this->Js->Form->request = $request;
        $this->Js->Form->Html = new HtmlHelper($this->View);
    }

    /**
     * test object construction
     */
    public function testConstruction()
    {
        $js = new JsHelper($this->View);
        $this->assertEquals(['Html', 'Form', 'JqueryEngine'], $js->helpers);

        $js = new JsHelper($this->View, ['mootools']);
        $this->assertEquals(['Html', 'Form', 'mootoolsEngine'], $js->helpers);

        $js = new JsHelper($this->View, 'prototype');
        $this->assertEquals(['Html', 'Form', 'prototypeEngine'], $js->helpers);

        $js = new JsHelper($this->View, 'MyPlugin.Dojo');
        $this->assertEquals(['Html', 'Form', 'MyPlugin.DojoEngine'], $js->helpers);
    }

    /**
     * test that methods dispatch internally and to the engine class
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testMethodDispatching()
    {
        $this->_useMock();

        $this->Js->TestJsEngine
            ->expects($this->once())
            ->method('event')
            ->with('click', 'callback');

        $this->Js->event('click', 'callback');

        $this->Js->TestJsEngine = new StdClass();
        $this->Js->someMethodThatSurelyDoesntExist();
    }

    /**
     * Test that method dispatching for events respects buffer parameters and bufferedMethods Lists.
     */
    public function testEventDispatchWithBuffering()
    {
        $this->_useMock();

        $this->Js->TestJsEngine->bufferedMethods = ['event', 'sortables'];
        $this->Js->TestJsEngine->expects($this->exactly(3))
            ->method('event')
            ->will($this->returnValue('This is an event call'));

        $this->Js->event('click', 'foo');
        $result = $this->Js->getBuffer();
        $this->assertEquals(1, count($result));
        $this->assertEquals('This is an event call', $result[0]);

        $result = $this->Js->event('click', 'foo', ['buffer' => false]);
        $buffer = $this->Js->getBuffer();
        $this->assertTrue(empty($buffer));
        $this->assertEquals('This is an event call', $result);

        $result = $this->Js->event('click', 'foo', false);
        $buffer = $this->Js->getBuffer();
        $this->assertTrue(empty($buffer));
        $this->assertEquals('This is an event call', $result);
    }

    /**
     * Test that method dispatching for effects respects buffer parameters and bufferedMethods Lists.
     */
    public function testEffectDispatchWithBuffering()
    {
        $this->_useMock();
        $this->Js->TestJsEngine->expects($this->exactly(4))
            ->method('effect')
            ->will($this->returnValue('I am not buffered.'));

        $result = $this->Js->effect('slideIn');
        $buffer = $this->Js->getBuffer();
        $this->assertTrue(empty($buffer));
        $this->assertEquals('I am not buffered.', $result);

        $result = $this->Js->effect('slideIn', true);
        $buffer = $this->Js->getBuffer();
        $this->assertNull($result);
        $this->assertEquals(1, count($buffer));
        $this->assertEquals('I am not buffered.', $buffer[0]);

        $result = $this->Js->effect('slideIn', ['speed' => 'slow'], true);
        $buffer = $this->Js->getBuffer();
        $this->assertNull($result);
        $this->assertEquals(1, count($buffer));
        $this->assertEquals('I am not buffered.', $buffer[0]);

        $result = $this->Js->effect('slideIn', ['speed' => 'slow', 'buffer' => true]);
        $buffer = $this->Js->getBuffer();
        $this->assertNull($result);
        $this->assertEquals(1, count($buffer));
        $this->assertEquals('I am not buffered.', $buffer[0]);
    }

    /**
     * test that writeScripts generates scripts inline.
     */
    public function testWriteScriptsNoFile()
    {
        $this->_useMock();
        $this->Js->buffer('one = 1;');
        $this->Js->buffer('two = 2;');
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'cache' => false, 'clear' => false]);
        $expected = [
            'script' => ['type' => 'text/javascript'],
            $this->cDataStart,
            "one = 1;\ntwo = 2;",
            $this->cDataEnd,
            '/script',
        ];
        $this->assertTags($result, $expected);

        $this->Js->TestJsEngine->expects($this->atLeastOnce())->method('domReady');
        $result = $this->Js->writeBuffer(['onDomReady' => true, 'cache' => false, 'clear' => false]);

        $this->View->expects($this->once())
            ->method('append')
            ->with('script', $this->matchesRegularExpression('/one\s\=\s1;\ntwo\s\=\s2;/'));
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'inline' => false, 'cache' => false]);
    }

    /**
     * test that writing the buffer with inline = false includes a script tag.
     */
    public function testWriteBufferNotInline()
    {
        $this->Js->set('foo', 1);

        $this->View->expects($this->once())
            ->method('append')
            ->with('script', $this->matchesRegularExpression('#<script type="text\/javascript">window.app \= \{"foo"\:1\}\;<\/script>#'));

        $this->Js->writeBuffer(['onDomReady' => false, 'inline' => false, 'safe' => false]);
    }

    /**
     * test that writeBuffer() sets domReady = false when the request is done by XHR.
     * Including a domReady() when in XHR can cause issues as events aren't triggered by some libraries
     */
    public function testWriteBufferAndXhr()
    {
        $this->_useMock();
        $requestWith = null;
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $requestWith = $_SERVER['HTTP_X_REQUESTED_WITH'];
        }
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->Js->buffer('alert("test");');
        $this->Js->TestJsEngine->expects($this->never())->method('domReady');
        $this->Js->writeBuffer();

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        if ($requestWith !== null) {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = $requestWith;
        }
    }

    /**
     * test that writeScripts makes files, and puts the events into them.
     */
    public function testWriteScriptsInFile()
    {
        $this->skipIf(!is_writable(WWW_ROOT . 'js'), 'webroot/js is not Writable, script caching test has been skipped.');

        Configure::write('Cache.disable', false);
        $this->Js->request->webroot = '/';
        $this->Js->JsBaseEngine = $this->getMock('JsBaseEngineHelper', [], [$this->View]);
        $this->Js->buffer('one = 1;');
        $this->Js->buffer('two = 2;');
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'cache' => true]);
        $expected = [
            'script' => ['type' => 'text/javascript', 'src' => 'preg:/(.)*\.js/'],
        ];
        $this->assertTags($result, $expected);
        preg_match('/src="(.*\.js)"/', $result, $filename);
        $this->assertTrue(file_exists(WWW_ROOT . $filename[1]));
        $contents = file_get_contents(WWW_ROOT . $filename[1]);
        $this->assertRegExp('/one\s=\s1;\ntwo\s=\s2;/', $contents);
        if (file_exists(WWW_ROOT . $filename[1])) {
            unlink(WWW_ROOT . $filename[1]);
        }

        Configure::write('Cache.disable', true);
        $this->Js->buffer('one = 1;');
        $this->Js->buffer('two = 2;');
        $result = $this->Js->writeBuffer(['onDomReady' => false, 'cache' => true]);
        $this->assertRegExp('/one\s=\s1;\ntwo\s=\s2;/', $result);
        $this->assertFalse(file_exists(WWW_ROOT . $filename[1]));
    }

    /**
     * test link()
     */
    public function testLinkWithMock()
    {
        $this->_useMock();

        $options = ['update' => '#content'];

        $this->Js->TestJsEngine->expects($this->at(0))
            ->method('get');

        $this->Js->TestJsEngine->expects($this->at(1))
            ->method('request')
            ->with('/posts/view/1', $options)
            ->will($this->returnValue('--ajax code--'));

        $this->Js->TestJsEngine->expects($this->at(2))
            ->method('event')
            ->with('click', '--ajax code--', $options + ['buffer' => null]);

        $result = $this->Js->link('test link', '/posts/view/1', $options);
        $expected = [
            'a' => ['id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'],
            'test link',
            '/a'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test link with a mock and confirmation
     */
    public function testLinkWithMockAndConfirm()
    {
        $this->_useMock();

        $options = [
            'confirm' => 'Are you sure?',
            'update'  => '#content',
            'class'   => 'my-class',
            'id'      => 'custom-id',
            'escape'  => false
        ];
        $this->Js->TestJsEngine->expects($this->once())
            ->method('confirmReturn')
            ->with($options['confirm'])
            ->will($this->returnValue('--confirm script--'));

        $this->Js->TestJsEngine->expects($this->once())
            ->method('request')
            ->with('/posts/view/1');

        $this->Js->TestJsEngine->expects($this->once())
            ->method('event')
            ->with('click', '--confirm script--');

        $result = $this->Js->link('test link »', '/posts/view/1', $options);
        $expected = [
            'a' => ['id' => $options['id'], 'class' => $options['class'], 'href' => '/posts/view/1'],
            'test link »',
            '/a'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test link passing on htmlAttributes
     */
    public function testLinkWithAribtraryAttributes()
    {
        $this->_useMock();

        $options = ['id' => 'something', 'htmlAttributes' => ['arbitrary' => 'value', 'batman' => 'robin']];
        $result = $this->Js->link('test link', '/posts/view/1', $options);
        $expected = [
            'a' => ['id' => $options['id'], 'href' => '/posts/view/1', 'arbitrary' => 'value',
                'batman' => 'robin'],
            'test link',
            '/a'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test that link() and no buffering returns an <a> and <script> tags.
     */
    public function testLinkWithNoBuffering()
    {
        $this->_useMock();

        $this->Js->TestJsEngine->expects($this->at(1))
            ->method('request')
            ->with('/posts/view/1', ['update' => '#content'])
            ->will($this->returnValue('ajax code'));

        $this->Js->TestJsEngine->expects($this->at(2))
            ->method('event')
            ->will($this->returnValue('-event handler-'));

        $options = ['update' => '#content', 'buffer' => false];
        $result = $this->Js->link('test link', '/posts/view/1', $options);
        $expected = [
            'a' => ['id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'],
            'test link',
            '/a',
            'script' => ['type' => 'text/javascript'],
            $this->cDataStart,
            '-event handler-',
            $this->cDataEnd,
            '/script'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test link with buffering off and safe on.
     */
    public function testLinkWithNoBufferingAndSafe()
    {
        $this->_useMock();

        $this->Js->TestJsEngine->expects($this->at(1))
            ->method('request')
            ->with('/posts/view/1', ['update' => '#content'])
            ->will($this->returnValue('ajax code'));

        $this->Js->TestJsEngine->expects($this->at(2))
            ->method('event')
            ->will($this->returnValue('-event handler-'));

        $options = ['update' => '#content', 'buffer' => false, 'safe' => false];
        $result = $this->Js->link('test link', '/posts/view/1', $options);

        $expected = [
            'a' => ['id' => 'preg:/link-\d+/', 'href' => '/posts/view/1'],
            'test link',
            '/a',
            'script' => ['type' => 'text/javascript'],
            '-event handler-',
            '/script'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test submit() with a Mock to check Engine method calls
     */
    public function testSubmitWithMock()
    {
        $this->_useMock();

        $options = ['update' => '#content', 'id' => 'test-submit', 'style' => 'margin: 0'];

        $this->Js->TestJsEngine->expects($this->at(0))
            ->method('get');

        $this->Js->TestJsEngine->expects($this->at(1))
            ->method('serializeForm')
            ->will($this->returnValue('serialize-code'));

        $this->Js->TestJsEngine->expects($this->at(2))
            ->method('request')
            ->will($this->returnValue('ajax-code'));

        $params = [
            'update' => $options['update'], 'data' => 'serialize-code',
            'method' => 'post', 'dataExpression' => true, 'buffer' => null
        ];

        $this->Js->TestJsEngine->expects($this->at(3))
            ->method('event')
            ->with('click', 'ajax-code', $params);

        $result = $this->Js->submit('Save', $options);
        $expected = [
            'div'   => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'id' => $options['id'], 'value' => 'Save', 'style' => 'margin: 0'],
            '/div'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test submit() with a mock
     */
    public function testSubmitWithMockRequestParams()
    {
        $this->_useMock();

        $this->Js->TestJsEngine->expects($this->at(0))
            ->method('get');

        $this->Js->TestJsEngine->expects($this->at(1))
            ->method('serializeForm')
            ->will($this->returnValue('serialize-code'));

        $requestParams = [
            'update'         => '#content',
            'data'           => 'serialize-code',
            'method'         => 'post',
            'dataExpression' => true
        ];

        $this->Js->TestJsEngine->expects($this->at(2))
            ->method('request')
            ->with('/custom/url', $requestParams)
            ->will($this->returnValue('ajax-code'));

        $params = [
            'update' => '#content', 'data' => 'serialize-code',
            'method' => 'post', 'dataExpression' => true, 'buffer' => null
        ];

        $this->Js->TestJsEngine->expects($this->at(3))
            ->method('event')
            ->with('click', 'ajax-code', $params);

        $options = ['update' => '#content', 'id' => 'test-submit', 'url' => '/custom/url'];
        $result = $this->Js->submit('Save', $options);
        $expected = [
            'div'   => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'id' => $options['id'], 'value' => 'Save'],
            '/div'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test that no buffer works with submit() and that parameters are leaking into the script tag.
     */
    public function testSubmitWithNoBuffer()
    {
        $this->_useMock();
        $options = ['update' => '#content', 'id' => 'test-submit', 'buffer' => false, 'safe' => false];

        $this->Js->TestJsEngine->expects($this->at(0))
            ->method('get');

        $this->Js->TestJsEngine->expects($this->at(1))
            ->method('serializeForm')
            ->will($this->returnValue('serialize-code'));

        $this->Js->TestJsEngine->expects($this->at(2))
            ->method('request')
            ->will($this->returnValue('ajax-code'));

        $this->Js->TestJsEngine->expects($this->at(3))
            ->method('event')
            ->will($this->returnValue('event-handler'));

        $params = [
            'update' => $options['update'], 'data' => 'serialize-code',
            'method' => 'post', 'dataExpression' => true, 'buffer' => false
        ];

        $this->Js->TestJsEngine->expects($this->at(3))
            ->method('event')
            ->with('click', 'ajax-code', $params);

        $result = $this->Js->submit('Save', $options);
        $expected = [
            'div'   => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'id' => $options['id'], 'value' => 'Save'],
            '/div',
            'script' => ['type' => 'text/javascript'],
            'event-handler',
            '/script'
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * Test that Object::Object() is not breaking json output in JsHelper
     */
    public function testObjectPassThrough()
    {
        $result = $this->Js->object(['one' => 'first', 'two' => 'second']);
        $expected = '{"one":"first","two":"second"}';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that inherited Helper::value() is overwritten in JsHelper::value()
     * and calls JsBaseEngineHelper::value().
     */
    public function testValuePassThrough()
    {
        $result = $this->Js->value('string "quote"', true);
        $expected = '"string \"quote\""';
        $this->assertEquals($expected, $result);
    }

    /**
     * test set()'ing variables to the JavaScript buffer and controlling the output var name.
     */
    public function testSet()
    {
        $this->Js->set('loggedIn', true);
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $result = $this->Js->getBuffer();
        $expected = 'window.app = {"loggedIn":true,"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);

        $this->Js->set('loggedIn', true);
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $this->Js->setVariable = 'WICKED';
        $result = $this->Js->getBuffer();
        $expected = 'window.WICKED = {"loggedIn":true,"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);

        $this->Js->set('loggedIn', true);
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $this->Js->setVariable = 'Application.variables';
        $result = $this->Js->getBuffer();
        $expected = 'Application.variables = {"loggedIn":true,"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * test that vars set with Js->set() go to the top of the buffered scripts list.
     */
    public function testSetVarsAtTopOfBufferedScripts()
    {
        $this->Js->set(['height' => 'tall', 'color' => 'purple']);
        $this->Js->alert('hey you!', ['buffer' => true]);
        $this->Js->confirm('Are you sure?', ['buffer' => true]);
        $result = $this->Js->getBuffer(false);

        $expected = 'window.app = {"height":"tall","color":"purple"};';
        $this->assertEquals($expected, $result[0]);
        $this->assertEquals('alert("hey you!");', $result[1]);
        $this->assertEquals('confirm("Are you sure?");', $result[2]);
    }
}

/**
 * JsBaseEngine Class Test case
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JsBaseEngineTest extends CakeTestCase
{
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $controller = null;
        $this->View = $this->getMock('View', ['append'], [&$controller]);
        $this->JsEngine = new OptionEngineHelper($this->View);
    }

    /**
     * tearDown method
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->JsEngine);
    }

    /**
     * test escape string skills
     */
    public function testEscaping()
    {
        $result = $this->JsEngine->escape('');
        $expected = '';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('CakePHP' . "\n" . 'Rapid Development Framework');
        $expected = 'CakePHP\\nRapid Development Framework';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
        $expected = 'CakePHP\\r\\nRapid Development Framework\\rFor PHP';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('CakePHP: "Rapid Development Framework"');
        $expected = 'CakePHP: \\"Rapid Development Framework\\"';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('CakePHP: \'Rapid Development Framework\'');
        $expected = 'CakePHP: \'Rapid Development Framework\'';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->escape('my \\"string\\"');
        $expected = 'my \\\\\\"string\\\\\\"';
        $this->assertEquals($expected, $result);
    }

    /**
     * test prompt() creation
     */
    public function testPrompt()
    {
        $result = $this->JsEngine->prompt('Hey, hey you', 'hi!');
        $expected = 'prompt("Hey, hey you", "hi!");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->prompt('"Hey"', '"hi"');
        $expected = 'prompt("\"Hey\"", "\"hi\"");';
        $this->assertEquals($expected, $result);
    }

    /**
     * test alert generation
     */
    public function testAlert()
    {
        $result = $this->JsEngine->alert('Hey there');
        $expected = 'alert("Hey there");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->alert('"Hey"');
        $expected = 'alert("\"Hey\"");';
        $this->assertEquals($expected, $result);
    }

    /**
     * test confirm generation
     */
    public function testConfirm()
    {
        $result = $this->JsEngine->confirm('Are you sure?');
        $expected = 'confirm("Are you sure?");';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->confirm('"Are you sure?"');
        $expected = 'confirm("\"Are you sure?\"");';
        $this->assertEquals($expected, $result);
    }

    /**
     * test Redirect
     */
    public function testRedirect()
    {
        $result = $this->JsEngine->redirect(['controller' => 'posts', 'action' => 'view', 1]);
        $expected = 'window.location = "/posts/view/1";';
        $this->assertEquals($expected, $result);
    }

    /**
     * testObject encoding with non-native methods.
     */
    public function testObject()
    {
        $object = ['title' => 'New thing', 'indexes' => [5, 6, 7, 8]];
        $result = $this->JsEngine->object($object);
        $expected = '{"title":"New thing","indexes":[5,6,7,8]}';
        $this->assertEquals($expected, $result);

        $object = new JsEncodingObject();
        $object->title = 'New thing';
        $object->indexes = [5, 6, 7, 8];
        $result = $this->JsEngine->object($object);
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->object(['default' => 0]);
        $expected = '{"default":0}';
        $this->assertEquals($expected, $result);

        $result = $this->JsEngine->object([
            '2007' => [
                'Spring' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky']
                ],
                'Fall' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky']
                ]
            ],
            '2006' => [
                'Spring' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky']
                ],
                'Fall' => [
                    '1' => ['id' => 1, 'name' => 'Josh'], '2' => ['id' => 2, 'name' => 'Becky']
                ]
            ]
        ]);
        $expected = '{"2007":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}},"2006":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}}}';
        $this->assertEquals($expected, $result);

        foreach (['true' => true, 'false' => false, 'null' => null] as $expected => $data) {
            $result = $this->JsEngine->object($data);
            $this->assertEquals($expected, $result);
        }

        $object = ['title' => 'New thing', 'indexes' => [5, 6, 7, 8], 'object' => ['inner' => ['value' => 1]]];
        $result = $this->JsEngine->object($object, ['prefix' => 'PREFIX', 'postfix' => 'POSTFIX']);
        $this->assertRegExp('/^PREFIX/', $result);
        $this->assertRegExp('/POSTFIX$/', $result);
        $this->assertNotRegExp('/.PREFIX./', $result);
        $this->assertNotRegExp('/.POSTFIX./', $result);
    }

    /**
     * test Mapping of options.
     */
    public function testOptionMapping()
    {
        $JsEngine = new OptionEngineHelper($this->View);
        $result = $JsEngine->testMap();
        $this->assertSame([], $result);

        $result = $JsEngine->testMap(['foo' => 'bar', 'baz' => 'sho']);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'sho'], $result);

        $result = $JsEngine->testMap(['complete' => 'myFunc', 'type' => 'json', 'update' => '#element']);
        $this->assertEquals(['success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'], $result);

        $result = $JsEngine->testMap(['success' => 'myFunc', 'dataType' => 'json', 'update' => '#element']);
        $this->assertEquals(['success' => 'myFunc', 'dataType' => 'json', 'update' => '#element'], $result);
    }

    /**
     * test that option parsing escapes strings and saves what is supposed to be saved.
     */
    public function testOptionParsing()
    {
        $JsEngine = new OptionEngineHelper($this->View);

        $result = $JsEngine->testParseOptions(['url' => '/posts/view/1', 'key' => 1]);
        $expected = 'key:1, url:"\\/posts\\/view\\/1"';
        $this->assertEquals($expected, $result);

        $result = $JsEngine->testParseOptions(['url' => '/posts/view/1', 'success' => 'doSuccess'], ['success']);
        $expected = 'success:doSuccess, url:"\\/posts\\/view\\/1"';
        $this->assertEquals($expected, $result);
    }
}
