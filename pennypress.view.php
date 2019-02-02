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
 * pennypress.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in pennypress_pennypress.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_pennypress_pennypress extends game_view
  {
    function getGameName() {
        return "pennypress";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $this->page->begin_block( "pennypress_pennypress", "playerNamesRow" );
        $this->page->begin_block( "pennypress_pennypress", "playerWarRow" );
        $this->page->begin_block( "pennypress_pennypress", "playerCrimeRow" );
        $this->page->begin_block( "pennypress_pennypress", "playerPoliticsRow" );
        $this->page->begin_block( "pennypress_pennypress", "playerCityRow" );
        $this->page->begin_block( "pennypress_pennypress", "playerHumanRow" );
        $this->page->begin_block( "pennypress_pennypress", "playerSumRow" );
        $this->page->begin_block( "pennypress_pennypress", "playerTotalRow" );
        $this->page->begin_block( "pennypress_pennypress", "finalTable" );

        foreach( $players as $player )  {
            // $this->page->reset_subblocks( 'playerNamesRow' ); 

            $this->page->insert_block( "playerNamesRow", array( 
                    'COLOR' => $player['player_color'], $player['player_name'],
                    'player_name' => $player['player_name'],
                    'stars_count' => self::_('Count of stars'),
            ) );
            

            // $this->page->reset_subblocks( 'playerWarRow' ); 
            $this->page->insert_block( "playerWarRow", array( 
            ) );

            // $this->page->reset_subblocks( 'playerCrimeRow' ); 
            $this->page->insert_block( "playerCrimeRow", array( 
            ) );

            // $this->page->reset_subblocks( 'playerPoliticsRow' ); 
            $this->page->insert_block( "playerPoliticsRow", array( 
            ) );

            // $this->page->reset_subblocks( 'playerCityRow' ); 
            $this->page->insert_block( "playerCityRow", array( 
            ) );

            // $this->page->reset_subblocks( 'playerHumanRow' ); 
            $this->page->insert_block( "playerHumanRow", array( 
            ) );
            
            // $this->page->reset_subblocks( 'playerSumRow' ); 
            $this->page->insert_block( "playerSumRow", array( 
            ) );

            // $this->page->reset_subblocks( 'playerTotalRow' ); 
            $this->page->insert_block( "playerTotalRow", array( 
            ) );

        }

        $this->page->insert_block( "finalTable", array( 
                                        "FinalScoring" => self::_('Final scoring'),
                                        "SumOfPoints"=> self::_('Press & scoop points'),
                                        "TotalPoints" => self::_('Total points'),
                                        "Most" => self::_('Most'),
                                        "starstranslate" => self::_('stars'),

        ) );
        


        $this->page->begin_block( "pennypress_pennypress", "newspaperTiles" );
        $this->page->begin_block( "pennypress_pennypress", "pennyTiles" );
        $this->page->begin_block( "pennypress_pennypress", "playerBox" );

        foreach( $players as $player )  {
            $this->page->reset_subblocks( 'newspaperTiles' ); 

            for( $i=0; $i<3; $i++ ) {
                for ($j=0; $j<4; $j++) {
                    $this->page->insert_block( "newspaperTiles", array( 
                            'X' => $j,
                            'Y' => $i,
                            "PLAYER" => $player['player_id'],
                    ) );
                }
            }

            $this->page->reset_subblocks( 'pennyTiles' ); 
            for ($i=2; $i>=0; $i--) {
                $this->page->insert_block( "pennyTiles", array( 
                        'X' => $i,
                        "PLAYER" => $player['player_id'],
                ) );
            }

            $this->page->insert_block( "playerBox", array( 
                                            "PLAYER_NAME" => $player['player_name'],
                                            "COLOR"=> $player['player_color'],
                                            "ID" => $player['player_id'],
                                            "player_color" => $this->game->player_mats[$player['player_color']],
                                    ) );
        }

        $this->page->begin_block( "pennypress_pennypress", "bonusTrackTiles" );
        $delta = $this->game->bonustrack_xposition;
        for ( $i=2;$i<21;$i++)  {
            $delta += $this->game->bonustrack_delta[$i-2];

            if ($i==2 || $i==20) {
                $size = "large";
            } else {
                $size = "";
            }
            
            $this->page->insert_block( "bonusTrackTiles", array( 
                                            "X" => $i,
                                            "LEFT" => $delta,
                                            "TOP" => $this->game->bonustrack_yposition,
                                            "SIZE" => $size
                                    ) );
        }

        $this->page->begin_block( "pennypress_pennypress", "newsBeatTiles" );
        for ( $i=1;$i<6;$i++) {
            for ( $j=0;$j<11;$j++)  {
                $this->page->insert_block( "newsBeatTiles", array( 
                                                "TYPE" => $this->game->arrowmarkers[$i]['name'],
                                                "X" => $j,
                                                "LEFT" => $this->game->newsbeat_xposition[$i-1],
                                                "TOP" => $this->game->newsbeat_yposition-$j*$this->game->newsbeat_delta
                                        ) );
            }
        }

        $this->page->begin_block( "pennypress_pennypress", "storiesTiles" );
        $alphabet =   array('A','B','C','D');
        for ( $i=1;$i<6;$i++) {
            for ( $j=0;$j<4;$j++)  {
                if ($j == 1 || $j == 3) {
                    $ydelta = $this->game->stories_ydelta;
                } else {
                    $ydelta = 0;
                }
                $this->page->insert_block( "storiesTiles", array( 
                                                "TYPE" => $this->game->arrowmarkers[$i]['name'],
                                                "X" => $alphabet[$j],
                                                "LEFT" => $this->game->stories_xposition[$i-1]+ $this->game->stories_xdelta*( min(1, max(0, $j-1) ) ),
                                                "TOP" => $this->game->stories_yposition+ $ydelta
                                        ) );
            }
        }



        /*********** Do not change anything below this line  ************/
  	}
  }
  

