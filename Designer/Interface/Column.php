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
require_once 'Gtk/MDB/Designer/Column.php';

class Gtk_MDB_Designer_Interface_Column extends Gtk_MDB_Designer_Column {

    var $widgets = array(); // associative array of the widgets 
    var $table;             // the parent  Gtk_MDB_Designer_Interface_Column object
    var $extra = array('isIndex','sequence'); // extra stuff to put in xml file.
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
            //name       widget                  display,   width,   pos 
            'name'     => array('GtkEntry',          null,       80,     1 , 'changed'),
            'type'      =>array('GtkEntry',           null,      60,     2 , 'button-press-event'),
            'length'   =>array('GtkEntry',           null,      20,      3 , 'changed'),
            'notnull'   =>array('GtkToggleButton',    'N',       20,     4 , 'toggled'),
            'isIndex'   =>array('GtkToggleButton',    'I',       20,     5 , 'toggled'),
            'sequence'  => array('GtkToggleButton',   '++',       20,     6 , 'toggled'),
            'default'   =>array('GtkEntry',           null,      40,     7 , 'changed'),
            'delete'   =>array('GtkButton',           'X',      20,     8 , 'pressed')
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
                    $this->widgets[$string] = &new GtkToggleButton($config[1]);
                    $this->widgets[$string]->set_active((int) $this->$string);
                    $this->widgets[$string]->connect('toggled',array(&$this,'callbackSetValue'),$string);
                    
                    break;
                case 'delete':
                    $this->widgets[$string] = &new GtkButton('X');
                    $this->widgets[$string]->connect('pressed',array(&$this,'callbackRowDelete'));
                    break;
                
            }  
            
            $this->widgets[$string]->set_usize($config[2],20);
            $this->table->addCell($this->widgets[$string],$config[3],$row, GTK_EXPAND|GTK_FILL);
            $this->widgets[$string]->show();
        }
        $this->setVisable();
            
            
            
            
    }
     /**
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
            //type              // length // default // NotNull // Indexed // Sequence/Auto
            'integer'  => array(  1,1,1,1,1,0),
            'decimal'  => array(  1,1,1,1,0,0.0),
            'float'    => array(  1,1,1,1,0,0.0),
            'double'   => array(  1,1,1,1,0,0),
            'text'     => array(  1,1,1,1,0,''),
            'cblob'    => array(  1,0,0,0,0),
            'blob'     => array(  1,0,0,0,0),
            'boolean'  => array(  1,1,1,1,0),
            'date'     => array(  0,1,1,1,0),
            'timestamp'=> array(  0,1,1,1,0),
            'time'     => array(  0,1,1,1,0)
        );
        
        $vis = $visable[$this->type];
        foreach(array('length','default','notnull','isIndex','sequence') as $k=>$v) {
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
            $this->default = $vis[5];
            $this->widgets['default']->set_text($this->default);
        }
        
        if ($this->visable['default'] &&  
            isset($this->default) && 
            $this->default == '' && 
            isset($vis[5]) && 
            ($this->default !== $vis[5])) 
        {
            $this->default = $vis[5];
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
        $this->widgets['name']->set_sensitive(false);
    
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
        $this->widgets['name']->set_sensitive(true);
    }
    
    
    
}
?>