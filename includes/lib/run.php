<?php

try {
    $game = new MainGame();
    $game->runMainLoop();
}
catch (Exception $exception){
    elog($exception->getMessage());
}