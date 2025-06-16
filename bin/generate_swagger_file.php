<?php
require(getcwd()."/../vendor/autoload.php");

$json = \Swagger\scan(getcwd().'/../src/');

//$yaml = (array)json_decode($swagger.' ',true);
//$yaml = Yaml::dump($yaml,100,2);
file_put_contents(getcwd().'/swagg.json' ,$json);

echo('The swagg file has been created'.PHP_EOL);
