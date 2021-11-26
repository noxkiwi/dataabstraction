<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction;

use Exception;
use JetBrains\PhpStorm\Pure;
use noxkiwi\core\Exception\InvalidArgumentException;
use noxkiwi\dataabstraction\Interfaces\EntryInterface;
use noxkiwi\validator\Validator;
use function array_key_exists;
use function compact;
use function is_array;
use function strtoupper;
use const E_USER_NOTICE;
use const E_WARNING;

/**
 * I am an arbitrary entry. Utilize me e.g. for Database entries
 * <br />All I contain is validated
 *
 * @package      noxkiwi\dataabstraction
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 - 2021 nox.kiwi
 * @version      1.1.3
 * @link         https://nox.kiwi/
 */
final class Entry implements EntryInterface
{
    /** @var array I am the data array of the Entry. */
    private array $data;
    /** @var \noxkiwi\dataabstraction\Model I am the Model instance that is capable of saving this Entry. */
    private Model $model;
    /** @var array|null I am the list of fields that have been changed. If null, the entry is not created yet. */
    private ?array $changedFields;

    /**
     * I will construct the class instance and set some data
     *
     * @param \noxkiwi\dataabstraction\Model $model
     * @param array                          $data
     */
    public function __construct(Model $model, array $data = [])
    {
        $this->data          = [];
        $this->model         = $model;
        $this->changedFields = null;
        foreach ($this->getModel()->getDefinitions() as $fieldDefinition) {
            if (! array_key_exists($fieldDefinition->name, $data)) {
                continue;
            }
            try {
                $this->setField($fieldDefinition->name, $data[$fieldDefinition->name]);
            } catch (Exception) {
            }
        }
        $this->changedFields = [];
    }

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     * @throws \noxkiwi\core\Exception\InvalidArgumentException
     */
    public function set(array $fields): void
    {
        $errors = [];
        foreach ($fields as $fieldName => $fieldValue) {
            try {
                $this->setField($fieldName, $fieldValue);
            } catch (InvalidArgumentException $exception) {
                $errors[] = $exception;
                continue;
            }
        }
        if (is_array($this->changedFields) && ! empty($errors)) {
            throw new InvalidArgumentException('INVALID_DATA', E_USER_NOTICE, $errors);
        }
    }

    /**
     * I will return the Model instance.
     *
     * @return \noxkiwi\dataabstraction\Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * I will mark the given $fieldName as changed, compared to the original entry.
     * The changedFields property will now contain the key of the given $fieldName,
     * where you can find the >old and >new value.
     *
     * @param string $fieldName
     * @param mixed  $newValue
     */
    final protected function changeField(string $fieldName, mixed $newValue): void
    {
        if (! is_array($this->changedFields)) {
            return;
        }
        if (! $this->getModel()->fieldExists($fieldName)) {
            return;
        }
        $this->changedFields[$fieldName] = [
            'old' => $this->data[$fieldName] ?? 'null',
            'new' => $newValue ?? 'null'
        ];
    }

    /**
     * I will solely return the fields that have been changed.
     *
     * @return array
     */
    final public function getChangedFields(): array
    {
        return $this->changedFields ?? [];
    }

    /**
     * I will solely return the value of the given $fieldName.
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    final public function getField(string $fieldName): mixed
    {
        return $this->data[$fieldName] ?? null;
    }

    /**
     * I will set the given $fieldName to the given $fieldValue.
     *
     * @param string $fieldName
     * @param mixed  $fieldValue
     *
     * @throws \noxkiwi\core\Exception\InvalidArgumentException
     */
    final protected function setField(string $fieldName, mixed $fieldValue): void
    {
        if (! $this->getModel()->fieldExists($fieldName)) {
            return;
        }
        if (Model::isEmpty($fieldValue)) {
            $fieldValue = null;
        }
        if (($this->data[$fieldName] ?? null) == $fieldValue) {
            return;
        }
        $this->changeField($fieldName, $fieldValue);
        $definition = $this->getModel()->getDefinition($fieldName);
        $validator  = Validator::get($definition->type);
        $errors     = $validator->validate($fieldValue, [Validator::OPTION_NULL_ALLOWED => ! $definition->required]);
        if (! empty($errors)) {
            $error = compact('fieldName', 'fieldValue', 'errors');
            throw new InvalidArgumentException('EXCEPTION_INVALID_' . strtoupper($fieldName), E_WARNING, $error);
        }
        $this->data[$fieldName] = $fieldValue;
    }

    /**
     * @see        \noxkiwi\dataabstraction\Entry::getField()
     */
    final public function __get(string $fieldName): mixed
    {
        return $this->data[$fieldName] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function save(): void
    {
        if (empty($this->getChangedFields())) {
            return;
        }
        $this->getModel()->saveEntry($this);
    }
}
