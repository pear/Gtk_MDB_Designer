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
// a database designer 
//
/*


TODO: 
- types - fill in values. - DONE
- fix I for integer - DONE
- disable length if not req. - DONE
- hints on N/K/S 
- support N = Not Null
- support K = Primary Key (one only) - change to indexed..
- support S = Sequence (one only) 

- add table. DONE
- delete table 
- add column
- delete column
- new database
- saveas



*/

require_once 'Gtk/MDB/Designer/Parser.php';
require_once 'Gtk/MDB/Designer/Table.php';
require_once 'Gtk/MDB/Designer/Column.php';

 
class Gtk_MDB_Designer {

    function start($file) {
        $this->loadInterface();
        if ($file) {
            $this->loadFile($file);
        } else {
            $this->newFile();
        }
        gtk::main();
    }
    var $dirty = false;
  
    
    
    function loadInterface() {
        
        if (!extension_loaded('php-gtk')) {
             dl('php_gtk.' .PHP_SHLIB_SUFFIX    );
        }
        $this->glade = new GladeXML(dirname(__FILE__).'/Designer/Designer.glade');
        $this->layout = $this->glade->get_widget('layout');
        $this->menu = $this->glade->get_widget('menu');
        
        define('GDK_HAND2',60);
        define('GDK_ARROW',68);
        define('GDK_CLOCK',26);
        define('GDK_XTERM',152);

        $this->pointers[GDK_HAND2] = gdk::cursor_new(GDK_HAND2);
        $this->pointers[GDK_ARROW] = gdk::cursor_new(GDK_ARROW);
        $this->pointers[GDK_CLOCK] = gdk::cursor_new(GDK_CLOCK);
        $this->pointers[GDK_XTERM] = gdk::cursor_new(GDK_XTERM);
        // bind the type menu
        foreach(array('integer','decimal','float','double','text','cblob','blob','boolean','date','timestamp','time') as $w) {
            $widget = $this->glade->get_widget('type_'.$w);
            $widget->connect('activate',array(&$this,'callbackSetType'),$w);
        }
        
        $connect = array(
            array('menu_newTable',  'activate','newTable'),
            array('menu_new',       'activate','showNewDialog'),
            array('menu_open',      'activate','showFileDialog'),
            array('menu_save',      'activate','saveReal'),
            array('menu_quit',      'activate','shutdown'),
            array('menu_export',    'activate','testExport'),
            
            array('btn_doNew',      'pressed', 'newFile'),
            array('btn_hideNew',    'pressed', 'hideNewDialog'),
            array('btn_fileOk',     'pressed', 'openFile'),
            array('btn_fileCancel', 'pressed', 'hideFileDialog'),
            array('btn_saveasOk',   'pressed', 'saveasFile'),
            array('btn_saveasCancel','pressed','hideSaveAsDialog'),
            
        );
        
        foreach ($connect as $data) {
            //echo "DO : {$data[0]}\n";
            $new = $this->glade->get_widget($data[0]);
            $new->connect($data[1],array(&$this,$data[2]));
        }
        //$new = $this->glade->get_widget('menu_saveas');
        //$new->connect('activate',array(&$this,'showSaveAsDialog'));
        
        // dialogs
        
        
        $window = $this->glade->get_widget('window');
        $window->connect('destroy', array(&$this,'shutdown'));
        $window->connect('delete-event', array(&$this,'deleteEvent'));
        
        
        
        
    }
    var $maxY = 500;
    var $maxX=10;
   
      
    
 
   
    
    function callbackSetType($obj,$type) {
        $this->activeColumn->callbackSetType($type);
    }
   
    
    
    var $table;
    var $name;
    var $create = 1;
     
    var $tables = array();
    function normalize() {
        foreach($this->table as $table) {
            $this->tables[$table->name] = $table;
            $this->tables[$table->name]->normalize();
        }
        unset($this->table);
    }
    
    
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
    
    
     
    function testExport() {
        // test mdb creation..
        require_once 'MDB.php';
        $db = MDB::factory('pgsql');
        //echo "loaded factory?";
        //print_r($db);
        $ret = '';
        foreach($this->tables as $table) {
            $ret .= $table->testExport($db);
        }
        echo $ret;
        
    }
    
    
    /* ---------------------------------------------------------------------------------------------------- */
    /*            Gtk Specific methods                                                                      */
    /* ---------------------------------------------------------------------------------------------------- */
    
    
    
    
      
    function buildWidgets() {
        $menu = $this->glade->get_widget('menubar');
        $menu->set_sensitive(false);
        $database = $this->glade->get_widget('databaseName');
        $database->connect('changed',array(&$this,'callbackNameChanged'));
        
        $database->set_text($this->name);
        $database->connect('leave-notify-event', array(&$this,'save'));
        $this->setTitle();
        foreach (array_keys($this->tables) as $name) {
           $this->tables[$name]->buildWidgets($this,$this->maxX,20);
           
        }
            
        $menu->set_sensitive(true);
        // create each row.
       
    }
    
    function callbackNameChanged($object) {
        if ($object->get_text() == $this->name) {
            return;
        }
        
        $this->name = $object->get_text();
        $this->setTitle();
        $this->dirty = true;
    }
    
    function setTitle() {
        $window =  $this->glade->get_widget('window');
        $window->set_title('Database Designer : '.$this->name. ' : ' . $this->file);
    }   
    
    function expand($x,$y) {
        if ($x < $this->maxX && $y <$this->maxY) {
            return;
        }
        if ($x > $this->maxX) {
            $this->maxX = $x;
        }
        if ($y > $this->maxY) {
            $this->maxY = $y;
        }
        $this->layout->set_size($this->maxX,$this->maxY);
    }
    
    
    
    
      
    function loadFile($file) {
    
        $this->file = $file;
        
        foreach (array_keys($this->tables) as $name) {
           $this->tables[$name]->destroy();
           unset($this->tables[$name]);
        }
    
        $parser = &new Gtk_MDB_Designer_Parser;
        $parser->setInputFile($file);
       
        $parser->parse();
        $parser->classes = array( 
            'database' => 'Gtk_MDB_Designer',
            'database-table' => 'Gtk_MDB_Designer_Table',
            'database-table-declaration' => 'StdClass',
            'database-table-declaration-field' => 'Gtk_MDB_Designer_Column',
            'database-table-declaration-index' => 'Gtk_MDB_Designer_Column',
            'database-sequence' => 'StdClass'
        );
          
        $database = $parser->parseResult();
        $this->maxX = 10;
        $this->maxY = 400;
        $this->name = $database->name;
        $this->table = $database->table;
        $this->normalize();
        
        $this->buildWidgets();
        
    
    }
    var $file;
    
    
    
    function save($istemp = false) {
        //print_r($this->data);
        // normalize changed data.
        if (!$this->dirty) {
            return;
        }
        if (!$this->file) {
            return;
        }
        
        $data = $this->toXml();
        $fh = fopen($this->file.'.tmp','w');
        fwrite($fh,$data);
        fclose($fh);
        
        $this->dirty = false;
    }
    
    function saveReal() {
        if (!$this->file) {
            //echo "NO FILE - show dialog?";
            $this->showSaveAsDialog();
            return;
        }
    
        $data = $this->toXml();
        $fh = fopen($this->file,'w');
        fwrite($fh,$data);
        fclose($fh);
    }
    
     
    
    
    
    
    
    
    
    function hideNewDialog() {
        $dialog = $this->glade->get_widget('dialog_new');
        $dialog->hide();
    }
    function showNewDialog() {
        $dialog = $this->glade->get_widget('dialog_new');
        $dialog->show();
    }
    function hideFileDialog() {
        $dialog = $this->glade->get_widget('dialog_file');
        $dialog->hide();
    }
    function showFileDialog() {
        $dialog = $this->glade->get_widget('dialog_file');
        $dialog->show();
    }
    
    function showSaveAsDialog() {
        $dialog = $this->glade->get_widget('dialog_saveas');
        $dialog->show();
    }
    function hideSaveAsDialog() {
        $dialog = $this->glade->get_widget('dialog_saveas');
        $dialog->hide();
    }
       
    function newFile() {
        $this->hideNewDialog();
        $this->save();
        foreach (array_keys($this->tables) as $name) {
        
           $this->tables[$name]->destroy();
           unset($this->tables[$name]);
        }
        $this->name = 'New Database';
        $this->file = '';
        $this->buildWidgets();
        $this->maxX = 10;
        $this->maxY = 400;
        $this->layout->set_size($this->maxX,$this->maxY);
    
    }
    
    
    function openFile() {
        $this->hideFileDialog();
        $this->save();
        $dialog = $this->glade->get_widget('dialog_file');
        $filename = $dialog->get_filename();
        $this->loadFile($filename);
        
        
    }
    function saveasFile() {
        $this->hideSaveAsDialog();
        
        $dialog = $this->glade->get_widget('dialog_saveas');
        $this->file = $dialog->get_filename();
        $this->setTitle();
        $this->saveReal();
    }
    
    function newTable() {
       
        $name = 'newtable'.(count($this->tables) +1);
        $this->tables[$name] = new Gtk_MDB_Designer_Table;
        $this->tables[$name]->name = $name;
        $this->tables[$name]->buildWidgets($this,20,20);
      
    
    }
    function deleteEvent()
    {
        return false;
    }
        
    function shutdown()
    {
        $this->save();
        gtk::main_quit();
    }

}

 



$des =new Gtk_MDB_Designer;
$des->start(@$_SERVER['argv'][1]);

?>