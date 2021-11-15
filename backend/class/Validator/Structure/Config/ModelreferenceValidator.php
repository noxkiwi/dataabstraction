<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Validator\Structure\Config;

use noxkiwi\validator\Validator\Structure\ConfigValidator;

/**
 * I am the Reference Validator.
 *
 * @package      noxkiwi\dataabstraction\Validator\Structure\Config
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 *
 * @todo         ReferenceValidator
 */
class ModelreferenceValidator extends ConfigValidator
{
    /**
     * @inheritDoc
     */
    protected array $structureDesign = ['model' => 'text_modelname', 'key' => 'text_modelfield', 'display' => 'text'];
}
