<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin;

/**
 * I am the Order Plugin.
 *
 * @package      noxkiwi\dataabstraction\Plugin
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class Order
{
    /** @var string Contains the $Field to order */
    public string $fieldName;
    /** @var string $direction Contains the direction for the ordering */
    public string $direction;

    /**
     * Constructor.
     *
     * @param string $fieldName
     * @param string $direction
     */
    public function __construct(string $fieldName, string $direction)
    {
        $this->fieldName = $fieldName;
        $this->direction = $direction;
    }
}
