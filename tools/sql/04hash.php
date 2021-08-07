<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "text_util.php";

if ( ! pg4e_user_db_load($LAUNCH) ) return;

function dohash($txt, $offset, $zero, $print=false)
{
    if ( $print ) echo("\nEnter a string: $txt\n");
    $hv = 0;
    $pos = 0;
    for($i=0; $i<strlen($txt);$i++) {
        $let = substr($txt,$i,1);
        if ( $zero == 0 ) {
            $pos = ($pos + 1) % $offset;
        } else {
            $pos = ($pos % $offset) + 1;
        }
        if ( ( $let <= 'Z' && $let >='A' ) || ( $let <= 'z' && $let >='a' ) ) {
            $ord = ord($let);
            $hv = ($hv + ($pos * $ord)) % 1000000;
            if ( $print ) echo("$let $pos $ord $hv\n");
        } else {
            return false;
        }
    }
    if ( $print ) echo("$hv $txt\n");
    return $hv;
}

// Compute the stuff for the output
$code = getCode($LAUNCH);

$offset = ($code % 3) + 2;
$zero = ($code % 2);
// $zero = 0;

$oldgrade = $RESULT->grade;

if ( count($_POST) > 0 ) {

    $thing1 = U::get($_POST,'thing1');
    $thing2 = U::get($_POST,'thing2');

    if ( strlen($thing1) < 1 || strlen($thing2) < 1 ) {
        $_SESSION['error'] = "You need non-blank strings";
        header('Location: '.addSession('index.php'));
        return;
    }

    if ( $thing1 == $thing2 ) {
        $_SESSION['error'] = "Identical strings *will* result in identical hashes - but do not solve the puzzle";
        header('Location: '.addSession('index.php'));
        return;
    }

    $hv1 = dohash($thing1,$offset, $zero, false);
    $hv2 = dohash($thing2,$offset, $zero, false);

    if ( $hv1 === false || $hv2 === false ) {
        $_SESSION['error'] = "Found illegal characters in your strings";
        header('Location: '.addSession('index.php'));
        return;
    }

    if ( $hv1 != $hv2 ) {
        $_SESSION['error'] = "Hash mismatch $hv1 $hv2";
        header('Location: '.addSession('index.php'));
        return;
    }

    $gradetosend = 1.0;
    $pg_PDO = false; // Don't send to pg4e_debug
    pg4e_grade_send($LAUNCH, $pg_PDO, $oldgrade, $gradetosend, $dueDate);

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}

$tries = U::get($_SESSION, 'tries', 0) + 1;
$_SESSION['tries'] = $tries;
if ( $oldgrade < 1 && $tries == 4 ) {
    echo('<h1 style="color:green;">Keep trying - you can do this.</h1>');
} else if ( $oldgrade < 1 && $tries == 6 ) {
    echo('<h1 style="color:green;">Hint: The number '.$offset.' is the key to this puzzle.</h1>');
} else if ( $oldgrade < 1 && ($tries % 10) == 0 ) {
    echo('<h1 style="color:green;">You might want to take a break and watch the video below.</h1>');
} else {
    echo('<h1>Puzzle: Break a Hashing Function</h1>');
}
?>
<p>In this assignment you will write a simple hashing function that uses addition
and multiplication and then find a pair of strings that will
return the same hash value for different strings (i.e you will cause a
<a href="https://en.wikipedia.org/wiki/Collision_(computer_science)" target="_blank">Hash Collision</a>).
</p>
<p>
The algorithm uses multiplication based on
the position of a letter in the hash to avoid a hash collision when two letters
are transposed like in 'ABCDE' and 'ABDCE'.  Your strings need to be at
least three characters long and no more than 10 characters long.
</p>
<p>
Enter your two strings below and check if they cause a collision:
<p>
<form method="post">
<p>
<input type="text" name="thing1" placeholder="String 1...">
<input type="text" name="thing2" placeholder="String 2...">
</p>
<input type="submit" id="submitbutton" class="btn btn-primary" name="submit" onclick="$('#submitbutton').hide();$('#submitspinner').show();return true;" value="Check for Collision">
<img id="submitspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
</form>
</p>
<p>
<?php
if ( $zero == 0 ) {
    $increment = "pos = ( pos + 1 ) % $offset ";
} else {
    $increment = "pos = ( pos % $offset ) + 1";
}
?>
Here is the code that computes your hash:
<pre>
while True:
    txt = input("Enter a string: ")
    if len(txt) &lt; 1 : break

    hv = 0
    pos = 0
    for let in txt:
        <?= $increment ?>  
        hv = (hv + (pos * ord(let))) % 1000000
        print(let, pos, ord(let), hv)

    print(hv, txt)
</pre>
For simplicity we will only use upper and lower case ASCII letters in our
text strings.
<pre>
<?php
$hv = dohash('ABCDE', $offset, $zero, true);
$hv = dohash('BACDE', $offset, $zero, true);
?>
</pre>
</p>
<p>
<b>Note:</b>
This is a puzzle assignment.  The answer is ultimately very simple.  All you need to do is <b>completely</b> understand
two lines of Python code.  It is easy to get the solution from another student
or perhaps ask StackOverflow.  But if you do that you deprive yourself of the satisfaction of solving the puzzle and
the learning moment that happens when you look at something, break it down, figure it out, and solve it on your own.
If you feel stuck - take a break then come back.
</p>
<p>
Here is a video to watch so you can take a break from working on this.
<a href="https://www.youtube.com/watch?v=eZNzMKS7zjo" target="_blank">Bruce Schneier: The Security Mindset</a>.
</p>
</p>

