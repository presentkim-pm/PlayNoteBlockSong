<?php
namespace PlayNoteBlockSong;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\TranslationContainer as Translation;
use AddNoteBlock\block\NoteBlock;
use PlayNoteBlockSong\task\LoadSongAsyncTask;
use PlayNoteBlockSong\task\PlaySongTask;

class PlayNoteBlockSong extends PluginBase{
	private $songs = [], $index = -1, $song, $play = false;
	public function onEnable(){
		$this->getServer()->getScheduler()->scheduleAsyncTask(new LoadSongAsyncTask());
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new PlaySongTask($this), 2);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		if(!isset($sub[0]) || $sub[0] == ""){
			return false;
		}
		switch(strtolower($sub[0])){
 			case "play":
				if(!$sender->hasPermission("playnoteblocksong.cmd.play")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif($this->play){
					$r = Color::RED . "[PlayNBS] " . ($ik ? "이미 재생중입니다." : "Already playing");
				}elseif(count($this->songs) <= 0){
					$r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
				}else{
					$this->play = true;
					$r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악을 재생합니다." : "Play the song");
				}
			break;
			case "stop":
				if(!$sender->hasPermission("playnoteblocksong.cmd.stop")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!$this->play){
					$r = Color::RED . "[PlayNBS] " . ($ik ? "음악이 재생중이 아닙니다." : "Shong is not playing");
				}else{
					$this->play = false;
					$r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악을 중디합니다." : "Stop the song");
				}
			break;
			case "next":
				if(!$sender->hasPermission("playnoteblocksong.cmd.next")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(count($this->songs) <= 0){
					$r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
				}else{
					$songNames = array_keys($this->songs);
					if(!isset($songNames[$this->index + 1])){
						$this->index = 0;
					}else{
						$this->index++;
					}
					$this->song = clone $this->songs[$name = $songNames[$this->index]];
					$this->getLogger()->notice(Color::AQUA . "[PlayeNBS] Play next song : $name");
					$r = Color::YELLOW . "[PlayNBS] " . ($ik ? "다음 음악을 재생합니다. : " : "Play next song : ") . $name;
				}
			break;
			case "prev":
				if(!$sender->hasPermission("playnoteblocksong.cmd.prev")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(count($this->songs) <= 0){
					$r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
				}else{
					$songNames = array_keys($this->songs);
					if(!isset($songNames[$this->index - 1])){
						$this->index = count($songNames) - 1;
					}else{
						$this->index--;
					}
					$this->song = clone $this->songs[$name = $songNames[$this->index]];
					$this->getLogger()->notice(Color::AQUA . "[PlayeNBS] Play next song : $name");
					$r = Color::YELLOW . "[PlayNBS] " . ($ik ? "이전 음악을 재생합니다. : " : "Play prev song : ") . $name;
				}
			break;
			case "shuffle":
				if(!$sender->hasPermission("playnoteblocksong.cmd.shuffle")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(count($this->songs) <= 0){
					$r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
				}else{
					shuffle($this->songs);
					$songNames = array_keys($this->songs);
					$this->index = 0;
					$this->song = clone $this->songs[$name = $songNames[$this->index]];
					$this->getLogger()->notice(Color::AQUA . "[PlayeNBS] Song list is Shuffled. Play next song : $name");
					$r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악 목록이 뒤섞였습니다. 다음 음악을 재생합니다. : " : "Song list is Shuffled. Play next song : ") . $name;
				}
			break;
			case "list":
				if(!$sender->hasPermission("playnoteblocksong.cmd.list")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(count($this->songs) <= 0){
					$r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
				}else{
					$lists = array_chunk($this->songs, 5);
					$r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악 목록 (페이지: " : "Song list (Page: ") . ($page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1] - 1]) ? $sub[1] : 1, count($lists))). "/" . count($lists) . ") (" . count($this->songs) . ")";
					if(isset($lists[$page - 1])){
						$keys = array_keys($this->songs);
						foreach($lists[$page - 1] as $key => $songPlayer){
							$r .= "\n" . Color::GOLD . "    [" . (($songKey = (($page - 1) * 5 + $key)) + 1) .  "] " . $keys[$songKey];
						}
					}
				}
			break;
			case "reload":
				if(!$sender->hasPermission("playnoteblocksong.cmd.reload")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->loadSong();
					$r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악을 다시 로드했습니다." : "Reloaded songs.");
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)){
			$sender->sendMessage($r);
		}
		return true;
	}

	public function loadSong(){
		$this->songs = [];
		$logger = $this->getLogger();
		@mkdir($folder = $this->getDataFolder());
		$opendir = opendir($folder);
		$logger->notice(Color::AQUA . "[PlayeNBS] Load song...");
		while(($file = readdir($opendir)) !== false){
			if(($pos = stripos($file, ".nbs")) !== false){
				$this->songs[$name = substr($file, 0, $pos)] = new SongPlayer($this, $folder . $file);
				$logger->notice(Color::AQUA . "[PlayeNBS] $name is loaded");
			}
		}
		if(count($this->songs) >= 1){
			$logger->notice(Color::AQUA . "[PlayeNBS] Load complete");
		}else{
			$logger->notice(Color::DARK_RED . "[PlayeNBS] You don't have song");			
			$logger->notice(Color::DARK_RED . "[PlayeNBS] Please put in the song to $folder");			
		}
	}

	public function playSong(){
		if($this->play){
			if($this->song === null || $this->song->isStop()){
				$songNames = array_keys($this->songs);
				if(!isset($songNames[$this->index + 1])){
					$this->index = 0;
				}else{
					$this->index++;
				}
				$this->song = clone $this->songs[$name = $songNames[$this->index]];
				$this->getLogger()->notice(Color::AQUA . "[PlayeNBS] Play next song : $name");
			}
			$this->song->onRun();
		}
	}

	public function sendSound($pitch, $type = NoteBlock::PIANO_OR_HARP){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			NoteBlock::runNoteBlockSound(new Position($player->x, $player->y + 1, $player->z, $player->level), $pitch, $type, $player);
		}
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}