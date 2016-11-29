<?php

namespace Notifier;

interface Service
{
    /**
     * Queries the service to trigger notification
     * alerts. Returns true if there are new
     * notifications to show.
     *
     * @return bool
     */
    public function query();

    /**
     * Marks the most recent notifications as seen.
     */
    public function dismiss();

    /**
     * Returns the name of this service.
     *
     * @return string
     */
    public function name();

    /**
     * Returns an array of pin color values,
     * for a common-anode RGB LED.
     *
     * @return int[]
     */
    public function colors();
}
