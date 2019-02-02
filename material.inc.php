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
 * material.inc.php
 *
 * PennyPress game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


$this->arrowmarkers = array(
  1 => array( "name" => "war",
              "x_position" => 45,
              "y_first_position" => 458,
              "y_increment" => 31
          ),
  2 => array( "name" => "crime",
              "x_position" => 100,
              "y_first_position" => 100,
              "y_increment" => 31
            ),
  3 => array( "name" => "politics",
              "x_position" => 100,
              "y_first_position" => 100,
              "y_increment" => 31
            ),
  4 => array( "name" => "city",
              "x_position" => 100,
              "y_first_position" => 100,
              "y_increment" => 31
            ),
  5 => array( "name" => "human",
              "x_position" => 100,
              "y_first_position" => 100,
              "y_increment" => 31
            ),
);

$this->bonustrack_yposition = 48;
$this->bonustrack_xposition = 55;
$this->bonustrack_delta = array(0, 43,29,29,    29,29,30,30,29,    30, 30,30,30,30,   28,30,30,29,29,29);

$this->newsbeat_xposition = array(45,176,307,438,569);
$this->newsbeat_yposition = 458;
$this->newsbeat_delta = 27;

$this->newsbeat_points = array(0,0,1,2,3,3,4,4,5,5,6);
$this->newsbeat_points2 = array(0,0,0,1,1,1,2,2,2,3,3);

$this->stories_xposition = array(41,172,303,434,565);
$this->stories_yposition = 518;
$this->stories_xdelta = 39;
$this->stories_ydelta = 71;

$this->stories_size = array(
  "A" => array( "W" => 1,
                "H" => 2
          ),
  "B" => array( "W" => 1,
                "H" => 3
            ),
  "C" => array( "W" => 2,
                "H" => 2
            ),
  "D" => array( "W" => 2,
                "H" => 3
            )
);

$this->player_mats = array(
  "000000" => 'herald',
  "ffffff" => 'journal',
  "0000ff" => 'sun',
  "ffa500" => 'times',
  "ff0000" => 'world'
);



$this->arrow_marker_values = array(  array(0,0), array(1,0), array(2,1), array(3,1), array(3,1), array(4,2), array(4,2), array(5,2), array(5,3), array(6,3) );

$this->articles = array(
  1 => array( "bonus" => array("war", 3),
    "stories" => array( array("politics", "B"), array("war", "D")),
    "advertisment" => 0,
    "tile" => "WAR",
    "headline" => clienttranslate("Remember the Maine, To HELL with Spain!"),
    "text" => ""
  ),

  2 => array( "bonus" => array("war", 1),
    "stories" => array( array("war", "B"), array("human", "D")),
    "advertisment" => 1,
    "tile" => "WAR",
    "headline" => clienttranslate("Disease Disables Troops"),
    "text" => ""
  ),          

  3 => array( "bonus" => array("war", 2),
    "stories" => array( array("war", "C"), array("city", "D")),
    "advertisment" => 2,
    "tile" => "WAR",
    "headline" => clienttranslate("Navy Whips Spanish Ships"),
    "text" => ""
  ),

  4 => array( "bonus" => array("war", 1),
    "stories" => array( array("war", "A"), array("human", "D"), array("crime", "B")),
    "advertisment" => 3,
    "tile" => "WAR",
    "headline" => clienttranslate( "Red Cross Arrives in Cuba"),
    "text" => ""
  ),

  5 => array( "bonus" => array("war", 2),
    "stories" => array( array("city", "A"), array("crime", "C"), array("war", "B")),
    "advertisment" => 0,
    "tile" => "WAR",
    "headline" => clienttranslate( "Territory Acqusition Gives U.S. Global Reach"),
    "text" => ""
  ),

  6 => array( "bonus" => array("war", 2),
    "stories" => array( array("politics", "A"), array("war", "C"), array("human", "B")),
    "advertisment" => 1,
    "tile" => "WAR",
    "headline" => clienttranslate( "N.Y. Honors Admiral Dewey With Historic 2-Day Parade"),
    "text" => ""
  ),

  7 => array( "bonus" => array("war", 3),
    "stories" => array( array("human", "A"), array("war", "D"), array("city", "B")),
    "advertisment" => 2,
    "tile" => "WAR",
    "headline" => clienttranslate( "Dewey Sinks Spanish Fleet"),
    "text" => ""
  ),

  8 => array( "bonus" => array("war", 3),
    "stories" => array( array("war", "C"), array("crime", "A"), array("politics", "D")),
    "advertisment" => 3,
    "tile" => "WAR",
    "headline" => clienttranslate( "Roosvelt's Rough Riders Charge, Take San Juan Hill"),
    "text" => ""
  ),

  9 => array( "bonus" => array("war", 1),
    "stories" => array( array("city", "C"), array("war", "A"), array("crime", "D")),
    "advertisment" => 0,
    "tile" => "WAR",
    "headline" => clienttranslate( "'Yellow Kid' Newspapers Fan Anti-Spain Flame"),
    "text" => ""
  ),

  10 => array( "bonus" => array("crime", 2),
    "stories" => array( array("war", "B"), array("crime", "D")),
    "advertisment" => 1,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Murder of the Century! Headless Body Found in River"),
    "text" => ""
  ),

  11 => array( "bonus" => array("crime", 1),
    "stories" => array( array("crime", "B"), array("city", "D")),
    "advertisment" => 2,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "N.Y. Stock Exchange Crashes! Thousands of Small Investors Ruined"),
    "text" => ""
  ),

  12 => array( "bonus" => array("crime", 2),
    "stories" => array( array("crime", "C"), array("politics", "D")),
    "advertisment" => 3,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Train-Tunnel Crash Kills 15 Horror Called City's Worst Rail Accident"),
    "text" => ""
  ),

  13 => array( "bonus" => array("crime", 1),
    "stories" => array( array("crime", "A"), array("city", "C"), array("human", "B")),
    "advertisment" => 0,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Poisoner Freed on Appeal Court Limits Evidence from Prior Crimes"),
    "text" => ""
  ),

  14 => array( "bonus" => array("crime", 2),
    "stories" => array( array("politics", "A"), array("human", "C"), array("crime", "B")),
    "advertisment" => 1,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Newsboy Strike Saps Sales"),
    "text" => ""
  ),

  15 => array( "bonus" => array("crime", 2),
    "stories" => array( array("war", "A"), array("crime", "C"), array("city", "B")),
    "advertisment" => 2,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Historic Blizzard Freezes City"),
    "text" => ""
  ),
  
  16 => array( "bonus" => array("crime", 3),
    "stories" => array( array("city", "A"), array("crime", "D"), array("politics", "B")),
    "advertisment" => 3,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Terrible 10-Day Heat Wave Kills 1,500 in New York City"),
    "text" => ""
  ),
  
  17 => array( "bonus" => array("crime", 3),
    "stories" => array( array("crime", "C"), array("human", "A"), array("war", "D")),
    "advertisment" => 0,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Hoboken Docks Fire Traps, Kills Hundreds of Victims"),
    "text" => ""
  ),
  
  18 => array( "bonus" => array("crime", 1),
    "stories" => array( array("politics", "C"), array("crime", "A"), array("human", "D")),
    "advertisment" => 1,
    "tile" => "CRIME & CALAMITY",
    "headline" => clienttranslate( "Police Stabbing by Black Man Leads to Deadly Race Riot"),
    "text" => ""
  ),  

  19 => array( "bonus" => array("politics", 3),
    "stories" => array( array("war", "B"), array("politics", "D") ),
    "advertisment" => 2,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "Speak Softly and Carry a Big Stick"),
    "text" => ""
  ),  

  20 => array( "bonus" => array("politics", 1),
    "stories" => array( array("politics", "B"), array("crime", "D") ),
    "advertisment" => 3,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "Tenement Act Adresses Immigrant Living Conditions"),
    "text" => ""
  ),
  
  21 => array( "bonus" => array("politics", 2),
    "stories" => array( array("politics", "C"), array("human", "D") ),
    "advertisment" => 0,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "'Separate But Equal' Upheld"),
    "text" => ""
  ),
  
  22 => array( "bonus" => array("politics", 1),
    "stories" => array( array("politics", "A"), array("crime", "C"), array("war", "B")  ),
    "advertisment" => 1,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "New York Estabilishes System of Five Boroughs"),
    "text" => ""
  ), 
  
  23 => array( "bonus" => array("politics", 2),
    "stories" => array( array("human", "A"), array("war", "C"), array("politics", "B")  ),
    "advertisment" => 2,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "New York Becomes First State to Require License Plates"),
    "text" => ""
  ), 
  
  24 => array( "bonus" => array("politics", 2),
    "stories" => array( array("city", "A"), array("politics", "C"), array("crime", "B")  ),
    "advertisment" => 3,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "Van Wyck Elected Mayor"),
    "text" => ""
  ), 

  25 => array( "bonus" => array("politics", 3),
    "stories" => array( array("crime", "A"), array("politics", "D"), array("human", "B")  ),
    "advertisment" => 0,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "'Cross of Gold' Speech Electrifies Convention"),
    "text" => ""
  ), 

  26 => array( "bonus" => array("politics", 3),
    "stories" => array( array("politics", "C"), array("war", "A"), array("city", "D")  ),
    "advertisment" => 1,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "McKinley Dies from Gunshot, Roosevelt Becomes President"),
    "text" => ""
  ), 

  27 => array( "bonus" => array("politics", 1),
    "stories" => array( array("human", "C"), array("politics", "A"), array("war", "D")  ),
    "advertisment" => 2,
    "tile" => "POLITICS",
    "headline" => clienttranslate( "Roosevelt Elected Governor"),
    "text" => ""
  ), 

  28 => array( "bonus" => array("city", 3),
    "stories" => array( array("human", "B"), array("city", "D")  ),
    "advertisment" => 3,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "Giant Cables Connect Record-Setting Bridge"),
    "text" => ""
  ), 

  29 => array( "bonus" => array("city", 1),
    "stories" => array( array("city", "B"), array("war", "D")  ),
    "advertisment" => 0,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "New 'Flatiron' Building Towers Over New York"),
    "text" => ""
  ), 

  30 => array( "bonus" => array("city", 2),
    "stories" => array( array("city", "C"), array("crime", "D")  ),
    "advertisment" => 1,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "New York City Now Stands as 2nd Largest in the World -Only London is Bigger-"),
    "text" => ""
  ), 

  31 => array( "bonus" => array("city", 1),
    "stories" => array( array("city", "A"), array("war", "C"), array("politics", "B")  ),
    "advertisment" => 2,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "New 74th Street Power Platn to Supply Electricity For Trains"),
    "text" => ""
  ), 

  32 => array( "bonus" => array("city", 2),
    "stories" => array( array("crime", "A"), array("politics", "C"), array("city", "B")  ),
    "advertisment" => 3,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "Grand Central Terminal to Replace Current Station"),
    "text" => ""
  ), 

  33 => array( "bonus" => array("city", 2),
    "stories" => array( array("human", "A"), array("city", "C"), array("war", "B")  ),
    "advertisment" => 0,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "Bronx Zoo Opens to Public"),
    "text" => ""
  ), 

  34 => array( "bonus" => array("city", 3),
    "stories" => array( array("war", "A"), array("city", "D"), array("crime", "B")  ),
    "advertisment" => 1,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "N.Y. Dedicates Grant's Tomb"),
    "text" => ""
  ), 

  35 => array( "bonus" => array("city", 3),
    "stories" => array( array("city", "C"), array("politics", "A"), array("human", "D")  ),
    "advertisment" => 2,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "New York Subway Opens"),
    "text" => ""
  ), 

  36 => array( "bonus" => array("city", 1),
    "stories" => array( array("crime", "C"), array("city", "A"), array("politics", "D")  ),
    "advertisment" => 3,
    "tile" => "NEW YORK CITY",
    "headline" => clienttranslate( "Lady Liberty Turns Green"),
    "text" => ""
  ),
  
  37 => array( "bonus" => array("human", 3),
    "stories" => array( array("crime", "B"), array("human", "D") ),
    "advertisment" => 0,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "'The Report of My Death Was an Exaggeration'"),
    "text" => ""
  ), 

  38 => array( "bonus" => array("human", 1),
    "stories" => array( array("human", "B"), array("politics", "D") ),
    "advertisment" => 1,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "Horse-Manure Dominates Urban Planning Conference"),
    "text" => ""
  ), 

  39 => array( "bonus" => array("human", 2),
    "stories" => array( array("human", "C"), array("war", "D") ),
    "advertisment" => 2,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "Woman Is First To Survive Niagara Falls Barrel Ride"),
    "text" => ""
  ), 

  40 => array( "bonus" => array("human", 1),
    "stories" => array( array("human", "A"), array("politics", "C"), array("city", "B") ),
    "advertisment" => 3,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "Joplin's 'Maple Leaf Rag' Popularizes Ragtime Music"),
    "text" => ""
  ),

  41 => array( "bonus" => array("human", 2),
    "stories" => array( array("war", "A"), array("city", "C"), array("human", "B") ),
    "advertisment" => 0,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "Bicycle Craze Sweeps City"),
    "text" => ""
  ),

  42 => array( "bonus" => array("human", 2),
    "stories" => array( array("crime", "A"), array("human", "C"), array("politics", "B") ),
    "advertisment" => 1,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "'X-Ray' Device Peers Inside Human Body"),
    "text" => ""
  ),

  43 => array( "bonus" => array("human", 3),
    "stories" => array( array("politics", "A"), array("human", "D"), array("war", "B") ),
    "advertisment" => 2,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "Temperance Terror Visits N.Y."),
    "text" => ""
  ),

  44 => array( "bonus" => array("human", 3),
    "stories" => array( array("human", "C"), array("city", "A"), array("crime", "D") ),
    "advertisment" => 3,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "Brooklyn 'Superbas' Win National League Pennant"),
    "text" => ""
  ),

  45 => array( "bonus" => array("human", 1),
    "stories" => array( array("war", "C"), array("human", "A"), array("city", "D") ),
    "advertisment" => 0,
    "tile" => "HUMAN CONDITION",
    "headline" => clienttranslate( "Edison's 'Motion Picture's' StunNew York Audiences"),
    "text" => ""
  ),
);

