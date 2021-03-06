<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\Validator\Constraints;

use Symfony\Bundle\DoctrineMongoDBBundle\Tests\Fixtures\Validator\Document;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\DoctrineMongoDBUnique;
use Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\DoctrineMongoDBUniqueValidator;

class DoctrineMongoDBUniqueValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $dm;
    private $repository;
    private $validator;
    private $classMetadata;
    private $uniqueFieldName = 'unique';

    public function setUp()
    {
        $this->classMetadata = $this->getClassMetadata();
        $this->repository = $this->getDocumentRepository();
        $this->dm = $this->getDocumentManager($this->classMetadata, $this->repository);
        $this->validator = new DoctrineMongoDBUniqueValidator($this->dm);
    }

    public function tearDown()
    {
        unset($this->validator, $this->dm, $this->repository, $this->classMetadata);
    }

    /**
     * @dataProvider getFieldsPathsValuesDocumentsAndReturns
     */
    public function testShouldValidateValidStringMappingValues($field, $path, $value, $document, $return)
    {
        $this->setFieldMapping($field, 'string');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(array($path => $value))
            ->will($this->returnValue($return));

        $this->assertTrue($this->validator->isValid($document, new DoctrineMongoDBUnique($path)));
    }

    public function getFieldsPathsValuesDocumentsAndReturns()
    {
        $field    = 'unique';
        $path     = $field;
        $value    = 'someUniqueValueToBeValidated';
        $document = $this->getFixtureDocument($field, $value);

        return array(
            array('unique', 'unique', 'someUniqueValueToBeValidated', $document, null),
            array('unique', 'unique', 'someUniqueValueToBeValidated', $document, $document),
            array('unique', 'unique', 'someUniqueValueToBeValidated', $document, $this->getFixtureDocument($field, $value)),
        );
    }

    /**
     * @dataProvider getFieldTypesFieldsPathsValuesAndQueries
     */
    public function testGetsCorrectQueryArrayForCollection($type, $field, $path, $value, $query)
    {
        $this->setFieldMapping($field, $type);
        $document = $this->getFixtureDocument($field, $value);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with($query);

        $this->validator->isValid($document, new DoctrineMongoDBUnique($path));
    }

    public function getFieldTypesFieldsPathsValuesAndQueries()
    {
        $field = 'unique';
        $key   = 'uniqueValue';
        $path  = $field.'.'.$key;
        $value = 'someUniqueValueToBeValidated';

        return array(
            array('collection', $field, $path, array($value), array($field => array('$in' => array($value)))),
            array('hash', $field, $path, array($key => $value), array($path => $value)),
        );
    }

    protected function getDocumentManager(ClassMetadata $classMetadata, DocumentRepository $repository)
    {
        $dm = $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getClassMetadata', 'getRepository'))
            ->getMock();
        $dm->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetadata));
        $dm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        return $dm;
    }

    protected function getDocumentRepository()
    {
        $dm = $this->getMock('Doctrine\ODM\MongoDB\DocumentRepository', array('findOneBy'), array(), '', false, false);

        return $dm;
    }

    protected function getClassMetadata()
    {
        $classMetadata = $this->getMock('Doctrine\ODM\MongoDB\Mapping\ClassMetadata', array(), array(), '', false, false);
        $classMetadata->expects($this->any())
            ->method('getFieldValue')
            ->will($this->returnCallback(function($document, $fieldName) {
                        return $document->{$fieldName};
                    }));

        $classMetadata->fieldmappings = array();

        return $classMetadata;
    }

    protected function setFieldMapping($fieldName, $type, array $attributes = array())
    {
        $this->classMetadata->fieldMappings[$fieldName] = array_merge(array(
                'fieldName' => $fieldName,
                'type' => $type,
                ), $attributes);
    }

    protected function getFixtureDocument($field, $value, $id = 1)
    {
        $document = new Document();
        $document->{$field} = $value;
        $document->id = 1;

        return $document;
    }
}
