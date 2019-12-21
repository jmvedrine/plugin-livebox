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

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class livebox extends eqLogic {
	/* * *************************Attributs****************************** */
	public $_cookies;
	public $_contextID;
	public $_version = "2";
	public $_pagesJaunesRequests = 0;  // Nombre de fois où on a interrogé Pages jaunes
    const MAX_PAGESJAUNES = 2;         // Nombre maximum de requêtes à Pages Jaunes
	/* * ***********************Methode static*************************** */

	public static function pull() {
		foreach (self::byType('livebox') as $eqLogic) {
			$eqLogic->scan();
		}
	}

	public static function saveFavorites() {
		$favoris = config::byKey('favorites','livebox', array());
		livebox_calls::deleteAllFavorites();
		foreach ( $favoris as $favori ) {
			if ($favori['callerName'] != '' && $favori['phone'] != '') {
				$caller = new livebox_calls;
				$caller->setCallerName(trim($favori['callerName']));
				$caller->setStartDate(date('Y-m-d H:i:s'));
				$caller->setPhone(trim($favori['phone']));
				$caller->setIsFetched(1);
				$caller->setFavorite(1);
				$caller->save();
			}
		}
		config::remove('favorites','livebox');
	}

	function getCookiesInfo() {
		if ( ! isset($this->_cookies) )
		{
			log::add('livebox','debug','get cookies');
			$cookiefile =  jeedom::getTmpFolder('livebox') . "/livebox.cookie";
			if ( ! defined("COOKIE_FILE") ) {
				define("COOKIE_FILE", $cookiefile);
			}
			$session = curl_init();

			curl_setopt($session, CURLOPT_HTTPHEADER, array(
			   'Content-type: application/x-www-form-urlencoded',
			   'User-Agent: Orange 8.0',
			   'Host: '.$this->getConfiguration('ip'),
			   'Accept: */*',
			   'Content-Length: 0'
			   )
			);
			$statuscmd = $this->getCmd(null, 'state');
			curl_setopt($session, CURLOPT_URL, 'http://'.$this->getConfiguration('ip').'/authenticate?username='.$this->getConfiguration('username').'&password='.$this->getConfiguration('password'));
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_COOKIESESSION, true);
			curl_setopt($session, CURLOPT_COOKIEJAR, COOKIE_FILE);
			curl_setopt($session, CURLOPT_COOKIEFILE, COOKIE_FILE);
			curl_setopt($session, CURLOPT_POST, true);

			$json = curl_exec ($session);
			log::add('livebox','debug','json : '.$json);
			$httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);

			if ( $httpCode != 200 )
			{
				log::add('livebox','debug','version 4');
				$this->_version = "4";
				curl_close($session);
				$session = curl_init();

				$paramInternet = '{"service":"sah.Device.Information","method":"createContext","parameters":{"applicationName":"so_sdkut","username":"'.$this->getConfiguration('username').'","password":"'.$this->getConfiguration('password').'"}}';
				curl_setopt($session, CURLOPT_HTTPHEADER, array(
				   'Content-type: application/x-sah-ws-4-call+json; charset=UTF-8',
				   'User-Agent: Orange 8.0',
				   'Host: '.$this->getConfiguration('ip'),
				   'Accept: */*',
				   'Authorization: X-Sah-Login',
				   'Content-Length: '.strlen($paramInternet)
				   )
				);
				curl_setopt($session, CURLOPT_POSTFIELDS, $paramInternet);
				curl_setopt($session, CURLOPT_URL, 'http://'.$this->getConfiguration('ip').'/ws');
				curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($session, CURLOPT_COOKIESESSION, true);
				curl_setopt($session, CURLOPT_COOKIEJAR, COOKIE_FILE);
				curl_setopt($session, CURLOPT_COOKIEFILE, COOKIE_FILE);
				curl_setopt($session, CURLOPT_POST, true);
				curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
				$json = curl_exec ($session);
				if ( $json === false ) {
					if ( is_object($statuscmd) ) {
						if ($statuscmd->execCmd() != 0) {
							$statuscmd->setCollectDate('');
							$statuscmd->event(0);
						}
					}
					log::add('livebox','error',__('La Livebox ne répond pas à la demande de cookie.',__FILE__)." ".$this->getName()." : ".curl_error ($session));
					throw new Exception(__('La Livebox ne répond pas à la demande de cookie.', __FILE__));
					return false;
				}
			}
			else
			{
				log::add('livebox','debug','version 2');
				$this->_version = "2";
			}
			$info = curl_getinfo($session);
			curl_close($session);
			$obj = json_decode($json);
			if ( ! isset($obj->data->contextID) ) {
				log::add('livebox','debug','unable to get contextID');
				throw new Exception(__('Le compte est incorrect.', __FILE__));
				return false;
			}
			$this->_contextID = $obj->data->contextID;
			if ( ! file_exists ($cookiefile) )
			{
				log::add('livebox','error',__('Le compte est incorrect.',__FILE__));
				if ($statuscmd->execCmd() != 0) {
					$statuscmd->setCollectDate('');
					$statuscmd->event(0);
				}
				throw new Exception(__('Le compte est incorrect.', __FILE__));
				return false;
			}
			if (is_object($statuscmd) && $statuscmd->execCmd() != 1) {
				$statuscmd->setCollectDate('');
				$statuscmd->event(1);
			}
			$file = @fopen($cookiefile, 'r');
			if ( $file === false ) {
				log::add('livebox','debug','unable to read cookie file');
				return false;
			}
			$cookie= fread($file, 100000000);
			fclose($file);
			unlink($cookiefile);

			$cookie1 = explode ("\t",$cookie);
			$cookies = $cookie1[5].'='.$cookie1[6];
			$this->_cookies = trim($cookies);
			log::add('livebox','debug','get cookies done');
		}
		return true;
	}

	function getContext($paramInternet) {
		$httpInternet = array('http' =>
			array(
			 'method' => 'POST',
			 'header' =>	"Host: ".$this->getConfiguration('ip')."\r\n" .
							"Connection: keep-alive\r\n" .
							"Content-Length: ".(strlen($paramInternet))."\r\n" .
							"X-Context: ".$this->_contextID."\r\n" .
							"Authorization: X-Sah ".$this->_contextID."\r\n" .
							"Origin: http://".$this->getConfiguration('ip')."\r\n" .
							"User-Agent: Jeedom plugin\r\n" .
							"Content-type: application/x-sah-ws-4-call+json\r\n" .
							"Accept: */*\r\n" .
							"Accept-Encoding: gzip, deflate, br\r\n" .
							"Accept-Language: fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4\r\n" .
							"Cookie: ".$this->_cookies."; ; sah/contextId=".$this->_contextID,
			 'content' => $paramInternet
			)
		);
		return stream_context_create($httpInternet);
	}

	function logOut() {
		@file_get_contents ('http://'.$this->getConfiguration('ip').'/logout');
	}

	function getPage($page, $option = array()) {
		switch ($page) {
			case "deviceinfo":
				$listpage = array("sysbus/DeviceInfo:get" => "");
				break;
			case "internet":
				$listpage = array("sysbus/NMC:getWANStatus" => "");
				break;
			case "wifilist":
				$listpage = array("sysbus/NeMo/Intf/lan:getIntfs" => '"flag":"wlanradio","traverse":"down"');
				break;
			case "dsl":
				$listpage = array("sysbus/NeMo/Intf/data:getMIBs" => '"mibs":"dsl","flag":"","traverse":"down"');
				break;
			case "voip":
				$listpage = array("sysbus/VoiceService/VoiceApplication:listTrunks" => "");
				break;
			case "tv":
				$listpage = array("sysbus/NMC/OrangeTV:getIPTVStatus" => "");
				break;
			case "wifi":
				$listpage = array("sysbus/NeMo/Intf/lan:getMIBs" => '"mibs":"wlanvap","flag":"","traverse":"down"');
				break;
			case "reboot":
				$listpage = array("sysbus/NMC:reboot" => "");
				break;
			case "wpspushbutton":
				$wifi5 = $this->getConfiguration('wifi5Name','wl1');
				$listpage = array("sysbus/NeMo/Intf/lan:setWLANConfig" => '"mibs":{"wlanvap":{"wl0":{"WPS":{"ConfigMethodsEnabled":"PushButton,Label,Ethernet"}}},"' . $wifi5 . '":{"WPS":{"ConfigMethodsEnabled":"PushButton,Label,Ethernet"}}}',
								"sysbus/NeMo/Intf/wl0/WPS:pushButton" => '',
								"sysbus/NeMo/Intf/$wifi5/WPS:pushButton" => '');
				break;
			case "ring":
				$listpage = array("sysbus/VoiceService/VoiceApplication:ring" => "");
				break;
			case "changewifi":
				if ($this->getConfiguration('productClass','') == 'Livebox 4' || $this->getConfiguration('productClass','') == 'Livebox Fibre') {
					$listpage = array("sysbus/NeMo/Intf/lan:setWLANConfig" => '"mibs":{"penable":{"'.$option['mibs'].'":{"PersistentEnable":'.$option['value'].',"Enable":'.$option['value'].'}}}');
				} else {
					$listpage = array("sysbus/NeMo/Intf/lan:setWLANConfig" => '"mibs":{"penable":{"'.$option['mibs'].'":{"PersistentEnable":'.$option['value'].',"Enable":'.$option['value'].'}}}');
				}
				break;
			case "changeguestwifi":
				if ($option['value']) {
					$guestWifiStatus = 'Enabled';
				} else {
					$guestWifiStatus = 'Disabled';
				}
				$listpage = array("sysbus/NMC/Guest:set" => '"Enable":'.$option['value'].',"Status":"'.$guestWifiStatus.'"');
				break;
			case "guestwifistate":
				$listpage = array("sysbus/NMC/Guest:get" => "");
				break;
			case "devicelist":
				$listpage = array("sysbus/Devices:get" => "");
				break;
			case "listcalls":
				$listpage = array("sysbus/VoiceService.VoiceApplication:getCallList" => "");
				break;
		}
		$statuscmd = $this->getCmd(null, 'state');
		foreach ($listpage as $pageuri => $param) {
			$this->_version = 4;
			if ( $this->_version == '4' )
			{
				$param = str_replace('/', '.', preg_replace('!sysbus/(.*):(.*)!i', '{"service":"$1", "method":"$2", "parameters": {'.$param.'}}', $pageuri));
				$pageuri = 'ws';
			} else {
				$param = '{"parameters":{'.$param.'}}';
			}
			log::add('livebox','debug',$page.' => get http://'.$this->getConfiguration('ip').'/'.$pageuri);
			log::add('livebox','debug',$page.' => param '.$param);
			$content = @file_get_contents('http://'.$this->getConfiguration('ip').'/'.$pageuri, false, $this->getContext($param));
			if ( $content === false ) {
				log::add('livebox','debug',$page.' => reget http://'.$this->getConfiguration('ip').'/'.$pageuri);
				$content = @file_get_contents('http://'.$this->getConfiguration('ip').'/'.$pageuri, false, $this->getContext($param));
			}
			if ( is_object($statuscmd) )
			{
				if ( $content === false ) {
					if ($statuscmd->execCmd() != 0) {
						$statuscmd->setCollectDate('');
						$statuscmd->event(0);
					}
					log::add('livebox','error',__('La Livebox ne répond pas.',__FILE__)." ".$this->getName());
					return false;
				}
					log::add('livebox','debug','content '.$content);
				if (is_object($statuscmd) && $statuscmd->execCmd() != 1) {
					$statuscmd->setCollectDate('');
					$statuscmd->event(1);
				}
			}
			else
			{
				break;
			}
		}
		if ( $content === false ) {
			return false;
		}
		else
		{
			$json = json_decode($content, true);
			if ( $json["status"] == "" && $page !== 'tv' && $page !== 'changewifi')
			{
				log::add('livebox','debug','Demande non traitée par la Livebox. Param: ' .print_r($param,true));
				return false;
			}
			return $json;
		}
	}

	public function preUpdate()
	{
		if ( $this->getIsEnable() )
		{
			return $this->getCookiesInfo();
		}
	}

	public function preSave()
	{
		if ( $this->getIsEnable() )
		{
			$result = $this->getCookiesInfo();
			if ($result) {
				$content = $this->getPage("deviceinfo");
				if ( $content !== false ) {
					if (isset($content['status']['ProductClass'])) {
						$this->setConfiguration('productClass', $content['status']['ProductClass']);
					}
					if (isset($content['status']['Manufacturer'])) {
						$this->setConfiguration('manufacturer', $content['status']['Manufacturer']);
					}
					if (isset($content['status']['ModelName'])) {
						$this->setConfiguration('modelName', $content['status']['ModelName']);
					}
					if (isset($content['status']['SerialNumber'])) {
						$this->setConfiguration('serialNumber', $content['status']['SerialNumber']);
					}
					if (isset($content['status']['HardwareVersion'])) {
						$this->setConfiguration('hardwareVersion', $content['status']['HardwareVersion']);
					}
					if (isset($content['status']['SoftwareVersion'])) {
						$this->setConfiguration('softwareVersion', $content['status']['SoftwareVersion']);
					}
				}
			}
			return $result;
		}
	}

	public function postUpdate() {
		if ( $this->getIsEnable() ) {
			$content = $this->getPage("internet");
			if ( $content !== false ) {
				if ( $content["data"]["LinkType"] == "dsl" || $content["data"]["LinkType"] == "vdsl" ) {
					log::add('livebox','debug','Connexion mode dsl ou vdsl');
					$cmd = $this->getCmd(null, 'debitmontant');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Debit montant');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('debitmontant');
						$cmd->setUnite('Kb/s');
						$cmd->setType('info');
						$cmd->setSubType('numeric');
						$cmd->setIsHistorized(0);
						$cmd->save();
					}

					$cmd = $this->getCmd(null, 'debitdescendant');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Debit descendant');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('debitdescendant');
						$cmd->setUnite('Kb/s');
						$cmd->setType('info');
						$cmd->setSubType('numeric');
						$cmd->setIsHistorized(0);
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'margebruitmontant');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Marge de bruit montant');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('margebruitmontant');
						$cmd->setUnite('dB');
						$cmd->setType('info');
						$cmd->setSubType('numeric');
						$cmd->setIsHistorized(1);
						$cmd->save();
					}

					$cmd = $this->getCmd(null, 'margebruitdescendant');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Marge de bruit descendant');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('margebruitdescendant');
						$cmd->setUnite('dB');
						$cmd->setType('info');
						$cmd->setSubType('numeric');
						$cmd->setIsHistorized(0);
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'lastchange');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Durée de la synchronisation DSL');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('lastchange');
						$cmd->setUnite('s');
						$cmd->setType('info');
						$cmd->setSubType('numeric');
						$cmd->setIsHistorized(1);
						if ( version_compare(jeedom::version(), "4", "<")) {
							$cmd->setTemplate('dashboard', 'dureev3');
							$cmd->setTemplate('mobile', 'dureev3');
						} else {
							$cmd->setTemplate('dashboard', 'livebox::duree');
							$cmd->setTemplate('mobile', 'livebox::duree');
						}
						$cmd->save();
					}

				} elseif ( $content->data->LinkType == "ethernet" ) {
					log::add('livebox','debug','Connexion mode ethernet');
					$cmd = $this->getCmd(null, 'debitmontant');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'debitdescendant');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'margebruitmontant');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'margebruitdescendant');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'lastchange');
					if ( is_object($cmd)) {
						$cmd->remove();
					}
				}
			}
			$content = $this->getPage("wifilist");
			if ( $content !== false ) {
				if ( count($content["status"]) == 1 ) {
					log::add('livebox','debug','Mode Wifi');
					$cmd = $this->getCmd(null, 'wifion');
					if ( ! is_object($cmd) ) {
						$cmd = new liveboxCmd();
						$cmd->setName('Activer wifi');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setType('action');
						$cmd->setSubType('other');
						$cmd->setLogicalId('wifion');
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifioff');
					if ( ! is_object($cmd) ) {
						$cmd = new liveboxCmd();
						$cmd->setName('Désactiver wifi');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setType('action');
						$cmd->setSubType('other');
						$cmd->setLogicalId('wifioff');
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifi2.4on');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'wifi2.4off');
					if ( is_object($cmd)) {
						$cmd->remove();
					}
					$cmd = $this->getCmd(null, 'wifi5on');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'wifi5off');
					if ( is_object($cmd)) {
						$cmd->remove();
					}
					$cmd = $this->getCmd(null, 'wifistatus');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Etat Wifi');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('wifistatus');
						$cmd->setUnite('');
						$cmd->setType('info');
						$cmd->setSubType('binary');
						$cmd->setIsHistorized(0);
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifi5status');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'wifi2.4status');
					if ( is_object($cmd)) {
						$cmd->remove();
					}
				} elseif ( count($content["status"]) == 2 ) {
					log::add('livebox','debug','Mode Wifi 2.4 et 5');
					$cmd = $this->getCmd(null, 'wifi2.4on');
					if ( ! is_object($cmd) ) {
						$cmd = new liveboxCmd();
						$cmd->setName('Activer wifi 2.4G');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setType('action');
						$cmd->setSubType('other');
						$cmd->setLogicalId('wifi2.4on');
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifi5on');
					if ( ! is_object($cmd) ) {
						$cmd = new liveboxCmd();
						$cmd->setName('Activer wifi 5G');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setType('action');
						$cmd->setSubType('other');
						$cmd->setLogicalId('wifi5on');
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifi2.4off');
					if ( ! is_object($cmd) ) {
						$cmd = new liveboxCmd();
						$cmd->setName('Désactiver wifi 2.4G');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setType('action');
						$cmd->setSubType('other');
						$cmd->setLogicalId('wifi2.4off');
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifi5off');
					if ( ! is_object($cmd) ) {
						$cmd = new liveboxCmd();
						$cmd->setName('Désactiver wifi 5G');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setType('action');
						$cmd->setSubType('other');
						$cmd->setLogicalId('wifi5off');
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifioff');
					if ( is_object($cmd)) {
						$cmd->remove();
					}

					$cmd = $this->getCmd(null, 'wifion');
					if ( is_object($cmd)) {
						$cmd->remove();
					}
					$cmd = $this->getCmd(null, 'wifi5status');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Etat Wifi 5G');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('wifi5status');
						$cmd->setUnite('');
						$cmd->setType('info');
						$cmd->setSubType('binary');
						$cmd->setIsHistorized(0);
						$cmd->save();
					}

					$cmd = $this->getCmd(null, 'wifi2.4status');
					if ( ! is_object($cmd)) {
						$cmd = new liveboxCmd();
						$cmd->setName('Etat Wifi 2.4G');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId('wifi2.4status');
						$cmd->setUnite('');
						$cmd->setType('info');
						$cmd->setSubType('binary');
						$cmd->setIsHistorized(0);
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifistatus');
					if ( is_object($cmd)) {
						$cmd->remove();
					}
				}
				$content2 = $this->getPage("deviceinfo");
				if ( $content2 !== false ) {
					if ($content2['status']['ProductClass'] == 'Livebox 4' || $content2['status']['ProductClass'] == 'Livebox Fibre') {
						$cmd = $this->getCmd(null, 'guestwifion');
						if ( ! is_object($cmd) ) {
							$cmd = new liveboxCmd();
							$cmd->setName('Activer wifi invité');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setType('action');
							$cmd->setSubType('other');
							$cmd->setLogicalId('guestwifion');
							$cmd->save();
						}
						$cmd = $this->getCmd(null, 'guestwifioff');
						if ( ! is_object($cmd) ) {
							$cmd = new liveboxCmd();
							$cmd->setName('Désactiver wifi invité');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setType('action');
							$cmd->setSubType('other');
							$cmd->setLogicalId('guestwifioff');
							$cmd->save();
						}
						$cmd = $this->getCmd(null, 'guestwifistatus');
						if ( ! is_object($cmd)) {
							$cmd = new liveboxCmd();
							$cmd->setName('Etat Wifi Invité');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setLogicalId('guestwifistatus');
							$cmd->setUnite('');
							$cmd->setType('info');
							$cmd->setSubType('binary');
							$cmd->setIsHistorized(0);
							$cmd->save();
						}
					} else {
						$cmd = $this->getCmd(null, 'guestwifion');
						if ( is_object($cmd) ) {
							$cmd->remove();
						}
						$cmd = $this->getCmd(null, 'guestwifioff');
						if ( is_object($cmd) ) {
							$cmd->remove();
						}
						$cmd = $this->getCmd(null, 'guestwifistatus');
						if ( is_object($cmd) ) {
							$cmd->remove();
						}
					}
				}
			}


			$cmd = $this->getCmd(null, 'numerotelephone');
			if ( is_object($cmd)) {
				$cmd->remove();
			}
			$content = $this->getPage("voip");
			if ( $content !== false ) {
				log::add('livebox','debug','Mode VOIP');
				setlocale(LC_TIME, 'fr_FR.utf8','fra');
				if ( isset($content["status"]) ) {
					log::add('livebox','debug','Mode VOIP actif');
					foreach ( $content["status"] as $voip ) {
						if ( ! isset($voip["signalingProtocol"]) ) {
							$voip["signalingProtocol"] = strstr($voip["name"], "-", true);
						}
						if ( strtolower($voip["trunk_lines"]["0"]["enable"]) == "enabled" ) {
							log::add('livebox','debug','Mode VOIP '.$voip["signalingProtocol"].' actif');
							$cmd = $this->getCmd(null, 'voipstatus'.$voip["signalingProtocol"]);
							if ( ! is_object($cmd)) {
								$cmd = new liveboxCmd();
								$cmd->setName('Etat VoIP '.$voip["signalingProtocol"]);
								$cmd->setEqLogic_id($this->getId());
								$cmd->setLogicalId('voipstatus'.$voip["signalingProtocol"]);
								$cmd->setUnite('');
								$cmd->setType('info');
								$cmd->setSubType('binary');
								$cmd->setIsHistorized(0);
								$cmd->setIsVisible(1);
								$cmd->save();
							}
							$cmd = $this->getCmd(null, 'numerotelephone'.$voip["signalingProtocol"]);
							if ( ! is_object($cmd)) {
								$cmd = new liveboxCmd();
								$cmd->setName('Numero de telephone '.$voip["signalingProtocol"]);
								$cmd->setEqLogic_id($this->getId());
								$cmd->setLogicalId('numerotelephone'.$voip["signalingProtocol"]);
								$cmd->setUnite('');
								$cmd->setType('info');
								$cmd->setSubType('string');
								$cmd->setIsHistorized(0);
								$cmd->setIsVisible(1);
								$cmd->save();
							}
						} else {
							log::add('livebox','debug','Mode VOIP '.$voip["signalingProtocol"].' inactif');
							$cmd = $this->getCmd(null, 'voipstatus'.$voip["signalingProtocol"]);
							if ( is_object($cmd)) {
								$cmd->remove();
							}
							$cmd = $this->getCmd(null, 'numerotelephone'.$voip["signalingProtocol"]);
							if ( is_object($cmd)) {
								$cmd->remove();
							}
						}
					}
				} else {
					log::add('livebox','debug','Mode VOIP inactif');
				}
				$cmd = $this->getCmd(null, 'missedcallsnumber');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName("Nombre des appels manqués");
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('missedcallsnumber');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(0);
					$cmd->setTemplate('dashboard', 'line');
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'incallsnumber');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName("Nombre des appels entrants");
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('incallsnumber');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(0);
					$cmd->setTemplate('dashboard', 'line');
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'outcallsnumber');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName("Nombre des appels sortants");
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('outcallsnumber');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(0);
					$cmd->setTemplate('dashboard', 'line');
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'totalcallsnumber');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName("Nombre total des appels");
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('totalcallsnumber');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(0);
					$cmd->setIsVisible(0);
					$cmd->setTemplate('dashboard', 'line');
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'outcallstable');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName('Liste des appels sortants');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('outcallstable');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('string');
					$cmd->setIsVisible(0);
					$cmd->setIsHistorized(0);
					if ( version_compare(jeedom::version(), "4", "<")) {
						$cmd->setTemplate('dashboard', 'deroulantv3');
						$cmd->setTemplate('mobile', 'deroulantv3');
					} else {
						$cmd->setTemplate('dashboard', 'livebox::deroulant');
						$cmd->setTemplate('mobile', 'livebox::deroulant');
					}
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'incallstable');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName('Liste des appels entrants');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('incallstable');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('string');
					$cmd->setIsVisible(0);
					$cmd->setIsHistorized(0);
					if ( version_compare(jeedom::version(), "4", "<")) {
						$cmd->setTemplate('dashboard', 'deroulantv3');
						$cmd->setTemplate('mobile', 'deroulantv3');
					} else {
						$cmd->setTemplate('dashboard', 'livebox::deroulant');
						$cmd->setTemplate('mobile', 'livebox::deroulant');
					}
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'missedcallstable');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName('Liste des appels manqués');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('missedcallstable');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('string');
					$cmd->setIsVisible(0);
					$cmd->setIsHistorized(0);
					if ( version_compare(jeedom::version(), "4", "<")) {
						$cmd->setTemplate('dashboard', 'deroulantv3');
						$cmd->setTemplate('mobile', 'deroulantv3');
					} else {
						$cmd->setTemplate('dashboard', 'livebox::deroulant');
						$cmd->setTemplate('mobile', 'livebox::deroulant');
					}
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'callstable');
				if ( ! is_object($cmd)) {
					$cmd = new liveboxCmd();
					$cmd->setName('Liste des appels');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('callstable');
					$cmd->setUnite('');
					$cmd->setType('info');
					$cmd->setSubType('string');
					$cmd->setIsHistorized(0);
					if ( version_compare(jeedom::version(), "4", "<")) {
						$cmd->setTemplate('dashboard', 'deroulantv3');
						$cmd->setTemplate('mobile', 'deroulantv3');
					} else {
						$cmd->setTemplate('dashboard', 'livebox::deroulant');
						$cmd->setTemplate('mobile', 'livebox::deroulant');
					}
					$cmd->save();
				}
			}

			$cmd = $this->getCmd(null, 'updatetime');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Dernier refresh');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('updatetime');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('string');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}
			$cmd = $this->getCmd(null, 'reboot');
			if ( ! is_object($cmd) ) {
				$cmd = new liveboxCmd();
				$cmd->setName('Reboot');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setType('action');
				$cmd->setSubType('other');
				$cmd->setLogicalId('reboot');
				$cmd->save();
			}
			$cmd = $this->getCmd(null, 'ring');
			if ( ! is_object($cmd) ) {
				$cmd = new liveboxCmd();
				$cmd->setName('Sonner');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setType('action');
				$cmd->setSubType('other');
				$cmd->setLogicalId('ring');
				$cmd->save();
			}

			$cmd = $this->getCmd(null, 'wpspushbutton');
			if ( ! is_object($cmd) ) {
				$cmd = new liveboxCmd();
				$cmd->setName('WPS Push Button');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setType('action');
				$cmd->setSubType('other');
				$cmd->setLogicalId('wpspushbutton');
				$cmd->save();
			}

			$cmd = $this->getCmd(null, 'state');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Etat');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('state');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}
			$cmd = $this->getCmd(null, 'uptime');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Durée de fonctionnement');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('uptime');
				$cmd->setUnite('s');
				$cmd->setType('info');
				$cmd->setSubType('numeric');
				if ( version_compare(jeedom::version(), "4", "<")) {
					$cmd->setTemplate('dashboard', 'dureev3');
					$cmd->setTemplate('mobile', 'dureev3');
				} else {
					$cmd->setTemplate('dashboard', 'livebox::duree');
					$cmd->setTemplate('mobile', 'livebox::duree');
				}
				$cmd->setIsHistorized(0);
				$cmd->save();
			}
			$cmd = $this->getCmd(null, 'linkstate');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Etat synchro');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('linkstate');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}

			$cmd = $this->getCmd(null, 'connectionstate');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Etat connexion');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('connectionstate');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}

			$cmd = $this->getCmd(null, 'tvstatus');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Etat TV');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('tvstatus');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('binary');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}

			$cmd = $this->getCmd(null, 'ipwan');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('IP Wan');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('ipwan');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('string');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}

			$cmd = $this->getCmd(null, 'devicelist');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('Liste des équipements');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('devicelist');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('string');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}

			$cmd = $this->getCmd(null, 'ipv6wan');
			if ( ! is_object($cmd)) {
				$cmd = new liveboxCmd();
				$cmd->setName('IPv6 Wan');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId('ipv6wan');
				$cmd->setUnite('');
				$cmd->setType('info');
				$cmd->setSubType('string');
				$cmd->setIsHistorized(0);
				$cmd->save();
			}
			$this->refreshInfo();
			$this->logOut();
		}
	}

	public function scan() {
		if ( $this->getIsEnable() ) {
			if ( $this->getCookiesInfo() ) {
				$this->refreshInfo();
				$this->logOut();
			}
		}
	}

	function refreshInfo() {
		$content = $this->getPage('deviceinfo');
		if ( $content !== false ) {
			$eqLogic_cmd = $this->getCmd(null, 'uptime');
			$this->checkAndUpdateCmd('uptime', $content["status"]["UpTime"]);
		}
		$content = $this->getPage("internet");
		if ( $content !== false ) {
			$eqLogic_cmd = $this->getCmd(null, 'linkstate');
			if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["data"]["LinkState"])) {
				log::add('livebox','debug','Maj linkstate');
				$eqLogic_cmd->setCollectDate('');
				$eqLogic_cmd->event($content["data"]["LinkState"]);
			}
			$eqLogic_cmd = $this->getCmd(null, 'connectionstate');
			if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["data"]["ConnectionState"])) {
				log::add('livebox','debug','Maj connectionstate');
				$eqLogic_cmd->setCollectDate('');
				$eqLogic_cmd->event($content["data"]["ConnectionState"]);
			}
			$eqLogic_cmd = $this->getCmd(null, 'ipwan');
			if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["data"]["IPAddress"])) {
				log::add('livebox','debug','Maj ipwan');
				$eqLogic_cmd->setCollectDate('');
				$eqLogic_cmd->event($content["data"]["IPAddress"]);
			}
			$eqLogic_cmd = $this->getCmd(null, 'ipv6wan');
			if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["data"]["IPv6Address"])) {
				log::add('livebox','debug','Maj ipv6wan');
				$eqLogic_cmd->setCollectDate('');
				$eqLogic_cmd->event($content["data"]["IPv6Address"]);
			}
			if ( $content["data"]["LinkType"] == "dsl" || $content["data"]["LinkType"] == "vdsl" ) {
				$content = $this->getPage("dsl");
				if ( $content !== false ) {
					$eqLogic_cmd = $this->getCmd(null, 'debitmontant');
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["dsl"]["dsl0"]["UpstreamCurrRate"])) {
						log::add('livebox','debug','Maj debitmontant');
					}

					$eqLogic_cmd->setCollectDate('');
					$eqLogic_cmd->event($content["status"]["dsl"]["dsl0"]["UpstreamCurrRate"]);

					$eqLogic_cmd = $this->getCmd(null, 'debitdescendant');
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["dsl"]["dsl0"]["DownstreamCurrRate"])) {
						log::add('livebox','debug','Maj debitdescendant');
					}

					$eqLogic_cmd->setCollectDate('');
					$eqLogic_cmd->event($content["status"]["dsl"]["dsl0"]["DownstreamCurrRate"]);

					$eqLogic_cmd = $this->getCmd(null, 'margebruitmontant');
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["dsl"]["dsl0"]["UpstreamNoiseMargin"]/10)) {
						log::add('livebox','debug','Maj margebruitmontant');
					}

					$eqLogic_cmd->setCollectDate('');
					$eqLogic_cmd->event($content["status"]["dsl"]["dsl0"]["UpstreamNoiseMargin"]/10);
					$eqLogic_cmd = $this->getCmd(null, 'margebruitdescendant');
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["dsl"]["dsl0"]["DownstreamNoiseMargin"])/10) {
						log::add('livebox','debug','Maj margebruitdescendant');
					}
					$eqLogic_cmd->setCollectDate('');
					$eqLogic_cmd->event($content["status"]["dsl"]["dsl0"]["DownstreamNoiseMargin"]/10);

					$eqLogic_cmd = $this->getCmd(null, 'lastchange');
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["dsl"]["dsl0"]["LastChange"])) {
						log::add('livebox','debug','Maj lastchange');
					}
					$eqLogic_cmd->setCollectDate('');
					$eqLogic_cmd->event($content["status"]["dsl"]["dsl0"]["LastChange"]);

			}
		}
		$content = $this->getPage("voip");
		if ( $content !== false ) {
			foreach ( $content["status"] as $voip ) {
				if ( ! isset($voip["signalingProtocol"]) ) {
					$voip["signalingProtocol"] = strstr($voip["name"], "-", true);
				}

					$eqLogic_cmd = $this->getCmd(null, 'voipstatus'.$voip["signalingProtocol"]);
					if (is_object($eqLogic_cmd) && $eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($voip["trunk_lines"]["0"]["status"])) {
						log::add('livebox','debug','Maj voipstatus '.$voip["signalingProtocol"]);
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($voip["trunk_lines"]["0"]["status"]);
					}
					$eqLogic_cmd = $this->getCmd(null, 'numerotelephone'.$voip["signalingProtocol"]);
					if (is_object($eqLogic_cmd) && $eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($voip["trunk_lines"]["0"]["directoryNumber"])) {
						log::add('livebox','debug','Maj numerotelephone '.$voip["signalingProtocol"]);
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($voip["trunk_lines"]["0"]["directoryNumber"]);
					}
				}
			}
		}
		$content = $this->getPage("tv");
		if ( $content !== false ) {
			$eqLogic_cmd = $this->getCmd(null, 'tvstatus');
			if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["data"]["IPTVStatus"])) {
				log::add('livebox','debug','Maj tvstatus');
				$eqLogic_cmd->setCollectDate('');
				$eqLogic_cmd->event($content["data"]["IPTVStatus"]);
			}
		}
		$content = $this->getPage("wifilist");
		if ( $content !== false ) {
			if ( count($content["status"]) == 1 ) {
				$content = $this->getPage("wifi");
				if ( $content !== false ) {
					$eqLogic_cmd = $this->getCmd(null, 'wifistatus');
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["wlanvap"]["wl0"]["VAPStatus"])) {
						log::add('livebox','debug','Maj wifistatus');
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($content["status"]["wlanvap"]["wl0"]["VAPStatus"]);
					}
				}
			} elseif ( count($content["status"]) == 2 ) {
				$content = $this->getPage("wifi");
				if ( $content !== false ) {
					$eqLogic_cmd = $this->getCmd(null, 'wifi2.4status');
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["wlanvap"]["wl0"]["VAPStatus"])) {
						log::add('livebox','debug','Maj wifi2.4status');
						$eqLogic_cmd->setCollectDate('');
						$eqLogic_cmd->event($content["status"]["wlanvap"]["wl0"]["VAPStatus"]);
					}
					$eqLogic_cmd = $this->getCmd(null, 'wifi5status');
					if (isset($content["status"]["wlanvap"]["eth6"])) {
						// Livebox 4.
						$this->setConfiguration('wifi5Name', 'eth6');
						$statusvalue = $content["status"]["wlanvap"]["eth6"]["VAPStatus"];
					} else if (isset($content["status"]["wlanvap"]["eth4"])) {
						// Livebox 5.
						$this->setConfiguration('wifi5Name', 'eth4');
						$statusvalue = $content["status"]["wlanvap"]["eth4"]["VAPStatus"];
					} else {
						// Livebox Play.
						$this->setConfiguration('wifi5Name', 'wl1');
						$statusvalue = $content["status"]["wlanvap"]["wl1"]["VAPStatus"];
					}
					if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($statusvalue)) {
							log::add('livebox','debug','Maj wifi5status');
							$eqLogic_cmd->setCollectDate('');
							$eqLogic_cmd->event($statusvalue);
					}
				}
			}
		}
		$content = $this->getPage("listcalls");
		if ( $content !== false ) {
			$callsTable = '';
			$outCallsTable = '';
			$missedCallsTable = '';
			$inCallsTable = '';
			$totalCallsNumber = 0;
			$outCallsNumber = 0;
			$inCallsNumber = 0;
			$missedCallsNumber = 0;
			$setting = config::byKey('minincallduration','livebox', 5);
			$usepagesjaunes = config::byKey('pagesjaunes','livebox', false);
			$calls = array();

			if ( isset($content["status"]) ) {
				foreach ( $content["status"] as $call ) {
					$totalCallsNumber++;
					$Call_numero = $call["remoteNumber"];
					$Call_duree = $call["duration"];
					$ts = strtotime($call["startTime"]);
					// log::add('livebox','warning',$call["startTime"]." ==> ".date("Y-m-d H:i:s",$ts));
					// Appel entrant
					if ( $call["callDestination"] == "local" ) {
						$in = 1;
						// Appel manqué ou trop court (considéré comme manqué).
						if($call["callType"] == "missed" || $Call_duree < $setting) {
							$missedCallsNumber++;
							$missed = 1;
							$icon = '<i class="icon icon_red techno-phone69"</i>';
						} else if($call["callType"] == "succeeded") {
							$missed = 0;
							$inCallsNumber++;
							$icon = '<i class="icon techno-phone3"</i>';
						} else {
							$missed = -1;
						}
					} else if($call["callOrigin"] == "local") {
						// Appel sortant
						$outCallsNumber++;
						$in = 0;
						$missed = 0;
						$icon = '<i class="icon icon_green techno-phone2"</i>';
					}
					$calls[] = array("timestamp" => $ts, "num" => $Call_numero, "duree" => $Call_duree, "in" => $in, "missed" => $missed, "icon" => $icon);

				}
				if(count($calls) > 1) {
					arsort($calls);
				}
			}

			log::add('livebox','debug','Appels '.print_r($calls, true));
			log::add('livebox','debug','Nombre appels manqués '.$missedCallsNumber);
			$this->checkAndUpdateCmd('missedcallsnumber', $missedCallsNumber);
			log::add('livebox','debug','Nombre appels entrants '.$inCallsNumber);
			$this->checkAndUpdateCmd('incallsnumber', $inCallsNumber);
			log::add('livebox','debug','Nombre appels sortants '.$outCallsNumber);
			$this->checkAndUpdateCmd('outcallsnumber', $outCallsNumber);
			log::add('livebox','debug','Nombre total appels '.$totalCallsNumber);
			$this->checkAndUpdateCmd('totalcallsnumber', $totalCallsNumber);

			$tabstyle = "<style> th, td { padding : 2px !important; } </style><style> th { text-align:center; } </style><style> td { text-align:right; } </style>";

			//	Tous les appels
			if ($totalCallsNumber > 0) {
				$callsTable = "$tabstyle<table border=1>";
				if($usepagesjaunes == 1) {
					$callsTable .=	"<tr><th>Nom</th><th>Numéro</th><th>Date</th><th>Durée</th><th></th></tr>";
				} else {
					$callsTable .=	"<tr><th>Numéro</th><th>Date</th><th>Durée</th><th></th></tr>";
				}
				foreach($calls as $key => $call) {
					if($usepagesjaunes == 1) {
						$callerName = trim($this->getCallerName($call["num"]));
						log::add('livebox','debug','Caller name returned by getCallerName $'.$callerName.'$');
						$callsTable .= "<tr><td>" . $callerName ."</td><td>".$this->fmt_numtel($call["num"])."</td><td>".$this->fmt_date($call["timestamp"])."</td><td>".$this->fmt_duree($call["duree"])."</td><td>".$call["icon"]."</td></tr>";
					} else {
					$callsTable .= "<tr><td>".$this->fmt_numtel($call["num"])."</td><td>".$this->fmt_date($call["timestamp"])."</td><td>".$this->fmt_duree($call["duree"])."</td><td>".$call["icon"]."</td></tr>";
				}
				}
				$callsTable .= "</table>";
			}

			//	Appels sortants
			if ($outCallsNumber > 0) {
				$outCallsTable = "$tabstyle<table border=1>";
				$outCallsTable .= "<tr><th>Numéro</th><th>Date</th><th>Durée</th></tr>";
				foreach($calls as $call) {
					if($call["in"] == 0) {
						$outCallsTable .= "<tr><td>".$this->fmt_numtel($call["num"])."</td><td>".$this->fmt_date($call["timestamp"])."</td><td>".$this->fmt_duree($call["duree"])."</td></tr>";
					}
				}
				$outCallsTable .= "</table>";
			}

			// Appels manqués
			if ($missedCallsNumber > 0) {
				$missedCallsTable =	 "$tabstyle<table border=1>";
				$missedCallsTable .=  "<tr><th>Numéro</th><th>Date</th></tr>";
				foreach($calls as $call) {
					if($call["missed"] == 1) {
						$missedCallsTable .=  "<tr><td>".$this->fmt_numtel($call["num"])."</td><td>".$this->fmt_date($call["timestamp"])."</td></tr>";
					}
				}
				$missedCallsTable .=  "</table>";
			}

			// Appels recus
			if ($inCallsNumber > 0) {
				$inCallsTable = "$tabstyle<table border=1>";
				$inCallsTable .= "<tr><th>Numéro</th><th>Date</th><th>Durée</th></tr>";
				foreach($calls as $call) {
					if($call["in"] == 1 && $call["missed"] == 0) {
						$inCallsTable .= "<tr><td>".$this->fmt_numtel($call["num"])."</td><td>".$this->fmt_date($call["timestamp"])."</td><td>".$this->fmt_duree($call["duree"])."</td></tr>";
					}
				}
				$inCallsTable .= "</table>";
			}
		}

		$this->checkAndUpdateCmd('callstable', $callsTable);
		log::add('livebox','debug','Appels entrants'.$inCallsTable);
		$this->checkAndUpdateCmd('incallstable', $inCallsTable);
		log::add('livebox','debug','Appels sortants'.$outCallsTable);
		$this->checkAndUpdateCmd('outcallstable', $outCallsTable);
		log::add('livebox','debug','Appels manqués'.$missedCallsTable);
		$this->checkAndUpdateCmd('missedcallstable', $missedCallsTable);

		$content = $this->getPage("devicelist");
		if ( $content !== false ) {
			$eqLogic_cmd = $this->getCmd(null, 'devicelist');
			$devicelist = array();
			if ( isset($content["status"]) )
			{
				foreach ( $content["status"] as $equipement ) {
					if ( $equipement["Active"] && isset($equipement["IPAddressSource"]) && $equipement["IPAddressSource"] == "DHCP" )
					{
						array_push($devicelist, $equipement["Name"]);
					}
				}
			}
			if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue(join(', ', $devicelist))) {
				log::add('livebox','debug','Maj devicelist');
				$eqLogic_cmd->setCollectDate('');
				$eqLogic_cmd->event(join(', ', $devicelist));
			}
		}
		if ($this->getConfiguration('productClass','') == 'Livebox 4' || $this->getConfiguration('productClass','') == 'Livebox Fibre') {
			$content = $this->getPage("guestwifistate");
			if ( $content !== false ) {
				log::add('livebox','debug', 'Gest Wifi ' . print_r($content, true));
				$eqLogic_cmd = $this->getCmd(null, 'guestwifistatus');
				if ($eqLogic_cmd->execCmd() != $eqLogic_cmd->formatValue($content["status"]["Enable"])) {
					log::add('livebox','debug','Maj wifi invité status');
					$eqLogic_cmd->setCollectDate('');
					$eqLogic_cmd->event($content["status"]["Enable"]);
				}
			}
		}

		$eqLogic_cmd = $this->getCmd(null, 'updatetime');
		$eqLogic_cmd->event(date("d/m/Y H:i",(time())));
	}

	function getPjCallerName($num) {
		$oups = 0;
		$opts = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=>array("Host: www.pagesjaunes.fr",
				"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0",
				"Accept: text/html,application/xhtml+xml,application/xml;",
				"Accept-Language: fr,fr-FR",
				"Accept-Encoding: gzip, deflate",
				"Referer: https://www.pagesjaunes.fr/",
				"Content-Type: application/x-www-form-urlencoded",
				"Connection: keep-alive",
				"Upgrade-Insecure-Requests: 1",
				"Cache-Control: max-age=0"
			  )
			)
		);
		$context = stream_context_create($opts);

		$pj = file_get_contents("https://www.pagesjaunes.fr/annuaireinverse/recherche?quoiqui=".$num,false,$context);
		$pj = zlib_decode($pj);
		if ( $pj !== false ) {
			$oups = strpos($pj,"Oups… nous");
			if ($oups > 0) {
				return "Oups";
			}
			// echo $pj;
			$previousValue = libxml_use_internal_errors(true);
			$dom = new DomDocument;
			$dom->loadHTML($pj);
			$xpath = new DomXPath($dom);
			$nodes = $xpath->query(".//a[@class='denomination-links pj-lb pj-link']/text()");
			libxml_clear_errors();
			libxml_use_internal_errors($previousValue);
			if (!is_null($nodes) && $nodes->length > 0) {
				return strip_tags($nodes[0]->nodeValue);
			} else {
				return '';
			}
		}
	}

	function normalizePhone($num) {
		if(is_numeric($num)) {
			if(strlen($num) == 12 && substr($num,0,3) == '033') {
				$num = '0' . substr($num,3);
			}  else if(strlen($num) == 11 && substr($num,0,2) == '33') {
				$num = '0' . substr($num,2);
			}
		}
		return $num;
	}

	function getCallerName($num) {
        log::add('livebox','debug','GetCallername count : '.$this->_pagesJaunesRequests);
		$normalizedPhone = $this->normalizePhone($num);
		if (strlen($num) == 0) {
            return 'Anonyme';
        }
		$responses = livebox_calls::searchByPhone($normalizedPhone);
		log::add('livebox','debug','caller : '.print_r($responses, true));
		log::add('livebox','debug','caller count : '.count($responses));
		if (!is_array($responses) || count($responses) ===0) {
			log::add('livebox','debug','caller not stored');
			// Il n'est pas dans la base, nouveau caller.
			$caller = new livebox_calls;
			$caller->setStartDate(date('Y-m-d H:i:s'));
			$caller->setPhone($normalizedPhone);
			$caller->setFavorite(0);
			if ($this->_pagesJaunesRequests < self::MAX_PAGESJAUNES && strlen($num) == 10) {
				log::add('livebox','debug','we fetch the name');
				$this->_pagesJaunesRequests++;
				$callerName = $this->getPjCallerName($normalizedPhone);
				$caller->setCallerName($callerName);
				$caller->setIsFetched(1);
			} else {
				log::add('livebox','debug','store it but not fetched');
				$caller->setCallerName('');
				$caller->setIsFetched(0);
			}
			$caller->save();
		} else {
			// Il est déjà dans la base
			log::add('livebox','debug','caller already stored');
			// On prend le premier retourné car priorité aux favoris et aux plus récents.
			$caller = $responses[0];
			if ($caller->getIsFetched() == 0 && $caller->getFavorite() == 0) {
				log::add('livebox','debug','but it is not fetched and not favorite');
				if ($this->_pagesJaunesRequests < self::MAX_PAGESJAUNES && strlen($num) == 10) {
					log::add('livebox','debug','we fetch the name');
					$this->_pagesJaunesRequests++;
					$callerName = $this->getPjCallerName($normalizedPhone);
					log::add('livebox','debug','response from pages jaunes '.$callerName);
					$caller->setCallerName($callerName);
					$caller->setIsFetched(1);
					log::add('livebox','debug','and we save it');
					$caller->save();
				}
			}
		}
		return $caller->getCallerName();
	}

	function fmt_date($timeStamp) {
		return(ucwords(strftime("%a %d %b %T",$timeStamp)));
	}

	function fmt_duree($duree) {
		$h = floor(((float)$duree)/3600); $m = floor(((float)$duree)/60); $s = $duree%60;
		$fmt = '';
		if($h>0) $fmt .= $h.'h ';
		if($m>0) $fmt .= $m.'mn ';
		$fmt .= $s.'s';
		return($fmt);
	}

	function fmt_numtel($num) {
        if (strlen($num) == 0) {
            return('****');
        }
		if(is_numeric($num)) {
			if(strlen($num) == 12 && substr($num,0,3) == '033') {
				$num = '0' . substr($num,3);
			}
			if(strlen($num) == 10) {
				$fmt = substr($num,0,2) .' '.substr($num,2,2) .' '.substr($num,4,2) .' '.substr($num,6,2) .' '.substr($num,8);
				return("<a target=_blank href=\"https://www.pagesjaunes.fr/annuaireinverse/recherche?quoiqui=".$num."&proximite=0\">".$fmt."</a>");
			} else {
				return("<a target=_blank href=\"https://www.pagesjaunes.fr/annuaireinverse/recherche?quoiqui=".$num."&proximite=0\">".$num."</a>");
			}
		} else {
			return($num);
		}
	}
}

class liveboxCmd extends cmd
{
	/*	   * *************************Attributs****************************** */


	/*	   * ***********************Methode static*************************** */


	/*	   * *********************Methode d'instance************************* */

	/*	   * **********************Getteur Setteur*************************** */
	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
			throw new Exception(__("Equipement désactivé impossible d'exécuter la commande : " . $this->getHumanName(), __FILE__));
		}
		log::add('livebox','debug','get '.$this->getLogicalId());
		$option = array();
		if ($eqLogic->getConfiguration('productClass','') == 'Livebox 4' || $eqLogic->getConfiguration('productClass','') == 'Livebox Fibre') {
			$mibs0 = 'wifi0_bcm';
			$mibs1 = 'wifi0_quan';
		} else {
			$mibs0 = 'wifi0_ath';
			$mibs1 = 'wifi1_ath';
		}
		switch ($this->getLogicalId()) {
			case "reset":
				$page = null;
				break;
			case "reboot":
				$page = "reboot";
				break;
			case "ring":
				$page = "ring";
				break;
			case "wpspushbutton":
				$page = "wpspushbutton";
				break;
			case "wifi2.4on":
				$option = array('mibs' => $mibs0, 'value' => 'true');
				$page = "changewifi";
				break;
			case "wifion":
				$option = array('mibs' => $mibs0, 'value' => 'true');
				$page = "changewifi";
				break;
			case "wifi2.4off":
				$option = array('mibs' => $mibs0, 'value' => 'false');
				$page = "changewifi";
				break;
			case "wifioff":
				$option = array('mibs' => $mibs0, 'value' => 'false');
				$page = "changewifi";
				break;
			case "wifi5on":
				$option = array('mibs' => $mibs1, 'value' => 'true');
				$page = "changewifi";
				break;
			case "wifi5off":
				$option = array('mibs' => $mibs1, 'value' => 'false');
				$page = "changewifi";
				break;
			case "guestwifion":
				$option = array('value' => 'true');
				$page = "changeguestwifi";
				break;
			case "guestwifioff":
				$option = array('value' => 'false');
				$page = "changeguestwifi";
				break;
		}
		if ( $page != null ) {
			$eqLogic->getCookiesInfo();
			$content = $eqLogic->getPage($page, $option);
			if ( $this->getLogicalId() != "reboot" ) {
				$eqLogic->refreshInfo();
				$eqLogic->logOut();
			}
			if ( $this->getLogicalId() != "ring" ) {
				$eqLogic->refreshInfo();
				$eqLogic->logOut();
			}
		} else {
			throw new Exception(__('Commande non implémentée actuellement', __FILE__));
		}
		return true;
	}

	public function formatValue($_value, $_quote = false) {
		if (trim($_value) == '') {
			return '';
		}
		if ($this->getType() == 'info') {
			switch ($this->getSubType()) {
				case 'binary':
					$_value = strtolower($_value);
					if ($_value == 'up') {
						$_value = 1;
					} else if ($_value == 'connected') {
						$_value = 1;
					} else if ($_value == 'bound') {
						$_value = 1;
					} else if ($_value == 'available') {
						$_value = 1;
					} else if ( (is_numeric( intval($_value) ) && intval($_value) > 1) || $_value == 1 ) {
						$_value = 1;
					} else {
					   $_value = 0;
					}
					return $_value;
				case 'string':
					if ( substr($this->getLogicalId(), 0, 15) == 'numerotelephone') {
						if( strlen($_value) > 9 ) {
							 $_value = '0'.substr($_value, -9);
						}
					}
					return $_value;
			}
		}
		return $_value;
	}
}

class livebox_calls {

	/*	   * *************************Attributs****************************** */

	private $id;
	private $callerName;
	private $phone;
	private $startDate;
	private $isFetched;
	private $favorite;
	protected $_changed = false;

	public static function byId($_id) {
		$values = array(
			'id' => $_id,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM livebox_calls
		WHERE id=:id';
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function searchByPhone($_phonenum) {
		$values = array(
			'phone' => $_phonenum,
		);
		log::add('livebox','debug','searchbyphone values ' .print_r($values, true));
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM livebox_calls
		WHERE phone=:phone ORDER BY favorite DESC, startDate DESC';
		log::add('livebox','debug','searchbyphone sql ' .$sql);
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function all() {
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM livebox_calls';
		return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function deleteAllFavorites() {
		$sql = 'DELETE	from `livebox_calls` WHERE favorite = 1';
		$row =	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
	}

	public static function allFavorites() {
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM livebox_calls
		WHERE favorite=1';
		$result = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
		return $result;
	}

	/*	   * *********************Methode d'instance************************* */
	public function save() {
		return DB::save($this);
	}

	public function remove() {
		return DB::remove($this);
	}

	/*	   * **********************Getteur Setteur*************************** */

	public function getId() {
		return $this->id;
	}

	public function setId($_id) {
		$this->_changed = utils::attrChanged($this->_changed,$this->id,$_id);
		$this->id = $_id;
	}

	public function getStartDate() {
		return $this->startDate;
	}

	public function setStartDate($_startDate) {
		$this->_changed = utils::attrChanged($this->_changed,$this->startDate,$_startDate);
		$this->startDate = $_startDate;
	}

	public function getPhone() {
		return $this->phone;
	}

	public function setPhone($_phone) {
		$this->_changed = utils::attrChanged($this->_changed,$this->phone,$_phone);
		$this->phone = $_phone;
	}

	public function getCallerName() {
		return $this->callerName;
	}

	public function setCallerName($_callerName) {
		$this->_changed = utils::attrChanged($this->_changed,$this->callerName,$_callerName);
		$this->callerName = $_callerName;
	}

	public function getIsFetched() {
		return $this->isFetched;
	}

	public function setIsFetched($_isFetched) {
		$this->_changed = utils::attrChanged($this->_changed,$this->isFetched,$_isFetched);
		$this->isFetched = $_isFetched;
	}

	public function getFavorite() {
		return $this->favorite;
	}

	public function setFavorite($_favorite) {
		$this->_changed = utils::attrChanged($this->_changed,$this->favorite,$_favorite);
		$this->favorite = $_favorite;
	}

	public function getChanged() {
		return $this->_changed;
	}

	public function setChanged($_changed) {
		$this->_changed = $_changed;
		return $this;
	}
}
?>
