<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors:  Alan Knowles <alan@akbkhome.com>                           |
// +----------------------------------------------------------------------+
//
// Based loosely on the MessageBox script by Christian Weiske cweiske@cweiske.de
//
//
//
// $Id$
//
//  Simple Generic Message Box display tool
//

/**
* Gtk MessageBox - simple pop up window tool.
*
* THIS IS INTENDED TO BE A PRECURSOR TO GTK_MESSAGEBOX WHEN IT WORKS 
*
* @abstract simple popup message window.
*
* for the time being look at the options list for details on how to do stuff with it..
*
* $mbox = new Gtk_MDB_Designer_MessageBox($options);
* $result = $mbox->display();
* $mbox->options['icon'] = 'question.xpm'
* $result = $mbox->display();
*
* @version    $Id$
*/
class Gtk_MDB_Designer_MessageBox {
        
    /**
    * Options - list of defaults.
    *
    * @var array
    * @access public 
    */
    var $options = array(
        'resourceDir' => '',                // directory for glade and icons (Default file/MessageBox
        'gladeFile' => 'messagebox.glade',  // default glade file.. (you can use your own.)
        'window'    => 'title goes here',   // the title for the window.
        'ok'        => 'OK',                // text on the OK button (or false)
        'cancel'    => 'cancel',            // text on the 'cancel' button (or false)
        'apply'     => false,               // text on the apply button (or false to hide)
        'message'   => 'Message Goes Here', // the text message
        'default'   => 'ok',                // the default button
        'icon'      => 'exclamation.xpm'    // icon  = exclamation.xpm|asterisk.xpm|cross.xpm|question.xpm
    );
        
        
    /**
    * The GladeXML object
    *
    * @var GladeXML 
    * @access public
    */
    var $glade;
    /**
    * What was pressed (also returned from display()
    *
    * @var string button pressed to close
    * @access public
    */
    var $result = '';
    
    
    /**
    * Constructor 
    *
    * Sets up options and makes sure gtk extension is loaded.
    * 
    * @param   array $options    see the options list.
    * @access   public
    */
    function Gtk_MDB_Designer_MessageBox($options= array()) {
    
        if (!extension_loaded('php-gtk')) {
            dl('php_gtk.' . PHP_SHLIB_SUFFIX);
        }
        
    
    
        foreach ($options as $k=>$v) {
            $this->options[$k] = $v;
        }
        if (!$this->options['resourceDir']) {
            $this->options['resourceDir'] = dirname(__FILE__).'/MessageBox';
        }
    }
    /**
    * display the dialog = and return what was pressed.
    *
    * @access   public
    */
    
    function display() 
    {
        $this->glade = &new GladeXML($this->options['resourceDir'] . '/'.$this->options['gladeFile']);
        
        if (!$this->glade) {
            echo 'unable to load glade';
            return;
        }
            
        
        foreach($this->options as $k=>$v) {
            $w = $this->glade->get_widget($k);
            $c = get_class($w);
            //echo "$c\n";
            if (!$w) {  
                continue;
            }
            if ($v === false) {
                $w->hide();
            }
            if (!is_string($v)) {
                continue;
            }
         
            switch ($c) {
                case 'GtkDialog':
                    $w->set_title($v);
                    $w->connect("delete-event"  , array( &$this, "callbackDelete")); 
                   
                    break;
                    
                case 'GtkLabel':
                    $w->set_text($v);
                    break;
                case 'GtkButton':
                    $c = $w->child;
                    $c->set_text($v);                    
                    $w->connect('pressed', array(&$this,'callbackClose'),$k);
                    break;
            }
        }
        $w = $this->glade->get_widget('window');
        $w->show();
        
        list($pixmap, $mask)= Gdk::pixmap_create_from_xpm( $w->window, NULL, $this->options['resourceDir'].'/'.$this->options['icon'] );
        if ($pixmap) {
            $pxmIcon = &new GtkPixmap($pixmap, $mask);
        
           
            $pxmIcon->show();
            $w = $this->glade->get_widget('pixmapHolder');
            $w->add($pxmIcon);
        } else {
            // this is debugging - and only really happens if the setup has gone wrong.
            echo "Warning: unable to locate icon: ". $this->options['resourceDir'].'/'.$this->options['icon'] . "\n";
        }
        $w = $this->glade->get_widget('window');
       

        $w = $this->glade->get_widget($this->options['default']);
        if ($w) {
            $w->set_flags(GTK_CAN_DEFAULT);
        }
        $w = $this->glade->get_widget('window');
        $w->show();
        // extra button array?
        gtk::main();
        return $this->result;
    }
    /**
    * callback for pressing the X on the window.
    *
    * @access   public
    */
    function callbackDelele() 
    {
        return true;
    }
    /**
    * callback for closing the window.
    *
    * @access   public
    */
    
    function callbackClose($widget,$value) 
    {
        $this->result = $value;
        $w = $this->glade->get_widget('window');
        $w->hide();
        $w->destroy();
        $this->glade = false;
        gtk::main_quit();
        
    }

}    
?>