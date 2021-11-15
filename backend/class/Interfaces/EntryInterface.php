<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Interfaces;

/**
 * I am the interface of all Entry objects
 *
 * @package      noxkiwi\dataabstraction\Interfaces
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 - 2021 nox.kiwi
 * @version      1.0.1
 * @link         https://nox.kiwi/
 */
interface EntryInterface
{
    /**
     * I will return the whole data property of the Entry.
     * @return array
     */
    public function get(): array;

    /**
     * I will validate the given data. If any field is invalid, I will throw an InvalidArgumentException.
     *
     * @param array $fields
     */
    public function set(array $fields): void;

    /**
     * I will store all data that was modified in this entry into the model.
     * @throws \noxkiwi\core\Exception\InvalidArgumentException
     */
    public function save(): void;
}
