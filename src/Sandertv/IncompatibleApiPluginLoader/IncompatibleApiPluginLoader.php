<?php

namespace Sandertv\IncompatibleApiPluginLoader;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;

class IncompatibleApiPluginLoader extends PluginBase {

	public function onEnable() {
		$incompatibleApiManager = new IncompatibleApiPluginManager($this->getServer(), $this->getServer()->getCommandMap());
		$this->getServer()->getScheduler()->scheduleDelayedTask(new LoadingTask($this, $incompatibleApiManager), 10);
	}
}