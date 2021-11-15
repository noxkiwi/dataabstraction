<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Interfaces;

use noxkiwi\validator\Validator;

/**
 * I am the interface for all Field instances.
 *
 * @package      noxkiwi\dataabstraction\Interfaces
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
interface FieldInterface
{
    /**
     * I will return the name of the field.
     * @return string
     */
    public function getName(): string;

    /**
     * I will return the matching Validator for the field object.
     * @return \noxkiwi\validator\Validator
     */
    public function getValidator(): Validator;
}

