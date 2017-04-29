<?php

// OCMOD updater.

$this->db->query("DELETE FROM `" . DB_PREFIX . "modification` WHERE `name` LIKE '%ImportMod by StasyanG%'");