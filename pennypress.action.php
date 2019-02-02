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
 * pennypress.action.php
 *
 * PennyPress main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/pennypress/pennypress/myAction.html", ...)
 *
 */
  
  
  class action_pennypress extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "pennypress_pennypress";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there

    // public function selectStory() {
    //     self::setAjaxMode();     
    //     $arg1 = self::getArg( "story", AT_alphanum, true );
    //     $this->game->selectStory( $arg1 );
    //     self::ajaxResponse( );
    // }

    // public function sendReporters() {
    //     self::setAjaxMode();     
    //     $arg1 = self::getArg( "reporters", AT_posint, true );
    //     $this->game->sendReporters( $arg1 );
    //     self::ajaxResponse();
    // }

    public function sendReportersFast() {
        self::setAjaxMode();     
        $arg1 = self::getArg( "reporters", AT_posint, true );
        $arg2 = self::getArg( "story", AT_alphanum, true );
        $this->game->sendReportersFast( $arg1,$arg2 );
        self::ajaxResponse();
    }

    public function cancelPlayerTurn() {
        self::setAjaxMode();     
        $this->game->cancelPlayerTurn();
        self::ajaxResponse();
    }

    public function selectReporter() {
        self::setAjaxMode();     
        $arg1 = self::getArg( "reporter", AT_posint, true );
        $this->game->selectReporter($arg1);
        self::ajaxResponse();
    }

    public function selectMoreReporters() {
        self::setAjaxMode();     
        $arg1 = self::getArg( "reporters", AT_numberlist, true  );

        if( substr( $arg1, -1 ) == ',' ) {
            $arg1 = substr( $arg1, 0, -1 );
        }
        if( $arg1 == '' ) {
            $arg = array();
        } else {
            $arg = explode( ',', $arg1 );
        }

        $this->game->selectMoreReporters( $arg );
        self::ajaxResponse( );
    }

    public function reassignReporter() {
        self::setAjaxMode();     
        $arg1 = self::getArg( "story", AT_alphanum, true );
        $this->game->reassignReporter( $arg1 );
        self::ajaxResponse( );
    }

    public function recallReporters() {
        self::setAjaxMode();     
        $arg1 = self::getArg( "meeples", AT_numberlist, true );

        if( substr( $arg1, -1 ) == ',' ) {
            $arg1 = substr( $arg1, 0, -1 );
        }
        if( $arg1 == '' ) {
            $arg = array();
        } else {
            $arg = explode( ',', $arg1 );
        }

        $this->game->recallReporters( $arg );
        self::ajaxResponse( );
    }

    public function goToPress() {
        self::setAjaxMode();     
        $this->game->goToPress();
        self::ajaxResponse();
    }

    public function placeStory() {
        self::setAjaxMode();     
        $story = self::getArg( "story", AT_alphanum, true );
        $x = self::getArg( "x", AT_posint, true );
        $y = self::getArg( "y", AT_posint, true );
        $state = self::getArg( "state", AT_posint, true );
        $this->game->placeStory( $story, $x, $y, $state );
        self::ajaxResponse( );
    }

    public function confirmFrontPage() {
        self::setAjaxMode();     
        $this->game->confirmFrontPage();
        self::ajaxResponse();
    }

    public function cancelFrontPage() {
        self::setAjaxMode();     
        $this->game->cancelFrontPage();
        self::ajaxResponse();
    }


  }
  

