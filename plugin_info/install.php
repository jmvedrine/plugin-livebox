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
	config::save('nominconnu', 'Oups', 'livebox');

	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
}

function livebox_update() {
	if(config::byKey('nominconnu', 'livebox') == '') {
		config::save('nominconnu', 'Oups', 'livebox');
	}

	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

	$cron = cron::byClassAndFunction('livebox', 'pull');
	if ( is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}

	foreach (eqLogic::byType('livebox') as $eqLogic) {
		if ($eqLogic->getConfiguration('type') == 'box') {
			if ($eqLogic->getConfiguration('autorefresh') == '') {
				log::add('livebox','info','Update configuration equipment ' . $eqLogic->getHumanName() . ' autorefresh : * * * * *');
				$eqLogic->setConfiguration('autorefresh', '* * * * *');
			}
			if ($eqLogic->getConfiguration('protocol') == '') {
				log::add('livebox','info','Update configuration equipment ' . $eqLogic->getHumanName() . ' protocol : http');
				$eqLogic->setConfiguration('protocol', 'http');
			}
			if ($eqLogic->getConfiguration('port') == '') {
				log::add('livebox','info','Update configuration equipment ' . $eqLogic->getHumanName() . ' port : 80');
				$eqLogic->setConfiguration('port', '80');
			}
			$eqLogic->save();
		}
	}
}

function livebox_remove() {
	$cron = cron::byClassAndFunction('livebox', 'pull');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}

	DB::Prepare('DROP TABLE IF EXISTS `livebox_calls`', array(), DB::FETCH_TYPE_ROW);
}
?>