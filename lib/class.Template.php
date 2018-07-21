<?php
/*
	iCal Events Widget Template Class
	
	Author: Frank Gregor <phranck@programmschmie.de>
	Author URI: http://programmschmie.de
	
	$Id: class.Template.php 434684 2011-09-07 14:39:23Z phranck $
*/
/* 
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	Online: http://www.gnu.org/licenses/gpl.txt
*/


class Template
{
	private /** @type {string} */ $_tpl_out = "";

    /** 
     * Constructor
     * 
     * @param {string}	$template 	This may be a filename URL/path or a simple string
     *                            	that is containing the hole template
     * @param {boolean} $fromFile	A boolean value that is indicating whether the $template comes from a file or a string.
     *                              Default is 'true' - the template comes from a file
     */ 
	public function __construct( $template, $fromFile = true ) {

		if (!$fromFile) {
			// read template from string
			$this->_tpl_out = $template;
		
		} else {
			// read template from file
			if ($fd = @fopen($template, "r")) {
				$this->_tpl_out = fread($fd, filesize($template));
				fclose ($fd);
		
			} else {
				print('
					<div class="tplErrorMessage">
						The File <span class="fileName">'. $template .'</span> doesn\'t exist or cannot be read.
					</div>'."\n"
				);
				exit;
			}
		}
	}


    /**
     * This method assings a given content string to a token. The token will replaced by this content string.
     *
     * @param {string} $token	which is the inner part of a template text token like:
     *                          {REPLACE_THIS} - REPLACE_THIS will be replaced by the content of $content
     * @param {string} $content	the content string for replacing the $token
     *
     * @return {string} the current template where the $token ist replaced by $content
     */
	function replaceTokenByContent( $token, $content) {
		$template = $this->_tpl_out;												// buffer template

		$template = preg_replace( "/{". $token ."}/", "$content", "$template" );
		$this->_tpl_out = $template;												// put modified version back
	}


    /**
     * Deletes a token from the template
     *
     * @param {string} $token	the token to delete
     */
	function deleteToken( $token ) {
		$template = $this->_tpl_out;												// buffer template
		$template = str_ireplace("{". $token ."}", "", $template);
		$this->_tpl_out = $template;												// put modified version back
	}


    /**
     * Shows the current template
     *
     * @return {string} 		the current template which all tokens replaced by content
     */
	function show() {
		echo $this->_tpl_out;
		flush();
	}


    /**
     * Returns the current template
     *
     * @return {string} 		the current template
     */
	function get() {
		return $this->_tpl_out;
	}

	function init() {
		$this->_tpl_out = "";
	}
}


/**
 * Extends the Template BaseClass. This class is for file templates only
 *
 * @param {string}		the name of the template to be loaded. It must include
 *                      a complete path
 */
class TemplateFromFile extends Template {
	public function __construct($filename) {
		parent::__construct($filename, TRUE);
	}
}

class TemplateFromString extends Template {
	public function __construct($templatestring) {
		parent::__construct($templatestring, FALSE);
	}
}

?>