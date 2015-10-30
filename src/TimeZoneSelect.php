<?php namespace ScottConnerly\TimeZone;

use DateTime;
use DateTimeZone;

class TimeZoneSelect {
    
    //$p for params
    public static function get_select($p = []) {
        //Default options
        if(empty($p['name'])) $p['name'] = 'time_zone';
        
        //Build it
        $output = "<select name='$p[name]'";
        if(!empty($p['class'])) $output .= " class='$p[class]'";
        if(!empty($p['id'])) $output .= " id='$p[id]'";
        if(!empty($p['data'])) foreach($p['data'] as $k => $v) $output .= " data-$k='$v'";
        $output .= '>';
    
        $output .= static::get_options_for_select($p).'</select>';
        
        return $output;
    }
    
    public static function get_options_for_select($p) {
        if(!empty($p['country'])) {
            $p['priority_zones'] = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $p['country']);
            //$p['priority_label'] = locale_get_display_region('-'.$p['country'], 'en');
            $p['priority_label'] = $p['country'];
        }
        if(empty($p['priority_label'])) $priority_label = 'Regional';
        
        $tzData = static::get_time_zones($p);  

        //TODO: add the ability to CONDITIONALLY have this empty <option> at the start, and change its text
        $output = "<option value=''>Select Time Zone</option>";

        if(!empty($p['priority_zones'])) {
            if(!is_array($p['priority_zones']))
                $p['priority_zones'] = array($p['priority_zones']);

            foreach($tzData as $k => $v) {
                if(in_array($v['identifier'], $p['priority_zones'])) {
                    $priorityData[] = $v;
                    unset($tzData[$k]);
                }
            }
                
            $output .= "<optgroup label='$p[priority_label]'>";
            foreach($priorityData as $priorityDatum) {
                $output .= static::make_zone_option($priorityDatum, $p);
            }
            $output .= "</optgroup>";
            $output .= "<optgroup label='International'>";
        }
        
        foreach($tzData as $tzDatum) {
            $output .= static::make_zone_option($tzDatum, $p);
        }
        
        if(!empty($p['priority_zones'])) {
            $output .= "</optgroup>";
        }
        
        return $output;
    }
    
    protected static function make_zone_option($tzDatum, $p) {
        $output = '<option';
        if($p['selected'] == $tzDatum['identifier'] && $tzDatum['primary'])
            $output .= ' selected';
        $output .= ">$tzDatum[alias]</option>";                    
        return $output;
    }
    
    public static function get_time_zones($p) {
        $identifiers = array();
        $tzJson = file_get_contents(__DIR__."/../tzid.json");
        $tzs = json_decode($tzJson);
        
        $defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        foreach($tzs as $nameDisplay => $tzinfoIdentifier) {
            $offsetSeconds = timezone_offset_get( new DateTimeZone($tzinfoIdentifier), new DateTime() );

            $offsetDirection = ($offsetSeconds >= 0) ? '+' : '-' ;
            $offsetSecondsForFormat = abs($offsetSeconds);
            $offsetHours = str_pad(floor($offsetSecondsForFormat / 3600), 2, '0', STR_PAD_LEFT);
            $offsetMinutes = str_pad(floor(($offsetSecondsForFormat / 60) % 60), 2, '0', STR_PAD_LEFT);
            $offsetFormatted = $offsetDirection.$offsetHours.':'.$offsetMinutes;

            //there could be multiple (in no reliable order), so lets pick the best match, in a fuzzy way.
            list($major,$minor) = explode('/', $tzinfoIdentifier, 2);
            $levenshtein = levenshtein($nameDisplay, $minor);
            //although, I did like this too: http://stackoverflow.com/a/5430851/218967
            $otherTzWithIdentifier = array_keys($identifiers,$tzinfoIdentifier);
            $tzIsBestFitForIdentifier = 1;
            if(!empty($otherTzWithIdentifier)) {
                foreach($otherTzWithIdentifier as $otherTz) {
                    if($levenshtein < $tzData[$otherTz]['levenshtein']) {
                        $tzData[$otherTz]['primary'] = 0;
                    }
                    else $tzIsBestFitForIdentifier = 0;
                }
            }

            $tzData[] = array(
                'alias'             => '(GMT'.$offsetFormatted.') '.$nameDisplay,
                'identifier'        => $tzinfoIdentifier,
                'name_display'      => $nameDisplay,
                'offset_seconds'    => $offsetSeconds,
                'offset_formatted'  => $offsetFormatted,
                'levenshtein'       => $levenshtein,
                'primary'           => $tzIsBestFitForIdentifier
            );
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
    }
}
