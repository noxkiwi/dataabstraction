<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction;

use JetBrains\PhpStorm\Pure;
use noxkiwi\core\Helper\JsonHelper;
use noxkiwi\dataabstraction\Model\Plugin\Filter;
use noxkiwi\database\Query;
use noxkiwi\database\QueryAddon;
use function array_key_exists;
use function implode;
use function in_array;
use function is_array;
use function is_object;

/**
 * I am the collection of hooks in the DataAbstraction system.
 *
 * @package      noxkiwi\dataabstraction
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class Slang
{
    /**
     * I will perform a search task on the current model.
     * The search task will apply...
     *
     * @param \noxkiwi\dataabstraction\Model $model
     *
     * @return      \noxkiwi\database\Query
     */
    public function search(Model $model): Query
    {
        $query         = new Query();
        $query->string = 'SELECT ';
        $query->attach($this->getFieldList($model->getSelectFields()));
        $query->string .= ' FROM ';
        $query->attach($this->getQueryTable($model));
        $joinAddon = $this->getQueryJoins($model);
        if($joinAddon) {
            $query->attach($joinAddon);
        }
        $query->attach($this->getQueryFilter($model, ' AND ', true));
        $query->attach($this->getQueryOrder($model, true));
        $query->attach($this->getQueryLimit($model->getLimit(), $model->getOffset()));

        return $query;
    }

    /**
     * @param string[] $fields
     *
     * @return \noxkiwi\database\QueryAddon
     */
    #[Pure] protected function getFieldList(array $fields): QueryAddon
    {
        $queryFields = '*';
        if (! empty($fields)) {
            $nFields = [];
            foreach ($fields as $field) {
                $nFields[] = $field;
            }
            $queryFields = implode(',', $nFields);
        }
        $addon         = new QueryAddon();
        $addon->data   = [];
        $addon->string = $queryFields;

        return $addon;
    }

    /**
     * @param \noxkiwi\dataabstraction\Model $model
     *
     * @return \noxkiwi\database\QueryAddon
     */
    #[Pure] protected function getQueryTable(Model $model): QueryAddon
    {
        $addon         = new QueryAddon();
        $addon->string = $this->delimitTableName($model->getTable());
        $addon->data   = [];

        return $addon;
    }

    /**
     * I will solely return the JOIN additions for the desired query.
     *
     * @param \noxkiwi\dataabstraction\Model $model
     *
     * @return \noxkiwi\database\QueryAddon|null
     */
    protected function getQueryJoins(Model $model): ?QueryAddon{
        $joinedModels = $model->getModels();
        if(empty($joinedModels)) {
            return null;
        }
        $addon = new QueryAddon();
        $addon->data= [];
        foreach($joinedModels as $joinedModel) {
            $joinedModel->setJoinAlias(uniqid());
            $addon->string .= <<<SQL
JOIN    {$this->delimitTableName($joinedModel::TABLE)} AS `{$joinedModel->getTable()}` USING ({$this->delimitTableName($joinedModel->getPrimarykey())})
SQL;
        }
        $model->setModels($joinedModels);
        return $addon;
    }

    /**
     * I will delimit the given $tableName.
     *
     * @param string $tableName
     *
     * @return string
     */
    protected function delimitTableName(string $tableName): string
    {
        return "`$tableName`";
    }

    /**
     * @param \noxkiwi\dataabstraction\Model $model
     * @param string                                         $operator
     * @param bool $top
     *
     * @return \noxkiwi\database\QueryAddon
     */
    protected function getQueryFilter(Model $model, string $operator, bool $top = false): QueryAddon
    {
        $addon         = new QueryAddon();
        $addon->string = $top ? ' WHERE TRUE ' : '';
        $addon->data   = [];
        foreach($model->getModels() as $joinedModel) {
            $joinedAddon = $this->getQueryFilter($joinedModel, $operator);
            $addon->string.=$joinedAddon->string;
            $addon->data  +=$joinedAddon->data;
        }
        $table = $model->getTable();
        foreach ($model->getFilters() as $filter) {
            $field = $filter->fieldName;
            $addon->string .=chr(10). "$operator `{$table}`.`{$field}` ";
            if (is_array($filter->getValue())) {
                $values = [];
                foreach ($filter->getValue() as $currentValue) {
                    $values[] = self::delimit($field, $currentValue);
                }
                $string = implode(', ', $values);
                $addon->string  .= ' IN ( ' . $string . ') ';
            } elseif (empty($filter->getValue()) || $filter->getValue() === 'null') {
                $addon->string .= ' IS NULL ';
            } else {
                $key=  "{$table}_FILTER_$field";
                $addon->data[$key] = $filter->getValue();
                $addon->string    .= "{$filter->getOperatorString()} :$key";
            }
        }
        return $addon;
    }

    /**
     * If required, I will delimit the given $value.
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return string
     */
    private static function delimit(string $fieldName, mixed $value): string
    {
        return "'$value'";
    }

    /**
     * I will return the order statements of this query
     *
     * @param \noxkiwi\dataabstraction\Model $orders
     * @param bool $top
     *
     * @return       \noxkiwi\database\QueryAddon
     */
    #[Pure] protected function getQueryOrder(Model $model, bool $top = false): QueryAddon
    {
        $addon         = new QueryAddon();
        $addon->string = $top ? ' ORDER BY ' : '';
        $addon->data   = [];
        $comma = '';
        foreach($model->getModels() as $joinedModel) {
            $joinedAddon = $this->getQueryOrder($joinedModel);
            $addon->string.="$comma $joinedAddon->string";
            $addon->data  +=$joinedAddon->data;
            $comma = ', ';
        }
        if (empty($model->getOrders())) {
            return $addon;
        }
        $addon->string .= $comma.$this->getOrders($model);

        return $addon;
    }

    /**
     * @param array $orders
     *
     * @return string
     */
    protected function getOrders(Model $model): string
    {
        $order         = '';
        $appliedOrders = false;
        foreach ($model->getOrders() as $myOrder) {
            $order .= $appliedOrders ? ', ' : '';
            $order .= chr(10)." `{$model->getTable()}`.`$myOrder->fieldName` $myOrder->direction";
            $appliedOrders = true;
        }

        return $order;
    }

    /**
     * I will return the limitation area of the query containing limit and offset
     *
     * @param int|null  $limit
     * @param int|null $offset
     *
     * @return       \noxkiwi\database\QueryAddon
     */
    #[Pure] protected function getQueryLimit(int $limit = null, int $offset = null): QueryAddon
    {
        $qA = new QueryAddon();
        if ($limit !== null) {
            $qA->string .= $this->getLimit($limit);
            if ($offset !== null) {
                $qA->string .= $this->getOffset($offset);
            }
        }

        return $qA;
    }

    /**
     * @param int $limit
     *
     * @return string
     */
    protected function getLimit(int $limit): string
    {
        if ($limit > 0) {
            return " LIMIT $limit ";
        }

        return '';
    }

    /**
     * @param int $offset
     *
     * @return string
     */
    protected function getOffset(int $offset): string
    {
        if ($offset > 0) {
            return " OFFSET $offset ";
        }

        return '';
    }

    /**
     * I will formulate the UPDATE query.
     *
     * @param \noxkiwi\dataabstraction\Model               $model
     * @param \noxkiwi\dataabstraction\Model\Plugin\Filter $primaryFilter
     * @param array                                        $saveData
     *
     * @return \noxkiwi\database\Query
     */
    public function update(Model $model, Filter $primaryFilter, array $saveData): Query
    {
        $query         = new Query();
        $query->string = 'UPDATE ';
        $query->attach($this->getQueryTable($model));
        $query->string .= ' SET ';
        $query->attach($this->getQuerySet($model, $saveData));
        $query->attach($this->getQueryFilter($model, ' AND '));

        return $query;
    }

    /**
     * I will return the data for the new Field contents
     *
     * @param \noxkiwi\dataabstraction\Model $model
     * @param array                          $data
     *
     * @return \noxkiwi\database\QueryAddon
     */
    protected function getQuerySet(Model $model, array $data): QueryAddon
    {
        $queryString = '';
        $queryData   = [];
        $index       = 0;
        $fields      = $model->getConfig()->get('fields');
        foreach ($fields as $fieldName => $field) {
            if (in_array($fieldName, [$model->getPrimarykey(), $model->getTable() . Model::FIELDSUFFIX_MODIFIED, $model->getTable() . Model::FIELDSUFFIX_CREATED], true)) {
                continue;
            }
            if (! array_key_exists($fieldName, $data)) {
                continue;
            }
            if (is_object($data[$fieldName]) || is_array($data[$fieldName])) {
                $data[$fieldName] = JsonHelper::encode($data[$fieldName]);
            }
            if ($index > 0) {
                $queryString .= ', ';
            }
            $index++;
            $setFieldName             = "SETFIELD_$fieldName";
            $queryString              .= " `$fieldName` = :$setFieldName ";
            $queryData[$setFieldName] = $data[$fieldName];
        }
        $qa         = new QueryAddon();
        $qa->string = $queryString;
        $qa->data   = $queryData;

        return $qa;
    }

    /**
     * I will insert the given $saveData into a new entry on the database.
     *
     * @param \noxkiwi\dataabstraction\Model $model
     * @param array                          $saveData
     *
     * @return \noxkiwi\database\Query
     */
    public function insert(Model $model, array $saveData, bool $forceMode = false): Query
    {
        $query         = new Query();
        $query->string = 'INSERT INTO';
        $query->attach($this->getQueryTable($model));
        $query->string .= ' ( ';
        $query->string .= implode(', ', $this->removeReadonlyFields($model, $saveData, null, true, $forceMode));
        $query->string .= ') VALUES (';
        $query->string .= implode(', ', $this->removeReadonlyFields($model, $saveData, ':SETFIELD_', $forceMode));
        $query->string .= ' );';
        foreach ($this->removeReadonlyFields($model, $saveData) as $field) {
            if (is_object($saveData[$field]) || is_array($saveData[$field])) {
                $query->data[$field] = JsonHelper::encode($saveData[$field]);
            }
            $query->data['SETFIELD_' . $field] = $saveData[$field];
        }

        return $query;
    }

    /**
     * I will return the list of available AND writeable fields for this data array
     *
     * @param \noxkiwi\dataabstraction\Model $model
     * @param array                          $data
     * @param string|null                    $prefix
     * @param bool                           $delimit
     *
     * @return       array
     */
    protected function removeReadonlyFields(Model $model, array $data, string $prefix = null, bool $delimit = false): array
    {
        $prefix      ??= '';
        $myData      = [];
        $definitions = $model->getDefinitions();
        foreach ($definitions as $fieldName => $definition) {
            if (! isset($data[$fieldName])) {
                continue;
            }
            if ($delimit) {
                $fieldName = self::delimitField($definition, $fieldName);
            }
            $myData[] = $prefix . $fieldName;
        }

        return $myData;
    }

    /**
     * I will [To be filled by Jan]
     *
     * @param \noxkiwi\dataabstraction\FieldDefinition $fieldDefinition
     * @param string                                   $fieldName
     *
     * @return string
     */
    private static function delimitField(FieldDefinition $fieldDefinition, string $fieldName): string
    {
        return "`$fieldName`";
    }

    /**
     * I will perform a search task on the current model.
     * The search task will apply...
     *
     * @param \noxkiwi\dataabstraction\Model                 $model
     * @param \noxkiwi\dataabstraction\Model\Plugin\Filter[] $filters
     *
     * @return      \noxkiwi\database\Query
     */
    public function delete(Model $model, array $filters): Query
    {
        $query         = new Query();
        $query->string = 'DELETE FROM';
        $query->attach($this->getQueryTable($model));
        $query->attach($this->getQueryFilter($model, ' AND '));

        return $query;
    }
}

