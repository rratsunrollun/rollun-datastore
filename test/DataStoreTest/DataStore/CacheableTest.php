<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.07.16
 * Time: 12:32
 */

namespace rollun\test\DataStoreTest\DataStore;

use rollun\datastore\DataSource\DbTableDataSource;
use rollun\datastore\DataStore\Cacheable;
use rollun\datastore\DataStore\DbTable;
use Zend\Db\TableGateway\TableGateway;

class CacheableTest extends AbstractTest
{

    /** @var  Cacheable */
    protected $object;

    /** @var  DbTableDataSource */
    protected $dataSource;

    /** @var  DbTable */
    protected $cacheable;

    protected $cacheableDbTableName;

    protected $dataSourceDbTableName;

    protected $adapter;

    protected $configTableDefault = array(
        'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
        'anotherId' => 'INT NOT NULL',
        'fString' => 'CHAR(20)',
        'fInt' => 'INT'

    );

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->adapter = $this->container->get('db');
        
        $this->cacheableDbTableName = $this->config['testDbTable']['tableName'];
        $this->dataSourceDbTableName = $this->config['testDataSourceDb']['tableName'];

        $this->cacheable = $this->container->get('testDbTable');
        $this->dataSource = $this->container->get('testDataSourceDb');

        $this->object = $this->container->get('testCacheable');

    }


    protected function _initObject($data = null)
    {

        if (is_null($data)) {
            $data = $this->_itemsArrayDelault;
        }

        $this->_prepareTable($data, $this->cacheableDbTableName);
        $this->_prepareTable($data, $this->dataSourceDbTableName);

        $cacheableDbTable = new TableGateway($this->cacheableDbTableName, $this->adapter);
        $dataSourceDbTable = new TableGateway($this->dataSourceDbTableName, $this->adapter);

        foreach ($data as $record) {
            $cacheableDbTable->insert($record);
            $dataSourceDbTable->insert($record);
        }
    }

    /**
     * This method init $this->object
     * @param $data
     * @param $dbTableName
     */
    protected function _prepareTable($data, $dbTableName)
    {

        $quoteTableName = $this->adapter->platform->quoteIdentifier($dbTableName);

        $deleteStatementStr = "DROP TABLE IF EXISTS " . $quoteTableName;
        $deleteStatement = $this->adapter->query($deleteStatementStr);
        $deleteStatement->execute();

        $createStr = "CREATE TABLE IF NOT EXISTS  " . $quoteTableName;
        $fields = $this->_getDbTableFields($data);
        $createStatementStr = $createStr . '(' . $fields . ') ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;';
        $createStatement = $this->adapter->query($createStatementStr);
        $createStatement->execute();
    }

    /**
     *
     * @param array $data
     */
    protected function _getDbTableFields($data)
    {
        $record = array_shift($data);
        reset($record);
        $firstKey = key($record);
        $firstValue = array_shift($record);
        $dbTableFields = '';
        if (is_string($firstValue)) {
            $dbTableFields = '`' . $firstKey . '` CHAR(80) PRIMARY KEY';
        } elseif (is_integer($firstValue)) {
            $dbTableFields = '`' . $firstKey . '` INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
        } else {
            trigger_error("Type of primary key must be int or string", E_USER_ERROR);
        }
        foreach ($record as $key => $value) {
            if (is_string($value)) {
                $fieldType = ', `' . $key . '` CHAR(80)';
            } elseif (is_integer($value)) {
                $fieldType = ', `' . $key . '` INT';
            } elseif (is_float($value)) {
                $fieldType = ', `' . $key . '` DOUBLE PRECISION';
            } elseif (is_null($value)) {
                $fieldType = ', `' . $key . '` INT';
            } elseif (is_bool($value)) {
                $fieldType = ', `' . $key . '` BIT';
            } else {
                trigger_error("Type of field of array isn't supported.", E_USER_ERROR);
            }
            $dbTableFields = $dbTableFields . $fieldType;
        }
        return $dbTableFields;
    }



    public function testCreate_withoutId()
    {
        $this->_initObject();
        $newItem = $this->object->create(
            array(
                'fFloat' => 1000.01,
                'fString' => 'Create_withoutId_'
            )
        );
        $this->object->refresh();
        $id = $newItem['id'];
        $insertedItem = $this->object->read($id);
        $this->assertEquals(
            'Create_withoutId_',
            $insertedItem['fString']
        );
        $this->assertEquals(
            1000.01,
            $insertedItem['fFloat']
        );
    }
    public function testCreate_withtId()
    {
        $this->_initObject();
        $newItem = $this->object->create(
            array(
                'id' => 1000,
                'fFloat' => 1000.01,
                'fString' => 'Create_withId'
            )
        );
        $this->object->refresh();
        $id = $newItem['id'];
        $insertedItem = $this->object->read($id);
        $this->assertEquals(
            'Create_withId', $insertedItem['fString']
        );
        $this->assertEquals(
            1000, $id
        );
    }

    public function testCreate_withtIdRewrite()
    {
        $this->_initObject();
        $newItem = $this->object->create(
            array(
                'id' => 2,
                'fString' => 'Create_withtIdRewrite'
            ), true
        );
        $this->object->refresh();
        $id = $newItem['id'];
        $insertedItem = $this->object->read($id);
        $this->assertEquals(
            'Create_withtIdRewrite', $insertedItem['fString']
        );
        $this->assertEquals(
            2, $id
        );
    }

    public function testUpdate_withtId_WhichPresent()
    {

        $this->_initObject();
        $row = $this->object->update(
            array(
                'id' => 3,
                'fString' => 'withtId_WhichPresent'
            )
        );
        $this->object->refresh();
        $item = $this->object->read(3);
        $this->assertEquals(
            40, $item['anotherId']
        );
        $this->assertEquals(
            'withtId_WhichPresent', $item['fString']
        );
        $this->assertEquals(
            array('id' => 3, 'anotherId' => 40, 'fString' => 'withtId_WhichPresent', 'fFloat' => 300.003), $row
        );
    }

    public function testUpdate_withtIdwhichAbsent_ButCreateIfAbsent_True()
    {
        $this->_initObject();
        $row = $this->object->update(
            array(
                'id' => 1000,
                'fFloat' => 1000.01,
                'fString' => 'withtIdwhichAbsent'
            ), true
        );
        $this->object->refresh();
        $item = $this->object->read(1000);
        $this->assertEquals(
            'withtIdwhichAbsent', $item['fString']
        );
        unset($row['anotherId']);
        $this->assertEquals(
            array(
                'id' => 1000,
                'fFloat' => 1000.01,
                'fString' => 'withtIdwhichAbsent',
            ), $row
        );
    }

    public function testDelete_withtId_WhichAbsent()
    {
        $this->_initObject();
        $item = $this->object->delete(1000);
        $this->object->refresh();
        $this->assertEquals(
            null, $item
        );
    }

    public function testDelete_withtId_WhichPresent()
    {
        $this->_initObject();
        $item = $this->object->delete(4);
        $this->object->refresh();
        $this->assertEquals($this->_itemsArrayDelault[3], $item);
        $this->assertNull(
            $this->object->read(4)
        );
    }

    public function testCount_count0()
    {
        $this->_initObject();
        $items = $this->object->deleteAll();
        $this->object->refresh();
        $this->assertEquals(
            0, $this->object->count()
        );
    }

    public function testCount2()
    {
        $this->_initObject();
        $count = $this->object->deleteAll();
        $this->object->refresh();
        $this->assertEquals(
            0, $this->object->count()
        );
    }

    public function zeroFirstDataProvider() {
        return [["010"]];//not need
    }
    /**
     * @param $fString
     * @dataProvider zeroFirstDataProvider
     */
    public function test_WithZeroString($fString)
    {
        $this->assertTrue(true);//cant create new item
    }

}
