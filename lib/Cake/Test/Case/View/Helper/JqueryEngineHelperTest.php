<?php
/**
 * JqueryEngineTestCase
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
 * @link          https://cakephp.org CakePHP Project
 *
 * @package       Cake.Test.Case.View.Helper
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('HtmlHelper', 'View/Helper');
App::uses('JsHelper', 'View/Helper');
App::uses('JqueryEngineHelper', 'View/Helper');
App::uses('View', 'View');

/**
 * JqueryEngineHelperTest
 *
 * @package       Cake.Test.Case.View.Helper
 */
class JqueryEngineHelperTest extends CakeTestCase
{
    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        $controller = null;
        $this->View = $this->getMock('View', ['addScript'], [&$controller]);
        $this->Jquery = new JqueryEngineHelper($this->View);
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Jquery);
    }

    /**
     * test selector method
     */
    public function testSelector()
    {
        $result = $this->Jquery->get('#content');
        $this->assertEquals($this->Jquery, $result);
        $this->assertEquals($this->Jquery->selection, '$("#content")');

        $result = $this->Jquery->get('document');
        $this->assertEquals($this->Jquery, $result);
        $this->assertEquals($this->Jquery->selection, '$(document)');

        $result = $this->Jquery->get('window');
        $this->assertEquals($this->Jquery, $result);
        $this->assertEquals($this->Jquery->selection, '$(window)');

        $result = $this->Jquery->get('ul');
        $this->assertEquals($this->Jquery, $result);
        $this->assertEquals($this->Jquery->selection, '$("ul")');
    }

    /**
     * test event binding
     */
    public function testEvent()
    {
        $this->Jquery->get('#myLink');
        $result = $this->Jquery->event('click', 'doClick', ['wrap' => false]);
        $expected = '$("#myLink").bind("click", doClick);';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->event('click', '$(this).show();', ['stop' => false]);
        $expected = '$("#myLink").bind("click", function (event) {$(this).show();});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->event('click', '$(this).hide();');
        $expected = '$("#myLink").bind("click", function (event) {$(this).hide();' . "\n" . 'return false;});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test dom ready event creation
     */
    public function testDomReady()
    {
        $result = $this->Jquery->domReady('foo.name = "bar";');
        $expected = '$(document).ready(function () {foo.name = "bar";});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test Each method
     */
    public function testEach()
    {
        $this->Jquery->get('#foo');
        $result = $this->Jquery->each('$(this).hide();');
        $expected = '$("#foo").each(function () {$(this).hide();});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test Effect generation
     */
    public function testEffect()
    {
        $this->Jquery->get('#foo');
        $result = $this->Jquery->effect('show');
        $expected = '$("#foo").show();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('hide');
        $expected = '$("#foo").hide();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('hide', ['speed' => 'fast']);
        $expected = '$("#foo").hide("fast");';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('fadeIn');
        $expected = '$("#foo").fadeIn();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('fadeOut');
        $expected = '$("#foo").fadeOut();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('slideIn');
        $expected = '$("#foo").slideDown();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('slideOut');
        $expected = '$("#foo").slideUp();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('slideDown');
        $expected = '$("#foo").slideDown();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->effect('slideUp');
        $expected = '$("#foo").slideUp();';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test Request Generation
     */
    public function testRequest()
    {
        $result = $this->Jquery->request(['controller' => 'posts', 'action' => 'view', 1]);
        $expected = '$.ajax({url:"\\/posts\\/view\\/1"});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->request(['controller' => 'posts', 'action' => 'view', 1], [
            'update' => '#content'
        ]);
        $expected = '$.ajax({dataType:"html", success:function (data, textStatus) {$("#content").html(data);}, url:"\/posts\/view\/1"});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->request('/people/edit/1', [
            'method'        => 'post',
            'before'        => 'doBefore',
            'complete'      => 'doComplete',
            'success'       => 'doSuccess',
            'error'         => 'handleError',
            'type'          => 'json',
            'data'          => ['name' => 'jim', 'height' => '185cm'],
            'wrapCallbacks' => false
        ]);
        $expected = '$.ajax({beforeSend:doBefore, complete:doComplete, data:"name=jim&height=185cm", dataType:"json", error:handleError, success:doSuccess, type:"post", url:"\\/people\\/edit\\/1"});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->request('/people/edit/1', [
            'update'        => '#updated',
            'success'       => 'doFoo',
            'method'        => 'post',
            'wrapCallbacks' => false
        ]);
        $expected = '$.ajax({dataType:"html", success:function (data, textStatus) {doFoo$("#updated").html(data);}, type:"post", url:"\\/people\\/edit\\/1"});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->request('/people/edit/1', [
            'update'         => '#updated',
            'success'        => 'doFoo',
            'method'         => 'post',
            'dataExpression' => true,
            'data'           => '$("#someId").serialize()',
            'wrapCallbacks'  => false
        ]);
        $expected = '$.ajax({data:$("#someId").serialize(), dataType:"html", success:function (data, textStatus) {doFoo$("#updated").html(data);}, type:"post", url:"\\/people\\/edit\\/1"});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->request('/people/edit/1', [
            'success'        => 'doFoo',
            'before'         => 'doBefore',
            'method'         => 'post',
            'dataExpression' => true,
            'data'           => '$("#someId").serialize()',
        ]);
        $expected = '$.ajax({beforeSend:function (XMLHttpRequest) {doBefore}, data:$("#someId").serialize(), success:function (data, textStatus) {doFoo}, type:"post", url:"\\/people\\/edit\\/1"});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->request('/people/edit/1', [
            'success'        => 'doFoo',
            'xhr'            => 'return jQuery.ajaxSettings.xhr();',
            'async'          => true,
            'method'         => 'post',
            'dataExpression' => true,
            'data'           => '$("#someId").serialize()',
        ]);
        $expected = '$.ajax({async:true, data:$("#someId").serialize(), success:function (data, textStatus) {doFoo}, type:"post", url:"\/people\/edit\/1", xhr:function () {return jQuery.ajaxSettings.xhr();}});';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that querystring arguments are not double escaped.
     */
    public function testRequestWithQueryStringArguments()
    {
        $url = '/users/search/sort:User.name/direction:desc?nome=&cpm=&audience=public';
        $result = $this->Jquery->request($url);
        $expected = '$.ajax({url:"\\/users\\/search\\/sort:User.name\\/direction:desc?nome=&cpm=&audience=public"});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test that alternate jQuery object values work for request()
     */
    public function testRequestWithAlternateJqueryObject()
    {
        $this->Jquery->jQueryObject = '$j';

        $result = $this->Jquery->request('/people/edit/1', [
            'update'         => '#updated',
            'success'        => 'doFoo',
            'method'         => 'post',
            'dataExpression' => true,
            'data'           => '$j("#someId").serialize()',
            'wrapCallbacks'  => false
        ]);
        $expected = '$j.ajax({data:$j("#someId").serialize(), dataType:"html", success:function (data, textStatus) {doFoo$j("#updated").html(data);}, type:"post", url:"\\/people\\/edit\\/1"});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test sortable list generation
     */
    public function testSortable()
    {
        $this->Jquery->get('#myList');
        $result = $this->Jquery->sortable([
            'distance'      => 5,
            'containment'   => 'parent',
            'start'         => 'onStart',
            'complete'      => 'onStop',
            'sort'          => 'onSort',
            'wrapCallbacks' => false
        ]);
        $expected = '$("#myList").sortable({containment:"parent", distance:5, sort:onSort, start:onStart, stop:onStop});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->sortable([
            'distance'    => 5,
            'containment' => 'parent',
            'start'       => 'onStart',
            'complete'    => 'onStop',
            'sort'        => 'onSort',
        ]);
        $expected = '$("#myList").sortable({containment:"parent", distance:5, sort:function (event, ui) {onSort}, start:function (event, ui) {onStart}, stop:function (event, ui) {onStop}});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test drag() method
     */
    public function testDrag()
    {
        $this->Jquery->get('#element');
        $result = $this->Jquery->drag([
            'container'     => '#content',
            'start'         => 'onStart',
            'drag'          => 'onDrag',
            'stop'          => 'onStop',
            'snapGrid'      => [10, 10],
            'wrapCallbacks' => false
        ]);
        $expected = '$("#element").draggable({containment:"#content", drag:onDrag, grid:[10,10], start:onStart, stop:onStop});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->drag([
            'container' => '#content',
            'start'     => 'onStart',
            'drag'      => 'onDrag',
            'stop'      => 'onStop',
            'snapGrid'  => [10, 10],
        ]);
        $expected = '$("#element").draggable({containment:"#content", drag:function (event, ui) {onDrag}, grid:[10,10], start:function (event, ui) {onStart}, stop:function (event, ui) {onStop}});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test drop() method
     */
    public function testDrop()
    {
        $this->Jquery->get('#element');
        $result = $this->Jquery->drop([
            'accept'        => '.items',
            'hover'         => 'onHover',
            'leave'         => 'onExit',
            'drop'          => 'onDrop',
            'wrapCallbacks' => false
        ]);
        $expected = '$("#element").droppable({accept:".items", drop:onDrop, out:onExit, over:onHover});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->drop([
            'accept' => '.items',
            'hover'  => 'onHover',
            'leave'  => 'onExit',
            'drop'   => 'onDrop',
        ]);
        $expected = '$("#element").droppable({accept:".items", drop:function (event, ui) {onDrop}, out:function (event, ui) {onExit}, over:function (event, ui) {onHover}});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test slider generation
     */
    public function testSlider()
    {
        $this->Jquery->get('#element');
        $result = $this->Jquery->slider([
            'complete'      => 'onComplete',
            'change'        => 'onChange',
            'min'           => 0,
            'max'           => 10,
            'value'         => 2,
            'direction'     => 'vertical',
            'wrapCallbacks' => false
        ]);
        $expected = '$("#element").slider({change:onChange, max:10, min:0, orientation:"vertical", stop:onComplete, value:2});';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->slider([
            'complete'  => 'onComplete',
            'change'    => 'onChange',
            'min'       => 0,
            'max'       => 10,
            'value'     => 2,
            'direction' => 'vertical',
        ]);
        $expected = '$("#element").slider({change:function (event, ui) {onChange}, max:10, min:0, orientation:"vertical", stop:function (event, ui) {onComplete}, value:2});';
        $this->assertEquals($expected, $result);
    }

    /**
     * test the serializeForm method
     */
    public function testSerializeForm()
    {
        $this->Jquery->get('#element');
        $result = $this->Jquery->serializeForm(['isForm' => false]);
        $expected = '$("#element").closest("form").serialize();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->serializeForm(['isForm' => true]);
        $expected = '$("#element").serialize();';
        $this->assertEquals($expected, $result);

        $result = $this->Jquery->serializeForm(['isForm' => false, 'inline' => true]);
        $expected = '$("#element").closest("form").serialize()';
        $this->assertEquals($expected, $result);
    }
}
