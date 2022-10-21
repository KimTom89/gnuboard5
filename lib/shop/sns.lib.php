<?php
// sns 공유하기
function get_sns_share_link($sns, $url, $title, $img)
{
    global $config;

    if(!$sns)
        return '';

    $str = '';
    switch($sns) {
        case 'facebook':
            $str = '<a href="https://www.facebook.com/sharer/sharer.php?u='.urlencode($url).'&amp;p='.urlencode($title).'" class="share-facebook" target="_blank"><img src="'.$img.'" alt="페이스북에 공유"></a>';
            break;
        case 'twitter':
            $str = '<a href="https://twitter.com/share?url='.urlencode($url).'&amp;text='.urlencode($title).'" class="share-twitter" target="_blank"><img src="'.$img.'" alt="트위터에 공유"></a>';
            break;
        case 'kakaotalk':
            if($config['cf_kakao_js_apikey'])
                $str = '<a href="javascript:kakaolink_send(\''.str_replace('+', ' ', urlencode($title)).'\', \''.urlencode($url).'\');" class="share-kakaotalk"><img src="'.$img.'" alt="카카오톡 링크보내기"></a>';
            break;
    }

    return $str;
}