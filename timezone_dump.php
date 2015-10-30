<?php

//This repo is hopefully a short-lived need. If PHP & Rails put their heads together, we can maybe have a native PHP solution:
//https://github.com/rails/rails/issues/22088
//https://bugs.php.net/bug.php?id=70801

//This is the file I use to update this repo's tzid.json file. 
//You can run it yourself if you want to too.
// *** DO NOT RUN IT IN PRODUCTION, FOR CRYING OUT LOUD. ***

$zoneRbFile='https://raw.githubusercontent.com/rails/rails/master/activesupport/lib/active_support/values/time_zone.rb';
$zoneRbLines = file($zoneRbFile);
if(count($zoneRbLines)) {
    $removeThese = array(
        'America/Indiana/Indianapolis'  //Its been E%T since 2006. No more special treatment.
    );
    $tzArray = array();
    $mapping_started = false;
    $mapping_ended = false;
    foreach($zoneRbLines as $zoneRbLine) {
        if($mapping_started && strpos($zoneRbLine, '}')) {
            $mapping_ended = true;
        }
        if($mapping_started && !$mapping_ended) {
            list($railsName, $tzInfoIdentifier) = explode('=>',$zoneRbLine);
            if(!in_array($tzInfoIdentifier,$removeThese)) {
                $railsName = trim($railsName,' "');
                $tzInfoIdentifier = trim($tzInfoIdentifier,"\" ,\n");
                $tzArray[$railsName] = $tzInfoIdentifier;
            }
        }
        if(strpos($zoneRbLine,'MAPPING')) {
            $mapping_started = true;
        }
    }

	//Add these time zones. Rails didn't include them. We want them.
    $addThese = array(
        'Puerto Rico' => 'America/Puerto_Rico', //easy call, population over 2.5M
        'Aleutian Islands' => 'America/Adak'    //questionable, population: slightly over 8K.
    );
    foreach($addThese as $name => $tzInfoIdentifier) {
        if(!in_array($tzInfoIdentifier, $tzArray)) {
            $tzArray[$name] = $tzInfoIdentifier;
        }
    }

    $tzJson = json_encode($tzArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    file_put_contents(__DIR__.'/tzid.json', $tzJson);
}