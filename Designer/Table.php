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
    var $originalName='';          // name prior to change..
    var $dirty = false;
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
                    $this->fields[$field->name]->originalName = $field->name;
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
        $begin = '    ';
        foreach($this->fields as $field) {
            if ($row =$field->toSQL($db)) {
                $ret .= $begin . $row;
                $begin = ",\n    ";
            }
            
        }
        $pkeys = array();
        if (in_array($db->phptype, array('mysql','fbsql'))) {
            

            foreach($this->fields as $field) {
                if ($field->sequence) {
                    $pkeys[] = $field->name;    
                }
            }
            if ($pkeys) {
                $ret .= ",\n   PRIMARY KEY(" . implode(',', $pkeys) . ')';
            }
        }    
        $ret .= "\n)";
        if (strlen($this->inherits)) {
            $ret.= "\nINHERITS ({$this->inherits})";
        }
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
    
    function updateDatabase(&$db) {
    
    
        // NOTE MDB SOULD HANDLE THIS OK - it it emits a not supported error - then
        // fall back to create a new table.. copy the data and rename it..
    
        
        if ($this->originalName == '') {
            echo "CREATE TABLE {$this->name}\n";
            echo $this->toSQL($db);
            $this->originalName = $this->name;
            foreach(array_keys($this->fields) as $i) {
                $this->fields[$i]->dirty = false;
            }
            return;
        }
        
        /* create or alter sequences */
        
        //foreach($this->fields as $field) {
        //     if ($r = $field->toSequenceSQL($db)) {
        //        $ret .= $r . ";\n";
        //   }
        //}
    
        /* create or alter sequences */
        $changes = array();
        if ($this->originalName != $this->name) {
            
            echo "CHANGE TABLENAME {$this->originalName} to {$this->name}\n";
          
            $changes['name'] = $this->name;
        }
        
        /* add or alter columns */
    
        foreach(array_keys($this->fields) as $i) {
            // removed (never created)
            if ($this->fields[$i]->deleted && $this->fields[$i]->originalName == '') {
                continue;
            }
            // removed (never created)
            if ($this->fields[$i]->deleted && $this->fields[$i]->originalName != '') {
                $changes['RemovedFields'][$this->fields[$i]->name] = array();
                $this->fields[$i]->originalName = $this->fields[$i]->name;
                continue;
            }
            
            // added..
            if ($this->fields[$i]->originalName == '') {
                
                
                $changes['AddedFields'][$this->fields[$i]->name] = array(
                    'Declaration' => $this->fields[$i]->toSQL($db)
                );
                $this->fields[$i]->originalName = $this->fields[$i]->name;
                continue;
            }
            // name changed. 
            if (!$this->fields[$i]->dirty && ($this->fields[$i]->originalName  != $this->fields[$i]->name)) {
                $changes['RenamedFields'][$this->fields[$i]->originalName] = array(
                    'name' => $this->fields[$i]->name,
                    'Declaration' => $this->fields[$i]->toSQL($db)
                );
                $this->fields[$i]->originalName = $this->fields[$i]->name;
                continue;
            }
            // modified
            if ($this->fields[$i]->dirty) {
                $changes['ChangedFields'][$this->fields[$i]->originalName] = array(
                    'Declaration' => $this->fields[$i]->toSQL($db)
                );
                $this->fields[$i]->originalName = $this->fields[$i]->name;
                continue;
            }
        }
        $db->loadManager();
        print_r($changes);
        $res = $db->manager->alterTable($db,$this->originalName, $changes,1);
        if (MDB::isError($res)) {
            require_once 'Gtk/MDB/Designer/MessageBox.php';
            $dialog = new Gtk_MDB_Designer_MessageBox(array(
                    'message' => $res->getMessage(),
                    'cancel' => false
            ));
            $dialog->display();
            return;
        }
                
            
        
        
        
        
        // if recreate -  does the database support row deletion? if not do the table copy trick..
        
        
        // check for changes in rows..
        
           $this->originalName = $this->name;
        
        /* add or alter indexes */
        
        //....
     
    }
  
}
?>
