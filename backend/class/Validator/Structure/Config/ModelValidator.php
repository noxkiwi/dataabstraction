<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Validator\Structure\Config;

use noxkiwi\validator\Validator\Structure\ConfigValidator;

/**
 * I am the Validator for Model Configuration.
 *
 * @package      noxkiwi\dataabstraction\Validator\Structure\Config
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
class ModelValidator extends ConfigValidator
{
    /**
     * @inheritDoc
     */
    protected function __construct(array $options = [])
    {
        $this->setOptions(
            [
                static::OPTION_KEYS => []
            ]
        );
        parent::__construct($options);
    }
}
