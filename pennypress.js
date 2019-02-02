/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * PennyPress implementation : © Adam Novotny <Adam.Novotny.ck@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * pennypress.js
 *
 * PennyPress user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare","dojo/fx",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.pennypress", ebg.core.gamegui, {
        constructor: function(){
            console.log('pennypress constructor');
              
            this.OpenedMenu = 0;

             // Zone control
             this.bonustrackZones = [];
             for (var i=0;i<19;i++) {
                this.bonustrackZones.push(new ebg.zone());  
             }   
             
             this.meepleZones = {}; 
             this.storyZones = {}; 

             this.handlers = [];
             this.panelhanler = null;

             this.actualClaimedStoryPostion = 0;
             this.panelPosition = 0;

             this.globalid = 0;
             this.firstpositions = null;
             this.screenWidth = screen.width;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {            
                var player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_player_board', {player: player_id } ), player_board_div );
                this.meepleZones[player_id+'_panel'] = new ebg.zone();
                this.meepleZones[player_id+'_panel'].create(this,player_id+'_meeplePanel',30,30); 
                this.meepleZones[player_id+'_panel'].item_margin = 0;
                dojo.removeAttr(player_id+'_meeplePanel', 'style');

                var text = _('Free meeples');
                var div = "<div>"+text+"</div>";
                this.addTooltipHtml( player_id+'_meeplePanel', div );

                if ( (Object.keys(gamedatas.players).length) >3 ){
                    var pennies_needed = 3;
                } else {
                    var pennies_needed = 4;
                }
                var text = dojo.string.substitute( _("Number of pennies, ${x} needed to trigger final edition"), {
                    x: pennies_needed
                } );
                var div = "<div>"+text+"</div>";
                this.addTooltipHtml( player_id+'_pennyCount', div );

                var text = _('Number of published stories (sum of stories stars)');
                var div = "<div>"+text+"</div>";
                this.addTooltipHtml( player_id+'_storiesCount', div );
            }
            
            // TODO: Set up your game interface here, according to "gamedatas"
            if (!this.isSpectator) {
                dojo.place( "playerbox_"+this.player_id, "playersframe", "first");
            }

            for (var i=0;i<19;i++) {
                this.bonustrackZones[i].create(this,'bonustracktile_'+(i+2),13,13);  
            }

            for( var player_id in gamedatas.players ) {
                var player = gamedatas.players[player_id];

                if ( player.advertisment !== null) {
                    this.addAdvertismentOnBoard(player.id, player.advertisment.slice(0,1) , player.advertisment.slice(-1) );
                }

                for (var i=0; i<player.penny;i++) {
                    if (i == 3) {
                        $(player.id+'_pennyCount').innerText = parseInt(4);
                    } else {
                        this.addPennyOnBoard(player.id);
                    }
                }
            }

            // Add bonusmarkers
            for( var id in gamedatas.bonusmarkers ) {
                var token = gamedatas.bonusmarkers[id];
                this.addBonusMarkerOnBoard(token.location,token.arg);
            }   
            
            // Add arrowmarkers
            for( var id in gamedatas.arrowmarkers ) {
                var token = gamedatas.arrowmarkers[id];
                this.addArrowMarkerOnBoard(token.arg,token.location);
            }
            
            // Add stories
            var claimedStories = new Array(parseInt(gamedatas.claimedStoriesNumber));
            var claimedArrowValues = new Array(parseInt(gamedatas.claimedStoriesNumber));

            for( var id in gamedatas.stories ) {
                var token = gamedatas.stories[id];
                this.addStoryOnBoard( token.name,token.type,token.stars, token.location, token.arg ); 

                if ( (token.location.split("_")[2] == this.getActivePlayerId() || token.arg == 3 ) && ( this.getActivePlayerId() !== null && this.getActivePlayerId() !== undefined ) ) {
                    if (token.arg == 3) {
                        claimedStories[token.claimed_location] = token.name+'_'+token.type+'_'+token.stars;
                        var key = this.getKeyByValue(gamedatas.claimedArrowValues['position'], token.claimed_location);
                        claimedArrowValues[token.claimed_location] = gamedatas.claimedArrowValues['value'][key];
                    } else {
                        var key = this.getKeyByValue(gamedatas.claimedArrowValues['position'], token.claimed_location);
                        claimedArrowValues[token.claimed_location] = gamedatas.claimedArrowValues['value'][key];
                    }
                }
            }

            if ( gamedatas.claimedStoriesNumber > 0 ) {
                // this.slidePanels(claimedStories.length, claimedStories, gamedatas.claimedTopBeatsNumber );
                dojo.connect(window,'onload',  dojo.hitch(this,'slidePanels', claimedStories.length, claimedStories, gamedatas.claimedTopBeatsNumber, claimedArrowValues) );
            } 
            
            // Add articles
            for( var id in gamedatas.articles ) {
                this.addArticleOnBoard( gamedatas.articles[id], gamedatas.headlines[id] );
            }

            // Add player meeples
            for( var id in gamedatas.meeples ) {
                var token = gamedatas.meeples[id];
                // var player_id = token.type.substr(-7);
                var player_id = token.type.split("_")[1];
                this.addPawnOnBoard(player_id,gamedatas.players[player_id].color,token.location,token.arg); 
            }

            if (gamedatas.final_edition == 1) {
                this.flipArrowmarkers();
            } else {
                if ( gamedatas.strike ) {
                    this.addStrike(gamedatas.strike);
                }
            }

            //font size recalculation
            // dojo.connect(window,'onload', this ,'recalculateArticlesFonts');
 
            // after finish show table
            // if ($('generalactions')) {
            //     dojo.query('.finaltable').addClass('open');
            // }
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );

            switch( stateName )
            {
                case 'playerTurn':
                //console.log(g_replayFrom )
                    if( this.isCurrentPlayerActive() ) {
                        // var stories = dojo.query('.storySelect').addClass('visible');
                        dojo.query('.storymenuContainer').addClass('visible');
                        dojo.query('.arrowplace').addClass('highlight');

                        var color = this.gamedatas.players[ this.player_id ].color;
                        var pawns = dojo.query('.pawn_'+color);
                        for(i=0;i<pawns.length;i++) {
                            if (dojo.hasClass(pawns[i],'small')) {
                                dojo.addClass(pawns[i],'hoverable');
                                this.handlers.push( dojo.connect(pawns[i],'click', this ,'selectReporter') );
                            }
                        }

                        var menus = dojo.query('.arrowplace');
                        for(i=0;i<menus.length;i++) {
                            this.handlers.push( dojo.connect(menus[i],'click', this ,'slideStoryMenu') );
                        }
                    }

                break;

                case 'playerSelectReporters':
                    if( this.isCurrentPlayerActive() ) {
                        var x = dojo.query($('pawn_'+args.args.reporters[0]+'_'+this.player_id)).addClass('selected');
                        var story =  dojo.query( $(x[0].parentNode))[0].parentNode;

                        var stories = dojo.query('.storySelect');
                        var index = dojo.indexOf(stories, story);
                        stories.splice(index, 1);
                        stories.addClass('visible');

                        for(i=0;i<stories.length;i++) {
                            this.handlers.push( dojo.connect(stories[i],'click', this ,'reassignReporter') );
                        }

                        var pawns = this.subselectPawns(args.args.reporters).addClass('hoverable');

                        for(i=0;i<pawns.length;i++) {
                            this.handlers.push( dojo.connect(pawns[i],'click', this ,'selectReporter') );
                        }

                        var stories = this.subselectStoriesForMenu(dojo.query('.storymenuContainer'), args.args.reporters);
                        // dojo.query('.storymenuContainer').addClass('visible');
                        // console.log(stories);
                        // var menus = dojo.query('.arrowplace');
                        // menus.forEach(element => {
                        //     this.handlers.push( dojo.connect(element,'click', this ,'slideStoryMenu') );
                        // });
                    }
                break;

                case 'playerRecallReporters':
                    if( this.isCurrentPlayerActive() ) {
                        for(var i=0;i<args.args.reporters.length;i++) {
                            dojo.query($('pawn_'+args.args.reporters[i]+'_'+this.player_id)).addClass('selected');
                        }

                        var pawns = this.subselectPawns(args.args.reporters).addClass('hoverable');
                        // pawns.forEach(element => {
                        //     this.handlers.push( dojo.connect(element,'click', this ,'selectReporter') );
                        // });

                        for(i=0;i<pawns.length;i++) {
                            this.handlers.push( dojo.connect(pawns[i],'click', this ,'selectReporter') );
                        }

                        this.subselectStoriesForMenu(dojo.query('.storymenuContainer'), args.args.reporters);
                    }
                break;

                case 'playerGoToPress':

                this.firstpositions = args.args.stories_first_position
                    for(var i=0;i<args.args.stories.length;i++) {
                        var element = dojo.query($('story_'+args.args.stories[i]+'_zoneSelect')).addClass('selected visible');

                        if( this.isCurrentPlayerActive() ) {
                            this.handlers.push( dojo.connect(element[0],'click', this ,'placeStoryToTile') );
                           // this.handlers.push( dojo.connect(element[0],'click', this ,'placeStoryToTile',args.args.stories_first_position[i].x,args.args.stories_first_position[i].y,args.args.stories_first_position[i].rotation) );
                        }
                    }
                    
                    if ($('outerClaimedHolder').getBoundingClientRect().width != 0) {
                        this.addCurtain(args.args.phase,args.args.nbr_top_stories,args.args.nbr_all_stories);
                    }
                    else {
                        dojo.connect(window,'onload',  dojo.hitch(this,'addCurtain', args.args.phase,args.args.nbr_top_stories,args.args.nbr_all_stories) );
                        // dojo.connect(window,'onresize',  dojo.hitch(this,'recalculateCurtain', args.args.phase,args.args.nbr_top_stories,args.args.nbr_all_stories) );
                    }

                    this.handlers.push( dojo.connect(dojo.query('.claimedslider')[0],'click', this ,'panelslider') );
                break;  
                
                case 'client_placeStory':
                    this.addHandlersToMenu(args.args.story);
                break;
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
                case 'playerTurn':                                                                 
                    dojo.query('.storySelect').removeClass('visible');
                    dojo.query('.storymenuContainer').removeClass('visible');
                    dojo.query('.arrowplace').removeClass('highlight');
                    dojo.query('.hoverable').removeClass('hoverable');

                    if ( $("story_"+this.OpenedMenu+"click")) {
                        dojo.removeClass("story_"+this.OpenedMenu+"click", 'click_back');
                        dojo.removeClass("story_"+this.OpenedMenu+"place", 'open');
                    }
                    this.OpenedMenu = 0;
                    dojo.forEach(this.handlers,dojo.disconnect);
                    this.handlers = [];
                    dojo.query('.menubutton').forEach(dojo.destroy);
                break;

                case 'playerAssignReporters':
                    dojo.query('.selected').removeClass('selected');
                break;

                case 'playerSelectReporters':
                    dojo.query('.selected').removeClass('selected');
                    dojo.query('.hoverable').removeClass('hoverable');
                    dojo.query('.storySelect').removeClass('visible');
                    dojo.query('.arrowplace').removeClass('highlight');
                    dojo.query('.storymenuContainer').removeClass('visible');
                    if ( $("story_"+this.OpenedMenu+"click")) {
                        dojo.removeClass("story_"+this.OpenedMenu+"click", 'click_back');
                        dojo.removeClass("story_"+this.OpenedMenu+"place", 'open');
                    }
                    this.OpenedMenu = 0;

                    dojo.forEach(this.handlers,dojo.disconnect);
                    this.handlers = [];

                    dojo.query('.menubutton').forEach(dojo.destroy);
                break;

                case 'playerRecallReporters':
                    dojo.query('.selected').removeClass('selected');
                    dojo.query('.hoverable').removeClass('hoverable');
                    dojo.query('.arrowplace').removeClass('highlight');
                    dojo.query('.storymenuContainer').removeClass('visible');
                    if ( $("story_"+this.OpenedMenu+"click")) {
                        dojo.removeClass("story_"+this.OpenedMenu+"click", 'click_back');
                        dojo.removeClass("story_"+this.OpenedMenu+"place", 'open');
                    }
                    this.OpenedMenu = 0;

                    dojo.forEach(this.handlers,dojo.disconnect);
                    this.handlers = [];
                break;

                case 'playerGoToPress':
                    dojo.query('.selected').removeClass('selected visible');
                    dojo.forEach(this.handlers,dojo.disconnect);
                    this.handlers = [];
                    dojo.destroy("curtain");
                    dojo.disconnect(this.panelhanler);

                    dojo.query('.claimedslider').removeClass('back');
                break;  

                case 'client_placeStory':
                    dojo.forEach(this.handlers,dojo.disconnect);
                    this.handlers = [];
                break;

            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'playerTurn':
                        if( this.isCurrentPlayerActive() ) {
                            if (args.press) {
                                var numberOfstories = args.press;
                                if (numberOfstories == 1) {
                                    this.addActionButton( 'press_button', _('go to press: (1 story will be claimed)'), 'goToPress' );
                                } else {
                                    var translated = dojo.string.substitute(_('go to press: (${numberOfstories} stories will be claimed)'), {
                                        numberOfstories: numberOfstories
                                    } );
                                    this.addActionButton( 'press_button', translated, 'goToPress' ); 
                                }
                            }
                        }
                    break; 

                    case 'playerAssignReporters':
                        if( this.isCurrentPlayerActive() ) {
                            //var free_meeples = this.meepleZones[this.player_id].getItemNumber();
                            var free_meeples = this.meepleZones[this.player_id+'_panel'].getItemNumber();

                            for (var i=0;i<free_meeples;i++) {
                                this.addActionButton( 'meeple'+(i+1), i+1, 'sendReporters' ); 
                            }

                            this.addActionButton( 'cancel_button', _('cancel selection'), 'cancelSelection' ); 
                        }
                    break;

                    case 'playerSelectReporters':
                        if( this.isCurrentPlayerActive() ) {
                            this.addActionButton( 'recall_button', _('recall reporter'), 'recallReporters' );
                            this.addActionButton( 'cancel_button', _('cancel selection'), 'cancelSelection' );  
                        }
                    break;                    
                    
                    case 'playerRecallReporters':
                        if( this.isCurrentPlayerActive() ) {
                            this.addActionButton( 'recall_button', _('recall reporters'), 'recallReporters' ); 
                            this.addActionButton( 'cancel_button', _('cancel selection'), 'cancelSelection' ); 
                        }
                    break;

                    case 'playerGoToPress':
                        if( this.isCurrentPlayerActive() ) {
                            this.addActionButton( 'confirm_button', _('Confirm your front page'), 'confirmFrontPage' );
                            this.addActionButton( 'cancel_button', _('Start over'), 'cancelFrontPage' );
                        }
                    break;                    

                    case 'client_placeStory':
                        if( this.isCurrentPlayerActive() ) {
                            this.addActionButton( 'cancel_button', _('cancel selection'), dojo.partial(this.cancelPlacement, args.story) ); 
                        }
                    break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        getKeyByValue: function(array, value) {
            var result = null;

            if (array !== undefined) {
                for (var i=0;i<array.length;i++) {
                    if (array[i] == value) {
                        result = i;
                    }
                }
            }

            return result;
        },

        getSizeFromStory: function(story) {
            var letter = story.slice(-3,-2);
            switch (letter) {
                case "A":
                    w = 1;
                    h = 2;
                break;
                case "B":
                    w = 1;
                    h = 3;
                break;
                case "C":
                    w = 2;
                    h = 2;
                break;
                case "D":
                    w = 2;
                    h = 3;
                break;
            }
            if (dojo.hasClass(story, 'enlargedrotated')) {
                return [h,w];
            } else {
                return [w,h];
            }
        },

        recalculateZone: function(name) {         // recalculate size of the whole zone of meeples to fit the story
            dojo.style(name+'_zone','transform','scale(1)');
            this.storyZones[name].create(this,name+'_zone',20,20);
            this.storyZones[name].updateDisplay();
            var currentItemH = this.storyZones[name].item_height;
            var currentItemw = this.storyZones[name].item_width;

            var targetH = $(name).getBoundingClientRect().height;
            var targetW = $(name).getBoundingClientRect().width;
            var items = this.storyZones[name].getAllItems();

            var actH =  $(name+'_zone').getBoundingClientRect().height;
            while (actH > targetH-5) {
                this.storyZones[name].create(this,name+'_zone',currentItemH-1,currentItemw-1);
                this.storyZones[name].updateDisplay();

                currentItemH = this.storyZones[name].item_height;
                currentItemw = this.storyZones[name].item_width;
                actH =  $(name+'_zone').getBoundingClientRect().height;
            }

            var possible_items_in_row =  Math.floor((targetW-10)/currentItemw); 
            var rows = Math.ceil(items.length/possible_items_in_row);

            if (rows > 1) {
                var actW =  possible_items_in_row*currentItemw;
                var maxrow = possible_items_in_row;
            } else {
                var actW =  items.length*currentItemw; 
                var maxrow = items.length;
            }

            var scaleH = 1; var scaleW = 1;
            if (currentItemH*rows > targetH-5) {
                scaleH = 1 - (targetH-6-currentItemH)/100;
            }

            if (currentItemw*maxrow > targetW-15 ) {
                scaleW = 1 - (targetW-currentItemw*maxrow)/100;  
            }

            dojo.style(name+'_zone','transform','scale('+Math.min(scaleH, scaleW)+')');
        },

        addPawnOnBoard: function(player, color, zone, i) {
            if (zone === 'home') {
                dojo.place( this.format_block( 'jstpl_pawn', {
                    id: player,
                    i: i,
                    color: color
                } ) , 'overall_player_board_'+player );

                this.meepleZones[player+'_panel'].placeInZone( "pawn_"+i+"_"+player, player );
            } else {
                dojo.place( this.format_block( 'jstpl_pawn', {
                    id: player,
                    i: i,
                    color: color
                 } ) , 'mainboard' );

                dojo.addClass("pawn_"+i+"_"+player, 'small');    
                this.storyZones['story_'+zone].placeInZone( "pawn_"+i+"_"+player,player );
                this.storyZones['story_'+zone].updateDisplay();

                this.recalculateZone('story_'+zone);
            }
        },

       addArrowMarkerOnBoard: function( type, position ) {
            dojo.place( this.format_block( 'jstpl_arrowmarker', {
                id: type
            } ) , 'mainboard' );
            
            this.placeOnObject( type+"_marker", "newsbeattile_"+type+"_"+position );
        },

        addBonusMarkerOnBoard: function( x, type ) {
            dojo.place( this.format_block( 'jstpl_bonustrack', {
                id: type
            } ) , 'mainboard' );

            var translated = dojo.string.substitute( _("${marker} bonusmarker, position ${p}"), {
                p: x,
                marker: dojo.string.substitute('<div class="bonustrack ${t} bonustooltip"></div>', {t:type }),
            } );
            
            this.bonustrackZones[x-2].placeInZone( type+"_bonustrack" );
            
            var div = '<div style=" display: inline-block;"><span>'+translated+'</span></div>';
            this.addTooltipHtml( type+'_bonustrack', div,"" );
        },

        addPennyOnBoard: function(player) {
            var number = 0;
            for(i=0;i<3;i++) {
                if ( dojo.query($('pennytile_'+i+'_'+player))[0].childElementCount>0 ){
                    number++;
                }
            }

            var div = "<div class = \"penny\" id = \"penny"+number+"_"+player+"\" ></div>";
            dojo.place(div, 'pennytile_'+number+'_'+player );

            this.placeOnObject( "penny"+number+"_"+player, 'mainboard' );
            this.slideToObjectPos( "penny"+number+"_"+player, 'pennytile_'+number+'_'+player,0,0 ).play();

            if (parseInt(number+1) < 4) {
                $(player+'_pennyCount').innerText = parseInt(number+1);
            }
        },

        addAdvertismentOnBoard: function(player,x,y) {
            dojo.place( this.format_block( 'jstpl_advertisment', {
                player: player
            } ) , "playerboard_"+player );

            this.slideToObjectPos( "advertisment_"+player, "newspapertile_"+x+"_"+y+"_"+player,0,0 ).play(); 
        },

        moveBonusTrackItem: function(from,to, item) {
            this.bonustrackZones[to-2].placeInZone( item );
            this.bonustrackZones[from-2].removeFromZone( item );

            var translated = dojo.string.substitute( _("${marker} bonusmarker, position ${p}"), {
                p: to,
                marker: dojo.string.substitute('<div class="bonustrack ${t} bonustooltip"></div>', {t:item.split("_")[0] }),
            } );

            var div = '<div style=" display: inline-block;"><span>'+translated+'</span></div>';
            this.addTooltipHtml( item, div,"" );
        },

        addStoryOnBoard: function( beat,type,stars, location, arg ) {
            if ( location == "deck") {
                dojo.place( this.format_block( 'jstpl_story', {
                    beat: beat,
                    type: type,
                    stars: stars
                } ) , "storiestile_"+beat+"_"+type );  
            }  else if ( location.substr(0,5) == 'newsb') {
                dojo.place( this.format_block( 'jstpl_story', {
                    beat: beat,
                    type: type,
                    stars: stars
                } ) , 'mainboard' );

                this.addZonesToStory("story_"+beat+"_"+type+"_"+stars, location, false);
            } else {
                // var player = location.slice(-7);
                var player = location.split("_")[0];

                if ( location.split("_")[1] != "published" ) {
                    player = location.split("_")[2];
                    dojo.place( this.format_block( 'jstpl_story', {
                        beat: beat,
                        type: type,
                        stars: stars
                    } ) , "playerboard_"+player ); 

                    if ( arg == 0) {
                        dojo.addClass("story_"+beat+"_"+type+"_"+stars, 'enlarged');
                    } else {
                        dojo.addClass("story_"+beat+"_"+type+"_"+stars, 'enlargedrotated');
                    }

                    this.slideToObjectPos( "story_"+beat+"_"+type+"_"+stars, "newspapertile_"+location,0,0 ).play();
        
                } else {
                    var div = $(player+'_publishedCounter_'+beat);
                    var actual = parseInt(div.innerHTML)+ parseInt(stars);
                    div.innerHTML = actual;

                    if (!this.isSpectator) {
                        this.attachToNewParent( 'circle',  "playerbox_"+this.player_id );              
                    }
                }
            }
        },

        addArticleOnBoard: function( number,text, recalculateFont ) {
            dojo.place( this.format_block( 'jstpl_article', {
                x: number,
                text: text
            } ) , "headlinesframe"); 

            // if (recalculateFont) {
            //     this.calculateFont('article'+number+'_text'); 
            // }

            var node = dojo.byId("article"+number);
            var newnode = dojo.clone(node);
            dojo.style(newnode, "transform", "scale(1.8)" );
            dojo.removeAttr(newnode, "id");
            var div = '<div style="width: 345px; height: 250px; display: flex; justify-content: center; align-items: center;">'+newnode.outerHTML+'</div>'
            this.addTooltipHtml( 'article'+number, div );

            if (recalculateFont) {          // info widget of new story
                // if (dijit.byId( 'id0' )) {
                //     dijit.byId( 'id0' ).destroy( true );
                // }

                // this.myDlg = new dijit.Dialog({ title: _("New headline card!"), id: "id0" } );

                // dojo.style(newnode, "transform", "scale(1.8)" );
                // var div = '<div style="width: 400px; height: 300px; display: flex; justify-content: center; align-items: center; ">'+newnode.outerHTML+'</div>' ;

                // this.myDlg.attr("content", div );
                // this.myDlg.show(); 
                var x = $('article'+number).getBoundingClientRect().left - document.body.getBoundingClientRect().left;
                var y = $('article'+number).getBoundingClientRect().top - document.body.getBoundingClientRect().top;

                if ( dojo.style($('headlinesframe'),'max-width' ) == '200' ) {
                    x = x - $('outerframe').offsetLeft;  //x = 'initial'; -76
                    y = y -13; 
                } else {
                    x = x +160;
                    if (dojo.style('right-side','margin-left') == 0) {
                        y = y - $('right-side').getBoundingClientRect().height  - $('page-title').getBoundingClientRect().height -80 ; //y = 'initial'; -122
                    } else {
                        y = y - $('page-title').getBoundingClientRect().height -80 ; 
                    }
                }
                dojo.addClass('article'+number, 'articlebeforeanim');
                dojo.style('article'+number,'top',dojo.style('mainboard','top')+'px' );     // IE+EDGE hack
                dojo.style('article'+number,'left',dojo.style('mainboard','left')+'px' );
                this.placeOnObject('article'+number, 'mainboard');

                var anim = dojo.animateProperty({
                    node:'article'+number,
                    duration: 1000,
                    delay: 2500,
                    onEnd: function() {
                        dojo.removeClass('article'+number,'articlebeforeanim')
                    },
                    properties: {
                        top: { end: y},
                        left: { end: x}
                    },
                });
                anim.play();
            }
        },

        getPosition: function(el) {
            var xPos = 0;
            var yPos = 0;
           
            while (el) {
              if (el.tagName == "BODY") {
                // deal with browser quirks with body/window/document and page scroll
                var xScroll = el.scrollLeft || document.documentElement.scrollLeft;
                var yScroll = el.scrollTop || document.documentElement.scrollTop;
           
                xPos += (el.offsetLeft - xScroll + el.clientLeft);
                yPos += (el.offsetTop - yScroll + el.clientTop);
              } else {
                // for all other non-BODY elements
                xPos += (el.offsetLeft - el.scrollLeft + el.clientLeft);
                yPos += (el.offsetTop - el.scrollTop + el.clientTop);
              }
           
              el = el.offsetParent;
            }
            return {
              x: xPos,
              y: yPos
            };
        },

        calculateFont: function(node) {         //recalculates font size - different languages needs different size
            var element = dojo.byId(node);
            var run = true;

            while (run == true) {
                if (element.scrollHeight > element.clientHeight+4) {
                    var actFontSize = dojo.style(element, "font-size" );
                    dojo.style(element, "font-size", parseInt(actFontSize.slice(0,-2))-1+"px" );
                    dojo.style(element, "line-height", parseInt(actFontSize.slice(0,-2))-1+"px" );
                } else {
                    run = false;
                }
            }
        },

        recalculateArticlesFonts: function() {
            var ids = this.gamedatas.articles;

            for (i=0; i<ids.length;i++) {
                // var node = "article"+ids[i]+"_text";
                // this.calculateFont(node);

                node = dojo.byId("article"+ids[i]);
                var newnode = dojo.clone(node);
                dojo.style(newnode, "transform", "scale(1.8)" );
                dojo.removeAttr(newnode, "id");
                this.addTooltipHtml( 'article'+ids[i], newnode.outerHTML );
            }
        },


        moveStoryToBeatTrack: function( beat,type,stars,  position, select ) {
            var anim = this.slideToObject( "story_"+beat+"_"+type+"_"+stars, "newsbeattile_"+beat+"_"+position );
            dojo.connect(anim,'onEnd',  dojo.hitch(this,'addZonesToStory', "story_"+beat+"_"+type+"_"+stars,"newsbeattile_"+beat+"_"+position, select) );
            anim.play();  
        },

        moveStoryToClaimedPanel: function( beat,type,stars,position ) {                                  
            var anim = this.slideToObject( "story_"+beat+"_"+type+"_"+stars, "claimedStory_"+position+"_inner" );
            dojo.connect(anim,'onEnd',  dojo.hitch(this,'addZonesToClaimedStory', "story_"+beat+"_"+type+"_"+stars, position) );
            anim.play();  
        },

        addZonesToStory: function(id,target, forSelection) {
            dojo.place(id, 'mainboard');
            dojo.addClass(id,'rotated');

            if (id.slice(-3,-2) == "C" || id.slice(-3,-2) == "D" ) {
                var w = dojo.style( target, 'height')/2;
            } else {
                var w=0;
            }

            dojo.style(id,'top',dojo.style(target,'top')+'px' );     // IE+EDGE hack
            dojo.style(id,'left',dojo.style(target,'left')+'px' );

            this.placeOnObjectPos(id, target,0,w);

            var actW =  $(id).getBoundingClientRect().width;
            var actH =  $(id).getBoundingClientRect().height;

            dojo.place( this.format_block( 'jstpl_storyzoneselect', {
                id: id+"_zoneSelect",
                w: actW,
                h: actH
            } ) , 'mainboard' );  

            dojo.place( this.format_block( 'jstpl_storyzone', {           
                id: id+"_zone",
                w: actW-10,
                h: actH
            } ) , id+"_zoneSelect" );

            if (!this.isSpectator) {
                dojo.place( this.format_block( 'jstpl_storymenu', {         
                    beat: id.split("_")[1],
                    type: id.split("_")[2],
                    stars: id.split("_")[3],
                    color: this.gamedatas.players[ this.player_id ].color,
                } ) , 'mainboard' );

                if (this.screenWidth < 740 && (id.split("_")[1] == 'city' || id.split("_")[1] == 'human') ) {                   // small screens menu oriented to right
                    dojo.query($('story_'+id.split("_")[1]+'_'+id.split("_")[2]+'_'+id.split("_")[3]+'_place')).addClass('smallscreens');
                }
            }

            this.placeOnObject(id+"_zoneSelect",id );

            this.storyZones[id] = new ebg.zone();
            this.storyZones[id].create(this,id+"_zone",10,10); 
            this.storyZones[id].item_margin = 0; 

            if (forSelection) {
                var element = dojo.query($(id+"_zoneSelect")).addClass('selected visible');

                if( this.isCurrentPlayerActive() ) {
                    this.handlers.push( dojo.connect(element[0],'click', this ,'placeStoryToTile') );
                }
            }

            if (!this.isSpectator) {
                this.placeOnObjectPos(id+"_menu",target, 15+$(target).getBoundingClientRect().width/2  ,w );
            }
        },

        addZonesToClaimedStory: function(id, position, addSelection) {
            var actW =  $(id).getBoundingClientRect().width;
            var actH =  $(id).getBoundingClientRect().height;

            dojo.place( this.format_block( 'jstpl_storyzoneselect', {
                id: id+"_zoneSelect",
                w: actW,
                h: actH
            } ) , 'claimedStory_'+position+'_inner' );                                                    
            
            this.placeOnObject(id+"_zoneSelect",'claimedStory_'+position+'_inner');
            this.attachToNewParent(id, 'claimedStory_'+position);

            if (addSelection) {
                var element = dojo.query($(id+"_zoneSelect")).addClass('selected visible');

                if( this.isCurrentPlayerActive() ) {
                    this.handlers.push( dojo.connect(element[0],'click', this ,'placeStoryToTile') );
                }
            }
        },

        movePawnToStory: function(pawn, story, oldStory) {
            if (oldStory === undefined) {
                dojo.addClass(pawn, 'small');
                // var player = pawn.substr(7,7);
                var player = pawn.split("_")[2];
                this.storyZones[story].placeInZone( pawn, player);
                this.storyZones[story].updateDisplay();
                this.meepleZones[player+'_panel'].removeFromZone(pawn);

                this.recalculateZone(story);
            } else {
                // var player = pawn.substr(7,7);
                var player = pawn.split("_")[2];
                this.storyZones[story].placeInZone(pawn, player);
                this.storyZones[story].updateDisplay();
                this.storyZones[oldStory].removeFromZone( pawn);
                this.storyZones[oldStory].updateDisplay();
                this.recalculateZone(story);
                this.recalculateZone(oldStory);
            }
        },

        movePawnHome: function(pawn, player) {
            dojo.query($(pawn)).removeClass('small');
            var zone = dojo.attr(dojo.query($(pawn))[0].parentNode,'id').slice(0,-5);;
            this.meepleZones[player+'_panel'].placeInZone( pawn,player );
            this.storyZones[zone].removeFromZone( pawn);
            this.storyZones[zone].updateDisplay();
            this.recalculateZone(zone);
        },

        endPlaceStory: function(story,x,y) {
            if (!dojo.hasClass(story, 'enlargedrotated') ) {
                dojo.addClass(story, 'enlarged');
            } 

            if (!dojo.addClass(story, 'elevated') ) {
                dojo.addClass(story, 'elevated');
            } 

            dojo.place(story, "newspapertile_"+x+"_"+y+"_"+this.getActivePlayerId() );
            dojo.style(story,'left','');
            dojo.style(story,'top','');

            var actW =  $(story).getBoundingClientRect().width;
            var actH =  $(story).getBoundingClientRect().height;

            dojo.style( $('circle'), 'width',actW*3+"px");
            dojo.style( $('circle'), 'height',actH*3+"px");

            this.placeOnObject( 'circle',  story );

            this.updateArrows(x,y,story);
            
            if (typeof g_replayFrom == "undefined") {
                dojo.query('.circle').addClass('open');
            }
        },

        updateArrows: function(x,y,tile) {
            var x = Number(x);
            var y = Number(y);
            var w = Number(this.getSizeFromStory(tile)[0]);
            var h = Number(this.getSizeFromStory(tile)[1]);

            if ( (x+w) >=  4) {
                dojo.style("button_right",'visibility', 'hidden');
            } else {
                dojo.style("button_right",'visibility', 'visible');
            }

            if ( (x-1) <  0) {
                dojo.style("button_left",'visibility', 'hidden');
            } else {
                dojo.style("button_left",'visibility', 'visible');
            }

            if ( (y-1) <  0) {
                dojo.style("button_up",'visibility', 'hidden');
            } else {
                dojo.style("button_up",'visibility', 'visible');
            }

            if ( (y+h) >=  3) {
                dojo.style("button_down",'visibility', 'hidden');
            } else {
                dojo.style("button_down",'visibility', 'visible');
            }
        },

        addHandlersToMenu : function(story) {
            dojo.forEach(this.handlers,dojo.disconnect);
            this.handlers = [];
            this.handlers.push( dojo.connect(dojo.query($('button_left'))[0],'click', this , dojo.partial(this.moveStory, story) ));
            this.handlers.push( dojo.connect(dojo.query($('button_right'))[0],'click', this ,dojo.partial(this.moveStory, story) ));
            this.handlers.push( dojo.connect(dojo.query($('button_up'))[0],'click', this, dojo.partial(this.moveStory, story) ));
            this.handlers.push( dojo.connect(dojo.query($('button_down'))[0],'click', this, dojo.partial(this.moveStory, story) ));
            this.handlers.push( dojo.connect(dojo.query($('button_cancel'))[0],'click', this, dojo.partial(this.cancelPlacement, story) ));
            this.handlers.push( dojo.connect(dojo.query($('button_rotate'))[0],'click', this, dojo.partial(this.rotateStory, story) ));
            this.handlers.push( dojo.connect(dojo.query($('button_confirm'))[0],'click', this, dojo.partial(this.confirmPlacement, story) ));
        },

        customAnimation : function (id, pixels,direction) {
            if (direction == 'x') {
                var anim = dojo.animateProperty({
                        node:id,
                        duration: 400,
                        properties: {
                            left: { end: pixels}
                        },
                    }); 
            } else if ( direction== 'y') {
                var anim = dojo.animateProperty({
                        node:id,
                        duration: 400,
                        properties: {
                            top: { end: pixels}
                        },
                    });  
            }

            return anim
        },

        subselectPawns: function(toExlude) {
            var selected = [];
            var color = this.gamedatas.players[ this.player_id ].color;
            var allPawns = dojo.query('.pawn_'+color+'.small');

            for (var i = 0;i<toExlude.length;i++) {
                selected.push( dojo.query($('pawn_'+toExlude[i]+'_'+this.player_id)) );
            }

            for (var i = 0;i<selected.length;i++) {
                var index = dojo.indexOf(allPawns, selected[i][0]);
                allPawns.splice(index, 1);
            }

            return allPawns;
        },

        moveArrowMarker: function(type, position) {
            this.slideToObject( type+"_marker", "newsbeattile_"+type+"_"+position ).play();
        },

        moveStoryBackToClaimedPanel: function(story, noZone, position) {                                 
            dojo.removeClass(story, 'enlargedrotated enlarged elevated');
            dojo.addClass(story, 'enlarged2');
            dojo.query('.circle').removeClass('open');

            if (noZone) {
                this.attachToNewParent( story, 'claimedStory_'+position );
                this.slideToObject( story, 'claimedStory_'+position+'_inner' ).play();
                this.restoreServerGameState();
            } else {
                var story_split = story.split("_");
                this.moveStoryToClaimedPanel(story_split[1],story_split[2],story_split[3], position);
            }
        },

        moveStoryToTile: function(story, player, x, y, rotation) {
            this.attachToNewParent( story, 'newspapertile_0_0_'+player  );
            dojo.query($(story)).removeClass('rotated');

            if (rotation == 0) {
                dojo.query($(story)).addClass('elevated enlarged');
            } else {
                dojo.query($(story)).addClass('elevated enlargedrotated');
            }

            var anim = this.slideToObjectPos( story, "newspapertile_"+x+"_"+y+"_"+player,0,0 );
            if (player == this.player_id && typeof g_replayFrom == "undefined" ) {                                           
                // dojo.connect(anim,'onEnd',  dojo.hitch(this,'endPlaceStory', story,0,0 ) );
                dojo.connect(anim,'onEnd',  dojo.hitch(this,'endPlaceStory', story,x,y ) );
            }
            anim.play();
        },

        moveStoryToPlayerPublished: function( beat,type,stars, player ) {                      
            var anim = this.slideToObject( "story_"+beat+"_"+type+"_"+stars, 'overall_player_board_'+player, 800,200);

            dojo.connect(anim,'onEnd',  function() { 
                dojo.destroy("story_"+beat+"_"+type+"_"+stars);
                dojo.destroy("story_"+beat+"_"+type+"_"+stars+"_menu");
                var div = $(player+'_publishedCounter_'+beat);
                var actual = parseInt(div.innerHTML)+ parseInt(stars);
                div.innerHTML = actual;
            } );

            return anim; 
        },

        moveStoryBackToDeck: function( beat,type,stars ) {                                                      
            var anim = this.slideToObject( "story_"+beat+"_"+type+"_"+stars, "storiestile_"+beat+"_"+type, 800,200);
            dojo.destroy("story_"+beat+"_"+type+"_"+stars+"_menu");
            dojo.connect(anim,'onEnd',  function() { 
                dojo.place("story_"+beat+"_"+type+"_"+stars, "storiestile_"+beat+"_"+type,"first");
                dojo.query($("story_"+beat+"_"+type+"_"+stars)).removeClass('rotated enlarged enlarged2');
                dojo.removeAttr($("story_"+beat+"_"+type+"_"+stars), 'style');

                dojo.destroy("story_"+beat+"_"+type+"_"+stars+"_zone");
                dojo.destroy("story_"+beat+"_"+type+"_"+stars+"_zoneSelect");
            } );
            delete this.storyZones["story_"+beat+"_"+type+"_"+stars];
            return anim; 
        },

        moveStoryToNewPosition: function( beat,type,stars, position ) {                             

            if (type === 'B' || type === 'D') {
                var w = (dojo.style( "newsbeattile_"+beat+"_"+position, 'width')-dojo.style( "story_"+beat+"_"+type+"_"+stars, 'height')) /4;
            } else {
                var w = -1 + (dojo.style( "newsbeattile_"+beat+"_"+position, 'width')-dojo.style( "story_"+beat+"_"+type+"_"+stars, 'height')) /2;
            }


            if (type === 'C' || type === 'D') {
                var ylen = ($("newsbeattile_"+beat+"_"+position).getBoundingClientRect().height)/2 -((50 - $("newsbeattile_"+beat+"_"+position).getBoundingClientRect().height)/2) +2;
            } else {
                var ylen = -((50 - $("newsbeattile_"+beat+"_"+position).getBoundingClientRect().height)/2) +2;
            }

            var anim = this.slideToObjectPos( "story_"+beat+"_"+type+"_"+stars, "newsbeattile_"+beat+"_"+position,w+1,0);
            dojo.connect(anim,'onEnd', dojo.hitch(this, function() { 
                this.placeOnObject("story_"+beat+"_"+type+"_"+stars+"_zoneSelect","story_"+beat+"_"+type+"_"+stars );
                this.slideToObjectPos( "story_"+beat+"_"+type+"_"+stars+"_menu","newsbeattile_"+beat+"_"+position,6+$("newsbeattile_"+beat+"_"+position).getBoundingClientRect().width ,ylen , 100, 500 ).play();
             })  )
            anim.play(); 
        },

        slidePanels: function(storiesCount, stories, topBeatCount, arrowValues){
            var coef = 0;
            if (dojo.position($('playersframe'), true).y - dojo.position($('mainboard'), true).y < 800 && dojo.style('headlinesframe', 'max-width') == 830 )  {
                var coef = 175;
            }

            this.slideToObjectPos( "playersframe", "mainboard", -15, 350-coef ).play();
            
            dojo.fx.wipeIn({
                node: "claimedPanel",
            }).play();

            dojo.style('disablingDiv', 'display','block');
            dojo.style('claimedPanel', 'display','inline-flex');

            for(var i = 0;i<storiesCount;i++) {
                if (i<topBeatCount) {
                    var text = _("TOP BEAT!");
                } else {
                    var text = '';
                }
                dojo.place( this.format_block( 'jstpl_claimed_holder', {
                    x: i,
                    text: text
                } ) , 'outerClaimedHolder' );  

                if (arrowValues[i]) {
                    dojo.place( this.format_block( 'jstpl_arrowmarkerindicator', {
                        x: i,
                        value: arrowValues[i],
                    } ) , 'claimedStory_'+i );  

                    this.placeOnObjectPos("arrowindicator"+i,'claimedStory_'+i, 0,87 );

                    var text = _('Arrow marker value');
                    var div = "<div>"+text+"</div>";
                    this.addTooltipHtml( "arrowindicator"+i, div );
                }
            }

            for(var i = 0;i<storiesCount;i++) {
                if (stories[i]) {
                    dojo.query($('story_'+stories[i])).removeClass('rotated');
                    dojo.query($('story_'+stories[i])).addClass('enlarged2');

                    delete this.storyZones['story_'+stories[i]];
                    dojo.destroy('story_'+stories[i]+'_zone');
                    dojo.destroy('story_'+stories[i]+'_zoneSelect');

                    var story_split = stories[i].split("_");
                    this.moveStoryToClaimedPanel( story_split[0],story_split[1],story_split[2], i );    
                }
            }

            var text_size = dojo.query('.claimedStoryText')[0].getBoundingClientRect().height;
            if (text_size > 18) {
                    var element = dojo.query('.claimedStoryText')[0];
                    var run = true;
                    var runcounter = 0;

                    while (run == true) {
                        if (element.getBoundingClientRect().height > 18) {
                            var actFontSize = dojo.style(element, "font-size" );
                            dojo.style(element, "font-size", parseInt(actFontSize.slice(0,-2))-1+"px" );
                            dojo.style(element, "line-height", parseInt(actFontSize.slice(0,-2))+3+"px" );
                            runcounter++;
                            if (runcounter>20){
                                break;
                            }
                        } else {
                            run = false;
                        }
                    }

                    for (i=0;i<dojo.query('.claimedStoryText').length;i++) {
                        dojo.style(dojo.query('.claimedStoryText')[i],"font-size", parseInt(actFontSize.slice(0,-2))-1+"px" ) ;
                        dojo.style(dojo.query('.claimedStoryText')[i], "line-height", parseInt(actFontSize.slice(0,-2))+3+"px") ;
                    }
            }


        },

        slidePanelsBack: function() {  
            dojo.fx.wipeOut({
                node: "claimedPanel",
            }).play();

            dojo.query('.outerClaimedHolder >').forEach(dojo.destroy);
 
            var anim = dojo.animateProperty({
                node:"playersframe",
                duration: 800,
                properties: {
                    left: { end: 0},
                    top: { end: 0}
                },
            }); 

            anim.play();
            dojo.style('disablingDiv', 'display','none');
        },

        addCurtain: function(phase,topCount, allCount) {
            dojo.destroy("curtain");

            var panel_width =  $('outerClaimedHolder').getBoundingClientRect().width;
            var restCount = allCount-topCount;


            if (phase === 'topBeats' ) {
                width = (panel_width/(allCount))*restCount;

                dojo.place( this.format_block( 'jstpl_curtain', {
                    width : width
                } ) , 'claimedPanel' );

                this.placeOnObjectPos( 'curtain', 'outerClaimedHolder',(panel_width-width)/2,0);
            }

            if (phase === 'other' ) {
                width = (panel_width/(allCount))*topCount;

                dojo.place( this.format_block( 'jstpl_curtain', {
                    width : width
                } ) , 'claimedPanel' );

                this.placeOnObjectPos( 'curtain', 'outerClaimedHolder', -(panel_width-width)/2,0);
            }

            dojo.disconnect(this.panelhanler);
            this.panelhanler = dojo.connect(window,'onresize',  dojo.hitch(this,'recalculateCurtain', phase,topCount, allCount) );
        },

        recalculateCurtain: function(phase,topCount, allCount) {
            dojo.destroy("curtain");
            this.addCurtain(phase,topCount, allCount);
        },

        flipArrowmarkers: function() {
            dojo.query('.markercontainer').addClass('flipped');
        },

        slideStoryMenu: function(evt) {
            dojo.stopEvent( evt );
            var target = evt.target || evt.srcElement;
            var story = target.id.slice(0, -5).slice(6);

            var node = $('story_'+story+'zone');
            var meeple = '.pawn_'+this.gamedatas.players[ this.player_id ].color ;
            var meeplesStory = dojo.query(meeple,node).length;
            node = $(this.player_id +'_meeplePanel');
            var meeplesFree = dojo.query(meeple,node).length;


            if (story != this.OpenedMenu && this.OpenedMenu != 0 ) {
                dojo.removeClass("story_"+this.OpenedMenu+"click", 'click_back');
                dojo.removeClass("story_"+this.OpenedMenu+"place", 'open');
                dojo.query('.menubutton').forEach(dojo.destroy);
            }

            if (dojo.hasClass("story_"+story+"click", 'click_back')) {
                dojo.removeClass("story_"+story+"click", 'click_back');
                dojo.removeClass("story_"+story+"place", 'open');
                dojo.query('.menubutton').forEach(dojo.destroy);
                this.OpenedMenu = 0;
            } else {
                this.OpenedMenu = story;

                if (meeplesStory == 0  ) {
                    dojo.addClass("story_"+story+"mp", 'none');
                } else {
                    dojo.removeClass("story_"+story+"mp", 'none');

                    for (i=0;i<meeplesStory;i++) {
                        dojo.place( this.format_block( 'jstpl_menubutton', {
                            x: i+1,
                            i: i+1
                        } ) , "story_"+story+"mp" );

                        if (i == 0) {
                            this.handlers.push( dojo.connect( dojo.query($('menubutton_'+Number(i+1)))[0],'click', this ,'selectReporter') );
                        } else {
                            this.handlers.push( dojo.connect(dojo.query($('menubutton_'+Number(i+1)))[0],'click', this ,'selectMoreReporters') );
                        }
                    }
                }

                if (meeplesFree == 0 || this.gamedatas.gamestate.name == 'playerSelectReporters' || this.gamedatas.gamestate.name == 'playerRecallReporters') {
                    dojo.addClass("story_"+story+"sp", 'none');
                }  else {
                    dojo.removeClass("story_"+story+"sp", 'none');
                
                    for (i=10;i<meeplesFree+10;i++) {
                        dojo.place( this.format_block( 'jstpl_menubutton', {
                            x: i+1,
                            i: i+1-10
                        } ) , "story_"+story+"sp" );

                        this.handlers.push( dojo.connect(dojo.query($('menubutton_'+Number(i+1)))[0],'click', this ,'sendReportersFast') );
                    }
                }

                dojo.addClass("story_"+story+"click", 'click_back');
                dojo.addClass("story_"+story+"place", 'open');
            }
        },

        subselectStoriesForMenu: function(menus,selectedReporters) {
            var meeple = '.pawn_'+this.gamedatas.players[ this.player_id ].color;
            var arrows = dojo.query('.arrowplace');

            for (i=0;i<menus.length;i++) {
                var story = menus[i].id.slice(0,-5);

                var parent = dojo.query($(story+'_zone'))[0];
                var result = dojo.query(meeple,parent);

                if (result.length > 0) {
                    var exclude = false;
                    for (j=0;j<result.length;j++) {
                        if (  selectedReporters.indexOf( Number(result[j].id.slice(5,6)) ) != -1  ) {
                            exclude = true
                        }
                    }

                    if ( !exclude) {
                        dojo.addClass(menus[i].id, 'visible');
                        dojo.addClass(arrows[i].id,'highlight');
                        this.handlers.push( dojo.connect(arrows[i],'click', this ,'slideStoryMenu') );
                    }
                }
            }
        },

        panelslider: function() {
            var top = 0;
            var left = 0;
            var top2 = -5;

            if ( dojo.hasClass(dojo.query('.claimedslider')[0],'back') ) {
                top = this.panelPosition;
                top2 = 50;
            } else {
                this.panelPosition = dojo.style('playersframe','top');
            }

            var anim = dojo.animateProperty({
                node:"playersframe",
                duration: 800,
                properties: {
                    left: { end: left},
                    top: { end: top}
                },
            }); 

            var anim2 = dojo.animateProperty({
                node:"claimedPanel",
                duration: 800,
                properties: {
                    top: { end: top2}
                },
            }); 

            dojo.toggleClass(dojo.query('.claimedslider')[0], "back");
            dojo.fx.combine([anim,anim2]).play();
        },

        addStrike: function(player_id) {
            this.disablePlayerPanel( player_id );

            dojo.place( this.format_block( 'jstpl_strike', {
                text: _('STRIKE!!'),
            } ) , "overall_player_board_"+player_id );

            this.placeOnObject('strike',"overall_player_board_"+player_id);
        },

        moveStrike: function(player_id) {
            this.enableAllPlayerPanels();
            this.disablePlayerPanel( player_id );
            this.attachToNewParent('strike',"overall_player_board_"+player_id);
            this.placeOnObject('strike',"overall_player_board_"+player_id);
        },


        //--------------------------/** Log injection */-------------------------------------------------------

        /* @Override */
        format_string_recursive : function(log, args) {
            try {
                if (log && args && !args.processed) {

                    args.processed = true;
                    
                    if (!this.isSpectator){
                        args.You = this.divYou(); // will replace ${You} with colored version
                    }

                    var keys = ['token_name', 'card_text'];

                    for ( var i in keys) {
                        var key = keys[i];

                        if (typeof args[key] == 'string' && key != 'card_text') {
                            args[key] = this.getTokenDiv(key, args);                            
                        }

                        if (typeof args[key] == 'string' && key == 'card_text') {
                            args[key] = this.getBoldText(args[key]);                            
                        }
                    }
                }
            } catch (e) {
                console.error(log,args,"Exception thrown", e.stack);
            }
            return this.inherited(arguments);
        },

        divYou : function() {
            var color = this.gamedatas.players[this.player_id].color;
            var color_bg = "";
            if (this.gamedatas.players[this.player_id] && this.gamedatas.players[this.player_id].color_back) {
                color_bg = "background-color:#" + this.gamedatas.players[this.player_id].color_back + ";";
            }
            var you = "<span style=\"font-weight:bold;color:#" + color + ";" + color_bg + "\">" + __("lang_mainsite", "You") + "</span>";
            return you;
        },

        getBoldText: function(args) {
            // var boldText = "<div style=\"font-weight:bold; padding: 6px;\">" +args+ "</div>";
            var boldText = "<div style=\"font-weight:bold; padding: 6px;\">" +_(args)+ "</div>";
            return boldText;
        },

        getTokenDiv : function(key, args) {
            var token = args[key];
            var item_type = token.split("_")[0];

            switch (item_type) {
                case 'bonustrack':
                    var tokenDiv = this.format_block('jstpl_bonustrack_log', {
                        "type" : token.split("_")[1],
                    });
                    return tokenDiv;
                case 'story':
                    if ($(token)) {
                        var clone = dojo.clone($(token));

                        dojo.removeAttr(clone, "id");
                        dojo.removeAttr(clone, "style");
                        dojo.addClass(clone, "logitem");
                        dojo.removeClass(clone,"rotated");

                        return clone.outerHTML;
                    } else {
                        var tokenDiv = this.format_block('jstpl_story_newlog', {
                            beat: token.split("_")[1],
                            type: token.split("_")[2],
                            stars: token.split("_")[3]
                        });
                        return tokenDiv;
                    }

                case 'frontpage':
                    var player_id = token.split("_")[1];
                    if ($("playerboard_"+token.split("_")[1])) {
                        var id = "playerboard_"+token.split("_")[1];
                        var clone = dojo.clone($(id));
                        dojo.attr(clone, "id", 'playerboardclone'+this.globalid);
                        dojo.addClass(clone, "logitem");

                        
                        var divs = dojo.query("div",clone);
                        for(i=0;i<divs.length;i++) {
                            dojo.attr(divs[i], "id", dojo.attr(divs[i], "id")+'_clone'+this.globalid);
                        }

                        dojo.query(".story",clone).forEach(dojo.destroy);
                        dojo.query(".pennyadvertisment",clone).forEach(dojo.destroy);
                        dojo.query(".pennyBox",clone).forEach(dojo.destroy);


                        // fake place
                        dojo.place(clone,'disablingDiv');

                        //build front page for log
                        var items = token.split(":");
                        var stories = [];
                        var advert = [];
                        for (i=1;i<items.length;i++) {
                            if (items[i].substring(0, 5) == 'story') {
                                stories.push(items[i]);
                            }

                            if (items[i].substring(0, 6) == 'advert') {
                                advert.push(items[i]);
                            }
                        }

                        for (i=0;i<stories.length;i++) {
                            dojo.place( this.format_block( 'jstpl_story_log', {
                                beat: stories[i].split("_")[1],
                                type: stories[i].split("_")[2],
                                stars: stories[i].split("_")[3]
                            } ) , 'newspapertile_'+stories[i].split("_")[4]+'_'+stories[i].split("_")[5]+'_'+player_id+'_clone'+this.globalid ); 
        
                            if ( stories[i].split("_")[6] == '0') {
                                dojo.addClass("story_"+stories[i].split("_")[1]+"_"+stories[i].split("_")[2]+"_"+stories[i].split("_")[3]+"_log", 'enlarged');
                            } else {
                                dojo.addClass("story_"+stories[i].split("_")[1]+"_"+stories[i].split("_")[2]+"_"+stories[i].split("_")[3]+"_log", 'enlargedrotated');
                            }
                        }

                        for (i=0;i<advert.length;i++) {
                            if (advert[i].split("_")[1]) {
                                dojo.place( this.format_block( 'jstpl_advertisment', {
                                    player: 'advert_clone'+this.globalid
                                } ) , 'newspapertile_'+advert[i].split("_")[1]+'_'+advert[i].split("_")[2]+'_'+player_id+'_clone'+this.globalid );
                            }
                        }

                        var newouterdiv = "<div style=\"height: 120px; width: 190px; margin: 0 auto\">"+clone.outerHTML+'</div>';

                        dojo.destroy(clone);
                        this.globalid++;
                        return newouterdiv;
                    }
                    break;                
     
                default:
                    break;
            }
            return token;
       },



        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
       
       selectStory: function(evt) {
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'selectStory' ) ){   return; }

            var target = evt.target || evt.srcElement;
            var story = target.id.slice(0, -5).slice(6);

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/selectStory.html", {story: story, lock : true}, 
                            this, function(result) {}, function(is_error) {
            });
       },

       sendReporters: function(evt) {                                               
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'sendReporters' ) ){   return; }

            var target = evt.target || evt.srcElement;
            var meeples_to_send = target.id.slice(-1);

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/sendReporters.html", {reporters: meeples_to_send, lock : true}, 
                            this, function(result) {}, function(is_error) {
            });
       },

       sendReportersFast: function(evt) {                                                
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'sendReportersFast' ) ){   return; }

            var target = evt.target || evt.srcElement;

            var meeples_to_send = target.id.slice(-1);
            var story = this.OpenedMenu.slice(0,-1);

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/sendReportersFast.html", {reporters: meeples_to_send, story: story, lock : true}, 
                            this, function(result) {}, function(is_error) {
            });
        },

       cancelSelection: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('cancel', true)) {
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/cancelPlayerTurn.html", {lock : true}, 
                    this, function(result) {}, function(is_error) {
                });
            }
       },

       selectReporter: function(evt) {                                 
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'selectReporter' ) ){   return; }

            var target = evt.target || evt.srcElement;

            if ( target.id.slice(0,4) == 'menu') {
                var target_story = dojo.query( $(target.id))[0].parentNode.id.slice(0,-3);
                var parent = dojo.query($(target_story+'_zone'))[0];
                var child = '.pawn_'+this.gamedatas.players[ this.player_id ].color ;
                var meeple = dojo.query(child,parent)[0].id.slice(5,6);

            } else {
                var meeple = target.id.slice(5,6);
            }

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/selectReporter.html", {reporter: meeple, lock : true}, 
                this, function(result) {}, function(is_error) {
            });
       },

       selectMoreReporters: function(evt) {
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'selectMoreReporters' ) ){   return; }

            var target = evt.target || evt.srcElement;
            var meepleCount = target.id.slice(-1);
            var story = this.OpenedMenu.slice(0,-1);

            var parent = dojo.query($('story_'+story+'_zone'))[0];
            var child = '.pawn_'+this.gamedatas.players[ this.player_id ].color ;
            var meepleList = dojo.query(child,parent);

            var listToSend = "";

            for(var i=0;i<meepleCount;i++) {
                var pawn = meepleList[i].id.slice(5,6);
                listToSend += pawn+',';
            }

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/selectMoreReporters.html", {reporters: listToSend, lock : true}, 
                this, function(result) {}, function(is_error) {
            });
        },

       recallReporters: function(evt) {
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'recallReporters' ) ){   return; }

            var meeples = dojo.query('.selected');
            var listToSend = "";

            for(var i=0;i<meeples.length;i++) {
                var pawn = dojo.attr(meeples[i], "id").split("_");
                if ( pawn[2] == this.getActivePlayerId() ) {
                    listToSend += pawn[1]+',';
                }
            }

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/recallReporters.html", {meeples: listToSend, lock : true}, 
                            this, function(result) {}, function(is_error) {
            });  
       },

       reassignReporter: function(evt) {
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'reassignReporter' ) ){   return; }

            var target = evt.target || evt.srcElement;
            var story = target.id.slice(0, -5).slice(6);

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/reassignReporter.html", {story: story, lock : true}, 
                            this, function(result) {}, function(is_error) {
            });            
       },

       goToPress: function(evt) {                    
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'goToPress' ) ){   return; }

            if ( (Object.keys(this.gamedatas.players).length) >3 ){
                var pennies_needed = 2;
            } else {
                var pennies_needed = 3;
            }
            var pennies =  parseInt($(this.getActivePlayerId()+'_pennyCount').innerText);

            if (pennies_needed != pennies || dojo.hasClass(dojo.query('.markercontainer')[0],'flipped') ){
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/goToPress.html", {lock : true}, 
                                this, function(result) {}, function(is_error) {
                });  
            } else {
                this.confirmationDialog( _('Are you sure you want to go to press? Final edition will be triggerd!'), dojo.hitch( this, function() {
                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/goToPress.html", {lock : true}, 
                                    this, function(result) {}, function(is_error) {
                    });  
                } ) ); 
                return;
            }
       },

       placeStoryToTile: function(evt) {
            dojo.stopEvent( evt );
            if( ! this.checkAction( 'placeStory' ) ){   return; }

            var target = evt.target || evt.srcElement;

            if (target.id.slice(-4) === 'zone') {
                var story = target.id.slice(0, -5); 
            } else if (target.id.slice(-10) === 'zoneSelect') {
                var story = target.id.slice(0, -11); 
            } else {
                return;
            }

            var parent =  dojo.query( $(story).parentNode)[0].id;
            this.actualClaimedStoryPostion = parent.slice(-1);

            // this.moveStoryToTile(story,this.getActivePlayerId(), 0, 0,0);
            if (this.firstpositions[story.slice(6)].x == null) {
                this.moveStoryToTile(story,this.getActivePlayerId(), 0 , 0 ,0 );
            } else {
                this.moveStoryToTile(story,this.getActivePlayerId(), this.firstpositions[story.slice(6)].y , this.firstpositions[story.slice(6)].x ,this.firstpositions[story.slice(6)].rotation );
            }
            this.setClientState( 'client_placeStory', {                
                "descriptionmyturn" : _('${you} must place the story'),
                "possibleactions" : ["moveStory", "rotateStory", "confirmPlacement", "cancelPlacement"],
                "args" : {"you": '', "story": story}  //story
            } );
       },

       moveStory: function(story, evt) {
            dojo.stopEvent(evt);
            if( ! this.checkAction( 'moveStory' ) ){   return; }

            var target = evt.target || evt.srcElement;
            var parent  = dojo.query($(story))[0].parentNode;
            var x =  parent.id.slice(14,15);
            var y =  parent.id.slice(16,17);
            var pixels = dojo.style(parent,'width');

            switch (target.id) {
                case "button_left":
                        x = Number(x)-1;
                        var direction = 'x';
                        pixels = -pixels;
                break;
                case "button_right":
                    x = Number(x)+1;
                    var direction = 'x';
                break;
                case "button_up":
                    y = Number(y)-1;
                    var direction = 'y';
                    pixels = -pixels;
                break;
                case "button_down":
                    y = Number(y)+1;
                    var direction = 'y';
                break;
            }

            anim = this.customAnimation( story, pixels, direction);
            dojo.removeClass('circle', 'open');
            dojo.connect(anim,'onEnd',  dojo.hitch(this,'endPlaceStory', story,x,y ) );
            anim.play(); 
        },

        rotateStory: function(story, evt){
            dojo.stopEvent(evt);
            if( ! this.checkAction( 'rotateStory' ) ){   return; }

            var parent  = dojo.query($(story))[0].parentNode;
            var x =  Number(parent.id.slice(14,15));
            var y = Number( parent.id.slice(16,17));

            if (dojo.hasClass(story, 'enlargedrotated')) {
                dojo.removeClass(story, 'enlargedrotated');
                dojo.addClass(story, 'enlarged');

                if ( (y+ this.getSizeFromStory(story)[1] -1) > 2 ) {
                    y = 2-this.getSizeFromStory(story)[1]+1;
                }
            } else {
                dojo.removeClass(story, 'enlarged');
                dojo.addClass(story, 'enlargedrotated');

                if ( (x+ this.getSizeFromStory(story)[0] -1) > 3 ) {
                    x = 3-this.getSizeFromStory(story)[0]+1;
                }
            }
            this.endPlaceStory(story,x,y);
        },

        confirmPlacement: function(story, evt){
            dojo.stopEvent(evt);
            if( ! this.checkAction( 'confirmPlacement' ) ){   return; }

            var parent  = dojo.query($(story))[0].parentNode;
            var x =  Number(parent.id.slice(14,15));
            var y = Number( parent.id.slice(16,17));

            var state =  dojo.hasClass(story, 'enlargedrotated') ? 1:0 ;
            var story = story.slice(6); 

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/placeStory.html", {story: story, x: x, y:y, state:state, lock : true}, 
                this, function(result) {}, function(is_error) {
            }); 
        },

        cancelPlacement: function(story, evt) {
            dojo.stopEvent(evt);
            if( ! this.checkAction( 'cancelPlacement' ) ){   return; }

            this.moveStoryBackToClaimedPanel(story, true, this.actualClaimedStoryPostion);
        },

        confirmFrontPage: function(evt) {
            dojo.stopEvent(evt);
            if( ! this.checkAction( 'confirmFrontPage' ) ){   return; }

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/confirmFrontPage.html", {lock : true}, 
                this, function(result) {}, function(is_error) {
            }); 
        },

        cancelFrontPage: function(evt) {
            dojo.stopEvent(evt);
            if( ! this.checkAction( 'cancel' ) ){   return; }

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/cancelFrontPage.html", {lock : true}, 
                this, function(result) {}, function(is_error) {
            }); 
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your pennypress.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'sendReporters', this, "notif_sendReporters" );
            dojo.subscribe( 'reassignReporter', this, "notif_reassignReporter" );
            dojo.subscribe( 'recallReporters', this, "notif_recallReporters" );
            dojo.subscribe( 'adjustNewsBeat', this, "notif_adjustNewsBeat" );

            dojo.subscribe( 'claimStories', this, "notif_claimStories" );
            this.notifqueue.setSynchronous( 'claimStories', 1000 );
            dojo.subscribe( 'scoopPlayer', this, "notif_scoopPlayer" );

            dojo.subscribe( 'placeStory', this, "notif_placeStory" );
            dojo.subscribe( 'startOver', this, "notif_startOver" );
            dojo.subscribe( 'scoreFrontPage', this, "notif_scoreFrontPage" );
            this.notifqueue.setSynchronous( 'scoreFrontPage', 3000 );
            dojo.subscribe( 'scoreFrontPagePositive', this, "notif_scoreFrontPagePositive" );
            dojo.subscribe( 'scoreFrontPageNegative', this, "notif_scoreFrontPageNegative" );
            dojo.subscribe( 'scoreFrontPageExclusive', this, "notif_scoreFrontPageExclusive" );
            dojo.subscribe( 'scoreFrontPageSum', this, "notif_scoreFrontPageSum" );
            dojo.subscribe( 'scoreFrontPageOverall', this, "notif_scoreFrontPageOverall" );
            dojo.subscribe('updateStoriesPosition', this, "notif_updateStoriesPosition");

            dojo.subscribe('newArticle', this, "notif_newArticle");
            this.notifqueue.setSynchronous( 'newArticle', 3000 );

            dojo.subscribe( 'bonusMarkerChange', this, "notif_bonusMarkerChange" );
            dojo.subscribe( 'newStoryToBeatTrack', this, "notif_newStoryToBeatTrack" );
            dojo.subscribe( 'newAdvertisment', this, "notif_newAdvertisment" );
            this.notifqueue.setSynchronous( 'newAdvertisment', 1000 );

            dojo.subscribe( 'logInfo', this, "notif_logInfo" );
            dojo.subscribe( 'delay', this, 'notif_logInfo' );
            this.notifqueue.setSynchronous( 'delay', 1000 );

            dojo.subscribe( 'finalEdition', this, 'notif_finalEdition' );
            this.notifqueue.setSynchronous( 'finalEdition', 800 );

            dojo.subscribe( 'finalScoring', this, 'notif_finalScoring' );
            dojo.subscribe( 'finalScoringSum', this, 'notif_finalScoringSum' );
            this.notifqueue.setSynchronous( 'finalScoringSum', 1000 );

            dojo.subscribe( 'strike', this, 'notif_strike' );

        },  

        notif_sendReporters: function(notif) {
            for(var i=0;i<notif.args.reporters.length;i++) {
                this.movePawnToStory(notif.args.reporters[i], 'story_'+notif.args.story);
            }
        },

        notif_reassignReporter: function(notif) {
           this.movePawnToStory('pawn_'+notif.args.pawn_id+'_'+notif.args.player_id,'story_'+notif.args.new_story, 'story_'+notif.args.old_story);
        },

        notif_recallReporters: function(notif) {
            for(var i=0;i<notif.args.meeples.length;i++) {
                this.movePawnHome('pawn_'+notif.args.meeples[i]+'_'+notif.args.player_id, notif.args.player_id);
            }
        },

        notif_adjustNewsBeat: function(notif) {
            this.moveArrowMarker(notif.args.name, notif.args.value);
        },

        notif_scoopPlayer: function(notif) {
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.x);

            for(var i=0;i<notif.args.meeples.length;i++) {
                this.movePawnHome('pawn_'+notif.args.meeples[i]+'_'+notif.args.player_id, notif.args.player_id);
            }
        },

        notif_claimStories: function(notif) {
            for(var i=0;i<notif.args.meeples.length;i++) {
                this.movePawnHome('pawn_'+notif.args.meeples[i]+'_'+notif.args.player_id, notif.args.player_id);
            }

            this.slidePanels(notif.args.x, notif.args.stories, notif.args.top_beat_stories_nbr, notif.args.arrow_values);
        },

        notif_placeStory: function(notif) {                             
            if( this.isCurrentPlayerActive() && typeof g_replayFrom == "undefined") {
                dojo.removeClass('story_'+notif.args.story, 'elevated');
                this.attachToNewParent( 'story_'+notif.args.story, 'playerboard_'+this.getActivePlayerId() );
    
                delete this.storyZones['story_'+notif.args.story];
                dojo.destroy('story_'+notif.args.story+'_zone');
                dojo.destroy('story_'+notif.args.story+'_zoneSelect');
                dojo.query('.circle').removeClass('open');
            } else {
                dojo.removeClass('story_'+notif.args.story, 'elevated');
                this.attachToNewParent( 'story_'+notif.args.story, 'playerboard_'+notif.args.player_id );
    
                delete this.storyZones['story_'+notif.args.story];
                dojo.destroy('story_'+notif.args.story+'_zone');
                dojo.destroy('story_'+notif.args.story+'_zoneSelect');

                this.moveStoryToTile('story_'+notif.args.story,notif.args.player_id,notif.args.x,notif.args.y,notif.args.state);
            }
        },

        notif_startOver: function(notif) {
            for(var i=0;i<notif.args.stories.length;i++) {
                this.moveStoryBackToClaimedPanel('story_'+notif.args.stories[i].n+"_"+notif.args.stories[i].t+"_"+notif.args.stories[i].s, false,notif.args.stories[i].old );
            }
        },

        notif_scoreFrontPage: function(notif) {
            for(var i=0;i<notif.args.stories_positive.length;i++) {
                var text = notif.args.stories_positive[i].score;
                if (notif.args.exclusive_story != null) {
                    if (notif.args.stories_positive[i].id == notif.args.exclusive_story.id) {
                        text += 'x2';
                    }
                }

                var div = "<div id= \"positive"+i+"\" class = \"scoreText\" style = \" position: abosolute;  \" ><h5>+"+text+"</h5></div>";
                // dojo.place( div, 'playersframe');
                dojo.place( div, 'playerbox_'+notif.args.player_id);

                dojo.style("positive"+i,'top',dojo.style("story_"+notif.args.stories_positive[i].name+"_"+notif.args.stories_positive[i].type+"_"+notif.args.stories_positive[i].stars,'top')+'px' );     // IE+EDGE hack
                dojo.style("positive"+i,'left',dojo.style("story_"+notif.args.stories_positive[i].name+"_"+notif.args.stories_positive[i].type+"_"+notif.args.stories_positive[i].stars,'left')+'px' );
                this.placeOnObject( "positive"+i, "story_"+notif.args.stories_positive[i].name+"_"+notif.args.stories_positive[i].type+"_"+notif.args.stories_positive[i].stars );

                var anim1 = dojo.fadeIn({
                    node: 'positive'+i,
                    duration: 1500
                });

                dojo.connect(anim1,'onEnd',  dojo.hitch(this,'fadeOutAndDestroy', 'positive'+i, 800,0 ) );

                if (i==0 && $('advertisment_'+notif.args.player_id)) {
                    dojo.connect(anim1,'onEnd',  dojo.hitch(this,'slideToObjectAndDestroy', 'advertisment_'+notif.args.player_id, 'overall_player_board_'+notif.args.player_id,600,0 ) );
                }

                var anim2 = this.moveStoryToPlayerPublished( notif.args.stories_positive[i].name,notif.args.stories_positive[i].type,notif.args.stories_positive[i].stars, notif.args.player_id );

                dojo.fx.chain([anim1,anim2]).play(); 
            }

            for(var i=0;i<notif.args.stories_negative.length;i++) {
                var text = notif.args.stories_negative[i].score;

                var div = "<div id= \"negative"+i+"\" class =  \"scoreText\"  ><h5>"+text+"</h5></div>"

                dojo.place( div, 'claimedPanel');
                dojo.style("negative"+i,'top',dojo.style("story_"+notif.args.stories_negative[i].name+"_"+notif.args.stories_negative[i].type+"_"+notif.args.stories_negative[i].stars,'top')+'px' );     // IE+EDGE hack
                dojo.style("negative"+i,'left',dojo.style("story_"+notif.args.stories_negative[i].name+"_"+notif.args.stories_negative[i].type+"_"+notif.args.stories_negative[i].stars,'left')+'px' );
                this.placeOnObject( "negative"+i, "story_"+notif.args.stories_negative[i].name+"_"+notif.args.stories_negative[i].type+"_"+notif.args.stories_negative[i].stars );

                var anim1 = dojo.fadeIn({
                    node: 'negative'+i,
                    duration: 1000
                });

                dojo.connect(anim1,'onEnd',  dojo.hitch(this,'fadeOutAndDestroy', 'negative'+i, 800,0 ) );

                var anim2 = this.moveStoryBackToDeck( notif.args.stories_negative[i].name,notif.args.stories_negative[i].type,notif.args.stories_negative[i].stars );

                dojo.fx.chain([anim1,anim2]).play(); 
                
                delete this.storyZones['story_'+notif.args.stories_negative[i].name+"_"+notif.args.stories_negative[i].type+"_"+notif.args.stories_negative[i].stars];
                dojo.destroy('story_'+notif.args.stories_negative[i].name+"_"+notif.args.stories_negative[i].type+"_"+notif.args.stories_negative[i].stars+'_zone');
                dojo.destroy('story_'+notif.args.stories_negative[i].name+"_"+notif.args.stories_negative[i].type+"_"+notif.args.stories_negative[i].stars+'_zoneSelect');
            }

            this.scoreCtrl[ notif.args.player_id].incValue( notif.args.page); 
        },

        notif_scoreFrontPagePositive : function(notif) {
            if (notif.args.final_edition == 0) {
                this.addPennyOnBoard( notif.args.player_id);
            }

            if (notif.args.final_edition == 1 && (Object.keys(this.gamedatas.players).length) < 4) {
                $(notif.args.player_id+'_pennyCount').innerText = parseInt(4);
            }
            
            if (notif.args.final_edition == 1 && (Object.keys(this.gamedatas.players).length) > 3) {
                this.addPennyOnBoard( notif.args.player_id);
            }

        },

        notif_scoreFrontPageNegative : function(notif) {

        },

        notif_scoreFrontPageExclusive : function(notif) {

        },

        notif_scoreFrontPageSum : function(notif) {
            this.slidePanelsBack();
        },

        notif_scoreFrontPageOverall: function(notif) {

        },

        notif_updateStoriesPosition: function(notif) {
            for(var i=0;i<notif.args.stories.length;i++) {
                this.moveStoryToNewPosition(notif.args.stories[i].name, notif.args.stories[i].type, notif.args.stories[i].stars, notif.args.stories[i].new_loc); 
            }
        },

        notif_newArticle: function(notif){                                            
            this.addArticleOnBoard(notif.args.id, notif.args.card_text, true);
        },

        notif_bonusMarkerChange: function(notif) {
            this.moveBonusTrackItem(notif.args.from, notif.args.from+notif.args.value, notif.args.name+"_bonustrack");
        },

        notif_newStoryToBeatTrack: function(notif) {
            this.moveStoryToBeatTrack(notif.args.name, notif.args.type, notif.args.stars, notif.args.position, false);
        },

        notif_newAdvertisment: function(notif){
            this.addAdvertismentOnBoard(notif.args.player_id,notif.args.x,notif.args.y);
        },

        notif_logInfo: function(notif) {

        },

        notif_finalEdition: function(notif) {
            // flip markers
            this.flipArrowmarkers();
            this.enableAllPlayerPanels();
            dojo.destroy('strike');
        },

        notif_finalScoring: function(notif) {
            this.scoreCtrl[ notif.args.player_id].incValue( notif.args.x);
        },

        notif_finalScoringSum: function(notif) {
            var playerSums = notif.args.scores.split("_");
            var categories = new Array('war','crime','politics','city','human');

            for(var i=0;i<playerSums.length-1;i++) {
                var number = i+2;
                var selector = '.finaltable tr:nth-child('+2+') td:nth-child('+number+')';
                dojo.query(selector)[0].innerText = this.scoreCtrl[playerSums[i].split(":")[0]].current_value;
                var player_sum = 0;

                for(var j=0;j<categories.length;j++) {
                    var row = j+3;
                    var player_sum = player_sum + parseInt(playerSums[i].split(":")[j+1]);
                    selector = '.finaltable tr:nth-child('+row+') td:nth-child('+number+')';

                    dojo.query(selector)[0].innerText = playerSums[i].split(":")[j+1];
                }

                selector = '.finaltable tr:nth-child('+8+') td:nth-child('+number+')';
                var value = parseInt(this.scoreCtrl[playerSums[i].split(":")[0]].current_value) + player_sum;
                dojo.query(selector)[0].innerText = value ;
            }

            dojo.query('.finaltable').addClass('open');
        },

        notif_strike: function(notif) {
            this.moveStrike(notif.args.new_strike_player_id);
        },

   });             
});
