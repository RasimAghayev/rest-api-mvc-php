<?php

function youtube($url, $width = 560, $height = 315, $fullscreen = true)
{
    parse_str(parse_url($url, PHP_URL_QUERY), $my_array_of_vars);
    $youtube = '<iframe allowtransparency="true" scrolling="no" width="' . $width . '" height="' . $height . '" src="//www.youtube.com/embed/' . $my_array_of_vars['v'] . '" frameborder="0"' . ($fullscreen ? ' allowfullscreen' : NULL) . '></iframe>';
    return $youtube;
}

// show youtube on my page
$url = 'http://www.youtube.com/watch?v=yvTd6XxgCBE';
echo youtube($url, 560, 315, true);

