<?php

    $text = read_file('E:\Labs\WT\Laba4\templateEngine\template.txt') ; // path to template file
    $text = config($text,'E:\Labs\WT\Laba4\templateEngine\config.txt'); // path to config file
    $text = if_else_instr($text);
    $text = loadFile($text);

    create_html_file($text);

    return 0 ;

function read_file ($path) {
    if (file_exists($path)) return (file_get_contents($path)) ;
    else {
        echo "File not found";
        return false ;
    }
}

function findInstr ($text, $regEx) {
    preg_match_all($regEx,$text,$matches, PREG_PATTERN_ORDER);
    return $matches[0] ;
}

function  loadFile ($text) {

    $regEx = "/\{FILE\s*=\s*.+\}/ " ;

    $regEx_array = array(
        "img" => "/\.(?:jp(?:e?g|e|2)|gif|png|tiff?|bmp|ico)$/i",
        "video" => "/\.(?:mpeg|ra?m|avi|mp(?:g|e|4)|mov|divx|asf|qt|wmv|m\dv|rv|vob|asx|ogm)$/i",
        "audio" => "/\.(?:mp3|wav|og(?:g|a)|flac|midi?|rm|aac|wma|mka|ape)$/i" );

    foreach (findInstr($text,$regEx) as $match) {
        $file_dir = $match ;

        $file_dir = preg_replace('/{.*?"/', '', $file_dir);
        $file_dir = preg_replace('/".*?}/', '', $file_dir);

        $tag = '';
        if (preg_match($regEx_array['img'],$file_dir)) {
            $tag = 'img';
        } else if (preg_match($regEx_array['video'],$file_dir)) {
            $tag = 'video' ;
        } else if (preg_match($regEx_array['audio'],$file_dir)) {
            $tag = 'audio' ;
        }
        if ( $tag == 'img' xor $tag == 'video' xor $tag == 'audio') {
            $end_tag = $tag ;

            $tag = '<'.$tag.' src='.$file_dir.' alt = Cant find file '.'>';
            if ($end_tag != 'img')
                $tag = $tag.' </'.$end_tag .'>' ;
        }
        else {
            if (file_exists($file_dir))
            $tag =  '<'.'p'.'>'.file_get_contents($file_dir).'<'.'/p'.'>' ;
            else  $tag = '<'.'p'.'>'.'File not Found'.'<'.'/p'.'>' ; // массивом
        }

        $match = preg_replace("/\//",'\/',preg_quote($match));
        $text = preg_replace('/'.$match.'/m', $tag, $text);

    }
    return $text ;
}

function if_else_instr ($text) {

    $reg_ex_full_if = "{\s*IF\s+\"?\s*.*?\s*\"?\s*((<)|(>)|(==)|(!=)|(<=)|(>=))\s*\"?\s*.*?\s*\"?\s*}.+?{\s*ENDIF\s*}";
    //$reg_ex_full_if = "{\s*IF.+?}.+?{\s*ENDIF\s*}";
    $regEx_if = "{\s*IF.+?}";
    $regEx_endif = "{\s*ENDIF\s*}";
    $regEx_else = "{\s*ELSE\s*}";

    $matches_full_if = findInstr($text, '/' . $reg_ex_full_if . '/');
    foreach ($matches_full_if as $match) {
        $matches_if = findInstr($match, '/' . $regEx_if . '/');

        $match_if = preg_replace('/{\s*IF\s+/', '', $matches_if);
        $match_if = preg_replace('/\s*}.*$/', '', $match_if);

        preg_match('/(<)|(>)|(==)|(!=)|(<=)|(>=)/', $match_if[0], $logic_operator,);
        $token = preg_split('/' . $logic_operator[0] . '/', $match_if[0]);

        for ($i = 0; $i < count($token); $i++) $token[$i] = trim($token[$i]);

        switch ($logic_operator[0]) {
            case '<' :
                $fl = $token[0] < $token[1];
                break;
            case '>' :
                $fl = $token[0] > $token[1];
                break;
            case '==' :
                $fl = $token[0] == $token[1];
                break; // ===?
            case '!=' :
                $fl = $token[0] != $token[1];
                break;
            case '<=' :
                $fl = $token[0] <= $token[1];
                break;
            case '>=' :
                $fl = $token[0] >= $token[1];
                break;
            default :
                return $text;
        }

        $is_else_exist = preg_match($regEx_else, $match);

        if ($is_else_exist) {
            if ($fl) {
                $part = preg_replace('/' . $regEx_if . '/', '', $match);
                $part = preg_replace('/' . $regEx_else . '.+?' . $regEx_endif . '/', '', $part);
            } else {
                $part = preg_replace('/' . $regEx_if . '.+?' . $regEx_else . '/', '', $match);
                $part = preg_replace('/' . $regEx_endif . '/', '', $part);
            }
        } elseif ($fl) {
            $part = preg_replace('/' . $regEx_if . '/', '', $match);
            $part = preg_replace('/' . $regEx_else . '.+?' . $regEx_endif . '/', '', $part);
        }

        $text = preg_replace('/' . preg_quote($match) . '/m', $part, $text, 1);

    }
    return $text;
}

function config ($text,$config_path) {

    $regEx = "/\{CONFIG\s*=\s*.+?\}/ " ; ;

    if (file_exists($config_path)) {

        $hashMap = array() ;
        foreach (file($config_path) as $line){
            $map = preg_split('/=/',$line);
            $map[0] = preg_replace('/^.*?"/','',$map[0]);
            $map[0] = preg_replace('/".*+$/','',$map[0]);
            $map[1] = preg_replace('/^.*?"/','',$map[1]);
            $map[1] = preg_replace('/".*$/','',$map[1]);
            $map[1] = preg_replace('/\n/','',$map[1]);
            $hashMap += array( $map[0] => $map[1]);
        }


        foreach (findInstr($text, $regEx) as $match) {
            $key = $match ;

            $key = preg_replace('/{\s*CONFIG.*?"/', '', $key);
            $key = preg_replace('/".*?}/', '', $key);

            $value = $hashMap[$key];

            $match = preg_replace("/\//",'\/',preg_quote($match));
            $text = preg_replace('/'.$match.'/m', $value, $text);
        }
    }
        return $text;
}

function create_html_file ($text) {
    $file = fopen("index.html","w");
    fwrite ($file,$text);
    fclose($file);
    include 'index.html';
}
