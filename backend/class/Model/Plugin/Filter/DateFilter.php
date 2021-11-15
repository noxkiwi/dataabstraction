<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin\Filter;

use noxkiwi\dataabstraction\Comparator;
use noxkiwi\dataabstraction\Model\Plugin\Filter;

/**
 * I am a filter that is applicable for date and time fields.
 *
 * @package      noxkiwi\dataabstraction\Plugin
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class DateFilter extends Filter
{
    public const COMPARATORS = [
        Comparator::NOT_EQUALS,
        Comparator::GREATER_OR_EQUAL,
        Comparator::GREATER,
        Comparator::EQUALS,
        Comparator::LESS_OR_EQUAL,
        Comparator::LESS,
    ];
}
