<?php

namespace test;

use test\data\Speaker;
use arSql\Model;
use test\data\Singer;
use test\data\RulesModel;
use test\data\InvalidRulesModel;

/**
 * @group base
 */
class ModelTest extends TestCase {

    public function testGetAttributeLabel()
    {
        $speaker = new Speaker();
        $this->assertEquals('First Name', $speaker->getAttributeLabel('firstName'));
        $this->assertEquals('This is the custom label', $speaker->getAttributeLabel('customLabel'));
        $this->assertEquals('Underscore Style', $speaker->getAttributeLabel('underscore_style'));
    }

    public function testGetAttributes()
    {
        $speaker = new Speaker();
        $speaker->firstName = 'Qiang';
        $speaker->lastName = 'Xue';

        $this->assertEquals(array(
            'firstName' => 'Qiang',
            'lastName' => 'Xue',
            'customLabel' => null,
            'underscore_style' => null,
        ), $speaker->getAttributes());

        $this->assertEquals(array(
            'firstName' => 'Qiang',
            'lastName' => 'Xue',
        ), $speaker->getAttributes(array('firstName', 'lastName')));

        $this->assertEquals(array(
            'firstName' => 'Qiang',
            'lastName' => 'Xue',
        ), $speaker->getAttributes(null, array('customLabel', 'underscore_style')));

        $this->assertEquals(array(
            'firstName' => 'Qiang',
        ), $speaker->getAttributes(array('firstName', 'lastName'), array('lastName', 'customLabel', 'underscore_style')));
    }

    public function testSetAttributes()
    {
        // by default mass assignment doesn't work at all
        $speaker = new Speaker();
        $speaker->setAttributes(array('firstName' => 'Qiang', 'underscore_style' => 'test'));
        $this->assertNull($speaker->firstName);
        $this->assertNull($speaker->underscore_style);

        // in the test scenario
        $speaker = new Speaker();
        $speaker->setScenario('test');
        $speaker->setAttributes(array('firstName' => 'Qiang', 'underscore_style' => 'test'));
        $this->assertNull($speaker->underscore_style);
        $this->assertEquals('Qiang', $speaker->firstName);

        $speaker->setAttributes(array('firstName' => 'Qiang', 'underscore_style' => 'test'), false);
        $this->assertEquals('test', $speaker->underscore_style);
        $this->assertEquals('Qiang', $speaker->firstName);
    }

    public function testLoad()
    {
        $singer = new Singer();
        $this->assertEquals('Singer', $singer->formName());

        $post = array('firstName' => 'Qiang');

        Speaker::$formName = '';
        $model = new Speaker();
        $model->setScenario('test');
        $this->assertTrue($model->load($post));
        $this->assertEquals('Qiang', $model->firstName);

        Speaker::$formName = 'Speaker';
        $model = new Speaker();
        $model->setScenario('test');
        $this->assertTrue($model->load(array('Speaker' => $post)));
        $this->assertEquals('Qiang', $model->firstName);

        Speaker::$formName = 'Speaker';
        $model = new Speaker();
        $model->setScenario('test');
        $this->assertFalse($model->load(array('Example' => array())));
        $this->assertEquals('', $model->firstName);
    }

    public function testLoadMultiple()
    {
        $data = array(
            array('firstName' => 'Thomas', 'lastName' => 'Anderson'),
            array('firstName' => 'Agent', 'lastName' => 'Smith'),
        );

        Speaker::$formName = '';
        $neo = new Speaker();
        $neo->setScenario('test');
        $smith = new Speaker();
        $smith->setScenario('test');
        $this->assertTrue(Speaker::loadMultiple(array($neo, $smith), $data));
        $this->assertEquals('Thomas', $neo->firstName);
        $this->assertEquals('Smith', $smith->lastName);

        Speaker::$formName = 'Speaker';
        $neo = new Speaker();
        $neo->setScenario('test');
        $smith = new Speaker();
        $smith->setScenario('test');
        $this->assertTrue(Speaker::loadMultiple(array($neo, $smith), array('Speaker' => $data), 'Speaker'));
        $this->assertEquals('Thomas', $neo->firstName);
        $this->assertEquals('Smith', $smith->lastName);

        Speaker::$formName = 'Speaker';
        $neo = new Speaker();
        $neo->setScenario('test');
        $smith = new Speaker();
        $smith->setScenario('test');
        $this->assertFalse(Speaker::loadMultiple(array($neo, $smith), array('Speaker' => $data), 'Morpheus'));
        $this->assertEquals('', $neo->firstName);
        $this->assertEquals('', $smith->lastName);
    }

    public function testActiveAttributes()
    {
        // by default mass assignment doesn't work at all
        $speaker = new Speaker();
        $this->assertEmpty($speaker->activeAttributes());

        $speaker = new Speaker();
        $speaker->setScenario('test');
        $this->assertEquals(array('firstName', 'lastName', 'underscore_style'), $speaker->activeAttributes());
    }

    public function testIsAttributeSafe()
    {
        // by default mass assignment doesn't work at all
        $speaker = new Speaker();
        $this->assertFalse($speaker->isAttributeSafe('firstName'));

        $speaker = new Speaker();
        $speaker->setScenario('test');
        $this->assertTrue($speaker->isAttributeSafe('firstName'));

    }

    public function testSafeScenarios()
    {
        $model = new RulesModel();
        $model->rules = array(
            // validated and safe on every scenario
            array(array('account_id', 'user_id'), 'required'),
        );
        $model->getScenario(Model::SCENARIO_DEFAULT);
        $this->assertEquals(array('account_id', 'user_id'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id'), $model->activeAttributes());
        $model->setScenario('update'); // not existing scenario
        $this->assertEquals(array(), $model->safeAttributes());
        $this->assertEquals(array(), $model->activeAttributes());

        $model = new RulesModel();
        $model->rules = array(
            // validated and safe on every scenario
            array(array('account_id', 'user_id'), 'required'),
            // only in create and update scenario
            array(array('user_id'), 'number', 'on' => array('create', 'update')),
            array(array('email', 'name'), 'required', 'on' => 'create')
        );
        $model->setScenario(Model::SCENARIO_DEFAULT);
        $this->assertEquals(array('account_id', 'user_id'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id'), $model->activeAttributes());
        $model->setScenario('update');
        $this->assertEquals(array('account_id', 'user_id'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id'), $model->activeAttributes());
        $model->setScenario('create');
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->activeAttributes());

        $model = new RulesModel();
        $model->rules = array(
            // validated and safe on every scenario
            array(array('account_id', 'user_id'), 'required'),
            // only in create and update scenario
            array(array('user_id'), 'number', 'on' => array('create', 'update')),
            array(array('email', 'name'), 'required', 'on' => 'create'),
            array(array('email', 'name'), 'required', 'on' => 'update'),
        );
        $model->scenario = Model::SCENARIO_DEFAULT;
        $this->assertEquals(array('account_id', 'user_id'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id'), $model->activeAttributes());
        $model->setScenario('update');
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->activeAttributes());
        $model->setScenario('create');
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->activeAttributes());
    }

    public function testUnsafeAttributes()
    {
        $model = new RulesModel();
        $model->rules = array(
            array(array('name', '!email'), 'required'), // Name is safe to set, but email is not. Both are required
        );
        $this->assertEquals(array('name'), $model->safeAttributes());
        $this->assertEquals(array('name', 'email'), $model->activeAttributes());
        $model->setAttributes(array('name' => 'mdmunir', 'email' => 'mdm@mun.com'));
        $this->assertNull($model->email);
        $this->assertFalse($model->validate());

        $model = new RulesModel();
        $model->rules = array(
            array(array('name'), 'required'),
            array(array('!user_id'), 'default', 'value' => '3426'),
        );
        $model->setAttributes(array('name' => 'mdmunir', 'user_id' => '62792684'));
        $this->assertTrue($model->validate());
        $this->assertEquals('3426', $model->user_id);

        $model = new RulesModel();
        $model->rules = array(
            array(array('name', 'email'), 'required'),
            array(array('!email'), 'safe')
        );
        $this->assertEquals(array('name'), $model->safeAttributes());
        $model->setAttributes(array('name' => 'mdmunir', 'email' => 'm2792684@mdm.com'));
        $this->assertFalse($model->validate());

        $model = new RulesModel();
        $model->rules = array(
            array(array('name', 'email'), 'required'),
            array(array('email'), 'email'),
            array(array('!email'), 'safe', 'on' => 'update')
        );
        $model->setScenario(RulesModel::SCENARIO_DEFAULT);
        $this->assertEquals(array('name', 'email'), $model->safeAttributes());
        $model->setAttributes(array('name' => 'mdmunir', 'email' => 'm2792684@mdm.com'));
        $this->assertTrue($model->validate());

        $model->setScenario('update');
        $this->assertEquals(array('name'), $model->safeAttributes());
        $model->setAttributes(array('name' => 'D426', 'email' => 'd426@mdm.com'));
        $this->assertNotEquals('d426@mdm.com', $model->email);
    }

    public function testErrors()
    {
        $speaker = new Speaker();

        $this->assertEmpty($speaker->getErrors());
        $this->assertEmpty($speaker->getErrors('firstName'));
        $this->assertEmpty($speaker->getFirstErrors());

        $this->assertFalse($speaker->hasErrors());
        $this->assertFalse($speaker->hasErrors('firstName'));

        $speaker->addError('firstName', 'Something is wrong!');
        $this->assertEquals(array('firstName' => array('Something is wrong!')), $speaker->getErrors());
        $this->assertEquals(array('Something is wrong!'), $speaker->getErrors('firstName'));

        $speaker->addError('firstName', 'Totally wrong!');
        $this->assertEquals(array('firstName' => array('Something is wrong!', 'Totally wrong!')), $speaker->getErrors());
        $this->assertEquals(array('Something is wrong!', 'Totally wrong!'), $speaker->getErrors('firstName'));

        $this->assertTrue($speaker->hasErrors());
        $this->assertTrue($speaker->hasErrors('firstName'));
        $this->assertFalse($speaker->hasErrors('lastName'));

        $this->assertEquals(array('firstName' => 'Something is wrong!'), $speaker->getFirstErrors());
        $this->assertEquals('Something is wrong!', $speaker->getFirstError('firstName'));
        $this->assertNull($speaker->getFirstError('lastName'));

        $speaker->addError('lastName', 'Another one!');
        $this->assertEquals(array(
            'firstName' => array(
                'Something is wrong!',
                'Totally wrong!',
            ),
            'lastName' => array('Another one!'),
        ), $speaker->getErrors());

        $speaker->clearErrors('firstName');
        $this->assertEquals(array(
            'lastName' => array('Another one!'),
        ), $speaker->getErrors());

        $speaker->clearErrors();
        $this->assertEmpty($speaker->getErrors());
        $this->assertFalse($speaker->hasErrors());
    }

    public function testAddErrors()
    {
        $singer = new Singer();

        $errors = array('firstName' => array('Something is wrong!'));
        $singer->addErrors($errors);
        $this->assertEquals($singer->getErrors(), $errors);

        $singer->clearErrors();
        $singer->addErrors(array('firstName' => 'Something is wrong!'));
        $this->assertEquals($singer->getErrors(), array('firstName' => array('Something is wrong!')));

        $singer->clearErrors();
        $errors = array('firstName' => array('Something is wrong!', 'Totally wrong!'));
        $singer->addErrors($errors);
        $this->assertEquals($singer->getErrors(), $errors);

        $singer->clearErrors();
        $errors = array(
            'firstName' => array('Something is wrong!'),
            'lastName' => array('Another one!')
        );
        $singer->addErrors($errors);
        $this->assertEquals($singer->getErrors(), $errors);

        $singer->clearErrors();
        $errors = array(
            'firstName' => array('Something is wrong!', 'Totally wrong!'),
            'lastName' => array('Another one!')
        );
        $singer->addErrors($errors);
        $this->assertEquals($singer->getErrors(), $errors);

        $singer->clearErrors();
        $errors = array(
            'firstName' => array('Something is wrong!', 'Totally wrong!'),
            'lastName' => array('Another one!', 'Totally wrong!')
        );
        $singer->addErrors($errors);
        $this->assertEquals($singer->getErrors(), $errors);
    }

    public function testArraySyntax()
    {
        $speaker = new Speaker();

        // get
        $this->assertNull($speaker['firstName']);

        // isset
        $this->assertFalse(isset($speaker['firstName']));
        $this->assertFalse(isset($speaker['unExistingField']));

        // set
        $speaker['firstName'] = 'Qiang';

        $this->assertEquals('Qiang', $speaker['firstName']);
        $this->assertTrue(isset($speaker['firstName']));

        // iteration
        $attributes = array();
        foreach ($speaker as $key => $attribute) {
            $attributes[$key] = $attribute;
        }
        $this->assertEquals(array(
            'firstName' => 'Qiang',
            'lastName' => null,
            'customLabel' => null,
            'underscore_style' => null,
        ), $attributes);

        // unset
        unset($speaker['firstName']);

        // exception isn't expected here
        $this->assertNull($speaker['firstName']);
        $this->assertFalse(isset($speaker['firstName']));
    }

    public function testDefaults()
    {
        $singer = new Model();
        $this->assertEquals(array(), $singer->rules());
        $this->assertEquals(array(), $singer->attributeLabels());
    }

    public function testDefaultScenarios()
    {
        $singer = new Singer();
        $this->assertEquals(array('default' => array('lastName', 'underscore_style', 'test')), $singer->scenarios());

        $scenarios = array(
            'default' => array('id', 'name', 'description'),
            'administration' => array('name', 'description', 'is_disabled'),
        );
        $model = new ComplexModel1();
        $this->assertEquals($scenarios, $model->scenarios());
        $scenarios = array(
            'default' => array('id', 'name', 'description'),
            'suddenlyUnexpectedScenario' => array('name', 'description'),
            'administration' => array('id', 'name', 'description', 'is_disabled'),
        );
        $model = new ComplexModel2();
        $this->assertEquals($scenarios, $model->scenarios());
    }

    public function testIsAttributeRequired()
    {
        $singer = new Singer();
        $this->assertFalse($singer->isAttributeRequired('firstName'));
        $this->assertTrue($singer->isAttributeRequired('lastName'));

        // attribute is not marked as required when a conditional validation is applied using `$when`.
        // the condition should not be applied because this info may be retrieved before model is loaded with data
        $singer->firstName = 'qiang';
        $this->assertFalse($singer->isAttributeRequired('test'));
        $singer->firstName = 'cebe';
        $this->assertFalse($singer->isAttributeRequired('test'));
    }

    /**
    * @expectedException arSql\exception\InvalidConfigException
    * @expectedExceptionMessage Invalid validation rule: a rule must specify both attribute names and validator type.
    */
    public function testCreateValidators()
    {
        // $this->expectException('yii\base\InvalidConfigException');
        // $this->expectExceptionMessage('Invalid validation rule: a rule must specify both attribute names and validator type.');

        $invalid = new InvalidRulesModel();
        $invalid->createValidators();
    }

    /**
     * Ensure 'safe' validator works for write-only properties.
     * Normal validator can not work here though.
     */
    public function testValidateWriteOnly()
    {
        $model = new WriteOnlyModel();

        $model->setAttributes(array('password' => 'test'), true);
        $this->assertEquals('test', $model->passwordHash);

        $this->assertTrue($model->validate());
    }
}

class ComplexModel1 extends Model
{
    public function rules()
    {
        return array(
            array(array('id'), 'required', 'except' => 'administration'),
            array(array('name', 'description'), 'filter', 'filter' => 'trim'),
            array(array('is_disabled'), 'boolean', 'on' => 'administration'),
        );
    }
}

class ComplexModel2 extends Model
{
    public function rules()
    {
        return array(
            array(array('id'), 'required', 'except' => 'suddenlyUnexpectedScenario'),
            array(array('name', 'description'), 'filter', 'filter' => 'trim'),
            array(array('is_disabled'), 'boolean', 'on' => 'administration'),
        );
    }
}

class WriteOnlyModel extends Model
{
    public $passwordHash;

    public function rules()
    {
        return array(
            array(array('password'), 'safe'),
        );
    }

    public function setPassword($pw)
    {
        $this->passwordHash = $pw;
    }
}

