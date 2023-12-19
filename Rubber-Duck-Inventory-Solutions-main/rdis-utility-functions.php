<?php
function generate_variants($matchedResult)
{
    $matchedResult = strtoupper($matchedResult); // Convert to uppercase
    $variants = [$matchedResult];
    $substitutions = [
        'S' => ['3', '5'],
        '3' => ['S', '5'],
        '5' => ['S', '3'],
        'L' => ['I', '1'], // Uppercase 'L'
        'I' => ['L', '1'], // Uppercase 'I'
        '1' => ['L', 'I'],
        'O' => ['Q', '0'], // Uppercase 'O'
        'Q' => ['O', '0'], // Uppercase 'Q'
        '0' => ['O', 'Q'],
    ];

    foreach ($substitutions as $char => $subs) {
        $new_variants = [];
        foreach ($variants as $variant) {
            foreach ($subs as $sub) {
                $new_variants[] = str_replace($char, $sub, $variant);
            }
        }
        $variants = array_merge($variants, $new_variants);
    }

    return array_unique($variants);
}

function rdis_custom_mime_types($mimes)
{
    $mimes['jpeg'] = 'image/jpeg';
    $mimes['jpg'] = 'image/jpeg';
    $mimes['png'] = 'image/png';
    $mimes['webp'] = 'image/webp';
    // Add other MIME types if needed
    return $mimes;
}
add_filter('upload_mimes', 'rdis_custom_mime_types', 1, 1);
