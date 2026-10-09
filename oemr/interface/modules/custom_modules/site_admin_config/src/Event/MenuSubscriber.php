<?php

namespace OpenEMR\Modules\SiteAdmin\Event;

use OpenEMR\Menu\MenuEvent;
use OpenEMR\Modules\SiteAdmin\Security\SiteAdminGuard;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    private const SUBSCRIPTION_MENU_ID = 'carelio_subscription_management';
    private const SUBSCRIPTION_URL = '/interface/modules/custom_modules/carelio_subscription/public/index.php';

    /**
     * Subscribe to OpenEMR's native MenuEvent::MENU_RESTRICT event
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MenuEvent::MENU_UPDATE => ['onMenuUpdate', 0],
            MenuEvent::MENU_RESTRICT => ['onMenuRestrict', -10],
        ];
    }

    /**
     * Add Carelio's tenant administration entry to the OpenEMR main menu.
     *
     * @param MenuEvent $event
     */
    public function onMenuUpdate(MenuEvent $event): void
    {
        $menu = $event->getMenu();

        if ($this->containsMenuId($menu, self::SUBSCRIPTION_MENU_ID) || $this->containsMenuId($menu, 'carelio_subscription0')) {
            return;
        }

        $item = new \stdClass();
        $item->requirement = 0;
        $item->target = 'adm0';
        $item->menu_id = self::SUBSCRIPTION_MENU_ID;
        $item->label = function_exists('xlt') ? xlt('Carelio Subscription') : 'Carelio Subscription';
        $item->url = self::SUBSCRIPTION_URL;
        $item->children = [];
        $item->acl_req = ['admin', 'practice'];
        $item->global_req = [];

        if (!$this->insertAfterAddressBook($menu, $item)) {
            $menu[] = $item;
        }

        $event->setMenu($menu);
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
                $item->url = '/interface/modules/custom_modules/site_admin_config/public/address_book.php';
            }

            if (($item->menu_id ?? null) === self::SUBSCRIPTION_MENU_ID) {
                $item->url = self::SUBSCRIPTION_URL;
            }

            // Recurse into children if present
            if (!empty($item->children) && is_array($item->children)) {
                $this->filterMenuRecursive($item->children);
            }
        }

        $items = array_values($items);
    }

    /**
     * Check nested menu nodes for an existing menu id.
     *
     * @param array $items
     */
    private function containsMenuId(array $items, string $menuId): bool
    {
        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }

            if (($item->menu_id ?? null) === $menuId) {
                return true;
            }

            if (!empty($item->children) && is_array($item->children) && $this->containsMenuId($item->children, $menuId)) {
                return true;
            }
        }

        return false;
    }

    private function insertAfterAddressBook(array &$menu, \stdClass $newItem): bool
    {
        foreach ($menu as $menuItem) {
            if (!is_object($menuItem)) {
                continue;
            }

            if (($menuItem->menu_id ?? null) === 'admimg' || strtolower((string) ($menuItem->label ?? '')) === 'admin') {
                $children = is_array($menuItem->children ?? null) ? $menuItem->children : [];

                foreach ($children as $index => $child) {
                    $label = strtolower(trim((string) ($child->label ?? '')));
                    if ($label === 'address book' || ($child->menu_id ?? null) === 'adb0') {
                        array_splice($children, $index + 1, 0, [$newItem]);
                        $menuItem->children = $children;
                        return true;
                    }
                }

                $children[] = $newItem;
                $menuItem->children = $children;
                return true;
            }
        }

        return false;
    }
}
