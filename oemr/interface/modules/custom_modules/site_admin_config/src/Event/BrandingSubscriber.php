<?php

namespace OpenEMR\Modules\SiteAdmin\Event;

use OpenEMR\Events\Core\TemplatePageEvent;
use OpenEMR\Events\Main\Tabs\RenderEvent;
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
            RenderEvent::EVENT_BODY_RENDER_POST => ['onTabsRenderPost', 10],
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

    /**
     * Whitelabel Carelio first-time login product registration modal and main screen dialogs
     *
     * @param RenderEvent $event
     */
    public function onTabsRenderPost(RenderEvent $event): void
    {
        echo <<<'HTML'
<script>
(function() {
    function whitelabelProductRegistration() {
        var modal = document.querySelector('.product-registration-modal');
        if (!modal) return;

        // TreeWalker to recursively replace all occurrences of "OpenEMR" with "Carelio"
        var walker = document.createTreeWalker(modal, NodeFilter.SHOW_TEXT, null, false);
        var node;
        while (node = walker.nextNode()) {
            if (node.nodeValue && node.nodeValue.indexOf('OpenEMR') !== -1) {
                node.nodeValue = node.nodeValue.replace(/OpenEMR/g, 'Carelio');
            }
        }
    }

    if (window.jQuery) {
        $(function() {
            if (typeof registrationTranslations !== 'undefined' && registrationTranslations && registrationTranslations.title) {
                registrationTranslations.title = registrationTranslations.title.replace(/OpenEMR/g, 'Carelio');
            }
            $('.product-registration-modal').on('show.bs.modal shown.bs.modal', function() {
                whitelabelProductRegistration();
            });
            whitelabelProductRegistration();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', whitelabelProductRegistration);
    } else {
        whitelabelProductRegistration();
    }

    var observerSetup = function() {
        var target = document.querySelector('.product-registration-modal');
        if (target) {
            var obs = new MutationObserver(function() {
                obs.disconnect();
                whitelabelProductRegistration();
                obs.observe(target, { childList: true, subtree: true, characterData: true });
            });
            obs.observe(target, { childList: true, subtree: true, characterData: true });
            whitelabelProductRegistration();
        } else {
            setTimeout(observerSetup, 150);
        }
    };
    observerSetup();
})();
</script>
HTML;
    }
}


