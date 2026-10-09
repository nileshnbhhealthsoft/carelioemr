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
        $subscriptionItem = $this->buildSubscriptionItem();
        $normalizedModulePath = strtolower(self::MODULE_PATH);

        $menu = array_values(array_filter($menu, static function ($menuItem) use ($normalizedModulePath): bool {
            $menuId = (string) ($menuItem->menu_id ?? '');
            $label = strtolower((string) ($menuItem->label ?? ''));
            $url = strtolower((string) ($menuItem->url ?? ''));

            if ($menuId === 'carelio_subscription0') {
                return false;
            }

            if (str_contains($url, $normalizedModulePath)) {
                return false;
            }

            return !in_array($label, ['carelio subscription management', 'carelio subscription', 'subscription management'], true);
        }));

        foreach ($menu as $menuItem) {
            if (($menuItem->menu_id ?? '') === 'admimg' || strtolower((string) ($menuItem->label ?? '')) === 'admin') {
                foreach (($menuItem->children ?? []) as $child) {
                    if (($child->menu_id ?? '') === 'carelio_subscription0') {
                        $child->label = xlt('Subscription Management');
                        $child->url = self::MODULE_PATH . '/public/index.php';
                        $event->setMenu($menu);
                        return $event;
                    }
                }

                $menuItem->children[] = $subscriptionItem;
                break;
            }
        }

        $event->setMenu($menu);
        return $event;
    }

    private function buildSubscriptionItem(): stdClass
    {
        $subscriptionItem = new stdClass();
        $subscriptionItem->requirement = 0;
        $subscriptionItem->target = 'adm0';
        $subscriptionItem->menu_id = 'carelio_subscription0';
        $subscriptionItem->label = xlt('Subscription Management');
        $subscriptionItem->url = self::MODULE_PATH . '/public/index.php';
        $subscriptionItem->children = [];
        $subscriptionItem->acl_req = ['admin', 'users'];
        $subscriptionItem->global_req = [];

        return $subscriptionItem;
    }
}
