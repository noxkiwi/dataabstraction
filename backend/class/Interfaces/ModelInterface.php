<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Interfaces;

use noxkiwi\core\Config;
use noxkiwi\dataabstraction\Entry;
use noxkiwi\dataabstraction\Model;

/**
 * I am the interface for all noxkiwi Model classes.
 *
 * @package      noxkiwi\dataabstraction\Interfaces
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
interface ModelInterface
{
    /**
     * I will perform a search task on the current model.
     * The search task will apply...
     *   - Previously added Filters @see ModelInterface::addFiler
     *   - Previously added Sorting @see ModelInterface::addOrder
     *   - Previously set result limit @see ModelInterface::setLimit
     *   - Previously set result offset @see ModelInterface::setOffset
     *   - Previously requested result fields @see ModelInterface::addField
     *
     * @return array
     */
    public function search(): array;

    /**
     * I will delete the entry identified by the given $primaryKey.
     * If $primaryKey is not given, I will delete any entries that match---
     *   - Previously added Filters @see ModelInterface::addFiler
     * These entries will be deleted in a recursive call given the primaryKey of each entry.
     *
     * @param string|int|null $primaryKey
     */
    public function delete(string|int $primaryKey = null): void;

    /**
     * I will save the given $data to the model if it is valid.
     * I will decide if the action will be an UPDATE or a CREATE action.
     * I will UPDATE, if $data has a correct value for a primaryKey.
     * I will CREATE in any other case.
     *
     * @param array $data
     */
    public function save(array $data): void;

    /**
     * I will duplicate the entry identified by the given $primaryKey.
     *
     * @param string|int $primaryKey
     */
    public function copy(string|int $primaryKey): void;

    /**
     * I will load the entry that is identified by the given $primaryKey.
     *
     * @param string|int $primaryKey
     *
     * @return       array
     */
    public function load(string|int $primaryKey): array;

    /**
     * Adds a filter to the query. It matches the $value in $Field using the given $operator
     *
     * @param string      $fieldName
     * @param null        $value
     * @param string|null $operator
     */
    public function addFilter(string $fieldName, $value = null, string $operator = null): void;

    /**
     * Adds an order $Field and $direction to the query.
     *
     * @param string $fieldName
     * @param string $direction
     */
    public function addOrder(string $fieldName, string $direction): void;

    /**
     * Sets the limit of the query
     *
     * @param int $limit
     */
    public function setLimit(int $limit): void;

    /**
     * Sets the offset of the query
     *
     * @param int $offset
     */
    public function setOffset(int $offset): void;

    /**
     * Returns the result of the given query as an array.
     *
     * @return       array
     */
    public function getResult(): array;

    /**
     * Adds a return Field to the given instance
     *
     * @param string $fieldName
     */
    public function addField(string $fieldName): void;

    /**
     * I will utilize the current model settings and return the count of rows
     *
     * @return       int
     */
    public function count(): int;

    /**
     * I will load the entry identified by the given $primaryKey into an Entry instance.
     * If the Graceful Mode of the model is not enabled, I will throw:
     *
     * @param string|int $primaryKey
     *
     * @return \noxkiwi\dataabstraction\Entry|null
     */
    public function loadEntry(string|int $primaryKey): ?Entry;

    /**
     * Returns the primary key that was configured in the model's JSON config
     *
     * @return       string
     */
    public function getPrimarykey(): string;

    /**
     * I will return the current config instance
     * @return \noxkiwi\core\Config
     */
    public function getConfig(): Config;

    /**
     * I will add the given $model instance to the current instance
     * <br />That's how easy you can make a JOIN query
     *
     *
     * @param \noxkiwi\dataabstraction\Model $model
     */
    public function addModel(Model $model): void;

    /**
     * Returns true if the given flag name is set to true in the data array. Returns false otherwise
     *
     * @param int                                      $flagValue
     * @param \noxkiwi\dataabstraction\Entry|array|int $data
     *
     * @return       bool
     */
    public function isFlag(int $flagValue, Entry|array|int $data): bool;
}
