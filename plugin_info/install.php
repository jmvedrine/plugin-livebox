<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function livebox_install() {
	$cron = cron::byClassAndFunction('livebox', 'pull');
	if ( ! is_object($cron)) {
		$cron = new cron();
		$cron->setClass('livebox');
		$cron->setFunction('pull');
		$cron->setEnable(1);
		$cron->setDeamon(0);
		$cron->setSchedule('* * * * *');
		$cron->save();
	}
}

function livebox_update() {
	$cron = cron::byClassAndFunction('livebox', 'pull');
	if ( ! is_object($cron)) {
		$cron = new cron();
	}
	$cron->setClass('livebox');
	$cron->setFunction('pull');
	$cron->setEnable(1);
	$cron->setDeamon(0);
	$cron->setSchedule('* * * * *');
	$cron->save();

	foreach (eqLogic::byType('livebox') as $eqLogic) {
		// Suppression du Wifi invité pour les anciennes LB
		if ($eqLogic->getConfiguration('productClass','') !== 'Livebox 4' && $eqLogic->getConfiguration('productClass','') !== 'Livebox 5') {
			$cmd = $eqLogic->getCmd(null, 'guestwifion');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $eqLogic->getCmd(null, 'guestwifioff');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $eqLogic->getCmd(null, 'guestwifistatus');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		$eqLogic->save();
	}
}

function livebox_remove() {
	$cron = cron::byClassAndFunction('livebox', 'pull');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
}
?>
