<?php
namespace PlayNoteBlockSong\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class PlaySongAsyncTask extends AsyncTask{
	public function __construct(){
	}

	public function onCompletion(Server $server){
		$server->getPluginManager()->getPlugin("PlayNoteBlockSong")->playSong();
	}

	public function onRun(){
	}
}