<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ConnectionsBundle\Tests\Unit\Entity;

use PHPUnit_Framework_MockObject_MockObject;

/**
 * Abstract entity test for setters and getters.
 */
abstract class AbstractEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string[] List of fields that should not be checked for tests.
     */
    private $ignoredFields = [];

    /**
     * Returns list of fields to test. Works as data provider.
     *
     * @return array
     */
    abstract public function getFieldsData();

    /**
     * Returns entity class name.
     *
     * @return string
     */
    abstract public function getClassName();

    /**
     * Returns list of fields that should not be checked for tests.
     *
     * @return array
     */
    protected function getIgnoredFields()
    {
        return $this->ignoredFields;
    }

    /**
     * Set list of fields that should not be checked for tests.
     *
     * @param string[] $fields
     */
    protected function setIgnoredFields(array $fields)
    {
        $this->ignoredFields = $fields;
    }

    /**
     * Set list of fields that should not be checked for tests.
     *
     * @param string[] $field
     */
    protected function addIgnoredFields($field)
    {
        $this->ignoredFields[] = $field;
    }

    /**
     * Tests field setter and getter.
     *
     * @param string        $field
     * @param null|string   $type
     * @param null|string   $addMethod
     * @param null|string   $removeMethod
     * @param null|string[] $additionalSetter
     *
     * @dataProvider getFieldsData()
     */
    public function testSetterGetter(
        $field,
        $type = null,
        $addMethod = null,
        $removeMethod = null,
        $additionalSetter = null
    ) {
        $objectClass = $this->getClassName();

        $setter = 'set' . ucfirst($field);
        $getter = 'get' . ucfirst($field);

        if ($type === 'boolean') {
            $getter = 'is' . ucfirst($field);
        }

        $stub = $this->getMockForAbstractClass($objectClass);

        $this->validate($stub, $getter, $setter, $addMethod, $removeMethod, $additionalSetter);

        $expectedObject = $this->getExpectedVariable($type);

        if ($type && class_exists($type)) {
            $hash = spl_object_hash($expectedObject);

            if ($addMethod) {
                $stub->$addMethod($expectedObject);

                foreach ($stub->$getter() as $collectionObject) {
                    $this->assertEquals($hash, spl_object_hash($collectionObject));
                }
            }

            if ($removeMethod) {
                $stub->$removeMethod($expectedObject);
                $this->assertEquals(0, count($stub->$getter()));
            }

            $stub->$setter($expectedObject);
            $this->assertEquals($hash, spl_object_hash($stub->$getter()));
        } else {
            $stub->$setter($expectedObject);
            $this->assertEquals($expectedObject, $stub->$getter());

            if ($addMethod) {
                $stub->$addMethod($this->getExpectedVariable(null));
                $this->assertEquals(2, count($stub->$getter()));
            }
        }

        if ($additionalSetter) {
            $stub = $this->getMockForAbstractClass($objectClass);
            $setter = key($additionalSetter);
            $stub->$setter($additionalSetter[$setter][0]);
            $this->assertEquals($additionalSetter[$setter][1], $stub->$getter());
        }
    }

    /**
     * Tests if all entity fields are registered.
     */
    public function testAllEntityFieldsRegistered()
    {
        $reflect = new \ReflectionClass($this->getClassName());
        $properties = $reflect->getProperties();

        $fields = [];

        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            $fields[] = $property->getName();
        }

        $parentClass = $reflect->getParentClass();
        if ($parentClass) {
            $parentClassProperties = $parentClass->getProperties();
            /** @var \ReflectionProperty $property */
            foreach ($parentClassProperties as $property) {
                $this->addIgnoredFields($property->getName());
            }
        }

        $traits = $reflect->getTraits();
        if ($traits) {
            foreach ($traits as $trait) {
                $traitProperties = $trait->getProperties();
                /** @var \ReflectionProperty $property */
                foreach ($traitProperties as $property) {
                    $this->addIgnoredFields($property->getName());
                }
            }
        }

        $registeredFields = [];

        foreach ($this->getFieldsData() as $data) {
            $registeredFields[] = $data[0];
        }

        $diff = array_diff($fields, $registeredFields, $this->getIgnoredFields());

        if (count($diff) !== 0) {
            $this->fail(
                sprintf(
                    'All entity fields must be registered in test. Please check field(s) "%s".',
                    implode('", "', $diff)
                )
            );
        }
    }

    /**
     * Return expected variable for compare.
     *
     * @param null|string $type
     *
     * @return object
     */
    protected function getExpectedVariable($type)
    {
        if ($type === null || $type == 'boolean') {
            return rand(0, 9999);
        } elseif ($type == 'string') {
            return 'string' . rand(0, 9999);
        } elseif ($type == 'array') {
            return [rand(0, 9999)];
        } elseif ($type == '\DateTime') {
            return new \DateTime();
        } elseif (class_exists($type)) {
            return $this->getMockForAbstractClass($type);
        }

        return null;
    }

    /**
     * Validate class before test.
     *
     * @param PHPUnit_Framework_MockObject_MockObject $stub
     * @param null|string                             $getter
     * @param null|string                             $setter
     * @param null|string                             $addMethod
     * @param null|string                             $removeMethod
     * @param null|string[]                           $additionalSetter
     */
    protected function validate($stub, $getter, $setter, $addMethod, $removeMethod, $additionalSetter)
    {
        $this->assertTrue(method_exists($stub, $setter), "Method ${setter}() not found!");
        $this->assertTrue(method_exists($stub, $getter), "Method ${getter}() not found!");

        if ($addMethod) {
            $this->assertTrue(method_exists($stub, $addMethod), "Method ${addMethod}() not found!");
        }

        if ($removeMethod) {
            $this->assertTrue(method_exists($stub, $removeMethod), "Method ${removeMethod}() not found!");
        }

        if ($additionalSetter) {
            $setter = key($additionalSetter);
            $this->assertTrue(method_exists($stub, $setter), "Method ${setter}() not found!");
        }
    }
}
