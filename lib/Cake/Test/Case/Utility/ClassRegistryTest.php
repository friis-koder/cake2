<?php
/**
 * ClassRegistryTest file
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
 * @package       Cake.Test.Case.Utility
 *
 * @since         CakePHP(tm) v 1.2.0.5432
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ClassRegistry', 'Utility');

/**
 * ClassRegisterModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class ClassRegisterModel extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var bool
     */
    public $useTable = false;
}

/**
 * RegisterArticle class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticle extends ClassRegisterModel
{
}

/**
 * RegisterArticleFeatured class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticleFeatured extends ClassRegisterModel
{
}

/**
 * RegisterArticleTag class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticleTag extends ClassRegisterModel
{
}

/**
 * RegistryPluginAppModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegistryPluginAppModel extends ClassRegisterModel
{
    /**
     * tablePrefix property
     *
     * @var string
     */
    public $tablePrefix = 'something_';
}

/**
 * TestRegistryPluginModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class TestRegistryPluginModel extends RegistryPluginAppModel
{
}

/**
 * RegisterCategory class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterCategory extends ClassRegisterModel
{
}
/**
 * RegisterPrefixedDs class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterPrefixedDs extends ClassRegisterModel
{
    /**
     * useDbConfig property
     *
     * @var string
     */
    public $useDbConfig = 'doesnotexist';
}

/**
 * Abstract class for testing ClassRegistry.
 */
abstract class ClassRegistryAbstractModel extends ClassRegisterModel
{
    abstract public function doSomething();
}

/**
 * Interface for testing ClassRegistry
 */
interface ClassRegistryInterfaceTest
{
    public function doSomething();
}

/**
 * ClassRegistryTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class ClassRegistryTest extends CakeTestCase
{
    /**
     * testAddModel method
     */
    public function testAddModel()
    {
        $Tag = ClassRegistry::init('RegisterArticleTag');
        $this->assertInstanceOf('RegisterArticleTag', $Tag);

        $TagCopy = ClassRegistry::isKeySet('RegisterArticleTag');
        $this->assertTrue($TagCopy);

        $Tag->name = 'SomeNewName';

        $TagCopy = ClassRegistry::getObject('RegisterArticleTag');

        $this->assertInstanceOf('RegisterArticleTag', $TagCopy);
        $this->assertSame($Tag, $TagCopy);

        $NewTag = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'NewTag']);
        $this->assertInstanceOf('RegisterArticleTag', $NewTag);

        $NewTagCopy = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'NewTag']);

        $this->assertNotSame($Tag, $NewTag);
        $this->assertSame($NewTag, $NewTagCopy);

        $NewTag->name = 'SomeOtherName';
        $this->assertNotSame($Tag, $NewTag);
        $this->assertSame($NewTag, $NewTagCopy);

        $Tag->name = 'SomeOtherName';
        $this->assertNotSame($Tag, $NewTag);

        $this->assertTrue($TagCopy->name === 'SomeOtherName');

        $User = ClassRegistry::init(['class' => 'RegisterUser', 'alias' => 'User', 'table' => false]);
        $this->assertInstanceOf('AppModel', $User);

        $UserCopy = ClassRegistry::init(['class' => 'RegisterUser', 'alias' => 'User', 'table' => false]);
        $this->assertInstanceOf('AppModel', $UserCopy);
        $this->assertEquals($User, $UserCopy);

        $Category = ClassRegistry::init(['class' => 'RegisterCategory']);
        $this->assertInstanceOf('RegisterCategory', $Category);

        $ParentCategory = ClassRegistry::init(['class' => 'RegisterCategory', 'alias' => 'ParentCategory']);
        $this->assertInstanceOf('RegisterCategory', $ParentCategory);
        $this->assertNotSame($Category, $ParentCategory);

        $this->assertNotEquals($Category->alias, $ParentCategory->alias);
        $this->assertEquals('RegisterCategory', $Category->alias);
        $this->assertEquals('ParentCategory', $ParentCategory->alias);
    }

    /**
     * Test that init() can make models with alias set properly
     */
    public function testAddModelWithAlias()
    {
        $tag = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'NewTag']);
        $this->assertInstanceOf('RegisterArticleTag', $tag);
        $this->assertSame('NewTag', $tag->alias);
        $this->assertSame('RegisterArticleTag', $tag->name);

        $newTag = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'OtherTag']);
        $this->assertInstanceOf('RegisterArticleTag', $tag);
        $this->assertSame('OtherTag', $newTag->alias);
        $this->assertSame('RegisterArticleTag', $newTag->name);
    }

    /**
     * Test that init() can make the Aco models with alias set properly
     */
    public function testAddModelWithAliasAco()
    {
        $aco = ClassRegistry::init(['class' => 'Aco', 'alias' => 'CustomAco']);
        $this->assertInstanceOf('Aco', $aco);
        $this->assertSame('Aco', $aco->name);
        $this->assertSame('CustomAco', $aco->alias);
    }

    /**
     * testClassRegistryFlush method
     */
    public function testClassRegistryFlush()
    {
        ClassRegistry::init('RegisterArticleTag');

        $ArticleTag = ClassRegistry::getObject('RegisterArticleTag');
        $this->assertInstanceOf('RegisterArticleTag', $ArticleTag);
        ClassRegistry::flush();

        $NoArticleTag = ClassRegistry::isKeySet('RegisterArticleTag');
        $this->assertFalse($NoArticleTag);
        $this->assertInstanceOf('RegisterArticleTag', $ArticleTag);
    }

    /**
     * testAddMultipleModels method
     */
    public function testAddMultipleModels()
    {
        $Article = ClassRegistry::isKeySet('Article');
        $this->assertFalse($Article);

        $Featured = ClassRegistry::isKeySet('Featured');
        $this->assertFalse($Featured);

        $Tag = ClassRegistry::isKeySet('Tag');
        $this->assertFalse($Tag);

        $models = [['class' => 'RegisterArticle', 'alias' => 'Article'],
            ['class' => 'RegisterArticleFeatured', 'alias' => 'Featured'],
            ['class' => 'RegisterArticleTag', 'alias' => 'Tag']];

        $added = ClassRegistry::init($models);
        $this->assertTrue($added);

        $Article = ClassRegistry::isKeySet('Article');
        $this->assertTrue($Article);

        $Featured = ClassRegistry::isKeySet('Featured');
        $this->assertTrue($Featured);

        $Tag = ClassRegistry::isKeySet('Tag');
        $this->assertTrue($Tag);

        $Article = ClassRegistry::getObject('Article');
        $this->assertInstanceOf('RegisterArticle', $Article);

        $Featured = ClassRegistry::getObject('Featured');
        $this->assertInstanceOf('RegisterArticleFeatured', $Featured);

        $Tag = ClassRegistry::getObject('Tag');
        $this->assertInstanceOf('RegisterArticleTag', $Tag);
    }

    /**
     * testPluginAppModel method
     */
    public function testPluginAppModel()
    {
        $TestRegistryPluginModel = ClassRegistry::isKeySet('TestRegistryPluginModel');
        $this->assertFalse($TestRegistryPluginModel);

        //Faking a plugin
        CakePlugin::load('RegistryPlugin', ['path' => '/fake/path']);
        $TestRegistryPluginModel = ClassRegistry::init('RegistryPlugin.TestRegistryPluginModel');
        $this->assertInstanceOf('TestRegistryPluginModel', $TestRegistryPluginModel);

        $this->assertEquals('something_', $TestRegistryPluginModel->tablePrefix);

        $PluginUser = ClassRegistry::init(['class' => 'RegistryPlugin.RegisterUser', 'alias' => 'RegistryPluginUser', 'table' => false]);
        $this->assertInstanceOf('RegistryPluginAppModel', $PluginUser);

        $PluginUserCopy = ClassRegistry::getObject('RegistryPluginUser');
        $this->assertInstanceOf('RegistryPluginAppModel', $PluginUserCopy);
        $this->assertSame($PluginUser, $PluginUserCopy);
        CakePlugin::unload();
    }

    /**
     * Tests prefixed datasource names for test purposes
     */
    public function testPrefixedTestDatasource()
    {
        ClassRegistry::config(['testing' => true]);
        $Model = ClassRegistry::init('RegisterPrefixedDs');
        $this->assertEquals('test', $Model->useDbConfig);
        ClassRegistry::removeObject('RegisterPrefixedDs');

        $testConfig = ConnectionManager::getDataSource('test')->config;
        ConnectionManager::create('test_doesnotexist', $testConfig);

        $Model = ClassRegistry::init('RegisterArticle');
        $this->assertEquals('test', $Model->useDbConfig);
        $Model = ClassRegistry::init('RegisterPrefixedDs');
        $this->assertEquals('test_doesnotexist', $Model->useDbConfig);
    }

    /**
     * Tests that passing the string parameter to init() will return false if the model does not exists
     */
    public function testInitStrict()
    {
        $this->assertFalse(ClassRegistry::init('NonExistent', true));
    }

    /**
     * Test that you cannot init() an abstract class. An exception will be raised.
     *
     * @expectedException CakeException
     */
    public function testInitAbstractClass()
    {
        ClassRegistry::init('ClassRegistryAbstractModel');
    }

    /**
     * Test that you cannot init() an abstract class. A exception will be raised.
     *
     * @expectedException CakeException
     */
    public function testInitInterface()
    {
        ClassRegistry::init('ClassRegistryInterfaceTest');
    }
}
