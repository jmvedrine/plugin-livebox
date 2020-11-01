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
	if ( version_compare(jeedom::version(), "4", "<")) {
		// Copie des templates dans le répertoire du plugin widget pour pouvoir éditer les commandes sans perte de la template associée.
		$srcDir	 = __DIR__ . '/../core/template/dashboard';
		$resuDir = __DIR__ . '/../../widget/core/template/dashboard';
		if (file_exists($resuDir)) { // plugin widget déjà installé
			$file = '/cmd.info.numeric.dureev3.html';
			shell_exec("cp $srcDir$file $resuDir");
			$file = '/cmd.info.string.deroulantv3.html';
			shell_exec("cp $srcDir$file $resuDir");
		}
		$srcDir	 = __DIR__ . '/../core/template/mobile';
		$resuDir = __DIR__ . '/../../widget/core/template/mobile';
		if (file_exists($resuDir)) { // plugin widget déjà installé
			$file = '/cmd.info.numeric.dureev3.html';
			shell_exec("cp $srcDir$file $resuDir");
			$file = '/cmd.info.string.deroulantv3.html';
			shell_exec("cp $srcDir$file $resuDir");
		}
	}
}

function livebox_update() {
	$unknownname = config::byKey('nominconnu','livebox');
	if($unknownname =='') {
		config::save('nominconnu', 'Oups', 'livebox');
	}
	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
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
	if ( version_compare(jeedom::version(), "4", "<")) {
		// Copie des templates dans le répertoire du plugin widget pour pouvoir éditer les commandes sans perte de la template associée.
		$srcDir	 = __DIR__ . '/../core/template/dashboard';
		$resuDir = __DIR__ . '/../../widget/core/template/dashboard';
		if (file_exists($resuDir)) { // plugin widget déjà installé
			$file = '/cmd.info.numeric.dureev3.html';
			shell_exec("cp $srcDir$file $resuDir");
			$file = '/cmd.info.string.deroulantv3.html';
			shell_exec("cp $srcDir$file $resuDir");
		}
		$srcDir	 = __DIR__ . '/../core/template/mobile';
		$resuDir = __DIR__ . '/../../widget/core/template/mobile';
		if (file_exists($resuDir)) { // plugin widget déjà installé
			$file = '/cmd.info.numeric.dureev3.html';
			shell_exec("cp $srcDir$file $resuDir");
			$file = '/cmd.info.string.deroulantv3.html';
			shell_exec("cp $srcDir$file $resuDir");
		}
	}
	foreach (eqLogic::byType('livebox') as $eqLogic) {
		if ($eqLogic->getConfiguration('type') == '') {
			$eqLogic->setConfiguration('type', 'box');
		}
		if ($eqLogic->getConfiguration('type') == 'box') {
		// Suppression du Wifi invité pour les anciennes LB
		if ($eqLogic->getConfiguration('productClass','') !== 'Livebox 4' && $eqLogic->getConfiguration('productClass','') !== 'Livebox Fibre') {
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
		} else {
			// Correction des commandes manquantes avec Livebox 5
			$cmd = $eqLogic->getCmd(null, 'guestwifion');
			if ( ! is_object($cmd) ) {
				$cmd = new liveboxCmd();
				$cmd->setName('Activer wifi invité');
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setType('action');
				$cmd->setSubType('other');
				$cmd->setLogicalId('guestwifion');
				$cmd->save();
			}
			$cmd = $eqLogic->getCmd(null, 'guestwifioff');
			if ( ! is_object($cmd) ) {
				$cmd = new liveboxCmd();
				$cmd->setName('Désactiver wifi invité');
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setType('action');
				$cmd->setSubType('other');
				$cmd->setLogicalId('guestwifioff');
				$cmd->save();
			}
			$cmd = $eqLogic->getCmd(null, 'guestwifistatus');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Etat Wifi Invité');
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setLogicalId('guestwifistatus');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}
		}
		$cmd = $eqLogic->getCmd(null, 'reset');
		if ( is_object($cmd) ) {
			$cmd->remove();
		}
		$cmd = $eqLogic->getCmd(null, 'voipstatus');
		if ( is_object($cmd)) {
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
	DB::Prepare('DROP TABLE IF EXISTS `livebox_calls`', array(), DB::FETCH_TYPE_ROW);
}
?>
