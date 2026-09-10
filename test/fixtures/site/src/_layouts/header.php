<?php
/**
 * prepros.before — prepended to every rendered page.
 * In scope: the `kirigami:` block ($project, $baseurl, $tagline, …) plus the
 * current page's PHPDOC annotations ($title, $abstract, …) and $relroot.
 */
$page_title = !empty($title) ? "{$title} — {$project}" : $project;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
</head>
<body>
    <header>
        <a href="<?php echo $relroot; ?>"><?php echo $project; ?></a>
    </header>
    <main>
