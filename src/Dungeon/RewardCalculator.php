<?php
declare(strict_types=1);

namespace Game\Dungeon;

use Game\Engine\TimeInterval;
use Game\Player\Player;

readonly class RewardCalculator
{
    private readonly TTKCalculator $ttkCalculator;

    public function __construct(private DropRepository $dropRepository)
    {
        $this->ttkCalculator = new TTKCalculator();
    }

    public function calculate(Dungeon $dungeon, Player $hunter, TimeInterval $timesSpentInDungeon): Reward
    {
        $approximateSpentMinutes = (int) round($timesSpentInDungeon->toMinutes());
        $stamina = $hunter->getStamina();

        // Prevents over-rewarding the player (1 stamina for 1 minute is how it should be).
        // Thus, it decreases the passed time for a player as if he has left the dungeon after depleting the stamina
        if ($stamina < $approximateSpentMinutes) {
            $approximateSpentMinutes = $stamina;
        }

        if ($approximateSpentMinutes === 0) {
            return Reward::none();
        }

        $ttkMonster = $this->ttkCalculator->calculate($hunter, $dungeon->inhabitant);
        $ttkPlayer = $this->ttkCalculator->calculateForMonster($dungeon->inhabitant, $hunter);

        $unitsKilled = (int) floor($approximateSpentMinutes/$ttkMonster->toMinutes());
        if ($unitsKilled === 0) {
            return Reward::none();
        }

        // If player needs more time to kill monster than monsters needs to kill player, then issue no rewards
        if ($ttkMonster->isGreaterThan($ttkPlayer)) {
            return Reward::none();
        }

        $expEarned = $unitsKilled * $dungeon->inhabitant->exp;
        $drop = array_fill(0, $unitsKilled, $this->dropRepository->getMonsterDrop($dungeon->inhabitant));

        return new Reward($expEarned, $drop);
    }
}