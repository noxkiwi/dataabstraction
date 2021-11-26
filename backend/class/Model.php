<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction;

use DateTime;
use Exception;
use JetBrains\PhpStorm\Pure;
use noxkiwi\cache\Cache;
use noxkiwi\core\Config;
use noxkiwi\core\Config\JsonConfig;
use noxkiwi\core\ErrorHandler;
use noxkiwi\core\ErrorStack;
use noxkiwi\core\Exception\InvalidArgumentException;
use noxkiwi\core\Exception\InvalidJsonException;
use noxkiwi\core\Helper\JsonHelper;
use noxkiwi\core\Traits\LanguageImprovementTrait;
use noxkiwi\dataabstraction\Exception\ModelException;
use noxkiwi\dataabstraction\Interfaces\ModelInterface;
use noxkiwi\dataabstraction\Model\Plugin\Field;
use noxkiwi\dataabstraction\Model\Plugin\Filter;
use noxkiwi\dataabstraction\Model\Plugin\Limit;
use noxkiwi\dataabstraction\Model\Plugin\Offset;
use noxkiwi\dataabstraction\Model\Plugin\Order;
use noxkiwi\dataabstraction\Validator\Structure\Config\ModelValidator;
use noxkiwi\database\Database;
use noxkiwi\database\Observer\DatabaseObserver;
use noxkiwi\database\Query;
use noxkiwi\singleton\Singleton;
use noxkiwi\validator\Validator;
use function array_keys;
use function compact;
use function count;
use function end;
use function explode;
use function hash;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function min;
use function serialize;
use function str_contains;
use function str_replace;
use function strtolower;
use function strtoupper;
use function trim;
use const E_ERROR;
use const E_USER_NOTICE;

/**
 * I am the basic Model.
 *
 * @package      noxkiwi\dataabstraction
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
abstract class Model extends Singleton implements ModelInterface
{
    use LanguageImprovementTrait;

    private const   CONFIG_CACHE         = Cache::DEFAULT_PREFIX . 'MODELCONFIG';
    public const    CACHE_DATA           = Cache::DEFAULT_PREFIX . 'MODELDATA_';
    public const    TABLE                = '';
    public const    SCHEMA               = 'public';
    public const    CONST_MAX_LIMIT      = 10;
    public const    FIELDSUFFIX_CREATED  = '_created';
    public const    FIELDSUFFIX_MODIFIED = '_modified';
    public const    FIELDSUFFIX_ID       = '_id';
    public const    DB_TYPE              = 'null';
    /** @var \noxkiwi\dataabstraction\Entry[] */
    private static array  $entries;
    private static string $cacheGroup;
    /** @var string I am the filterOperator */
    public string $filterOperator;
    /** @var bool Set to true if the query shall be cached after finishing. */
    protected bool $cache;
    /** @var array|null $result I am the result of the query that was executed. */
    protected ?array $result;
    /** @var \noxkiwi\dataabstraction\Model\Plugin\Field[] */
    protected array $fields;
    /** @var string[] */
    protected array $hiddenFields;
    /** @var \noxkiwi\dataabstraction\Model\Plugin\Limit|null I'll limit the output of the search result. */
    protected ?Limit $limit;
    /** @var \noxkiwi\dataabstraction\Model\Plugin\Offset|null I'll offset the output of the search result. */
    protected ?Offset $offset;
    /** @var string I am the delimiter string. */
    protected string $delimiter;
    /** @var \noxkiwi\core\Errorstack I am a Stack of errors. */
    protected ErrorStack $errorStack;
    /** @var \noxkiwi\dataabstraction\Model[] */
    protected array $models;
    /** @var \noxkiwi\core\Config I am the Model's setup. */
    protected Config $config;
    /** @var \noxkiwi\dataabstraction\Model[] */
    protected array $siblings;
    /** @var \noxkiwi\dataabstraction\Model\Plugin\Order[] */
    private array $order;
    /** @var \noxkiwi\dataabstraction\Model\Plugin\Filter[] */
    private array $filters;
    /** @var string I am the model Name. */
    private string $modelName;
    /** @var array I am the list of Flag filters. */
    private array $flagFilters;
    /** @var \noxkiwi\cache\Cache I am the Cache object. */
    private Cache $cacheInstance;
    /** @var string[] I am the array of available fields. I only exist for performance improvements. */
    private array $fieldNames;
    /** @var \noxkiwi\dataabstraction\FieldDefinition[] */
    private array $fieldDefinitions;
    /** @var string I am the primary key name. I'm here for performance improvements. */
    private string $primaryKey;

    /**
     * Creates the Model instance
     * @throws \noxkiwi\core\Exception
     * @throws \noxkiwi\dataabstraction\Exception\ModelException
     */
    final protected function __construct()
    {
        parent::__construct();
        $this->reset();
        $this->makeConfig();
    }

    /**
     * Creates and configures the instance of the Model. Fallback connection is 'default' Database
     *
     * @throws \noxkiwi\dataabstraction\Exception\ModelException
     */
    private function makeConfig(): void
    {
        $cacheKey = strtoupper(str_replace('\\', '_', static::class));
        $config   = $this->cacheInstance->get(self::CONFIG_CACHE, $cacheKey);
        if (is_array($config)) {
            $this->config = new Config($config);

            return;
        }
        $configFile = 'config/model/' . static::SCHEMA . '_' . static::TABLE . '.json';
        try {
            $this->config = new JsonConfig($configFile);
            $this->cacheInstance->set(self::CONFIG_CACHE, $cacheKey, $this->config->get());
            $errors = ModelValidator::getInstance()->validate($this->config->get());
        } catch (Exception $exception) {
            throw new ModelException($exception->getMessage(), E_ERROR, compact('configFile'));
        }
        if (! empty($errors)) {
            throw new ModelException('EXCEPTION_SETCONFIG_INVALIDMODELCONFIG', E_ERROR, $errors);
        }
    }

    /**
     * I will return an array of entries representing the result of the query.
     * @return \noxkiwi\dataabstraction\Entry[]
     */
    final public function getEntries(): array
    {
        $return  = [];
        $results = $this->getResult();
        foreach ($results as $result) {
            $currentEntry = $this->getEntry();
            try {
                $currentEntry->set($result);
            } catch (InvalidArgumentException) {
                continue;
            }
            $return[] = $currentEntry;
        }

        return $return;
    }

    /**
     * @inheritDoc
     */
    final public function getResult(): array
    {
        $result = $this->result;
        if ($result === null) {
            try {
                $this->result = $this->getDbConnection()->getResult();
            } catch (Exception $exception) {
                ErrorHandler::handleException($exception);
                $this->result = [];
            }
        }

        return $this->normalizeResult($this->result);
    }

    /**
     * I will return an instance of a database connection if the connection could be established.
     *
     * @throws \noxkiwi\core\Exception
     * @return \noxkiwi\database\Database
     */
    final protected function getDbConnection(): Database
    {
        return Database::getInstance($this->getConnectionName());
    }

    /**
     * I will return the name of the Database connection that will be used.
     * @return string
     */
    protected function getConnectionName(): string
    {
        return $this->config->get('connection', Singleton::IDENTIFIER);
    }

    /**
     * Normalizes a result. Nests normalizeRow when more than one single row is in the result.
     *
     * @param array $result
     *
     * @return       array
     */
    final protected function normalizeResult(array $result): array
    {
        if (empty($result)) {
            return [];
        }
        if (count($result) === 1) {
            $result = $result[0];

            return [$this->normalizeRow($result)];
        }
        foreach ($result as $key => $value) {
            $result[$key] = $this->normalizeRow($value);
        }

        return $result;
    }

    /**
     * Normalizes a single row of a dataset
     *
     * @param array $dataset
     *
     * @return       array
     */
    final protected function normalizeRow(array $dataset): array
    {
        if (count($dataset) === 1 && isset($dataset[0])) {
            $dataset = $dataset[0];
        }
        foreach ($dataset as $fieldName => $fieldValue) {
            $fieldType           = $this->getFieldType($fieldName);
            $dataset[$fieldName] ??= null;
            switch ($fieldType) {
                case Type::STRUCTURE:
                    if (! is_string($fieldValue)) {
                        break;
                    }
                    try {
                        $dataset[$fieldName] = JsonHelper::decodeStringToArray($fieldValue);
                    } catch (InvalidJsonException) {
                        $dataset[$fieldName] = null;
                    }
                    break;
                case Type::NUMBER:
                    $dataset[$fieldName] = $fieldValue === null ? null : (float)$fieldValue;
                    break;
                case Type::NUMBER_NATURAL:
                    $dataset[$fieldName] = $fieldValue === null ? null : (int)$fieldValue;
                    break;
                default:
                    break;
            }
        }

        return $dataset;
    }

    /**
     * I will return the Field type of the given $fieldName.
     *
     * @param string $fieldName
     *
     * @return string
     */
    final public function getFieldType(string $fieldName): string
    {
        try {
            return $this->getDefinition($fieldName)->type;
        } catch (Exception) {
            return '';
        }
    }

    /**
     * I will return the FieldDefinition object for the given $fieldName.
     * If the field was not found, I will throw InvalidArgumentException.
     *
     * @param string $fieldName
     *
     * @throws \noxkiwi\core\Exception\InvalidArgumentException
     * @return \noxkiwi\dataabstraction\FieldDefinition
     */
    final public function getDefinition(string $fieldName): FieldDefinition
    {
        if (! isset($this->fieldDefinitions[$fieldName])) {
            if (! $this->fieldExists($fieldName)) {
                throw new InvalidArgumentException("Field $fieldName was not found!", E_ERROR);
            }
            $fieldDefinition                   = new FieldDefinition();
            $fieldDefinition->name             = $fieldName;
            $fieldDefinition->displayName      = (string)$this->getConfig()->get("fields>$fieldName>displayName");
            $fieldDefinition->type             = (string)$this->getConfig()->get("fields>$fieldName>type");
            $fieldDefinition->displayType      = (string)$this->getConfig()->get("fields>$fieldName>displayType", $fieldDefinition->type);
            $fieldDefinition->required         = (bool)$this->getConfig()->get("fields>$fieldName>required");
            $fieldDefinition->min              = (int)$this->getConfig()->get("fields>$fieldName>min");
            $fieldDefinition->max              = (int)$this->getConfig()->get("fields>$fieldName>max");
            $fieldDefinition->unique           = (bool)$this->getConfig()->get("fields>$fieldName>unique");
            $fieldDefinition->foreign          = (array)$this->getConfig()->get("fields>$fieldName>foreign");
            $fieldDefinition->enum             = (string)$this->getConfig()->get("fields>$fieldName>enum");
            $fieldDefinition->readonly         = (bool)$this->getConfig()->get("fields>$fieldName>readonly");
            $fieldDefinition->validatorOptions = [];
            if ($fieldName === $this->getPrimarykey()) {
                $fieldDefinition->readonly = true;
            }
            $this->fieldDefinitions[$fieldName] = $fieldDefinition;
        }

        return $this->fieldDefinitions[$fieldName];
    }

    /**
     * Returns true if the given $Field exists in this model's configuration
     *
     * @param string $fieldName
     *
     * @return       bool
     */
    public function fieldExists(string $fieldName): bool
    {
        return in_array($fieldName, $this->getFieldNames(), false);
    }

    /**
     * I will return the list of FieldNames available on the model.
     * I will also check for existence of a previously loaded list of field names for performance reaseons.
     * @return string[]
     */
    final public function getFieldNames(): array
    {
        if (empty($this->fieldNames)) {
            $this->fieldNames = array_keys($this->config->get('fields', []));
        }

        return $this->fieldNames;
    }

    /**
     * @inheritDoc
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    final public function getPrimarykey(): string
    {
        if (empty($this->primaryKey)) {
            $this->primaryKey = (string)$this->config->get('primary')[0];
        }

        return $this->primaryKey;
    }

    final public function getEntry(array $entryData = []): Entry
    {
        return new Entry($this, $entryData);
    }

    /**
     * @return \noxkiwi\dataabstraction\FieldDefinition[]
     */
    final public function getDefinitions(): array
    {
        $definitions = [];
        foreach ($this->getFieldNames() as $fieldName) {
            try {
                $definitions[$fieldName] = $this->getDefinition($fieldName);
            } catch (InvalidArgumentException $exception) {
                ErrorHandler::handleException($exception);
            }
        }

        return $definitions;
    }

    /**
     * @inheritDoc
     */
    final public function count(): int
    {
        return count($this->search()->getResult());
    }

    /**
     * @inheritDoc
     */
    final public function search(): array
    {
        $queryBuilder = $this->getSlang();
        $dbQuery      = $queryBuilder->search($this);
        $this->doQuery($dbQuery, DatabaseObserver::SELECT);

        return $this->getResult();
    }

    /**
     * I will return the correct Slang object for the configured database.
     * @return \noxkiwi\dataabstraction\Slang
     */
    #[Pure] final protected function getSlang(): Slang
    {
        return new Slang();
    }

    /**
     * I will perform the given $query and save the result in the instance.
     *
     * @param \noxkiwi\database\Query $query
     * @param string|null             $queryType
     */
    final protected function doQuery(Query $query, string $queryType = null): void
    {
        $cacheKey = 'manualcache' . hash('sha512', serialize($this->getFilters()));
        if ($this->cache) {
            $this->result = $this->cacheInstance->get($this->getCacheGroup(), $cacheKey);
            if (is_array($this->result)) {
                $result = $this->result;
                $this->reset();
                $this->result = $result;

                return;
            }
        }
        try {
            switch ($queryType ?? DatabaseObserver::SELECT) {
                case DatabaseObserver::SELECT:
                case DatabaseObserver::READ:
                    $this->getDbConnection()->read($query->string, $query->data);
                    break;
                case DatabaseObserver::INSERT:
                case DatabaseObserver::UPDATE:
                case DatabaseObserver::DELETE:
                case DatabaseObserver::WRITE:
                default:
                    $this->getDbConnection()->write($query);
                    break;
            }
        } catch (Exception $exception) {
            ErrorHandler::handleException($exception);
        }
        // if cache enabled, save it
        if ($this->cache && ! empty($this->getResult())) {
            $this->cacheInstance->set($this->getCacheGroup(), $cacheKey, [$this->getResult()]);
        }
        $this->reset();
    }

    /**
     * I will return all filters defined on the instance.
     * @return array
     */
    final public function getFilters(): array
    {
        return $this->filters;
    }

    final public function setFilters(array $filters): void
    {
        $this->filters = $filters ?? [];
    }

    /**
     * Returns the cache group identifier for this model
     *
     * @return       string
     */
    final public function getCacheGroup(): string
    {
        if (empty(self::$cacheGroup)) {
            self::$cacheGroup = static::CACHE_DATA . strtoupper($this->getConnectionName() . '_' . str_replace('\\', '_', static::class));
        }

        return self::$cacheGroup;
    }

    /**
     * resets all the parameters of the instance for another query
     */
    final protected function reset(): void
    {
        $this->setFilters([]);
        $this->cacheInstance = Cache::getInstance();
        $this->errorStack    = ErrorStack::getErrorStack('MODEL');
        $this->order         = [];
        $this->fields        = [];
        $this->models        = [];
        $this->limit         = null;
        $this->offset        = null;
        $this->filters       = [];
        $this->flagFilters   = [];
        $this->cache         = false;
        $this->result        = null;
    }

    /**
     * @inheritDoc
     */
    final public function loadEntry($primaryKey): ?Entry
    {
        if (self::isEmpty($primaryKey)) {
            return null;
        }
        $entryName = $this->getEntryName($primaryKey);
        if (! empty(static::$entries[$entryName])) {
            return static::$entries[$entryName];
        }
        $entryData = $this->load($primaryKey);
        if (empty($entryData)) {
            return null;
        }
        static::$entries[$entryName] = $this->getEntry($entryData);
        try {
            static::$entries[$entryName]->set($entryData);
        } catch (InvalidArgumentException) {
        }

        return static::$entries[$entryName];
    }

    /**
     * I will return true if the given $value resembles an empty value
     *
     * @param null $value
     *
     * @return bool
     */
    #[Pure] final public static function isEmpty($value = null): bool
    {
        return in_array(
            $value,
            [null, '', [], '0000-00-00', '0000', '0000-00-00 00:00:00'],
            true
        );
    }

    /**
     * I will return the simple name of the given $primary key to make caching of entries possible.
     *
     * @param int|string $primaryKey
     *
     * @return string
     */
    private function getEntryName(int|string $primaryKey): string
    {
        return strtoupper(static::class . '_' . $this->getConnectionName() . '_' . $primaryKey);
    }

    /**
     * @inheritDoc
     */
    public function load($primaryKey): array
    {
        if (empty($primaryKey)) {
            return [];
        }

        return $this->loadByUnique($this->getPrimarykey(), (string)$primaryKey);
    }

    /**
     * I will load an entry from a unique field.
     *
     * @param string $field
     * @param string $value
     *
     * @return array
     */
    public function loadByUnique(string $field, string $value): array
    {
        $this->addFilter($field, $value);
        $this->setLimit(1);
        if ($field === $this->getPrimarykey()) {
            $cacheGroup = $this->getCacheGroup();
            $cacheKey   = static::getPrimaryCacheKey($value);
            $myData     = $this->cacheInstance->get($cacheGroup, $cacheKey);
            if (! is_array($myData) || empty($myData)) {
                $myData = $this->search();
                if (count($myData) === 1) {
                    $this->cacheInstance->set($cacheGroup, $cacheKey, $myData);
                }
            }
            $this->reset();
            if (! empty($myData)) {
                return $myData[0] ?? [];
            }

            return [];
        }
        $this->useCache();
        $data = $this->search();
        if (empty($data)) {
            return [];
        }

        return $data[0] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function addFilter(string $fieldName, $value = null, string $operator = null): void
    {
        $operator ??= Comparator::EQUALS;
        if (is_array($value) && empty($value)) {
            return;
        }
        if (! $this->fieldExists($fieldName)) {
            return;
        }
        $fieldType       = $this->getFieldType($fieldName);
        $this->filters[] = Filter::get($fieldName, $fieldType, $operator, $value);
    }

    /**
     * I will return the cacheKey for an ambigious entry identified by nothing but the primary key
     *
     *
     * @param int|string $primaryKey
     *
     * @return string
     */
    final public static function getPrimaryCacheKey(int|string $primaryKey): string
    {
        return 'PRIMARY_' . $primaryKey;
    }

    final public function useCache(bool $cache = true): void
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     * @throws \noxkiwi\core\Exception\InvalidArgumentException
     */
    final public function delete(mixed $primaryKey = null): void
    {
        if ($primaryKey instanceof Entry) {
            $primaryKey = $primaryKey->{$this->getPrimarykey()};
        }
        if (! empty($primaryKey)) {
            $queryBuilder = $this->getSlang();
            $dbQuery      = $queryBuilder->delete($this, $this->getFilters());
            $this->doQuery($dbQuery, DatabaseObserver::SELECT);

            return;
        }
        if (empty($this->getFilters())) {
            return;
        }
        $this->addField($this->getPrimarykey());
        $entries = $this->search();
        foreach ($entries as $entry) {
            $this->delete($entry[$this->getPrimarykey()]);
        }
    }

    /**
     * @inheritDoc
     */
    final public function addField(string $fieldName): void
    {
        if (str_contains($fieldName, ',')) {
            foreach (explode(',', $fieldName) as $myField) {
                $this->addField(trim($myField));
            }

            return;
        }
        if (! $this->fieldExists($fieldName)) {
            return;
        }
        $this->fields[] = new Field($fieldName);
    }

    /**
     * I will return the list of fields that will be queried for.
     *
     * @return \noxkiwi\dataabstraction\Model\Plugin\Field[]
     */
    final public function getSelectFields(): array
    {
        return $this->fields;
    }

    /**
     *
     * @param \noxkiwi\dataabstraction\Entry $entry
     *
     * @throws \noxkiwi\core\Exception\InvalidArgumentException
     */
    public function saveEntry(Entry $entry): void
    {
        $this->save($entry->get());
    }

    /**
     * @inheritDoc
     * @throws \noxkiwi\core\Exception\InvalidArgumentException
     */
    final public function save(array $data): void
    {
        if (empty($data)) {
            return;
        }
        $this->reset();
        $errors = $this->validate($data);
        if (! empty($errors)) {
            throw new InvalidArgumentException('INVALID_ENTRY', E_USER_NOTICE, $errors);
        }
        if (! empty($data[$this->getPrimarykey()])) {
            $data[$this->getPrimarykey()] = (string)$data[$this->getPrimarykey()];
            $this->update($data);

            return;
        }
        $this->insert($data);
    }

    /**
     * I will have the given $data validated against this Model's structure and content.
     *
     * @param array $data
     *
     * @return array
     */
    final public function validate(array $data): array
    {
        $fields = $this->getDefinitions();
        foreach ($fields as $fieldDefinition) {
            if (in_array($fieldDefinition, [$this->getPrimarykey(), static::TABLE . static::FIELDSUFFIX_MODIFIED, static::TABLE . static::FIELDSUFFIX_CREATED], true)) {
                continue;
            }
            $this->validateField($fieldDefinition, $data[$fieldDefinition->name] ?? null);
        }

        return $this->errorStack->getAll();
    }

    /**
     * I will validate the given $value for the given $fieldDefinition.
     *
     * @param \noxkiwi\dataabstraction\FieldDefinition $fieldDefinition
     * @param mixed                                    $value
     */
    private function validateField(FieldDefinition $fieldDefinition, mixed $value): void
    {
        if (self::isEmpty($value)) {
            if ($this->isRequired($fieldDefinition->name)) {
                $this->errorStack->addError('FIELD_IS_REQUIRED', $this->config->get('required', []));

                return;
            }

            return;
        }
        $errors = Validator::get($fieldDefinition->type)->validate($value, $fieldDefinition->validatorOptions);
        if (! empty($errors)) {
            $this->errorStack->addError("INVALID_$fieldDefinition->name", $errors);
        }
    }

    /**
     * I will solely return true if the given $fieldName is among the required fields.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    private function isRequired(string $fieldName): bool
    {
        return in_array($fieldName, (array)$this->config->get('required', []), true);
    }

    /**
     * I will update the entry and will also invalidate cache and write the cache again with the new data
     *
     * @param array $saveData
     */
    final protected function update(array $saveData): void
    {
        $slang = $this->getSlang();
        $this->addFilter($this->getPrimarykey(), $saveData[$this->getPrimarykey()]);
        $updateQuery = $slang->update($this, $this->filters[0], $saveData);
        $this->doQuery($updateQuery, DatabaseObserver::UPDATE);
        try {
            $cacheGroup = $this->getCacheGroup();
            $cacheKey   = static::getPrimaryCacheKey($saveData[$this->getPrimarykey()]);
            $this->cacheInstance->clearKey($cacheGroup, $cacheKey);
        } catch (Exception $exception) {
            ErrorHandler::handleException($exception);
        }
    }

    /**
     * I will insert a new entry on the underlying Database class with the given $saveData.
     *
     * @param array $saveData
     */
    final protected function insert(array $saveData): void
    {
        $slang       = $this->getSlang();
        $insertQuery = $slang->insert($this, $saveData);
        $this->doQuery($insertQuery, DatabaseObserver::UPDATE);
    }

    /**
     * @inheritDoc
     */
    final public function addModel(Model $model): void
    {
        $this->models[] = $model;
    }

    /**
     * @inheritDoc
     */
    final public function isFlag(int $flagValue, Entry|array|int $data): bool
    {
        if (empty($data)) {
            return false;
        }
        $flagField   = static::TABLE . '_flag';
        $currentFlag = 0;
        if ($data instanceof Entry) {
            if (empty($data->{$flagField})) {
                return false;
            }
            $currentFlag = $data->{$flagField};
        } elseif (is_array($data)) {
            $currentFlag = (int)($data[$flagField] ?? 0);
        }
        if (is_int($data)) {
            $currentFlag = $data;
        }

        return ($currentFlag & $flagValue) === $flagValue;
    }

    /**
     * @inheritDoc
     */
    final public function addOrder(string $fieldName, string $direction = null): void
    {
        $direction     ??= 'ASC';
        $this->order[] = new Order($fieldName, $direction);
    }

    /**
     * I will normalize the given $data.
     *
     * @param array $data
     *
     * @return array
     */
    final public function normalizeData(array $data): array
    {
        $myData      = [];
        $definitions = $this->getDefinitions();
        foreach ($definitions as $fieldName => $definition) {
            // if Field has object identified
            if (isset($data["{$fieldName}_"])) {
                $object = [];
                foreach ($data as $key => $value) {
                    if (str_contains($key, $fieldName . '__')) {
                        $object[str_replace($fieldName . '__', '', strtolower($key))] = $value;
                    }
                }
                $myData[$fieldName] = $object;
            }
            if ($fieldName === static::TABLE . '_flag') {
                if (! empty($data[static::TABLE . '_flag'])) {
                    if (! is_array($data[static::TABLE . '_flag'])) {
                        continue;
                    }
                    $flagval = 0;
                    foreach ($data[static::TABLE . '_flag'] as $flagname => $status) {
                        $currflag = $this->config->get('flag>' . $flagname);
                        if ($currflag === null) {
                            continue;
                        }
                        $flagval |= $currflag;
                    }
                    $myData[$fieldName] = $flagval;
                } else {
                    unset($data[$fieldName]);
                }
                continue;
            }
            // Otherwise, the Field exists in the data object
            if (isset($data[$fieldName])) {
                $myData[$fieldName] = $this->importField($fieldName, $data[$fieldName]);
            }
        }

        return $myData;
    }

    /**
     * Converts the given Field, and it's value from a human readible format into a storage format
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return       mixed
     */
    final protected function importField(string $fieldName, mixed $value = null): mixed
    {
        $return = $value;
        switch ($this->getFieldType($fieldName)) {
            case 'boolean':
                if ($value === 'true') {
                    $return = true;
                } elseif ($value === 'false') {
                    $return = false;
                } else {
                    $return = null;
                }
                break;
            case 'number_natural':
                $return = (int)$value;
                break;
            case 'text_date':
                if (empty($value)) {
                    $return = null;
                    break;
                }
                $date = DateTime::createFromFormat('d.m.Y', $value);
                if ($date !== false) {
                    $return = $date->format('Y-m-d');
                }
                break;
            default:
                break;
        }

        return $return;
    }

    /**
     * I will solely return the string name of the model.
     * @return string
     */
    final public function getModelName(): string
    {
        $fullQualified = $this::class;
        $classElements = explode('\\', $fullQualified);
        $className     = end($classElements);

        return (string)str_replace('Model', '', $className);
    }

    /**
     * I will return the sorting setup config.
     *
     * @return \noxkiwi\dataabstraction\Model\Plugin\Order[]
     */
    final public function getOrders(): array
    {
        return $this->order;
    }

    final public function getLimit(): ?Limit
    {
        return $this->limit;
    }

    /**
     * @inheritDoc
     */
    final public function setLimit(int $limit): void
    {
        $this->limit = new Limit(min($limit, static::CONST_MAX_LIMIT));
    }

    /**
     * @inheritDoc
     */
    final public function copy($primaryKey): void
    {
    }

    final public function getOffset(): ?Offset
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     */
    final public function setOffset(int $offset): void
    {
        $this->offset = new Offset($offset);
    }
}
