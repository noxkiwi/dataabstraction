<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Validator\Text;

use noxkiwi\validator\Validator\TextValidator;

/**
 * I am the ModelField Validator.
 *
 * @package      noxkiwi\dataabstraction\Validator\Text
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
class ModelfieldValidator extends TextValidator
{
    /**
     * @inheritDoc
     */
    protected function __construct(array $options = [])
    {
        $this->setOptions(
            [
                static::OPTION_MINLENGTH     => 3,
                static::OPTION_MAXLENGTH     => 64,
                static::OPTION_CHARS_ALLOWED => 'abcdefghijklmnopqrstuvwxyz_'
            ]
        );
        parent::__construct($options);
    }
}
