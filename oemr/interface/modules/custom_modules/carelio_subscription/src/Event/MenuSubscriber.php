<?php

namespace OpenEMR\Modules\CarelioSubscription\Event;

use OpenEMR\Menu\MenuEvent;
use stdClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    public const MODULE_PATH = '/interface/modules/custom_modules/carelio_subscription';

    public static function getSubscribedEvents(): array
    {
        return [
            MenuEvent::MENU_UPDATE => ['onMenuUpdate', 0],
        ];
    }

    public function onMenuUpdate(MenuEvent $event): MenuEvent
    {
        $menu = $event->getMenu();

        foreach ($menu as $menuItem) {
            if (($menuItem->menu_id ?? '') === 'admimg' || strtolower((string) ($menuItem->label ?? '')) === 'admin') {
                $subscriptionItem = new stdClass();
                $subscriptionItem->requirement = 0;
                $subscriptionItem->target = 'adm0';
                $subscriptionItem->menu_id = 'carelio_subscription0';
                $subscriptionItem->label = xlt('Carelio Subscription');
                $subscriptionItem->url = self::MODULE_PATH . '/public/index.php';
                $subscriptionItem->children = [];
                $subscriptionItem->acl_req = ['admin', 'users'];
                $subscriptionItem->global_req = [];

                $menuItem->children[] = $subscriptionItem;
                break;
            }
        }

        $event->setMenu($menu);
        return $event;
    }
}
