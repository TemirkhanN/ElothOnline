<?php
declare(strict_types=1);

use Game\Wiki;

require_once __DIR__ . '/../bootstrap.php';

enum Tab: string
{
    case DUNGEON = 'dungeons';
    case HIGH_SCORE = 'highscores';
    case MAIN = 'main';
    case INVENTORY = 'inventory';
    case LIBRARY = 'library';

    public function load(): void
    {
        $currentUserName = $_SESSION['username'] ?? '';
        if ($currentUserName === '') {
            header('Location: /login.php');
            exit();
        }

        $game = getService(\Game\Game::class);
        $player = $game->findPlayer($currentUserName);

        switch ($this) {
            case self::LIBRARY:
            case self::DUNGEON:
                $wiki = getService(Wiki::class);
                break;
        }
        require PROJECT_ROOT . '/tabs/' . $this->value . '.php';
    }
}

session_start();

$currentTab = Tab::MAIN;
if (isset($_GET['tab']) && is_string($_GET['tab'])) {
    $currentTab = Tab::tryFrom($_GET['tab']);
    if ($currentTab === null) {
        exit('Unknown tab');
    }
}

$currentTab->load();
