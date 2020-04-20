<?php

function es_getexpire() {
    $future = date("Y/m/d", strtotime(" +3 months"));
    $retval = substr($future, 2, 2) . substr($future, 5, 2);
    return $retval;
}

// ('testing', '12345') => '2005_975c9677'
function es_makepw($user, $secret) {
    $expire = es_getexpire();
    $base = $user . '_' . $expire . '_' . $secret;
    $sig = hash('sha256', $base);
    $pw = $expire . '_' . substr($sig, 0, 8);
    return($pw);
}


echo("A typical user account is like pg4e_645d4d2\n\n");
$user = readline('Enter user: ');
$password = readline('Enter ES server password: ');

$pass = es_makepw($user, $password);

echo("Pass: $pass\n");
