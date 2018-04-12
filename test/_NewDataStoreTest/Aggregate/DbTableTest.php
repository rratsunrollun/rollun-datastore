<?php


namespace rollun\test\datastore\DataStore\Aggregate;

use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\TableManagerMysql;
use rollun\test\datastore\DataStore\OldStyleAggregateDecorator;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

class DbTableTest extends AbstractAggregateTest
{
    use OldStyleAggregateDecorator;
    use AggregateSimpleDataProviderTrait;
    use AggregateMixedDataProviderTrait;

    const TEST_TABLE_NAME = "test_aggregate_table";

    const TEST_DB_ADAPTER = "TestDbAdapter";

    static private $INITIAL_CONFIG = [];

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * DbTableTest constructor.
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->adapter = $this->container->get(static::TEST_DB_ADAPTER);
    }

    /**
     * Prepare
     * @throws \ReflectionException
     */
    public function setUp()
    {
        $name = $this->getName(false);
        $dbTableConfig = static::$INITIAL_CONFIG[$name]["dbTableConfig"];
        $initialData = static::$INITIAL_CONFIG[$name]["initialData"];;

        $this->createTableFromConfig($dbTableConfig);

        //create store
        $tableGateway = new TableGateway(static::TEST_TABLE_NAME, $this->adapter);
        $this->object = new DbTable($tableGateway);

        //create data
        foreach ($initialData as $datum) {
            $this->object->create($datum);
        }
    }

    /**
     * Prepare datastore for initialized with transmitted data
     * @param array $data
     * @return void
     */
    protected function setInitialData(array $data)
    {
        $testCaseName = $this->getTestCaseName();
        static::$INITIAL_CONFIG[$testCaseName] = [
            "dbTableConfig" => [static::TEST_TABLE_NAME => $this->createTableConfig($data)],
            "initialData" => $data
        ];
    }

    /**
     * @return string
     */
    function getDataStoreIdentifier()
    {
        return DbTable::DEF_ID;
    }

    /**
     * @param array $data
     * @return array
     */
    private function createTableConfig(array $data)
    {
        $config = [];
        foreach ($data as $datum) {
            foreach ($datum as $filedName => $value) {
                if (!isset($config[$filedName])) {
                    $config[$filedName] = [];
                }
                switch (true) {
                    case is_null($value):
                        $config[$filedName][TableManagerMysql::FIELD_PARAMS] = array_merge(
                            isset($config[$filedName][TableManagerMysql::FIELD_PARAMS]) ? $config[$filedName][TableManagerMysql::FIELD_PARAMS] : [],
                            ["nullable" => true]
                        );
                        break;
                    case is_string($value):
                        $config[$filedName][TableManagerMysql::FIELD_TYPE] = "Varchar";
                        $length = strlen($value);
                        if (isset($config[$filedName][TableManagerMysql::FIELD_PARAMS]["length"])) {
                            $length = $length > $config[$filedName][TableManagerMysql::FIELD_PARAMS]["length"] ?
                                $length : $config[$filedName][TableManagerMysql::FIELD_PARAMS]["length"];
                        }
                        $config[$filedName][TableManagerMysql::FIELD_PARAMS] = array_merge(
                            isset($config[$filedName][TableManagerMysql::FIELD_PARAMS]) ? $config[$filedName][TableManagerMysql::FIELD_PARAMS] : [],
                            ["length" => $length]
                        );
                        break;
                    case is_float($value) && (!isset($config[$filedName][TableManagerMysql::FIELD_TYPE]) || $config[$filedName][TableManagerMysql::FIELD_TYPE] === "Integer"):
                        $config[$filedName][TableManagerMysql::FIELD_TYPE] = "Decimal";
                        $config[$filedName][TableManagerMysql::FIELD_PARAMS] =
                            array_merge(
                                isset($config[$filedName][TableManagerMysql::FIELD_PARAMS]) ? $config[$filedName][TableManagerMysql::FIELD_PARAMS] : [],
                                ["digits" => 11, "decimal" => 3]
                            );
                        break;
                    case is_integer($value) && !isset($config[$filedName][TableManagerMysql::FIELD_TYPE]):
                        $config[$filedName][TableManagerMysql::FIELD_TYPE] = "Integer";
                        break;
                }
            }
        }
        if(!isset($config[$this->getDataStoreIdentifier()])) {
            throw new \RuntimeException("Initial data is not valid. Identify field not found.");
        }
        $config[$this->getDataStoreIdentifier()][TableManagerMysql::PRIMARY_KEY] = true;
        return $config;
    }

    /**
     * @param array $config
     * @throws \ReflectionException
     */
    private function createTableFromConfig(array $config)
    {
        //create db
        $tableManager = new TableManagerMysql(
            $this->adapter,
            [TableManagerMysql::KEY_TABLES_CONFIGS => $config]
        );
        $tableManager->rewriteTable(static::TEST_TABLE_NAME);
    }
}