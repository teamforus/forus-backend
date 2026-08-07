<?php

$missed_records = include __DIR__ . '/default.php';

unset($missed_records['999993112']['geboorte']);
unset($missed_records['999993112']['leeftijd']);
unset($missed_records['999993112']['naam']['voornamen']);
unset($missed_records['999994542']['geboorte']);
unset($missed_records['999994542']['leeftijd']);
unset($missed_records['123456782']['geboorte']);
unset($missed_records['123456782']['leeftijd']);

return $missed_records;
