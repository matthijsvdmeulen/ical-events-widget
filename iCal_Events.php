<?php
/*
	Plugin Name: iCal Events Widget
	Plugin Script: iCal_Events.php
	Plugin URI: https://jmvdmeulen.nl/icalevents/
	Description: Shows you upcoming events for a configurable iCal .ics file or URL. You can also set a range of dates.
	Version: 0.1
	Author: Matthijs van der Meulen
	Author URI: https://jmvdmeulen.nl
	Text Domain: ical_events
	Domain Path: /languages/
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

define('ICAL_EVENTS_VERSION', '0.1');

class iCal_Events extends WP_Widget {
	private	/** @type {string} */ $widgetFilePath;
	private /** @type {string} */ $libPath;
	private /** @type {string} */ $templatePath;
	private /** @type {string} */ $languagePath;
	private /** @type {string} */ $imagePath;
	private /** @type {string} */ $cssPath;
	private /** @type {string} */ $javaScriptPath;
	
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$this->widgetFilePath	= dirname(__FILE__);
		$this->libPath			= $this->widgetFilePath.'/lib/';
		$this->templatePath		= $this->widgetFilePath.'/templates/';
		$this->imagePath		= $this->widgetFilePath.'/images/';
		$this->cssPath			= basename(dirname(__FILE__)).'/css/';
		$this->javaScriptPath	= basename(dirname(__FILE__)).'/js/';
		$this->languagePath		= basename(dirname(__FILE__)).'/languages';
	    
        load_plugin_textdomain('ical_events', 'false', $this->languagePath);
    
		if (!file_exists( $this->libPath.'class.iCalReader.php' )) return false;
		require_once( $this->libPath.'class.iCalReader.php' );
		
	    if (!file_exists( $this->libPath.'class.Template.php' )) return false;
	    require_once( $this->libPath.'class.Template.php' );

		// widgets own javascript files
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');

	    wp_register_script('jquery-ui-datepicker', plugins_url($this->javaScriptPath.'jquery.ui.datepicker.min.js'), false, ICAL_EVENTS_VERSION);
	    wp_enqueue_script('jquery-ui-datepicker');
		
	    wp_register_script('iCal_Events_JS', plugins_url($this->javaScriptPath.'iCal_Events.js'), false, ICAL_EVENTS_VERSION);
	    wp_enqueue_script('iCal_Events_JS');
		
	    wp_register_script('DateJS', plugins_url($this->javaScriptPath.'date.js'), false, ICAL_EVENTS_VERSION);
	    wp_enqueue_script('DateJS');

		// widgets own css styles
	    wp_register_style('jquery-ui-theme', plugins_url($this->javaScriptPath.'custom-theme/jquery-ui-1.8.16.custom.css'), array(), ICAL_EVENTS_VERSION, 'screen');
	    wp_enqueue_style('jquery-ui-theme');

	    wp_register_style('ui-datepicker', plugins_url($this->cssPath.'ui.datepicker.css'), array(), ICAL_EVENTS_VERSION, 'screen');
	    wp_enqueue_style('ui-datepicker');

	    wp_register_style('iCal_Events_CSS', plugins_url($this->cssPath.'iCal_Events.css'), array(), ICAL_EVENTS_VERSION, 'screen');
	    wp_enqueue_style('iCal_Events_CSS');

		$widget_ops = array('classname' => __CLASS__, 'description' => esc_html__('Shows you upcoming events for a configurable iCal .ics file or URL.', 'ical_events'));
		parent::__construct(__CLASS__, esc_html__( 'iCal Events', 'ical_events' ), $widget_ops);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	function widget( $args, $instance ) {

        // outputs the content of the widget
        // ADD YOUR FRONT-END FORM HERE
		extract( $args );
		$title					= $instance['title'];
		$iCalURI				= $instance['iCalURI'];
		$showNrOfEvents			= $instance['showNrOfEvents'];
		$showEventRangeDateFrom	= $instance['showEventRangeDateFrom'];
		$showEventRangeDateTo	= $instance['showEventRangeDateTo'];
		$showEventDateStart		= $instance['showEventDateStart'];
		$showEventDateEnd		= $instance['showEventDateEnd'];
		$showEventTimeStart		= $instance['showEventTimeStart'];
		$showEventTimeEnd		= $instance['showEventTimeEnd'];
		$showEventSummary		= $instance['showEventSummary'];
		$showEventDescription	= $instance['showEventDescription'];
		$showEventLocation		= $instance['showEventLocation'];
		$showEventURL			= $instance['showEventURL'];
		// prepare the URL for use with the "ical" class
		$iCalURI = str_ireplace ( 'webcal:' , 'http:' , $instance['iCalURI'] );

		$iCal = new ical($iCalURI);
		$iCalEvents = $iCal->eventsFromRange( $showEventRangeDateFrom, $showEventRangeDateTo );
		echo('<pre>'); print_r($iCal); echo('</pre>');

		if (!$iCalEvents)
			return false;


		if (!$showEventRangeDateFrom)
			$showEventRangeDateFrom = new DateTime();
		else
			$showEventRangeDateFrom = new DateTime($showEventRangeDateFrom);
			
		if (!$showEventRangeDateTo)
			$showEventRangeDateTo = new DateTime('2038/12/31');
		else
			$showEventRangeDateTo = new DateTime($showEventRangeDateTo);
			


		// if the title for this event list is not given
		// by the options, so set the widget title to iCal name
		$title = ( $title == '' ? $iCal->cal['VCALENDAR']['X-WR-CALNAME'] : $title);


		// -----------------------------------------------------------------------------------------------
		// show the widget
		// -----------------------------------------------------------------------------------------------
		
		print( $before_widget );
		if ( $title )
			print( $before_title . $title . $after_title );
		
		// there are NO event items
		if (count($iCalEvents) == 0) {
			$eventListEmptyTpl = new TemplateFromFile( $this->templatePath.'event_list_empty.tpl' );
			$eventListEmptyTpl->replaceTokenByContent( 'EVENTLIST_EMPTY_MESSAGE', esc_html__( 'There are no events available.', 'ical_events' ) );
			$eventListEmptyTpl->show();
		

		// there are event items available
		} else {
			$eventCounter = 0;
			$eventListItems = '';

			// iterate through the events, extract the $showNrOfEvents events
			// and put into a list
			foreach( $iCalEvents as $anEvent) {
				// get a new event list item template
				$eventListItemTpl = new TemplateFromFile( $this->templatePath.'event_list_item.tpl' );

				// ---------------------------------------------------------------------------------------
				// event start date
				// ---------------------------------------------------------------------------------------
				if ($showEventDateStart) {
					$eventDateStartTpl = new TemplateFromString( '<span class="eventListItemDateStart">{EVENTLIST_ITEM_DATE_START}</span>' );
					$eventDateStartTpl->replaceTokenByContent( 'EVENTLIST_ITEM_DATE_START', date('d.m.Y', $iCal->iCalDateToUnixTimestamp($anEvent['DTSTART'])) );
					$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_DATE_START_TPL', $eventDateStartTpl->get() );
				} else {
					$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_DATE_START_TPL' );
				}

				// ---------------------------------------------------------------------------------------
				// event start time
				// ---------------------------------------------------------------------------------------
				if ($showEventTimeStart) {
					$eventTimeStartTpl = new TemplateFromString( '<span class="eventListItemTimeStart">{EVENTLIST_ITEM_TIME_START}</span>' );
					$eventTimeStartTpl->replaceTokenByContent( 'EVENTLIST_ITEM_TIME_START', date('H:i', $iCal->iCalDateToUnixTimestamp($anEvent['DTSTART'])) );
					$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_TIME_START_TPL', $eventTimeStartTpl->get() );
				} else {
					$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_TIME_START_TPL' );
				}

				// ---------------------------------------------------------------------------------------
				// event end date
				// ---------------------------------------------------------------------------------------
				if ($showEventDateEnd) {
					$eventDateEndTpl = new TemplateFromString( '<span class="eventListItemDateEnd">{EVENTLIST_ITEM_DATE_END}</span>' );
					$eventDateEndTpl->replaceTokenByContent( 'EVENTLIST_ITEM_DATE_END', date('d.m.Y', $iCal->iCalDateToUnixTimestamp($anEvent['DTEND'])) );
					$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_DATE_END_TPL', $eventDateEndTpl->get() );
				} else {
					$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_DATE_END_TPL' );
				}

				// ---------------------------------------------------------------------------------------
				// event end time
				// ---------------------------------------------------------------------------------------
				if ($showEventTimeEnd) {
					$eventTimeEndTpl = new TemplateFromString( '<span class="eventListItemTimeEnd">{EVENTLIST_ITEM_TIME_END}</span>' );
					$eventTimeEndTpl->replaceTokenByContent( 'EVENTLIST_ITEM_TIME_END', date('H:i', $iCal->iCalDateToUnixTimestamp($anEvent['DTEND'])) );
					$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_TIME_END_TPL', $eventTimeEndTpl->get() );
				}
				else						$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_TIME_END_TPL' );
				
				// ---------------------------------------------------------------------------------------
				// event summary
				// ---------------------------------------------------------------------------------------
				if (array_key_exists( 'SUMMARY', $anEvent )) {
					if ($showEventSummary) {
						$eventSummaryTpl = new TemplateFromString( '<span class="eventListItemSummary">{EVENTLIST_ITEM_SUMMARY}</span>' );
						$eventSummaryTpl->replaceTokenByContent( 'EVENTLIST_ITEM_SUMMARY', stripslashes_deep($anEvent['SUMMARY']) );
						$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_SUMMARY_TPL', $eventSummaryTpl->get() );
					} else {
						$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_SUMMARY_TPL' );
					}
				} else {
					$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_SUMMARY_TPL' );
				}

				// ---------------------------------------------------------------------------------------
				// event description
				// ---------------------------------------------------------------------------------------
				if (array_key_exists( 'DESCRIPTION', $anEvent )) {
					if ($showEventDescription) {
						$eventDescriptionTpl = new TemplateFromString( '<span class="eventListItemDescription">{EVENTLIST_ITEM_DESCRIPTION}</span>' );
						$eventDescriptionTpl->replaceTokenByContent( 'EVENTLIST_ITEM_DESCRIPTION', stripslashes_deep($anEvent['DESCRIPTION']) );
						$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_DESCRIPTION_TPL', $eventDescriptionTpl->get() );
					} else {
						$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_DESCRIPTION_TPL' );
					}
				} else {
					$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_DESCRIPTION_TPL' );
				}

				// ---------------------------------------------------------------------------------------
				// event location
				// ---------------------------------------------------------------------------------------
				if (array_key_exists( 'LOCATION', $anEvent )) {
					if ($showEventLocation) {
						$eventLocationTpl = new TemplateFromString( '<span class="eventListItemLocation">'. esc_html__( 'Location', 'ical_events' ) .': {EVENTLIST_ITEM_LOCATION}</span>' );
						$eventLocationTpl->replaceTokenByContent( 'EVENTLIST_ITEM_LOCATION', stripslashes_deep($anEvent['LOCATION']) );
						$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_LOCATION_TPL', $eventLocationTpl->get() );
					} else {
						$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_LOCATION_TPL' );
					}
				} else {
					$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_LOCATION_TPL' );
				}

				// ---------------------------------------------------------------------------------------
				// event url
				// ---------------------------------------------------------------------------------------
				if (array_key_exists( 'URL', $anEvent )) {
					if ($showEventURL) {
						$spch = ':';
						$eventURLTpl = new TemplateFromString( '<span class="eventListItemURL"><a href="{EVENTLIST_ITEM_URL}" target="_blank">{EVENTLIST_ITEM_URL}</a></span>' );
						$eventURLTpl->replaceTokenByContent( 'EVENTLIST_ITEM_URL', $anEvent['URL'] );
						$eventListItemTpl->replaceTokenByContent( 'EVENTLIST_ITEM_URL_TPL', $eventURLTpl->get() );
					} else {
						$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_URL_TPL' );
					}
				} else {
					$eventListItemTpl->deleteToken( 'EVENTLIST_ITEM_URL_TPL' );
				}

				// append the string of the current item to the list of items
				$eventListItems .= $eventListItemTpl->get();
				$eventListItemTpl = NULL;
				
				$eventCounter++;
				if ($eventCounter >= (int)$showNrOfEvents)
					break;
			}
			
			// replace the event list item token by real content
			$eventListTpl = new TemplateFromFile( $this->templatePath.'event_list.tpl' );
			$eventListTpl->replaceTokenByContent( 'EVENTLIST_ITEMS',  $eventListItems);
			$eventListTpl->show();
			$eventListItems = '';
		}
		print( $after_widget );
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
    function form( $instance ) {
	    
        // setting up stored values for all widget options
        if ( $instance ) {
			$title					= $instance[ 'title' ];
			$iCalURI				= $instance[ 'iCalURI' ];
			$showNrOfEvents			= $instance[ 'showNrOfEvents' ];
			$showEventDateStart		= $instance[ 'showEventDateStart' ];
			$showEventDateEnd		= $instance[ 'showEventDateEnd' ];
			$showEventTimeStart		= $instance[ 'showEventTimeStart' ];
			$showEventTimeEnd		= $instance[ 'showEventTimeEnd' ];
			$showEventSummary		= $instance[ 'showEventSummary' ];
			$showEventDescription	= $instance[ 'showEventDescription' ];
			$showEventLocation		= $instance[ 'showEventLocation' ];
			$showEventURL			= $instance[ 'showEventURL' ];
			$showEventRangeDateFrom	= $instance[ 'showEventRangeDateFrom' ];
			$showEventRangeDateTo	= $instance[ 'showEventRangeDateTo' ];
			
		// setting up default values for all widget options
		} else {
			$title					= esc_html__( 'Events', 'ical_events' );
			$iCalURI				= '';
			$showNrOfEvents			= '5';			// show the next 5 events by default
			$showEventDateStart		= true;			// 0/false = no, 1/true = yes
			$showEventDateEnd		= false;		// 0/false = no, 1/true = yes
			$showEventTimeStart		= true;			// 0/false = no, 1/true = yes
			$showEventTimeEnd		= false;		// 0/false = no, 1/true = yes
			$showEventSummary		= true;			// 0/false = no, 1/true = yes
			$showEventDescription	= true;			// 0/false = no, 1/true = yes
			$showEventLocation		= false;		// 0/false = no, 1/true = yes
			$showEventURL			= false;		// 0/false = no, 1/true = yes
			$showEventRangeDateFrom	= '';
			$showEventRangeDateTo	= '';
		}
		
		// build an asoc array with all of our options as items
		$widgetOptions = array(

			// ---------------------------------------------------------------------
			// list of textfields
			// ---------------------------------------------------------------------
			array(
				'field_id'		=> 'title',
				'field_name'	=> 'title',
				'field_label'	=> esc_html__( 'Title', 'ical_events' ),
				'field_title'	=> esc_html__( 'When empty, the widget title will be derived from the iCal calendar name.', 'ical_events' ),
				'field_type'	=> 'textfield',
				'field_css'		=> 'widefat',
				'field_value'	=> $title
			),
			array(
				'field_id'		=> 'iCalURI',
				'field_name'	=> 'iCalURI',
				'field_label'	=> esc_html__( 'iCal URL', 'ical_events' ),
				'field_title'	=> esc_html__( 'Enter a URL to an iCal calendar. It *MUST* start with \'http://\', \'https://\' or \'webcal://\'. ', 'ical_events' ),
				'field_type'	=> 'textfield',
				'field_css'		=> 'widefat',
				'field_value'	=> $iCalURI,
			),
			array(
				'field_id'		=> 'showNrOfEvents',
				'field_name'	=> 'showNrOfEvents',
				'field_label'	=> esc_html__( 'Amount of shown items', 'ical_events' ),
				'field_title'	=> esc_html__( 'Enter a numeric value here. It represents the number of the events that will be shown in the widget.', 'ical_events' ),
				'field_type'	=> 'textfield',
				'field_css'		=> 'widefat',
				'field_value'	=> $showNrOfEvents
			),
			array(
				'field_type'	=> 'separator'
			),
			array(
				'field_type'	=> 'note',
				'field_value'	=> esc_html__( 'Select a date range here. The format of the given date must be "YYYY-MM-DD".', 'ical_events' )
			),
			array(
				'field_id'		=> 'showEventRangeDateFrom',
				'field_name'	=> 'showEventRangeDateFrom',
				'field_label'	=> esc_html__( 'Show Events from', 'icalevents' ),
				'field_title'	=> esc_html__( 'If you leave this setting empty, the current date will be used.', 'ical_events' ),
				'field_type'	=> 'datepicker',
				'field_css'		=> 'rangeDateFrom',
				'field_value'	=> $showEventRangeDateFrom
			),
			array(
				'field_id'		=> 'showEventRangeDateTo',
				'field_name'	=> 'showEventRangeDateTo',
				'field_label'	=> esc_html__( 'Show Events to', 'icalevents' ),
				'field_title'	=> esc_html__( 'If you leave this setting empty, the 2038/12/31 will be used as the maximum date.', 'ical_events' ),
				'field_type'	=> 'datepicker',
				'field_css'		=> 'rangeDateTo',
				'field_value'	=> $showEventRangeDateTo
			),
			array(
				'field_type'	=> 'separator'
			),
			
			// ---------------------------------------------------------------------
			// list of checkboxes
			// ---------------------------------------------------------------------
			array(
				'field_id'		=> 'showEventSummary',
				'field_name'	=> 'showEventSummary',
				'field_label'	=> esc_html__( 'Show event summary (title)', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventSummary
			),
			array(
				'field_id'		=> 'showEventDateStart',
				'field_name'	=> 'showEventDateStart',
				'field_label'	=> esc_html__( 'Show start date', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventDateStart
			),
			array(
				'field_id'		=> 'showEventTimeStart',
				'field_name'	=> 'showEventTimeStart',
				'field_label'	=> esc_html__( 'Show start time', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventTimeStart
			),
			array(
				'field_id'		=> 'showEventDateEnd',
				'field_name'	=> 'showEventDateEnd',
				'field_label'	=> esc_html__( 'Show end date', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventDateEnd
			),
			array(
				'field_id'		=> 'showEventTimeEnd',
				'field_name'	=> 'showEventTimeEnd',
				'field_label'	=> esc_html__( 'Show end time', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventTimeEnd
			),
			array(
				'field_id'		=> 'showEventDescription',
				'field_name'	=> 'showEventDescription',
				'field_label'	=> esc_html__( 'Show description', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventDescription
			),
			array(
				'field_id'		=> 'showEventLocation',
				'field_name'	=> 'showEventLocation',
				'field_label'	=> esc_html__( 'Show location', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventLocation
			),
			array(
				'field_id'		=> 'showEventURL',
				'field_name'	=> 'showEventURL',
				'field_label'	=> esc_html__( 'Show a given URL', 'ical_events' ),
				'field_title'	=> esc_html__( 'Yes/No', 'ical_events' ),
				'field_type'	=> 'checkbox',
				'field_css'		=> '',
				'field_value'	=> $showEventURL
			),
		);
		
		$closePTag = true;
		foreach($widgetOptions as $option) {
			switch ($option['field_type']) {
				case 'separator':
					print('<hr class="horizontalRule" />');
				break;
				
				case 'note':
					print('<div class="icaleventsNote">'.$option['field_value'].'</div>');
				break;

				case 'checkbox':
					$checkBoxTemplate = new TemplateFromFile( $this->templatePath.'admin_option_checkbox.tpl' );
					$checkBoxTemplate->replaceTokenByContent( 'OPTION_ITEM_ID',				$this->get_field_id( $option['field_id'] ) );
					$checkBoxTemplate->replaceTokenByContent( 'OPTION_ITEM_NAME',			$this->get_field_name( $option['field_name'] ) );
					$checkBoxTemplate->replaceTokenByContent( 'OPTION_ITEM_VALUE',			$option['field_value'] );
					$checkBoxTemplate->replaceTokenByContent( 'OPTION_ITEM_LABEL',			$option['field_label'] );
					$checkBoxTemplate->replaceTokenByContent( 'OPTION_ITEM_TITLE',			$option['field_title'] );
					$checkBoxTemplate->replaceTokenByContent( 'OPTION_ITEM_CSS_CLASS',		$option['field_css'] );
					$checkBoxTemplate->replaceTokenByContent( 'OPTION_ITEM_CHECKED_FLAG',	($option['field_value'] ? 'checked="checked"' : '') );

					if ($closePTag) print('<p>');
					$checkBoxTemplate->show();
					$closePTag = false;
				break;
				
				case 'textfield':
				case 'number':
				case 'datepicker':
				default:
					$textFieldTemplate = new TemplateFromFile( $this->templatePath.'admin_option_'.$option['field_type'].'.tpl' );
					$textFieldTemplate->replaceTokenByContent( 'OPTION_ITEM_ID',			$this->get_field_id( $option['field_id'] ) );
					$textFieldTemplate->replaceTokenByContent( 'OPTION_ITEM_NAME',			$this->get_field_name( $option['field_name'] ) );
					$textFieldTemplate->replaceTokenByContent( 'OPTION_ITEM_VALUE',			$option['field_value'] );
					$textFieldTemplate->replaceTokenByContent( 'OPTION_ITEM_LABEL',			$option['field_label'] );
					$textFieldTemplate->replaceTokenByContent( 'OPTION_ITEM_TITLE',			$option['field_title'] );
					$textFieldTemplate->replaceTokenByContent( 'OPTION_ITEM_CSS_CLASS',		$option['field_css'] );
					$textFieldTemplate->replaceTokenByContent( 'OPTION_ITEM_FIELD_TYPE',	'text' );

					if (!$closePTag) print('</p>');		// switch from checkbox to textfield
					print('<p>');
					
					// if we have a datepicker
					// there are a few more things to do
					if ($option['field_type'] == 'datepicker') {
						
						// get a message template if we need it for an error
						$errorMessageTemplate = new TemplateFromFile( $this->templatePath.'admin_option_message.tpl' );
						$errorMessageTemplate->replaceTokenByContent( 'OPTION_ITEM_ID', $this->get_field_id( $option['field_id'] ).'_errorMessage' );
						$errorMessageTemplate->replaceTokenByContent( 'OPTION_ITEM_CSS_CLASS', 'icaleventsErrorMessage' );
						$errorMessageTemplate->replaceTokenByContent( 'OPTION_ITEM_ERRORMESSAGE', esc_html__( 'This is NOT a valid date format!', 'ical_events' ) );
						
						print('
			<script>
				jQuery(document).ready(function(){
					jQuery("#'.$this->get_field_id( $option['field_id'] ).'")
					.datepicker({
						dateFormat: "yy-mm-dd",
						showWeek: true,
						firstDay: 1,
						showOn: "button",
						buttonImage: "'.plugins_url(basename(dirname(__FILE__))).'/images/calendar_icon.gif",
						buttonImageOnly: true
					})
					.change(function() {
						var parsedDate = Date.parse( jQuery(this).val() );
						if ( parsedDate == null && jQuery(this).val() != "" ) {
							jQuery("#'.$this->get_field_id( $option['field_id'] ).'_errorMessage").fadeIn().delay(3000);
							jQuery(this).focus();
							jQuery("#'.$this->get_field_id( $option['field_id'] ).'_errorMessage").fadeOut();
						} else {
							jQuery("#'.$this->get_field_id( $option['field_id'] ).'_errorMessage").fadeOut();
						}
					});
				});
			</script>
						');
						$errorMessageTemplate->show();
					}
					$textFieldTemplate->show();
					$closePTag = true;
				break;
			}
			if ($closePTag) print('</p>');				// if the last option in loop was a textfield
		}
		if (!$closePTag) print('</p>');					// if the last option was a checkbox field
	}
	
	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
    function update( $new_instance, $old_instance ) {
        // processes widget options to be saved
        $instance = $old_instance;
		$instance['title']					= trim(apply_filters( 'widget_title', sanitize_text_field($new_instance['title'])));
		$instance['iCalURI']				= trim($new_instance['iCalURI']);
		$instance['showNrOfEvents']			= sanitize_text_field(preg_replace('/[^0-9]/', '', $new_instance['showNrOfEvents']));
		$instance['showEventRangeDateFrom']	= sanitize_text_field($new_instance['showEventRangeDateFrom']);
		$instance['showEventRangeDateTo']	= sanitize_text_field($new_instance['showEventRangeDateTo']);
		$instance['showEventDateStart']		= sanitize_text_field($new_instance['showEventDateStart']);
		$instance['showEventDateEnd']		= sanitize_text_field($new_instance['showEventDateEnd']);
		$instance['showEventTimeStart']		= sanitize_text_field($new_instance['showEventTimeStart']);
		$instance['showEventTimeEnd']		= sanitize_text_field($new_instance['showEventTimeEnd']);
		$instance['showEventSummary']		= sanitize_text_field($new_instance['showEventSummary']);
		$instance['showEventDescription']	= sanitize_text_field($new_instance['showEventDescription']);
		$instance['showEventLocation']		= sanitize_text_field($new_instance['showEventLocation']);
		$instance['showEventURL']			= sanitize_text_field($new_instance['showEventURL']);
		return $instance;
    }
	
}

add_action('widgets_init', function(){
	register_widget( 'iCal_Events' );
});

?>