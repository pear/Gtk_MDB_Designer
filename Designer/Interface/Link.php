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
//  The Gtk part of the linking..
//

$GLOBALS['_Gtk_MDB_Designer_Interface_Link'] = array();


class Gtk_MDB_Designer_Interface_Link {
    
    var $from; // Gtk_MDB_Designer_Interface_Column
    var $to;   // Gtk_MDB_Designer_Interface_Column
    
      
   
    
    
    
    function matches(&$array) {
        if ($this->from->table->name != $array[0]->table->name) {
            return;
        }
        if ($this->to->table->name != $array[1]->table->name) {
            return;
        }
        if ($this->from->name != $array[0]->name) {
            return;
        }
        if ($this->to->name != $array[1]->name) {
            return;
        }
        return true;
    }
    var $fromPos;
    var $toPos;
    
    function drawLink() {
        // ask first for startpos
        // make up firstpos
        //require_once 'Gtk/VarDump.php';new Gtk_VarDump($this);
        $this->setGC();
        
        $base = array($this->from->table->x,$this->from->table->y);
        $toPos = $this->to->getStartPos($base);
        $this->fromPos = $this->from->getStartPos($toPos);
        $this->toPos = $this->to->getStartPos($this->fromPos);
        $this->drawDragLine($GLOBALS['_Gtk_MDB_Designer_Interface_Link']['gc']);
      
    
    
    }
    
    
    
    // refactor later? - move these to a shared location for column and links
    
    function setGC() {
        if (isset($GLOBALS['_Gtk_MDB_Designer_Interface_Link']['gc'])) {
            return;
        }
        //require_once 'Gtk/VarDump.php';new Gtk_VarDump($this);
        $database = &$this->from->table->database;
        $w = $database->layout->window; 
        $cmap = $database->layout->get_colormap();
        
        $GLOBALS['_Gtk_MDB_Designer_Interface_Link']['gc'] = $w->new_gc();
        
        $gc = &$GLOBALS['_Gtk_MDB_Designer_Interface_Link']['gc'];
        $gc->background =  $cmap->alloc("#000000");
        $gc->foreground =  $cmap->alloc("#FF0000");
        //$gc->function   =  GDK_INVERT;
        $gc->line_width  =  3;
        $gc->line_style = GDK_LINE_ON_OFF_DASH;
        $gc->cap_style = GDK_CAP_ROUND;
         
        
    }
        
    
    // similar to the one in column.
    
    function drawDragLine(&$gc) {
        list($x1,$y1) = $this->fromPos;
        list($x2,$y2) = $this->toPos;
        $database = &$this->from->table->database;
       
        //echo "DRAW LINE\n";
        $layout = &$database->layout;
        $xAdj = $layout->get_hadjustment();
        $yAdj = $layout->get_vadjustment();
        $da =  &$database->designer->drawingArea;
        $xf = -5;
        if ($x1 > $x2) {
            $xf = 5;
        }
        
        $pix = &$database->designer->pixmap;
        gdk::draw_line($pix,  $gc    ,
            $x1 + $xAdj->value, $y1 + $yAdj->value,
            $x2 + $xAdj->value+$xf, $y2 + $yAdj->value );
        
        $da->draw($this->makeRectangle(
               $x1 + $xAdj->value, $y1 + $yAdj->value,
               $x2 + $xAdj->value,  $y2 + $yAdj->value )
        );
       
        $x = $x2 + $xAdj->value;
        $y = $y2 + $yAdj->value; 
         
        $xf = -5;
        $s = "<";
        if ($x2 < $this->to->table->x) {
            $xf = -5;
            $s = ">";
        }
        
        // this needs to be tagged onto the column so 
        // that multiple links can be associated with one column.
        
        $button = &new GtkButton($s);
        $button->show();
        $layout->put($button,$x+$xf,$y-10);
        
        
        
        
        
        
     
    }    
    function &makeRectangle($x1,$y1,$x2,$y2) {
        //print_r(array($x1,$y1,$x2,$y2));
        if ($x2 < $x1) {
            $x = $x1;
            $x1 = $x2;
            $x2 = $x;
        }
        if ($y2 < $y1) {
            $y = $y1;
            $y1 = $y2;
            $y2 = $y;
        }
        //print_r(array($x1,$y1,$x2,$y2));
        return new GdkRectangle($x1,$y1,$x2,$y2);
    }
}
?>