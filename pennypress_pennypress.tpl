{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- PennyPress implementation : © Adam Novotny <Adam.Novotny.ck@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->
<!-- 
--This work is for my recently born daughter, Marie. I wish you happy and healty life full of love! Your dad, Adam
-->
<div id="disablingDiv" style="position: absolute; display: none; width:100%; z-index:10; height:100%; background-color: grey; opacity:.4; filter: alpha(opacity=00)" >
</div>

<div id="claimedPanel" class="claimedPanel">
    <div id="outerClaimedHolder" class="outerClaimedHolder" ></div>
    <div  class="claimedslider" ></div>
</div>

<div style="margin: 0 auto; display: table" id="outerframe">
    <!-- BEGIN finalTable -->
    <div class= "finaltable">
        <table>
        <caption>{FinalScoring}</caption>
        <col class="firstcolumn"/>
            <tr class="playersrow">
                <th style="border-left: hidden; border-top: hidden;"></th>
                <!-- BEGIN playerNamesRow -->
                <th style="color: #{COLOR}">{player_name}</th>
                <!-- END playerNamesRow -->
            </tr>
            <tr>
                <th>{SumOfPoints}</th>
                <!-- BEGIN playerSumRow -->
                <td></td>
                <!-- END playerSumRow -->
            </tr>
            <tr>
                <td><span>{Most}<div class="bonustrack war tableicon"></div>&nbsp;&nbsp;{starstranslate}</span></td>
                <!-- BEGIN playerWarRow -->
                <td></td>
                <!-- END playerWarRow -->
            </tr>
            <tr>
                <td><span>{Most}<div class="bonustrack crime tableicon"></div>&nbsp;&nbsp;{starstranslate}</span></td>
                <!-- BEGIN playerCrimeRow -->
                <td></td>
                <!-- END playerCrimeRow -->
            </tr>
             <tr>
                <td><span>{Most}<div class="bonustrack politics tableicon"></div>&nbsp;&nbsp;{starstranslate}</span></td>
                <!-- BEGIN playerPoliticsRow -->
                <td></td>
                <!-- END playerPoliticsRow -->
            </tr>
            <tr>
                <td><span>{Most}<div class="bonustrack city  tableicon"></div>&nbsp;&nbsp;{starstranslate}</span></td>
                <!-- BEGIN playerCityRow -->
                <td></td>
                <!-- END playerCityRow -->
            </tr>
            <tr>
                <td><span>{Most}<div class="bonustrack human tableicon"></div>&nbsp;&nbsp;{starstranslate}</span></td>
                <!-- BEGIN playerHumanRow -->
                <td></td>
                <!-- END playerHumanRow -->
            </tr>
            <tr>
                <th class="colored">{TotalPoints}</th>
                <!-- BEGIN playerTotalRow -->
                <td class="colored"></td>
                <!-- END playerTotalRow -->
            </tr>
        </table>
    </div>
    <!-- END finalTable -->

    <div id="mainframe">
        <div id="mainboard">
            <!-- BEGIN bonusTrackTiles -->
                <div  id="bonustracktile_{X}" class="bonustracktile {SIZE}" style="left: {LEFT}px; top: {TOP}px;"></div>
            <!-- END bonusTrackTiles -->

            <!-- BEGIN newsBeatTiles -->
                <div  id="newsbeattile_{TYPE}_{X}" class="newsbeattile" style="left: {LEFT}px; top: {TOP}px;"></div>
            <!-- END newsBeatTiles --> 

            <!-- BEGIN storiesTiles -->
                <div  id="storiestile_{TYPE}_{X}" class="storiestile {X}" style="left: {LEFT}px; top: {TOP}px;"></div>
            <!-- END storiesTiles -->               

        </div>
        <div id="outerheadlineframe">
            <div id="headlinesframe">
            </div>
        </div>
    </div>

    <div id="playersframe">
            <!-- BEGIN playerBox -->
                <div id="playerbox_{ID}" class="whiteblock playerbox">
                    <h3 style="color: #{COLOR}; text-align: center; margin-top: 0px; ">{PLAYER_NAME}</h3>
                    <div  id="playerboard_{ID}" class="playerboard {player_color}">
                        <div id="tetris_{ID}" class = "tetris">
                            <!-- BEGIN newspaperTiles -->
                                    <div  id="newspapertile_{X}_{Y}_{PLAYER}" class="newspapertile" "></div>
                            <!-- END newspaperTiles --> 
                        </div> 
                        <div id="pennys_{ID}" class = "pennyBox">
                            <!-- BEGIN pennyTiles -->
                                <div  id="pennytile_{X}_{PLAYER}" class="pennytile" "></div>
                            <!-- END pennyTiles -->  
                        </div> 
                    </div>
                </div>
            <!-- END playerBox -->
    </div>

    <div class="circle" id="circle">
        <div id="button_left" class="my_button arrowbutton button_left"></div>
        <div id="button_right" class="my_button arrowbutton button_right"></div> 
        <div id="button_up" class="my_button arrowbutton button_up" ></div>
        <div id="button_down" class="my_button arrowbutton button_down" ></div> 
        <div id="button_rotate" class="my_button rotatebutton"></div>  
        <div id="button_confirm" class="my_button confirmbutton"></div> 
        <div id="button_cancel" class="my_button cancelbutton"></div>    
    </div>

</div>

<script type="text/javascript">

// Javascript HTML templates

var jstpl_arrowmarker ='<div class="markercontainer" id="${id}_marker"><div class="arrowmarker ${id} arrowmarkerback"></div><div class="arrowmarker ${id}"></div></div>';
var jstpl_bonustrack ='<div class="bonustrack ${id}" id="${id}_bonustrack"></div>';
var jstpl_bonustrack_log ='<div class="bonustrack ${type} logitem"></div>';
var jstpl_story ='<div class="story story${type} ${beat}  star${stars}" id="story_${beat}_${type}_${stars}"> </div>';
var jstpl_story_newlog ='<div class="story story${type} ${beat}  star${stars} logitem"> </div>';
var jstpl_story_log ='<div class="story story${type} ${beat}  star${stars}" id="story_${beat}_${type}_${stars}_log"> </div>';
var jstpl_pawn ='<div class="pawn pawn_${color}" id="pawn_${i}_${id}"></div>';
var jstpl_storyzone = '<div id="${id}" class="storyZone" style="width: ${w}px; height: ${h}px;"></div>';
var jstpl_storyzoneselect = '<div id="${id}" class="storySelect" style="width: ${w}px; height: ${h}px;"></div>';
//var jstpl_article = '<div id="article${x}" class="article article${x}"><div id="article${x}_text" class="articletext"><div>${text}</div></div></div>';
var jstpl_article = '<div id="article${x}" class="article article${x}"></div>';
var jstpl_advertisment = '<div id="advertisment_${player}" class="pennyadvertisment"></div>';
var jstpl_claimed_holder = '<div class="claimedHolder" id="claimedStory_${x}"><div class="claimedStoryText">${text}</div><div id="claimedStory_${x}_inner" style="position: absolute;top: 0;width: 100%;height: 100%;"></div></div>';

var jstpl_curtain = '<div class="curtain" id="curtain" style="width: ${width}px"></div>';
var jstpl_menubutton = '<div class="menubutton" id="menubutton_${x}">${i}</div>';
var jstpl_strike = '<div id="strike" class="strikediv">${text}</div>';

var jstpl_arrowmarkerindicator = '<div class="arrowmarkerindicator" id="arrowindicator${x}">${value}</div>';

var jstpl_player_board = '\<div class="cp_board">\
    <div class="row1">\
        <div id="${player}_meeplePanel" class="meeplepanel"></div>\
        <div class="penny pennypanel"></div>\
        <div id="${player}_pennyCount" class="pennycount">0</div>\
    </div>\
    <div class="row3" id="${player}_storiesCount" >\
        <div class="cellStories war"></div>\
        <div class="cell" id="${player}_publishedCounter_war">0</div>\
        <div class="cellStories crime"></div>\
        <div class="cell" id="${player}_publishedCounter_crime">0</div>\
        <div class="cellStories politics"></div>\
        <div class="cell" id="${player}_publishedCounter_politics">0</div>\
        <div class="cellStories city"></div>\
        <div class="cell" id="${player}_publishedCounter_city">0</div>\
        <div class="cellStories human"></div>\
        <div class="cell" id="${player}_publishedCounter_human">0</div>\
    </div>\
    </div>';    

var jstpl_storymenu = '\<div class="storymenuContainer" id="story_${beat}_${type}_${stars}_menu">\
        <div style="width:18px; height:50px;"><div class="arrowplace" id="story_${beat}_${type}_${stars}_click"></div></div>\
        <div class="menuplace" id="story_${beat}_${type}_${stars}_place">\
            <div class="meepleplace" id="story_${beat}_${type}_${stars}_mp">\
                <div class="pawnNoID pawnNoID_${color}"></div>\
                <div class ="curvedrarrow rotation"></div>\
                <div class="separator">:</div>\
            </div>\
            <div class="storyselectplace" id="story_${beat}_${type}_${stars}_sp">\
                <div class="pawnNoID pawnNoID_${color}"></div>\
                <div class ="curvedrarrow "></div>\
                <div class="separator">:</div>\
            </div>\
        </div>\
    </div>';    

</script>  

{OVERALL_GAME_FOOTER}
