<?php
/**
 * PrototypeEngine TestCase
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
 * @link          https://cakephp.org CakePHP Project
 *
 * @package       Cake.Test.Case.View.Helper
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('View', 'View');
App::uses('HtmlHelper', 'View/Helper');
App::uses('JsHelper', 'View/Helper');
App::uses('PrototypeEngineHelper', 'View/Helper');

/**
 * PrototypeEngineHelperTest
 *
 * @package       Cake.Test.Case.View.Helper
 */
class PrototypeEngineHelperTest extends CakeTestCase
{
    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        $controller = null;
        $this->View = $this->getMock('View', ['addScript'], [&$controller]);
        $this->Proto = new PrototypeEngineHelper($this->View);
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Proto);
    }

    /**
     * test selector method
     */
    public function testSelector()
    {
        $result = $this->Proto->get('#content');
        $this->assertEquals($this->Proto, $result);
        $this->assertEquals($this->Proto->selection, '$("content")');

        $result = $this->Proto->get('a .remove');
        $this->assertEquals($this->Proto, $result);
        $this->assertEquals($this->Proto->selection, '$$("a .remove")');

        $result = $this->Proto->get('document');
        $this->assertEquals($this->Proto, $result);
        $this->assertEquals($this->Proto->selection, '$(document)');

        $result = $this->Proto->get('window');
        $this->assertEquals($this->Proto, $result);
        $this->assertEquals($this->Proto->selection, '$(window)');

        $result = $this->Proto->get('ul');
        $this->assertEquals($this->Proto, $result);
        $this->assertEquals($this->Proto->selection, '$$("ul")');

        $result = $this->Proto->get('#some_long-id.class');
        $this->assertEquals($this->Proto, $result);
        $this->assertEquals($this->Proto->selection, '$$("#some_long-id.class")');
    }

    /**
     * test event binding
     */
    public function testEvent()
    {
        $this->Proto->get('#myLink');
        $result = $this->Proto->event('click', 'doClick', ['wrap' => false]);
        $expected = '$("myLink").observe("click", doClick);';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->event('click', 'Element.hide(this);', ['stop' => false]);
        $expected = '$("myLink").observe("click", function (event) {Element.hide(this);});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->event('click', 'Element.hide(this);');
        $expected = "\$(\"myLink\").observe(\"click\", function (event) {event.stop();\nElement.hide(this);});";
        $this->assertEquals($expected, $result);
    }

    /**
     * test dom ready event creation
     */
    public function testDomReady()
    {
        $result = $this->Proto->domReady('foo.name = "bar";');
        $expected = 'document.observe("dom:loaded", function (event) {foo.name = "bar";});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test Each method
     */
    public function testEach()
    {
        $this->Proto->get('#foo li');
        $result = $this->Proto->each('item.hide();');
        $expected = '$$("#foo li").each(function (item, index) {item.hide();});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test Effect generation
     */
    public function testEffect()
    {
        $this->Proto->get('#foo');
        $result = $this->Proto->effect('show');
        $expected = '$("foo").show();';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('hide');
        $expected = '$("foo").hide();';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('fadeIn');
        $expected = '$("foo").appear();';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('fadeIn', ['speed' => 'fast']);
        $expected = '$("foo").appear({duration:0.50000000000});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('fadeIn', ['speed' => 'slow']);
        $expected = '$("foo").appear({duration:2});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('fadeOut');
        $expected = '$("foo").fade();';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('fadeOut', ['speed' => 'fast']);
        $expected = '$("foo").fade({duration:0.50000000000});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('fadeOut', ['speed' => 'slow']);
        $expected = '$("foo").fade({duration:2});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('slideIn');
        $expected = 'Effect.slideDown($("foo"));';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('slideOut');
        $expected = 'Effect.slideUp($("foo"));';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('slideOut', ['speed' => 'fast']);
        $expected = 'Effect.slideUp($("foo"), {duration:0.50000000000});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->effect('slideOut', ['speed' => 'slow']);
        $expected = 'Effect.slideUp($("foo"), {duration:2});';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test Request Generation
     */
    public function testRequest()
    {
        $result = $this->Proto->request(['controller' => 'posts', 'action' => 'view', 1]);
        $expected = 'var jsRequest = new Ajax.Request("/posts/view/1");';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->request('/posts/view/1', [
            'method'        => 'post',
            'complete'      => 'doComplete',
            'before'        => 'doBefore',
            'success'       => 'doSuccess',
            'error'         => 'doError',
            'data'          => ['name' => 'jim', 'height' => '185cm'],
            'wrapCallbacks' => false
        ]);
        $expected = 'var jsRequest = new Ajax.Request("/posts/view/1", {method:"post", onComplete:doComplete, onCreate:doBefore, onFailure:doError, onSuccess:doSuccess, parameters:{"name":"jim","height":"185cm"}});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->request('/posts/view/1', ['update' => 'content']);
        $expected = 'var jsRequest = new Ajax.Updater("content", "/posts/view/1");';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->request('/people/edit/1', [
            'method'        => 'post',
            'complete'      => 'doSuccess',
            'update'        => '#update-zone',
            'wrapCallbacks' => false
        ]);
        $expected = 'var jsRequest = new Ajax.Updater("update-zone", "/people/edit/1", {method:"post", onComplete:doSuccess});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->request('/people/edit/1', [
            'method'        => 'post',
            'complete'      => 'doSuccess',
            'error'         => 'handleError',
            'type'          => 'json',
            'data'          => ['name' => 'jim', 'height' => '185cm'],
            'wrapCallbacks' => false
        ]);
        $expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {method:"post", onComplete:doSuccess, onFailure:handleError, parameters:{"name":"jim","height":"185cm"}});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->request('/people/edit/1', [
            'method'         => 'post',
            'complete'       => 'doSuccess',
            'error'          => 'handleError',
            'type'           => 'json',
            'data'           => '$("element").serialize()',
            'dataExpression' => true,
            'wrapCallbacks'  => false
        ]);
        $expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {method:"post", onComplete:doSuccess, onFailure:handleError, parameters:$("element").serialize()});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->request('/people/edit/1', [
            'method'   => 'post',
            'before'   => 'doBefore();',
            'success'  => 'doSuccess();',
            'complete' => 'doComplete();',
            'error'    => 'handleError();',
        ]);
        $expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {method:"post", onComplete:function (transport) {doComplete();}, onCreate:function (transport) {doBefore();}, onFailure:function (response, jsonHeader) {handleError();}, onSuccess:function (response, jsonHeader) {doSuccess();}});';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->request('/people/edit/1', [
            'async'    => false,
            'method'   => 'post',
            'before'   => 'doBefore();',
            'success'  => 'doSuccess();',
            'complete' => 'doComplete();',
            'error'    => 'handleError();',
        ]);
        $expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {asynchronous:false, method:"post", onComplete:function (transport) {doComplete();}, onCreate:function (transport) {doBefore();}, onFailure:function (response, jsonHeader) {handleError();}, onSuccess:function (response, jsonHeader) {doSuccess();}});';
        $this->assertEquals($expected, $result);

        $this->Proto->get('#submit');
        $result = $this->Proto->request('/users/login', [
            'before'         => 'login.create(event)',
            'complete'       => 'login.complete(event)',
            'update'         => 'auth',
            'data'           => $this->Proto->serializeForm(['isForm' => false, 'inline' => true]),
            'dataExpression' => true
        ]);
        $this->assertTrue(strpos($result, '$($("submit").form).serialize()') > 0);
        $this->assertFalse(strpos($result, 'parameters:function () {$($("submit").form).serialize()}') > 0);
    }

    /**
     * test sortable list generation
     */
    public function testSortable()
    {
        $this->Proto->get('#myList');
        $result = $this->Proto->sortable([
            'complete'      => 'onComplete',
            'sort'          => 'onSort',
            'wrapCallbacks' => false
        ]);
        $expected = 'var jsSortable = Sortable.create($("myList"), {onChange:onSort, onUpdate:onComplete});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test drag() method. Scriptaculous lacks the ability to take an Array of Elements
     * in new Drag() when selection is a multiple type. Iterate over the array.
     */
    public function testDrag()
    {
        $this->Proto->get('#element');
        $result = $this->Proto->drag([
            'start'         => 'onStart',
            'drag'          => 'onDrag',
            'stop'          => 'onStop',
            'snapGrid'      => [10, 10],
            'wrapCallbacks' => false
        ]);
        $expected = 'var jsDrag = new Draggable($("element"), {onDrag:onDrag, onEnd:onStop, onStart:onStart, snap:[10,10]});';
        $this->assertEquals($expected, $result);

        $this->Proto->get('div.dragger');
        $result = $this->Proto->drag([
            'start'         => 'onStart',
            'drag'          => 'onDrag',
            'stop'          => 'onStop',
            'snapGrid'      => [10, 10],
            'wrapCallbacks' => false
        ]);
        $expected = '$$("div.dragger").each(function (item, index) {new Draggable(item, {onDrag:onDrag, onEnd:onStop, onStart:onStart, snap:[10,10]});});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test drop() method
     */
    public function testDrop()
    {
        $this->Proto->get('#element');
        $result = $this->Proto->drop([
            'hover'         => 'onHover',
            'drop'          => 'onDrop',
            'accept'        => '.drag-me',
            'wrapCallbacks' => false
        ]);
        $expected = 'Droppables.add($("element"), {accept:".drag-me", onDrop:onDrop, onHover:onHover});';
        $this->assertEquals($expected, $result);
    }

    /**
     * ensure that slider() method behaves properly
     */
    public function testSlider()
    {
        $this->Proto->get('#element');
        $result = $this->Proto->slider([
            'handle'        => '#handle',
            'direction'     => 'horizontal',
            'change'        => 'onChange',
            'complete'      => 'onComplete',
            'value'         => 4,
            'wrapCallbacks' => false
        ]);
        $expected = 'var jsSlider = new Control.Slider($("handle"), $("element"), {axis:"horizontal", onChange:onComplete, onSlide:onChange, sliderValue:4});';
        $this->assertEquals($expected, $result);

        $this->Proto->get('#element');
        $result = $this->Proto->slider([
            'handle'   => '#handle',
            'change'   => 'change();',
            'complete' => 'complete();',
            'value'    => 4,
            'min'      => 10,
            'max'      => 100
        ]);
        $expected = 'var jsSlider = new Control.Slider($("handle"), $("element"), {onChange:function (value) {complete();}, onSlide:function (value) {change();}, range:$R(10,100), sliderValue:4});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test the serializeForm implementation.
     */
    public function testSerializeForm()
    {
        $this->Proto->get('#element');
        $result = $this->Proto->serializeForm(['isForm' => true]);
        $expected = '$("element").serialize();';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->serializeForm(['isForm' => true, 'inline' => true]);
        $expected = '$("element").serialize()';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->serializeForm(['isForm' => false]);
        $expected = '$($("element").form).serialize();';
        $this->assertEquals($expected, $result);

        $result = $this->Proto->serializeForm(['isForm' => false, 'inline' => true]);
        $expected = '$($("element").form).serialize()';
        $this->assertEquals($expected, $result);
    }
}
