<?php
$dom = new DOMDocument;
$dom->load('jats/temp.xml');
if ($dom->validate()) {
    echo "This document is valid!\n";
}
?>
