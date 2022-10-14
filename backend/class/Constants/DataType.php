<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Constants;

/**
 * I am the collection of hooks in the DataAbstraction system.
 *
 * @package      noxkiwi\dataabstraction
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2022 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class DataType
{
    public const TEXT           = 'text';
    public const NUMBER         = 'number';
    public const NUMBER_NATURAL = 'number_natural';
    public const NUMBER_INTEGER = 'number_integer';
    public const NUMBER_PORT    = 'number_port';
    public const DATE           = 'text_date';
    public const TEXT_DOMAIN    = 'text_domain';
    public const DATE_TIME      = 'text_timestamp';
    public const FILE           = 'file';
    public const STRUCTURE      = 'structure';
    public const BOOLEAN        = 'boolean';
}
