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
//  The column dataobject - interface
//
$GLOBALS['_Gtk_MDB_Designer_Interface_Column'] = array();



require_once 'Gtk/MDB/Designer/Column.php';

class Gtk_MDB_Designer_Interface_Column extends Gtk_MDB_Designer_Column {

    var $widgets = array(); // associative array of the widgets 
    var $table;             // the parent  Gtk_MDB_Designer_Interface_Column object
    var $extra = array('isIndex','sequence'); // extra stuff to put in xml file.
    var $unique;        
   /**
    * build the widgets that make up a column (eg. 1 row)
    * 
    *
    * @param object Gtk_MDB_Designer_Interface_Table $table object
    * @param int $row which row to put it on.
    * @access   public
    */
    
    function buildWidgets(&$table,$row) {
        //print_r($this);
        $this->table = &$table;
        $widgets = array(
            // format:
            //name                       display,   width,   pos , span , 
            'name'     =>  array(null,       80,     1 , 1),
            'type'      => array(null,      60,     2 , 1),
            'length'   =>  array(null,      20,      3 , 1),
            'notnull'   => array('N',       20,     4 , 1),
            'isIndex'   => array('I',       20,     5 , 1),
            'sequence'  => array('++',       20,     6 , 1),
            'unique'   =>  array('U',      20,     7 , 1),
            'default'   => array(null,      40,     8 , 2)
            
        );
    
        foreach ($widgets as $string=>$config) {
           
            switch($string) {
            
                case 'name':
                case 'length':
                case 'default':
                    $this->widgets[$string] = &new GtkEntry;
                    $this->widgets[$string]->set_text((string) $this->$string);
                    $this->widgets[$string]->connect('changed',array(&$this,'callbackSetValue'),$string);
                    $this->widgets[$string]->connect('leave-notify-event', array(&$this->table->database,'save'));
                    if ($string == 'name') {
                        $this->widgets[$string]->connect_after('drag_begin',array(&$this,'callbackNamePressed'));
                        $this->widgets[$string]->connect_after('drag-end',array(&$this,'callbackNameReleased'));
                        $this->widgets[$string]->show();
                        //$this->widgets[$string]->show();
                       // $this->widgets[$string]->realize();
                        $this->widgets[$string]->connect('drag_data_get',      array(&$this,'callbackDropAsk'));
                        $this->widgets[$string]->drag_source_set(
                                GDK_BUTTON1_MASK|GDK_BUTTON3_MASK, 
                                 array(array('text/plain', 0, -1)),
                                GDK_ACTION_COPY
                        );
                        $this->widgets[$string]->connect('drag_data_received', array(&$this,'callbackDropReceived'));
                        $this->widgets[$string]->drag_dest_set(
                                GTK_DEST_DEFAULT_ALL, 
                                array(array('text/plain', 0, -1)) ,
                                GDK_ACTION_COPY);

                    }
                    break;
                    
                case 'type':
                    $this->widgets[$string] = &new GtkEntry;
                    $this->widgets[$string]->set_text((string) $this->$string);
                    $this->widgets[$string]->connect('button-press-event',array(&$this,'callbackTypePressed'),$string);
                    $this->widgets[$string]->set_editable(false); 
                    break;
                case 'notnull':
                case 'isIndex':
                case 'sequence':
                case 'unique':
                    $this->widgets[$string] = &new GtkToggleButton($config[0]);
                    $this->widgets[$string]->set_active((int) $this->$string);
                    $this->widgets[$string]->connect('toggled',array(&$this,'callbackSetValue'),$string);
                    
                    break;
                case 'delete':
                    $this->widgets[$string] = &new GtkButton('X');
                    $this->widgets[$string]->connect('pressed',array(&$this,'callbackRowDelete'));
                    break;
                
            }  
            
            $this->widgets[$string]->set_usize($config[1],20);
            $this->table->addCell($this->widgets[$string],$config[2],$row, $config[3], GTK_EXPAND|GTK_FILL);
            $this->widgets[$string]->show();
        }
        $this->deleteMenuItem = &new GtkMenuItem($this->name);
        $this->deleteMenuItem->show();
        $this->deleteMenuItem->connect('activate',array(&$this,'callbackRowDelete'));
        $this->table->deleteMenu->add( $this->deleteMenuItem);
        $this->setVisable();
            
      
            
    }
    function callbackDropAsk($widget, $context, $selection_data, $info, $time)
    {
        //print_r(func_get_args());
        //  $dnd_string = "Perl is the only language that looks\nthe same before and after RSA encryption";
        
        if (method_exists($selection_data,'set')) {
            $selection_data->set($selection_data->target, 8, '');
        }
        $this->table->database->newLink[0] = &$this;
        //echo "START TO {$this->table->name}:{$this->name}\n";
        $this->callbackNameReleased();
        //print_r(func_get_args());
        //$selection_data->set($selection_data->target, 8, "fred");
	}
    
    
    function callbackDropReceived($widget, $context, $selection_data, $info, $time)
    {
        //echo "END TO {$this->table->name}:{$this->name}\n";
        $this->table->database->newLink[1] = &$this;
        $this->table->database->createLink();
        //if ($data && $data->format == 8)
        //			print "Drop data of type " . $data->target->string";
	
	} /**
    * callback for setting a value of anyhting..
    * 
    *
    * @param object GtkObject $table object
    * @param string $field which field to change
    * @access   public
    */
    
    
    function callbackSetValue($object,$field) {
                        

        //echo get_class($object);
        switch ($field) {
            case 'name':
            case 'length':
            case 'default':
                $value = $object->get_text();
                if (@$this->$field == $value) {
                    return;
                }
                // echo "CHANGE?";
                $this->$field = $value;
                //print_r($this->data['TABLES'][$table]['FIELDS']);
                $this->table->database->dirty=true;
            
                break;
            case 'notnull':
            case 'isIndex':
            case 'sequence':
            case 'unique':    
                $value = (int) $object->get_active();
                if (@$this->$field == $value) {
                    return;
                }
                //echo "CHANGE?";
                $this->$field = $value;
                //print_r($this->data['TABLES'][$table]['FIELDS']);
                $this->table->database->dirty=true;
                $this->table->database->save();
                $this->setVisable();
                
                break;
            default: 
                echo "opps forgot :$field\n";
            
                
        }
        //print_r(func_get_args());
    }
    /**
    * callback pressing on the type box - popup the menu
    * 
    *
    * @param object GtkObject that was presed
    * @param object GtkEvent $event object
    * @access   public
    */
      
    function callbackTypePressed($entry,$event) {
    
        
        $this->table->database->designer->activeColumn = &$this;
        
        $v = $entry->get_text();
        $w = $this->table->database->glade->get_widget('type_'.$v);
        
        $w->set_active(true);
        $this->table->database->designer->menu->popup(null, null, null, (int) $event->button, (int) $event->time);
   
    
    }
    /**
    * relayed callback form the popup menu
    * 
    *
    * @param string new value
    * @access   public
    */
    function callbackSetType($value) {
        
        if ($this->type == $value) {
            return;
        }
        $this->widgets['type']->set_text($value);
        $this->type = $value;
        $this->setVisable();
        $this->table->database->dirty=true;
        $this->table->database->save();
        
    }
   /**
    * configure the visiblity and set defaults of an row.
    * 
    * @access   public
    */
    function setVisable() {
        $visable = array(
            //type              // length // default // NotNull // Indexed // Sequence/Auto // unique // default value
            'integer'  => array(  1,1,1,1,1,1,0),
            'decimal'  => array(  1,1,1,1,0,1,0.0),
            'float'    => array(  1,1,1,1,0,1,0.0),
            'double'   => array(  1,1,1,1,0,1,0),
            'text'     => array(  1,1,1,1,0,1,''),
            'cblob'    => array(  1,0,0,0,0,0),
            'blob'     => array(  1,0,0,0,0,0),
            'boolean'  => array(  1,1,1,1,0,0),
            'date'     => array(  0,1,1,1,0,1,''),
            'timestamp'=> array(  0,1,1,1,0,1,''),
            'time'     => array(  0,1,1,1,0,1,'')
        );
        
        $vis = $visable[$this->type];
        foreach(array('length','default','notnull','isIndex','sequence','unique') as $k=>$v) {
            if ($vis[$k]) {
                $this->widgets[$v]->show();
                $this->visable[$v] = 1;
            } else {
                $this->widgets[$v]->hide();
                $this->visable[$v] = 0;
            }
        }
        // hide defaults for sequences.
        //print_r(array($this->sequence,$vis[4]));
        if ($this->sequence && $vis[4]) {
            unset($this->default);
            $this->widgets['default']->hide();
            $this->visable['default'] = 0;
            $this->widgets['notnull']->set_active(true);
            $this->notnull = 1;
            $this->widgets['isIndex']->set_active(true);
            $this->isIndex = 1;
        }
        // set defaults if not null and !sequence and it's empty.
        if ($this->notnull && !$this->sequence && !strlen(@$this->default)) {
            $this->default = $vis[6];
            $this->widgets['default']->set_text($this->default);
        }
        
        if ($this->visable['default'] &&  
            isset($this->default) && 
            $this->default == '' && 
            isset($vis[6]) && 
            ($this->default !== $vis[6])) 
        {
            $this->default = $vis[6];
            $this->widgets['default']->set_text($this->default);
        }
        
        
    }
    /**
    * callback delete a row
    *
    * @access   public
    */
    function callbackRowDelete() {
        $this->deleted = true;
        foreach(array_keys($this->widgets) as $w) {
            $this->widgets[$w]->hide();
            $this->table->table->remove($this->widgets[$w]);
            $this->widgets[$w]->destroy();
        }
        $this->deleteMenuItem->hide();
         
        $this->table->deleteMenu->remove($this->deleteMenuItem);
        $this->deleteMenuItem->destroy();
        $this->table->frame->hide();
        $this->table->frame->show();
    }
    
    var $nameDrag = false;
    var $startPos;
    var $lastEnd = false;
    /**
    * callback when you do a mouse down on the name field.
    *
    * @access   public
    */
    function callbackNamePressed() {
        $w = $this->table->database->layout->window;
        $w->set_cursor($this->table->database->designer->pointers[GDK_HAND2]);
        //$this->startPos         = $w->pointer;
        
       
        // modify this to start on left or right.?
        
        $this->startPos = $this->getStartPos();
        
        
        
        
        $this->nameDrag = true;
        $this->setGC();
        
        $this->lastEnd = false;
        gtk::timeout_add(50,array(&$this,'callbackDragMove'));
       
    }   
    
    
    function setGC() {
        if (isset($GLOBALS['_Gtk_MDB_Designer_Interface_Column']['gc'])) {
            return;
        }
        $w = $this->table->database->layout->window;
        $cmap = $this->table->database->layout->get_colormap();
        $GLOBALS['_Gtk_MDB_Designer_Interface_Column']['gc'] = $w->new_gc();
        
        $gc = &$GLOBALS['_Gtk_MDB_Designer_Interface_Column']['gc'];
        $gc->background =  $cmap->alloc("#000000");
        $gc->function   =  GDK_INVERT;
        $gc->line_width  =  3;
        $gc->line_style = GDK_LINE_ON_OFF_DASH;
        $gc->cap_style = GDK_CAP_ROUND;
    }
    
    
    /**
    * set the startPosition either left or right.. - depending on target.
    *
    * @param array [0] = x [1] = y
    * @access   public
    */
    
    function getStartPos($endPos=false) {
        $ret = array();
        $ww2 = $this->widgets['name']->window;
        
        $yAdj = $this->table->database->layout->get_vadjustment();
        $ret[1] = $ww2->y + $this->table->y + 10 - $yAdj->value;
        //print_r(array(,$this->startPos[1]));
        $xAdj = $this->table->database->layout->get_hadjustment();
        $ww = $this->table->frame->window;
        //print_r(array($this->table->x,$this->table->y));
        //print_r(array($ww->width,$ww->height));
        if (!$endPos) {
            $ret[0] = ($this->table->x * $this->table->scale)- 3 - $xAdj->value;
            //$this->startPos[1] = $this->table->x - 3;
            return $ret;
        }
        if (($endPos[0] + $xAdj->value) > (($this->table->x  * $this->table->scale)+ ($ww->width/2))) {
            // right hand side..
            $ret[0] = ($this->table->x  * $this->table->scale) +$ww->width + 3 - $xAdj->value;
        } else {
            $ret[0] = ($this->table->x  * $this->table->scale) - 3 - $xAdj->value;
        }
        return $ret;
        
    }
    
    /**
    * show the dotted line when moving
    *
    * @access   public
    */
    function callbackDragMove() {
            
        if (!$this->nameDrag) {
            return $this->nameDrag;
        }
        $w = $this->table->database->layout->window;
        // remove last line
        if ($this->lastEnd) {
            $this->drawDragLine($GLOBALS['_Gtk_MDB_Designer_Interface_Column']['gc'],
                $this->startPos[0], $this->startPos[1],
                $this->lastEnd[0] ,$this->lastEnd[1]  );
             
        }
        
        $this->lastEnd = $w->pointer;
        $this->startPos = $this->getStartPos($w->pointer);
        // draw new line
        $this->drawDragLine($GLOBALS['_Gtk_MDB_Designer_Interface_Column']['gc'],
                $this->startPos[0], $this->startPos[1],
                $this->lastEnd[0] ,$this->lastEnd[1]  );
       
        
        return true;
       
    }
    
    function drawDragLine(&$gc, $x1,$y1,$x2,$y2) {
        //echo "DRAW LINE\n";
        $layout = &$this->table->database->layout;
        $xAdj = $layout->get_hadjustment();
        $yAdj = $layout->get_vadjustment();
        $da =  &$this->table->database->designer->drawingArea;
        
        $pix = &$this->table->database->designer->pixmap;
        gdk::draw_line($pix,$GLOBALS['_Gtk_MDB_Designer_Interface_Column']['gc'],
            $x1 + $xAdj->value, $y1 + $yAdj->value,
            $x2 + $xAdj->value, $y2 + $yAdj->value );
        
        $da->draw($this->makeRectangle(
               $x1 + $xAdj->value, $y1 + $yAdj->value,
            $x2 + $xAdj->value,  $y2 + $yAdj->value )
        );
    
    
    
    }
    
    
    function callbackNameReleased() {
        //echo "RELEASE?";
        if ($this->lastEnd) {
            $this->drawDragLine($GLOBALS['_Gtk_MDB_Designer_Interface_Column']['gc'],
                    $this->startPos[0], $this->startPos[1],
                    $this->lastEnd[0] ,$this->lastEnd[1]  );
            $this->lastEnd = false;
            //echo "DONE RELEASE?";
        }
        //echo "CONNECTED FROM {$this->table->name}:{$this->name}\n";
        $w = $this->table->database->layout->window;
        $w->set_cursor($this->table->database->designer->pointers[GDK_ARROW]);
        $this->nameDrag = false;
       
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
    * shrink view.
    *
    * @access   public
    */
    
    function shrink() {
        foreach (array_keys($this->widgets) as $w) {
            if ($w == 'name') {
                continue;
            }
            $this->widgets[$w]->hide();
        }
        $this->widgets['name']->set_editable(false);
    
    }
     /**
    * expand view.
    *
    * @access   public
    */
    
    function expand() {
        foreach (array_keys($this->widgets) as $w) {
            if ($w == 'name') {
                continue;
            }
            $this->widgets[$w]->show();
        }
        $this->setVisable();
        $this->widgets['name']->set_editable(true);
    }
    /**
    * make sure the menau
    *
    * @access   public
    */
    
    function callbackDeletePopup() {
        if ($this->deleted) {
            return;
        }
        $child  = $this->deleteMenuItem->child;
        $name = $this->name;
        if (!$name) {   
            $name = 'UNNAMED';
        }
        $child->set_text('Delete Field : '.  $name);
        $this->deleteMenuItem->show();
    
    }
    /**
    * override standard xml export with our extra variables
    *
    * @access   public
    */
    

    
    function toXml($array = array()) {
        return parent::toXml(array_merge($array, array('isIndex','sequence','unique')));
    }
    
}
?>