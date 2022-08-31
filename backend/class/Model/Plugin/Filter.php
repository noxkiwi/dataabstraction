<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin;

use noxkiwi\core\Helper\StringHelper;
use noxkiwi\dataabstraction\Comparator;
use noxkiwi\dataabstraction\Model;
use noxkiwi\dataabstraction\Model\Plugin\Filter\DateFilter;
use noxkiwi\dataabstraction\Model\Plugin\Filter\NumberFilter;
use noxkiwi\dataabstraction\Model\Plugin\Filter\TextFilter;
use noxkiwi\dataabstraction\Type;
use function str_starts_with;

/**
 * I am the Filter Plugin.
 *
 * @package      noxkiwi\dataabstraction\Plugin
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
abstract class Filter
{
    public string $fieldName;
    public string|int|float|array $value;
    public string $operator;
    public string $mask = '';
    public Model $model;

    /**
     * @return string|int|float|array
     */
    final public function getValue(): string|int|float|array
    {
        $this->injectMask();
        if (empty($this->mask)) {
            return $this->value;
        }

        return StringHelper::interpolate($this->mask, ['value' => $this->value]);
    }

    /**
     * @return void
     */
    final protected function injectMask(): void
    {
        switch ($this->operator) {
            case Comparator::BEGINS:
                $this->mask = '{value}%';

                return;
            case Comparator::ENDS:
                $this->mask = '%{value}';

                return;
            case Comparator::CONTAINS:
                $this->mask = '%{value}%';

                return;
            default:
        }
    }

    /**
     * I will return the operator string according to the operator that was set.
     * @return string
     */
    final public function getOperatorString(): string
    {
        $this->injectMask();

        return match ($this->operator) {
            Comparator::LESS => '<',
            Comparator::LESS_OR_EQUAL => '<=',
            Comparator::GREATER_OR_EQUAL => '>=',
            Comparator::GREATER => '>',
            Comparator::NOT_EQUALS => '!=',
            Comparator::BEGINS, Comparator::ENDS, Comparator::CONTAINS => 'LIKE',
            default => '='
        };
    }
}
