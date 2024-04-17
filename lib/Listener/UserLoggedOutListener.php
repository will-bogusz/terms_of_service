<?php

declare(strict_types=1);

namespace OCA\TermsOfService\Listener;

use OCA\TermsOfService\Db\Mapper\SignatoryMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserLoggedOutEvent;

class UserLoggedOutListener implements IEventListener {

	/** @var SignatoryMapper */
	private $signatoryMapper;

	public function __construct(SignatoryMapper $signatoryMapper) {
		$this->signatoryMapper = $signatoryMapper;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserLoggedOutEvent)) {
			// Unrelated
			return;
		}

		$this->signatoryMapper->deleteSignatoriesByUser($event->getUser());
	}
}
