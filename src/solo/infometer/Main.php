<?

namespace solo\infometer;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;


class Main extends PluginBase implements Listener{

	public $config;
	public $viewString;

	public $playersOS = [];
	public $playersDevice = [];

	public $economyapi;

	public function onEnable(){
		@mkdir($this->getDataFolder());

		$this->config = new Config($this->getDataFolder() . "setting.yml", Config::YAML, [
			"availableParameter" => [
				"{MOTD}" => "서버의 MOTD 입니다.",
				"{TPS}" => "서버의 TPS 상태 입니다.",
				"{AVERAGETPS}" => "서버의 평균 TPS 입니다.",
				"{PLAYERS}" => "서버의 접속 인원 입니다.",
				"{MAXPLAYERS}" => "서버에 접속 가능한 최대 인원입니다.",
				"{DATE}" => "날짜를 표시합니다. *월 *일 형식으로 표시됩니다.",
				"{TIME}" => "시간을 표시합니다. *:* am/pm 형식으로 표시됩니다.",

				"{WORLD}" => "플레이어가 속해있는 월드 이름입니다.",
				"{WORLDPLAYERS}" => "플레이어가 속해있는 월드의 플레이어 수 입니다.",
				"{WORLDTIME}" => "월드의 시간입니다.",

				"{NAME}" => "플레이어의 이름입니다.",
				"{MONEY}" => "플레이어가 소지한 돈입니다. (EconomyAPI 플러그인 필요)",
				"{MONEYRANK}" => "플레이어의 돈 순위입니다. (EconomyAPI 3.0.0 이상 플러그인 필요)",
				"{X}" => "플레이어의 X 좌표입니다.",
				"{Y}" => "플레이어의 Y 좌표입니다.",
				"{Z}" => "플레이어의 Z 좌표입니다.",
				"{HEALTH}" => "플레이어의 현재 체력입니다.",
				"{MAXHEALTH}" => "플레이어의 최대 체력입니다.",
				"{OS}" => "플레이어의 운영체제입니다.",
				"{DEVICE}" => "플레이어의 디바이스 모델명입니다",
				"{GAMEMODE}" => "플레이어의 게임모드입니다."
			],
			"view" => "§l{MOTD}§r\n§b접속자 수 : §f{PLAYERS}/{MAXPLAYERS}명\n§b현재 월드 : §f{WORLD} ({WORLDPLAYERS}명)\n§b돈 : §f{MONEY}원 ({MONEYRANK}위)"
		]);

		$this->viewString = $this->config->get("view");
		//$this->viewString = "{MOTD} {TPS} {AVERAGETPS} {PLAYERS}\n{DATE} {TIME}\n{WORLD} {WORLDPLAYERS} {WORLDTIME}\n{MONEY} {X} {Y} {Z} {HEALTH} {MAXHEALTH}\n{OS} {DEVICE} {GAMEMODE}";

		$this->economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");

		$this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this) extends PluginTask{
			public function onRun($currentTick){
				$server = $this->owner->getServer();
				$msg = $this->owner->viewString;

				// Server side data
				$msg = str_replace([
					"{MOTD}",
					"{TPS}",
					"{AVERAGETPS}",
					"{PLAYERS}",
					"{MAXPLAYERS}",
					"{DATE}",
					"{TIME}"
				], [
					$server->getMotd(),
					$server->getTicksPerSecond(),
					$server->getTicksPerSecondAverage(),
					count($server->getOnlinePlayers()),
					$server->getMaxPlayers(),
					date("m월 d일"),
					date("g:i a")
				], $msg);

				// Level data
				foreach($server->getLevels() as $level){
					$msgLevel = str_replace([
						"{WORLD}",
						"{WORLDPLAYERS}",
						"{WORLDTIME}"
					], [
						$level->getFolderName(),
						count($level->getPlayers()),
						WorldTimeInfo::getTime($level->getTime())
					], $msg);

					// Player data
					foreach($level->getPlayers() as $player){
						$msgPlayer = str_replace([
							"{NAME}",
							"{MONEY}",
							"{MONEYRANK}",
							"{X}",
							"{Y}",
							"{Z}",
							"{HEALTH}",
							"{MAXHEALTH}",
							"{OS}",
							"{DEVICE}",
							"{GAMEMODE}"
						], [
							$player->getName(),
							$this->owner->economyapi ===  null ? "§cEconomyAPI Not Found Error§r" : $this->owner->economyapi->myMoney($player),
							method_exists($this->owner->economyapi, "getRank") ? $this->owner->economyapi->getRank($player) : "§cEconomyAPI Old Version Error",
							round($player->x, 2),
							round($player->y, 2),
							round($player->z, 2),
							$player->getHealth(),
							$player->getMaxHealth(),
							$player->getOperatingSystem(),
							$player->getDeviceModel(),
							$player->getGamemode() == 0 ? "서바이벌" : "크리에이티브"
						], $msgLevel);

						$player->sendTip($msgPlayer);
					}
				}
			}
		}, 20);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(){

	}
}


class WorldTimeInfo{

	public static function getTime(int $time){
		if($time < 2000){
			return "아침";
		}else if($time < 12000){ // 2000 ~ 12000 : 낮
			return "낮";
		}else if($time < 14000){ // 12000 ~ 14000 : 저녁
			return "저녁";
		}else if($time < 23000){ // 14000 ~ 23000 : 밤
			return "밤";
		}else{
			return "아침";
		}
	}
}