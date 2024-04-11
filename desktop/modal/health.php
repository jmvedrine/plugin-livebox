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

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
$eqLogics = livebox::byType('livebox');
?>

<table class="table table-condensed tablesorter" id="table_healthpiHole">
	<thead>
		<tr>
			<th>{{Module}}</th>
			<th>{{Livebox}}</th>
			<th>{{Adresse MAC}}</th>
			<th>{{IP}}</th>
			<th>{{Type}}</th>
			<th>{{Présent}}</th>
			<th>{{Première connexion}}</th>
			<th>{{Dernière connexion}}</th>
			<th>{{Dernier changement}}</th>
		</tr>
	</thead>
	<tbody>
	 <?php
foreach ($eqLogics as $eqLogic) {
	echo '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
	if ($eqLogic->getConfiguration('type')=='cli') {
		$boxid = $eqLogic->getConfiguration('boxId','');
		$boxEqLogic = livebox::byId($boxid);
		echo '<td><a href="' . $boxEqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $boxEqLogic->getHumanName(true) . '</a></td>';
	} else {
		echo '<td></td>';
	}
	if ($eqLogic->getConfiguration('type')=='box') {
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('BaseMAC') . '</span></td>';
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('ip') . '</span></td>';
	} else {
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('macAddress') . '</span></td>';
		$clicmd = $eqLogic->getCmd('info', 'ip');
		$value = '';
		if (is_object($clicmd)) {
			 $value = $clicmd->execCmd();
		}
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '</span></td>';
	}
	if ($eqLogic->getConfiguration('type')=='box') {
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('productClass') . '</span></td>';
	} else {
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('deviceType') . '</span></td>';
	}
	if ($eqLogic->getConfiguration('type')=='cli') {
		$clicmd = $eqLogic->getCmd('info', 'present');
		$value = '';
		if (is_object($clicmd)) {
			 $value = $clicmd->execCmd() ? 'Oui' : 'Non';
		}
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '</span></td>';
		$clicmd = $eqLogic->getCmd('info', 'firstseen');
		$value = '';
		if (is_object($clicmd)) {
			$value = $clicmd->execCmd();
		}
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '</span></td>';
		$clicmd = $eqLogic->getCmd('info', 'lastlogin');
		$value = '';
		if (is_object($clicmd)) {
			$value = $clicmd->execCmd();
		}
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '</span></td>';
		$clicmd = $eqLogic->getCmd('info', 'lastchanged');
		$value = '';
		if (is_object($clicmd)) {
			$value = $clicmd->execCmd();
		}
		echo '<td><span class="label label-info" style="font-size : 1em;">' . $value . '</span></td>';
	} else {
		echo '<td></td><td></td><td></td><td></td>';
	}
	echo '</tr>';
}
?>
	</tbody>
</table>
