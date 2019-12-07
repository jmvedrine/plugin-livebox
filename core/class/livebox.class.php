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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class livebox extends eqLogic {
	/* * *************************Attributs****************************** */
	public $_cookies;
	public $_contextID;
	public $_version = "2";
	/* * ***********************Methode static*************************** */

	public static function pull() {
		foreach (self::byType('livebox') as $eqLogic) {
			$eqLogic->scan();
		}
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
					log::add('livebox','error',__('La livebox ne repond pas a la demande de cookie.',__FILE__)." ".$this->getName()." : ".curl_error ($session));
					throw new Exception(__('La livebox ne repond pas a la demande de cookie.', __FILE__));
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
				if ($this->getConfiguration('productClass','') == 'Livebox 4' || $this->getConfiguration('productClass','') == 'Livebox 5') {
					$wifi5 = 'eth6';
				} else {
					$wifi5 = 'wl1';
				}
				$listpage = array("sysbus/NeMo/Intf/lan:setWLANConfig" => '"mibs":{"wlanvap":{"wl0":{"WPS":{"ConfigMethodsEnabled":"PushButton,Label,Ethernet"}}},"' . $wifi5 . '":{"WPS":{"ConfigMethodsEnabled":"PushButton,Label,Ethernet"}}}',
								"sysbus/NeMo/Intf/wl0/WPS:pushButton" => '',
								"sysbus/NeMo/Intf/$wifi5/WPS:pushButton" => '');
				break;
			case "ring":
				$listpage = array("sysbus/VoiceService/VoiceApplication:ring" => "");
				break;
			case "changewifi":
				if ($this->getConfiguration('productClass','') == 'Livebox 4' || $this->getConfiguration('productClass','') == 'Livebox 5') {
					$listpage = array("sysbus/NeMo/Intf/lan:setWLANConfig" => '"mibs":{"penable":{"'.$option['mibs'].'":{"PersistentEnable":'.$option['value'].',"Enable":'.$option['value'].'}}}');
				} else {
					$listpage = array("sysbus/NeMo/Intf/lan:setWLANConfig" => '"mibs":{"penable":{"'.$option['mibs'].'":{"PersistentEnable":'.$option['value'].',"Enable":'.$option['value'].'}}}');
				}
				break;
			case "changeguestwifi":
				$listpage = array("sysbus/NMC/Guest:set" => '"Enable":'.$option['value']);
				break;
			case "guestwifistate":
				$listpage = array("sysbus/NMC/Guest:get" => "");
				break;
			case "devicelist":
				$listpage = array("sysbus/Devices:get" => "");
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
					log::add('livebox','error',__('La livebox ne repond pas.',__FILE__)." ".$this->getName());
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
				log::add('livebox','debug','Demande non traitee par la livebox. Param: ' .print_r($param,true));
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

/*	public function preInsert()
	{
		$this->setConfiguration('username', 'admin');
		$this->setConfiguration('password', 'admin');
		$this->setConfiguration('ip', 'livebox');
		$this->setLogicalId('livebox');
		$this->setEqType_name('livebox');
		$this->setIsEnable(1);
		$this->setIsVisible(0);
	}
*/
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
						$cmd->save();
					}

				}
				elseif ( $content->data->LinkType == "ethernet" ) {
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
			$cmd = $this->getCmd(null, 'reset');
			if ( is_object($cmd) ) {
				$cmd->remove();
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
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
						$cmd->setEventOnly(1);
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'wifistatus');
					if ( is_object($cmd)) {
						$cmd->remove();
					}
				}
				$cmd = $this->getCmd(null, 'guestwifion');
				if ( ! is_object($cmd) ) {
					$cmd = new liveboxCmd();
					$cmd->setName('Activer wifi invité');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setType('action');
					$cmd->setSubType('other');
					$cmd->setLogicalId('guestwifion');
					$cmd->setEventOnly(1);
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
					$cmd->setEventOnly(1);
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
					$cmd->setEventOnly(1);
					$cmd->save();
				}
			}

			$cmd = $this->getCmd(null, 'voipstatus');
			if ( is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'numerotelephone');
			if ( is_object($cmd)) {
				$cmd->remove();
			}
			$content = $this->getPage("voip");
			if ( $content !== false ) {
				log::add('livebox','debug','Mode VOIP');

				if ( isset($content["status"]) )
				{
					log::add('livebox','debug','Mode VOIP actif');
					foreach ( $content["status"] as $voip ) {
						if ( ! isset($voip["signalingProtocol"]) ) {
							$voip["signalingProtocol"] = strstr($voip["name"], "-", true);
						}
						if ( strtolower($voip["enable"]) == "enabled" ) {
							log::add('livebox','debug','Mode VOIP '.$voip["signalingProtocol"].' actif');
							if ( strtolower($voip["trunk_lines"]["0"]["enable"]) == "enabled" ) {
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
								$cmd->setEventOnly(1);
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
								$cmd->setEventOnly(1);
								$cmd->setIsVisible(1);
								$cmd->save();
							}
						} else {
							$cmd = $this->getCmd(null, 'voipstatus'.$voip["signalingProtocol"]);
							if ( is_object($cmd)) {
								$cmd->remove();
							}
							$cmd = $this->getCmd(null, 'numerotelephone'.$voip["signalingProtocol"]);
							if ( is_object($cmd)) {
								$cmd->remove();
							}
						}
					} else {
							log::add('livebox','debug','Mode VOIP '.$voip["signalingProtocol"].' inactif');
						}
					}
				}
				else
				{
					log::add('livebox','debug','Mode VOIP inactif');
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
				$cmd->setEventOnly(1);
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
						$statusvalue = $content["status"]["wlanvap"]["eth6"]["VAPStatus"];
					} else {
						// Livebox Play.
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
		$content = $this->getPage("devicelist");
		if ( $content !== false ) {
			$eqLogic_cmd = $this->getCmd(null, 'devicelist');
			$devicelist = array();
			if ( isset($content["status"]) )
			{
				foreach ( $content["status"] as $equipement ) {
					if ( $equipement["Active"] && $equipement["IPAddressSource"] == "DHCP" )
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
		$eqLogic_cmd = $this->getCmd(null, 'updatetime');
		$eqLogic_cmd->event(date("d/m/Y H:i",(time())));
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
			throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
		}
		log::add('livebox','debug','get '.$this->getLogicalId());
		$option = array();
		if ($eqLogic->getConfiguration('productClass','') == 'Livebox 4' || $eqLogic->getConfiguration('productClass','') == 'Livebox 5') {
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
?>
