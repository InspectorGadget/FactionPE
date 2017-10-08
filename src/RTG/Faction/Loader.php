<?php
/**
 * Created by PhpStorm.
 * User: RTG
 * Date: 7/10/2017
 * Time: 10:38 PM
 */

namespace RTG\Faction;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

class Loader extends PluginBase {

    public $dbn = "database.db";

    public function onEnable() {

        if (!is_file($this->getDataFolder() . $this->dbn)) {
            $db = new \SQLite3($this->getDataFolder() . $this->dbn);
            $db->exec("CREATE TABLE IF NOT EXISTS `faction` (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, `name` TEXT NOT NULL, `points` INTEGER NOT NULL, `owner` TEXT NOT NULL);");
            $this->getLogger()->warning("Prepared the Database!");
        } else {
            $this->getLogger()->warning("Loaded Database!");
        }

    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $commandLabel, array $args): bool {

        switch ($command->getName()) {

            case "fac":

                if ($sender->hasPermission("fac.command")) {

                    if (isset($args[0])) {

                        switch ($args[0]) {

                            case "create";

                                if (isset($args[1])) {

                                    $count = strlen($args[1]);

                                    if ($count < 8) {
                                        $sender->sendMessage(TF::RED . "Your faction name must be more than 8 digits!");
                                    } else {
                                        $name = $sender->getName();
                                        $this->factionWrite($args[1], 0, $name, $sender);
                                    }

                                } else {
                                    $sender->sendMessage(TF::GREEN . "[USAGE] /fac create {facname]");
                                }

                                return true;
                            break;

                            case "top":

                                $this->getAll($sender);

                                return true;
                            break;

                            case "get":

                                if (isset($args[1])) {

                                    $this->getPoints($args[1], $sender);

                                } else {
                                    $sender->sendMessage(TF::GREEN . "[USAGE] /fac get [facname]");
                                }

                                return true;
                            break;

                            case "addpoints":

                                if ($sender->hasPermission("fac.command.admin")) {

                                    if (isset($args[1]) && isset($args[2])) {

                                        $facname = $args[1];
                                        $int = $args[2];

                                        $this->addPoints($facname, $int, $sender);

                                    } else {
                                        $sender->sendMessage(TF::GREEN . "[USAGE] /fac addpoints [facname] [point]");
                                    }

                                } else {
                                    $sender->sendMessage(TF::RED . "You have no permission to use this command!");
                                }

                                return true;
                            break;

                            case "deductpoints":

                                if ($sender->hasPermission("fac.command.admin")) {

                                    if (isset($args[1]) && isset($args[2])) {

                                        $facname = $args[1];
                                        $int = $args[2];

                                        $this->deductPoints($facname, $int, $sender);

                                    } else {
                                        $sender->sendMessage(TF::GREEN . "[USAGE] /fac deductpoints [facname] [point]");
                                    }

                                } else {
                                    $sender->sendMessage(TF::RED . "You have no permission to use this command!");
                                }

                                return true;
                            break;

                        }

                    } else {
                        $sender->sendMessage(TF::GREEN . "[USAGE] /fac <create:top:get:addpoints:deductpoints>");
                    }

                } else {
                    $sender->sendMessage(TF::RED . "You have no permission to use this command!");
                }

                return true;
            break;

        }

    }

    /**
     * @param $name
     * @param int $point
     * @param CommandSender $sender
     */
    public function factionWrite($name, int $point, $owner, CommandSender $sender) {

        $statement = "INSERT INTO `faction` (`name`, `points`, `owner`) VALUES ('$name', '$point', '$owner')";
        $file = new \SQLite3($this->getDataFolder() . $this->dbn);
        $res = $file->query($statement);

        if (!$res) {
            $sender->sendMessage(TF::RED . "Error! : $name , $point");
        } else {
            $sender->sendMessage(TF::GREEN . "Faction has been made!");
            $file->close(); // Release memory
        }

    }

    /**
     * @param CommandSender $sender
     */
    public function getAll(CommandSender $sender) {

        $statement = "SELECT * FROM `faction`";
        $file = new \SQLite3($this->getDataFolder() . $this->dbn);
        $result = $file->query($statement);

        $sender->sendMessage(TF::YELLOW . "Top Factions:");

        while ($row = $result->fetchArray(1)) {

            $sender->sendMessage($row['id'] . " : " . $row['name']);

        }

    }

    /**
     * @param $facname
     * @param CommandSender $sender
     */
    public function getPoints($facname, CommandSender $sender)
    {

        $statement = "SELECT * FROM `faction` WHERE `name` = '$facname'";
        $file = new \SQLite3($this->getDataFolder() . $this->dbn);
        $res = $file->query($statement);

        if ($row = $res->fetchArray(1)) {
            $sender->sendMessage("Name: " . $row['name']);
            $sender->sendMessage("Points: " . $row['points']);
            $sender->sendMessage("Owner: " . $row['owner']);
        } else {
            $sender->sendMessage(TF::RED . "No such faction exists with the name of " . $facname . "!");
        }
    }

    /**
     * @param $facname
     * @return int
     */
    public function getOnlyPoint($facname): int {
        $statement = "SELECT * FROM `faction` WHERE `name` = '$facname'";
        $file = new \SQLite3($this->getDataFolder() . $this->dbn);
        $res = $file->query($statement);

        if ($row = $res->fetchArray(1)) {
            return $row['points'];
        } else {
            return 0;
        }

        $file->close();

    }

    public function addPoints($facname, int $number, CommandSender $sender) {

        $final = $this->getOnlyPoint($facname) + $number;

        $statement = "UPDATE `faction` SET `points` = '$final' WHERE `name` = '$facname'";
        $file = new \SQLite3($this->getDataFolder() . $this->dbn);
        $res = $file->query($statement);

        if (!$res) {
            $sender->sendMessage(TF::RED . "Points addition failed! Please make sure if there's a faction with that name");
        } else {
            $sender->sendMessage(TF::GREEN . "$number Points added to $facname!");
        }

    }

    public function deductPoints($facname, int $number, CommandSender $sender) {

        $final = $this->getOnlyPoint($facname) - $number;

        $statement = "UPDATE `faction` SET `points` = '$final' WHERE `name` = '$facname'";
        $file = new \SQLite3($this->getDataFolder() . $this->dbn);
        $res = $file->query($statement);

        if (!$res) {
            $sender->sendMessage(TF::RED . "Points deduction failed! Please make sure if there's a faction with that name");
        } else {
            $sender->sendMessage(TF::GREEN . "$number Points has been deducted from $facname!");
        }

    }

}