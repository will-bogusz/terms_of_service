<?php
namespace OCA\TermsOfService\Cron;

use OC\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserManager;
use OCP\IUserSession;

class LogoutStaleUsers extends TimedJob {
    /** @var IUserManager */
    private IUserManager $userManager;

    /** @var IUserSession */
    private IUserSession $userSession;

    public function __construct(ITimeFactory $timeFactory, IUserManager $userManager, IUserSession $userSession) {
        parent::__construct($timeFactory);
        $this->userManager = $userManager;
        $this->userSession = $userSession;

        // run the job every 30 seconds for testing purposes
        $this->setInterval(30);
    }

    protected function run($argument) {
        // return ALL users
        $users = $this->userManager->search('');
        $currentTime = time();
        $thresholdTime = $currentTime - (1 * 60); // log the user out if they have been logged in for longer than a minute

        foreach ($users as $user) {
            $lastLogin = $user->getLastLogin();
            if ($lastLogin !== null && $lastLogin < $thresholdTime) {
                $this->userSession->logoutUser($user);
            }
        }
    }
}