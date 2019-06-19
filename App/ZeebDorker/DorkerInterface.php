<?php
namespace ZeebDorker;

interface DorkerInterface{
    public function setDork(string $dork);
    public function getInfo();
    public function parseData();
    public function search();
    public function parseOutput();
    public function write(String $name, String $source);
}
?>