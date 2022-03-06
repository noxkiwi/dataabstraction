<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction;

/**
 * I am a field definition for a Model.
 *
 * @package      noxkiwi\dataabstraction
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class FieldDefinition
{
    /** @var string I am the Name of the Field. */
    public string $name;
    /** @var string I am the displayed Name of the Field. Used for columns, form fields, etc. */
    public string $displayName;
    /** @var string I am the plain type of the Field. */
    public string $type;
    /** @var string I am the displayed type of the field. */
    public string $displayType;
    /** @var bool I'll mark the Field as mandatory. */
    public bool $required;
    /** @var int I'll give info about the min value or length of the Field. */
    public int $min;
    /** @var int I'll give info about the max value or length of the Field. */
    public int $max;
    /** @var bool I'll set the Field to a UNIQUE constraint. */
    public bool $unique;
    /** @var array I'll set the Field up to point to another Model. */
    public array $foreign;
    /** @var bool I'll preven the user from changing this Field. */
    public bool $readonly;
    /** @var string I am name of the Enumerator to choose from. */
    public string $enum;
    /** @var array I am the options array for the validator configuration. */
    public array $validatorOptions;
    /** @var mixed I am the value that will be pre-filled. */
    public mixed $defaultValue;
}

