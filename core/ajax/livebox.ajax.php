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

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect()) {
		throw new \Exception('401 Unauthorized');
	}

	switch (init('action')){
		case 'addFavorite':
			ajax::success(livebox::addFavorite(init('num'), init('name')));
			break;
		case 'getLinkCalendar':
			if (!isConnect('admin')) {
				throw new Exception(__('401 - Accès non autorisé', __FILE__));
			}
			$livebox = livebox::byId(init('id'));
			if (!is_object($livebox)) {
				throw new Exception(__('Equipement Livebox non trouvé : ', __FILE__) . init('id'));
			}
			try {
				$plugin = plugin::byId('calendar');
				if (!is_object($plugin) || $plugin->isActive() != 1) {
					ajax::success(array());
				}
			} catch (Exception $e) {
				ajax::success(array());
			}
			if (!class_exists('calendar_event')) {
				ajax::success(array());
			}
			$return = array();
			$programmablecmds = $livebox->getCmd('action');
			foreach ($programmablecmds as $livebox_cmd) {
				if (is_object($livebox_cmd)) {
					foreach (calendar_event::searchByCmd($livebox_cmd->getId()) as $event) {
						$return[$event->getId()] = $event;
					}
				}
			}
			ajax::success(utils::o2a($return));
			break;
		case 'syncLivebox':
			if (!isConnect('admin')) {
				throw new \Exception('401 Unauthorized');
			}
			if(init('what'))
				$param=init('what');
			else
				$param=null;
			livebox::syncLivebox($param);
			ajax::success();
			break;
		case 'deleteDisabledEQ':
			if (!isConnect('admin')) {
				throw new \Exception('401 Unauthorized');
			}
			livebox::deleteDisabledEQ(init('what'));
			ajax::success();
			break;
		case 'noMoreIgnore':
			if (!isConnect('admin')) {
				throw new \Exception('401 Unauthorized');
			}
			livebox::noMoreIgnore(init('what'));
			ajax::success();
			break;
	}
	throw new \Exception('Aucune méthode correspondante');
} catch (\Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}
