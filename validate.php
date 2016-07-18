<?php
$dom = new DOMDocument;
$dom->load('jats/smmr.xml');
if ($dom->validate()) {
    echo "This document is valid!\n";
}
?>
