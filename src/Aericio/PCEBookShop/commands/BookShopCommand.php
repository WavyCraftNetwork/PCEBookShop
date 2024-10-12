<?php

declare(strict_types=1);

namespace Aericio\PCEBookShop\commands;

use Aericio\PCEBookShop\PCEBookShop;
use DaPigGuy\PiggyCustomEnchants\utils\Utils;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use wavycraft\core\economy\MoneyManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BookShopCommand extends Command
{
    /** @var PCEBookShop */
    protected $plugin;

    public function __construct(PCEBookShop $plugin)
    {
        $this->plugin = $plugin;
        parent::__construct("bookshop", "Open the book shop", "/bookshop");
        $this->setPermission("pcebookshop.command.bookshop");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getMessage("command.use-in-game"));
            return false;
        }

        $this->sendShopForm($sender);
        return true;
    }

    public function sendShopForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, ?int $data): void {
            if ($data !== null) {
                $type = array_keys(Utils::RARITY_NAMES)[$data];
                $name = Utils::RARITY_NAMES[$type];
                $cost = $this->plugin->getConfig()->getNested("cost." . strtolower($name));
                $form = new ModalForm(function (Player $player, ?bool $data) use ($cost, $name, $type): void {
                    if ($data !== null) {
                        if ($data) {
                            $moneyManager = MoneyManager::getInstance();
                            if ($moneyManager->getBalance($player) < $cost) {
                                $player->sendMessage($this->plugin->getMessage("command.insufficient-funds", ["{AMOUNT}" => round($cost - $moneyManager->getBalance($player), 2, PHP_ROUND_HALF_DOWN)]));
                                return;
                            }
                            $item = VanillaItems::BOOK();
                            $item->setCustomName(TextFormat::RESET . $this->plugin->getMessage("item.name", ["{COLOR_RARITY}" => Utils::getColorFromRarity($type), "{ENCHANTMENT}" => $name]) . TextFormat::RESET);
                            $item->setLore([$this->plugin->getMessage("item.lore")]);
                            $item->getNamedTag()->setInt("pcebookshop", $type);
                            $inventory = $player->getInventory();
                            if ($inventory->canAddItem($item)) {
                                $moneyManager->removeMoney($player, $cost);
                                $inventory->addItem($item);
                                return;
                            }
                            $player->sendMessage($this->plugin->getMessage("menu.confirmation.inventory-full"));
                        } else {
                            $this->sendShopForm($player);
                        }
                    }
                });
                $form->setTitle($this->plugin->getMessage("menu.confirmation.title"));
                $form->setContent($this->plugin->getMessage("menu.confirmation.content", ["{RARITY_COLOR}" => Utils::getColorFromRarity($type), "{ENCHANTMENT}" => $name, "{AMOUNT}" => round($cost, 2, PHP_ROUND_HALF_DOWN)]));
                $form->setButton1("Yes");
                $form->setButton2("No");
                $player->sendForm($form);
                return;
            }
        });
        $form->setTitle($this->plugin->getMessage("menu.title"));
        foreach (Utils::RARITY_NAMES as $rarity => $name) {
            $cost = $this->plugin->getConfig()->getNested('cost.' . strtolower($name));
            $form->addButton($this->plugin->getMessage("menu.button", ["{RARITY_COLOR}" => Utils::getColorFromRarity($rarity), "{ENCHANTMENT}" => $name, "{AMOUNT}" => round($cost, 2, PHP_ROUND_HALF_DOWN)]));
        }
        $player->sendForm($form);
    }
}
