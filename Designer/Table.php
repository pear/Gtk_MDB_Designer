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
    var $name;                  // name of the database
 
    var $fields = array();      // fields (array of columns)
    var $indexes = array();     // index  (array of indexes)
    var $declaration;           // original declation child (pre normalization)
    
    var $extras = array();      // extra items to export 
    
 
    
    var $deleted = false; // has it been deleted
   /**
    * convert declaration contents into field and index.
    * 
    * @access   public
    */
    
    
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
    
    /**
    * output XML - add this->extra's the xml if neccesary
    * 
    * @access   public
    */
    
    function toXml() {
        if ($this->deleted) {
            return;
        }
        $export = array_merge(array('name'),$this->extras);
    
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
     /**
    * output SQL for create tables
    * 
    * @access   public
    */
    function toSQL($db) {
        // comments would be nice in the file..
        if ($this->deleted) {
            return;
        }
        $ret = '';
        foreach($this->fields as $field) {
            if ($r = $field->toSequenceSQL($db)) {
                $ret .= $r . ";\n";
            }
        }
    
        $ret .= "\nCREATE TABLE {$this->name} (\n";
        foreach($this->fields as $field) {
            if ($row =$field->toSQL($db)) {
                $ret .= "    ". $row. ",\n";
            }
        }
        $ret .= ")";
        if (strlen($this->inherits)) {
            $ret.= "\nINHERITS ({$this->inherits})";
        
        $ret .= ";\n";
        // now indexes..
        foreach($this->fields as $field) {
            if ($r = $field->toIndexSQL($db)) {
                $ret .= $r . ";\n";
            }
        }
        
        $ret .= "\n\n";
        
        return $ret;
    }
    
    
     
    
  
}
?>