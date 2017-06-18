<?php

namespace Sandertv\IncompatibleApiPluginLoader;

use pocketmine\plugin\PluginLoadOrder;
use pocketmine\scheduler\PluginTask;

class LoadingTask extends PluginTask {

	private $manager;

	public function __construct(IncompatibleApiPluginLoader $plugin, IncompatibleApiPluginManager $incompatibleApiManager) {
		parent::__construct($plugin);
		$this->manager = $incompatibleApiManager;
	}

	public function onRun($currentTick){
		$this->manager->loadPlugins($this->manager->getServer()->getPluginPath());

		$this->manager->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
		$this->manager->getServer()->enablePlugins(PluginLoadOrder::POSTWORLD);
	}
}