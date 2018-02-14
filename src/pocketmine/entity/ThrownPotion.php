<?php

namespace pocketmine\entity;
use pocketmine\item\Potion;
use pocketmine\level\format\FullChunk;
use pocketmine\level\particle\SpellParticle;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class ThrownPotion extends Projectile{
	const NETWORK_ID = 86;
	const DATA_POTION_ID = 16;
	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;
	protected $gravity = 0.1;
	protected $drag = 0.05;
	private $hasSplashed = false;
	public function __construct(FullChunk $level, Compound $nbt, Entity $shootingEntity = null){
		if(!isset($nbt->PotionId)){
			$nbt->PotionId = new ShortTag("PotionId", Potion::AWKWARD);
		}
		parent::__construct($level, $nbt, $shootingEntity);
		unset($this->dataProperties[self::DATA_SHOOTER_ID]);
		$this->setDataProperty(self::DATA_POTION_ID, self::DATA_TYPE_SHORT, $this->getPotionId());
	}
	public function getPotionId() : int{
		return (int) $this->namedtag["PotionId"];
	}
	public function splash(){
		if(!$this->hasSplashed){
			$this->hasSplashed = true;
			$color = Potion::getColor($this->getPotionId());
			$this->getLevel()->addParticle(new SpellParticle($this, $color[0], $color[1], $color[2]));
			$players = $this->getViewers();
			foreach($players as $p) {
				if($p->distance($this) <= 6) {
					foreach(Potion::getEffectsById($this->getPotionId()) as $effect) {
						$p->addEffect($effect);
					}
				}
			}
			$this->kill();
		}
	}
	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}
//		$this->timings->startTiming();
		$hasUpdate = parent::onUpdate($currentTick);
		$this->age++;
		if($this->age > 1200 or $this->isCollided){
			$this->splash();
			$hasUpdate = true;
		}
//		$this->timings->stopTiming();
		return $hasUpdate;
	}
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->type = ThrownPotion::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}
}