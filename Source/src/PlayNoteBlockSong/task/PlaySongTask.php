<?php
namespace PlayNoteBlockSong\task;

use pocketmine\scheduler\PluginTask;

class PlaySongTask extends PluginTask{
	protected $owner;

 	public function onRun($currentTick){
		$this->owner->getServer()->getScheduler()->scheduleAsyncTask(new PlaySongAsyncTask());
	}
}