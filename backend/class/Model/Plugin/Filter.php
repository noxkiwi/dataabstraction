<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin;

use noxkiwi\core\Helper\StringHelper;
use noxkiwi\dataabstraction\Comparator;
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
    /** @var string The field that is used to filter data from the model */
    private string $fieldName;
    /** @var string|int|float|array Contains the value to searched in the $Field */
    private string|int|float|array $value;
    /** @var string $operator Contains the $operator for the $Field */
    private string $operator;
    /** @var string I may masquerade the value. */
    private string $mask = '';

    /**
     * Constructor.
     *
     * @param string $fieldName
     * @param string $operator
     * @param mixed  $value
     */
    final protected function __construct(string $fieldName, string $operator, mixed $value)
    {
        $this->setValue($value);
        $this->setFieldName($fieldName);
        $this->setOperator($operator);
    }

    /**
     * I will create a new filter instance.
     *
     * @param string $fieldName
     * @param string $fieldType
     * @param string $operator
     * @param        $value
     *
     * @return \noxkiwi\dataabstraction\Model\Plugin\Filter
     */
    public static function get(string $fieldName, string $fieldType, string $operator, $value): Filter
    {
        if (str_starts_with($fieldType, Type::NUMBER)) {
            return new NumberFilter($fieldName, $operator, $value);
        }
        if (str_starts_with($fieldType, Type::DATE)) {
            return new DateFilter($fieldName, $operator, $value);
        }

        return new TextFilter($fieldName, $operator, $value);
    }

    /**
     * I'll solely return the Field name.
     * @return string
     */
    final public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     */
    final public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string|int|float|array
     */
    final public function getValue(): string|int|float|array
    {
        $this->injectMask();
        if (empty($this->getMask())) {
            return $this->value;
        }

        return StringHelper::interpolate($this->getMask(), ['value' => $this->value]);
    }

    /**
     * @param mixed $value
     */
    final public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * @return void
     */
    final protected function injectMask(): void
    {
        switch ($this->getOperator()) {
            case Comparator::BEGINS:
                $this->setMask('{value}%');

                return;
            case Comparator::ENDS:
                $this->setMask('%{value}');

                return;
            case Comparator::CONTAINS:
                $this->setMask('%{value}%');

                return;
            default:
        }
    }

    /**
     * @return string
     */
    final public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    final public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getMask(): string
    {
        return $this->mask;
    }

    /**
     * I will set the mask of the filter.
     *
     * @param string $mask
     */
    public function setMask(string $mask): void
    {
        $this->mask = $mask;
    }

    /**
     * I will return the operator string according to the operator that was set.
     * @return string
     */
    final public function getOperatorString(): string
    {
        $this->injectMask();

        return match ($this->getOperator()) {
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
