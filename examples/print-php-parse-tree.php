#!/usr/bin/env php
<?php

require_once(dirname(__FILE__).'/../libs/PhpLexer.php');
require_once(dirname(__FILE__).'/../libs/PhpParser.php');
require_once(dirname(__FILE__).'/../libs/EventDispatcherHolder.php');

if ($_SERVER['argc'] <= 1) {
    echo "usage: ./show-php-parse-tree.php [php program]\n";
    exit(0);
}
$input_file = $_SERVER['argv'][1];
if (!file_exists($input_file)) {
    echo "error: no such file\n";
    exit(1);
}

EventDispatcherHolder::getDispatcher()->connect('start', 'output_dot_from_parser_tree');
$l = new PhpLexer();
$l->setPhpSource(file_get_contents($input_file));
$p = new PhpParser();
$p->yyparse($l);

function output_dot_from_parser_tree(sfEvent $ev)
{
    $node = $ev->getSubject();
    output_header();
    output_node_info($node);
    output_children($node);
    output_footer();
}

function output_header()
{
    echo 'digraph sample {
  node [shape = record, style = filled,
        fontname = "Monaco", fontsize = 12, fontcolor = black];
';
}

function output_footer()
{
    echo "}\n";
}

function output_node_info($node)
{
    $labels =  array();
    if (is_object($node->zendToken)) {
        // leaf
        $labels[] = $node->zendToken->phpPiece;
        if (is_integer($node->zendToken->tokenValue) ||
            ($node->zendToken->phpPiece !== $node->zendToken->tokenName)) {
            $labels[] = $node->zendToken->tokenName;
        }
    } else {
        // not leaf
        $labels[] = $node->tokenName;
    }
    foreach ($labels as &$label) {
        $label = preg_replace('/([{}\"\\\\])/', '\\\\$1', $label);
    }
    printf('  obj_%s [ label = "%s" ];'.PHP_EOL,
           spl_object_hash($node), implode('\n', $labels));
}

function output_children($node)
{
    $node_hash = spl_object_hash($node);
    if (is_array($node->children)) {
        foreach ($node->children as $child_node) {
            printf('  obj_%s -> obj_%s [ dir = back ];'.PHP_EOL,
                   $node_hash, spl_object_hash($child_node));
            output_node_info($child_node);
            output_children($child_node);
        }
    }
}