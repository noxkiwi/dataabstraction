<?php declare(strict_types = 1);
namespace noxkiwi\dataabstraction\Model\Plugin;

/**
 * I am the Limit Plugin.
 *
 * @package      noxkiwi\dataabstraction\Plugin
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2020 nox.kiwi
 * @version      1.0.0
 * @link         https://nox.kiwi/
 */
final class Limit
{
    /** @var int $limit of datarows to write to the result */
    public int $limit;

    /**
     * Constructor.
     *
     * @param int $limit
     */
    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }
}
