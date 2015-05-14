<?php
namespace Icecave\Siphon\Reader;

use Icecave\Siphon\Atom\AtomResponse;
use Icecave\Siphon\Schedule\ScheduleResponse;

/**
 * Response visitor.
 */
interface ResponseVisitorInterface
{
    /**
     * Visit the given response.
     *
     * @param AtomResponse $response
     *
     * @return mixed
     */
    public function visitAtomResponse(AtomResponse $response);

    /**
     * Visit the given response.
     *
     * @param ScheduleResponse $response
     *
     * @return mixed
     */
    public function visitScheduleResponse(ScheduleResponse $response);
}
