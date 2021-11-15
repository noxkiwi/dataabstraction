<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction;

use Exception;
use noxkiwi\core\ErrorHandler;

/**
 * I am
 *
 * @package      noxkiwi\core
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2021 noxkiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class EntryStack
{
    /** @var array I am the array of entries inside this stack. */
    private array $entries;
    /** @var bool I am the trigger that locks the stack. */
    private bool $locked;

    /**
     * EntryStack constructor.
     */
    public function __construct()
    {
        $this->entries = [];
        $this->locked  = false;
    }

    /**
     * I will add the given $entries to the stack.
     *
     * @param \noxkiwi\dataabstraction\Entry[] $entries
     *
     * @return \noxkiwi\dataabstraction\EntryStack
     */
    public function addEntries(array $entries): EntryStack
    {
        foreach ($entries as $entry) {
            if (! $entry instanceof Entry) {
                continue;
            }
            $this->addEntry($entry);
        }

        return $this;
    }

    /**
     * I will add the given $entry to the EntryStack.
     *
     *
     * @param \noxkiwi\dataabstraction\Entry $entry
     *
     * @return \noxkiwi\dataabstraction\EntryStack
     */
    public function addEntry(Entry $entry): EntryStack
    {
        if ($this->locked === true) {
            return $this;
        }
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * I will return every possible value for the given $field.
     *
     * @param string $field
     *
     * @return array
     */
    public function __get(string $field): array
    {
        $fields = [];
        foreach ($this->entries as $entry) {
            $fields[] = $entry->{$field};
        }

        return $fields;
    }

    /**
     * I will set the given $field to the given $value for all Entries you have given before.
     *
     * @param string $field
     * @param mixed  $value
     */
    public function __set(string $field, mixed $value): void
    {
        $this->lock();
        foreach ($this->entries as $entry) {
            $entry->{$field} = $value;
        }
    }

    /**
     * I will lock the object whenever you start editing stuff in it.
     */
    private function lock(): void
    {
        $this->locked = true;
    }

    /**
     * I will
     *
     * @param string $field
     *
     * @return bool
     */
    public function __isset(string $field): bool
    {
        return false;
    }

    /**
     * I will commit the updated data and store it to the models.
     *
     * @return       \noxkiwi\dataabstraction\EntryStack
     */
    public function save(): EntryStack
    {
        $this->lock();
        foreach ($this->entries as $entry) {
            try {
                $entry->save();
            } catch (Exception $exception) {
                ErrorHandler::handleException($exception);
            }
        }
        $this->unlock();

        return $this;
    }

    /**
     * I will unlock the object to make adding elements possible again.
     */
    private function unlock(): void
    {
        $this->locked = false;
    }
}

