<?php

namespace Sandertv\IncompatibleApiPluginLoader;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoadOrder;

class IncompatibleApiPluginLoader extends PluginBase {

	public function onEnable() {
		$incompatibleApiManager = new IncompatibleApiPluginManager($this->getServer(), $this->getServer()->getCommandMap());
		$incompatibleApiManager->loadPlugins($this->getServer()->getPluginPath());

		$this->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
		$this->getServer()->enablePlugins(PluginLoadOrder::POSTWORLD);
	}
}