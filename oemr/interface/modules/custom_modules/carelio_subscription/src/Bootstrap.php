<?php

namespace OpenEMR\Modules\CarelioSubscription;

use OpenEMR\Modules\CarelioSubscription\Event\MenuSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Bootstrap
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function subscribeToEvents(): void
    {
        $this->eventDispatcher->addSubscriber(new MenuSubscriber());
    }
}
