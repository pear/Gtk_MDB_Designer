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
require_once 'Gtk/MDB/Designer/Interface/Database.php';

 
class Gtk_MDB_Designer {
    
    var $database; // the database object.. (Gtk_MDB_Designer_Interface_Database)
    var $glade;    // the glade widget      (GtkGlade)
    var $menu;     // the popup menu for the types (GtkMenu)
    var $layout;   // the layout            (GtkLayout)
    var $activeColumn; // the active column object for popup type selection
    /**
    * Constructor - not called automatically - so it could be wrapped into phpmole?
    * 
    * @access   public
    */    
    function start($file) {
        $this->loadInterface();
        if ($file) {
            $this->loadFile($file);
        } else {
            $this->newFile();
        }
        gtk::main();
    }
    
    
    
    
   /**
    * Main interface builder
    * 
    * @access   public
    */   
    
    
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
            array('menu_newTable',  'activate','callbackNewTable'),
            array('menu_new',       'activate','showNewDialog'),
            array('menu_open',      'activate','showFileDialog'),
            array('menu_save',      'activate','callbackSave'),
            array('menu_quit',      'activate','callbackShutdown'),
            array('menu_export',    'activate','callbackSaveSQL'),
            array('menu_zoomOut',   'activate','callbackShrink'),
            array('menu_zoomIn',    'activate','callbackExpand'),
            
            array('btn_doNew',      'pressed', 'newFile'),
            array('btn_hideNew',    'pressed', 'hideNewDialog'),
            array('btn_fileOk',     'pressed', 'callbackOpenFile'),
            array('btn_fileCancel', 'pressed', 'hideFileDialog'),
            array('btn_saveasOk',   'pressed', 'callbackSaveAsFile'),
            array('btn_saveasCancel','pressed','hideSaveAsDialog'),
            
        );
        
        foreach ($connect as $data) {
            //echo "DO : {$data[0]}\n";
            $new = $this->glade->get_widget($data[0]);
            $new->connect($data[1],array(&$this,$data[2]));
        }
        // todo a save as button...
        //$new = $this->glade->get_widget('menu_saveas');
        //$new->connect('activate',array(&$this,'showSaveAsDialog'));
        
     
        
        
        $window = $this->glade->get_widget('window');
        $window->connect('destroy',      array(&$this,'callbackShutdown'));
        $window->connect('delete-event', array(&$this,'callbackDeleteEvent'));
        
        
        
        
    }
   
   
   
      
    
    
   
    
  
    
    
    /* ----------------------------------------------------------------------------------*/
    /*            Gtk Specific methods                                                   */
    /* ----------------------------------------------------------------------------------*/
   
   /**
    * set the Window Title
    * 
    * @access   public
    */    
    function setTitle() {
        if (!isset($this->database)) {
            return;
        }
        $window =  $this->glade->get_widget('window');
        $window->set_title('Database Designer : '.$this->database->name. ' : ' . $this->database->file);
    }   
    /**
    * open a file callback
    * 
    * @access   public
    */   
    function loadFile($file) {
    

        $this->database->destroy();
        // load a new parser.
        
        $parser = &new Gtk_MDB_Designer_Parser;
        $parser->setInputFile($file);
       
        $parser->parse();
        $parser->classes = array( 
            'database' => 'Gtk_MDB_Designer_Interface_Database',
            'database-table' => 'Gtk_MDB_Designer_Interface_Table',
            'database-table-declaration' => 'StdClass',
            'database-table-declaration-field' => 'Gtk_MDB_Designer_Interface_Column',
            'database-table-declaration-index' => 'Gtk_MDB_Designer_Interface_Column',
            'database-sequence' => 'StdClass'
        );
        
        $this->database = $parser->parseResult();
        
        $this->database->normalize();
        
        $this->database->file = $file;
        $this->database->buildWidgets($this);
        
    
    }
    
    /**
    * create a new file.
    * 
    * @access   public
    */   
    
    
   
    
     
    function newFile() {
        $this->hideNewDialog();
        if (isset($this->database)) {
            $this->database->save('.tmp');
            $this->database->destroy();
        }
        $this->database = new Gtk_MDB_Designer_Interface_Database;
        $this->database->name = 'New Database';
        $this->database->buildWidgets($this);
    }
   
    /* --------------------------------------------------------------------------------- */
    /*           Main Callbacks                                                          */
    /* --------------------------------------------------------------------------------- */
   
    /**
    * callback relay popup menu to curent callback.
    * 
    * @access   public
    */

    
    function callbackSetType($obj,$type) {
        $this->activeColumn->callbackSetType($type);
    }
    
    /**
    * call back for open a file = called by pressing the ok button on the file dialog.
    * 
    * @access   public
    */
    
    function callbackOpenFile() {
        $this->hideFileDialog();
        $this->database->save('.tmp');
        $dialog = $this->glade->get_widget('dialog_file');
        $filename = $dialog->get_filename();
        $this->loadFile($filename);
    }
   /**
    * call back for save  - from menu
    * 
    * @access   public
    */
    function callbackSave() {
        $this->database->save('');        
    }
    /**
    * call back for save  - from menu
    * 
    * @access   public
    */
    function callbackSaveSQL() {
        $this->database->saveSQL();
    }
     /**
    * call back for save as file = called by pressing the ok button on the file dialog.
    * 
    * @access   public
    */
    function callbackSaveAsFile() {
        $this->hideSaveAsDialog();
        $dialog = $this->glade->get_widget('dialog_saveas');
        $this->database->file = $dialog->get_filename();
        $this->setTitle();
        $this->database->save('');
    }
    /**
    * call back for new table - from menu
    * 
    * @access   public
    */
    function callbackNewTable() {
        $this->database->newTable();
    }
    /**
    * call back for zoom in/expand
    * 
    * @access   public
    */
    function callbackExpand() {
        $this->database->expand();
    }
    /**
    * call back for zoom out/shrink
    * 
    * @access   public
    */
    function callbackShrink() {
        $this->database->shrink();
    }
    /**
    * call back for pressing close button on the window.
    * 
    * @access   public
    */
    function callbackDeleteEvent()
    {
        return false;
    }
     /**
    * call back for pressing close button (phase 2) on the window = and quit button.
    * 
    * @access   public
    */   
    function callbackShutdown()
    {
        $this->datbase->save('.tmp');
        gtk::main_quit();
        exit;
    }
     
    /* these could probably be removed by connect_object('...',$this->glade->get_widget('dialog_new'),'hide') */
    
    function hideNewDialog() 
    {
        $dialog = $this->glade->get_widget('dialog_new');
        $dialog->hide();
    }
    function showNewDialog() 
    {
        $dialog = $this->glade->get_widget('dialog_new');
        $dialog->show();
    }
    function hideFileDialog() 
    {
        $dialog = $this->glade->get_widget('dialog_file');
        $dialog->hide();
    }
    function showFileDialog() 
    {
        $dialog = $this->glade->get_widget('dialog_file');
        $dialog->show();
    }
    
    function showSaveAsDialog() 
    {
        $dialog = $this->glade->get_widget('dialog_saveas');
        $dialog->show();
    }
    function hideSaveAsDialog() 
    {
        $dialog = $this->glade->get_widget('dialog_saveas');
        $dialog->hide();
    }
    

}

 



$des =new Gtk_MDB_Designer;
$des->start(@$_SERVER['argv'][1]);

?>