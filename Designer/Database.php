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
    
    var $link = array();    // incomming list of links.
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
        //print_r($this);
        
        
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
        foreach($this->links as $k=>$v) {
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
        
        if ($extension == '') {
            $this->updateDatabase();
        }
    }
    /**
    * translate the object to a create tables etc. SQL
    * this is a recersive call function - if table key = '' - it starts the loop.
    *    
    * @param    database type - used to call MDB factory method.
    * @param    tablekey - blank to start the routine - otherwise returns table and inherited table sql.
    *
    * @return   string - the database sql create stuff.
    * @access   public
    */
    
     
    function toSQL($dbtype,$tablekey = '') {
        // test mdb creation..
        static $done;
        static $db;
        //echo "loaded factory?";
        //print_r($db);
        // this part outputs a table ..
        $ret = '';
        if ($tablekey != '') {
            // already exported.
            if (in_array($tablekey,$done)) {
                return;
            }
            
            if (isset($this->tables[$tablekey]->inherits) && strlen($this->tables[$tablekey]->inherits)) {
                $keyB = $this->findTableByName($this->tables[$tablekey]->inherits);
                // fudge..
                if (!$keyB) {
                    $this->tables[$tablekey]->inherits = '';
                    return;
                }
                
                $ret = $this->toSQL('',$keyB);
            }
            $done[] = $tablekey;
            //echo "DO $tablekey\n";
            return  $ret . $this->tables[$tablekey]->toSQL($db);
        }
        if ($dbtype == '') {
            return '';
        }
        // the code to start this..
        require_once 'MDB.php';
        $db = MDB::factory($dbtype);
        $done = array();
        foreach($this->tables as $key => $table) {
          $ret .= $this->toSQL($db,$key);  
        }
        return $ret;
        
    }
    
    /**
    * find a table key by name = as the key=>value association in the table
    * gets broken as soon as you edit it..
    * 
    * @param    string table name to look for
    * @return   string - key found.
    * @access   public
    */   
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
    /**
    * relay to links to write the links.ini file.
    * 
    * @return   string - the links.ini file contents.
    * @access   public
    */
    
    
    
    function toLinksIni() {
        $ret = '';
        
        foreach($this->tables as $k=>$v) {  
            $ret .= "\n\n[{$v->name}]\n";
            foreach($this->links as $kk=>$vv) {
                $ret .= $vv->toLinksIni($v->name);
            }
        }
        
        return $ret;
    }
    
     /**
    * save to links.ini
    * 
    * @access   public
    */
    
    
    function saveLinksIni() {
        $this->save(''); // save it first and get a filename? 
         
        if (!$this->file) {
            return;
        }
        
        $data = $this->toLinksIni();
        
        // ?? check?
        //echo dirname(realpath($this->file)).'/'.$this->name.'.links.ini' . "\n";
        $fh = fopen(dirname(realpath($this->file)).'/'.$this->name.'.links.ini','w');
        fwrite($fh,$data);
        fclose($fh);
        
        
    }
    
    function updateDatabase() {
        return;
        // this stuff is very experimental..
        // connect to database...
        require_once 'MDB.php';
        $db = MDB::connect('pgsql://alan:@localhost/hebe');
        foreach(array_keys($this->tables) as $k) {
            $this->tables[$k]->updateDatabase($db);
        }
          
    }
    
}
?>