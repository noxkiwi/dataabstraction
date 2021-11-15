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
final class Hook extends \noxkiwi\hook\Hook
{
    public const ENTRY_SET_FIELD_START   = 'FIELD_SET_START';
    public const ENTRY_SET_FIELD_INVALID = 'FIELD_SET_INVALID';
    public const ENTRY_SET_FIELD_SUCCESS = 'FIELD_SET_SUCCESS';
}

