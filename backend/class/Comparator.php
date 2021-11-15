<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction;

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
final class Comparator
{
    public const LESS             = 'less';
    public const LESS_OR_EQUAL    = 'lessOrEqual';
    public const EQUALS           = 'equals';
    public const GREATER_OR_EQUAL = 'greaterOrEqual';
    public const GREATER          = 'greater';
    public const NOT_EQUALS       = 'not_equals';
    public const BEGINS           = 'begins';
    public const ENDS             = 'ends';
    public const CONTAINS         = 'contains';
    public const NOT_BEGINS       = 'not_begins';
    public const NOT_ENDS         = 'not_ends';
    public const NOT_CONTAINS     = 'not_contains';
    public const ALL              = [
        self::LESS,
        self::LESS_OR_EQUAL,
        self::EQUALS,
        self::GREATER_OR_EQUAL,
        self::GREATER,
        self::NOT_EQUALS,
        self::BEGINS,
        self::ENDS,
        self::CONTAINS,
        self::NOT_BEGINS,
        self::NOT_ENDS,
        self::NOT_CONTAINS,
    ];
}
