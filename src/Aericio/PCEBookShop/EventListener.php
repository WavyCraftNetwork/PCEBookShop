<?php

declare(strict_types=1);

namespace Aericio\PCEBookShop;

use DaPigGuy\PiggyCustomEnchants\utils\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class EventListener implements Listener
{
    /** @var PCEBookShop */
    private $plugin;

    public function __construct(PCEBookShop $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPlayerInteractEvent(PlayerInteractEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getPlayer();
        if ($item->getTypeId() !== ItemTypeIds::BOOK) return;
        if ($item->getNamedTag()->getTag("pcebookshop")) {
            $event->cancel();
            $nbt = $item->getNamedTag()->getInt("pcebookshop");
            $enchants = $this->plugin->getEnchantmentsByRarity($nbt);
            $enchant = $enchants[array_rand($enchants)];
            if ($enchant instanceof Enchantment) {
                $item = VanillaItems::ENCHANTED_BOOK();
                $item->setCustomName(TextFormat::RESET . $this->plugin->getMessage("item.unused-name") . TextFormat::RESET);
                $item->setLore(array(TextFormat::EOL . "§eDescription:§f " . $enchant->getDescription() . TextFormat::EOL . "§eType:§f " . Utils::TYPE_NAMES[$enchant->getItemType()] . TextFormat::EOL . "§eRarity:§f " . Utils::RARITY_NAMES[$enchant->getRarity()]. TextFormat::EOL . "§e---------------"));
                $item->addEnchantment(new EnchantmentInstance($enchant, $this->plugin->getRandomWeightedElement($enchant->getMaxLevel())));
                $inventory = $player->getInventory();
                if ($inventory->canAddItem($item)) {
                    $inventory->removeItem($inventory->getItemInHand()->pop());
                    $inventory->addItem($item->pop());
                    return;
                }
                $player->sendMessage($this->plugin->getMessage("inventory-full"));
            }
        }
    }
}
