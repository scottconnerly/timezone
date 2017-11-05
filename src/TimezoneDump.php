<?php

namespace ScottConnerly\TimeZone;

//This repo is hopefully a short-lived need. If PHP & Rails put their heads together, we can maybe have a native PHP solution:
//https://github.com/rails/rails/issues/22088
//https://bugs.php.net/bug.php?id=70801

//This is the file I use to update this repo's tzid.json file. 
//You can run it yourself if you want to too.
// *** DO NOT RUN IT IN PRODUCTION, FOR CRYING OUT LOUD. ***

Class TimeZoneDump {
    
    public $ignoreTZ = [];
    public $ensureTZ = [];
    
    private $initialized = false;
    private $tzList = [];
    
    private $mapping_started = false;
    private $mapping_ended = false;
    
    private function init() {
        //Rails included this, I don't think we should.
        $this->ignoreTZ[]  = 'America/Indiana/Indianapolis';            //Its been E%T since 2006. No more special treatment.
        
        //Add these time zones. Rails didn't include them. We want them.
        $this->ensureTZ['Puerto Rico']      = 'America/Puerto_Rico';    //easy call, population over 2.5M
        $this->ensureTZ['Aleutian Islands'] = 'America/Adak';           //questionable, population: slightly over 8K.
        
        //ensure overrules ignore.
    }
    
    private function loadFromRails() {
        $this->zoneRbFile = 'https://raw.githubusercontent.com/rails/rails/master/activesupport/lib/active_support/values/time_zone.rb';
        $this->zoneRbLines = file($this->zoneRbFile);
        
        if($this->zoneRbLines) {
            //cache the file locally for possible future runs
        }
        else {
            //if it exists, load the cached file, issue a warning
            
            
            //else, throw an exception
        }
    }
    
    private function parseLine($zoneRbLine) {
        if ($this->mapping_started && strpos($zoneRbLine, '}')) {
            $this->mapping_ended = true;
        }
        
        if ($this->mapping_started && !$this->mapping_ended) {
            list($railsName, $tzInfoIdentifier) = explode('=>', $zoneRbLine);
            $railsName = trim($railsName,' "');
            $tzInfoIdentifier = trim($tzInfoIdentifier, "\" ,\n");
            $this->includeTZ($railsName, $tzInfoIdentifier);
        }
        
        if (strpos($zoneRbLine, 'MAPPING')) {
            $this->mapping_started = true;
        }
    }
    
    private function includeTZ($railsName, $tzInfoIdentifier) {
        if (!in_array($tzInfoIdentifier, $this->ignoreTZ)) {
            $this->tzList[$railsName] = $tzInfoIdentifier;
        }
    }
    
    private function writeJSONFile() {
        $tzJson = json_encode($this->tzList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(__DIR__.'/../tzid.json', $tzJson);
    }
    
    public function dump() {
        
        if (!$this->initialized) {
            $this->init();
            $this->initialized = true;
        }
        
        $this->loadFromRails();
        
        if(count($this->zoneRbLines)) {
            foreach($zoneRbLines as $zoneRbLine) {
                $this->parseLine($zoneRbLine);
            }
        }
        
        foreach ($this->ensureTZ as $name => $tzInfoIdentifier) {
            if (!in_array($tzInfoIdentifier, $this->tzList)) {
                $this->tzList[$name] = $tzInfoIdentifier;
            }
        }
        
        $this->writeJSONFile();
    }    
}

$tzDump = new TimezoneDump();
$tzDump->dump();
