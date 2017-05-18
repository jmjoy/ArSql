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
        $model->scenarios = array(
            Model::SCENARIO_DEFAULT => array('account_id', 'user_id'),
        );
        $model->getScenario(Model::SCENARIO_DEFAULT);
        $this->assertEquals(array('account_id', 'user_id'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id'), $model->activeAttributes());
        $model->setScenario('update'); // not existing scenario
        $this->assertEquals(array(), $model->safeAttributes());
        $this->assertEquals(array(), $model->activeAttributes());

        $model = new RulesModel();
        $model->scenarios = array(
            Model::SCENARIO_DEFAULT => array('account_id', 'user_id'),
            'update' => array('account_id', 'user_id'),
            'create' => array('account_id', 'user_id', 'email', 'name'),
        );
        $model->scenario = Model::SCENARIO_DEFAULT;
        $this->assertEquals(array('account_id', 'user_id'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id'), $model->activeAttributes());
        $model->setScenario('update');
        $this->assertEquals(array('account_id', 'user_id'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id'), $model->activeAttributes());
        $model->setScenario('create');
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->safeAttributes());
        $this->assertEquals(array('account_id', 'user_id', 'email', 'name'), $model->activeAttributes());

        $model = new RulesModel();
        $model->scenarios = array(
            Model::SCENARIO_DEFAULT => array('account_id', 'user_id'),
            // only in create and update scenario
            'create' => array('account_id', 'user_id', 'email', 'name'),
            'update' => array('account_id', 'user_id', 'email', 'name'),
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
        $model->scenarios = array(
            Model::SCENARIO_DEFAULT => array('name', '!email'), // Name is safe to set, but email is not. Both are required
        );
        $this->assertEquals(array('name'), $model->safeAttributes());
        $this->assertEquals(array('name', 'email'), $model->activeAttributes());
        $model->setAttributes(array('name' => 'mdmunir', 'email' => 'mdm@mun.com'));
        $this->assertNull($model->email);

        $model = new RulesModel();
        $model->scenarios = array(
            RulesModel::SCENARIO_DEFAULT => array('name', 'email'),
            'update' => array('name', '!email'),
        );
        $model->setScenario(RulesModel::SCENARIO_DEFAULT);
        $this->assertEquals(array('name', 'email'), $model->safeAttributes());

        $model->setScenario('update');
        $this->assertEquals(array('name'), $model->safeAttributes());
        $model->setAttributes(array('name' => 'D426', 'email' => 'd426@mdm.com'));
        $this->assertNotEquals('d426@mdm.com', $model->email);
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
        $this->assertEquals(array(), $singer->attributeLabels());
    }

    public function testDefaultScenarios()
    {
        $singer = new Singer();
        $this->assertEquals(array('default' => array('lastName', 'underscore_style', 'test')), $singer->scenarios());
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
    }
}

class WriteOnlyModel extends Model
{
    public $passwordHash;


    protected function defaultAttributes() {
        return array('password');
    }

    public function setPassword($pw)
    {
        $this->passwordHash = $pw;
    }
}

