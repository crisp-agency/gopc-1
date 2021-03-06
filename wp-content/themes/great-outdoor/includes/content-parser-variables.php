<?php

$title = get_the_title( $post->ID );
if( get_the_excerpt( $post->ID ) != null ){
    $body = get_the_excerpt( $post->ID );
    if( $body ){ $desc = strip_tags( substr( $body, 0, strpos( $body, ' ', 85 ))) . '...'; }
}else{
    $body = $post->post_content;
    if( $body ){ $desc = strip_tags( substr( $body, 0, strpos( $body, ' ',85 ))). '...'; }
}
$linkText = 'Read More';
$linkURL = get_the_permalink( $post->ID );
$imageID = get_post_thumbnail_id($post->ID);

if( $automate != true ){
    if( $fullWidth == true ){

        if( $section['customize_content'] ){

            $customTitle    = $section['custom_title'];
            $customDesc     = $section['custom_description'];
            $customLinkText = $section['custom_link_text'];
            $customLinkURL  = $section['custom_link_url'];
            $customImage    = $section['custom_image'];
            $customVideo    = $section['custom_video'];
            $mediaType      = $section['image_or_video'];
            $noURL          = $section['no_link_url'];

            if( $customTitle ){ $title = $customTitle; }
            if( $customDesc ){ $desc = $customDesc; }
            if( $customLinkText ){ $linkText = $customLinkText; }
            if( $customLinkURL ){ $linkURL = $customLinkURL; }
            if( $mediaType == 'image' ){
                gopc_bgimageID( $imageID, '#bg-' . $postCount );
               
                if( $customImage ){
                    gopc_bgimage( $customImage, '#bg-' . $postCount );
                }
            }else{
                gopc_videoBg( $customVideo, '#bg-' . $postCount );

            }
        }

    }else if( $item['customize_content'] ){

        $mediaType = $item['image_or_video'];
        if( $item['custom_title'] ){ $title = $item['custom_title']; }
        if( $item['custom_description'] ){ $desc = $item['custom_description']; }
        if( $item['custom_link_text'] ){ $linkText = $item['custom_link_text']; }
        if( $item['custom_link_url'] ){ $linkURL = $item['custom_link_url']; }
        gopc_bgimageID( $imageID, '#bg-' . $postCount );

        if( $item['custom_image'] ){
            gopc_bgimage( $item['custom_image'], '#bg-' . $postCount );
        }
        /*}else{
            if( $item['custom_video'] ){
                gopc_videoBg( $item['custom_video'], '#bg-' . $postCount );
            }
        }*/
    }else{
        $mediaType = '';
        gopc_bgimageID( $imageID, '#bg-' . $postCount );
    }
}else{
    gopc_bgimageID( $imageID, '#bg-' . $postCount );
}

// TODO -- TEST WHEN THERE IS NO MEDIA TYPE



?>
