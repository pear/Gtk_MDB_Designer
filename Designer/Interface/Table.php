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
// $Id$
//
//  The table dataobject
//
require_once 'Gtk/MDB/Designer/Table.php';
require_once 'Gtk/MDB/Designer/Interface/Column.php';

class Gtk_MDB_Designer_Interface_Table extends Gtk_MDB_Designer_Table {
    
    var $tableMovePos  = false;         // start position. (GtkPointer) pos when mouse is down.
    var $tableMoveFrameStart = false;   // copy of it's self when moving.
    var $table;                         // the (GtkTable)
    var $frame;                         // the (GtkFrame) holder.
    var $height;                        // the height of an object
    var $addRow;                        // the addrow button (GtkButton)
    var $deleteMenu;                    // the delete popup menu
    var $deleteMenuTable;               // the delete popup menu - table line
    
    var $largeWidgets = array();        // array of widgets that are hidden on zoom out.
    
    var $extras = array('x','y','inherits');      // extra items to export 
    var $inherits;              // database that it inherits (postgres and a few other only)
    var $x;                     // x location
    var $y;                     // y location
    var $scale = 1;
    
    var $row = 3;               // number of rows.
    
    /**
    * overlay the normalized indexes into the table defs..
    * 
    * @access   public
    */    
    
    function normalize() {
        parent::normalize();
        // map indexes into table..
        foreach ($this->indexes as $k=>$v) {
            $this->fields[$v->field_name]->isIndex = $k;
            $this->fields[$v->field_name]->isUnique = @$v->unique;
        }
    }
    
    
    /**
    * build the widgets that make up a table view.. -  the dragable bit.
    * 
    *
    * @param object Gtk_MDB_Designer_Interface_Database $database object
    * @param int $x default insert to 
    * @param int $y default insert to 
    * @access   public
    */
    function buildWidgets(&$database,$x,$y) {
         $this->database = &$database;
        // create each row.
       
        $this->frame   = &new GtkNotebook;
        $this->frame->set_show_tabs(false);
                 // create table
        $this->table = &new GtkTable;
        
        $this->title = &new GtkButton($this->name);
        Gtk_MDB_Designer_Interface_Database::setWidgetStyle($this->title, '#FFFFFF','#000000');
        $child =$this->title->child;
        Gtk_MDB_Designer_Interface_Database::setWidgetStyle($child, '#FFFFFF','#000000');
        
        $this->title ->connect("event", array(&$this,'callbackTitleEvent'));
        $this->title->set_usize(20,20);
        $this->title ->show();
        $this->table->attach( $this->title,
                1,9 ,
                1,2,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        
        
        
        $this->largeWidgets['deleteTable']= &new GtkButton('X');
        $this->largeWidgets['deleteTable']->set_usize(20,20);
        $this->largeWidgets['deleteTable']->connect('button-press-event', array(&$this,'callbackDeletePopup'));
        $this->largeWidgets['deleteTable']->show();
        $this->table->attach( $this->largeWidgets['deleteTable'],
                9,10 ,
                1,2,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        Gtk_MDB_Designer_Interface_Database::setWidgetStyle($this->largeWidgets['deleteTable'], '#FFFFFF','#FF0000');
        $child =$this->largeWidgets['deleteTable']->child;
        Gtk_MDB_Designer_Interface_Database::setWidgetStyle($child, '#FFFFFF','#FF0000');
       
        
        $this->largeWidgets['tableLabel'] = &new GtkLabel('Table:');
        $this->largeWidgets['tableLabel']->set_usize(50,20);
        $this->largeWidgets['tableLabel']->show();
        
        $this->table->attach( $this->largeWidgets['tableLabel'],
                1,2 ,
                2,3,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
                
        $this->largeWidgets['tableEntry']= &new GtkEntry;
        $this->largeWidgets['tableEntry']->set_text($this->name);
        $this->largeWidgets['tableEntry']->set_usize(50,20);
        $this->largeWidgets['tableEntry']->connect('changed', array(&$this,'callbackNameChanged'));
        $this->largeWidgets['tableEntry']->connect('leave-notify-event', array(&$this->database,'save'));

        $this->largeWidgets['tableEntry']->show();
        $this->table->attach($this->largeWidgets['tableEntry'],
                2,4 ,
                2,3,
                GTK_FILL ,  // xdir
                GTK_SHRINK // ydir
        );
        
        $this->largeWidgets['inheritsLabel']= &new GtkLabel('inherits');
        $this->largeWidgets['inheritsLabel']->set_usize(50,20);
        $this->largeWidgets['inheritsLabel']->show();
        $this->table->attach( $this->largeWidgets['inheritsLabel'],
                4,7 ,
                2,3,
                GTK_SHRINK,   // xdir
                GTK_SHRINK // ydir
        );
        
        $this->largeWidgets['inheritsEntry']= &new GtkEntry;
        $this->largeWidgets['inheritsEntry']->set_text($this->inherits);
        $this->largeWidgets['inheritsEntry']->set_usize(50,20);
        $this->largeWidgets['inheritsEntry']->connect('changed', array(&$this,'callbackExtendsChanged'));
        $this->largeWidgets['inheritsEntry']->connect('leave-notify-event', array(&$this->database,'save'));

        $this->largeWidgets['inheritsEntry']->show();
        $this->table->attach( $this->largeWidgets['inheritsEntry'],
                7,10 ,
                2,3,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        
        // header !!
        $row =3;
        foreach(array('Name','type','len','N','I','++','U','default') as $k=>$v) {
            $this->largeWidgets['header'.$k]= &new GtkLabel($v);
            //$child->set_usize(50,20);
            $this->addCell($this->largeWidgets['header'.$k],$k+1,$row,1);
            $this->largeWidgets['header'.$k]->show();
        }
        
        /* the delete menu */
        
        $this->deleteMenu      = &new GtkMenu();
        $this->deleteMenu->show();
        $this->deleteMenuTable = &new GtkMenuItem("Delete ".$this->name);
        $this->deleteMenuTable->show();
        $this->deleteMenuTable->connect('activate',array(&$this,'callbackDeleteTable'));
        $this->deleteMenu->add($this->deleteMenuTable);
        $sep = &new GtkMenuItem();
        $sep->show();
        $this->deleteMenu->add($sep);
        
         
        
        
        
        $this->rows = 4;
        // the rows.
        foreach(array_keys($this->fields) as $i=>$name) {
            $this->fields[$name]->buildWidgets($this,$i+4);
            $this->rows++;
        }
        
        
        
        $this->addRow = &new GtkButton('Add Row');
        $this->addRow->connect('pressed', array(&$this,'callbackAddRow'));
        $this->addRow->show();
        $this->table->attach($this->addRow,
                1,2 ,
                $this->rows+5,$this->rows+6,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        
        
        
        
        $this->frame->add($this->table);
        $this->table->show();
        $this->frame->show();
        
        
        
        
        
        
        
     
        
        if (!isset($this->x)  && $this->x < 1) {
            $this->x = $x;
        }
        if (!isset($this->y)  && $this->y < 1) {
            $this->y = $y;
        }
        
        // add it to the layout.
        $this->height = (20 * (count($this->fields)+3));
        
        $maxY = $this->y + $this->height;
        $maxX = $this->x + 300;
        $this->database->layout->put($this->frame,$this->x,$this->y); 
        
        
        
        
       
        
    

        
        
        
        
        
        $this->database->grow($maxX,$maxY);
        while (gtk::events_pending()) gtk::main_iteration();
        
        
    }
   /**
    * add a cell to the table
    * 
    *
    * @param object GtkObject $child object to add
    * @param int $x position
    * @param int $y position
    * @param int $xfill - see GtkTable for details.
    * @access   public
    */
    
    
    function addCell(&$child,$x,$y,$span, $xfill = GTK_SHRINK) {
        $this->table->attach( $child,
                $x,$x+$span,  
                $y,$y+1,
                $xfill,     // xdir
                GTK_SHRINK // ydir
        );
    }
    /**
    * destroy an widget.
    *
    * @access   public
    */
 
    function destroy() {
        $this->database->layout->remove($this->frame);
        $this->frame->destroy();
        
    }
     
    /**
    * callback for press on the title etc.
    * 
    *
    * @param object GtkButton $button  - widget
    * @param object GtkEvent $event - the event object
    * @access   public
    */
   
    function callbackTitleEvent($button,$event) {
        
        $w = $this->database->layout->window;
       
        switch($event->type) {
            case 4: // press
                
                $w->set_cursor($this->database->designer->pointers[GDK_HAND2]);
                $this->tableMovePos         = $w->pointer;
                $this->tableMoveFrameStart  = $this;
                
                gtk::timeout_add(5,array(&$this,'callbackTableMove'));
                
                
                break;
            case 7:
                $w->set_cursor($this->database->designer->pointers[GDK_ARROW]);
                $this->tableMovePos        = false;
                $this->tableMoveFrameStart = false;
                $this->database->dirty = true;
                $this->database->save();
                $this->database->grow($this->x + 300,$this->y+$this->height);
                //$this->tableMoveFrame      = false;
                break;
         
                
            default:
             
                //$pos = $event;
                break;
        }
            
        return false;
    
    }
   
    /**
    * callback on timeout while button is being pressed.for press on the title etc.
    *
    * @access   public
    */
    function callbackTableMove() {
        if ($this->tableMovePos == false) {
            return false;
        }
        
        //print_r(array($w->pointer, $w->pointer_state));
        $w = $this->database->layout->window;
      
        // echo $event->type ."\n";
        $xdiff = $w->pointer[0] - $this->tableMovePos[0];
        $ydiff = $w->pointer[1] - $this->tableMovePos[1];
         //print_r(array($xdiff,$ydiff));
        
         //print_r($event);
       
        
        $this->x  = (($this->tableMoveFrameStart->x * $this->scale)+ $xdiff) / $this->scale;
        $this->y  = (($this->tableMoveFrameStart->y)+ $ydiff);
        if ($this->x < 0 ) { 
            $this->x = 0;
        }
        if ($this->y < 0 ) { 
            $this->y = 0;
        }
        
        // grid :)
        $this->x = floor($this->x/(10 * $this->scale)) * (10 * $this->scale);
        $this->y = floor($this->y/10) * 10;
        
        $this->database->layout->move($this->frame, $this->x * $this->scale, $this->y);
        
        while (gtk::events_pending()) gtk::main_iteration();
        
        
        return true;
    }
    
    /**
    * callback on the table name changed
    *
    * @access   public
    */
    function callbackNameChanged($object) {
        $child = $this->title->child;
        $child->set_text($object->get_text());
        $this->name = $object->get_text();
        $this->database->dirty = true;
    }
     /**
    * callback on the inherits name changed
    *
    * @access   public
    */
    function callbackExtendsChanged($object) {
        $this->inherits = $object->get_text();
        $this->database->dirty = true;
    }

 
    /**
    * callback on the delete table menu item pressed
    *
    * @access   public
    */
    
    function callbackDeleteTable() {
        $this->frame->destroy();
        $this->deleted = true;
    }
     /**
    * callback on add a row
    *
    * @access   public
    */
    function callbackAddRow($button) {
        $button->hide();
        $this->table->remove($button);
        $name = 'row'.$this->rows;
        $this->fields[$name]  = new Gtk_MDB_Designer_Interface_Column;
        $this->fields[$name]->type = 'text';
        $this->fields[$name]->buildWidgets($this,$this->rows);
        $this->rows++;
         
        $this->table->attach( $button,
                1,2 ,
                $this->rows,$this->rows+1,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        $button->show();
        
       
    }
    /**
    * show a shrunk version.
    *
    * @access   public
    */

    function shrink() {
        foreach (array_keys($this->largeWidgets) as $w) {
            $this->largeWidgets[$w]->hide();
        }
        foreach(array_keys($this->fields) as $i=>$name) {
            $this->fields[$name]->shrink();
        }
        $this->scale = 0.5;
        //$this->title->hide();
        //$this->table->remove($this->title);
        //$this->table->attach( $this->title,
        //        1,2,
        //        1,2,
        //        GTK_FILL,   // xdir
        //        GTK_SHRINK // ydir
        //);
        $this->addRow->set_sensitive(false);
        $this->database->layout->move($this->frame, $this->x * $this->scale, $this->y );
        
        
    }
    /**
    * show a expanded version.
    *
    * @access   public
    */
    function expand() {
        foreach (array_keys($this->largeWidgets) as $w) {
            $this->largeWidgets[$w]->show();
        }
        foreach(array_keys($this->fields) as $name) {
            $this->fields[$name]->expand();
        }
        $this->scale = 1;
        //$this->title->hide();
        //$this->table->remove($this->title);
        //$this->table->attach( $this->title,
        //        1,9,
        //        1,2,
        //        GTK_FILL,   // xdir
        //        GTK_SHRINK // ydir
        //);
        //$this->frame->hide();
        //$this->frame->show();
        $this->addRow->set_sensitive(true);
        $this->database->layout->move($this->frame, $this->x * $this->scale, $this->y );
    }
     /**
    * callback on the delete X presed
    *
    * @access   public
    */
    
    function callbackDeletePopup($entry,$event) {
     
        
        $child = $this->deleteMenuTable->child;
        $child->set_text('Delete '. $this->name);
        foreach(array_keys($this->fields) as $name) {
            $this->fields[$name]->callbackDeletePopup();
        }
        $this->deleteMenu->popup(null, null, null, (int) $event->button, (int) $event->time);
   
    
    }

    
}
?>