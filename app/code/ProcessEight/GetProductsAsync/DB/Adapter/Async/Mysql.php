<?php

namespace ProcessEight\GetProductsAsync\DB\Adapter\Async;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Async MySQL database adapter
 */
class Mysql implements AdapterInterface
{
    /**
     * Whether transaction was rolled back or not
     *
     * @var bool
     */
    private $isRolledBack = false;

    /**
     * @var \Magento\Framework\DB\LoggerInterface
     */
    private $logger;

    /**
     * Current Transaction Level
     *
     * @var int
     */
    private $transactionLevel = 0;

    /**
     * Constructor
     *
     * @param StringUtils $string
     * @param DateTime $dateTime
     * @param \Magento\Framework\DB\LoggerInterface $logger
     * @param SelectFactory $selectFactory
     * @param array $config
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
//        StringUtils $string,
//        DateTime $dateTime,
        \Magento\Framework\DB\LoggerInterface $logger,
//        SelectFactory $selectFactory,
        array $config = []
//        SerializerInterface $serializer = null
    ) {
//        $this->string = $string;
//        $this->dateTime = $dateTime;
        $this->logger = $logger;
//        $this->selectFactory = $selectFactory;
//        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
        $this->exceptionMap = [
            // SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
            2006 => ConnectionException::class,
            // SQLSTATE[HY000]: General error: 2013 Lost connection to MySQL server during query
            2013 => ConnectionException::class,
            // SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded
            1205 => LockWaitException::class,
            // SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock
            1213 => DeadlockException::class,
            // SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
            1062 => DuplicateException::class,
        ];
        try {
            parent::__construct($config);
        } catch (\Zend_Db_Adapter_Exception $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Begin new DB transaction for connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @throws \Exception
     */
    public function beginTransaction()
    {
        if ($this->isRolledBack) {
            throw new \Exception(AdapterInterface::ERROR_ROLLBACK_INCOMPLETE_MESSAGE);
        }
        if ($this->transactionLevel === 0) {
            $this->logger->startTimer();
            parent::beginTransaction();
            $this->logger->logStats(LoggerInterface::TYPE_TRANSACTION, 'BEGIN');
        }
        ++$this->transactionLevel;
        return $this;
    }

    /**
     * Commit DB transaction
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function commit()
    {
        return $this;
    }

    /**
     * Roll-back DB transaction
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function rollBack()
    {
        return $this;
    }

    /**
     * Retrieve DDL object for new table
     *
     * @param string $tableName  the table name
     * @param string $schemaName the database or schema name
     *
     * @return Table
     */
    public function newTable($tableName = null, $schemaName = null)
    {
        // TODO: Implement newTable() method.
    }

    /**
     * Create table from DDL object
     *
     * @param Table $table
     *
     * @throws \Zend_Db_Exception
     * @return \Zend_Db_Statement_Interface
     */
    public function createTable(Table $table)
    {
        // TODO: Implement createTable() method.
    }

    /**
     * Drop table from database
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return boolean
     */
    public function dropTable($tableName, $schemaName = null)
    {
        // TODO: Implement dropTable() method.
    }

    /**
     * Create temporary table from DDL object
     *
     * @param Table $table
     *
     * @throws \Zend_Db_Exception
     * @return \Zend_Db_Statement_Interface
     */
    public function createTemporaryTable(Table $table)
    {
        // TODO: Implement createTemporaryTable() method.
    }

    /**
     * Create temporary table from other table
     *
     * @param string $temporaryTableName
     * @param string $originTableName
     * @param bool   $ifNotExists
     *
     * @return \Zend_Db_Statement_Interface
     */
    public function createTemporaryTableLike($temporaryTableName, $originTableName, $ifNotExists = false)
    {
        // TODO: Implement createTemporaryTableLike() method.
    }

    /**
     * Drop temporary table from database
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return boolean
     */
    public function dropTemporaryTable($tableName, $schemaName = null)
    {
        // TODO: Implement dropTemporaryTable() method.
    }

    /**
     * Rename several tables
     *
     * @param array $tablePairs array('oldName' => 'Name1', 'newName' => 'Name2')
     *
     * @return boolean
     * @throws \Zend_Db_Exception
     */
    public function renameTablesBatch(array $tablePairs)
    {
        // TODO: Implement renameTablesBatch() method.
    }

    /**
     * Truncate a table
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function truncateTable($tableName, $schemaName = null)
    {
        return $this;
    }

    /**
     * Checks if table exists
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return boolean
     */
    public function isTableExists($tableName, $schemaName = null)
    {
        // TODO: Implement isTableExists() method.
    }

    /**
     * Returns short table status array
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return array|false
     */
    public function showTableStatus($tableName, $schemaName = null)
    {
        // TODO: Implement showTableStatus() method.
    }

    /**
     * Returns the column descriptions for a table.
     *
     * The return value is an associative array keyed by the column name,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * SCHEMA_NAME      => string; name of database or schema
     * TABLE_NAME       => string;
     * COLUMN_NAME      => string; column name
     * COLUMN_POSITION  => number; ordinal position of column in table
     * DATA_TYPE        => string; SQL datatype name of column
     * DEFAULT          => string; default expression of column, null if none
     * NULLABLE         => boolean; true if column can have nulls
     * LENGTH           => number; length of CHAR/VARCHAR
     * SCALE            => number; scale of NUMERIC/DECIMAL
     * PRECISION        => number; precision of NUMERIC/DECIMAL
     * UNSIGNED         => boolean; unsigned property of an integer type
     * PRIMARY          => boolean; true if column is part of the primary key
     * PRIMARY_POSITION => integer; position of column in primary key
     * IDENTITY         => integer; true if column is auto-generated with unique values
     *
     * @param string $tableName
     * @param string $schemaName OPTIONAL
     *
     * @return array
     */
    public function describeTable($tableName, $schemaName = null)
    {
        // TODO: Implement describeTable() method.
    }

    /**
     * Create \Magento\Framework\DB\Ddl\Table object by data from describe table
     *
     * @param string $tableName
     * @param string $newTableName
     *
     * @return Table
     */
    public function createTableByDdl($tableName, $newTableName)
    {
        // TODO: Implement createTableByDdl() method.
    }

    /**
     * Modify the column definition by data from describe table
     *
     * @param string       $tableName
     * @param string       $columnName
     * @param array|string $definition
     * @param boolean      $flushData
     * @param string       $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function modifyColumnByDdl($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        return $this;
    }

    /**
     * Rename table
     *
     * @param string $oldTableName
     * @param string $newTableName
     * @param string $schemaName
     *
     * @return boolean
     */
    public function renameTable($oldTableName, $newTableName, $schemaName = null)
    {
        // TODO: Implement renameTable() method.
    }

    /**
     * Adds new column to the table.
     *
     * Generally $defintion must be array with column data to keep this call cross-DB compatible.
     * Using string as $definition is allowed only for concrete DB adapter.
     *
     * @param string       $tableName
     * @param string       $columnName
     * @param array|string $definition string specific or universal array DB Server definition
     * @param string       $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function addColumn($tableName, $columnName, $definition, $schemaName = null)
    {
        return $this;
    }

    /**
     * Change the column name and definition
     *
     * For change definition of column - use modifyColumn
     *
     * @param string       $tableName
     * @param string       $oldColumnName
     * @param string       $newColumnName
     * @param array|string $definition
     * @param boolean      $flushData flush table statistic
     * @param string       $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function changeColumn(
        $tableName,
        $oldColumnName,
        $newColumnName,
        $definition,
        $flushData = false,
        $schemaName = null
    ) {
        return $this;
    }

    /**
     * Modify the column definition
     *
     * @param string       $tableName
     * @param string       $columnName
     * @param array|string $definition
     * @param boolean      $flushData
     * @param string       $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function modifyColumn($tableName, $columnName, $definition, $flushData = false, $schemaName = null)
    {
        return $this;
    }

    /**
     * Drop the column from table
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $schemaName
     *
     * @return boolean
     */
    public function dropColumn($tableName, $columnName, $schemaName = null)
    {
        // TODO: Implement dropColumn() method.
    }

    /**
     * Check is table column exists
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $schemaName
     *
     * @return boolean
     */
    public function tableColumnExists($tableName, $columnName, $schemaName = null)
    {
        // TODO: Implement tableColumnExists() method.
    }

    /**
     * Add new index to table name
     *
     * @param string       $tableName
     * @param string       $indexName
     * @param string|array $fields    the table column name or array of ones
     * @param string       $indexType the index type
     * @param string       $schemaName
     *
     * @return \Zend_Db_Statement_Interface
     */
    public function addIndex($tableName, $indexName, $fields, $indexType = self::INDEX_TYPE_INDEX, $schemaName = null)
    {
        // TODO: Implement addIndex() method.
    }

    /**
     * Drop the index from table
     *
     * @param string $tableName
     * @param string $keyName
     * @param string $schemaName
     *
     * @return bool|\Zend_Db_Statement_Interface
     */
    public function dropIndex($tableName, $keyName, $schemaName = null)
    {
        // TODO: Implement dropIndex() method.
    }

    /**
     * Returns the table index information
     *
     * The return value is an associative array keyed by the UPPERCASE index key (except for primary key,
     * that is always stored under 'PRIMARY' key) as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * SCHEMA_NAME      => string; name of database or schema
     * TABLE_NAME       => string; name of the table
     * KEY_NAME         => string; the original index name
     * COLUMNS_LIST     => array; array of index column names
     * INDEX_TYPE       => string; lowercase, create index type
     * INDEX_METHOD     => string; index method using
     * type             => string; see INDEX_TYPE
     * fields           => array; see COLUMNS_LIST
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return array
     */
    public function getIndexList($tableName, $schemaName = null)
    {
        // TODO: Implement getIndexList() method.
    }

    /**
     * Add new Foreign Key to table
     * If Foreign Key with same name is exist - it will be deleted
     *
     * @param string  $fkName
     * @param string  $tableName
     * @param string  $columnName
     * @param string  $refTableName
     * @param string  $refColumnName
     * @param string  $onDelete
     * @param string  $onUpdate
     * @param boolean $purge trying remove invalid data
     * @param string  $schemaName
     * @param string  $refSchemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function addForeignKey(
        $fkName,
        $tableName,
        $columnName,
        $refTableName,
        $refColumnName,
        $onDelete = self::FK_ACTION_CASCADE,
        $purge = false,
        $schemaName = null,
        $refSchemaName = null
    ) {
        return $this;
    }

    /**
     * Drop the Foreign Key from table
     *
     * @param string $tableName
     * @param string $fkName
     * @param string $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function dropForeignKey($tableName, $fkName, $schemaName = null)
    {
        return $this;
    }

    /**
     * Retrieve the foreign keys descriptions for a table.
     *
     * The return value is an associative array keyed by the UPPERCASE foreign key,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * FK_NAME          => string; original foreign key name
     * SCHEMA_NAME      => string; name of database or schema
     * TABLE_NAME       => string;
     * COLUMN_NAME      => string; column name
     * REF_SCHEMA_NAME  => string; name of reference database or schema
     * REF_TABLE_NAME   => string; reference table name
     * REF_COLUMN_NAME  => string; reference column name
     * ON_DELETE        => string; action type on delete row
     * ON_UPDATE        => string; action type on update row
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return array
     */
    public function getForeignKeys($tableName, $schemaName = null)
    {
        // TODO: Implement getForeignKeys() method.
    }

    /**
     * Creates and returns a new \Magento\Framework\DB\Select object for this adapter.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function select()
    {
        // TODO: Implement select() method.
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param mixed $table  The table to insert data into.
     * @param array $data   Column-value pairs or array of column-value pairs.
     * @param array $fields update fields pairs or values
     *
     * @return int The number of affected rows.
     */
    public function insertOnDuplicate($table, array $data, array $fields = [])
    {
        $inserted = 0;

        return $inserted;
    }

    /**
     * Inserts a table multiply rows with specified data.
     *
     * @param mixed $table The table to insert data into.
     * @param array $data  Column-value pairs or array of Column-value pairs.
     *
     * @return int The number of affected rows.
     */
    public function insertMultiple($table, array $data)
    {
        $inserted = 0;

        return $inserted;
    }

    /**
     * Insert array into a table based on columns definition
     *
     * $data can be represented as:
     * - arrays of values ordered according to columns in $columns array
     *      array(
     *          array('value1', 'value2'),
     *          array('value3', 'value4'),
     *      )
     * - array of values, if $columns contains only one column
     *      array('value1', 'value2')
     *
     * @param   string   $table
     * @param   string[] $columns the data array column map
     * @param   array    $data
     *
     * @return  int
     */
    public function insertArray($table, array $columns, array $data)
    {
        $inserted = 0;

        return $inserted;
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param mixed $table The table to insert data into.
     * @param array $bind  Column-value pairs.
     *
     * @return int The number of affected rows.
     */
    public function insert($table, array $bind)
    {
        $inserted = 0;

        return $inserted;
    }

    /**
     * Inserts a table row with specified data
     * Special for Zero values to identity column
     *
     * @param string $table
     * @param array  $bind
     *
     * @return int The number of affected rows.
     */
    public function insertForce($table, array $bind)
    {
        $inserted = 0;

        return $inserted;
    }

    /**
     * Updates table rows with specified data based on a WHERE clause.
     *
     * The $where parameter in this instance can be a single WHERE clause or an array containing a multiple.  In all
     * instances, a WHERE clause can be a string or an instance of {@see Zend_Db_Expr}.  In the event you use an array,
     * you may specify the clause as the key and a value to be bound to it as the value. E.g., ['amt > ?' => $amt]
     *
     * If the $where parameter is an array of multiple clauses, they will be joined by AND, with each clause wrapped in
     * parenthesis.  If you wish to use an OR, you must give a single clause that is an instance of {@see Zend_Db_Expr}
     *
     * @param  mixed $table The table to update.
     * @param  array $bind  Column-value pairs.
     * @param  mixed $where UPDATE WHERE clause(s).
     *
     * @return int          The number of affected rows.
     */
    public function update($table, array $bind, $where = '')
    {
        $updated = 0;

        return $updated;
    }

    /**
     * Deletes table rows based on a WHERE clause.
     *
     * @param  mixed $table The table to update.
     * @param  mixed $where DELETE WHERE clause(s).
     *
     * @return int          The number of affected rows.
     */
    public function delete($table, $where = '')
    {
        $deleted = 0;

        return $deleted;
    }

    /**
     * Prepares and executes an SQL statement with bound data.
     *
     * @param  mixed $sql   The SQL statement with placeholders.
     *                      May be a string or \Magento\Framework\DB\Select.
     * @param  mixed $bind  An array of data or data itself to bind to the placeholders.
     *
     * @return \Zend_Db_Statement_Interface
     */
    public function query($sql, $bind = [])
    {
        // TODO: Implement query() method.
    }

    /**
     * Fetches all SQL result rows as a sequential array.
     * Uses the current fetchMode for the adapter.
     *
     * @param string|\Magento\Framework\DB\Select $sql       An SQL SELECT statement.
     * @param mixed                               $bind      Data to bind into SELECT placeholders.
     * @param mixed                               $fetchMode Override current fetch mode.
     *
     * @return array
     */
    public function fetchAll($sql, $bind = [], $fetchMode = null)
    {
        $rows = [];

        return $rows;
    }

    /**
     * Fetches the first row of the SQL result.
     * Uses the current fetchMode for the adapter.
     *
     * @param string|\Magento\Framework\DB\Select $sql       An SQL SELECT statement.
     * @param mixed                               $bind      Data to bind into SELECT placeholders.
     * @param mixed                               $fetchMode Override current fetch mode.
     *
     * @return array
     */
    public function fetchRow($sql, $bind = [], $fetchMode = null)
    {
        $row = [];

        return $row;
    }

    /**
     * Fetches all SQL result rows as an associative array.
     *
     * The first column is the key, the entire row array is the
     * value.  You should construct the query to be sure that
     * the first column contains unique values, or else
     * rows with duplicate values in the first column will
     * overwrite previous data.
     *
     * @param string|\Magento\Framework\DB\Select $sql  An SQL SELECT statement.
     * @param mixed                               $bind Data to bind into SELECT placeholders.
     *
     * @return array
     */
    public function fetchAssoc($sql, $bind = [])
    {
        $rows = [];

        return $rows;
    }

    /**
     * Fetches the first column of all SQL result rows as an array.
     *
     * The first column in each row is used as the array key.
     *
     * @param string|\Magento\Framework\DB\Select $sql  An SQL SELECT statement.
     * @param mixed                               $bind Data to bind into SELECT placeholders.
     *
     * @return array
     */
    public function fetchCol($sql, $bind = [])
    {
        $rows = [];

        return $rows;
    }

    /**
     * Fetches all SQL result rows as an array of key-value pairs.
     *
     * The first column is the key, the second column is the
     * value.
     *
     * @param string|\Magento\Framework\DB\Select $sql  An SQL SELECT statement.
     * @param mixed                               $bind Data to bind into SELECT placeholders.
     *
     * @return array
     */
    public function fetchPairs($sql, $bind = [])
    {
        $rows = [];

        return $rows;
    }

    /**
     * Fetches the first column of the first row of the SQL result.
     *
     * @param string|\Magento\Framework\DB\Select $sql  An SQL SELECT statement.
     * @param mixed                               $bind Data to bind into SELECT placeholders.
     *
     * @return string
     */
    public function fetchOne($sql, $bind = [])
    {
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @param mixed $value The value to quote.
     * @param mixed $type  OPTIONAL the SQL datatype name, or constant, or null.
     *
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
        // TODO: Implement quote() method.
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param mixed   $value The value to quote.
     * @param string  $type  OPTIONAL SQL datatype
     * @param integer $count OPTIONAL count of placeholders to replace
     *
     * @return string An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        // TODO: Implement quoteInto() method.
    }

    /**
     * Quotes an identifier.
     *
     * Accepts a string representing a qualified indentifier. For Example:
     * <code>
     * $adapter->quoteIdentifier('myschema.mytable')
     * </code>
     * Returns: "myschema"."mytable"
     *
     * Or, an array of one or more identifiers that may form a qualified identifier:
     * <code>
     * $adapter->quoteIdentifier(array('myschema','my.table'))
     * </code>
     * Returns: "myschema"."my.table"
     *
     * The actual quote character surrounding the identifiers may vary depending on
     * the adapter.
     *
     * @param string|array|\Zend_Db_Expr $ident The identifier.
     * @param boolean                    $auto  If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     *
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($ident, $auto = false)
    {
        // TODO: Implement quoteIdentifier() method.
    }

    /**
     * Quote a column identifier and alias.
     *
     * @param string|array|\Zend_Db_Expr $ident The identifier or expression.
     * @param string                     $alias An alias for the column.
     * @param boolean                    $auto  If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     *
     * @return string The quoted identifier and alias.
     */
    public function quoteColumnAs($ident, $alias, $auto = false)
    {
        // TODO: Implement quoteColumnAs() method.
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array|\Zend_Db_Expr $ident The identifier or expression.
     * @param string                     $alias An alias for the table.
     * @param boolean                    $auto  If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     *
     * @return string The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias = null, $auto = false)
    {
        // TODO: Implement quoteTableAs() method.
    }

    /**
     * Format Date to internal database date format
     *
     * @param int|string|\DateTimeInterface $date
     * @param boolean                       $includeTime
     *
     * @return \Zend_Db_Expr
     */
    public function formatDate($date, $includeTime = true)
    {
        // TODO: Implement formatDate() method.
    }

    /**
     * Run additional environment before setup
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function startSetup()
    {
        return $this;
    }

    /**
     * Run additional environment after setup
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function endSetup()
    {
        return $this;
    }

    /**
     * Set cache adapter
     *
     * @param \Magento\Framework\Cache\FrontendInterface $cacheAdapter
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function setCacheAdapter(\Magento\Framework\Cache\FrontendInterface $cacheAdapter)
    {
        return $this;
    }

    /**
     * Allow DDL caching
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function allowDdlCache()
    {
        return $this;
    }

    /**
     * Disallow DDL caching
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function disallowDdlCache()
    {
        return $this;
    }

    /**
     * Reset cached DDL data from cache
     * if table name is null - reset all cached DDL data
     *
     * @param string $tableName
     * @param string $schemaName OPTIONAL
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function resetDdlCache($tableName = null, $schemaName = null)
    {
        return $this;
    }

    /**
     * Save DDL data into cache
     *
     * @param string $tableCacheKey
     * @param int    $ddlType
     * @param mixed  $data
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function saveDdlCache($tableCacheKey, $ddlType, $data)
    {
        return $this;
    }

    /**
     * Load DDL data from cache
     * Return false if cache does not exists
     *
     * @param string $tableCacheKey the table cache key
     * @param int    $ddlType       the DDL constant
     *
     * @return string|array|int|false
     */
    public function loadDdlCache($tableCacheKey, $ddlType)
    {
        // TODO: Implement loadDdlCache() method.
    }

    /**
     * Build SQL statement for condition
     *
     * If $condition integer or string - exact value will be filtered ('eq' condition)
     *
     * If $condition is array - one of the following structures is expected:
     * - array("from" => $fromValue, "to" => $toValue)
     * - array("eq" => $equalValue)
     * - array("neq" => $notEqualValue)
     * - array("like" => $likeValue)
     * - array("in" => array($inValues))
     * - array("nin" => array($notInValues))
     * - array("notnull" => $valueIsNotNull)
     * - array("null" => $valueIsNull)
     * - array("moreq" => $moreOrEqualValue)
     * - array("gt" => $greaterValue)
     * - array("lt" => $lessValue)
     * - array("gteq" => $greaterOrEqualValue)
     * - array("lteq" => $lessOrEqualValue)
     * - array("finset" => $valueInSet)
     * - array("regexp" => $regularExpression)
     * - array("seq" => $stringValue)
     * - array("sneq" => $stringValue)
     *
     * If non matched - sequential array is expected and OR conditions
     * will be built using above mentioned structure
     *
     * @param string               $fieldName
     * @param integer|string|array $condition
     *
     * @return string
     */
    public function prepareSqlCondition($fieldName, $condition)
    {
        // TODO: Implement prepareSqlCondition() method.
    }

    /**
     * Prepare value for save in column
     * Return converted to column data type value
     *
     * @param array $column the column describe array
     * @param mixed $value
     *
     * @return mixed
     */
    public function prepareColumnValue(array $column, $value)
    {
        // TODO: Implement prepareColumnValue() method.
    }

    /**
     * Generate fragment of SQL, that check condition and return true or false value
     *
     * @param string $condition expression
     * @param string $true      true value
     * @param string $false     false value
     *
     * @return \Zend_Db_Expr
     */
    public function getCheckSql($condition, $true, $false)
    {
        // TODO: Implement getCheckSql() method.
    }

    /**
     * Returns valid IFNULL expression
     *
     * @param string     $expression
     * @param string|int $value OPTIONAL. Applies when $expression is NULL
     *
     * @return \Zend_Db_Expr
     */
    public function getIfNullSql($expression, $value = 0)
    {
        // TODO: Implement getIfNullSql() method.
    }

    /**
     * Generate fragment of SQL, that combine together (concatenate) the results from data array
     * All arguments in data must be quoted
     *
     * @param array  $data
     * @param string $separator concatenate with separator
     *
     * @return \Zend_Db_Expr
     */
    public function getConcatSql(array $data, $separator = null)
    {
        // TODO: Implement getConcatSql() method.
    }

    /**
     * Generate fragment of SQL that returns length of character string
     * The string argument must be quoted
     *
     * @param string $string
     *
     * @return \Zend_Db_Expr
     */
    public function getLengthSql($string)
    {
        // TODO: Implement getLengthSql() method.
    }

    /**
     * Generate fragment of SQL, that compare with two or more arguments, and returns the smallest
     * (minimum-valued) argument
     * All arguments in data must be quoted
     *
     * @param array $data
     *
     * @return \Zend_Db_Expr
     */
    public function getLeastSql(array $data)
    {
        // TODO: Implement getLeastSql() method.
    }

    /**
     * Generate fragment of SQL, that compare with two or more arguments, and returns the largest
     * (maximum-valued) argument
     * All arguments in data must be quoted
     *
     * @param array $data
     *
     * @return \Zend_Db_Expr
     */
    public function getGreatestSql(array $data)
    {
        // TODO: Implement getGreatestSql() method.
    }

    /**
     * Add time values (intervals) to a date value
     *
     * @see INTERVAL_* constants for $unit
     *
     * @param \Zend_Db_Expr|string $date quoted field name or SQL statement
     * @param int                  $interval
     * @param string               $unit
     *
     * @return \Zend_Db_Expr
     */
    public function getDateAddSql($date, $interval, $unit)
    {
        // TODO: Implement getDateAddSql() method.
    }

    /**
     * Subtract time values (intervals) to a date value
     *
     * @see INTERVAL_* constants for $unit
     *
     * @param \Zend_Db_Expr|string $date quoted field name or SQL statement
     * @param int|string           $interval
     * @param string               $unit
     *
     * @return \Zend_Db_Expr
     */
    public function getDateSubSql($date, $interval, $unit)
    {
        // TODO: Implement getDateSubSql() method.
    }

    /**
     * Format date as specified
     *
     * Supported format Specifier
     *
     * %H   Hour (00..23)
     * %i   Minutes, numeric (00..59)
     * %s   Seconds (00..59)
     * %d   Day of the month, numeric (00..31)
     * %m   Month, numeric (00..12)
     * %Y   Year, numeric, four digits
     *
     * @param \Zend_Db_Expr|string $date quoted field name or SQL statement
     * @param string               $format
     *
     * @return \Zend_Db_Expr
     */
    public function getDateFormatSql($date, $format)
    {
        // TODO: Implement getDateFormatSql() method.
    }

    /**
     * Extract the date part of a date or datetime expression
     *
     * @param \Zend_Db_Expr|string $date quoted field name or SQL statement
     *
     * @return \Zend_Db_Expr
     */
    public function getDatePartSql($date)
    {
        // TODO: Implement getDatePartSql() method.
    }

    /**
     * Prepare substring sql function
     *
     * @param \Zend_Db_Expr|string          $stringExpression quoted field name or SQL statement
     * @param int|string|\Zend_Db_Expr      $pos
     * @param int|string|\Zend_Db_Expr|null $len
     *
     * @return \Zend_Db_Expr
     */
    public function getSubstringSql($stringExpression, $pos, $len = null)
    {
        // TODO: Implement getSubstringSql() method.
    }

    /**
     * Prepare standard deviation sql function
     *
     * @param \Zend_Db_Expr|string $expressionField quoted field name or SQL statement
     *
     * @return \Zend_Db_Expr
     */
    public function getStandardDeviationSql($expressionField)
    {
        // TODO: Implement getStandardDeviationSql() method.
    }

    /**
     * Extract part of a date
     *
     * @see INTERVAL_* constants for $unit
     *
     * @param \Zend_Db_Expr|string $date quoted field name or SQL statement
     * @param string               $unit
     *
     * @return \Zend_Db_Expr
     */
    public function getDateExtractSql($date, $unit)
    {
        // TODO: Implement getDateExtractSql() method.
    }

    /**
     * Retrieve valid table name
     * Check table name length and allowed symbols
     *
     * @param string $tableName
     *
     * @return string
     */
    public function getTableName($tableName)
    {
        // TODO: Implement getTableName() method.
    }

    /**
     * Build a trigger name based on table name and trigger details
     *
     * @param string $tableName The table that is the subject of the trigger
     * @param string $time      Either "before" or "after"
     * @param string $event     The DB level event which activates the trigger, i.e. "update" or "insert"
     *
     * @return string
     */
    public function getTriggerName($tableName, $time, $event)
    {
        // TODO: Implement getTriggerName() method.
    }

    /**
     * Retrieve valid index name
     * Check index name length and allowed symbols
     *
     * @param string       $tableName
     * @param string|array $fields the columns list
     * @param string       $indexType
     *
     * @return string
     */
    public function getIndexName($tableName, $fields, $indexType = '')
    {
        // TODO: Implement getIndexName() method.
    }

    /**
     * Retrieve valid foreign key name
     * Check foreign key name length and allowed symbols
     *
     * @param string $priTableName
     * @param string $priColumnName
     * @param string $refTableName
     * @param string $refColumnName
     *
     * @return string
     */
    public function getForeignKeyName($priTableName, $priColumnName, $refTableName, $refColumnName)
    {
        // TODO: Implement getForeignKeyName() method.
    }

    /**
     * Stop updating indexes
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function disableTableKeys($tableName, $schemaName = null)
    {
        return $this;
    }

    /**
     * Re-create missing indexes
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function enableTableKeys($tableName, $schemaName = null)
    {
        return $this;
    }

    /**
     * Get insert from Select object query
     *
     * @param \Magento\Framework\DB\Select $select
     * @param string                       $table insert into table
     * @param array                        $fields
     * @param int|bool                     $mode
     *
     * @return string
     */
    public function insertFromSelect(\Magento\Framework\DB\Select $select, $table, array $fields = [], $mode = false)
    {
        // TODO: Implement insertFromSelect() method.
    }

    /**
     * Get insert queries in array for insert by range with step parameter
     *
     * @param string                       $rangeField
     * @param \Magento\Framework\DB\Select $select
     * @param int                          $stepCount
     *
     * @return \Magento\Framework\DB\Select[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function selectsByRange($rangeField, \Magento\Framework\DB\Select $select, $stepCount = 100)
    {
        // TODO: Implement selectsByRange() method.
    }

    /**
     * Get update table query using select object for join and update
     *
     * @param \Magento\Framework\DB\Select $select
     * @param string|array                 $table
     *
     * @return string
     */
    public function updateFromSelect(\Magento\Framework\DB\Select $select, $table)
    {
        // TODO: Implement updateFromSelect() method.
    }

    /**
     * Get delete from select object query
     *
     * @param \Magento\Framework\DB\Select $select
     * @param string                       $table the table name or alias used in select
     *
     * @return string|int
     */
    public function deleteFromSelect(\Magento\Framework\DB\Select $select, $table)
    {
        // TODO: Implement deleteFromSelect() method.
    }

    /**
     * Return array of table(s) checksum as table name - checksum pairs
     *
     * @param array|string $tableNames
     * @param string       $schemaName
     *
     * @return array
     */
    public function getTablesChecksum($tableNames, $schemaName = null)
    {
        // TODO: Implement getTablesChecksum() method.
    }

    /**
     * Check if the database support STRAIGHT JOIN
     *
     * @return boolean
     */
    public function supportStraightJoin()
    {
        // TODO: Implement supportStraightJoin() method.
    }

    /**
     * Adds order by random to select object
     * Possible using integer field for optimization
     *
     * @param \Magento\Framework\DB\Select $select
     * @param string                       $field
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function orderRand(\Magento\Framework\DB\Select $select, $field = null)
    {
        return $this;
    }

    /**
     * Render SQL FOR UPDATE clause
     *
     * @param string $sql
     *
     * @return string
     */
    public function forUpdate($sql)
    {
        // TODO: Implement forUpdate() method.
    }

    /**
     * Try to find installed primary key name, if not - formate new one.
     *
     * @param string $tableName  Table name
     * @param string $schemaName OPTIONAL
     *
     * @return string Primary Key name
     */
    public function getPrimaryKeyName($tableName, $schemaName = null)
    {
        // TODO: Implement getPrimaryKeyName() method.
    }

    /**
     * Converts fetched blob into raw binary PHP data.
     * Some DB drivers return blobs as hex-coded strings, so we need to process them.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function decodeVarbinary($value)
    {
        // TODO: Implement decodeVarbinary() method.
    }

    /**
     * Get adapter transaction level state. Return 0 if all transactions are complete
     *
     * @return int
     */
    public function getTransactionLevel()
    {
        // TODO: Implement getTransactionLevel() method.
        $transactionLevel = 0;

        return $transactionLevel;
    }

    /**
     * Create trigger
     *
     * @param \Magento\Framework\DB\Ddl\Trigger $trigger
     *
     * @return \Zend_Db_Statement_Pdo
     */
    public function createTrigger(\Magento\Framework\DB\Ddl\Trigger $trigger)
    {
        // TODO: Implement createTrigger() method.
    }

    /**
     * Drop trigger from database
     *
     * @param string      $triggerName
     * @param string|null $schemaName
     *
     * @return bool
     */
    public function dropTrigger($triggerName, $schemaName = null)
    {
        // TODO: Implement dropTrigger() method.
    }

    /**
     * Retrieve tables list
     *
     * @param null|string $likeCondition
     *
     * @return array
     */
    public function getTables($likeCondition = null)
    {
        // TODO: Implement getTables() method.
        $tablesList = [];

        return $tablesList;
    }

    /**
     * Generate fragment of SQL, that check value against multiple condition cases
     * and return different result depends on them
     *
     * @param string $valueName    Name of value to check
     * @param array  $casesResults Cases and results
     * @param string $defaultValue value to use if value doesn't confirm to any cases
     *
     * @return \Zend_Db_Expr
     */
    public function getCaseSql($valueName, $casesResults, $defaultValue = null)
    {
        // TODO: Implement getCaseSql() method.
    }

    /**
     * Returns auto increment field if exists
     *
     * @param string      $tableName
     * @param string|null $schemaName
     *
     * @return string|bool
     * @since 100.1.0
     */
    public function getAutoIncrementField($tableName, $schemaName = null)
    {
        // TODO: Implement getAutoIncrementField() method.
    }
}
