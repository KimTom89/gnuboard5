<?php
//위시리스트 데이터 가져오기
function get_wishlist_datas($mb_id, $is_cache=false)
{
    global $g5, $member;

    if( !$mb_id ){
        $mb_id = $member['mb_id'];

        if( !$mb_id ) return array();
    }

    static $cache = array();

    if( $is_cache && isset($cache[$mb_id]) ){
        return $cache[$mb_id];
    }

    $cache[$mb_id] = array();
    $sql  = " select a.it_id, b.it_name from {$g5['g5_shop_wish_table']} a, {$g5['g5_shop_item_table']} b ";
    $sql .= " where a.mb_id = '".$mb_id."' and a.it_id  = b.it_id order by a.wi_id desc ";
    $result = sql_query($sql);
    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        $key = $row['it_id'];
        $cache[$mb_id][$key] = $row;
    }

    return $cache[$mb_id];
}

//위시리스트 데이터 갯수 출력
function get_wishlist_datas_count($mb_id='')
{
    global $member;

    if( !$mb_id ){
        $mb_id = $member['mb_id'];

        if( !$mb_id ) return 0;
    }

    $wishlist_datas = get_wishlist_datas($mb_id, true);

    return is_array($wishlist_datas) ? count($wishlist_datas) : 0;
}

//각 상품에 대한 위시리스트 담은 갯수 출력
function get_wishlist_count_by_item($it_id='')
{
    global $g5;

    if( !$it_id ) return 0;

    $sql = "select count(a.it_id) as num from {$g5['g5_shop_wish_table']} a, {$g5['g5_shop_item_table']} b where a.it_id  = b.it_id and b.it_id = '$it_id'";

    $row = sql_fetch($sql);

    return (int) $row['num'];
}