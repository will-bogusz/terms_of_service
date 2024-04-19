<?php

declare(strict_types=1);

namespace OCA\TermsOfService\Listener;

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

    public function __construct(ISession $session, IConfig $config, IGroupManager $groupManager, SignatoryMapper $signatoryMapper, LoggerInterface $logger) {
        $this->logger = $logger;
        $this->session = $session;
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->signatoryMapper = $signatoryMapper;
    }

    // we may optionally decide to exclude certain user groups from having their ToS signatures reset
    private function isExcludedUser(IUser $user): bool {
        // pull from the admin settings
        $excludedGroups = $this->config->getAppValue(Application::APPNAME, 'excluded_groups', []);
        $this->logger->error('Excluded groups: ' . json_encode($excludedGroups), ['extra_context' => 'fetching excluded groups']);
        $excludedGroups = array_filter(array_map('trim', explode(',', $excludedGroups)));
        $this->logger->error('Filtered excluded groups: ' . json_encode($excludedGroups), ['extra_context' => 'filtering excluded groups']);

        if (empty($excludedGroups)) {
            return false;
        }

        $userGroups = $this->groupManager->getUserGroupIds($user);
        return array_intersect($excludedGroups, $userGroups) !== [];
    }

    public function handle(Event $event): void {
        // check if the feature to show on every login is enabled
        $this->logger->error('tos_on_every_login value: ' . $this->config->getAppValue(Application::APPNAME, 'tos_on_every_login', '0'), ['extra_context' => 'checking tos_on_every_login']);
        if ($this->config->getAppValue(Application::APPNAME, 'tos_on_every_login', '0') === '1') {
            $this->logger->error('tos_on_every_login is enabled', ['extra_context' => 'tos_on_every_login enabled']);
            // align processing of the signature clear to occur when the user logs in or out
            if ($event instanceof UserLoggedInEvent || $event instanceof UserLoggedOutEvent) {
                // grab the user that performed the action
                $user = $event->getUser();
                if ($this->isExcludedUser($user)) {
                    $this->logger->error('User is excluded from signature reset', ['extra_context' => 'user exclusion']);
                    return;
                }
                $this->signatoryMapper->deleteSignatoriesByUser($user);
            }
        }
    }
}
