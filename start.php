<?php

Autoloader::map(array('FreeGeoIP' => __DIR__.'/FreeGeoIP.php'));
FreeGeoIP::refresh();