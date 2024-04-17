<?php

declare(strict_types=1);

namespace OCA\TermsOfService\Listener;

use OCA\TermsOfService\Db\Mapper\SignatoryMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ISession;
use OCP\IUser;
use OCP\User\Events\UserLoggedOutEvent;

class UserLoggedOutListener implements IEventListener {

	/** @var ISession */
    private $session;

    /** @var IConfig */
    private $config;

    /** @var IGroupManager */
    private $groupManager;

    public function __construct(ISession $session, IConfig $config, IGroupManager $groupManager) {
        $this->session = $session;
        $this->config = $config;
        $this->groupManager = $groupManager;
    }

    private function isExcludedUser(IUser $user): bool {
        // check if the user belongs to a specific group
        $excludedGroups = ['zaza', 'management'];
        $userGroups = $this->groupManager->getUserGroupIds($user);
        foreach ($excludedGroups as $groupName) {
            if (in_array($groupName, $userGroups)) {
                return true;
            }
        }

        // check if the user's email ends with a specific domain
        $excludedDomains = ['@foxguardsolutions.com'];
        $userEmail = $user->getSystemEMailAddress();
        foreach ($excludedDomains as $domain) {
            if (str_ends_with($userEmail, $domain)) {
                return true;
            }
        }

        return false;
    }

	public function handle(Event $event): void {
		if (!($event instanceof UserLoggedOutEvent)) {
			// Unrelated
			return;
		}

        $user = $event->getUser();

        // check if the user belongs to a specific group or has a specific email domain
        if ($this->isExcludedUser($user)) {
            return;
        }

		$this->signatoryMapper->deleteSignatoriesByUser($user);
	}
}
