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
    
    var $from;              // Gtk_MDB_Designer_Interface_Column
    var $to;                // Gtk_MDB_Designer_Interface_Column
    var $id;                // position in database->links[$id] array
    var $deleted = false;   // has it been deleted.
      
   
    
    
    
    function matches(&$array) {
        if ($this->deleted) {
            return;
        }
        
        if ($this->from->table->name != $array[0]->table->name) {
            return;
        }
        if ($this->to->table->name != $array[1]->table->name) {
            return;
        }
        // same table?
        if ($array[0]->table->name == $array[1]->table->name) {
            return true;
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
    
    function addLink() {
        // ask first for startpos
        // make up firstpos
        //require_once 'Gtk/VarDump.php';new Gtk_VarDump($this);
        $this->setGC();
        
       
      
        $this->from->table->links[] = &$this;
        $this->to->table->links[] = &$this;
        $this->from->links[] = &$this;
        $this->to->links[] = &$this;
        
        $this->show();
    
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
        $gc->background =  $cmap->alloc("#FFFFFF");
        $gc->foreground =  $cmap->alloc("#FF00FF");
        $gc->function   =  GDK_XOR;
        $gc->line_width  =  3;
        $gc->line_style = GDK_LINE_ON_OFF_DASH;
        $gc->cap_style = GDK_CAP_ROUND;
         
        
    }
    
    
    function hide() {
        if ($this->deleted) {
            return;
        }
            
        $this->drawDragLine();
        $this->to->widgets['button'.$this->toButton]->hide();
    }
    
    
    function show() {
        if ($this->deleted) {
            return;
        }
        $this->drawDragLine();
        $this->to->showLinkButtons();
    }
    
    function remove() {
        $this->hide();
        $this->deleted = true;
        $this->to->showLinkButtons();
    }
    
    // similar to the one in column.
    
    function drawDragLine() {
        if ($this->deleted) {
            return;
        }
        //echo "DRAWDRAGLINE 1\n";
    
        $base = array($this->from->table->x,$this->from->table->y);
        $toPos = $this->to->getStartPos($base);
        $this->fromPos = $this->from->getStartPos($toPos);
        $this->toPos = $this->to->getStartPos($this->fromPos);
        
        $gc = &$GLOBALS['_Gtk_MDB_Designer_Interface_Link']['gc'];
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
        
        if ($x2 < ($this->to->table->x - $xAdj->value) ) {
           $this->toButton = 'L';
        } else {
           $this->toButton = 'R';
        }
        //echo "DRAWDRAGLINE 2\n";
        $this->to->addLinkButton($this->toButton);
        //echo "DRAWDRAGLINE 3\n";
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
    
      /**
    * output XML
    * 
    * @access   public
    * @return  string the XML
    */
    
    
    function toXml($array=array()) {
        if ($this->deleted) {
            return;
        }
        
        $export = array(
            'fromtable' => $this->from->table->name,
            'fromfield'   => $this->from->name,
            'totable'   => $this->to->table->name,
            'tofield'     => $this->to->name,
        );
        $ret  = "      <link>\n";
        foreach($export as $k=>$v) {
            $ret .= "        <$k>{$v}</$k>\n";
        }
        $ret .= "      </link>\n";
        return $ret;
    }
    
    
    
    
    
    
}
?>