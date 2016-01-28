<?php
namespace PlayNoteBlockSong;

/*
 * NBS is Note Block Song
 * Info : http://StuffByDavid.com/mcnbs
 * NBS Format : http://StuffByDavid.com/mcnbs/format
 * 
 * ETC : 
 * http://dev.bukkit.org/bukkit-plugins/noteblockapi/
 * http://dev.bukkit.org/bukkit-plugins/noteblockplayer/
 * https://github.com/xxmicloxx/NoteBlockAPI
 * 
 * http://dev.bukkit.org/bukkit-plugins/icjukebox/pages/tracks/
 */

use AddNoteBlock\block\NoteBlock;

class SongPlayer extends \stdClass{
	private $plugin;
	private $length;
	private $sounds = [];
	private $tick = 0;
	private $buffer;
	private $offset = 0;
	private $isStop = false;

	public function __construct(PlayNoteBlockSong $plugin, $path){
		$this->plugin = $plugin;
		$fopen = fopen($path, "r");
		$this->buffer = fread($fopen, filesize($path));
		fclose($fopen);

		/*
		 * Part #1: Header
		 */

		// Short: Song length
		$this->length = $this->getShort();

		// Short: Song height
		$height = $this->getShort();

		// String: Song name
		$this->getString();

		// String: Song author
		$this->getString();

		// String: Original song author
		$this->getString();

		// String: Song description
		$this->getString();

		// Short: Tempo
		$this->getShort();

		// Byte: Auto-saving
		$this->getByte();

		// Byte: Auto-saving duration
		$this->getByte();

		// Byte: Time signature
		$this->getByte();

		// Integer: Minutes spent
		$this->getInt();

		// Integer: Left clicks
		$this->getInt();

		// Integer: Right clicks
		$this->getInt();

		// Integer: Blocks added
		$this->getInt();

		// Integer: Blocks removed
		$this->getInt();

		// String: MIDI/Schematic file name
		$this->getString();


		/*
		 * Part #2: Note blocks
		 */

		// Step #1: Short: Jumps to the next tick
 		$tick = $this->getShort() - 1;

		while(true){
			$sounds = [];

			// Step #2: Short: Jumps to the next layer
			$this->getShort();

			while(true){
				// Step #3: Byte: Note block instrument
				switch($this->getByte()){
					case 1: // Double Bass (wood)
						$type = NoteBlock::BASS_GUITAR;
					break;
					case 2: // Bass Drum (stone)
						$type = NoteBlock::BASS_DRUM;
					break;
					case 3: // Snare Drum (sand)
						$type = NoteBlock::SNARE_DRUM;
					break;
					case 4: // Click (glass)
						$type = NoteBlock::CLICKS_AND_STICKS;
					break;
					default: // Piano (air)
						$type = NoteBlock::PIANO_OR_HARP;
					break;
				}

				/* Step #4: Byte: Note block key
				 * 0 is A0 and 87 is C8.
				 * 33-57 is within the 2 octave
				 */
				if($height == 0){
					$pitch = $this->getByte() - 33;
				}elseif($height < 10){
					$pitch = $this->getByte() - 33 + $height;
				}else{
					$pitch = $this->getByte() - 48 + $height;
				}

				$sounds[] = [$pitch, $type];
				if($this->getShort() == 0) break;
			}
			$this->sounds[$tick] = $sounds;

			/* Step #2: Short: Jumps to the next layer
			 * If this is 0, we go back to Step #1
			 */
			if(($jump = $this->getShort()) !== 0){
				$tick += $jump;
			}else{
				break;
			}
		}
	}

	public function onRun(){
		if(!$this->isStop){
			if(isset($this->sounds[$this->tick])){
				foreach($this->sounds[$this->tick] as $data){
					$this->plugin->sendSound(...$data);
				}
			}
			$this->tick++;
			if($this->tick > $this->length){
				$this->isStop = true;
			}
		}
	}

	public function isStop(){
		return $this->isStop;
	}

	public function get($len){
		if($len < 0){
			$this->offset = strlen($this->buffer) - 1;
			return "";
		}elseif($len === true){
			return substr($this->buffer, $this->offset);
		}
		return $len === 1 ? $this->buffer{$this->offset++} : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	public function getByte(){
		return ord($this->buffer{$this->offset++});
	}

	public function getInt(){
		return (PHP_INT_SIZE === 8 ? unpack("N", $this->get(4))[1] << 32 >> 32 : unpack("N", $this->get(4))[1]);
	}

	public function getShort(){
		return unpack("S", $this->get(2))[1];
	}
	
	public function getString(){
		return $this->get(unpack("I", $this->get(4))[1]);
	}
}