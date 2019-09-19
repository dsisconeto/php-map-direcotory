<?php
require_once __DIR__ . '/Node.php';

$root = new Node(
    new SplFileInfo('')
);

file_put_contents('tree.json', json_encode($root->toMap()));



