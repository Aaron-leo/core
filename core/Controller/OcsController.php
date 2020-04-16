<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Controller;

use OC\OCS\Result;

class OcsController extends \OCP\AppFramework\OCSController {
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return Result
	 */
	public function getConfig() {
		$xml['version'] = '1.7';
		$xml['website'] = 'ownCloud';
		$xml['host'] = $this->request->getServerHost();
		$xml['contact'] = '';
		$xml['ssl'] = 'false';
		return new Result($xml);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $login
	 * @param string $password
	 *
	 * @return Result
	 */
	public function checkPerson($login, $password) {
		if ($login && $password) {
			if (\OC_User::checkPassword($login, $password)) {
				$xml['person']['personid'] = $login;
				return new Result($xml);
			} else {
				return new Result(null, 102);
			}
		} else {
			return new Result(null, 101);
		}
	}

	/**
	 * read keys
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/getattribute
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Result
	 */
	public function getDefaultAttributes() {
		return $this->getAttribute('');
	}

	/**
	 * read keys
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/getattribute/testy
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $app
	 *
	 * @return Result
	 */
	public function getAppAttributes($app) {
		return $this->getAttribute($app);
	}

	/**
	 * read keys
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/getattribute/testy/123
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $app
	 * @param string|null $key
	 *
	 * @return Result
	 */
	public function getAttribute($app, $key = null) {
		$user = \OC_User::getUser();
		$app = \addslashes(\strip_tags($app));

		if ($key === null) {
			$query = \OCP\DB::prepare('SELECT `key`, `app`, `value`  FROM `*PREFIX*privatedata` WHERE `user` = ? AND `app` = ? ');
			$result = $query->execute([$user, $app]);
		} else {
			$query = \OCP\DB::prepare('SELECT `key`, `app`, `value`  FROM `*PREFIX*privatedata` WHERE `user` = ? AND `app` = ? AND `key` = ? ');
			$result = $query->execute([$user, $app, $key]);
		}

		$xml = [];
		while ($row = $result->fetchRow()) {
			$data= [];
			$data['key']=$row['key'];
			$data['app']=$row['app'];
			$data['value']=$row['value'];
			$xml[] = $data;
		}

		return new Result($xml);
	}

	/**
	 * set a key
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/setattribute/testy/123  --data "value=foobar"
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $app
	 * @param string $key
	 *
	 * @return Result
	 */
	public function setAttribute($app, $key) {
		$user = \OC_User::getUser();
		$app = \addslashes(\strip_tags($app));
		$key = \addslashes(\strip_tags($key));
		$value = (string)$_POST['value'];

		// update in DB
		$query = \OCP\DB::prepare('UPDATE `*PREFIX*privatedata` SET `value` = ?  WHERE `user` = ? AND `app` = ? AND `key` = ?');
		$numRows = $query->execute([$value, $user, $app, $key]);

		if ($numRows === false || $numRows === 0) {
			// store in DB
			$query = \OCP\DB::prepare('INSERT INTO `*PREFIX*privatedata` (`user`, `app`, `key`, `value`)' . ' VALUES(?, ?, ?, ?)');
			$query->execute([$user, $app, $key, $value]);
		}

		return new Result(null, 100);
	}


	/**
	 * delete a key
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/deleteattribute/testy/123 --data "post=1"
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $app
	 * @param string $key
	 *
	 * @return Result
	 */
	public function delete($app, $key) {
		$user = \OC_User::getUser();
		if (!isset($parameters['app']) or !isset($parameters['key'])) {
			//key and app are NOT optional here
			return new Result(null, 101);
		}

		$app = \addslashes(\strip_tags($app));
		$key = \addslashes(\strip_tags($key));

		// delete in DB
		$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*privatedata`  WHERE `user` = ? AND `app` = ? AND `key` = ? ');
		$query->execute([$user, $app, $key]);

		return new Result(null, 100);
	}
}
