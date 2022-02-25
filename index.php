<?php

const TBL_OPCODE = 0;
const TBL_CYCLES = 1;
const TBL_CMD = 2;
const TBL_ARG1 = 3;
const TBL_ARG2 = 4;

CONST TBL_00_OFFSET = 0;
CONST TBL_CE_OFFSET = 4;
CONST TBL_CF_OFFSET = 8;

$tbl = array_slice(
            array_map('str_getcsv', file('input.csv')),
            1);

const ARG_BITS = 0;
const ARG_IMMEDIATE = 1;

const ARG_REPLACER = 3;

const ARGUMENTS = [
  'A' => [8, False, False, ' a'],
  'B' => [8, False, False, ' b'],
  'L' => [8, False, False, ' l'],
  'H' => [8, False, False, ' h'],
  'BR' => [8, False, False, ' br'],
  'SC' => [8, False, False, ' sc'],
  'EP' => [8, False, False, ' ep'],
  'XP' => [8, False, False, ' xp'],
  'YP' => [8, False, False, ' yp'],
  'NB' => [8, False, False, ' nb'],

  'BA' => [16, False, False, ' ba'],
  'HL' => [16, False, False, ' hl'],
  'IX' => [16, False, False, ' ix'],
  'IY' => [16, False, False, ' iy'],
  'SP' => [16, False, False, ' sp'],
  'PC' => [16, False, False, ' pc'],
  'IP' => [-1, false, false, ' IP'],

  '#nn' =>   [8, True, False, ' #*08'],
  'rr' =>    [8, True, False, ' *08'],
  '#mmnn' => [16, True, False, ' #*16'],
  'qqrr' =>  [16, True, False, ' *16'],

  '[kk]' =>   [8, True, True, ' [*08]'], # Special
  '[hhll]' => [16, True, True, ' [*16]'],
  '[HL]' =>   [-1, False, True, ' [hl]'],
  '[IX]' =>   [-1, False, True, ' [x]'],
  '[IY]' =>   [-1, False, True, ' [y]'],
  '[BR:ll]' => [8, True, True, ' [br:*08]'],
//  '[SP+dd]' => [-1, True, True, 'indDSP'],
//  '[IX+dd]' => [-1, True, True, 'indDIX'],
//  '[IY+dd]' => [-1, True, True, 'indDIY'],
//  '[IX+L]' =>  [-1, True, True, 'indIIX'],
//  '[IY+L]' =>  [-1, True, True, 'indIIY'],

// replacers
  'Z' => [-1, False, false, 'z'],
  'NZ' => [-1, False, false, 'nz'],
  'C' => [-1, False, false, 'c'],
  'NC' => [-1, False, false, 'nc'],
  'LT' => [-1, False, false, 'lt'], // sure?
  'LE' => [-1, False, false, 'le'], // sure?
  'GT' => [-1, False, false, 'gt'], // sure?
  'GE' => [-1, False, false, 'ge'], // sure?
];

$codes = [];
foreach($tbl as $item) {
  foreach([TBL_00_OFFSET, TBL_CE_OFFSET] as $offset) {
    $k = $item[TBL_CMD+$offset];
    if($k == 'undefined') {
      print("skipp $offset:".$item[TBL_OPCODE]."\n");
      continue;
    }

    if(!array_key_exists($k, $codes)) {
      $codes[$k] = [];
    }

    // bits
    $bits = 0;
    if($item[TBL_ARG1+$offset]) $a1 = ARGUMENTS[$item[TBL_ARG1+$offset]];
    if($item[TBL_ARG2+$offset]) $a2 = ARGUMENTS[$item[TBL_ARG2+$offset]];
    if($a1 && $a1[ARG_IMMEDIATE]) $bits = max($bits, $a1[ARG_BITS]);
    if($a2 && $a2[ARG_IMMEDIATE]) $bits = max($bits, $a2[ARG_BITS]);

    $code = $item[TBL_OPCODE];
    if($offset == TBL_CE_OFFSET) $code = 'CE'.$code;
    else if($offset == TBL_CF_OFFSET) $code = 'CF'.$code;

    $store = [
      TBL_OPCODE => $item[TBL_OPCODE], //TODO!
      TBL_CYCLES => $item[TBL_CYCLES+$offset],
      TBL_CMD => $item[TBL_CMD+$offset],
      TBL_ARG1 => $item[TBL_ARG1+$offset],
      TBL_ARG2 => $item[TBL_ARG2+$offset],
      'bits' => $bits,
    ];
    $codes[$k][] = $store;
  }
}

foreach($codes as $code) {
  usort($code, function($a, $b) {
    return $a['bits'] < $b['bits'] ? 1 : -1;
  });

  foreach($code as $item) {
    if($item[TBL_CYCLES]=='0') continue;
    $ls = '';
    $rs = '';
    $params = [];
    $debug = true;

    // left side
    $ls = strtolower($item[TBL_CMD]);

    foreach([TBL_ARG1, TBL_ARG2] as $i) {
      $matches = null;
      $str = $item[$i];
      //if($str=='NZ') $debug = true;

      if(strlen($str)==0) continue;
      if(!array_key_exists($str, ARGUMENTS)) {
        $ls .= ' todo';
        $debug = true;
        continue;
      }

      $arg = ARGUMENTS[$str];
      if($arg[ARG_IMMEDIATE]==true) {
        $bits = $arg[ARG_BITS];
        
        $ls .= $arg[ARG_REPLACER];
        if($arg[ARG_IMMEDIATE]) $params[] = '=a';

      } else {
        $ls .= ' ' . strtolower($str);
      }
    }

    // right side
    $oc = str_pad($item[TBL_OPCODE], 2, '0', STR_PAD_LEFT);
    $rs = '$'.$oc.' '.implode(' ', $params);

    // $full def line:
    $all = $item[TBL_CMD].' '.$item[TBL_ARG1].' '.$item[TBL_ARG2];
    $ls = str_pad($ls, 16);
    $rs = str_pad($rs, 16);

    //if($debug) print($ls . ";" . $rs . "// $all \n");
  }
}

