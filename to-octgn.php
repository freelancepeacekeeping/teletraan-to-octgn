<?php

// https://stackoverflow.com/questions/18206851/com-create-guid-function-got-error-on-server-side-but-works-fine-in-local-usin/18206984
function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }
    else {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = md5(uniqid(rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
               .substr($charid, 8, 4).$hyphen
               .substr($charid,12, 4).$hyphen
               .substr($charid,16, 4).$hyphen
               .substr($charid,20,12);
        return $uuid;
    }
}

// 'standard, small, large, combiner'
function toSize($size) {
    switch($size) {
      case 'standard':
        return 'Large';
      case 'small':
        return 'Small';
      case 'large':
        return 'Extra Large';
      case 'combiner':
        return 'Extra Large';
      default:
        return 'Small';
    }
}

function toRarityString($rarity) {
    switch($rarity) {
      case 'C':
        return 'Common';
      case 'U':
        return 'Uncommon';
      case 'R':
        return 'Rare';
      case 'SR':
        return 'Super Rare';
    }
}

function writeProperty($writer, $propertyName, $value) {
    if($value) {
        $writer->startElement('property');
        $writer->writeAttribute('name', $propertyName);
        $writer->writeAttribute('value', $value);
        $writer->endElement();
    }
}

// Function:
//   If a character, this is their subname
//   If a stratagem, this is the target
//   If this is a battle card, it's Upgrade, Action, Secret Action
// Extra:
//   If an Upgrade, it's Armor, Weapon, Utility
function toType($row, $modenum) {
    $type = $row['type'];
    switch($type) {
      case 'battle':
        if($row['function'] == 'Upgrade') {
          return $row['function'] . ' - ' . $row['extra'];
        } else {
          return $row['function'];
        }
      case 'bot':
        return 'Character - ' . $row['mode' . $modenum] . ' Mode';
      case 'stratagem':
        return 'Stratagem';
      case 'combiner':
          return 'Character - Combiner Mode';
        return '';
    }
}

function subName($row) {
    if($row['type'] == 'bot' or $row['type'] == 'combiner') {
        return ' - ' . $row['function'];
    } else {
        return '';
    }
}

// 'battle, bot, stratagem, combiner'
function cardNumberPrefix($type) {
    switch($type) {
      case 'battle':
        return '';
      case 'bot':
        return 'T';
      case 'stratagem':
        return 'S';
      case 'combiner':
        return '';
    }
}

function addPip($wobug, $icon) {
    switch($icon) {
      case 'W':
        $wobug[0] += 1;
        break;
      case 'O':
        $wobug[1] += 1;
        break;
      case 'K':
        $wobug[2] += 1;
        break;
      case 'B':
        $wobug[3] += 1;
        break;
      case 'G':
        $wobug[4] += 1;
        break;
      case 'X':
        // ignore
        break;
    }
    return $wobug;
}
function writePip($writer, $icon1, $icon2, $icon3) {
    $wobug = [0,0,0,0,0];
    $wobug = addPip($wobug, $icon1);
    $wobug = addPip($wobug, $icon2);
    $wobug = addPip($wobug, $icon3);
    writeProperty($writer, 'White Pips', $wobug[0]);
    writeProperty($writer, 'Orange Pips', $wobug[1]);
    writeProperty($writer, 'Black Pips', $wobug[2]);
    writeProperty($writer, 'Blue Pips', $wobug[3]);
    writeProperty($writer, 'Green Pips', $wobug[4]);
}

function writeFactionAndTraits($writer, $traits) {
    if($traits) {
        $trait_array = explode(';', trim($traits, ';'));
        // Teletraan keeps the faction in the traits
        writeProperty($writer, 'Faction', $trait_array[0]);
        if(count($trait_array) > 1) {
            writeProperty($writer, 'Traits', implode(',', array_slice($trait_array, 2)));
        }
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    $writer = new XMLWriter();
    $writer->openURI('php://output');
    $writer->startDocument('1.0','UTF-8');
    $writer->setIndent(4);

    $wave_guid = getGUID();

    $writer->startElement('set');
    $writer->writeAttribute('xmlns:noNamespaceSchemaLocation', 'CardSet.xsd');
    $writer->writeAttribute('name', 'Team Bayformers - Encounter 1');
    $writer->writeAttribute('id', $wave_guid);
    $writer->writeAttribute('gameId', 'f44befce-4d6d-4fb9-a286-9585f36aece9');
    $writer->writeAttribute('gameVersion', '0.0.0.1');
    $writer->writeAttribute('version', '0.0.0.1');

    $dbh = new PDO('mysql:host=localhost;dbname=Teletraan1', 'hen', '');
    foreach($dbh->query('SELECT * FROM cards WHERE wave LIKE "%BFA%"') as $row) {


        /*
  `type` varchar(10) CHARACTER SET utf8 NOT NULL COMMENT 'battle, bot, stratagem, combiner',
  `name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `function` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT 'Bot function, BattleCard type, Stratagem target',  `extra` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT 'Bot second name, BattleCard subtype, Stratagem botID',
  `size` varchar(10) CHARACTER SET utf8 DEFAULT NULL COMMENT 'standard, small, large, combiner',
  `wave` varchar(20) CHARACTER SET utf8 NOT NULL,
  `rarity` varchar(2) CHARACTER SET utf8 DEFAULT NULL,
  `number` varchar(10) CHARACTER SET utf8 NOT NULL,
  `stars` tinyint(4) DEFAULT 0,
  `icon1` char(1) CHARACTER SET utf8 DEFAULT NULL,
  `icon1trait` varchar(20) DEFAULT NULL,
  `icon2` char(1) CHARACTER SET utf8 DEFAULT NULL,
  `icon2trait` varchar(20) DEFAULT NULL,
  `icon3` char(1) CHARACTER SET utf8 DEFAULT NULL,
  `icon3trait` varchar(20) DEFAULT NULL,
  `mode1` varchar(10) DEFAULT NULL COMMENT 'Alt, Bot, Body, Head',
  `mode1attack` tinyint(4) DEFAULT NULL,
  `mode1health` tinyint(4) DEFAULT NULL,
  `mode1defense` tinyint(4) DEFAULT NULL,
  `mode1text` text CHARACTER SET utf8 DEFAULT NULL,
  `mode1traits` varchar(75) DEFAULT NULL,
  `mode2` varchar(10) DEFAULT NULL COMMENT 'Alt, Bot, Body, Head',
  `mode2attack` tinyint(4) DEFAULT NULL,
  `mode2health` tinyint(4) DEFAULT NULL,
  `mode2defense` tinyint(4) DEFAULT NULL,
  `mode2text` text CHARACTER SET utf8 DEFAULT NULL,
  `mode2traits` varchar(75) DEFAULT NULL,
         */

        /*
<card id="ece9d083-8f2b-49f2-a96e-0bc43f01d7a2" name="Anti-Air Battery" size="Small">
<property name="Card Number" value="X:S01" />
<property name="Type" value="Stratagem" />
<property name="HP" value="+3" />
<property name="Stars" value="2" />
<property name="Faction" value="Decepticon" />
<property name="Traits" value="Truck,Ranged" />
<property name="Text" value="If your Demolisher, Devoted Decepticon would take non-attack damage from an opponent's card and is upgraded with an Armor -> Instead he takes that much damage minus 1." />
<property name="Rarity" value="Rare" />
<property name="Draft Type" value="Stratagem" />
</card>
         */


        $writer->startElement('cards');
        $writer->startElement('card');
        $writer->writeAttribute('id', getGUID());
        $writer->writeAttribute('name', $row['name'] . subName($row));
        // TODO: Need to use function to add subname if its a character
        $writer->writeAttribute('size', toSize($row['size']));
        writeProperty($writer, 'Card Number', 'X:' . cardNumberPrefix($row['type']) . $row['number']);
        writeProperty($writer, 'Type', toType($row, 1));
        writeProperty($writer, 'ATK', $row['mode1attack']);
        writeProperty($writer, 'HP', $row['mode1health']);
        writeProperty($writer, 'DEF', $row['mode1defense']);
        writeProperty($writer, 'Stars', $row['stars']);
        writePip($writer, $row['icon1'], $row['icon2'], $row['icon3']);
        writeFactionAndTraits($writer, $row['mode1traits']);
        writeProperty($writer, 'Text', $row['mode1text']);
        if($row['mode2']) {
            $writer->startElement('alternate');
            if($row['mode2'] == "Alt") {
                // Supporting triple changers
                $writer->writeAttribute('type', 'alt2');
            } elseif($row['type'] == 'stratagem') {
                $writer->writeAttribute('type', 'back');
            } else {
                $writer->writeAttribute('type', 'bot');
            }
            $writer->writeAttribute('name', $row['name'] . subName($row));
            $writer->writeAttribute('size', toSize($row['size']));
            writeProperty($writer, 'Type', toType($row, 2));
            writeProperty($writer, 'ATK', $row['mode2attack']);
            writeProperty($writer, 'HP', $row['mode2health']);
            writeProperty($writer, 'DEF', $row['mode2defense']);
            writeFactionAndTraits($writer, $row['mode2traits']);
            writeProperty($writer, 'Text', $row['mode2text']);
            $writer->endElement();
        }
        if($row['mode3']) {
            $writer->startElement('alternate');
            $writer->writeAttribute('type', 'bot');
            $writer->writeAttribute('name', $row['name'] . subName($row));
            $writer->writeAttribute('size', toSize($row['size']));
            writeProperty($writer, 'Type', toType($row, 3));
            writeProperty($writer, 'ATK', $row['mode3attack']);
            writeProperty($writer, 'HP', $row['mode3health']);
            writeProperty($writer, 'DEF', $row['mode3defense']);
            writeFactionAndTraits($writer, $row['mode3traits']);
            writeProperty($writer, 'Text', $row['mode3text']);
            $writer->endElement();
        }
        writeProperty($writer, 'Rarity', toRarityString($row['rarity']));
        $writer->endElement();
    }
    $writer->endElement();

    $dbh = null;

    $writer->endDocument();
    $writer->endDocument();
    $writer->flush();
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>
