<?php

namespace ScottConnerly\TimeZone;

use DateTime;
use DateTimeZone;

class TimeZoneSelect {
    
    public static function get_select_html($params = []) {
        //Default options
        if (empty($params['name'])) $params['name'] = 'time_zone';
        if (empty($params['empty_option_text'])) $params['empty_option_text'] = 'Select Time Zone';
        if (!isset($params['show_empty_option'])) $params['show_empty_option'] = true;
        
        
        //Build it
        $output = "<select name='$params[name]'";
        if (!empty($params['class'])) $output .= " class='$params[class]'";
        if (!empty($params['id'])) $output .= " id='$params[id]'";
        if (!empty($params['data'])) foreach($params['data'] as $k => $v) $output .= " data-$k='$v'";
        $output .= '>';
    
        $output .= static::get_options_for_select($params).'</select>';
        
        return $output;
    }
    
    public static function get_options_for_select($params = []) {
        $output = '';
        $priorityData = [];

        if (!empty($params['country'])) {
            $params['priority_zones'] = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $params['country']);
            //$params['priority_label'] = locale_get_display_region('-'.$params['country'], 'en');
            $params['priority_label'] = $params['country'];
        }
        if (empty($params['priority_label'])) $priority_label = 'Regional';
        
        $tzData = static::get_time_zones($params);  

        if ($params['show_empty_option']) {
            $output = "<option value=''>$params[empty_option_text]</option>";
        }

        if (!empty($params['priority_zones'])) {
            if(!is_array($params['priority_zones']))
                $params['priority_zones'] = array($params['priority_zones']);

            foreach($tzData as $k => $v) {
                if(in_array($v['identifier'], $params['priority_zones'])) {
                    $priorityData[] = $v;
                    unset($tzData[$k]);
                }
            }
                
            $output .= "<optgroup label='$params[priority_label]'>";
            foreach($priorityData as $priorityDatum) {
                $output .= static::make_zone_option($priorityDatum, $params);
            }
            $output .= "</optgroup>";
            $output .= "<optgroup label='International'>";
        }
        
        foreach ($tzData as $tzDatum) {
            $output .= static::make_zone_option($tzDatum, $params);
        }
        
        if (!empty($params['priority_zones'])) {
            $output .= "</optgroup>";
        }
        
        return $output;
    }
    
    protected static function make_zone_option($tzDatum, $params = []) {
        $output = '<option value="' . $tzDatum['identifier'] . '"';
        if($params['selected'] == $tzDatum['identifier'] && $tzDatum['primary'])
            $output .= ' selected';
        $output .= ">$tzDatum[alias]</option>";                    
        return $output;
    }
    
    public static function get_time_zones($params = []) {
        // Prep varrs and arrays that wil lbe accessed but not yet created
        $identifiers = array();
        $tzData = [];
        $tzJson = file_get_contents(__DIR__."/../tzid.json");
        $tzs = json_decode($tzJson);
        
        $defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC'); //Is this problematic for larger systems to load this file? Should we return it to its value?
        $i = 0;
        foreach($tzs as $nameDisplay => $tzinfoIdentifier) {
            $offsetSeconds = timezone_offset_get( new DateTimeZone($tzinfoIdentifier), new DateTime() );
            $offsetFormatted = static::formatOffset($offsetSeconds);

            //there could be multiple (in no reliable order), so lets pick the best match, in a fuzzy way.
            list($major, $minor) = explode('/', $tzinfoIdentifier, 2);
            $levenshtein = levenshtein($nameDisplay, $minor);
            //although, I did like this too: http://stackoverflow.com/a/5430851/218967
            $otherTzWithIdentifier = array_keys($identifiers, $tzinfoIdentifier);
            $tzIsBestFitForIdentifier = 1;
            if (!empty($otherTzWithIdentifier)) {
                foreach ($otherTzWithIdentifier as $otherTz) {
                    // RG - Check for the presence of the sub-array first
                    if (isset($tzData[$otherTz]) && $levenshtein < $tzData[$otherTz]['levenshtein']) {
                        $tzData[$otherTz]['primary'] = 0;
                    }
                    else {
                        $tzIsBestFitForIdentifier = 0;
                    }
                }
            }

            $tzData[] = [
                'alias'             => '(GMT'.$offsetFormatted.') '.$nameDisplay,
                'identifier'        => $tzinfoIdentifier,
                'name_display'      => $nameDisplay,
                'offset_seconds'    => $offsetSeconds,
                'offset_formatted'  => $offsetFormatted,
                'levenshtein'       => $levenshtein,
                'primary'           => $tzIsBestFitForIdentifier
            ];
            $offsetSort[] = $offsetSeconds;
            $originalSort[] = $i;
            $identifiers[] = $tzinfoIdentifier;

            $i++;
        }
        
        //sort them
        //Only upset their original order if their offsets are out of order.
        //without multisorting on $originalSort, 'International Date Line West' was no longer the first item.
        array_multisort($offsetSort, SORT_NUMERIC, $originalSort, $tzData);
        
        date_default_timezone_set($defaultTimeZone);
        
        return $tzData;
        // TODO This could be cached
    }
    
    private static function formatOffset($offsetSeconds) {
        
        $offsetDirection = ($offsetSeconds >= 0) ? '+' : '-' ;
        $offsetSecondsForFormat = abs($offsetSeconds);
        
        $offsetHours = str_pad(floor($offsetSecondsForFormat / 3600), 2, '0', STR_PAD_LEFT);
        $offsetMinutes = str_pad(floor(($offsetSecondsForFormat / 60) % 60), 2, '0', STR_PAD_LEFT);
        
        $offsetFormatted = $offsetDirection. $offsetHours. ':'. $offsetMinutes;
        
        return $offsetFormatted;
    }

    public static function get_select($params)
    {
        //Deprecated
        // RG - Fixed reference to static method
        return self::get_select_html($params);
    }
}
