<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin\Filter;

use noxkiwi\dataabstraction\Comparator;
use noxkiwi\dataabstraction\Model\Plugin\Filter;

/**
 * I am a filter that is applicable for text fields.
 *
 * @package      noxkiwi\dataabstraction\Plugin
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class TextFilter extends Filter
{
    public const COMPARATORS = [
        Comparator::CONTAINS,
        Comparator::NOT_CONTAINS,
        Comparator::BEGINS,
        Comparator::NOT_BEGINS,
        Comparator::ENDS,
        Comparator::NOT_ENDS,
        Comparator::EQUALS,
        Comparator::NOT_EQUALS
    ];
}
