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
 * states.inc.php
 *
 * PennyPress game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),

    // 2 => array(
    // 		"name" => "playerTurn",
    // 		// "description" => clienttranslate('${actplayer} must select story or reporter'),
    //         // "descriptionmyturn" => clienttranslate('${you} must select story or reporter'),
    //         "description" => clienttranslate('${actplayer} ${playerTurnText}'),
    // 		"descriptionmyturn" => clienttranslate('${you} ${playerTurnText_myTurn}'),
    //         "type" => "activeplayer",
    //         "args" => "argPlayerTurn", 
    // 		"possibleactions" => array( "selectStory", "selectReporter","goToPress", "selectMoreReporters", "sendReportersFast" ),
    // 		"transitions" => array( "selectStory" => 3, "selectReporter" => 4, "goToPress" =>  6, "selectMoreReporters"=> 5, "sendReportersFast"=>10 )
    // ),

    2 => array(
        "name" => "playerTurn",
        // "description" => clienttranslate('${actplayer} must send reporter(s) to story, select reporter(s) or go to press'),
        // "descriptionmyturn" => clienttranslate('${you} must send reporter(s) to story, select reporter(s) or go to press'),
        "description" => clienttranslate('${actplayer} ${Message_for_other_players}'),
        "descriptionmyturn" => clienttranslate('${you} ${Message_for_player_on_turn}'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn", 
        "possibleactions" => array("selectReporter","goToPress", "selectMoreReporters", "sendReportersFast" ),
        "transitions" => array( "selectReporter" => 4, "goToPress" =>  6, "selectMoreReporters"=> 5, "sendReportersFast"=>10, "zombiePass" => 10 )
    ),

    // 3 => array(
    //     "name" => "playerAssignReporters",
    //     "description" => clienttranslate('${actplayer} must send reporters to story'),
    //     "descriptionmyturn" => clienttranslate('How many reporters will you send to the story?'),
    //     "type" => "activeplayer",
    //     "args" => "argPlayerAssignReporters", 
    //     "possibleactions" => array( "sendReporters", "cancel"),
    //     "transitions" => array( "sendReporters" => 10, "cancel" => 2 )
    // ),

    4 => array(
        "name" => "playerSelectReporters",
        // "description" => clienttranslate('${actplayer} must reassign or recall reporters '),
        "description" => clienttranslate('${actplayer} ${Message_for_other_players}'),
        // "descriptionmyturn" => clienttranslate('Reassign reporter, select more reporters or recall selected'),
        "descriptionmyturn" => clienttranslate('${Message_for_player_on_turn}'),
        "type" => "activeplayer",
        "args" => "argPlayerSelectReporters", 
        "possibleactions" => array( "selectReporter", "selectMoreReporters", "reassignReporter", "recallReporters", "cancel"),
        "transitions" => array( "selectReporter" => 5, "selectMoreReporters" => 5, "reassignReporter" => 10,"recallReporters" => 10, "cancel" => 2 )
    ),
    
    5 => array(
        "name" => "playerRecallReporters",
        "description" => clienttranslate('${actplayer} must recall reporters '),
        "descriptionmyturn" => clienttranslate('Select more reporters or recall selected'),
        "type" => "activeplayer",
        "args" => "argPlayerSelectReporters",
        "possibleactions" => array( "selectReporter", "selectMoreReporters", "cancel", "recallReporters"),
        "transitions" => array( "selectReporter" => 5, "selectMoreReporters" => 5, "recallReporters" => 10, "cancel" => 2 )
    ),

    6 => array(
        "name" => "playerGoToPress",
        "description" => clienttranslate('${actplayer} must build front page'),
        "descriptionmyturn" => clienttranslate('${you} must build your front page'),
        "type" => "activeplayer",
        "args" => "argPlayerGoToPress", 
        "possibleactions" => array( "placeStory", "confirmFrontPage", "cancel"),
        "transitions" => array( "placeStory" => 6, "cancel" => 6,  "confirmFrontPage" => 10 )
    ),

    7 => array(
        "name" => "frontPageScore",
        "description" => '',
        "type" => "game",
        "action" => "stfrontPageScore",
        "updateGameProgression" => false,   
        "transitions" => array( "nextPlayer" => 10 )
    ), 

    10 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "endGame" => 99, "nextPlayer" => 2 )
    ), 
   
    // Final state.
    // Please do not modify.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



