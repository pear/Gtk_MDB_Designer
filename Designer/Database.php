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
//  The Core text routines for database data interation
//

require_once 'Gtk/MDB/Designer/Table.php';

class Gtk_MDB_Designer_Database {
    
    var $table;         // provisionally the table array from the parser (pre normalization)
    var $name;          // the database name;
    var $create = 1;    // mdb create entry.
    var $tables = array(); // associative array of tables (keys not reliable after editing..)
    var $dirty = false; // has the diagram changed and hence needs quick saving
    var $file;          // the filename 
    
    
    /**
    * Noramlize the parser array into a tree
    *
    * deletes the $this->table and sets $this->tables
    * 
    * @return   none
    * @access   public
    */
  
    function normalize() {
        foreach($this->table as $table) {
            $this->tables[$table->name] = $table;
            $this->tables[$table->name]->normalize();
        }
        unset($this->table);
    }
    
    /**
    * translate the object to xml (not MDBxml?)
    * 
    * @return   string - the database xml
    * @access   public
    */
    
    function toXml() {
        $export = array('name','create');
        $ret = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
        $ret .= "<database>\n";
        foreach($export as $k) {
            if (!isset($this->$k)) {
                continue;
            }
            $ret .= "  <$k>{$this->$k}</$k>\n";
        }
        foreach($this->tables as $k=>$v) {
            $ret .= $v->toXml();
        }
        $ret .= "</database>\n";
        return $ret;
    }
   /**
    * save to XML (not mdb)..
    * 
    * @return   string - the database sql create stuff.
    * @access   public
    */
    
    
    function save($extension = '') {
         
        if (!$this->dirty && $extension != '') {
            return;
        }
        if (!$this->file) {
            return;
        }
        
        $data = $this->toXml();
        // ?? check?
        $fh = fopen($this->file.$extension,'w');
        fwrite($fh,$data);
        fclose($fh);
        
        $this->dirty = false;
    }
    /**
    * translate the object to a create tables etc. SQL
    * 
    * @return   string - the database sql create stuff.
    * @access   public
    */
    
     
    function toSQL($dbtype) {
        // test mdb creation..
        require_once 'MDB.php';
        $db = MDB::factory($dbtype);
        //echo "loaded factory?";
        //print_r($db);
        
        
        
        $ret = '';
        // attempt to handle inheritance.. = really needs recursive coding.
        $done = array();
        foreach($this->tables as $key => $table) {
            if (isset($done[$key])) {
                continue;
            }
            if ($table->inherits) {
                $keyB = $this->findTableByName($table->inherits);
                if (!isset($done[$keyB])) { 
                    $ret .= $this->tables[$keyB]->toSQL($db);
                }
                $done[$keyB] = true;
            }
            $ret .= $table->toSQL($db);
            $done[$key]= true;
        }
        return $ret;
        
    }
    
    function findTableByName($name) {
        foreach($this->tables as $k=>$table) {
            if ($table->name == $name) {
                return $k;
            }
        }  
    }
    
    /**
    * save to SQL (not mdb)..
    * 
    * @return   string - the database sql create stuff.
    * @access   public
    */
    
    
    function saveSQL($dbtype) {
        $this->save(''); // save it first and get a filename? 
         
        if (!$this->file) {
            return;
        }
        
        $data = $this->toSQL($dbtype);
        
        // ?? check?
        $fh = fopen($this->file.'.'.$dbtype,'w');
        fwrite($fh,$data);
        fclose($fh);
        
        $this->dirty = false;
    }
    
    
    
    
}
?>