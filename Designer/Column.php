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
//  The column dataobject
//



class Gtk_MDB_Designer_Column {
    var $originalName;      // original name (set by table normalize method)
    var $dirty = false;
    var $name;              // column name
    var $type;              // type = integer|decimal|float|double|text|cblob|blob|boolean|date|timestamp|time
    var $length;            // field size   
    var $default;           // default value
    var $notnull;           // not null
    
    var $isIndex;           // is it indexed == non mdb?
    
    var $sequence;          // sequence/autoincrement used == not directly mdb (merged seqeneces)
   
    var $deleted = false;   // has it been deleted.
    
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
        if (!strlen($this->name)) {
            return;
        }
        $export = array_merge(array('name','type','length','default','notnull'),$array);
        $ret  = "      <field>\n";
        foreach($export as $k) {
            if (!isset($this->$k)) {
                continue;
            }
            $ret .= "        <$k>{$this->$k}</$k>\n";
        }
        $ret .= "      </field>\n";
        return $ret;
    }
    /**
    * get the create SQL lines
    * 
    * @param object MDB $db database object to use for creating strings.
    * @return string the SQL 
    * @access   public
    */
    function toSQL($db) {
        if ($this->deleted) {
            return;
        }
        if (!strlen($this->name)) {
            return;
        }
        require_once 'MDB/Modules/Manager/Common.php';
        if ($this->sequence) {
            unset($this->default);
        }
        switch ($this->type) {
            case 'date':
            case 'datetime':
            case 'time':
                if (isset($this->default) && !strlen($this->default)) {
                    unset($this->default);
                }
            
        }
        
        $r = MDB_Manager_Common::getFieldDeclaration(&$db, $this->name, $this->toMdb());
        
       // print_r($db);
        if ($this->sequence) {
            // switch case on $db->dntype..
            switch ($db->phptype) {
                case 'mysql':
                case 'fbsql':
                    $r .= " AUTOINCREMENT ";
                    break;
                case 'pgsql':
                case 'oci8': // no idea if this works..
                    $r .= " DEFAULT nextval('{$this->table->name}_seq') ";
            }
        }
        return $r;
        
    }
    /**
    * create SQL for sequences if neccesary.
    * - defaults to use database's native - eg. AUTOINCREMENT on those that support it.
    * - cant use mdb code as currently mdb runs the query. 
    *
    * @param object MDB $db database object to use for creating strings.
    * @return string the SQL 
    * @access   public
    */
    function toSequenceSQL($db) {
        
        if (!$this->sequence) {
            return;
        }
        // looks like mdb cant return the sql for sequences - it actually does the work.
        //$db->loadManager();
        //$db->manager->
        // WILL NOT HANDLE MULTIPLE SEQUENCES WELL...
        switch ($db->phptype) {
            case 'mysql':
            case 'fbsql':
                    return;
            case 'pgsql':
                return "CREATE SEQUENCE {$this->table->name}_seq INCREMENT 1 START 1";
            case 'oci8':
                return "CREATE SEQUENCE {$this->table->name}_seq START WITH 1 INCREMENT BY 1";
        }
    }
    /**
    * create SQL for indexes if neccesary.
    * - cant use mdb code as currently mdb runs the query.     
    * 
    * @param object MDB $db database object to use for creating strings.
    * @return string the SQL 
    * @access   public
    */
    function toIndexSQL($db) {
        
        if (!$this->isIndex) {
            return;
        }
        $unique = '';
        $index = ' INDEX ';
        if ($this->unique) {
            $unique = ' UNIQUE ';
            $index = ' UNIQUE ';
        }
        
        switch ($db->phptype) {
            case 'mysql':
            case 'fbsql':
                return "ALTER TABLE {$this->table->name} ADD {$index} {$this->name}_index ({$this->name})";
            case 'pgsql':
            case 'oci8':
                return "CREATE {$unique} INDEX {$this->table->name}__{$this->name}_index on {$this->table->name} ({$this->name})";
                
        }
    }
    /**
    * get an MDB array from the row.
    * @return  array - associative array of key=>val for mdb call
    * @access   public
    */ 
    
    function toMdb() {
        if ($this->deleted) {
            return;
        }
        if (!strlen($this->name)) {
            return;
        }
        foreach (array('name','type','length','default','notnull') as $k) {
            if (!isset($this->$k)) {
                continue;
            }
            $ret[$k] = $this->$k;
        }
        return $ret;
    }
    
    
    function updateDatabase(&$db) {
        
        if ($this->originalName == '') {
            echo "ALTER TABLE ADD COLUMN ....\n";
            $this->originalName = $this->name;
            return;
        }
        // mmh - technically we dont need to updates if nothing has changed.
        // maybe a dirty flag is needed here..
        
        echo "ALTER TABLE {$this->originalName} ".$this->toSQL($db)."\n";
    
    
    }
    
    
}

?>
