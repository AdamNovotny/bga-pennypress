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
  * pennypress.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class PennyPress extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            "articles_in_play" => 10,
            "selected_story" =>11,
            "number_of_claimed_stories" => 12,
            "number_of_claimed_topbeats" => 13,
            "number_top_stories_top_edge" => 14,
            "number_top_stories_rest" => 15,
            "placed_top_stories_top_edge" => 16,
            "placed_top_stories_rest" => 17,
            "final_edition" => 18,
            "two_player_turn_marker" => 19,
            "selected_reporter0" =>21,
            "selected_reporter1" =>22,
            "selected_reporter2" =>23,
            "selected_reporter3" =>24,
            "selected_reporter4" =>25,
            "gameVariant" => 100
        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "pennypress";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player ) {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        // Create tokens
        $sql = "INSERT INTO tokens (tokens_type, tokens_type_arg, tokens_location) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player ) {
            $name = "meeple_".$player_id;
            $location = "home";
            for( $i=0; $i<5; $i++ ) {
                $values[] = "('$name', '$i','$location')";
            }    
        }

        for( $i=0; $i<5; $i++ ) {
            $name = "bonusmarker";
            $name_arg = $this->arrowmarkers[$i+1]["name"];
            $values[] = "('$name', '$name_arg','2')";
        }       
        
        for( $i=0; $i<5; $i++ ) {
            $name = "arrowmarker";
            $name_arg = $this->arrowmarkers[$i+1]["name"];
            $values[] = "('$name', '$name_arg','0')";
        } 

        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );

        // Create articles
        $random_number_array = range(0, 44);
        shuffle($random_number_array );
        $values = array();
        $location = "deck";
        $sql = "INSERT INTO articles (articles_location, articles_position) VALUES ";
        for( $i=0; $i<45; $i++ ) {
            $values[] = "('$location', '$random_number_array[$i]')";
        }

        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );

        // Create stories
        $values = array();
        $location = "deck";
        $types = ["A", "B", "C", "D"];
        $sql = "INSERT INTO stories (stories_name, stories_type, stories_type_arg, stories_location, stories_location_arg) VALUES ";

        for( $i=0; $i<5; $i++ ) {
            $name = $this->arrowmarkers[$i+1]["name"];
            for( $j=0; $j<4; $j++ ) {
                $type = $types[$j];

                for( $k=1; $k<4; $k++ ) {
                    $values[] = "('$name', '$type','$k', '$location', '$k'-1)";
                }
            }
        } 

        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue( 'articles_in_play', 0 );
        self::setGameStateInitialValue( 'selected_story', 0 );
        self::setGameStateInitialValue( 'number_of_claimed_stories', 0 );
        self::setGameStateInitialValue( 'number_of_claimed_topbeats', 0 );
        self::setGameStateInitialValue( 'number_top_stories_top_edge', 0 );
        self::setGameStateInitialValue( 'number_top_stories_rest', 0 );
        self::setGameStateInitialValue( 'placed_top_stories_top_edge', 0 );
        self::setGameStateInitialValue( 'placed_top_stories_rest', 0 );
        self::setGameStateInitialValue( 'final_edition', 0 );
        self::setGameStateInitialValue( 'two_player_turn_marker', 1);

        for( $i=0; $i<5; $i++ ) {
            self::setGameStateInitialValue( 'selected_reporter'.$i, 0 );
        }

        // Init game statistics
        self::initStat( 'table', 'turns_number', 0 ); 
        self::initStat( 'player', 'turns_number', 0 );  
        self::initStat( 'player', 'claimed_number', 0 );  
        self::initStat( 'player', 'published_number', 0 );  
        self::initStat( 'player', 'publishing_points', 0 );
        self::initStat( 'player' , 'exclusive_points', 0 );
        self::initStat( 'player', 'empty_spaces', 0 );
        self::initStat( 'player', 'deduct_points', 0 );   
        self::initStat( 'player', 'final_scoring_points', 0 );  
        self::initStat( 'player', 'scoop_points', 0 );  

        // Draw initial articles
        for( $i=0; $i<(count($players)); $i++ ) {
            $this->newArticle(null);
        } 

        // Update markers
        $this->adjustNewsBeats();

        // Set strike for player
        if (self::getGameStateValue('gameVariant') == 2 ) {
            $player = self::getPlayerAfter( self::getNextPlayerTable()[0] );
            $sql = "UPDATE player SET player_strike = 1 WHERE player_id = '$player' ";
            self::DbQuery( $sql );
        }

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        // Information about players
        $sql = "SELECT player_id id, player_score score, player_advertisment advertisment, player_penny penny FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        //Information about tokens
        $sql = "SELECT tokens_id id, tokens_type type, tokens_type_arg arg, tokens_location location FROM tokens ";
        $tokens = self::getCollectionFromDb( $sql );

        $meeples = array_filter($tokens, function($v) {
            return( substr($v['type'], 0, 6) === 'meeple'); } 
        );

        $bonusmarkers = array_filter($tokens, function($v) {
            return( substr($v['type'], 0,5) === 'bonus'); } 
        );

        $arrowmarkers = array_filter($tokens, function($v) {
            return( substr($v['type'], 0,5) === 'arrow'); } 
        );

        foreach ($meeples as &$element) {
            $element = array_slice($element, 1);
        }

        foreach ($bonusmarkers as &$element) {
            $element = array_slice($element, 1);
        }

        foreach ($arrowmarkers as &$element) {
            $element = array_slice($element, 1);
        }

        $result['meeples'] = $meeples;
        $result['bonusmarkers'] = $bonusmarkers;
        $result['arrowmarkers'] = $arrowmarkers;

        //Information about stories
        $sql = "SELECT stories_id id, stories_name name, stories_type type, stories_type_arg stars, stories_location location, stories_location_arg arg, stories_location_backup claimed_location FROM stories ";
        $stories = self::getCollectionFromDb( $sql );

        foreach ($stories as &$element) {
            $element = array_slice($element, 1);
        }

        $result['stories'] = $stories;

        $result['claimedArrowValues'] = array();
        foreach ($stories as $story) {
            $placed = false;
            if (isset(explode("_", $story['location'])[2]) ) {
                if ( explode("_", $story['location'])[2] == self::getActivePlayerId() ) {
                    $placed = true;
                }
            }
            
            if($story['arg'] == 3 || $placed ) {
                $result['claimedArrowValues']['value'][] = $this->getArrowPoints($story['name'],'main');
                $result['claimedArrowValues']['position'][] = $story['claimed_location'];
            }
        }

        $result['claimedStoriesNumber'] = self::getGameStateValue( 'number_of_claimed_stories');
        $result['claimedTopBeatsNumber'] = self::getGameStateValue('number_of_claimed_topbeats');

        //Information about articles
        $sql = "SELECT articles_id id, articles_location location, articles_position position FROM articles WHERE (articles_location != 'deck') ORDER BY articles_position ASC";
        $articles = self::getCollectionFromDb( $sql );
        $result['articles'] = array_keys($articles);

        $text = array();
        foreach ($result['articles'] as $id) {
            $texts[] = $this->articles[$id]['headline'];
        }

        $result['headlines'] = $texts;

        $result['final_edition'] = self::getGameStateValue('final_edition');

        if (self::getGameStateValue('gameVariant') == 2 ) {
            $sql = "SELECT player_id FROM player WHERE player_strike = 1 ";
            $result['strike'] = self::getUniqueValueFromDB( $sql );
        }
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $players = self::getPlayersNumber();

        if (self::getGameStateValue( 'final_edition') == 0) {  // count number pf pennies of all players and add number of meeples in play
            if ($players <4) {
                $needed_for_final_edition = $players*4-$players+1;
            } else {
                $needed_for_final_edition = $players*3-$players+1;
            }

            $sql = "SELECT player_penny FROM player";
            $pennies = array_sum(self::getObjectListFromDB($sql, true));

            $value_A = (90/$needed_for_final_edition)*$pennies;

            $sql = "SELECT COUNT(tokens_id) FROM tokens WHERE  SUBSTRING(tokens_type, 1, 6) = 'meeple' AND SUBSTRING(tokens_location, 1, 4) <> 'home' ";
            $tokensInPlay = self::getUniqueValueFromDB( $sql );
            $value_B = ((90/$needed_for_final_edition)/($players*5))*$tokensInPlay;

        } else {        // count number of moves left in case of final edition 
            $value_A = 90;

            $sql = "SELECT COUNT(player_id) FROM player WHERE  player_final_edition_state = 0";
            $two_moves_players = self::getUniqueValueFromDB( $sql );
            $sql = "SELECT COUNT(player_id) FROM player WHERE  player_final_edition_state = 1";
            $one_move_players = self::getUniqueValueFromDB( $sql );

            $max_moves = ($players-1) *2;
            $moves = $max_moves - ($two_moves_players*2 + $one_move_players);

            $value_B = (10/$max_moves)*$moves;
        }

        return $value_A+$value_B;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function newArticle($player){
        $next_article = self::getGameStateValue( 'articles_in_play')+1;
        $article_id = self::getUniqueValueFromDB( "SELECT articles_id id FROM articles WHERE articles_position='$next_article'" );

        self::DbQuery( "UPDATE articles SET articles_location = 'inplay' WHERE articles_id = '$article_id'  " );

        // self::notifyAllPlayers('newArticle',clienttranslate('New headline card comes into play: ${card_text}'), array ( 
        //     'id' => $article_id, 'i18n' => array( 'card_text' ), 'card_text' => $this->articles[$article_id]["headline"]
        // ));

        self::notifyAllPlayers('newArticle',clienttranslate('New headline card comes into play: ${card_text}'), array ( 
            'id' => $article_id, 'card_text' => $this->articles[$article_id]["headline"]
        ));        

        $bonus = $this->articles[$article_id]["bonus"];
        $stories = $this->articles[$article_id]["stories"];
        $advertisment = $this->articles[$article_id]["advertisment"];

        $this->updateBonusMarker($bonus[0], $bonus[1]);

        foreach ($stories as $story) {
            $this->placeStoryOnBoard($story[0], $story[1]);
        }

        if( $player != null) {
            $this->setAdvertisment($player, $advertisment);
        }

        self::setGameStateValue( 'articles_in_play', $next_article );
    }

    function updateBonusMarker($name,$value) {
        $from =  self::getUniqueValueFromDB( "SELECT tokens_location FROM tokens WHERE tokens_type = 'bonusmarker' AND tokens_type_arg = '$name'  " ) +0;

        if ( $from < 20) {   
            $value = ( ($value+$from) > 20 )? ($value+$from-20) : $value;
            $log_name = 'bonustrack_'.$name;

            self::DbQuery( "UPDATE tokens SET tokens_location = tokens_location+'$value' WHERE tokens_type = 'bonusmarker' AND tokens_type_arg = '$name'  " );

            self::notifyAllPlayers('bonusMarkerChange',clienttranslate('Bonus marker ${token_name} is updated (+${value})'), array ( 
                'name' => $name, 'from' => $from, 'value' => $value, 'token_name' => $log_name
            )); 
        }
    }

    function placeStoryOnBoard($name,$type) {              
        $sql = "SELECT COUNT(stories_id) FROM stories WHERE stories_name = '$name' AND stories_type = '$type' AND stories_location = 'deck' ";
        $numberInDeck = self::getUniqueValueFromDB( $sql);
        $sql = "SELECT stories_type_arg FROM stories WHERE stories_name = '$name' AND stories_type = '$type' AND stories_location = 'deck' AND stories_location_arg = 0 ";
        $stars = self::getUniqueValueFromDB( $sql);

        if ($numberInDeck > 0 ) {
            $tile = $this->getPositionForNewStory($name,$type); 
            $log_name = 'story_'.$name.'_'.$type.'_'.$stars;

            if ($tile < 9) {
                $position = 'newsbeattile_'.$name.'_'.$tile ;

                $sql = "UPDATE stories SET stories_location = '$position', stories_location_backup = '$tile '  WHERE stories_name = '$name' AND stories_type = '$type' AND stories_type_arg = '$stars' ";
                self::DbQuery($sql);

                self::notifyAllPlayers('newStoryToBeatTrack',clienttranslate('New story is placed on the board: ${token_name}'), array ( 
                    'name' => $name, 'type' => $type, 'stars'=>$stars, 'position' => $tile, 'token_name' => $log_name
                )); 

                for ($i=0;$i<($numberInDeck-1);$i++) { //rearange in deck
                    $sql = "UPDATE stories SET stories_location_arg = stories_location_arg -1 WHERE stories_name = '$name' AND stories_type = '$type' AND stories_location_arg = '$i'+1 ";
                    self::DbQuery($sql);
                }
            } else {
                self::notifyAllPlayers('logInfo', clienttranslate('This story cannot be placed: ${token_name}'), array ( 
                     'token_name' => $log_name
                )); 
            }

        }  else {
            self::notifyAllPlayers('logInfo',clienttranslate('No more stories in the pile, nothing is placed on the board'), array ( 
            )); 
        }
    }

    function getPositionForNewStory($name,$type) {
        $position = $this->stories_size[$type]["W"]-1;
        $sql = "SELECT stories_type FROM stories WHERE stories_name = '$name' AND (SUBSTRING(stories_location,1,12) = 'newsbeattile') ";
        $stories = self::getObjectListFromDB($sql, true);

        foreach ($stories as $story) {
            $position += $this->stories_size[$story]["W"];
        }

        return $position;
    }

    function updateArrowMarkerInDB($name,$value, $replace) {
        if ($replace === true) {
            self::DbQuery( "UPDATE tokens SET tokens_location = '$value' WHERE tokens_type = 'arrowmarker' AND tokens_type_arg = '$name'  ");
        } else {
            self::DbQuery( "UPDATE tokens SET tokens_location = tokens_location+'$value' WHERE tokens_type = 'arrowmarker' AND tokens_type_arg = '$name'  ");
        }
    }

    function setAdvertisment($player,$position) {
        $sql = "SELECT player_penny FROM player WHERE player_id = '$player'  ";
        $y = 3 - self::getUniqueValueFromDB( $sql ); 

        $advertisment = $position."_".$y;

        $sql = "UPDATE player SET player_advertisment = '$advertisment' WHERE player_id = '$player'  ";
        self::DbQuery( $sql );
        
        self::notifyAllPlayers('newAdvertisment',clienttranslate('An advertisement is placed on ${player_name}\'s front page'), array ( 
            'player_name' => self::getActivePlayerName(), 'player_id' => $player, 'y' => $y, 'x'=>$position
        )); 
    }

    function adjustNewsBeats() {                                                                            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // $sql = "SELECT stories_name name, stories_type type, stories_type_arg stars FROM stories WHERE  SUBSTRING(stories_location, 1, 4) <> 'deck' AND SUBSTRING(stories_location, 9, 3) <> 'pub'  ";
        $sql = "SELECT stories_name name, stories_type type, stories_type_arg stars FROM stories WHERE  SUBSTRING(stories_location, 1, 4) <> 'deck' AND SUBSTRING(stories_location, -9, 3) <> 'pub'  ";
        $storiesInPlay = self::getObjectListFromDB( $sql );

        $sql = "SELECT tokens_location loc FROM tokens WHERE  SUBSTRING(tokens_type, 1, 6) = 'meeple' AND SUBSTRING(tokens_location, 1, 4) <> 'home' ";
        $tokensInPlay = self::getObjectListFromDB( $sql,true );
      
        foreach ($this->arrowmarkers as $marker) {
            $name = $marker['name'];
            $baseValue = 0;
            $increment = 0;

            $storiesInField = array_filter($storiesInPlay,function($v) use( $name) {return( $v['name'] === $name); }); 

            foreach ($storiesInField as $story) {
                $baseValue += $this->stories_size[$story['type']]["W"];
                $compreString = $name.'_'. $story['type'].'_'. $story['stars'];
                if (in_array($compreString, $tokensInPlay)) {
                    $increment++;
                }
            }
            $oldValue = self::getUniqueValueFromDB( "SELECT tokens_location FROM tokens WHERE tokens_type = 'arrowmarker' AND tokens_type_arg = '$name'  ");

            if ($oldValue != ($baseValue+$increment) ) {               //!!!!!!!!!!!!!!!!!!!!!
                if ( ($baseValue+$increment) > max(array_keys($this->arrow_marker_values)) ) {
                    $this->updateArrowMarkerInDB($name, max(array_keys($this->arrow_marker_values)), true );

                    self::notifyAllPlayers('adjustNewsBeat', "", array ('name' => $name, 'value' => max(array_keys($this->arrow_marker_values)) ));
                } else {
                    $this->updateArrowMarkerInDB($name, $baseValue+$increment, true );

                    self::notifyAllPlayers('adjustNewsBeat', "", array ('name' => $name, 'value' => $baseValue+$increment ));
                }

            }
        }
    }

    function getArrowPoints($story, $value) {
        $name = explode("_", $story)[0];
        $marker_position = self::getUniqueValueFromDB( "SELECT tokens_location FROM tokens WHERE tokens_type = 'arrowmarker' AND tokens_type_arg = '$name' ");

        if ($value === 'main' ) {
            return $this->arrow_marker_values[$marker_position][0];
        } 

        if ($value === 'scoop' ) {
            return $this->arrow_marker_values[$marker_position][1]; 
        }
    }

    function getCoordsFromType($x, $y, $type, $rotation) {
        if ($rotation == 0) {
            $x_end = $x+$this->stories_size[$type]['W']-1;
            $y_end = $y+$this->stories_size[$type]['H']-1;
        } else {
            $x_end = $x+$this->stories_size[$type]['H']-1;
            $y_end = $y+$this->stories_size[$type]['W']-1;
        }

        return  array($x_end,  $y_end);
    }

    function isStoryFromTopBeat($name) {                                                            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $markers = self::getCollectionFromDB( "SELECT tokens_type_arg n, tokens_location loc FROM tokens WHERE tokens_type = 'arrowmarker' ");

        $max_value = 0;

        foreach( $markers as $marker ) {
            if ( $this->arrow_marker_values[$marker['loc']][0] > $max_value ) {
            // if ( $marker['loc'] > $max_value ) {    
                $max_value = $this->arrow_marker_values[$marker['loc']][0];
            }
        }

        $topBeats = array();

        foreach( $markers as $marker ) {
            if ( $this->arrow_marker_values[$marker['loc']][0] === $max_value ) {
            // if ( $marker['loc'] == $max_value ) {
                array_push($topBeats, $marker['n']) ;
            }
        }

        if (in_array($name, $topBeats)) {
            return true;
        } else {
            return false;
        }
    }

    function buildFrontPage($player_id, $scoring) {                               //// substring
        // $sql = "SELECT stories_id id, stories_name n, stories_type t, stories_type_arg s, stories_location loc, stories_location_arg rot FROM stories WHERE (SUBSTRING(stories_location,-7) = '$player_id') ";
        $sql = "SELECT stories_id id, stories_name n, stories_type t, stories_type_arg s, stories_location loc, stories_location_arg rot FROM stories WHERE (SUBSTRING(stories_location,5) = '$player_id') ";
        $stories = self::getCollectionFromDB($sql);

        $sql = "SELECT player_advertisment adv FROM player WHERE player_id ='$player_id' ";
        $advertisment = self::getUniqueValueFromDB($sql); 

        if ($scoring) {
            $front_page = array( array( -2, -2, -2, -2), array( -1, -1, -1, -1), array( -1, -1, -1, -1) );
        } else {
            $front_page = array( array( null, null, null, null), array( null, null, null, null), array( null, null, null, null) );
        }

        if ($advertisment != null) {
            $front_page[explode("_", $advertisment)[1]][explode("_", $advertisment)[0]] = 'A';
        }

        foreach( $stories as $story) { 
            $x_start = explode("_", $story['loc'])[0]; 
            $y_start = explode("_", $story['loc'])[1]; 

            list($x_end, $y_end) = $this->getCoordsFromType($x_start,$y_start, $story['t'], $story['rot'] );

            for ($i=$y_start;$i<=$y_end;$i++) {
                for ($j=$x_start;$j<=$x_end;$j++) {
                    $front_page[$i][$j] = $story['id'];
                }
            }
        }

        return $front_page;
    }

    function buildTestFrontPage($stories,$positions,$advertisment) {
        $front_page = array( array( null, null, null, null), array( null, null, null, null), array( null, null, null, null) );

        if ($advertisment != null) {
            $front_page[explode("_", $advertisment)[1]][explode("_", $advertisment)[0]] = 'A';
        }

        $pos = 0;
        foreach( $stories as $story) { 
            $x_start = $positions[$pos]['x']; 
            $y_start = $positions[$pos]['y'];

            list($x_end, $y_end) = $this->getCoordsFromType($x_start,$y_start, $story['t'], $story['r'] );

            for ($i=$y_start;$i<=$y_end;$i++) {
                for ($j=$x_start;$j<=$x_end;$j++) {
                    $front_page[$i][$j] = 1;
                }
            }
            $pos++;
        }

        return $front_page;
    }

    function checkNewTestPlacement($front_page,$story, $x, $y) {
        $player_id = self::getActivePlayerId();

        list($x_end, $y_end) = $this->getCoordsFromType($x,$y, $story['t'], $story['r']);

        if ($x_end > 3) {
            return false;
        }

        if ($y_end > 2) {
            return false;
        }
        
        $result = true;

        for ($i=$y;$i<=$y_end;$i++) {
            for ($j=$x;$j<=$x_end;$j++) {
                if ( !is_null($front_page[$i][$j]) ) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    
    function checkNewPlacement($story, $x, $y, $rotation) {
        $player_id = self::getActivePlayerId();

        list($x_end, $y_end) = $this->getCoordsFromType($x,$y, explode("_", $story)[1], $rotation);

        $front_page = $this->buildFrontPage($player_id, false);
        
        $result = array(true,true);

        for ($i=$y;$i<=$y_end;$i++) {
            for ($j=$x;$j<=$x_end;$j++) {

                if ($i<3 && $j<4 ) {
                    if ( !is_null($front_page[$i][$j]) ) {
                        $result[0] = false;
                    }
                } else {
                    $result[0] = false;
                }

            }
        }

        if ( $this->isStoryFromTopBeat(explode("_", $story)[0]) ) {
            if ($y != 0) {
                $result[1] = false;
            }
        }

        return $result;
    }

    function updateStoriesPosition() {     // substring
        // $sql = "SELECT stories_name name, stories_type type, stories_type_arg stars, SUBSTRING(stories_location, -2) loc FROM stories WHERE  SUBSTRING(stories_location, 1, 4) <> 'deck' AND SUBSTRING(stories_location, 9, 3) <> 'pub'  ";
        $sql = "SELECT stories_name name, stories_type type, stories_type_arg stars, SUBSTRING(stories_location, -2) loc FROM stories WHERE  SUBSTRING(stories_location, 1, 4) <> 'deck' AND SUBSTRING(stories_location, -9, 3) <> 'pub'  ";
        $storiesInPlay = self::getObjectListFromDB( $sql );

        $storiesToUpdate = array();
      
        foreach ($this->arrowmarkers as $marker) {
            $name = $marker['name'];

            $storiesInField = array_filter($storiesInPlay,function($v) use( $name) {return( $v['name'] === $name); });
            array_walk($storiesInField,function(&$v) {
                if ( substr($v['loc'],0,1) === "_" ) {
                    $v['loc'] = substr($v['loc'],1,1) ; 
                }
            });    
            array_multisort( array_column($storiesInField, "loc"), SORT_ASC, $storiesInField ); 
            array_values($storiesInField);

            $shift = 0;

            for ($i=0; $i< count($storiesInField) ; $i++) {
                if ($i == 0) {
                    $gap = $storiesInField[$i]['loc'] +1;
                } else {
                    $gap = $storiesInField[$i]['loc'] - $storiesInField[$i-1]['loc'];
                }

                $height = $this->stories_size[$storiesInField[$i]['type']]['W'];

                if ($gap > $height) {
                    $shift += $gap - $height;
                }

                if ($shift >0) {
                    $new = $storiesInField[$i]['loc'] - $shift;
                    $storiesToUpdate[] = $storiesInField[$i];
                    
                    end($storiesToUpdate);
                    $storiesToUpdate[key($storiesToUpdate)]['new_loc'] = $new;

                    $string = "newsbeattile_".$name."_".($new);
                    $n = $storiesInField[$i]['name'];
                    $t = $storiesInField[$i]['type'];
                    $s = $storiesInField[$i]['stars'];
                    $sql = "UPDATE stories SET stories_location = '$string', stories_location_backup = '$new' WHERE stories_name = '$n' AND stories_type = '$t' AND stories_type_arg = '$s' ";
                    self::DbQuery($sql);
                }
            } 
        }


        self::notifyAllPlayers('updateStoriesPosition', '', array ( 
            'stories' =>  $storiesToUpdate
        )); 
    }

    function mayGoToPress($player_id) {
        $storiesNbr = 0;

        $sql = "SELECT stories_name n, stories_type t, stories_type_arg s, stories_location l FROM stories WHERE  SUBSTRING(stories_location, 1, 12) = 'newsbeattile' ";
        $storiesInPlay = self::getObjectListFromDB( $sql );

        foreach ($storiesInPlay as $story) {
            $name = $story['n'];
            $type = $story['t'];
            $stars = $story['s'];

            $player_token = "meeple_".$player_id;
            $position = $name."_".$type."_".$stars;
            $sql = "SELECT COUNT(tokens_id) FROM tokens WHERE tokens_type = '$player_token' AND tokens_location = '$position' ";
            $player_meeples = self::getUniqueValueFromDB($sql);

            if ($player_meeples > 0) {

                $sql = "SELECT player_id id FROM player ";
                $players = self::getObjectListFromDB($sql, true);

                if (($key = array_search($player_id, $players)) !== false) {
                    unset($players[$key]);
                }

                $thisStoryPossible = true;

                foreach($players as $other_player){
                    $player_token = "meeple_".$other_player;
                    $sql = "SELECT COUNT(tokens_id) FROM tokens WHERE tokens_type = '$player_token' AND tokens_location = '$position' ";
                    $other_player_meeples = self::getUniqueValueFromDB($sql);

                    if ($other_player_meeples > $player_meeples){
                        $thisStoryPossible = false;
                        break;
                    }
                };

                if ( $thisStoryPossible == true) {
                    $storiesNbr++;
                }
            }

        }

        return $storiesNbr;
    }

    function getCombinations($array, $k) {  // get k-length combinations of array
        $len = count($array);
        $combs = array();


        if ($k > $len|| $k <= 0) {
            return array();
        }
        
        if ($k == $len) {
            return array($array);
        }
        
        if ($k == 1) {
            for ($i = 0; $i < $len; $i++) {
                $combs[] = array($array[$i]);
            }
            return $combs;
        }

        for ($i = 0; $i < $len - $k + 1; $i++) {
            $head = array($array[$i]);
            $tail = array_slice($array, ($i+1));
            $tailcombs = $this->getCombinations($tail, $k - 1);
            foreach($tailcombs as $comb) {
                if (!is_array($comb)){
                    $c = array($comb);
                } else {
                    $c = $comb;              
                }
                $combs[] = array_merge($head,$c);
            }
        }

        return $combs;
    }

    function getPermutations($array) { // get all permutations of array
        $result = array();
        $len = count($array);

        if ($len == 1) {
            return array($array);
        }

        for ($i = 0; $i < $len ; $i++) {
            $actual = array($array[$i]);

            $rest = $array;
            unset($rest[$i]);
            $rest = array_values($rest);

            $restperms = $this->getPermutations($rest);

            foreach($restperms as $perm) {
                $result[] = array_merge($actual,$perm);
            }
        }

        return $result;
    }

    function calculateFrontPage($topBeat_stories,$player_id) {                 // cleaning!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $sql = "SELECT player_advertisment adv FROM player WHERE player_id ='$player_id' ";
        $advertisment = self::getUniqueValueFromDB($sql); 

        $ntice = array();
        $topBeatStories_count = count($topBeat_stories);

        if ($topBeatStories_count == 0) {
            return;
        }

        $rotationPositions = array();
        $rotationPositions_combs = array();
        $skipp = array();

        foreach($topBeat_stories as $story) {
            $name = explode("_", $story)[0];
            $type = explode("_", $story)[1];
            $stars = explode("_", $story)[2];

            $ntice[0][array_search($story, $topBeat_stories)] = array('n' => $name, 't' => $type, 's' => $stars, 'r' => 0 );

            if ( $type == 'C' ) {
                $skipp[] = array_search($story, $topBeat_stories);
            }
        }

        for ($i=0;$i<(count($topBeat_stories));$i++) {
            if (!in_array($i,$skipp)) {
                $rotationPositions[] = $i;      //all stories that could be rotated
            }
        }

        for ($i=0;$i<count($topBeat_stories);$i++) {
            $rotationPositions_combs[] = $this->getCombinations($rotationPositions, ($i+1));  //combinations of position
        }

        $pos = 0;
        foreach($rotationPositions_combs as $actComb) { // all possible sets
            $len = count($actComb);

            for ($i=0;$i<$len;$i++) {
                    $ntice[] = $ntice[0];

                    for ($j=0;$j<count($actComb[$i]);$j++) {
                        $ntice[$pos+1][$actComb[$i][$j]]['r'] = 1;
                    }

                    $pos++;
            }
        }

        $allPosibleCombinations = array();

        foreach ($ntice as $actual) {                       // all permutations of all possible sets
            $result = $this->getPermutations($actual);

            $allPosibleCombinations = array_merge($allPosibleCombinations, $result);
        }

        // calculate front page with all permutations of top beat stories at top edge
        $allIterations = array();
        foreach ($allPosibleCombinations as $set) {

            $first_story = $set[0];
            unset($set[0]);
            $rest_stories = $set;
    
            for ($i=0;$i<4;$i++) {
                $positions = array();
                $actual_subset = array();
                $others_subset = array();
                $actual_len = 0;
                $front_page = $this->buildTestFrontPage($actual_subset,$positions, $advertisment);

                if ($this->checkNewTestPlacement($front_page, $first_story,$i, 0) ) {
                    $actual_subset[] = $first_story;
                    $positions[$actual_len]['x'] = $i;
                    $positions[$actual_len]['y'] = 0;
                    $front_page = $this->buildTestFrontPage($actual_subset,$positions, $advertisment);
                    $actual_len++;

                    foreach($rest_stories as $story) {
                        for($x=$i;$x<4;$x++) {
                            if ($this->checkNewTestPlacement($front_page, $story,$x, 0) ) {
                                $actual_subset[] = $story;
                                $positions[$actual_len]['x'] = $x;
                                $positions[$actual_len]['y'] = 0;

                                $front_page = $this->buildTestFrontPage($actual_subset,$positions, $advertisment);
                                $actual_len++; 
                                break 1;
                            }

                            if ($x == 3) {
                                $others_subset[] = $story;
                            }
                        }
                    }
                
            
                    $allIterations[] = array('stories' =>$actual_subset, 'positions' =>$positions, 'len' => $actual_len, 'stories_notfit' => $others_subset );
                }
            }

        }

        if (!empty($allIterations) ) {
            $numberOfStoriesCanBeTop = max(array_column($allIterations, 'len'));
        }  else {
            $numberOfStoriesCanBeTop = array();
        }  

        // filter sets with maximal stories placed
        if (!empty($allIterations) ) {
            $maxLen_iterations = array_filter($allIterations, function ($var) use ($numberOfStoriesCanBeTop) {
                return ($var['len'] == $numberOfStoriesCanBeTop);
            });
        } else {
            $maxLen_iterations = array();
        }

        $allFrontPages = array();
        foreach ($maxLen_iterations as $iteration) {
            $top_edge_set = $iteration['stories'];
            $positions = $iteration['positions'];
            $rest_set = $iteration['stories_notfit'];

            //permute rest set
            $rest_set_permutes = $this->getPermutations($rest_set);

            foreach($rest_set_permutes as $perm) {
                $actual_len = 0;
                $front_page = $this->buildTestFrontPage($top_edge_set,$positions, $advertisment);
                $actual_subset = array();
                $positions_rest = array();

                foreach($perm as $story) {
                    for($x=0;$x<4;$x++) {
                        for($y=0;$y<3;$y++) {
                            if ($this->checkNewTestPlacement($front_page, $story,$x, $y) ) {
                                $actual_subset[] = $story;
                                $positions_rest[$actual_len]['x'] = $x;
                                $positions_rest[$actual_len]['y'] = $y;
        
                                $front_page = $this->buildTestFrontPage(array_merge($top_edge_set,$actual_subset),array_merge($positions,$positions_rest), $advertisment);
                                $actual_len++; 
                                break 2;
                            }
                        }
                    }
                }

                $allFrontPages[] = array('stories' =>$top_edge_set, 'positions' =>$positions, 'len' => $actual_len, 'stories_rest' => $actual_subset, 'positions_rest' =>$positions_rest, 'rest_len' => $actual_len );
            }
        }

        if (!empty($allFrontPages)) {
            $numberOfStoriesCanBeRest = max(array_column($allFrontPages, 'rest_len'));
        } else {
            $numberOfStoriesCanBeRest = 0;
        }

        $frontPagesBest = array_filter($allFrontPages, function ($var) use ($numberOfStoriesCanBeRest) {
            return ($var['rest_len'] == $numberOfStoriesCanBeRest);
        });

        $frontPagesBest = array_values($frontPagesBest);

        self::setGameStateValue( 'number_top_stories_top_edge', $numberOfStoriesCanBeTop );
        self::setGameStateValue( 'number_top_stories_rest', $numberOfStoriesCanBeRest );
    }

    function checkFinaledition($player) {
        $players = self::getPlayersNumber();

        $sql = "SELECT player_penny FROM player WHERE player_id ='$player' ";
        $pennies = self::getUniqueValueFromDB($sql);

        if ( ( $pennies == 2 && $players > 3 ) || ($pennies == 3 && $players < 4 ) ) {
            self::setGameStateValue( 'final_edition', 1 );
            self::notifyAllPlayers('finalEdition', clienttranslate('Final edition is trigered!!'), array (  ));
            
            $sql = "UPDATE player SET player_final_edition_state = 2 WHERE player_id ='$player' ";
            self::DbQuery($sql);

            // if (self::getGameStateValue('gameVariant') == 2) {   

            // }

        }
    }

    function checkNextPlayer() {
        $next_player_id= self::getActivePlayerId();
        $players =self::getPlayersNumber();

        if ( self::getGameStateValue( 'final_edition') == 0 ) { 
            if (self::getGameStateValue( 'two_player_turn_marker' ) == 0 && $players == 2 ) { // two players transition
                self::setGameStateValue( 'two_player_turn_marker',1);
                return $next_player_id;
            }

            if (self::getGameStateValue( 'two_player_turn_marker' ) == 1 && $players == 2 ) { // two players transition
                self::setGameStateValue( 'two_player_turn_marker',0);
                return self::getPlayerAfter( $next_player_id );
            }

            if ($players>2){
                if (self::getGameStateValue('gameVariant') == 2) {                                              // newsboy strike !!!!!!!!!!!!!!!!1
                    $left_player_id = self::getPlayerAfter( $next_player_id );
                    $sql = "SELECT player_id FROM player WHERE player_strike =1 ";
                    $strike_id = self::getUniqueValueFromDB($sql);

                    if ($strike_id == $left_player_id) {
                        $sql = "UPDATE player SET player_strike = 0 WHERE player_id ='$left_player_id' ";
                        self::DbQuery($sql);
                        $sql = "UPDATE player SET player_strike = 1 WHERE player_id ='$next_player_id' ";
                        self::DbQuery($sql);

                        self::notifyAllPlayers('strike', clienttranslate('${player_name}\'s newsboy on strike!'), array (  
                            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$left_player_id' ", true ), 'new_strike_player_id' => $next_player_id
                        ));

                        return self::getPlayerAfter( $left_player_id );
                    } else {
                        return $left_player_id;
                    }

                } else {
                    return self::getPlayerAfter( $next_player_id );
                }
            }

        } else {                                                                

                for($i=0;$i<$players;$i++) {
                    $next_player_id = self::getPlayerAfter( $next_player_id );

                    $sql = "SELECT player_zombie FROM player WHERE player_id ='$next_player_id' ";
                    $zombie = self::getUniqueValueFromDB($sql);

                    if ( $zombie == 0) {
                        $sql = "SELECT player_final_edition_state FROM player WHERE player_id ='$next_player_id' ";
                        $final_state = self::getUniqueValueFromDB($sql);

                        if ($final_state<2) {
                            $sql = "SELECT COUNT(stories_id) FROM stories WHERE  SUBSTRING(stories_location, 1, 12) = 'newsbeattile' ";
                            $storiesInPlay = self::getUniqueValueFromDB( $sql );

                            if ($storiesInPlay > 0) {
                                // if ( $final_state == 1 && $this->mayGoToPress($next_player_id)) {
                                if ( $final_state == 1 && $this->mayGoToPress($next_player_id)>0 ) {
                                    return $next_player_id;
                                } 
                                if ($final_state == 0) {
                                    return $next_player_id;
                                }

                                $sql = "UPDATE player SET  player_final_edition_state = 2 WHERE player_id ='$next_player_id' "; 
                                $final_state = self::DbQuery($sql);

                                self::notifyAllPlayers('logInfo',clienttranslate('${player_name} cannot play, turn is skipped'), array ( 
                                    'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$next_player_id' ", true ), 'player_id' => $next_player_id
                                ));

                            } else {
                                self::notifyAllPlayers('logInfo',clienttranslate('No more stories in play, rest of turns are skipped'), array ( ));
                                break;
                            }
                        }
                    }
                }
        }

        return null;
    }

    function getActivePlayerFinalEditionPhase () {
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_final_edition_state pf  FROM player WHERE player_id = '$player_id' ";
        $state = self::getUniqueValueFromDB($sql);
        return $state;
    }

    function finalScoring() {
        $sql = "SELECT player_id id FROM player ORDER BY player_no";
        $players = self::getObjectListFromDB($sql, true);
        $playerTable = array();

        foreach($players as $player) {
            $playerTable[$player] = array(0,0,0,0,0);

            $stories = self::getStat( 'published_number', $player);
            $sql = "UPDATE player SET player_score_aux = '$stories' WHERE player_id ='$player' ";
            self::DbQuery($sql);
        } 

        $index = 0;
        foreach ($this->arrowmarkers as $marker) {
            $name = $marker['name'];
            $sql = "SELECT tokens_location l  FROM tokens WHERE tokens_type='bonusmarker' AND tokens_type_arg = '$name' ";
            $value_to_score = self::getUniqueValueFromDB($sql, true);
            $scoreTable = array();


            foreach($players as $player) {
                $loc = $player.'_published';
                $sql = "SELECT stories_type_arg s  FROM stories WHERE stories_name='$name' AND stories_location = '$loc' ";
                $storiesToScore = self::getObjectListFromDB($sql, true);

                $scoreTable[$player] = array_sum($storiesToScore);
            }

            $maximum = max($scoreTable);

            if ($maximum != 0) {
                $player_keys = array_keys($scoreTable, $maximum);

                foreach($player_keys as $player_id) {
                    // give points to player
                    self::DbQuery( "UPDATE player SET player_score=player_score+'$value_to_score' WHERE player_id='$player_id'" );

                    $log_name = "bonustrack_".$name;
                    self::notifyAllPlayers('finalScoring',clienttranslate('${player_name} scores ${x} points for most ${token_name} stories'), array ( 
                        'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 'player_id' => $player_id,
                        'x' => $value_to_score, 'name' => $name, 'token_name' => $log_name
                    ));

                    self::incStat( $value_to_score, 'final_scoring_points', $player_id );
                    $playerTable[$player_id][$index] = $value_to_score;
                }
            }
            $index++;
        }   

        $scoreSumText = "";
        foreach($players as $player) {

            $scoreSumText = $scoreSumText.$player.":";
            for($i=0;$i<5;$i++) {
                $scoreSumText = $scoreSumText.$playerTable[$player][$i].":";
            }
            $scoreSumText = $scoreSumText."_";
        }

        self::notifyAllPlayers('finalScoringSum',"", array ( 
            'scores' =>  $scoreSumText
        ));
    }


    function test()  {
        // $scoreSumText = "";
        // $sql = "SELECT player_id id FROM player ORDER BY player_no";
        // $players = self::getObjectListFromDB($sql, true);

        // foreach($players as $player) {
        //     $scoreSumText = $scoreSumText.$player.":".self::getStat( 'final_scoring_points', $player)."_";
        // }

        // self::notifyAllPlayers('finalScoringSum',"", array ( 
        //     'scores' =>  $scoreSumText
        // ));
        $this->finalScoring();
    }

    function calculateFreeSpace($claimed_stories) {
        $result = array();
        foreach($claimed_stories as $story) {

            for ($i=0;$i<3;$i++) {
                for ($j=0;$j<4;$j++) {
                    if ($this->checkNewPlacement($story,$j,$i, 0)[0] ) {
                        $result[$story] = array('x' => $i, 'y'=> $j,'rotation' => 0);
                        break 2;
                    }

                    if ($this->checkNewPlacement($story,$j,$i, 1)[0] ) {
                        $result[$story] = array('x' => $i, 'y'=> $j,'rotation' => 1);
                        break 2;
                    }  
                    
                    if ($i == 2 && $j==3) {
                        $result[$story] = array('x' => null, 'y'=> null,'rotation' => null);
                    }
                }
            }

        }

        return $result;
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in pennypress.action.php)
    */

    // function selectStory($story) {
    //     self::checkAction( 'selectStory' ); 

    //     if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() > 0 ) {
    //         throw new BgaUserException( self::_("You can only go to press") );
    //     }

    //     if ($story[0]==='_') {
    //         throw new BgaUserException( self::_("You cannot select opponents pawn") );
    //     }

    //     $name = explode("_", $story)[0];
    //     $type = explode("_", $story)[1];
    //     $stars = explode("_", $story)[2];

    //     $selected_id = self::getUniqueValueFromDB( "SELECT stories_id FROM stories WHERE stories_name='$name' AND stories_type='$type' AND stories_type_arg = '$stars' " );
    //     self::setGameStateValue( 'selected_story', $selected_id);

    //     $this->gamestate->nextState( "selectStory" );
    // }

    // function sendReporters($number) {                              // shorthand send !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    //     self::checkAction( 'sendReporters' );
        
    //     if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() > 0 ) {
    //         throw new BgaUserException( self::_("You can only go to press") );
    //     }

    //     if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() == 0 && $number > 1) {
    //         throw new BgaUserException( self::_("You can only place one reporter during final edition") );
    //     }

    //     $player = self::getActivePlayerId();

    //     $story_id = self::getGameStateValue( 'selected_story');
    //     $sql = "SELECT stories_name n, stories_type t, stories_type_arg s  FROM stories WHERE stories_id='$story_id' ";
    //     $story_name = self::getObjectFromDB($sql);
    //     $story_name = $story_name['n'].'_'.$story_name['t'].'_'.$story_name['s'];

    //     $type = 'meeple_'.$player;
    //     $sql = "SELECT tokens_type_arg arg FROM tokens WHERE tokens_type = '$type' AND tokens_location = 'home' ";
    //     $free_meeples = self::getObjectListFromDB( $sql,true );

    //     $sent_reporters = array();
    //     for ($i=0;$i<$number;$i++) {
    //         $arg = $free_meeples[$i];
    //         $sql = "UPDATE tokens SET tokens_location = '$story_name' WHERE tokens_type = '$type' AND tokens_type_arg = '$free_meeples[$i]' ";
    //         self::DbQuery( $sql );
    //         $sent_reporters[] = 'pawn_'.$free_meeples[$i].'_'.$player;
    //     }

    //     if ($number === 1) {
    //         self::notifyAllPlayers('sendReporters', clienttranslate('${player_name} send ${x} reporter to story'), array ( 
    //             'player_name' => self::getActivePlayerName(), 'x' => $number, 'reporters' => $sent_reporters, 'story' => $story_name
    //         )); 
    //     } else {
    //         self::notifyAllPlayers('sendReporters', clienttranslate('${player_name} send ${x} reporters to story'), array ( 
    //             'player_name' => self::getActivePlayerName(), 'x' => $number, 'reporters' => $sent_reporters, 'story' => $story_name
    //         )); 
    //     }

    //     if ( self::getGameStateValue( 'final_edition') == 1 ) {
    //         $sql = "UPDATE player SET player_final_edition_state = 1 WHERE player_id ='$player' ";
    //         self::DbQuery($sql);
    //     }

    //     $this->gamestate->nextState("sendReporters");
    // }

    function sendReportersFast($number, $story) {
        self::checkAction( 'sendReportersFast' );
        
        if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() > 0 ) {
            throw new BgaUserException( self::_("You can only go to press") );
        }

        if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() == 0 && $number > 1) {
            throw new BgaUserException( self::_("You can only place one reporter during final edition") );
        }

        $player = self::getActivePlayerId();

        $type = 'meeple_'.$player;
        $sql = "SELECT tokens_type_arg arg FROM tokens WHERE tokens_type = '$type' AND tokens_location = 'home' ";
        $free_meeples = self::getObjectListFromDB( $sql,true );

        $sent_reporters = array();
        for ($i=0;$i<$number;$i++) {
            $sql = "UPDATE tokens SET tokens_location = '$story' WHERE tokens_type = '$type' AND tokens_type_arg = '$free_meeples[$i]' ";
            self::DbQuery( $sql );
            $sent_reporters[] = 'pawn_'.$free_meeples[$i].'_'.$player;
        }

        if ($number == 1) {
            self::notifyAllPlayers('sendReporters', clienttranslate('${player_name} assigns ${x} reporter to story'), array ( 
                'player_name' => self::getActivePlayerName(), 'x' => $number, 'reporters' => $sent_reporters, 'story' => $story
            )); 
        } else {
            self::notifyAllPlayers('sendReporters', clienttranslate('${player_name} assigns ${x} reporters to story'), array ( 
                'player_name' => self::getActivePlayerName(), 'x' => $number, 'reporters' => $sent_reporters, 'story' => $story
            )); 
        }

        if ( self::getGameStateValue( 'final_edition') == 1 ) {
            $sql = "UPDATE player SET player_final_edition_state = 1 WHERE player_id ='$player' ";
            self::DbQuery($sql);
        }

        $this->gamestate->nextState("sendReportersFast");
    }

    function cancelPlayerTurn() {
        self::checkAction( 'cancel' ); 
        self::setGameStateValue( 'selected_story', 0);
        for( $i=0; $i<5; $i++ ) {
            self::setGameStateValue( 'selected_reporter'.$i, 0 );
        }
        $this->gamestate->nextState("cancel");
    }

    function selectReporter($reporter) {
        self::checkAction( 'selectReporter' ); 
        
        if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() > 0 ) {
            throw new BgaUserException( self::_("You can only go to press") );
        }

        if ( self::getGameStateValue( 'selected_reporter'.$reporter) == 1 ) {
            throw new BgaUserException( self::_("Already selected") );
        }

        self::setGameStateValue( 'selected_reporter'.$reporter, 1);
        $this->gamestate->nextState( "selectReporter" );
    }

    function selectMoreReporters($reporters) {
        self::checkAction( 'selectMoreReporters' ); 
        
        if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() > 0 ) {
            throw new BgaUserException( self::_("You can only go to press") );
        }

        foreach( $reporters as $reporter) {
            if ( self::getGameStateValue( 'selected_reporter'.$reporter) == 1 ) {
                throw new BgaUserException( self::_("Already selected") );
            }

            self::setGameStateValue( 'selected_reporter'.$reporter, 1);
        }

        $this->gamestate->nextState( "selectMoreReporters" );
    }

    function reassignReporter($story) {
        self::checkAction( 'reassignReporter' ); 

        if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() > 0 ) {
            throw new BgaUserException( self::_("You can only go to press") );
        }

        if ($story[0]==='_' ) {
            throw new BgaUserException( self::_("You cannot select opponents pawn") );
        }

        $player = self::getActivePlayerId();
        $name = explode("_", $story)[0];
        $type = explode("_", $story)[1];
        $stars = explode("_", $story)[2];

        $new_story_name = $name.'_'.$type.'_'.$stars;

        for( $id=0; $id<5; $id++ ) {
            if ( self::getGameStateValue( 'selected_reporter'.$id ) == 1 ) {
                break;
            }
        }

        $type = 'meeple_'.$player;
        $sql = "SELECT tokens_location FROM tokens WHERE tokens_type='$type' AND tokens_type_arg = '$id'  ";
        $old_story_name = self::getUniqueValueFromDB($sql);

        $sql = "UPDATE tokens SET tokens_location = '$new_story_name' WHERE tokens_type = '$type' AND tokens_type_arg = '$id' ";
        self::DbQuery( $sql );

        self::notifyAllPlayers('reassignReporter', clienttranslate('${player_name} reassigns reporter'), array ( 
            'player_name' => self::getActivePlayerName(), 'player_id' => $player, 'pawn_id' => $id, 'new_story' => $new_story_name,
            'old_story' => $old_story_name
        )); 

        if ( self::getGameStateValue( 'final_edition') == 1 ) {
            $sql = "UPDATE player SET player_final_edition_state = 1 WHERE player_id ='$player' ";
            self::DbQuery($sql);
        }

        $this->gamestate->nextState( "reassignReporter" );
    }

    function recallReporters($reporters) {
        self::checkAction( 'recallReporters' ); 

        if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() > 0 ) {
            throw new BgaUserException( self::_("You can only go to press") );
        }

        if ( self::getGameStateValue( 'final_edition' ) == 1 && $this->getActivePlayerFinalEditionPhase() == 0  && count($reporters) > 1) {
            throw new BgaUserException( self::_("You can only recall one reporter during final edition") );
        }

        $player = self::getActivePlayerId();
        $type = 'meeple_'.self::getActivePlayerId();
        foreach( $reporters as $reporter) {
            $sql = "UPDATE tokens SET tokens_location = 'home' WHERE tokens_type = '$type' AND tokens_type_arg = '$reporter' ";
            self::DbQuery( $sql );  
        }

        if ( count($reporters) == 1 ) {
            self::notifyAllPlayers('recallReporters', clienttranslate('${player_name} recalls one reporter'), array ( 
                'player_name' => self::getActivePlayerName(), 'player_id' => $player, 
                'meeples' => $reporters
            )); 
        } else {
            self::notifyAllPlayers('recallReporters', clienttranslate('${player_name} recalls ${x} reporters'), array ( 
                'player_name' => self::getActivePlayerName(), 'player_id' => $player, 'x' => count($reporters), 
                'meeples' => $reporters
            )); 
        }

        if ( self::getGameStateValue( 'final_edition') == 1 ) {
            $sql = "UPDATE player SET player_final_edition_state = 1 WHERE player_id ='$player' ";
            self::DbQuery($sql);
        }

        $this->gamestate->nextState( "recallReporters" );
    }

    function goToPress() {                         
        self::checkAction( 'goToPress' ); 

        $active_player = self::getActivePlayerId();

        // if (! ($this->mayGoToPress($active_player)) ) {
        if ( $this->mayGoToPress($active_player) == 0 ) {    
            throw new BgaUserException( self::_("You need to claim at least one story!!") );
        }

        if ( self::getGameStateValue( 'final_edition') == 1 ) {
            $sql = "UPDATE player SET player_final_edition_state = 2 WHERE player_id ='$active_player' ";
            self::DbQuery($sql);
        }

        if ( self::getGameStateValue( 'final_edition') == 0 ) {
            $this->checkFinaledition($active_player);
        }

        $players = self::getObjectListFromDB( "SELECT player_id id FROM player", true );
        $players = array_diff($players, array($active_player));

        // claim stories
        $type = 'meeple_'.$active_player;
        $sql = "SELECT tokens_type_arg arg, tokens_location loc FROM tokens WHERE tokens_type='$type' AND SUBSTRING(tokens_location, 1, 4) <> 'home' ";
        $active_player_meeples = self::getCollectionFromDB($sql, true);
        $meeple_counts = array_count_values($active_player_meeples);

        $counts_all_players = array( $active_player => $meeple_counts );

        foreach( $meeple_counts as $story => $value) {
            foreach ( $players as $other_player) {
                $type = 'meeple_'.$other_player;
                $sql = "SELECT COUNT(tokens_type_arg) FROM tokens WHERE tokens_type='$type' AND tokens_location = '$story' ";
                $result= self::getUniqueValueFromDB( $sql);

                if ( ! isset($counts_all_players[$other_player]) ) {
                    $counts_all_players[$other_player] =  array();
                }
                $counts_all_players[$other_player] +=  array($story => $result+0);
            }
        }

        foreach( $meeple_counts as $story => $value) {
            $maxs[$story] =  max(array_column($counts_all_players, $story));
        }

        $stories_to_claim = array();
        foreach( $meeple_counts as $story => $value) {
            if ( $counts_all_players[$active_player][$story]  >= $maxs[$story]) {
                $stories_to_claim[] = $story;
            }
        }

        $topBeat_stories = array();
        $other_stories = array();
        foreach ( $stories_to_claim as $story) {
            if ( $this->isStoryFromTopBeat( explode("_", $story)[0] ) ) {
                $topBeat_stories[] = $story;
            } else {
                $other_stories[] = $story;
            }
        }

        $stories_to_claim_ordered = array_merge($topBeat_stories,$other_stories );

        // recall pawns and score scooped
        $pawns_to_return_activePlayer = array();
        $type = 'meeple_'.$active_player;
        foreach ( $stories_to_claim as $story) {
            $sql = "SELECT tokens_type_arg arg FROM tokens WHERE tokens_type='$type' AND tokens_location = '$story' ";
            $pawns_to_return_activePlayer =  array_merge($pawns_to_return_activePlayer, self::getObjectListFromDB( $sql, true ));
        }

        $arrow_values= array();
        foreach ( $stories_to_claim_ordered as $story) {
            $actual_scoop = $this->getArrowPoints($story,'scoop');
            $arrow_values[] = $this->getArrowPoints($story,'main');

            foreach ( $players as $other_player) {
                if ( $counts_all_players[$other_player][$story] > 0 ) {
                    $type = 'meeple_'.$other_player;

                    if ( ! isset($scoop_points[$other_player]) ) {
                        $scoop_points[$other_player] =  0;
                    }
                    $scoop_points[$other_player] += $actual_scoop;

                    $sql = "SELECT tokens_type_arg FROM tokens WHERE tokens_type='$type' AND tokens_location = '$story' ";
                    $pawns_to_return = self::getObjectListFromDB( $sql, true );

                    self::DbQuery( "UPDATE player SET player_score=player_score+'$actual_scoop' WHERE player_id='$other_player'" );
                    self::incStat( $actual_scoop, 'scoop_points', $other_player );

                    if ($actual_scoop == 1) {
                        self::notifyAllPlayers('scoopPlayer', clienttranslate('${player_name} scores 1 point from being scooped out of story'), array ( 
                            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$other_player' ", true ), 
                            'player_id' => $other_player, 'x' => $actual_scoop, 'meeples' => $pawns_to_return
                        )); 
                    } else {
                        self::notifyAllPlayers('scoopPlayer', clienttranslate('${player_name} scores ${x} points from being scooped out of story'), array ( 
                            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$other_player' ", true ), 
                            'player_id' => $other_player, 'x' => $actual_scoop, 'meeples' => $pawns_to_return
                        ));  
                    }
                }
            } 

            $sql = "UPDATE tokens SET tokens_location = 'home' WHERE tokens_location = '$story' ";
            self::DbQuery( $sql ); 

            $name = explode("_", $story)[0];
            $type = explode("_", $story)[1];
            $stars = explode("_", $story)[2];
            $position = array_search($story, $stories_to_claim_ordered);

            $sql = "UPDATE stories SET stories_location_arg = 3, stories_location_backup = '$position' WHERE stories_name='$name' AND stories_type='$type' AND stories_type_arg = '$stars'  ";
            self::DbQuery( $sql ); 
        }

        self::notifyAllPlayers('delay', '', array (  )); 

        if (count($stories_to_claim) ==1 ) {
            self::notifyAllPlayers('claimStories', clienttranslate('${player_name} claims 1 story'), array ( 
                'player_name' => self::getActivePlayerName(), 'player_id' => self::getActivePlayerId(), 'x' => count($stories_to_claim), 'stories' => $stories_to_claim_ordered,
                'meeples' => $pawns_to_return_activePlayer, 'top_beat_stories_nbr' => count($topBeat_stories), 'arrow_values' => $arrow_values
            )); 
        } else {
            self::notifyAllPlayers('claimStories', clienttranslate('${player_name} claims ${x} stories'), array ( 
                'player_name' => self::getActivePlayerName(), 'player_id' => self::getActivePlayerId(), 'x' => count($stories_to_claim), 'stories' => $stories_to_claim_ordered,
                'meeples' => $pawns_to_return_activePlayer, 'top_beat_stories_nbr' => count($topBeat_stories), 'arrow_values' => $arrow_values
            ));             
        }

        self::setGameStateValue( 'number_of_claimed_stories', count($stories_to_claim) );
        self::setGameStateValue( 'number_of_claimed_topbeats', count($topBeat_stories) );

        self::incStat( count($stories_to_claim), 'claimed_number', $active_player );

        $this->calculateFrontPage($topBeat_stories, $active_player );

        $this->gamestate->nextState( "goToPress" );
    }

    function placeStory($story, $x, $y, $state) { 
        self::checkAction( 'placeStory' );

        if ( $this->checkNewPlacement($story, $x, $y, $state )[0] === false ) {
            throw new BgaUserException( self::_("This space is already occupied") );
        }

        $player_id = self::getActivePlayerId();
        $name = explode("_", $story)[0];
        $type = explode("_", $story)[1];
        $stars = explode("_", $story)[2];

        $all_top_edges = self::getGameStateValue( 'number_top_stories_top_edge');
        $all_rest_tops = self::getGameStateValue( 'number_top_stories_rest');
        $all_top_stories = $all_top_edges+$all_rest_tops;

        $act_top_edges = self::getGameStateValue( 'placed_top_stories_top_edge');
        $act_rest_tops = self::getGameStateValue( 'placed_top_stories_rest');
        $act_top_stories = $act_top_edges+$act_rest_tops;

        if ($this->isStoryFromTopBeat($name )) {            
            if ( $this->checkNewPlacement($story, $x, $y, $state )[1] === false) {
                if ($act_top_edges < $all_top_edges ) {
                    throw new BgaUserException( self::_("You must place as many as possible stories from top news beats touching the top edge!!") );
                } else {
                    self::incGameStateValue( 'placed_top_stories_rest', 1);
                }
            } else {
                self::incGameStateValue( 'placed_top_stories_top_edge', 1);
            }

        } else {
            if( $act_top_stories < $all_top_stories ) {
                throw new BgaUserException( self::_("You must place as many as possible stories from top news beats!!") );
            } 
        }

        $position = $x."_".$y."_".$player_id ;

        $sql = "UPDATE stories SET stories_location = '$position', stories_location_arg = '$state' WHERE stories_name='$name' AND stories_type='$type' AND stories_type_arg = '$stars'  ";
        self::DbQuery( $sql ); 
        
        self::notifyAllPlayers('placeStory','', array ( 
            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 
            'story' => $story, 'player_id' => $player_id, 'x' => $x, 'y' => $y, 'state' => $state
        ));  

        
        $this->gamestate->nextState( "placeStory" );
    }

    function confirmFrontPage() {                              
        self::checkAction( 'confirmFrontPage' );

        $all_top_edges = self::getGameStateValue( 'number_top_stories_top_edge');
        $all_rest_tops = self::getGameStateValue( 'number_top_stories_rest');
        $all_top_stories = $all_top_edges+$all_rest_tops;

        $act_top_edges = self::getGameStateValue( 'placed_top_stories_top_edge');
        $act_rest_tops = self::getGameStateValue( 'placed_top_stories_rest');
        $act_top_stories = $act_top_edges+$act_rest_tops;

        if( $act_top_stories < $all_top_stories ) {
            throw new BgaUserException( self::_("You must place as many as possible stories from top news beats!!") );
        } 
        
        self::setGameStateValue( 'number_of_claimed_stories', 0 );
        self::setGameStateValue( 'number_of_claimed_topbeats', 0 );

        self::setGameStateValue( 'number_top_stories_top_edge', 0 );
        self::setGameStateValue( 'number_top_stories_rest', 0 );

        self::setGameStateValue( 'placed_top_stories_top_edge', 0 );
        self::setGameStateValue( 'placed_top_stories_rest', 0 );

        $player_id = self::getActivePlayerId();

        // build front page                                                                                     // substring
        // $sql = "SELECT stories_id id, stories_name n, stories_type t, stories_type_arg s, stories_location loc, stories_location_arg rot FROM stories WHERE (SUBSTRING(stories_location,-7) = '$player_id') ";
        $sql = "SELECT stories_id id, stories_name n, stories_type t, stories_type_arg s, stories_location loc, stories_location_arg rot FROM stories WHERE (SUBSTRING(stories_location,5) = '$player_id') ";
        $stories = self::getCollectionFromDB($sql);

        $front_page = $this->buildFrontPage($player_id, true);

        $sql = "SELECT player_advertisment adv FROM player WHERE player_id ='$player_id' ";
        $advertisment = self::getUniqueValueFromDB($sql); 

        for ($i=0;$i<3;$i++) {
            for ($j=0;$j<4;$j++) {
                if ($front_page[$i][$j] > 0 || $front_page[$i][$j] === 'A') {
                    $front_page[$i][$j] = 0;
                } else {
                    self::incStat( 1, 'empty_spaces', $player_id );
                }
            }
        }

        // calculate points from build stories
        $score_per_story = array();
        $page_sum = 0;

        foreach( $front_page as $row) { 
            $page_sum += array_sum($row);
        }

        foreach( $stories as $story) { 
            //$score_per_story[$story['id']] = array(  'id' => $story['id'], 'name' => $story['n'], 'type' => $story['t'], 'stars' => $story['s'], 'score'=> $this->getArrowPoints($story['n'], 'main') );
            $score_per_story[] = array(  'id' => $story['id'], 'name' => $story['n'], 'type' => $story['t'], 'stars' => $story['s'], 'score'=> $this->getArrowPoints($story['n'], 'main') );       
        }

        if ( $score_per_story) {
            $stories_score = array_sum(array_column($score_per_story,'score'));
        } else {
            $stories_score = 0;
        }

        // exclusive story
        $stories_possible_exclusive = array();
        foreach( $score_per_story as $story) { 
                if ( substr($stories[$story['id']]['loc'], 2, 1) == 0 && !$this->isStoryFromTopBeat($story['name']) ) {
                    //$stories_possible_exclusive['id'] = $story;
                    $stories_possible_exclusive[] = $story;
                } 
        }

        if ( $stories_possible_exclusive) {
            $exclusive_score = max(array_column($stories_possible_exclusive, 'score'));
            $key = array_search($exclusive_score, array_column($stories_possible_exclusive, 'score')  );
            $exclusive_story = $stories_possible_exclusive[$key];
        } else {
            $exclusive_score = 0;
            $exclusive_story = null;
        }

        // left stories
        $sql = "SELECT stories_id id, stories_name n, stories_type t, stories_type_arg s, stories_location loc, stories_location_arg rot FROM stories WHERE stories_location_arg = 3 ";
        $stories_left = self::getCollectionFromDB($sql);

        array_multisort( array_column($stories_left, "s"), SORT_DESC, $stories_left );  // sort by stars

        $score_leftout_story = array();
        foreach( $stories_left as $story) { 
            //$score_leftout_story[$story['id']] = array(  'id' => $story['id'], 'name' => $story['n'], 'type' => $story['t'], 'stars' => $story['s'], 'score'=> -($this->getArrowPoints($story['n'], 'main')) );
            $score_leftout_story[] = array(  'id' => $story['id'], 'name' => $story['n'], 'type' => $story['t'], 'stars' => $story['s'], 'score'=> -($this->getArrowPoints($story['n'], 'main')) );
        }

        if ( $score_leftout_story) {
            $leftout_score = array_sum(array_column($score_leftout_story,'score'));
        } else {
            $leftout_score = 0;
        }

        $overall_score = $page_sum + $stories_score + $exclusive_score+$leftout_score ;

        if ($overall_score > 0) {
            self::incStat($leftout_score+ $page_sum , 'deduct_points', $player_id );
        } else {
            self::incStat($leftout_score+ $page_sum - $overall_score, 'deduct_points', $player_id );
        }

        $overall_score = ($overall_score > 0) ? $overall_score:0;

        self::DbQuery( "UPDATE player SET player_score=player_score+'$overall_score' WHERE player_id='$player_id'" );


        // move published stories
        foreach( $stories as $story) { 
            $loc = $player_id.'_published';
            $id = $story['id'];
            $sql = "UPDATE stories SET stories_location = '$loc', stories_location_arg = 0, stories_location_backup = 0 WHERE stories_id = '$id'  ";
            self::DbQuery( $sql ); 
        }

        // return unpublished stories                  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!! rearange
        foreach( $stories_left as $story) { 
            $name = $story['n'];
            $type = $story['t'];
            $sql = "SELECT COUNT(stories_id) FROM stories WHERE stories_name = '$name' AND stories_type = '$type'  AND stories_location = 'deck' ";
            $numberInDeck = self::getUniqueValueFromDB( $sql);

            for ($i=($numberInDeck-1);$i>-1;$i--) { //rearange in deck
                $sql = "UPDATE stories SET stories_location_arg = stories_location_arg +1 WHERE stories_name = '$name' AND stories_type = '$type' AND stories_location_arg = '$i' ";
                self::DbQuery($sql);
            }

            $id = $story['id'];
            $sql = "UPDATE stories SET stories_location = 'deck', stories_location_arg = 0, stories_location_backup = 0 WHERE stories_id = '$id'  ";
            self::DbQuery( $sql ); 
        }

        //remove advertisment
        $sql = "UPDATE player SET player_advertisment = null WHERE player_id = '$player_id'  ";
        self::DbQuery( $sql ); 

        // give penny
        // if ( self::getGameStateValue('final_edition') == 0) { 
        //     $sql = "UPDATE player SET player_penny = player_penny + 1 WHERE player_id = '$player_id'  ";
        //     self::DbQuery( $sql );
        // } 
        $sql = "UPDATE player SET player_penny = player_penny + 1 WHERE player_id = '$player_id'  ";
        self::DbQuery( $sql );

        $log_name = 'frontpage_'.$player_id.'_:';                    // variable for log front page reconstruction
        foreach( $stories as $story) {
            $log_name = $log_name.'story_'.$story['n'].'_'.$story['t'].'_'.$story['s'].'_'.explode("_", $story['loc'])[0].'_'.explode("_", $story['loc'])[1].'_'.$story['rot'].':';
        }
        $log_name = $log_name.'advert_'.$advertisment;

        // notify all players
        self::notifyAllPlayers('scoreFrontPage',clienttranslate('${player_name} builds front page ${token_name}'), array ( 
            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 'player_id' => $player_id,
            'stories_positive' => $score_per_story, 'stories_negative' => $score_leftout_story, 'exclusive_story' => $exclusive_story, 'page' => $overall_score,
            'token_name' => $log_name,
        ));


        self::notifyAllPlayers('scoreFrontPagePositive',clienttranslate('${player_name} scores ${x} points from placed stories'), array ( 
            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 'player_id' => $player_id,
            'x' => $stories_score, 'final_edition' => self::getGameStateValue('final_edition')
        ));

        if ($leftout_score != 0) {
            self::notifyAllPlayers('scoreFrontPageNegative',clienttranslate('${player_name} loses ${x} points from unplaced stories '), array ( 
                'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 'player_id' => $player_id,
                'x' => $leftout_score
            ));
        }

        if ($exclusive_score != 0) {
            self::notifyAllPlayers('scoreFrontPageExclusive',clienttranslate('${player_name} scores ${x} points from exclusive story '), array ( 
                'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 'player_id' => $player_id,
                'x' => $exclusive_score
            ));
        }

        self::notifyAllPlayers('scoreFrontPageSum',clienttranslate('${player_name} loses ${x} points from empty spaces'), array ( 
            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 'player_id' => $player_id,
            'x' => $page_sum
        ));

        self::notifyAllPlayers('scoreFrontPageOverall',clienttranslate('${player_name} scores overall ${x} points'), array ( 
            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 'player_id' => $player_id,
            'x' => $overall_score
        ));

        self::incStat( count($stories) , 'published_number', $player_id );
        self::incStat( $overall_score, 'publishing_points', $player_id );
        self::incStat( $exclusive_score , 'exclusive_points', $player_id );

        //rearrange stories
        $this->updateStoriesPosition();

        // draw new article
        if ( self::getGameStateValue('final_edition') == 0 ) {
            $this->newArticle($player_id);
        }

        //next player
        $this->gamestate->nextState( "confirmFrontPage" );
    }

    function cancelFrontPage() {
        self::checkAction( 'cancel' );

        self::setGameStateValue( 'placed_top_stories_top_edge', 0 );
        self::setGameStateValue( 'placed_top_stories_rest', 0 );

        $player_id = self::getActivePlayerId();                                    
        // $sql = "SELECT stories_id id, stories_location_backup old, stories_name n, stories_type t, stories_type_arg s FROM stories WHERE SUBSTRING(stories_location,-7) = '$player_id'  ";
        $sql = "SELECT stories_id id, stories_location_backup old, stories_name n, stories_type t, stories_type_arg s FROM stories WHERE SUBSTRING(stories_location,5) = '$player_id'  ";
        $stories_to_return = self::getObjectListFromDB( $sql);

        foreach ( $stories_to_return as $story) {
            $id = $story['id'];
            $new = 'newsbeattile_'.$story['n'].'_'.$story['old'];
            $sql = "UPDATE stories SET stories_location = '$new', stories_location_arg = 3 WHERE stories_id = '$id' ";
            self::DbQuery( $sql );
        }

        self::notifyAllPlayers('startOver','', array ( 
            'player_name' => self::getObjectListFromDB( "SELECT player_name name FROM player WHERE player_id = '$player_id' ", true ), 
            'stories' => $stories_to_return, 'player_id' => $player_id
        ));  

        $this->gamestate->nextState( "cancel" );
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerTurn() { 
        $player_id = self::getActivePlayerId();

        if( $this->getActivePlayerFinalEditionPhase() == 1 && self::getGameStateValue('final_edition') == 1) {
            $text_for_player = clienttranslate('must go to press'); 
            $text_for_others = $text_for_player; 
        } else {
            if( $this->getActivePlayerFinalEditionPhase() == 0 && self::getGameStateValue('final_edition') == 1) {
                $text_for_player = clienttranslate('must send reporter to a story, select reporter or go to press'); 
                $text_for_others = $text_for_player; 
            } else {
                // ( $this->getActivePlayerFinalEditionPhase() == 0) 
                $text_for_player = clienttranslate('must send reporter(s) to a story, select reporter(s) or go to press'); 
                $text_for_others = $text_for_player; 
            }
        }
        
        return array ( 'press' => $this->mayGoToPress($player_id), 'Message_for_player_on_turn'=>$text_for_player, 'Message_for_other_players' => $text_for_others, 'i18n'=>['Message_for_player_on_turn', 'Message_for_other_players']  );
    }
 
    function argPlayerAssignReporters() { 
        $story_id = self::getGameStateValue( 'selected_story');
        $sql = "SELECT stories_name n, stories_type t, stories_type_arg s  FROM stories WHERE stories_id='$story_id' ";
        $story_name = self::getObjectFromDB($sql);
        $story_name = $story_name['n'].'_'.$story_name['t'].'_'.$story_name['s'];

        return array ('story' => $story_name);
    }

    function argPlayerSelectReporters() {  
        $selected_reporters = array();
        for ($i=0;$i<5;$i++) {
            if (self::getGameStateValue( 'selected_reporter'.$i) == 1 ) {
                $selected_reporters[] = $i;
            }
        }

        if ( ( self::getGameStateValue('final_edition') == 0 ) ) {
            $text_for_player = clienttranslate('Reassign reporter, select more reporters or recall selected'); 
            $text_for_others = clienttranslate('must reassign reporter, select more reporters or recall selected'); 
        } else {
            $text_for_player = clienttranslate('Reassign reporter or recall selected'); 
            $text_for_others = clienttranslate('must reassign reporter or recall selected'); 
        }

        //return array ('reporters' => $selected_reporters, 'playerSelectReporters_text'=>clienttranslate('${text}'), 'i18n'=>['text']);
        // 'Message_for_player_on_turn' => array('Message_for_player_on_turn' => clienttranslate('${text_for_player}'), 'args' => ['i18n'=>['text'] ] );

        return array ('reporters' => $selected_reporters, 'Message_for_player_on_turn'=>$text_for_player, 'Message_for_other_players'=>$text_for_others, 'i18n'=>['Message_for_player_on_turn', 'Message_for_other_players'] );
    }

    function argPlayerGoToPress() {
        // claimed stories
        $sql = "SELECT stories_name n, stories_type t, stories_type_arg s  FROM stories WHERE stories_location_arg = '3' ";
        $story_names = self::getObjectListFromDB($sql);

        array_walk($story_names,function(&$value) {return( $value = $value['n'].'_'.$value['t'].'_'.$value['s'] ); }  );

        // top beat phase or other
        if ( self::getGameStateValue( 'placed_top_stories_top_edge' )+self::getGameStateValue( 'placed_top_stories_rest') <
           self::getGameStateValue( 'number_top_stories_top_edge')+self::getGameStateValue( 'number_top_stories_rest' ) ) {
            $phase = 'topBeats';
        } else {
            $phase = 'other';
        }

        // first empty space for story
        $free_positions = $this->calculateFreeSpace($story_names);

        return array ('stories' => $story_names,'phase'=> $phase, 'nbr_top_stories' => self::getGameStateValue( 'number_of_claimed_topbeats'),
         'nbr_all_stories'=>self::getGameStateValue( 'number_of_claimed_stories'), 'stories_first_position'=> $free_positions);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
       

    function stNextPlayer() { 
        $player_id = self::getActivePlayerId();  
        // cleaning
        for( $i=0; $i<5; $i++ ) {
            self::setGameStateValue( 'selected_reporter'.$i, 0 );
        }
        self::setGameStateValue( 'selected_story', 0);

        self::incStat( 1, 'turns_number');
        if ( !self::isCurrentPlayerZombie() ) {
            self::incStat( 1, 'turns_number', $player_id);
        }

        // check game end
        $next_player_id = $this->checkNextPlayer();

        // adjust news beats 
        if ( self::getGameStateValue('final_edition') == 0 ) {
            $this->adjustNewsBeats();
        }

        if ($next_player_id == null) { 
            //game end
            $this->finalScoring();
            $this->gamestate->nextState("endGame");
        } else {
            // next player transition
            self::giveExtraTime( $player_id);
            $this->gamestate->changeActivePlayer( $next_player_id );
            $this->gamestate->nextState("nextPlayer");
        }

    }
    

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
