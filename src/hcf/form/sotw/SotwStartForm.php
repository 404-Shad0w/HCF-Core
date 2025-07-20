<?php

namespace hcf\form\sotw;

use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\entries\custom\InputEntry;
use cosmicpe\form\CustomForm;
use cosmicpe\form\SimpleForm;
use hcf\util\Utils;
use hcf\timer\TimerFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class SotwStartForm extends CustomForm {
    
    public function __construct(
        private int $sotwTimer = 0,
        private int $keyallTimer = 0,
        private int $keyallopTimer = 0,
        private int $airdropTimer = 0,
        private int $mysteryTimer = 0
    ) {
        parent::__construct(TextFormat::colorize('&gSotw Timer Form'));
        
        $sotwInput = new InputEntry('Sotw Timer = 1h');
        $keyallInput = new InputEntry('Key All Timer = 15m');
        $keyallopInput = new InputEntry('Key AllOp Timer = 20m');
        $airdropInput = new InputEntry('Airdrop Timer = 30m');
        $mysteryInput = new InputEntry('Mystery Timer = 40m');
   # }
    
    $this->addEntry($sotwInput, function (Player $player, InputEntry $entry, string $value) : void {
        if (($value !== 0) || ($value !== ' ')) {
            $this->sotwTimer = Utils::stringToTime($value)  - time();
        }
    });
    $this->addEntry($keyallInput, function (Player $player, InputEntry $entry, string $value) : void {
        if (($value !== 0) || ($value !== ' ')) {
            $this->keyallTimer = Utils::stringToTime($value) - time();
        }
    });
    $this->addEntry($keyallopInput, function (Player $player, InputEntry $entry, string $value) : void {
        if (($value !== 0) || ($value !== ' ')) {
            $this->keyallopTimer = Utils::stringToTime($value) - time();
        }
    });
    $this->addEntry($airdropInput, function (Player $player, InputEntry $entry, string $value) : void {
        if (($value !== 0) || ($value !== ' ')) {
            $this->airdropTimer = Utils::stringToTime($value) - time();
        }
    });
    $this->addEntry($mysteryInput, function (Player $player, InputEntry $entry, string $value) : void {
        if (($value !== 0) || ($value !== ' ')) {
            $this->mysteryTimer = Utils::stringToTime($value) - time();
        }
        
        foreach (TimerFactory::getAll() as $timer) {
            if ($timer->getName() === 'sotw') {
                $timer->setTime((int)$this->sotwTimer);
                $timer->setEnabled(true);
            }
            if ($timer->getName() === 'KeyAll') {
                $timer->setTime((int)$this->keyallTimer);
                $timer->setEnabled(true);
            }
            if ($timer->getName() === 'OpKeyAll') {
                $timer->setTime((int)$this->keyallopTimer);
                $timer->setEnabled(true);
            }
            if ($timer->getName() === 'AirdropAll') {
                $timer->setTime((int)$this->airdropTimer);
                $timer->setEnabled(true);
            }
            if ($timer->getName() === 'BoxAll') {
                $timer->setTime((int)$this->mysteryTimer);
                $timer->setEnabled(true);
            }
        }
    });
   }
}