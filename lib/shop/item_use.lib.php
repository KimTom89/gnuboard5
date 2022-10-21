<?php
// 상품후기 작성가능한지 체크
function check_itemuse_write($it_id, $mb_id, $close=true)
{
    global $g5, $default, $is_admin;

    if(!$is_admin && $default['de_item_use_write'])
    {
        $sql = " select count(*) as cnt
                    from {$g5['g5_shop_cart_table']}
                    where it_id = '$it_id'
                      and mb_id = '$mb_id'
                      and ct_status = '완료' ";
        $row = sql_fetch($sql);

        if($row['cnt'] == 0)
        {
            if($close)
                alert_close('사용후기는 주문이 완료된 경우에만 작성하실 수 있습니다.');
            else
                alert('사용후기는 주문하신 상품의 상태가 완료인 경우에만 작성하실 수 있습니다.');
        }
    }
}

// 사용후기의 확인된 건수를 상품테이블에 저장합니다.
function update_use_cnt($it_id)
{
    global $g5;
    $row = sql_fetch(" select count(*) as cnt from {$g5['g5_shop_item_use_table']} where it_id = '{$it_id}' and is_confirm = 1 ");
    return sql_query(" update {$g5['g5_shop_item_table']} set it_use_cnt = '{$row['cnt']}' where it_id = '{$it_id}' ");
}

// 사용후기 썸네일 생성
function get_itemuse_thumb($contents, $thumb_width, $thumb_height, $is_create=false, $is_crop=true, $crop_mode='center', $is_sharpen=true, $um_value='80/0.5/3'){
    
    global $config;

    $img = $filename = $alt = "";

    $matches = get_editor_image($contents, false);

    for($i=0; $i<count($matches[1]); $i++)
    {
        // 이미지 path 구함
        $p = parse_url($matches[1][$i]);
        if(strpos($p['path'], '/'.G5_DATA_DIR.'/') != 0)
            $data_path = preg_replace('/^\/.*\/'.G5_DATA_DIR.'/', '/'.G5_DATA_DIR, $p['path']);
        else
            $data_path = $p['path'];

        $srcfile = G5_PATH.$data_path;

        if(preg_match("/\.({$config['cf_image_extension']})$/i", $srcfile) && is_file($srcfile)) {
            $size = @getimagesize($srcfile);
            if(empty($size))
                continue;

            $filename = basename($srcfile);
            $filepath = dirname($srcfile);

            preg_match("/alt=[\"\']?([^\"\']*)[\"\']?/", $matches[0][$i], $malt);
            $alt = isset($malt[1]) ? get_text($malt[1]) : '';

            break;
        }
    }

    if($filename) {
        $thumb = thumbnail($filename, $filepath, $filepath, $thumb_width, $thumb_height, $is_create, $is_crop, $crop_mode, $is_sharpen, $um_value);

        if($thumb) {
            $src = G5_URL.str_replace($filename, $thumb, $data_path);
            $img = '<img src="'.$src.'" width="'.$thumb_width.'" height="'.$thumb_height.'" alt="'.$alt.'">';
        }
    }

    return $img;
}

// 사용후기에서 후기에 이미지가 있으면 썸네일을 리턴하며 후기에 이미지가 없으면 상품이미지를 리턴합니다.
function get_itemuselist_thumbnail($it_id, $contents, $thumb_width, $thumb_height, $is_create=false, $is_crop=true, $crop_mode='center', $is_sharpen=true, $um_value='80/0.5/3')
{
    global $g5, $config;
    $img = $filename = $alt = "";

    if($contents) {
        $img = get_itemuse_thumb($contents, $thumb_width, $thumb_height);
    }

    if(!$img)
        $img = get_it_image($it_id, $thumb_width, $thumb_height);

    return $img;
}