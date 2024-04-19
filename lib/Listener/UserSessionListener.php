<?php

declare(strict_types=1);

namespace OCA\TermsOfService\Listener;

use OCA\TermsOfService\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ISession;
use OCP\IUser;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedOutEvent;
use OCA\TermsOfService\Db\Mapper\SignatoryMapper;

use Psr\Log\LoggerInterface;

class UserSessionListener implements IEventListener {

    private $session;
    private $config;
    private $groupManager;
    private $signatoryMapper;
    private $logger;

    public function __construct(ISession $session, IConfig $config, IGroupManager $groupManager, SignatoryMapper $signatoryMapper, LoggerInterface $logger) {
        $this->session = $session;
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->signatoryMapper = $signatoryMapper;
        $this->logger = $logger;
    }

    // we may optionally decide to exclude certain user groups from having their ToS signatures reset
    private function isExcludedUser(IUser $user): bool {
        // pull from the admin settings
        $excludedGroups = $this->config->getAppValue(Application::APPNAME, 'excluded_groups', '[]');        
        // decode the JSON string into an array
        $excludedGroups = json_decode($excludedGroups, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Failed to parse excluded groups JSON: ' . json_last_error_msg());
            return false;
        }

        if (empty($excludedGroups)) {
            return false;
        }

        $userGroupIds = $this->groupManager->getUserGroupIds($user);
        $excludedGroupIds = array_column($excludedGroups, 'label');
        $intersectedGroups = array_intersect($excludedGroupIds, $userGroupIds);
        return !empty($intersectedGroups);
    }

    public function handle(Event $event): void {
        // check if the feature to show on every login is enabled
        if ($this->config->getAppValue(Application::APPNAME, 'tos_on_every_login', '0') === '1') {
            $this->logger->info('ToS on every login is enabled');
            // align processing of the signature clear to occur when the user logs in or out
            if ($event instanceof UserLoggedInEvent || $event instanceof UserLoggedOutEvent) {
                // grab the user that performed the action
                $user = $event->getUser();
                if ($this->isExcludedUser($user)) {
                    $this->logger->info('User is excluded from signature reset: ' . json_encode($user->getUID()));
                    return;
                }
                $this->signatoryMapper->deleteSignatoriesByUser($user);
            }
        }
    }
}