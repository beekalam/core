<?php
/**
 * ownCloud
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Sergio Bertolin <sbertolin@owncloud.com>
 * @author Phillip Davis <phil@jankaritech.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

require_once 'bootstrap.php';

/**
 * Federation context.
 */
class FederationContext implements Context {

	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * @When /^user "([^"]*)" from server "(LOCAL|REMOTE)" shares "([^"]*)" with user "([^"]*)" from server "(LOCAL|REMOTE)" using the sharing API$/
	 *
	 * @param string $sharerUser
	 * @param string $sharerServer "LOCAL" or "REMOTE"
	 * @param string $sharerPath
	 * @param string $shareeUser
	 * @param string $shareeServer "LOCAL" or "REMOTE"
	 *
	 * @return void
	 */
	public function userFromServerSharesWithUserFromServerUsingTheSharingAPI(
		$sharerUser, $sharerServer, $sharerPath, $shareeUser, $shareeServer
	) {
		if ($shareeServer == "REMOTE") {
			$shareWith
				= "$shareeUser@" . $this->featureContext->getRemoteBaseUrl() . '/';
		} else {
			$shareWith
				= "$shareeUser@" . $this->featureContext->getLocalBaseUrl() . '/';
		}
		$previous = $this->featureContext->usingServer($sharerServer);
		$this->featureContext->createShare(
			$sharerUser, $sharerPath, 6, $shareWith, null, null, null
		);
		$this->featureContext->usingServer($previous);
	}
	
	/**
	 * @Given /^user "([^"]*)" from server "(LOCAL|REMOTE)" has shared "([^"]*)" with user "([^"]*)" from server "(LOCAL|REMOTE)"$/
	 *
	 * @param string $sharerUser
	 * @param string $sharerServer "LOCAL" or "REMOTE"
	 * @param string $sharerPath
	 * @param string $shareeUser
	 * @param string $shareeServer "LOCAL" or "REMOTE"
	 *
	 * @return void
	 */
	public function userFromServerHasSharedWithUserFromServer(
		$sharerUser, $sharerServer, $sharerPath, $shareeUser, $shareeServer
	) {
		$this->userFromServerSharesWithUserFromServerUsingTheSharingAPI(
			$sharerUser, $sharerServer, $sharerPath, $shareeUser, $shareeServer
		);
		$this->featureContext->theHTTPStatusCodeShouldBe('200');
		$this->featureContext->theOCSStatusCodeShouldBe(
			'100', 'Could not share file/folder! message: "' .
				$this->featureContext->getOCSResponseStatusMessage(
					$this->featureContext->getResponse()
				) . '"'
		);
	}

	/**
	 * @When /^user "([^"]*)" from server "(LOCAL|REMOTE)" accepts the last pending share using the sharing API$/
	 *
	 * @param string $user
	 * @param string $server
	 *
	 * @return void
	 */
	public function userFromServerAcceptsLastPendingShareUsingTheSharingAPI($user, $server) {
		$previous = $this->featureContext->usingServer($server);
		$url = "/apps/files_sharing/api/v1/remote_shares/pending";
		$this->featureContext->asUser($user);
		$this->featureContext->theUserSendsToOcsApiEndpointWithBody(
			'GET',
			$url,
			null
		);
		$errorMessage = "Error when requesting $url information";
		$this->featureContext->theHTTPStatusCodeShouldBe('200', $errorMessage);
		$this->featureContext->theOCSStatusCodeShouldBe('100', $errorMessage);
		$share_id = $this->featureContext->getResponseXml()->data[0]->element[0]->id;
		$this->featureContext->theUserSendsToOcsApiEndpointWithBody(
			'POST',
			"/apps/files_sharing/api/v1/remote_shares/pending/{$share_id}",
			null
		);
		$this->featureContext->usingServer($previous);
	}

	/**
	 * @Given /^user "([^"]*)" from server "(LOCAL|REMOTE)" has accepted the last pending share$/
	 *
	 * @param string $user
	 * @param string $server
	 *
	 * @return void
	 */
	public function userFromServerHasAcceptedLastPendingShare($user, $server) {
		$this->userFromServerAcceptsLastPendingShareUsingTheSharingAPI(
			$user, $server
		);
		$this->featureContext->theHTTPStatusCodeShouldBe('200');
		$this->featureContext->theOCSStatusCodeShouldBe('100');
	}

	/**
	 * This will run before EVERY scenario.
	 * It will set the properties for this object.
	 *
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 */
	public function before(BeforeScenarioScope $scope) {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
	}
}
