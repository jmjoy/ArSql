<?php

namespace test\data\validators\models;

class FakedValidationModel extends Model
{
    public $val_attr_a;
    public $val_attr_b;
    public $val_attr_c;
    public $val_attr_d;
    public $safe_attr;
    private $attr = array();
    private $inlineValArgs;

    /**
     * @param  array $attributes
     * @return self
     */
    public static function createWithAttributes($attributes = array())
    {
        $m = new static();
        foreach ($attributes as $attribute => $value) {
            $m->$attribute = $value;
        }

        return $m;
    }

    public function rules()
    {
        return array(
            array(array('val_attr_a', 'val_attr_b'), 'required', 'on' => 'reqTest'),
            array('val_attr_c', 'integer'),
            array('attr_images', 'file', 'maxFiles' => 3, 'extensions' => array('png'), 'on' => 'validateMultipleFiles', 'checkExtensionByMimeType' => false),
            array('attr_image', 'file', 'extensions' => array('png'), 'on' => 'validateFile', 'checkExtensionByMimeType' => false),
            array('!safe_attr', 'integer')
        );
    }

    public function inlineVal($attribute, $params = array(), $validator)
    {
        $this->inlineValArgs = func_get_args();

        return true;
    }

    public function clientInlineVal($attribute, $params = array(), $validator)
    {
        return func_get_args();
    }

    public function __get($name)
    {
        if (stripos($name, 'attr') === 0) {
            return isset($this->attrarray($name)) ? $this->attrarray($name) : null;
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (stripos($name, 'attr') === 0) {
            $this->attrarray($name) = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    public function getAttributeLabel($attr)
    {
        return $attr;
    }

    /**
     * Returns the arguments of the inlineVal method in the last call.
     * @return array|null an array of arguments in the last call or null if method never been called.
     * @see inlineVal
     */
    public function getInlineValArgs()
    {
        return $this->inlineValArgs;
    }
}

