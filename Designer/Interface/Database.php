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
// 
//
//  The Gtk part of the database layer
//

require_once 'Gtk/MDB/Designer/Database.php';
require_once 'Gtk/MDB/Designer/Interface/Table.php';

class Gtk_MDB_Designer_Interface_Database extends Gtk_MDB_Designer_Database {

    var $designer;      // (Gtk_MDB_Designer)
    var $layout;        // alias to (GtkLayout) widget
    var $glade;         // alias to (GtkGlade) from interface
    
    var $maxY = 500;    // maximum size of layout widget 
    var $maxX = 10;       //
    
    /**
    * build the widgets that make up a database view.. - add the connections
    * 
    * @param  object Gtk_MDB_Designer $designer the interface object.
    * @access   public
    */
    
    function buildWidgets(&$designer) {
        $this->designer = &$designer;
        $this->layout   = &$designer->layout;
        $this->glade    = &$designer->glade;
        // reset size..
        //$this->setWidgetStyle( $this->layout,'#FF0000','#FF0000');
        
        $this->layout->set_size($this->maxX,$this->maxY);
        
        $menu = $this->glade->get_widget('menubar');
        $menu->set_sensitive(false);
        
        $database = $this->glade->get_widget('databaseName');
        $database->connect('changed',array(&$this,'callbackNameChanged'));
        
        $database->set_text($this->name);
        $database->connect('leave-notify-event', array(&$this,'save'));
        //?? right place?
        $this->designer->setTitle();
        
        foreach (array_keys($this->tables) as $name) {
           $this->tables[$name]->buildWidgets($this,$this->maxX,20);
           
        }
            
        $menu->set_sensitive(true);
        // create each row.
        $this->designer->loadDrawingArea();
    }
    
    
    
     /**
    * callback for any change in widget so that the layout is expanded to fit..
    * 
    * @access   public
    */ 
    function grow($x,$y) {
        if ($x < $this->maxX && $y <$this->maxY) {
            return;
        }
        if ($x > $this->maxX) {
            $this->maxX = $x;
        }
        if ($y > $this->maxY) {
            $this->maxY = $y;
        }
        //if ($this->pixmap) {
        //    $this->layout->remove($this->pixmap);
            //$this->pixmap->destroy();
            
        //}
        $ww = $this->layout->window;
        
        //print_r(array($ww->width,$ww->height));
        //print_r(array($this->maxX,$this->maxY));
        
        $maxX = ($this->maxX > $ww->width) ? $this->maxX : $ww->width;
        $maxY = ($this->maxY > $ww->height) ? $this->maxY : $ww->height;
        
        
        if ($this->designer->pixmap) {
            $this->designer->pixmap = new GdkPixmap(
                    $this->designer->drawingArea->window,
                    $maxX ,$maxY,
                    -1);
                    
            gdk::draw_rectangle($this->designer->pixmap, 
                $this->designer->drawingArea->style->white_gc,
                true, 0, 0,
                $maxX ,$maxY);
                
            // at this point you have to hook in the call to redraw the connectors.
            
            
            
            $this->designer->drawingArea->size($maxX,$maxY);
        }
        //$this->drawingArea->hide();
        //$this->drawingArea->show();
        
        
        $this->layout->set_size($maxX,$maxY);
    }
    
    /**
    * destroy all the widgets (either for new or loading..
    * 
    * @access   public
    */ 
    function destroy() {
        foreach (array_keys($this->tables) as $name) {
           $this->tables[$name]->destroy();
           unset($this->tables[$name]);
        }
    }
    /**
    * expand  // show full details
    * 
    * @access   public
    */ 
    function expand() {
        foreach (array_keys($this->tables) as $name) {
           $this->tables[$name]->expand();
        }
    }   /**
    * shrink // show names only
    * 
    * @access   public
    */ 
    function shrink() {
        foreach (array_keys($this->tables) as $name) {
           $this->tables[$name]->shrink();
        }
    }
   /**
    * create a new table
    * 
    * @access   public
    */ 
    
    function newTable() {
        $name = 'newtable'.(count($this->tables) +1);
        $this->tables[$name] = new Gtk_MDB_Designer_Interface_Table;
        $this->tables[$name]->name = $name;
        $this->tables[$name]->buildWidgets($this,20,20);
    }
    /**
    * override save to call back to parent if no name is set.
    * 
    * @access   public
    */ 
    
    function save($extension='.tmp') {
        if ($extension=='' && !isset($this->file)) {
            $this->designer->showSaveAsDialog();
            return;
        }
        parent::save($extension);
    }
    
    /**
    * call back for database name change
    * 
    * @access   public
    */
    
    function callbackNameChanged($object) {
        if ($object->get_text() == $this->name) {
            return;
        }
        
        $this->name = $object->get_text();
        $this->designer->setTitle();
        $this->dirty = true;
    }
    /**
    * set the style of a widget
    *
    * @access   public
    */
    
    
    function setWidgetStyle(&$widget,$fgcolor='',$bgcolor='',$copy=false) {
        if ($copy) {
            $oldstyle = $widget->get_style();
            $newstyle = $oldstyle->copy();
        } else {
            $newstyle = &new GtkStyle();
        }
        if ($fgcolor) { // set foreground color
            $fg = &new GdkColor($fgcolor);
            $newstyle->fg[GTK_STATE_PRELIGHT] = $fg;
            $newstyle->fg[GTK_STATE_NORMAL] = $fg;
            $newstyle->fg[GTK_STATE_ACTIVE] = $fg;
            $newstyle->fg[GTK_STATE_SELECTED] = $fg;
            $newstyle->fg[GTK_STATE_INSENSITIVE] = $fg;
            //$newstyle->bg_pixmap=NULL;
        }
        if ($bgcolor) { // set background color
            $bg = &new GdkColor($bgcolor);
            $newstyle->bg[GTK_STATE_PRELIGHT] = $bg;
            $newstyle->bg[GTK_STATE_NORMAL] = $bg;
            $newstyle->bg[GTK_STATE_ACTIVE] = $bg;
            $newstyle->bg[GTK_STATE_SELECTED] = $bg;
            $newstyle->bg[GTK_STATE_INSENSITIVE] = $bg;
            //$newstyle->bg_pixmap=NULL;
        }
        $widget->set_style($newstyle);
    }
}
?>