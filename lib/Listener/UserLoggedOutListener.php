// lib/Listener/UserLoggedOutListener.php

<?php

namespace OCA\TermsOfService\Listener;

use OCA\TermsOfService\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\ISession;
use OCP\User\Events\UserLoggedOutEvent;

class UserLoggedOutListener implements IEventListener {
    /** @var ISession */
    private $session;

    /** @var IConfig */
    private $config;

    public function __construct(ISession $session, IConfig $config) {
        $this->session = $session;
        $this->config = $config;
    }

    public function handle(Event $event): void {
        if (!($event instanceof UserLoggedOutEvent)) {
            return;
        }

        console.log("Detected a user logging out!");
        // Remove the session key when a user logs out
        $this->session->remove('terms_of_service_agreed');
    }
}