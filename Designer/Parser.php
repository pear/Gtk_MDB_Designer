<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2002 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB is a merge of PEAR DB and Metabases that provides a unified DB   |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Christian Dickmann <dickmann@php.net>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once('XML/Parser.php');

/**
 * Parses an XML schema file
 *
 * @package MDB
 * @category Database
 * @access private
 * @author  Christian Dickmann <dickmann@php.net>
 */
class Gtk_MDB_Designer_Parser extends XML_Parser
{
    var $level  = 0;
    var $elements = array();
    var $element = '';
    var $result = array();
    var $error = NULL;
    var $stack = array();
     
    var $fail_on_invalid_names = 1;

    function  Gtk_MDB_Parser() 
    {
        $this->XML_Parser();
    }
  
    function startHandler($xp, $element, $attribs) 
    {   
        //echo "start $element";
        $this->elements[] = strtolower($element);
        $this->element = implode('-', $this->elements);
        // echo "start  {$this->element}\n";
        $this->result[] = array( $this->element , '');
        
        $this->stack[] = count($this->result)-1; 
        //print_r($this->stack);
        
    } 
    function cdataHandler($xp, $data)
    {
         
        //print_r($this->stack);
        $cur = array_pop($this->stack);
      //  print_r($this->result);
        $this->result[$cur] = array($this->element,trim($this->result[$cur][1] .$data));
        
        $this->stack[] = $cur;
        
    }
    function raiseError($msg, $xp = NULL)
    {
        if ($this->error === NULL) {
            if(is_resource($msg)) {
                $error = "Parser error: ";
                $xp = $msg;
            } else {
                $error = "Parser error: \"".$msg."\"\n";
            }
            if($xp != NULL) {
                $byte = @xml_get_current_byte_index($xp);
                $line = @xml_get_current_line_number($xp);
                $column = @xml_get_current_column_number($xp);
                $error .= "Byte: $byte; Line: $line; Col: $column\n";
            }
            $this->error = PEAR::raiseError(NULL, MDB_ERROR_MANAGER_PARSE, NULL, NULL,
                $error, 'MDB_Error', TRUE);
        };
        return(FALSE);
    }
    function endHandler($xp, $element)
    {
        $cur = array_pop($this->stack);
       // print_r($end);
        //$this->result[$end] =  $end;
        array_pop($this->elements);
        // no 'post tag' text please..
        //$this->parseElement($this->element,$this->result[$cur][$this->element]);
        $this->element = implode('-', $this->elements);
        
            
    }
    
    var $classes = array( 
            'database' => 'StdClass',
            'database-table' => 'StdClass',
            'database-table-declaration' => 'StdClass',
            'database-table-declaration-field' => 'StdClass',
            'database-table-declaration-index' => 'StdClass',
            'database-sequence' => 'StdClass'
    );
    
    
    
   
    var $pos =0;
    
    function parseResult($prefix='') {
        //echo "PREFIX: $prefix\n";
        $class = isset($this->classes[$prefix]) ? $this->classes[$prefix] : 'stdClass';
        
        $ret = new $class;
        $lastChild = '';
        
        while ($this->pos < count($this->result)) {
            list($key,$val) = $this->result[$this->pos];
            //echo "READ: $key=>$val\n";
            
            
            if ($prefix == $key) {
          //          print_r($ret);
                return $ret;
            }
            if (strlen($key) < strlen($prefix)) {
                //    print_r($ret);
                return $ret;
            }
            // new subclass
            if (isset($this->classes[$key])) {
                $bits = explode('-',$key);
                $new = array_pop($bits);
                $this->pos++;
                if (!isset($ret->$new)) {
                    $ret->$new = array();
                }
                $child = $this->parseResult($key);
                //print_r($child);
                array_push($ret->$new,$child);
                $lastChild = $new;
                continue;
            }
            // go down..
            
            
            $str = str_replace('-','_',substr($key,strlen($prefix)+1));
            $ret->$str = $val;
            $this->pos++;
        }
        //print_r($ret);
        
        if ($prefix == '') {
            return array_pop($ret->$lastChild);
        }
        return $ret;
    }
    
    
     
          
}




?>