<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Openbizx\Object;

use Openbizx\Object\ObjectHelper;

/**
 * Description of BizObject
 *
 * @author agus
 */
class Object {

    /**
     * Returns the fully qualified name of this class.
     *  This method can use as identifier of singleton object. 
     *  For example object that subtitute MetaObject
     * 
     * @return string the fully qualified name of this class.
     */
    public static function className() {
        return get_called_class();
    }

    public function __construct($config = []) {
        if (!empty($config)) {
            ObjecttHelper::configure($this, $config);
        }
        $this->init();        
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init() {
        
    }

    /**
     * Returns the value of an object property.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $object->property;`.
     * @param string $name the property name
     * @return mixed the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only
     * @see __set()
     */
    public function __get($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            //throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
            throw new \Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            //throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
            throw new \Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Sets value of an object property.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$object->property = $value;`.
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is read-only
     * @see __get()
     */
    public function __set($name, $value) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            //throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
            throw new \Exception('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            //throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
            throw new \Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Checks if the named property is set (not null).
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($object->property)`.
     *
     * Note that if the property is not defined, false will be returned.
     * @param string $name the property name or the event name
     * @return boolean whether the named property is set (not null).
     */
    public function __isset($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    /**
     * Sets an object property to null.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($object->property)`.
     *
     * Note that if the property is not defined, this method will do nothing.
     * If the property is read-only, it will throw an exception.
     * @param string $name the property name
     * @throws InvalidCallException if the property is read only.
     */
    public function __unset($name) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Calls the named method which is not a class method.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     * @param string $name the method name
     * @param array $params method parameters
     * @throws UnknownMethodException when calling unknown method
     * @return mixed the method return value
     */
    public function __xcall($name, $params) {
        throw new UnknownMethodException('Unknown method: ' . get_class($this) . "::$name()");
    }

    /**
     * Returns a value indicating whether a property is defined.
     * A property is defined if:
     *
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property is defined
     * @see canGetProperty()
     * @see canSetProperty()
     */
    public function hasProperty($name, $checkVars = true) {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

    /**
     * Returns a value indicating whether a property is readable.
     * A property is readable if:
     *
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be read
     * @see isReadableProperty()
     */
    public function canGetProperty($name, $checkVars = true) {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
     * Returns a value indicating whether a property is writable.
     * A property is writable if:
     *
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be written
     * @see isReadableProperty()
     */
    public function isWritableProperty($name, $checkVars = true) {
        return method_exists($this, 'set' . $name) || ($checkVars && property_exists($this, $name));
    }

    /**
     * Returns a value indicating whether a method is defined.
     *
     * The default implementation is a call to php function `method_exists()`.
     * You may override this method when you implemented the php magic method `__call()`.
     * @param string $name the property name
     * @return boolean whether the property is defined
     */
    public function hasMethod($name) {
        return method_exists($this, $name);
    }

    /**
     * Converts the object into an array.
     * The default implementation will return all public property values as an array.
     * 
     * @return array the array representation of the object
     * @todo Maybe this method must inherit from Arrayable interface.
     */
    public function toArray() {
        return ObjectHelper::getObjectVars($this);
    }

}
