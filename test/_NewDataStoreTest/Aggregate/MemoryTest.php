<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 12.04.18
 * Time: 2:25 PM
 */

namespace rollun\test\datastore\DataStore\Aggregate;


use rollun\datastore\DataStore\Memory;
use rollun\test\datastore\DataStore\OldStyleAggregateDecorator;

class MemoryTest extends AbstractAggregateTest
{
    use OldStyleAggregateDecorator;
    use AggregateSimpleDataProviderTrait;
    use AggregateMixedDataProviderTrait;

    static private $INITIAL_CONFIG = [];

    /**
     * Prepare datastore for initialized with transmitted data
     * @param array $data
     * @return void
     */
    protected function setInitialData(array $data)
    {
        $testCaseName = $this->getTestCaseName();
        static::$INITIAL_CONFIG[$testCaseName] = ["initialData" => $data];
    }

    /**
     * Prepare
     */
    public function setUp()
    {
        $name = $this->getName(false);
        $initialData = static::$INITIAL_CONFIG[$name]["initialData"];
        //create store
        $this->object = new Memory();

        //create data
        foreach ($initialData as $datum) {
            $this->object->create($datum);
        }
    }

    /**
     * Return dataStore Identifier field name
     * @return string
     */
    protected function getDataStoreIdentifier()
    {
        return Memory::DEF_ID;
    }
}