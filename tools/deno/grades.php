<?php
require_once "../config.php";

use \Tsugi\Grades\GradeUtil;
use \Tsugi\Grades\UI;

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('Back'), 'index.php');

// $GRADE_DETAIL_CLASS, $done_href=false, $done_text=false, $menu=false

$GRADE_DETAIL_CLASS = new \Tsugi\Grades\SimpleGradeDetail();

UI::GradeTable($GRADE_DETAIL_CLASS, 'none', 'none', $menu);
