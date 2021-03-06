<?php

/*
       _             _____           ______ __
      | |           |  __ \         |____  / /
      | |_   _ _ __ | |  | | _____   __ / / /_
  _   | | | | | '_ \| |  | |/ _ \ \ / // / '_ \
 | |__| | |_| | | | | |__| |  __/\ V // /| (_) |
  \____/ \__,_|_| |_|_____/ \___| \_//_/  \___/


This program was produced by JunDev76 and cannot be reproduced, distributed or used without permission.

Developers:
 - JunDev76 (https://github.jundev.me/)

Copyright 2022. JunDev76. Allrights reserved.
*/

namespace JunDev76\SkinManager;

use FormSystem\form\ModalForm;
use JsonException;
use JunDev76\TutorialManager\TutorialManager;
use JunKR\CrossUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Skin;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use ReflectionException;

class SkinManager extends PluginBase{
    use SingletonTrait;

    public function onLoad() : void{
        self::setInstance($this);
    }

    public array $db = [];

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    protected function onEnable() : void{
        for($i = 1; $i <= 20; $i++){
            $this->saveResource($i . '.png');
        }
        $this->saveResource('steve.json');

        $this->db = CrossUtils::getDataArray($this->getDataFolder() . 'userSkinData.json');
        CrossUtils::registercommand('skinmanager', $this, '', DefaultPermissions::ROOT_OPERATOR);

        $this->getServer()->getPluginManager()->registerEvent(PlayerJoinEvent::class, function(PlayerJoinEvent $ev){
            $player = $ev->getPlayer();
            if(isset($this->db[$player->getName()])){
                $this->setPlayerSkin($player, $this->db[$player->getName()]);
            }
        }, EventPriority::NORMAL, $this);
    }

    /**
     * @throws JsonException
     */
    protected function onDisable() : void{
        file_put_contents($this->getDataFolder() . 'userSkinData.json', json_encode($this->db, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws JsonException
     */
    public function setPlayerSkin(Player $player, $index) : void{
        $img = @imagecreatefrompng($skinPath = $this->getDataFolder() . $index . '.png');
        $skinbytes = '';
        $size = getimagesize($skinPath);

        for($y = 0; $y < $size[1]; $y++){
            for($x = 0; $x < $size[0]; $x++){
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~($colorat >> 24)) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        $player->setSkin(new Skin(Uuid::uuid4()->toString(), $skinbytes, "", "geometry.humanoid.custom", file_get_contents($this->getDataFolder() . "steve.json")));
        $player->sendSkin();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!isset($args[0], $args[1], $args[2]) || $command->getName() !== 'skinmanager'){
            return true;
        }

        if($args[0] === 'setskin'){
            [$playerName, $index] = [$args[1], $args[2]];
            $player = $this->getServer()->getPlayerExact($playerName);
            if($player === null){
                return true;
            }

            $form = new ModalForm(function(Player $target, $data) use ($index){
                if($data === true){
                    $this->db[$target->getName()] = $index;
                    $this->setPlayerSkin($target, $index);

                    TutorialManager::getInstance()->finish($target);

                    $items = [ItemIds::STONE_PICKAXE, ItemIds::WOODEN_HOE];

                    $inv = $target->getInventory();
                    $inv->clearAll();

                    foreach($items as $itema){
                        $item = ItemFactory::getInstance()->get($itema, 0, 1);
                        $item->setLore(["\n??r??c??l??? ??r??f?????? ?????? ?????????"]);

                        $inv->addItem($item);
                    }

                    $item = ItemFactory::getInstance()->get(ItemIds::BREAD, 0, 10);
                    $item->setLore(["\n??r??c??l??? ??r??f?????? ?????? ?????????"]);

                    $inv->addItem($item);

                    $item = ItemFactory::getInstance()->get(ItemIds::PAPER, 1, 1);
                    $item->setCustomName("??r??b??l< ??f?????????????????? ??b>");
                    $item->setLore(["\n??r??c??l??? ??r??f?????? ?????? ?????????"]);

                    $inv->addItem($item);

                    $item = ItemFactory::getInstance()->get(ItemIds::FISHING_ROD, 0, 1);
                    $item->setLore(["\n??r??c??l??? ??r??f?????? ?????? ?????????"]);

                    $inv->addItem($item);

                    $item = ItemFactory::getInstance()->get(ItemIds::COMPASS, 0, 1);

                    $inv->setItem(8, $item);

                    $targetName = $target->getName();
                    $this->getServer()->broadcastMessage("\n ??l??e{$targetName}?????? ???????????? ??????????????? ?????? ??????????????????!\n ??l??f???????????? ???????????? ???????????????!\n");
                    $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($targetName) : void{
                        $this->getServer()->broadcastMessage('??a??l[????????????!] ??r??e' . $targetName . '??f, ??a??l????????? ?????????!');
                    }), 35);
                }
            });

            $form->setTitle("??l??????");
            $form->setContent("?????? ?????? ????????? ?????? ???????????? ?????? ???????????????????\n\n??c! ?????? ??? ??? ????????????.");

            $form->setButton1("??l??? ???????????? ????????????");
            $form->setButton2("??l????????????");

            $player->sendForm($form);
        }
        return true;
    }

}