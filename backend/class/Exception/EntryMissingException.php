<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Exception;

use noxkiwi\core\Exception;

/**
 * I am the Exception that is thrown when an entry should be loaded but the database query did not return data.
 *
 * @package      noxkiwi\dataabstraction\Exception
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class EntryMissingException extends Exception
{
}
