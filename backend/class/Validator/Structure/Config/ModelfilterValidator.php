<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Validator\Structure\Config;

use noxkiwi\validator\Validator\StructureValidator;

/**
 * I am the Filter Validator.
 *
 * @package      noxkiwi\dataabstraction\Validator\Structure\Config
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 *
 * @todo         FilterValidator
 */
class ModelfilterValidator extends StructureValidator
{
    /**
     * @inheritDoc
     */
    protected function __construct(array $options = null)
    {
        parent::__construct($options);
        $this->structureDesign = ['field' => 'text_modelname', 'value' => 'text', 'operator' => 'text'];
    }
}
