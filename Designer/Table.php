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



class Gtk_MDB_Designer_Table {
    var $name;
    var $extends;
    var $fields = array();
    var $indexes = array();
    var $declaration;
    var $frame;
    var $x;
    var $y;
    var $table;
    var $height;
    function normalize() {
        //print_r($this->declaration[0]->field);
        foreach($this->declaration as $declaration) {
            if (isset($declaration->field)) {
                foreach ($declaration->field as $field) {
                    $this->fields[$field->name] = $field;
                }
                continue;
            }
            if (isset($declaration->index)) {
                foreach ($declaration->index as $index) {
                    $this->indexes[$index->name] = $index;
                }
                continue;
            }
        }
        //print_r($this->fields);
        unset($this->declaration);
        
    }
    
    
    
    function toXml() {
        if ($this->deleted) {
            return;
        }
        $export = array('name','x','y');
    
        $ret = "  <table>\n";
        foreach($export as $k) {
            if (!isset($this->$k)) {
                continue;
            }
            $ret .= "    <$k>{$this->$k}</$k>\n";
        }
        $ret .= "    <declaration>\n";
        foreach($this->fields as $k=>$v) {
            $ret .= $v->toXml();
        }
        foreach($this->indexes as $k=>$v) {
            $ret .= $v->toXml();
        }
        $ret .= "    </declaration>\n";
        $ret .= "  </table>\n";
        return $ret;
    }
    
    function testExport($db) {
        $ret ="CREATE TABLE {$this->name} (\n";
        foreach($this->fields as $field) {
            if ($row =$field->testExport($db)) {
                $ret .= "    ". $row. ",\n";
            }
        }
        $ret .= ");\n";
       return $ret;
    }
    
    
    
    
    
    /* ---------------------------------------------------------------------------------------------------- */
    /*            Gtk Specific methods                                                                      */
    /* ---------------------------------------------------------------------------------------------------- */
    
    
    
    
    var $row = 3;
    
    function buildWidgets(&$database,$x,$y) {
           $this->database = &$database;
        // create each row.
       
        $this->frame   = &new GtkNotebook;
        $this->frame->set_show_tabs(false);
                 // create table
        $this->table = &new GtkTable;
        
        $this->title = &new GtkButton($this->name);
        
        $this->title ->connect("event", array(&$this,'titleEvent'));
        $this->title ->show();
        $this->table->attach( $this->title,
                1,9 ,
                1,2,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        
        
        $label = &new GtkLabel('Table:');
        $label->show();
        $label->set_usize(50,20);
        $this->table->attach( $label,
                1,2 ,
                2,3,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
                
        $title = &new GtkEntry;
        $title->set_text($this->name);
        $title->set_usize(50,20);
        $title->connect('changed', array(&$this,'nameChanged'));
        $title->connect('leave-notify-event', array(&$this->database,'save'));

        $title->show();
        $this->table->attach( $title,
                2,4 ,
                2,3,
                GTK_FILL ,  // xdir
                GTK_SHRINK // ydir
        );
        $label = &new GtkLabel('inherits');
        $label->set_usize(50,20);
        $label->show();
        $this->table->attach( $label,
                4,7 ,
                2,3,
                GTK_SHRINK,   // xdir
                GTK_SHRINK // ydir
        );
        
        $title = &new GtkEntry;
        $title->set_text($this->extends);
        $title->set_usize(50,20);
        $title->connect('changed', array(&$this,'extendsChanged'));
        $title->connect('leave-notify-event', array(&$this->database,'save'));

        $title->show();
        $this->table->attach( $title,
                7,8 ,
                2,3,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        
        
        
        
        
        
        
        
        
        $delete = &new GtkButton('X');
        $delete->connect('pressed', array(&$this,'deleteTable'));
        $delete->show();
        $this->table->attach( $delete,
                8,9 ,
                2,3,
                GTK_FILL,   // xdir
                GTK_SHRINK // ydir
        );
        
        // header !!
        $row =3;
        foreach(array('Name','type','len','N','I','++','default') as $k=>$v) {
            $child= &new GtkLabel($v);
            //$child->set_usize(50,20);
            $this->addCell($child,$k+1,$row);
            $child->show();
        }
        
        $this->rows = 4;
        // the rows.
        foreach(array_keys($this->fields) as $i=>$name) {
            $this->fields[$name]->buildWidgets($this,$i+4);
            $this->rows++;
        }
        
        
        
        $add = &new GtkButton('Add Row');
        $add->connect('pressed', array(&$this,'addRow'));
        $add->show();
        $this->table->attach( $add,
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
        $this->database->expand($maxX,$maxY);
        while (gtk::events_pending()) gtk::main_iteration();
        
        
    }
 
    
    
    function addCell(&$child,$x,$y,$xfill = GTK_SHRINK) {
        $this->table->attach( $child,
                $x,$x+1,  
                $y,$y+1,
                $xfill,     // xdir
                GTK_SHRINK // ydir
        );
    }
    
    
    
    var $tableMovePos  = false;
    var $tableMoveFrameStart = false;
    function titleEvent($button,$event) {
        
         
        
        $w = $this->database->layout->window;
       
        switch($event->type) {
            case 4: // press
                
                $w->set_cursor($this->database->pointers[GDK_HAND2]);
                $this->tableMovePos         = $w->pointer;
                $this->tableMoveFrameStart  = $this;
                
                gtk::timeout_add(5,array(&$this,'tableMove'));
                
                
                break;
            case 7:
                $w->set_cursor($this->database->pointers[GDK_ARROW]);
                $this->tableMovePos        = false;
                $this->tableMoveFrameStart = false;
                $this->database->dirty = true;
                $this->database->save();
                $this->database->expand($this->x + 300,$this->y+$this->height);
                //$this->tableMoveFrame      = false;
                break;
         
                
            default:
             
                //$pos = $event;
                break;
        }
            
        return false;
    
    }
    
    function tableMove() {
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
       
        
        $this->x  = $this->tableMoveFrameStart->x + $xdiff;
        $this->y  =  $this->tableMoveFrameStart->y + $ydiff;
        if ($this->x < 0 ) { 
            $this->x = 0;
        }
        if ($this->y < 0 ) { 
            $this->y = 0;
        }
        
        // grid :)
        $this->x = floor($this->x/10) * 10;
        $this->y = floor($this->y/10) * 10;
        
        $this->database->layout->move($this->frame, $this->x, $this->y);
        
        while (gtk::events_pending()) gtk::main_iteration();
        
        
        return true;
    }
    
    
    function nameChanged($object) {
        $child = $this->title->child;
        $child->set_text($object->get_text());
        $this->name = $object->get_text();
        $this->database->dirty = true;
    }
    var $deleted = false;
    function deleteTable() {
        $this->frame->destroy();
        $this->deleted = true;
    }
    
    function destroy() {
        $this->database->layout->remove($this->frame);
        $this->frame->destroy();
    }
        
    function addRow($button) {
        $button->hide();
        $this->table->remove($button);
        $name = 'row'.$this->rows;
        $this->fields[$name]  = new Gtk_MDB_Designer_Column;
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
    
  
}
?>