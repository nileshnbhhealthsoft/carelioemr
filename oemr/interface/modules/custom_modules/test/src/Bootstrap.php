<?php

namespace OpenEMR\Modules\SiteAdmin;

use OpenEMR\Modules\SiteAdmin\Event\MenuSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Bootstrap
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Subscribe all module event listeners
     */
    public function subscribeToEvents(): void
    {
        $this->eventDispatcher->addSubscriber(new MenuSubscriber());
    }
}

