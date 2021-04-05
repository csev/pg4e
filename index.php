<?php
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

require "top.php";
require "nav.php";

?>
<div id="container">
<div style="margin-left: 10px; float:right">
<iframe width="400" height="225" src="https://www.youtube.com/embed/flRUuodVPq0?rel=0" frameborder="0" allowfullscreen></iframe>
</div>
<h1>PostgreSQL for Everybody</h1>
<p>
This web site provides free / OER materials to help you
learn the <a href="https://www.postgresql.org/" target="_blank">PostgreSQL</a> database.
You can take this course and receive a certificate at:
<ul>
<li><a href="https://www.coursera.org/specializations/postgresql-for-everybody" target="_blank">Coursera: PostgreSQL for Everybody Specialization</a> </li>
</ul>
</p>
<p>
When you take this course we will provide you with a small PostgreSQL server with limited capabilities.
You will need a PostgreSQL client to run your SQL commands and send them to your PostgreSQL server.
All the examples in the course use the simple "least common demoninator" command line client called <b>psql</b>.
</p>
<p>
We have arranged to make it so you can run <b>psql</b> for this course using a completely free
account from <a href="assn/pg4e_pyaw_psql.md">PythonAnywhere</a>.
</p>
<p>
You can install a wide range of
<a href="https://wiki.postgresql.org/wiki/PostgreSQL_Clients" target="_blank">PostgreSQL Clients</a>
for your system if you like.  Some will work better than others with the limited database that we
give you for this course.
</p>
<h2>Technology</h2>
<p>
This site uses <a href="http://www.tsugi.org" target="_blank">Tsugi</a> 
framework to embed a learning 
management system into this site and handle the autograders.  
If you are interested in collaborating
to build these kinds of sites for yourself, please see the 
<a href="http://www.tsugi.org" target="_blank">tsugi.org</a> website.
<h3>Copyright</h3>
<p>
The material produced specifically for this site is by Charles Severance and others
and is Copyright Creative Commons Attribution 3.0 
unless otherwise indicated.  
</p>
<!--
<?php
echo("IP Address: ".Net::getIP()."\n");
echo(Output::safe_var_dump($_SESSION));
var_dump($USER);
?>
-->
</div>
<?php 
require "foot.php";
