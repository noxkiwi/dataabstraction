<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin;

/**
 * I am the Field plugin.
 *
 * @package      noxkiwi\dataabstraction\Plugin
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class Field
{
    /** @var string Contains the $Field to return */
    public string $fieldName;

    /**
     * Constructor.
     *
     * @param string $fieldName
     */
    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }
}
