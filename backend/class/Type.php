<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction;

/**
 * I am the collection of Types.
 *
 * @package      noxkiwi\dataabstraction
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class Type
{
    public const TEXT           = 'text';
    public const DATE           = 'date';
    public const NUMBER         = 'number';
    public const NUMBER_NATURAL = 'number_natural';
    public const STRUCTURE      = 'structure';
    public const ALL            = [
        self::TEXT,
        self::DATE,
        self::NUMBER,
        self::NUMBER_NATURAL,
        self::STRUCTURE
    ];
}

