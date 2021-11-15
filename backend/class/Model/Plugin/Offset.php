<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin;

/**
 * I am the Offset Plugin.
 *
 * @package      noxkiwi\dataabstraction\Plugin
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class Offset
{
    /** @var int of data rows to offset the result */
    public int $offset;

    /**
     * Constructor.
     *
     * @param int $offset
     */
    public function __construct(int $offset)
    {
        $this->offset = $offset;
    }
}
