<?php

namespace OpenEMR\Modules\SiteAdmin\Event;

use OpenEMR\Events\Core\TemplatePageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BrandingSubscriber implements EventSubscriberInterface
{
    /**
     * Subscribe to OpenEMR page render events
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TemplatePageEvent::RENDER_EVENT => ['onPageRender', 10],
        ];
    }

    /**
     * Whitelabel CarelioEMR About page and branding overrides
     *
     * @param TemplatePageEvent $event
     */
    public function onPageRender(TemplatePageEvent $event): void
    {
        if ($event->getPageName() === 'about_page') {
            $vars = $event->getTwigVariables();

            // 1. Remove external support and user manual links
            $vars['onlineSupportHref'] = '';
            $vars['onlineSupportLink'] = false;
            $vars['userManualHref'] = false;

            // 2. Clear UUID
            $vars['theUUID'] = '';

            // 3. Disable acknowledgments, reviews, and donation links
            $vars['displayAcknowledgements'] = false;
            $vars['displayDonations'] = false;
            $vars['displayReview'] = false;

            // 4. Inject inline CSS to cleanly hide UUID block and external link buttons
            $style = '<style>.uuid-info, .online-support, .user-manual, .ack, .review, .donations { display: none !important; }</style>';
            $vars['additionalInfoContent'] = ($vars['additionalInfoContent'] ?? '') . $style;

            $event->setTwigVariables($vars);
        }
    }
}
