<?php

$REGISTER_LTI2 = array(
"name" => "Elastic Search Management",
"FontAwesome" => "fa-server",
"short_name" => "ES Tool",
"description" => "This is a place holder for some ES management features.",
    // By default, accept launch messages..
    "messages" => array("launch"),
    "privacy_level" => "name_only",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English", "Spanish"
    ),
    "source_url" => "https://github.com/csev/pg4e",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    )

);
