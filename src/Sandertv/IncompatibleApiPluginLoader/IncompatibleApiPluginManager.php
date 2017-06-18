<?php

namespace Sandertv\IncompatibleApiPluginLoader;

use pocketmine\command\defaults\TimingsCommand;
use pocketmine\command\SimpleCommandMap;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginManager;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class IncompatibleApiPluginManager extends PluginManager {

	private $server;

	public function __construct(Server $server, SimpleCommandMap $commandMap) {
		parent::__construct($server, $commandMap);
		$this->server = $server;
	}

	public function getServer(): Server {
		return $this->server;
	}

	/**
	 * @param string $directory
	 * @param array  $newLoaders
	 *
	 * @return Plugin[]
	 */
	public function loadPlugins($directory, $newLoaders = null){
		if(is_dir($directory)){
			$plugins = [];
			$loadedPlugins = [];
			$dependencies = [];
			$softDependencies = [];
			if(is_array($newLoaders)){
				$loaders = [];
				foreach($newLoaders as $key){
					if(isset($this->fileAssociations[$key])){
						$loaders[$key] = $this->server->getPluginmanager()->fileAssociations[$key];
					}
				}
			}else{
				$loaders = $this->server->getPluginManager()->fileAssociations;
			}
			foreach($loaders as $loader){
				foreach(new \RegexIterator(new \DirectoryIterator($directory), $loader->getPluginFilters()) as $file){
					if($file === "." or $file === ".."){
						continue;
					}
					$file = $directory . $file;
					try{
						$description = $loader->getPluginDescription($file);
						if($description instanceof PluginDescription){
							$name = $description->getName();
							if(stripos($name, "pocketmine") !== false or stripos($name, "minecraft") !== false or stripos($name, "mojang") !== false){
								$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.restrictedName"]));
								continue;
							}elseif(strpos($name, " ") !== false){
								$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.plugin.spacesDiscouraged", [$name]));
							}
							if(isset($plugins[$name]) or $this->server->getPluginManager()->getPlugin($name) instanceof Plugin){
								continue;
							}
							$plugins[$name] = $file;
							$softDependencies[$name] = (array) $description->getSoftDepend();
							$dependencies[$name] = (array) $description->getDepend();
							foreach($description->getLoadBefore() as $before){
								if(isset($softDependencies[$before])){
									$softDependencies[$before][] = $name;
								}else{
									$softDependencies[$before] = [$name];
								}
							}
						}
					}catch(\Throwable $e){
						$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.fileError", [$file, $directory, $e->getMessage()]));
						$this->server->getLogger()->logException($e);
					}
				}
			}
			while(count($plugins) > 0){
				$missingDependency = true;
				foreach($plugins as $name => $file){
					if(isset($dependencies[$name])){
						foreach($dependencies[$name] as $key => $dependency){
							if(isset($loadedPlugins[$dependency]) or $this->server->getPluginManager()->getPlugin($dependency) instanceof Plugin){
								unset($dependencies[$name][$key]);
							}elseif(!isset($plugins[$dependency])){
								$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.unknownDependency"]));
								break;
							}
						}
						if(count($dependencies[$name]) === 0){
							unset($dependencies[$name]);
						}
					}
					if(isset($softDependencies[$name])){
						foreach($softDependencies[$name] as $key => $dependency){
							if(isset($loadedPlugins[$dependency]) or $this->server->getPluginManager()->getPlugin($dependency) instanceof Plugin){
								unset($softDependencies[$name][$key]);
							}
						}
						if(count($softDependencies[$name]) === 0){
							unset($softDependencies[$name]);
						}
					}
					if(!isset($dependencies[$name]) and !isset($softDependencies[$name])){
						unset($plugins[$name]);
						$missingDependency = false;
						if($plugin = $this->server->getPluginManager()->loadPlugin($file, $loaders) and $plugin instanceof Plugin){
							$loadedPlugins[$name] = $plugin;
							$this->server->getLogger()->info(TextFormat::YELLOW . "Loading " . $name . " plugin with incompatible API version...");
						}else{
							$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.genericLoadError", [$name]));
						}
					}
				}
				if($missingDependency === true){
					foreach($plugins as $name => $file){
						if(!isset($dependencies[$name])){
							unset($softDependencies[$name]);
							unset($plugins[$name]);
							$missingDependency = false;
							if($plugin = $this->server->getPluginManager()->loadPlugin($file, $loaders) and $plugin instanceof Plugin){
								$loadedPlugins[$name] = $plugin;
								$this->server->getLogger()->info(TextFormat::YELLOW . "Loading " . $name . " plugin with incompatible API version...");
							}else{
								$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.genericLoadError", [$name]));
							}
						}
					}
					if($missingDependency === true){
						foreach($plugins as $name => $file){
							$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.circularDependency"]));
						}
						$plugins = [];
					}
				}
			}
			TimingsCommand::$timingStart = microtime(true);
			return $loadedPlugins;
		}else{
			TimingsCommand::$timingStart = microtime(true);
			return [];
		}
	}
}