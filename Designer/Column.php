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
    var $name;              // column name
    var $type;              // type = integer|decimal|float|double|text|cblob|blob|boolean|date|timestamp|time
    var $length;            // field size   
    var $default;           // default value
    var $notnull;           // not null
    
    var $isIndex;           // is it indexed == non mdb?
    
    var $sequence;          // sequence/autoincrement used == not directly mdb (merged seqeneces)
   
    var $deleted = false;   // has it been deleted.
    
    var $extra = array();   // extra items to export into xml file
    /**
    * output XML
    * 
    * @access   public
    * @return  string the XML
    */
    
    
    function toXml() {
        if ($this->deleted) {
            return;
        }
        if (!strlen($this->name)) {
            return;
        }
        $export = array_merge(array('name','type','length','default','notnull'),$this->extra);
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
        $r = MDB_Manager_Common::getFieldDeclaration(&$db, $this->name, $this->toMdb());
        
       // print_r($db);
        if ($this->sequence) {
            // switch case on $db->dntype..
            switch ($db->phptype) {
                case 'mysql':
                    $r .= " AUTOINCREMENT ";
                    break;
                default:
                    $r .= " DEFAULT nextval({$this->name}_sequence) ";
            }
        }
        return $r;
        
        
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
    
    
    
}

?>