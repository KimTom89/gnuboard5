<?php

// 상품명과 건수를 반환
function get_goods($cart_id)
{
    global $g5;

    // 상품명만들기
    $row = sql_fetch(" select a.it_id, b.it_name from {$g5['g5_shop_cart_table']} a, {$g5['g5_shop_item_table']} b where a.it_id = b.it_id and a.od_id = '$cart_id' order by ct_id limit 1 ");
    // 상품명에 "(쌍따옴표)가 들어가면 오류 발생함
    $goods['it_id'] = $row['it_id'];
    $goods['full_name']= $goods['name'] = addslashes($row['it_name']);
    // 특수문자제거
    $goods['full_name'] = preg_replace ("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "",  $goods['full_name']);

    // 상품건수
    $row = sql_fetch(" select count(*) as cnt from {$g5['g5_shop_cart_table']} where od_id = '$cart_id' ");
    $cnt = $row['cnt'] - 1;
    if ($cnt)
        $goods['full_name'] .= ' 외 '.$cnt.'건';
    $goods['count'] = $row['cnt'];

    return $goods;
}

//오늘본상품 데이터
function get_view_today_items($is_cache=false)
{
    global $g5;
    
    $tv_idx = get_session("ss_tv_idx");

    if( !$tv_idx ){
        return array();
    }

    static $cache = array();

    if( $is_cache && !empty($cache) ){
        return $cache;
    }

    for ($i=1;$i<=$tv_idx;$i++){

        $tv_it_idx = $tv_idx - ($i - 1);
        $tv_it_id = get_session("ss_tv[$tv_it_idx]");

        $rowx = get_shop_item($tv_it_id, true);
        if(!$rowx['it_id'])
            continue;
        
        $key = $rowx['it_id'];

        $cache[$key] = $rowx;
    }

    return $cache;
}

//오늘본상품 갯수 출력
function get_view_today_items_count()
{
    $tv_datas = get_view_today_items(true);

    return count($tv_datas);
}

// 품절상품인지 체크
function is_soldout($it_id, $is_cache=false)
{
    global $g5;

    static $cache = array();

    $it_id = preg_replace('/[^a-z0-9_\-]/i', '', $it_id);
    $key = md5($it_id);

    if( $is_cache && isset($cache[$key]) ){
        return $cache[$key];
    }

    // 상품정보
    $it = get_shop_item($it_id, $is_cache);

    if($it['it_soldout'] || $it['it_stock_qty'] <= 0)
        return true;

    $count = 0;
    $soldout = false;

    // 상품에 선택옵션 있으면..
    $sql = " select count(*) as cnt from {$g5['g5_shop_item_option_table']} where it_id = '$it_id' and io_type = '0' ";
    $row = sql_fetch($sql);

    if($row['cnt']) {
        $sql = " select io_id, io_type, io_stock_qty
                    from {$g5['g5_shop_item_option_table']}
                    where it_id = '$it_id'
                      and io_type = '0'
                      and io_use = '1' ";
        $result = sql_query($sql);

        for($i=0; $row=sql_fetch_array($result); $i++) {
            // 옵션 재고수량
            $stock_qty = get_option_stock_qty($it_id, $row['io_id'], $row['io_type']);

            if($stock_qty <= 0)
                $count++;
        }

        // 모든 선택옵션 품절이면 상품 품절
        if($i == $count)
            $soldout = true;
    } else {
        // 상품 재고수량
        $stock_qty = get_it_stock_qty($it_id);

        if($stock_qty <= 0)
            $soldout = true;
    }
    
    $cache[$key] = $soldout;

    return $soldout;
}

// 상품이미지에 유형 아이콘 출력
function item_icon($it)
{
    global $g5;

    $icon = '<span class="sit_icon">';

    if ($it['it_type1'])
        $icon .= '<span class="shop_icon shop_icon_1">히트</span>';

    if ($it['it_type2'])
        $icon .= '<span class="shop_icon shop_icon_2">추천</span>';

    if ($it['it_type3'])
        $icon .= '<span class="shop_icon shop_icon_3">최신</span>';

    if ($it['it_type4'])
        $icon .= '<span class="shop_icon shop_icon_4">인기</span>';

    if ($it['it_type5'])
        $icon .= '<span class="shop_icon shop_icon_5">할인</span>';


    // 쿠폰상품
    $sql = " select count(*) as cnt
                from {$g5['g5_shop_coupon_table']}
                where cp_start <= '".G5_TIME_YMD."'
                  and cp_end >= '".G5_TIME_YMD."'
                  and (
                        ( cp_method = '0' and cp_target = '{$it['it_id']}' )
                        OR
                        ( cp_method = '1' and ( cp_target IN ( '{$it['ca_id']}', '{$it['ca_id2']}', '{$it['ca_id3']}' ) ) )
                      ) ";
    $row = sql_fetch($sql);
    if($row['cnt'])
        $icon .= '<span class="shop_icon shop_icon_coupon">쿠폰</span>';

    $icon .= '</span>';

    return $icon;
}

// 상품 목록 : 관련 상품 출력
function relation_item($it_id, $width, $height, $rows=3)
{
    global $g5;

    $str = '';

    if(!$it_id)
        return $str;

    $sql = " select b.it_id, b.it_name, b.it_price, b.it_tel_inq from {$g5['g5_shop_item_relation_table']} a left join {$g5['g5_shop_item_table']} b on ( a.it_id2 = b.it_id ) where a.it_id = '$it_id' order by ir_no asc limit 0, $rows ";
    $result = sql_query($sql);

    for($i=0; $row=sql_fetch_array($result); $i++) {
        if($i == 0) {
            $str .= '<span class="sound_only">관련 상품 시작</span>';
            $str .= '<ul class="sct_rel_ul">';
        }

        $it_name = get_text($row['it_name']); // 상품명
        $it_price = get_price($row); // 상품가격
        if(!$row['it_tel_inq'])
            $it_price = display_price($it_price);

        $img = get_it_image($row['it_id'], $width, $height);

        $str .= '<li class="sct_rel_li"><a href="'.get_pretty_url('shop', $row['it_id']).'" class="sct_rel_a">'.$img.'</a></li>';
    }

    if($i > 0)
        $str .= '</ul><span class="sound_only">관련 상품 끝</span>';

    return $str;
}