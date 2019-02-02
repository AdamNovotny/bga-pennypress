<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * PennyPress implementation : © Adam Novotny <Adam.Novotny.ck@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * PennyPress game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in pennypress.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
                'name' => totranslate('Game variant'),    
                'values' => array(

                            1 => array( 'name' => totranslate('Standard rules') ),

                            2 => array( 'name' => totranslate('Newsboy strike'), 'tmdisplay' => totranslate('Newsboy strike') ),
                ),

                'startcondition' => array(
                                1 => array(),
                                2 => array( array( 'type' => 'minplayers', 'value' => 4, 'message' => totranslate( 'Newsboy strike variant is only available for 4 or 5 players' ) ) ),
                )
            
    )
);


