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
        $this->loadDrawingArea();
    }
    
    function loadDrawingArea() {
        $this->drawingArea  = &new GtkDrawingArea();
        
        
        
        //print_r(array($this->maxX,$this->maxY));
        $this->drawingArea->size($this->maxX,$this->maxY);
        $this->layout->put( $this->drawingArea ,0,0);
        $this->drawingArea->show();
        $this->drawingArea->connect("configure_event",        array(&$this,"drawingAreaCallbackConfigure"));
        $this->drawingArea->connect("expose_event",           array(&$this,"drawingAreaCallbackExpose"));
    
    }
    
    
    var $pixmap = false;
    var $drawingArea;
    
    // the callback to create the pixmap & start building
    function drawingAreaCallbackConfigure($widget, $event) { 
        //echo "da configure\n";
        if (@$this->pixmap) {
            return true;
        }
        $this->pixmap = new GdkPixmap($this->drawingArea->window,
                $this->maxX ,$this->maxY,
                -1);

        gdk::draw_rectangle($this->pixmap, 
            $this->drawingArea->style->white_gc,
            true, 0, 0,
            $this->maxX ,$this->maxY);
        // flash cursor GC
        $window = $this->drawingArea->window;
        $cmap = $this->drawingArea->get_colormap();
        $this->_cursorGC = $window->new_gc();
        $this->_cursorGC->background =  $cmap->alloc("#000000");
        $this->_cursorGC->function   =  GDK_INVERT;    
        
        return true;
    }
    // standard callback to repaint a drawing area
    
    function drawingAreaCallbackExpose($widget,$event) { 
        //echo "da expose\n";
        if (!$this->pixmap) {
            return;
        }
        /*
        I THINK THIS IS A WINDOWS FUDGE FIX
        if (!$this->_flag_rebuild  && ($this->layout->allocation->width > 400) && ($this->_area_x != $this->layout->allocation->width )) {

            if (  abs($this->_area_x - $this->layout->allocation->width) > 15) {
                $this->_new_area_x = $this->layout->allocation->width ;

                gtk::timeout_add(500, array(&$this,'_ChangeSize'), $this->layout->allocation->width);
            }

        }
        */
        gdk::draw_pixmap($this->drawingArea->window,
            $widget->style->fg_gc[$widget->state],
            $this->pixmap,
            $event->area->x, $event->area->y,
            $event->area->x, $event->area->y,
            $event->area->width, $event->area->height);
        return false;
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
        $this->pixmap = new GdkPixmap(
                $this->drawingArea->window,
                $this->maxX ,$this->maxY,
                -1);
                
         gdk::draw_rectangle($this->pixmap, 
            $this->drawingArea->style->white_gc,
            true, 0, 0,
            $this->maxX ,$this->maxY);
            
        $this->drawingArea->size($this->maxX,$this->maxY);
        //$this->drawingArea->hide();
        //$this->drawingArea->show();
        
        
        $this->layout->set_size($this->maxX,$this->maxY);
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