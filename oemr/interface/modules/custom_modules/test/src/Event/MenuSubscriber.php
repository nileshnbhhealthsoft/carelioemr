<?php

namespace OpenEMR\Modules\SiteAdmin\Event;

use OpenEMR\Menu\MenuEvent;
use OpenEMR\Modules\SiteAdmin\Security\SiteAdminGuard;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    /**
     * Subscribe to OpenEMR's native MenuEvent::MENU_RESTRICT event
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MenuEvent::MENU_RESTRICT => ['onMenuRestrict', -10],
        ];
    }

    /**
     * Filter menu items for Site Administrator users
     *
     * @param MenuEvent $event
     */
    public function onMenuRestrict(MenuEvent $event): void
    {
        // Only apply restrictions for Site Administrator role; NEVER restrict root admin / super admin
        if (!SiteAdminGuard::isSiteAdmin()) {
            return;
        }

        $menu = $event->getMenu();
        $this->filterMenuRecursive($menu);
        $event->setMenu($menu);
    }

    /**
     * Recursively traverse and filter forbidden menu nodes and rewrite address book target
     *
     * @param array $items
     */
    private function filterMenuRecursive(array &$items): void
    {
        $restrictedLabels = ['system', 'config', 'forms', 'acl', 'modules'];

        foreach ($items as $key => $item) {
            if (!is_object($item)) {
                continue;
            }

            $label = isset($item->label) ? strtolower(trim((string)$item->label)) : '';

            // Check if this item is restricted
            if (in_array($label, $restrictedLabels, true)) {
                unset($items[$key]);
                continue;
            }

            // If Address Book, redirect to module-owned isolated view that excludes root admin
            if ($label === 'address book' || (isset($item->menu_id) && $item->menu_id === 'adb0')) {
                $item->url = '/interface/modules/custom_modules/test/public/address_book.php';
            }

            // Recurse into children if present
            if (!empty($item->children) && is_array($item->children)) {
                $this->filterMenuRecursive($item->children);
            }
        }

        $items = array_values($items);
    }
}

